<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectSiteRequest;
use App\Http\Requests\UpdateProjectSiteRequest;
use App\Models\Ledger;
use App\Models\StockTransfer;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\IdCipherService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectSiteController extends Controller
{
    public function __construct(
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display a listing of project sites and clients.
     */
    public function index(Request $request): View|JsonResponse
    {
        $sites = $this->projectSiteService->getAllSites();
        $clients = $this->projectSiteService->getAllClients();
        $stats = $this->projectSiteService->getSiteStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $sites,
                'clients' => $clients,
                'stats' => $stats,
            ]);
        }

        return view('sites.index', [
            'sites' => $sites,
            'clients' => $clients,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created project site and client record.
     */
    public function store(StoreProjectSiteRequest $request): JsonResponse|RedirectResponse
    {
        $site = $this->projectSiteService->createSite($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Project site \"{$site->name}\" created successfully!",
                'data' => $site,
                'clients' => $this->projectSiteService->getAllClients(),
                'stats' => $this->projectSiteService->getSiteStats(),
            ], 201);
        }

        return redirect()->route('sites.index')
            ->with('success', "Project site \"{$site->name}\" created successfully!");
    }

    /**
     * Display the specified project site record.
     */
    public function show(Request $request, string $encryptedId): View|JsonResponse
    {
        $id = IdCipherService::decrypt($encryptedId);

        if (! $id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project site record not found.',
                ], 404);
            }

            abort(404, 'Project site record not found.');
        }

        $site = $this->projectSiteService->getSiteById($id);

        if (! $site) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project site record not found.',
                ], 404);
            }

            abort(404, 'Project site record not found.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $site,
            ]);
        }
        // Labour tab: daily allocations for this site (load items and expenses for detailed view)
        $labourRecords = $site->dailyAllocations()
            ->with(['creator', 'items.workerCategory', 'expenses'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Materials Used tab: material usage records for this site
        $materialUsageRecords = $site->materialUsages()
            ->with(['creator', 'items.material', 'purchaseOrder.supplier'])
            ->orderBy('usage_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Stock Transferred tab: outward stock transfers from this site
        $stockTransferRecords = StockTransfer::with(['toSite', 'items', 'creator'])
            ->where('from_project_site_id', $id)
            ->where('transfer_type', 'TRANSFER')
            ->orderBy('transfer_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Site Ledgers: records managed by Account Manager for this project site
        $siteLedgers = Ledger::with(['supplier', 'creator', 'cashTransfer'])
            ->where('project_site_id', $id)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $combinedCashTransactions = collect();

        // 1. Ingest SiteCash records (managed by Site Manager / Site team)
        foreach ($site->siteCashes as $sc) {
            $creatorName = $sc->creator?->name ?? 'Site Team';
            $paidBy = trim((string) $sc->paid_by);
            $paidTo = trim((string) $sc->paid_to);
            $receiverName = trim((string) $sc->receiver_name);

            $handlerRole = 'Site Manager';
            $handlerName = $paidBy ?: $creatorName;
            $partyName = $sc->supplier_name ?: ($sc->supplier?->name ?: ($paidTo ?: ($receiverName ?: ($paidBy ?: '-'))));

            $combinedCashTransactions->push([
                'id' => 'sc_'.$sc->id,
                'raw_id' => $sc->id,
                'source' => 'site_cash',
                'source_label' => 'Site Cash Handling',
                'source_badge_class' => 'bg-amber-subtle text-dark border border-warning-subtle',
                'handler_role' => $handlerRole,
                'handler_badge_class' => 'bg-warning-subtle text-dark border border-warning-subtle',
                'handler_name' => $handlerName,
                'date' => $sc->payment_date ? Carbon::parse($sc->payment_date)->format('Y-m-d') : '',
                'date_formatted' => $sc->payment_date ? Carbon::parse($sc->payment_date)->format('d M Y') : 'N/A',
                'time' => $sc->payment_time ?: '',
                'type' => $sc->transaction_type,
                'supplier_name' => $sc->supplier_name ?: ($sc->supplier?->name ?? ''),
                'party_name' => $partyName,
                'description' => $sc->description,
                'bill_no' => $sc->bill_no ?: 'N/A',
                'receipt_image' => $sc->receipt_image,
                'in_amount' => (float) $sc->in_amount,
                'out_amount' => (float) $sc->out_amount,
                'is_expense' => (bool) $sc->is_expense,
                'items' => $sc->items,
                'view_url' => route('site-cash.show', $sc->encrypted_id),
                'encrypted_id' => $sc->encrypted_id,
                'created_at' => $sc->created_at,
            ]);
        }

        // 2. Ingest Ledger records (managed by Account Manager / Office)
        foreach ($siteLedgers as $ldg) {
            $creatorName = $ldg->creator?->name ?? 'Accountant';
            $isIncome = $ldg->flow === 'income';
            $type = $ldg->transaction_type_name ?: ($isIncome ? 'Client Receipt (வரவு)' : 'Project Expense (செலவு)');
            $partyName = $ldg->supplier_name ?: ($ldg->supplier?->name ?: ($ldg->cash_received_from ?: ($isIncome ? ($ldg->client_name ?: 'Client / Owner') : '-')));
            $handlerName = 'Accountant ('.$creatorName.')';

            $combinedCashTransactions->push([
                'id' => 'ldg_'.$ldg->id,
                'raw_id' => $ldg->id,
                'source' => 'ledger',
                'source_label' => 'Office Ledger',
                'source_badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
                'handler_role' => 'Account Manager',
                'handler_badge_class' => 'bg-primary-subtle text-primary border border-primary-subtle',
                'handler_name' => $handlerName,
                'date' => $ldg->entry_date ? Carbon::parse($ldg->entry_date)->format('Y-m-d') : '',
                'date_formatted' => $ldg->entry_date ? Carbon::parse($ldg->entry_date)->format('d M Y') : 'N/A',
                'time' => '',
                'type' => $type,
                'supplier_name' => $ldg->supplier_name ?: ($ldg->supplier?->name ?? ''),
                'party_name' => $partyName,
                'description' => $ldg->description,
                'bill_no' => $ldg->cash_transfer_id ? ('XFER-'.$ldg->cash_transfer_id) : ('LEDGER-'.$ldg->id),
                'receipt_image' => $ldg->receipt_image,
                'in_amount' => $isIncome ? (float) $ldg->amount : 0.0,
                'out_amount' => ! $isIncome ? (float) $ldg->amount : 0.0,
                'is_expense' => ! $isIncome,
                'items' => null,
                'view_url' => route('ledger.show', $ldg->encrypted_id),
                'encrypted_id' => $ldg->encrypted_id,
                'created_at' => $ldg->created_at,
            ]);
        }

        // Sort by date DESC, then raw_id DESC
        $sortedCashTransactions = $combinedCashTransactions->sort(function ($a, $b) {
            $dateCmp = strcmp($b['date'], $a['date']);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            return $b['raw_id'] <=> $a['raw_id'];
        })->values();

        // Calculate project cash summary KPIs:
        $totalProjectFundsReceived = (float) $siteLedgers->where('flow', 'income')->sum('amount') + (float) $site->siteCashes->where('flow_type', 'inflow')->where('transaction_type', 'Client Collection')->sum('amount');
        $totalAmProjectSpent = (float) $siteLedgers->where('flow', 'expense')->sum('amount');
        $totalSmSiteSpent = (float) $site->siteCashes->where('is_expense', true)->sum('amount');
        $siteCashInHand = (float) $site->siteCashes->sum('in_amount') - (float) $site->siteCashes->sum('out_amount');
        $amProjectBalance = (float) $siteLedgers->where('flow', 'income')->sum('amount') - (float) $siteLedgers->where('flow', 'expense')->sum('amount');

        $projectCashKpis = [
            'total_funds_received' => $totalProjectFundsReceived,
            'am_spent' => $totalAmProjectSpent,
            'sm_spent' => $totalSmSiteSpent,
            'site_cash_in_hand' => $siteCashInHand,
            'am_balance' => $amProjectBalance,
        ];

        return view('sites.show', [
            'site' => $site,
            'labourRecords' => $labourRecords,
            'materialUsageRecords' => $materialUsageRecords,
            'stockTransferRecords' => $stockTransferRecords,
            'combinedCashTransactions' => $sortedCashTransactions,
            'projectCashKpis' => $projectCashKpis,
        ]);
    }

    /**
     * Update the specified project site and client record.
     */
    public function update(UpdateProjectSiteRequest $request, string|int $id): JsonResponse|RedirectResponse
    {
        $numericId = IdCipherService::decrypt((string) $id) ?: (int) $id;
        $site = $this->projectSiteService->updateSite($numericId, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Project site \"{$site->name}\" updated successfully!",
                'data' => $site,
                'clients' => $this->projectSiteService->getAllClients(),
                'stats' => $this->projectSiteService->getSiteStats(),
            ]);
        }

        return redirect()->route('sites.index')
            ->with('success', "Project site \"{$site->name}\" updated successfully!");
    }
}
