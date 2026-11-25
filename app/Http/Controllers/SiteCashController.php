<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteCashRequest;
use App\Http\Requests\UpdateSiteCashRequest;
use App\Models\CashTransfer;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\WeeklyWage;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\SiteCashServiceInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use App\Services\IdCipherService;
use App\Services\SupplierPurchaseOrderService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SiteCashController extends Controller
{
    public function __construct(
        public SiteCashServiceInterface $siteCashService,
        public ProjectSiteServiceInterface $projectSiteService,
        public WarehouseServiceInterface $warehouseService,
        public SupplierPurchaseOrderService $purchaseOrderService
    ) {}

    /**
     * Display the site cash management book.
     */
    public function index(Request $request): View|JsonResponse
    {
        $transactions = $this->siteCashService->getAllTransactions($request->all());
        $sites = $this->projectSiteService->getAllSites();
        $suppliers = Supplier::query()
            ->where('status', 'Active')
            ->with(['materials', 'materials.unit'])
            ->orderBy('name')
            ->get();
        $warehouseItems = $this->warehouseService->getInStockItems();
        $user = $request->user();
        $purchaseOrders = $this->purchaseOrderService->getPendingPurchaseOrders(
            isSiteBased: false,
            user: $user,
            scopeToUserSites: true,
        )['orders'];
        $dailyAllocationsQuery = DailyAllocation::with(['items', 'projectSite', 'expenses']);
        if ($user && ! $user->isAdmin()) {
            $dailyAllocationsQuery->whereHas('projectSite', function ($sq) use ($user) {
                $sq->where('created_by', $user->id);
            });
        }
        $rawDailyAllocations = $dailyAllocationsQuery
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();
        $dailyAllocations = $this->filterUnrecordedOrUpdatedAllocations($rawDailyAllocations);
        $selectedSiteId = $request->query('project_site_id') ?: $request->query('site_id');
        $stats = $this->siteCashService->getCashStats($selectedSiteId ? (int) $selectedSiteId : null);

        $pendingIncomingTransfers = collect();
        if ($user) {
            $pendingQuery = CashTransfer::with('projectSite')
                ->where('acceptance_status', 'pending');

            if ($user->isAdmin()) {
                $pendingIncomingTransfers = $pendingQuery->get();
            } elseif ($user->isSiteManager()) {
                $pendingIncomingTransfers = $pendingQuery
                    ->whereIn('transfer_direction', ['office_to_site', 'project_cash'])
                    ->whereHas('projectSite', function ($sq) use ($user) {
                        $sq->where('created_by', $user->id);
                    })
                    ->get();
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $transactions,
                'sites' => $sites,
                'suppliers' => $suppliers,
                'warehouse_items' => $warehouseItems,
                'purchase_orders' => $purchaseOrders,
                'daily_allocations' => $dailyAllocations,
                'stats' => $stats,
                'pending_transfers' => $pendingIncomingTransfers,
            ]);
        }

        return view('site-management.site-cash', [
            'transactions' => $transactions,
            'sites' => $sites,
            'suppliers' => $suppliers,
            'warehouseItems' => $warehouseItems,
            'purchaseOrders' => $purchaseOrders,
            'dailyAllocations' => $dailyAllocations,
            'stats' => $stats,
            'pendingIncomingTransfers' => $pendingIncomingTransfers,
        ]);
    }

    /**
     * Get daily allocations filtered by site.
     */
    public function getDailyAllocations(Request $request): JsonResponse
    {
        $siteId = $request->query('project_site_id');
        $user = $request->user();

        $query = DailyAllocation::with(['items', 'projectSite', 'expenses']);
        if ($user && ! $user->isAdmin()) {
            $query->where('created_by', $user->id);
        }

        if ($siteId) {
            $query->where('project_site_id', $siteId);
        }

        $allocations = $query->orderByDesc('date')->orderByDesc('id')->limit(100)->get();
        $filtered = $this->filterUnrecordedOrUpdatedAllocations($allocations, $siteId ? (int) $siteId : null);

        return response()->json([
            'success' => true,
            'allocations' => $filtered,
        ]);
    }

    /**
     * Get office cash wage payments made by Account Manager for a specific site,
     * including detailed trade breakdown (Painter, Electrician, Helpers) and return balance.
     */
    public function getOfficeCashWages(Request $request): JsonResponse
    {
        $siteId = (int) $request->query('project_site_id');
        if (! $siteId) {
            return response()->json([
                'success' => true,
                'total_office_cash_wages' => 0.0,
                'total_already_returned' => 0.0,
                'net_pending_return' => 0.0,
                'payments' => [],
            ]);
        }

        if ($request->user()?->isSiteManager() && ! ProjectSite::query()
            ->whereKey($siteId)
            ->where('created_by', $request->user()->id)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this project site.',
            ], 403);
        }

        // Query SiteCash wage transactions paid by Account Manager with office_cash
        $allWageTransactions = SiteCash::query()
            ->where('project_site_id', $siteId)
            ->where('paid_by', 'Account Manager')
            ->where('wage_cash_source', 'office_cash')
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $pendingWageTransactions = $allWageTransactions->whereNull('return_site_cash_id');
        $returnedWageTransactions = $allWageTransactions->whereNotNull('return_site_cash_id');

        // Query total balance returned to Account Manager for this site
        $totalReturnRecords = (float) SiteCash::query()
            ->where('project_site_id', $siteId)
            ->whereIn('transaction_type', ['Balance Returned', 'Balance Returned to AM', 'Balance Returned to Account Manager'])
            ->sum('out_amount');

        $totalOfficeCashWages = (float) $allWageTransactions->sum('amount');
        $totalAlreadyReturned = max((float) $returnedWageTransactions->sum('amount'), $totalReturnRecords);
        $netPendingReturn = max(0.0, $totalOfficeCashWages - $totalAlreadyReturned);

        $mapPayment = function (SiteCash $txn) {
            $dateFormatted = $txn->payment_date instanceof DateTimeInterface
                ? $txn->payment_date->format('d M Y')
                : Carbon::parse($txn->payment_date)->format('d M Y');

            // Gather item breakdown
            $items = [];
            $allocItems = collect();

            if ($txn->ledger_id) {
                $allocItems = DailyAllocationItem::where('ledger_id', $txn->ledger_id)
                    ->where('paid_by', 'Account Manager')
                    ->where('wage_cash_source', 'office_cash')
                    ->get();
            }

            if ($allocItems->isEmpty() && $txn->payment_date) {
                $allocItems = DailyAllocationItem::whereHas('dailyAllocation', function ($q) use ($txn) {
                    $q->where('project_site_id', $txn->project_site_id)
                        ->whereDate('date', $txn->payment_date);
                })
                    ->where('paid_by', 'Account Manager')
                    ->where('wage_cash_source', 'office_cash')
                    ->get();
            }

            foreach ($allocItems as $item) {
                $items[] = [
                    'trade' => $item->name_ta ?: $item->name_en,
                    'workers' => (int) $item->worker_count,
                    'wage' => (float) $item->daily_wage,
                    'subtotal' => (float) $item->subtotal,
                    'is_helper' => $item->category_code === 'EXTRA-HELPER',
                ];
            }

            // Fallback to extra_workers if no allocItems or helper in extra_workers
            if (empty($items) && ! empty($txn->extra_workers) && is_array($txn->extra_workers)) {
                foreach ($txn->extra_workers as $ew) {
                    $items[] = [
                        'trade' => ($ew['name'] ?? 'Extra Worker').(! empty($ew['work']) ? " ({$ew['work']})" : ''),
                        'workers' => 1,
                        'wage' => (float) ($ew['salary'] ?? 0),
                        'subtotal' => (float) ($ew['salary'] ?? 0),
                        'is_helper' => true,
                    ];
                }
            }

            return [
                'id' => $txn->id,
                'ledger_id' => $txn->ledger_id,
                'payment_date' => $txn->payment_date instanceof DateTimeInterface ? $txn->payment_date->format('Y-m-d') : (string) $txn->payment_date,
                'date_formatted' => $dateFormatted,
                'time' => $txn->payment_time ?? '',
                'amount' => (float) $txn->amount,
                'description' => $txn->description ?? '',
                'return_site_cash_id' => $txn->return_site_cash_id,
                'is_returned' => ! empty($txn->return_site_cash_id),
                'items' => $items,
            ];
        };

        $payments = $pendingWageTransactions->map($mapPayment)->values();
        $returnedPayments = $returnedWageTransactions->map($mapPayment)->values();

        return response()->json([
            'success' => true,
            'total_office_cash_wages' => $totalOfficeCashWages,
            'total_already_returned' => $totalAlreadyReturned,
            'net_pending_return' => $netPendingReturn,
            'payments' => $payments,
            'returned_payments' => $returnedPayments,
        ]);
    }

    /**
     * Get pending cash transfers for a specific site that can be returned or settled.
     */
    public function getCashTransfers(Request $request): JsonResponse
    {
        $siteId = (int) $request->query('project_site_id');
        if (! $siteId) {
            return response()->json([
                'success' => true,
                'transfers' => [],
                'total_pending' => 0.0,
            ]);
        }

        if ($request->user()?->isSiteManager() && ! ProjectSite::query()
            ->whereKey($siteId)
            ->where('created_by', $request->user()->id)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to access this project site.',
            ], 403);
        }

        $transfers = CashTransfer::query()
            ->where('project_site_id', $siteId)
            ->where('acceptance_status', 'accepted')
            ->where('settlement_status', 'pending')
            ->orderBy('transfer_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalPending = (float) $transfers->sum('amount');

        $mapped = $transfers->map(function (CashTransfer $ct) {
            $dateFormatted = $ct->transfer_date instanceof DateTimeInterface
                ? $ct->transfer_date->format('d M Y')
                : Carbon::parse($ct->transfer_date)->format('d M Y');

            return [
                'id' => $ct->id,
                'ref' => 'CTR-'.$ct->id,
                'date' => $dateFormatted,
                'time' => $ct->transfer_time ?: 'N/A',
                'direction' => $ct->transfer_direction,
                'direction_label' => $ct->directionLabel(),
                'amount' => (float) $ct->amount,
                'amount_formatted' => '₹'.number_format((float) $ct->amount, 2),
                'handed_over_by' => $ct->handed_over_by,
                'received_by' => $ct->received_by,
                'purpose' => $ct->purpose ?: 'General site expenses',
                'payment_mode' => $ct->payment_mode ?: 'Cash',
                'status' => $ct->settlement_status,
            ];
        });

        return response()->json([
            'success' => true,
            'transfers' => $mapped,
            'total_pending' => $totalPending,
        ]);
    }

    /**
     * Filter allocations to exclude those whose wages are already recorded in site cash,
     * unless the allocation was updated after the wage transaction was recorded.
     *
     * @param  Collection<int, DailyAllocation>  $allocations
     * @return Collection<int, DailyAllocation>
     */
    protected function filterUnrecordedOrUpdatedAllocations($allocations, ?int $siteId = null)
    {
        $siteCashQuery = SiteCash::query()
            ->whereIn('transaction_type', ['Daily Wage Payment', 'Daily Worker Wage Payment']);

        if ($siteId) {
            $siteCashQuery->where('project_site_id', $siteId);
        }

        $siteWageTransactions = $siteCashQuery->get();

        return $allocations->filter(function (DailyAllocation $alloc) use ($siteWageTransactions) {
            $allocDateStr = $alloc->date instanceof DateTimeInterface
                ? $alloc->date->format('Y-m-d')
                : (string) $alloc->date;
            $allocDateFormatted = $alloc->date instanceof DateTimeInterface
                ? $alloc->date->format('d M Y')
                : Carbon::parse($alloc->date)->format('d M Y');

            // Find matching wage transactions for this site and date
            $matchingTxn = $siteWageTransactions
                ->where('project_site_id', $alloc->project_site_id)
                ->filter(function (SiteCash $txn) use ($allocDateStr, $allocDateFormatted) {
                    $billNo = $txn->bill_no ?? '';
                    $desc = $txn->description ?? '';

                    if (str_starts_with($billNo, 'WAGE-') || str_starts_with($billNo, 'ALLOC-')) {
                        return str_contains($billNo, $allocDateStr);
                    }

                    if (str_contains($desc, $allocDateFormatted) || str_contains($desc, $allocDateStr)) {
                        return true;
                    }

                    $txnPaymentDate = $txn->payment_date instanceof DateTimeInterface
                        ? $txn->payment_date->format('Y-m-d')
                        : (string) $txn->payment_date;

                    return $txnPaymentDate === $allocDateStr;
                })
                ->sortByDesc('updated_at')
                ->first();

            $amPaidTotal = (float) $alloc->items->where('paid_by', 'Account Manager')->sum('subtotal');
            $smPaidTotal = (float) $alloc->items->where('paid_by', 'Site Manager')->sum('subtotal');
            $unpaidTotal = (float) $alloc->items->filter(fn ($it) => empty($it->paid_by))->sum('subtotal');
            $payableWage = $unpaidTotal > 0 || $smPaidTotal > 0 || $amPaidTotal > 0
                ? $unpaidTotal
                : max(0.0, (float) $alloc->total_labour_wage - $amPaidTotal);
            $alloc->setAttribute('am_paid_wage', $amPaidTotal);
            $alloc->setAttribute('sm_paid_wage', $smPaidTotal);
            $alloc->setAttribute('site_payable_wage', $payableWage);

            if ($payableWage <= 0.01 && ($amPaidTotal > 0 || $smPaidTotal > 0)) {
                // Entire allocation wage was paid (by Account Manager and/or Site Manager)
                return false;
            }

            if (! $matchingTxn) {
                $alloc->setAttribute('is_wage_updated', false);
                $alloc->setAttribute('is_wage_recorded', false);

                return true;
            }

            // If Site Manager previously paid specific items and unpaid items still remain, allow paying the remaining balance
            if ($smPaidTotal > 0 && $payableWage > 0.01) {
                $alloc->setAttribute('is_wage_updated', true);
                $alloc->setAttribute('is_wage_recorded', true);
                $alloc->setAttribute('previous_wage_paid', $smPaidTotal);

                return true;
            }

            // Allocation was recorded previously. Check if it was updated since.
            $wageDiff = abs($payableWage - (float) $matchingTxn->amount);
            $isUpdated = ($alloc->updated_at && $matchingTxn->updated_at && $alloc->updated_at->gt($matchingTxn->updated_at))
                || $wageDiff >= 0.01;

            if ($isUpdated) {
                $alloc->setAttribute('is_wage_updated', true);
                $alloc->setAttribute('is_wage_recorded', true);
                $alloc->setAttribute('previous_wage_paid', (float) $matchingTxn->amount);

                return true;
            }

            // Already recorded in Site Cash and unmodified: exclude it
            return false;
        })->values();
    }

    /**
     * Get purchase orders filtered by site and supplier.
     */
    public function getPurchaseOrders(Request $request): JsonResponse
    {
        $siteId = $request->query('project_site_id');
        $supplierId = $request->query('supplier_id');
        $supplierName = $request->query('supplier_name');
        $scope = $request->query('scope', 'site_based');
        $isSiteBased = $request->has('is_site_based')
            ? filter_var($request->query('is_site_based'), FILTER_VALIDATE_BOOLEAN)
            : ($scope !== 'total');

        $result = $this->purchaseOrderService->getPendingPurchaseOrders(
            siteId: $siteId ? (int) $siteId : null,
            supplierId: $supplierId ? (int) $supplierId : null,
            supplierName: $supplierName ? (string) $supplierName : null,
            isSiteBased: $isSiteBased,
            user: $request->user(),
            scopeToUserSites: true,
        );

        return response()->json([
            'success' => true,
            'orders' => $result['orders'],
            'total_pending_amount' => $result['total_pending_amount'],
            'pending_count' => $result['pending_count'],
        ]);
    }

    /**
     * Get settled weekly wage periods for a project site with daily SiteCash deductions.
     */
    public function getWeeklySettlements(Request $request): JsonResponse
    {
        $siteId = $request->query('project_site_id');
        if (! $siteId) {
            return response()->json([
                'success' => true,
                'settlements' => [],
            ]);
        }

        $user = $request->user();
        $site = ProjectSite::find((int) $siteId);
        if (! $site || ($user && ! $user->isAdmin() && (int) $site->created_by !== (int) $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to project site.',
                'settlements' => [],
            ], 403);
        }

        $settledWeeks = WeeklyWage::with(['expenses'])
            ->where('project_site_id', (int) $siteId)
            ->where('status', 'settled')
            ->orderByDesc('week_start_date')
            ->get();

        $settlements = $settledWeeks->map(function (WeeklyWage $ww) {
            $summary = $this->computeWeeklySettlementSummary($ww);
            $startFormatted = Carbon::parse($ww->week_start_date)->format('d M Y');
            $endFormatted = Carbon::parse($ww->week_end_date)->format('d M Y');

            return [
                'id' => $ww->id,
                'encrypted_id' => $ww->encrypted_id,
                'week_start_date' => Carbon::parse($ww->week_start_date)->toDateString(),
                'week_end_date' => Carbon::parse($ww->week_end_date)->toDateString(),
                'date_range_label' => "{$startFormatted} – {$endFormatted}",
                'net_payout' => $summary['net_payout'],
                'daily_paid_amount' => $summary['daily_paid_amount'],
                'weekly_already_settled' => $summary['weekly_already_settled'],
                'balance_amount' => $summary['balance_amount'],
                'view_url' => route('site-cash.weekly-settlement.show', $ww->encrypted_id),
            ];
        })->filter(fn (array $s) => $s['balance_amount'] > 0.01)->values();

        return response()->json([
            'success' => true,
            'settlements' => $settlements,
        ]);
    }

    /**
     * Show detailed breakdown of a settled Weekly Wage for Site Cash Handling (target="_blank").
     */
    public function showWeeklySettlement(Request $request, string $encryptedId): View|RedirectResponse
    {
        $id = is_numeric($encryptedId) ? (int) $encryptedId : IdCipherService::decrypt($encryptedId);
        $weeklyWage = WeeklyWage::with(['projectSite.client', 'expenses'])->find($id);

        if (! $weeklyWage) {
            return redirect()->route('site-cash.index')->with('error', 'Weekly settlement record not found.');
        }

        $user = $request->user();
        if ($user && ! $user->isAdmin() && (int) ($weeklyWage->projectSite?->created_by) !== (int) $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $summary = $this->computeWeeklySettlementSummary($weeklyWage);

        return view('site-management.weekly-settlement-detail', [
            'weeklyWage' => $weeklyWage,
            'site' => $weeklyWage->projectSite,
            'summary' => $summary,
        ]);
    }

    /**
     * Compute settled weekly payout, daily SiteCash payments made within that week, and remaining balance.
     *
     * @return array{
     *     net_payout: float,
     *     daily_paid_amount: float,
     *     weekly_already_settled: float,
     *     balance_amount: float,
     *     allocations: Collection<int, DailyAllocation>,
     *     paid_dates_map: array<string, float>,
     *     daily_wage_txns: Collection<int, SiteCash>,
     *     total_workers: int,
     *     total_labour_wage: float,
     *     total_site_expenses: float,
     *     worker_summary: array<int, array{label: string, name_en: string, total_workers: int, days_count: int, total_wage: float}>,
     *     expense_summary: array<int, array{date_label: string, source: string, item_name: string, quantity: float, unit_price: float, amount: float}>
     * }
     */
    protected function computeWeeklySettlementSummary(WeeklyWage $weeklyWage): array
    {
        $siteId = (int) $weeklyWage->project_site_id;
        $startStr = Carbon::parse($weeklyWage->week_start_date)->toDateString();
        $endStr = Carbon::parse($weeklyWage->week_end_date)->toDateString();

        $allocations = DailyAllocation::with(['items', 'expenses'])
            ->where('project_site_id', $siteId)
            ->whereBetween('date', [$startStr, $endStr])
            ->orderBy('date')
            ->get();

        $dailyWageTxns = SiteCash::query()
            ->where('project_site_id', $siteId)
            ->whereIn('transaction_type', ['Daily Wage Payment', 'Daily Worker Wage Payment'])
            ->where(function ($q) {
                $q->whereNull('paid_by')
                    ->orWhere('paid_by', '!=', 'Account Manager');
            })
            ->get();

        $paidDatesMap = [];
        $matchedTxns = collect();

        foreach ($dailyWageTxns as $txn) {
            $resolvedDate = null;
            $billNo = (string) ($txn->bill_no ?? '');
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $billNo, $m)) {
                $resolvedDate = $m[1];
            } elseif ($txn->payment_date) {
                $resolvedDate = Carbon::parse($txn->payment_date)->toDateString();
            }

            if ($resolvedDate && $resolvedDate >= $startStr && $resolvedDate <= $endStr) {
                $paidDatesMap[$resolvedDate] = ($paidDatesMap[$resolvedDate] ?? 0.0) + (float) $txn->amount;
                $matchedTxns->push($txn);
            }
        }

        $dailyPaidAmount = round((float) array_sum($paidDatesMap), 2);

        $weeklyAlreadySettled = (float) SiteCash::query()
            ->where('project_site_id', $siteId)
            ->where('weekly_wage_id', $weeklyWage->id)
            ->where('transaction_type', 'Weekly Settlement')
            ->sum('amount');

        $netPayout = round((float) $weeklyWage->net_payout, 2);
        $balanceAmount = max(0.0, round($netPayout - $dailyPaidAmount - $weeklyAlreadySettled, 2));

        $workerCategoriesMap = [];
        $totalWorkers = 0;
        $totalLabourWage = 0.0;
        $expenseSummary = [];
        $totalSiteExpenses = 0.0;

        foreach ($allocations as $alloc) {
            $dateStr = Carbon::parse($alloc->date)->toDateString();
            $dateLabel = Carbon::parse($alloc->date)->format('d M Y');

            foreach ($alloc->items as $item) {
                $catKey = (string) ($item->worker_category_id ?: ($item->name_ta ?: $item->name_en));
                if (! isset($workerCategoriesMap[$catKey])) {
                    $workerCategoriesMap[$catKey] = [
                        'label' => (string) ($item->name_ta ?: $item->name_en),
                        'name_en' => (string) ($item->name_en ?? ''),
                        'total_workers' => 0,
                        'dates' => [],
                        'total_wage' => 0.0,
                    ];
                }

                $count = (int) $item->worker_count;
                $subtotal = (float) $item->subtotal;
                $workerCategoriesMap[$catKey]['total_workers'] += $count;
                $workerCategoriesMap[$catKey]['dates'][$dateStr] = true;
                $workerCategoriesMap[$catKey]['total_wage'] += $subtotal;

                $totalWorkers += $count;
                $totalLabourWage += $subtotal;
            }

            foreach ($alloc->expenses as $exp) {
                $amount = (float) $exp->amount;
                $totalSiteExpenses += $amount;
                $expenseSummary[] = [
                    'date_label' => $dateLabel,
                    'source' => 'Daily Allocation',
                    'item_name' => (string) $exp->item_name,
                    'quantity' => (float) ($exp->quantity ?? 1),
                    'unit_price' => (float) ($exp->unit_price ?? $amount),
                    'amount' => round($amount, 2),
                ];
            }
        }

        foreach ($weeklyWage->expenses as $wExp) {
            $amount = (float) $wExp->amount;
            $totalSiteExpenses += $amount;
            $expenseSummary[] = [
                'date_label' => 'Weekly',
                'source' => 'Weekly Expense',
                'item_name' => (string) $wExp->item_name,
                'quantity' => (float) ($wExp->quantity ?? 1),
                'unit_price' => (float) ($wExp->unit_price ?? $amount),
                'amount' => round($amount, 2),
            ];
        }

        $workerSummary = collect($workerCategoriesMap)
            ->map(function (array $row): array {
                return [
                    'label' => $row['label'],
                    'name_en' => $row['name_en'],
                    'total_workers' => $row['total_workers'],
                    'days_count' => count($row['dates']),
                    'total_wage' => round($row['total_wage'], 2),
                ];
            })
            ->sortByDesc('total_workers')
            ->values()
            ->all();

        return [
            'net_payout' => $netPayout,
            'daily_paid_amount' => $dailyPaidAmount,
            'weekly_already_settled' => round($weeklyAlreadySettled, 2),
            'balance_amount' => $balanceAmount,
            'allocations' => $allocations,
            'paid_dates_map' => $paidDatesMap,
            'daily_wage_txns' => $matchedTxns,
            'total_workers' => $totalWorkers,
            'total_labour_wage' => round($totalLabourWage, 2),
            'total_site_expenses' => round($totalSiteExpenses, 2),
            'worker_summary' => $workerSummary,
            'expense_summary' => $expenseSummary,
        ];
    }

    /**
     * Display the specified site cash transaction record.
     */
    public function show(Request $request, string $encryptedId): View|JsonResponse
    {
        $id = IdCipherService::decrypt($encryptedId);

        if (! $id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cash transaction record not found.',
                ], 404);
            }

            abort(404, 'Cash transaction record not found.');
        }

        $transaction = $this->siteCashService->getTransactionById($id);

        if (! $transaction) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cash transaction record not found.',
                ], 404);
            }

            abort(404, 'Cash transaction record not found.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $transaction,
            ]);
        }

        $sites = $this->projectSiteService->getAllSites();
        $suppliers = Supplier::query()->where('status', 'Active')->orderBy('name')->get();

        return view('site-management.site-cash-detail', [
            'transaction' => $transaction,
            'id' => $id,
            'sites' => $sites,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Update the specified site cash transaction.
     * Recalculates project_sites.spent automatically for expenses and site transfers.
     */
    public function update(UpdateSiteCashRequest $request, int|string $id): JsonResponse|RedirectResponse
    {
        $numericId = IdCipherService::decrypt((string) $id) ?: (int) $id;
        $payload = $request->validated();

        if ($request->hasFile('receipt_image')) {
            $payload['receipt_image'] = $request->file('receipt_image');
        } elseif ($request->filled('image')) {
            $payload['receipt_image'] = $request->input('image');
        }

        $transaction = $this->siteCashService->updateTransaction($numericId, $payload);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cash transaction updated successfully! (தள ரொக்க பதிவு வெற்றிகரமாக திருத்தப்பட்டது)',
                'data' => $transaction,
                'encrypted_id' => $transaction->encrypted_id,
            ]);
        }

        return redirect()->route('site-cash.show', $transaction->encrypted_id)
            ->with('success', 'Cash transaction updated successfully! (தள ரொக்க பதிவு வெற்றிகரமாக திருத்தப்பட்டது)');
    }

    /**
     * Store a newly created site cash transaction.
     * Updates project_sites.spent automatically for expenses.
     */
    public function store(StoreSiteCashRequest $request): JsonResponse|RedirectResponse
    {
        $payload = $request->validated();

        if ($request->hasFile('receipt_image')) {
            $payload['receipt_image'] = $request->file('receipt_image');
        } elseif ($request->filled('image')) {
            $payload['receipt_image'] = $request->input('image');
        }

        $transaction = $this->siteCashService->createTransaction($payload);

        if ($request->wantsJson() || $request->ajax()) {
            $site = $transaction->project_site_id ? ProjectSite::find($transaction->project_site_id) : null;

            return response()->json([
                'success' => true,
                'message' => 'Transaction of ₹'.number_format((float) $transaction->amount, 2).' recorded successfully!',
                'data' => $transaction,
                'stats' => $this->siteCashService->getCashStats(),
                'site' => $site,
            ], 201);
        }

        return redirect()->route('site-cash.index')
            ->with('success', 'Transaction of ₹'.number_format((float) $transaction->amount, 2).' recorded successfully!');
    }

    /**
     * Remove the specified site cash transaction record.
     * Recalculates project_sites.spent automatically.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $transaction = $this->siteCashService->getTransactionById($id);

        if (! $transaction) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cash transaction record not found.',
                ], 404);
            }

            return redirect()->route('site-cash.index')->with('error', 'Cash transaction record not found.');
        }

        $siteId = $transaction->project_site_id;
        $this->siteCashService->deleteTransaction($id);
        $site = $siteId ? ProjectSite::find($siteId) : null;

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Cash transaction removed successfully.',
                'stats' => $this->siteCashService->getCashStats(),
                'site' => $site,
            ]);
        }

        return redirect()->route('site-cash.index')->with('success', 'Cash transaction removed successfully.');
    }
}
