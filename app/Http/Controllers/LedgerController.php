<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLedgerRequest;
use App\Http\Requests\UpdateLedgerRequest;
use App\Models\Bank;
use App\Models\DailyAllocation;
use App\Models\ProjectSite;
use App\Models\Property;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Services\Contracts\LedgerServiceInterface;
use App\Services\Contracts\TransactionTypeServiceInterface;
use App\Services\IdCipherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function __construct(
        public LedgerServiceInterface $ledgerService,
        public TransactionTypeServiceInterface $transactionTypeService
    ) {}

    /**
     * Display the Daily Ledger page or return entries JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'source' => $request->query('source'),
            'flow' => $request->query('flow'),
            'project_site_id' => $request->query('site_id'),
            'search' => $request->query('search'),
        ];

        $ledgers = $this->ledgerService->getAllLedgers($filters);
        $summary = $this->ledgerService->getSummary();
        $ledgerBalances = $this->ledgerService->getRunningBalances();

        $dayTotals = [];
        foreach ($ledgers as $ledger) {
            $dateKey = $ledger->entry_date instanceof \DateTimeInterface
                ? $ledger->entry_date->format('Y-m-d')
                : (string) $ledger->entry_date;

            if (! isset($dayTotals[$dateKey])) {
                $dayTotals[$dateKey] = [
                    'income' => 0.0,
                    'expense' => 0.0,
                ];
            }

            if ($ledger->flow === 'income') {
                $dayTotals[$dateKey]['income'] += (float) $ledger->amount;
            } else {
                $dayTotals[$dateKey]['expense'] += (float) $ledger->amount;
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $ledgers,
                'summary' => $summary,
                'day_totals' => $dayTotals,
                'running_balances' => $ledgerBalances,
            ]);
        }

        $activeBanks = Bank::where('status', 'Active')
            ->with(['branches' => fn ($q) => $q->where('status', 'Active')->orderBy('branch_name')])
            ->orderBy('bank_name')
            ->get();

        $activeProperties = Property::where('status', 'Active')
            ->with(['propertyTypes' => fn ($q) => $q->where('status', 'Active')->orderBy('shop_home_office_name')])
            ->orderBy('property_name')
            ->get();

        $activeSuppliers = Supplier::where('status', 'Active')
            ->orderBy('name')
            ->get();

        $user = $request->user();
        $activeProjectSitesQuery = ProjectSite::with('client')
            ->whereNotIn('status', ['Completed', 'completed']);
        if ($user && ! $user->isAdmin() && ! $user->isAccountManager()) {
            $activeProjectSitesQuery->where('created_by', $user->id);
        }
        $activeProjectSites = $activeProjectSitesQuery
            ->orderBy('name')
            ->get();

        $activeTransactionTypes = TransactionType::active()
            ->orderBy('name_en')
            ->get();

        return view('accounts.ledger', compact(
            'ledgers',
            'summary',
            'dayTotals',
            'ledgerBalances',
            'activeBanks',
            'activeProperties',
            'activeSuppliers',
            'activeProjectSites',
            'activeTransactionTypes'
        ));
    }

    /**
     * Store a newly created ledger entry.
     */
    public function store(StoreLedgerRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image');
        } elseif ($request->filled('image')) {
            $validated['receipt_image'] = $request->input('image');
        }

        $ledger = $this->ledgerService->createLedger($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Ledger entry recorded successfully!',
                'data' => $ledger,
                'summary' => $this->ledgerService->getSummary(),
            ], 201);
        }

        return redirect()->route('ledger.index')->with('success', 'Ledger entry recorded successfully!');
    }

    /**
     * Update the specified ledger entry.
     */
    public function update(UpdateLedgerRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('receipt_image')) {
            $validated['receipt_image'] = $request->file('receipt_image');
        } elseif ($request->filled('image')) {
            $validated['receipt_image'] = $request->input('image');
        }

        $ledger = $this->ledgerService->updateLedger($id, $validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Ledger entry updated successfully!',
                'data' => $ledger,
                'summary' => $this->ledgerService->getSummary(),
            ]);
        }

        return redirect()->route('ledger.index')->with('success', 'Ledger entry updated successfully!');
    }

    /**
     * Display the specified ledger entry details.
     */
    public function show(Request $request, string $encryptedId): View|JsonResponse
    {
        $id = IdCipherService::decrypt($encryptedId);

        if (! $id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ledger entry not found.',
                ], 404);
            }

            return view('accounts.ledger-detail', [
                'ledger' => null,
                'id' => null,
            ]);
        }

        $ledger = $this->ledgerService->getLedgerById($id);

        if (! $ledger) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ledger entry not found.',
                ], 404);
            }

            return view('accounts.ledger-detail', [
                'ledger' => null,
                'id' => $id,
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $ledger,
            ]);
        }

        return view('accounts.ledger-detail', [
            'ledger' => $ledger,
            'id' => $id,
        ]);
    }

    /**
     * Delete a ledger entry.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->ledgerService->deleteLedger($id);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Ledger entry not found or could not be deleted.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ledger entry deleted successfully.',
            'summary' => $this->ledgerService->getSummary(),
        ]);
    }

    /**
     * Retrieve active transaction types by category for dynamic dropdown/inputs.
     */
    public function transactionTypesByCategory(string $category): JsonResponse
    {
        // Normalize category if "Bank" is requested
        $searchCategory = ($category === 'Bank') ? 'Bank Valuation' : $category;
        $types = TransactionType::active()
            ->where(function ($q) use ($searchCategory, $category) {
                $q->where('category', $searchCategory)
                    ->orWhere('category', $category);
            })
            ->orderBy('name_en')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }

    /**
     * Check if painter / electrician wages are allocated for a project site and date.
     */
    public function checkWages(Request $request): JsonResponse
    {
        $siteId = (int) $request->query('project_site_id');
        $entryDate = $request->query('entry_date');

        if (! $siteId || ! $entryDate) {
            return response()->json([
                'success' => false,
                'message' => 'Site and entry date are required.',
                'found' => false,
                'items' => [],
            ]);
        }

        $allocation = DailyAllocation::with('items')
            ->where('project_site_id', $siteId)
            ->whereDate('date', $entryDate)
            ->first();

        if (! $allocation) {
            return response()->json([
                'success' => true,
                'found' => false,
                'message' => 'No daily labour allocation found for this date.',
                'items' => [],
                'unpaid_total' => 0,
                'has_matching_trades' => false,
            ]);
        }

        $matchingItems = [];
        $unpaidTotal = 0.0;
        $alreadyPaidTotal = 0.0;
        $allPaid = true;
        $summaryParts = [];

        foreach ($allocation->items as $item) {
            if ($item->worker_count <= 0) {
                continue;
            }

            // Skip items already paid by Site Manager in Site Cash
            if ($item->paid_by === 'Site Manager') {
                continue;
            }

            $catCode = strtoupper(trim((string) $item->category_code));
            $nameEn = strtolower(trim((string) $item->name_en));
            $nameTa = trim((string) $item->name_ta);

            $isPainterOrElectrician = in_array($catCode, ['CAT-007', 'CAT-008', 'CAT-009'], true)
                || str_contains($nameEn, 'painter')
                || str_contains($nameEn, 'electrician')
                || str_contains($nameEn, 'wireman')
                || str_contains($nameTa, 'பெயிண்டர்')
                || str_contains($nameTa, 'எலக்ட்ரீஷியன்');

            $isMatch = $isPainterOrElectrician || (bool) $item->referred_to_am;

            if ($isMatch) {
                $subtotal = (float) $item->subtotal;
                $isPaid = ($item->paid_by === 'Account Manager');

                if ($isPaid) {
                    $alreadyPaidTotal += $subtotal;
                } else {
                    $unpaidTotal += $subtotal;
                    $allPaid = false;
                }

                $matchingItems[] = [
                    'id' => $item->id,
                    'worker_category_id' => $item->worker_category_id,
                    'category_code' => $item->category_code,
                    'name_ta' => $item->name_ta,
                    'name_en' => $item->name_en,
                    'count' => (int) $item->worker_count,
                    'daily_wage' => (float) $item->daily_wage,
                    'subtotal' => $subtotal,
                    'is_paid' => $isPaid,
                    'paid_by' => $item->paid_by,
                    'wage_cash_source' => $item->wage_cash_source,
                    'ledger_id' => $item->ledger_id,
                ];

                $summaryParts[] = "{$item->name_en} ({$item->name_ta}): {$item->worker_count} @ ₹{$item->daily_wage} = ₹{$subtotal}";
            }
        }

        return response()->json([
            'success' => true,
            'found' => true,
            'allocation_id' => $allocation->id,
            'has_matching_trades' => count($matchingItems) > 0,
            'all_paid' => count($matchingItems) > 0 && $allPaid,
            'unpaid_total' => $unpaidTotal,
            'already_paid_total' => $alreadyPaidTotal,
            'items' => $matchingItems,
            'summary_text' => implode(', ', $summaryParts),
        ]);
    }
}
