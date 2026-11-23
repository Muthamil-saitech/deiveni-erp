<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteCashRequest;
use App\Http\Requests\UpdateSiteCashRequest;
use App\Models\CashTransfer;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\SiteCashServiceInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use App\Services\IdCipherService;
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
        public WarehouseServiceInterface $warehouseService
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
        $purchaseOrdersQuery = PurchaseOrder::with(['items', 'projectSite', 'supplier'])
            ->whereIn('status', ['Order Received', 'Order Completed']);
        if ($user && ! $user->isAdmin()) {
            $purchaseOrdersQuery->where('created_by', $user->id);
        }
        $rawPurchaseOrders = $purchaseOrdersQuery
            ->orderByDesc('order_date')
            ->get();
        $purchaseOrders = $this->filterUnrecordedOrUpdatedPurchaseOrders($rawPurchaseOrders);
        $dailyAllocationsQuery = DailyAllocation::with(['items', 'projectSite', 'expenses']);
        if ($user && ! $user->isAdmin()) {
            $dailyAllocationsQuery->where('created_by', $user->id);
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
            $payableWage = max(0.0, (float) $alloc->total_labour_wage - $amPaidTotal);
            $alloc->setAttribute('am_paid_wage', $amPaidTotal);
            $alloc->setAttribute('site_payable_wage', $payableWage);

            if ($payableWage <= 0 && $amPaidTotal > 0) {
                // Entire allocation wage was paid by Account Manager from office cash
                return false;
            }

            if (! $matchingTxn) {
                $alloc->setAttribute('is_wage_updated', false);
                $alloc->setAttribute('is_wage_recorded', false);

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

        $user = $request->user();
        $query = PurchaseOrder::with(['items', 'projectSite', 'supplier'])
            ->whereIn('status', ['Order Received', 'Order Completed']);
        if ($user && ! $user->isAdmin()) {
            $query->where('created_by', $user->id);
        }

        if ($siteId) {
            $query->where('project_site_id', $siteId);
        }

        if ($supplierId) {
            $query->where(function ($q) use ($supplierId, $supplierName) {
                $q->where('supplier_id', $supplierId);
                if ($supplierName) {
                    $q->orWhere('supplier_name', $supplierName);
                }
            });
        } elseif ($supplierName) {
            $query->where('supplier_name', $supplierName);
        }

        $orders = $query->orderByDesc('order_date')->get();
        $filtered = $this->filterUnrecordedOrUpdatedPurchaseOrders($orders, $siteId ? (int) $siteId : null);

        return response()->json([
            'success' => true,
            'orders' => $filtered,
        ]);
    }

    /**
     * Filter purchase orders to exclude those already recorded in site cash,
     * unless the purchase order was updated after the cash transaction was recorded.
     *
     * @param  Collection<int, PurchaseOrder>  $orders
     * @return Collection<int, PurchaseOrder>
     */
    protected function filterUnrecordedOrUpdatedPurchaseOrders($orders, ?int $siteId = null)
    {
        $siteCashQuery = SiteCash::query();

        if ($siteId) {
            $siteCashQuery->where('project_site_id', $siteId);
        }

        $siteTransactions = $siteCashQuery->get();

        return $orders->filter(function (PurchaseOrder $po) use ($siteTransactions) {
            $poNumber = trim((string) $po->po_number);

            if (empty($poNumber)) {
                return true;
            }

            // Find matching site cash transactions for this PO number
            $matchingTxn = $siteTransactions
                ->where('project_site_id', $po->project_site_id)
                ->filter(function (SiteCash $txn) use ($poNumber) {
                    return $txn->purchase_order_no === $poNumber
                        || $txn->bill_no === $poNumber
                        || (str_starts_with($txn->bill_no ?? '', $poNumber))
                        || (str_contains($txn->description ?? '', $poNumber));
                })
                ->sortByDesc('updated_at')
                ->first();

            if (! $matchingTxn) {
                $po->setAttribute('is_po_updated', false);
                $po->setAttribute('is_po_recorded', false);

                return true;
            }

            // PO was previously recorded. Check if it was updated since.
            $amountDiff = abs((float) $po->total_amount - (float) $matchingTxn->amount);
            $isUpdated = ($po->updated_at && $matchingTxn->updated_at && $po->updated_at->gt($matchingTxn->updated_at))
                || $amountDiff >= 0.01;

            if ($isUpdated) {
                $po->setAttribute('is_po_updated', true);
                $po->setAttribute('is_po_recorded', true);
                $po->setAttribute('previous_amount_paid', (float) $matchingTxn->amount);

                return true;
            }

            // Already recorded in Site Cash and unmodified: exclude it
            return false;
        })->values();
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
            $site = ProjectSite::find($transaction->project_site_id);

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
        $site = ProjectSite::find($siteId);

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
