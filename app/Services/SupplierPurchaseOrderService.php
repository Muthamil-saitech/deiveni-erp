<?php

namespace App\Services;

use App\Models\AdminLedger;
use App\Models\Ledger;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\User;
use Illuminate\Support\Collection;

class SupplierPurchaseOrderService
{
    /**
     * Get pending (unpaid or partially paid / updated) purchase orders and total pending amount
     * for a supplier across Site Cash, Admin Ledger, and Daily Ledger payments.
     *
     * @return array{orders: Collection<int, PurchaseOrder>, total_pending_amount: float, pending_count: int}
     */
    public function getPendingPurchaseOrders(
        ?int $siteId = null,
        ?int $supplierId = null,
        ?string $supplierName = null,
        bool $isSiteBased = true,
        ?User $user = null,
        bool $scopeToUserSites = false,
    ): array {
        $query = PurchaseOrder::with(['items', 'projectSite', 'supplier'])
            ->whereIn('status', ['Order Received', 'Order Completed']);

        if ($supplierId) {
            $query->where(function ($q) use ($supplierId, $supplierName) {
                $q->where('supplier_id', $supplierId);
                if ($supplierName) {
                    $q->orWhere('supplier_name', $supplierName);
                }
            });
        } elseif ($supplierName) {
            $query->where('supplier_name', $supplierName);
        } elseif ($isSiteBased && $siteId) {
            $query->where('project_site_id', $siteId);
        }

        // Order oldest-first for accurate FIFO lump-sum allocation
        $allOrders = $query->orderBy('order_date')->orderBy('id')->get();

        // 1. Fetch SiteCash and AdminLedger supplier payments
        $siteCashTxns = $this->fetchSiteCashPayments($supplierId, $supplierName, $siteId, $isSiteBased);
        $adminLedgerTxns = $this->fetchAdminLedgerPayments($supplierId, $supplierName, $siteId, $isSiteBased);

        // 2. Exclude or mark POs that already have direct PO-matched payments
        $unpaidOrUpdated = $this->filterDirectPoPayments($allOrders, $siteCashTxns, $adminLedgerTxns);

        // 3. Apply lump-sum (non-PO) payments FIFO across remaining POs when a supplier is specified
        if ($supplierId || $supplierName) {
            $unpaidOrUpdated = $this->applyLumpSumPayments(
                $unpaidOrUpdated,
                $siteCashTxns,
                $adminLedgerTxns,
                $supplierId,
                $supplierName
            );
        }

        // 4. Apply final Site-Based and User-Site visibility filters
        $filtered = $unpaidOrUpdated->filter(function (PurchaseOrder $po) use ($isSiteBased, $siteId, $scopeToUserSites, $user) {
            if ($isSiteBased && $siteId && (int) $po->project_site_id !== $siteId) {
                return false;
            }

            if ($scopeToUserSites && $user && ! $user->isAdmin()) {
                return (int) ($po->projectSite?->created_by) === (int) $user->id;
            }

            return true;
        })->sortByDesc(function (PurchaseOrder $po) {
            return [$po->order_date?->format('Y-m-d') ?? '', $po->id];
        })->values();

        $totalPendingAmount = (float) $filtered->sum(function (PurchaseOrder $po) {
            $paid = (float) ($po->getAttribute('previous_amount_paid') ?? 0);

            return max(0.0, (float) $po->total_amount - $paid);
        });

        return [
            'orders' => $filtered,
            'total_pending_amount' => round($totalPendingAmount, 2),
            'pending_count' => $filtered->count(),
        ];
    }

    /**
     * Fetch SiteCash transactions relevant to supplier/site PO matching.
     *
     * @return Collection<int, SiteCash>
     */
    protected function fetchSiteCashPayments(?int $supplierId, ?string $supplierName, ?int $siteId, bool $isSiteBased): Collection
    {
        $query = SiteCash::query();

        if ($supplierId || $supplierName) {
            $query->where(function ($q) use ($supplierId, $supplierName) {
                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
                if ($supplierName) {
                    $q->orWhere('supplier_name', $supplierName);
                }
                $q->orWhereNotNull('purchase_order_no');
            });
        } elseif ($isSiteBased && $siteId) {
            $query->where('project_site_id', $siteId);
        }

        return $query->get();
    }

    /**
     * Fetch AdminLedger supplier payment entries.
     *
     * @return Collection<int, AdminLedger>
     */
    protected function fetchAdminLedgerPayments(?int $supplierId, ?string $supplierName, ?int $siteId, bool $isSiteBased): Collection
    {
        $query = AdminLedger::query()->where('category', 'supplier_payment');

        if ($supplierId || $supplierName) {
            $query->where(function ($q) use ($supplierId, $supplierName) {
                if ($supplierId) {
                    $q->where('supplier_id', $supplierId);
                }
                if ($supplierName) {
                    $q->orWhere('supplier_name', $supplierName);
                }
            });
        } elseif ($isSiteBased && $siteId) {
            $query->where('project_site_id', $siteId);
        }

        return $query->get();
    }

    /**
     * Filter out POs that have been fully paid by direct PO reference in SiteCash or AdminLedger.
     *
     * @param  Collection<int, PurchaseOrder>  $orders
     * @param  Collection<int, SiteCash>  $siteCashTxns
     * @param  Collection<int, AdminLedger>  $adminLedgerTxns
     * @return Collection<int, PurchaseOrder>
     */
    protected function filterDirectPoPayments(Collection $orders, Collection $siteCashTxns, Collection $adminLedgerTxns): Collection
    {
        return $orders->filter(function (PurchaseOrder $po) use ($siteCashTxns, $adminLedgerTxns) {
            $poNumber = trim((string) $po->po_number);

            if ($poNumber === '') {
                return true;
            }

            $scMatches = $siteCashTxns
                ->where('project_site_id', $po->project_site_id)
                ->filter(function (SiteCash $txn) use ($poNumber) {
                    return $txn->purchase_order_no === $poNumber
                        || $txn->bill_no === $poNumber
                        || str_starts_with($txn->bill_no ?? '', $poNumber)
                        || str_contains($txn->description ?? '', $poNumber);
                });

            $alMatches = $adminLedgerTxns->filter(function (AdminLedger $entry) use ($poNumber, $po) {
                if (trim((string) $entry->purchase_order_no) !== $poNumber) {
                    return false;
                }

                return $entry->project_site_id === null || (int) $entry->project_site_id === (int) $po->project_site_id;
            });

            if ($scMatches->isEmpty() && $alMatches->isEmpty()) {
                $po->setAttribute('is_po_updated', false);
                $po->setAttribute('is_po_recorded', false);
                $po->setAttribute('previous_amount_paid', 0.0);

                return true;
            }

            $totalPaidForPo = (float) $scMatches->sum('amount') + (float) $alMatches->sum('amount');

            $latestUpdatedAt = collect()
                ->merge($scMatches->pluck('updated_at'))
                ->merge($alMatches->pluck('updated_at'))
                ->filter()
                ->max();

            $isUpdated = ($po->updated_at && $latestUpdatedAt && $po->updated_at->gt($latestUpdatedAt))
                || ($totalPaidForPo < ((float) $po->total_amount - 0.01));

            if ($isUpdated) {
                $po->setAttribute('is_po_updated', true);
                $po->setAttribute('is_po_recorded', true);
                $po->setAttribute('previous_amount_paid', round($totalPaidForPo, 2));

                return true;
            }

            return false;
        })->values();
    }

    /**
     * Deduct unallocated (non-PO) supplier payments FIFO across remaining unpaid POs.
     *
     * @param  Collection<int, PurchaseOrder>  $orders
     * @param  Collection<int, SiteCash>  $siteCashTxns
     * @param  Collection<int, AdminLedger>  $adminLedgerTxns
     * @return Collection<int, PurchaseOrder>
     */
    protected function applyLumpSumPayments(
        Collection $orders,
        Collection $siteCashTxns,
        Collection $adminLedgerTxns,
        ?int $supplierId,
        ?string $supplierName
    ): Collection {
        $siteLumpSums = [];
        $globalLumpSum = 0.0;

        $matchesSupplier = function ($record) use ($supplierId, $supplierName): bool {
            if ($supplierId && (int) $record->supplier_id === $supplierId) {
                return true;
            }

            return $supplierName && trim((string) $record->supplier_name) === trim($supplierName);
        };

        // AdminLedger non-PO supplier payments
        foreach ($adminLedgerTxns as $entry) {
            if (! $matchesSupplier($entry)) {
                continue;
            }
            if (trim((string) $entry->purchase_order_no) !== '') {
                continue;
            }
            $amt = (float) $entry->amount;
            if ($entry->is_site_based && $entry->project_site_id) {
                $siteId = (int) $entry->project_site_id;
                $siteLumpSums[$siteId] = ($siteLumpSums[$siteId] ?? 0.0) + $amt;
            } else {
                $globalLumpSum += $amt;
            }
        }

        // SiteCash non-PO supplier payments
        foreach ($siteCashTxns as $txn) {
            if ($txn->transaction_type !== 'Supplier Payment' || ! $matchesSupplier($txn)) {
                continue;
            }
            if (trim((string) $txn->purchase_order_no) !== '') {
                continue;
            }
            // Also skip if bill_no or description references a PO number
            $hasPoRef = $orders->contains(function (PurchaseOrder $po) use ($txn) {
                $poNumber = trim((string) $po->po_number);

                return $poNumber !== '' && (
                    $txn->bill_no === $poNumber
                    || str_starts_with($txn->bill_no ?? '', $poNumber)
                    || str_contains($txn->description ?? '', $poNumber)
                );
            });
            if ($hasPoRef) {
                continue;
            }

            $amt = (float) $txn->amount;
            if ($txn->is_site_based && $txn->project_site_id) {
                $siteId = (int) $txn->project_site_id;
                $siteLumpSums[$siteId] = ($siteLumpSums[$siteId] ?? 0.0) + $amt;
            } else {
                $globalLumpSum += $amt;
            }
        }

        // Daily Ledger supplier payments (always global / no PO)
        $ledgerQuery = Ledger::query()
            ->where('source', 'Supplier')
            ->where('flow', 'expense');
        if ($supplierId) {
            $ledgerQuery->where(function ($q) use ($supplierId, $supplierName) {
                $q->where('supplier_id', $supplierId);
                if ($supplierName) {
                    $q->orWhere('supplier_name', $supplierName);
                }
            });
        } elseif ($supplierName) {
            $ledgerQuery->where('supplier_name', $supplierName);
        }
        $globalLumpSum += (float) $ledgerQuery->sum('amount');

        if ($globalLumpSum <= 0.0 && empty($siteLumpSums)) {
            return $orders;
        }

        return $orders->filter(function (PurchaseOrder $po) use (&$siteLumpSums, &$globalLumpSum) {
            $alreadyPaid = (float) ($po->getAttribute('previous_amount_paid') ?? 0.0);
            $remainingPo = max(0.0, (float) $po->total_amount - $alreadyPaid);

            if ($remainingPo <= 0.01) {
                return false;
            }

            $siteId = (int) $po->project_site_id;
            $covered = 0.0;

            if (($siteLumpSums[$siteId] ?? 0.0) > 0.0) {
                $take = min($remainingPo, $siteLumpSums[$siteId]);
                $siteLumpSums[$siteId] -= $take;
                $remainingPo -= $take;
                $covered += $take;
            }

            if ($remainingPo > 0.01 && $globalLumpSum > 0.0) {
                $take = min($remainingPo, $globalLumpSum);
                $globalLumpSum -= $take;
                $remainingPo -= $take;
                $covered += $take;
            }

            if ($remainingPo <= 0.01) {
                return false;
            }

            if ($covered > 0.0) {
                $po->setAttribute('previous_amount_paid', round($alreadyPaid + $covered, 2));
                $po->setAttribute('is_po_recorded', true);
                $po->setAttribute('is_po_updated', true);
            }

            return true;
        })->values();
    }
}
