<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminLedgerRequest;
use App\Http\Requests\UpdateAdminLedgerRequest;
use App\Models\Client;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Contracts\AdminLedgerServiceInterface;
use App\Services\IdCipherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLedgerController extends Controller
{
    public function __construct(
        public AdminLedgerServiceInterface $service
    ) {}

    /**
     * Display the Admin Ledger page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'category' => $request->query('category'),
            'flow' => $request->query('flow'),
            'client_id' => $request->query('client_id'),
            'supplier_id' => $request->query('supplier_id'),
            'project_site_id' => $request->query('project_site_id'),
            'date' => $request->query('date'),
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
            'search' => $request->query('search'),
        ];

        $entries = $this->service->getAll($filters);
        $summary = $this->service->getSummary($filters);
        $runningBalances = $this->service->calculateRunningBalances($entries);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'entries' => $entries,
                'summary' => $summary,
                'running_balances' => $runningBalances,
            ]);
        }

        $clients = Client::with('projectSites')->orderBy('name')->get();
        $sites = ProjectSite::with('client')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();

        return view('accounts.admin-ledger', compact(
            'entries',
            'summary',
            'runningBalances',
            'clients',
            'sites',
            'suppliers',
            'filters'
        ));
    }

    /**
     * Store a newly created Admin Ledger entry.
     */
    public function store(StoreAdminLedgerRequest $request): JsonResponse
    {
        $entry = $this->service->createEntry(
            $request->validated(),
            $request->file('receipt_image')
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin ledger entry recorded successfully.',
            'entry' => $entry->load(['client', 'projectSite', 'supplier', 'creator']),
        ], 201);
    }

    /**
     * Display a specific Admin Ledger entry.
     */
    public function show(string $encryptedId): JsonResponse|View
    {
        $id = is_numeric($encryptedId) ? (int) $encryptedId : IdCipherService::decrypt($encryptedId);
        $entry = $this->service->getById($id);

        if (! $entry) {
            abort(404, 'Admin ledger entry not found.');
        }

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'entry' => $entry,
            ]);
        }

        $clients = Client::with('projectSites')->orderBy('name')->get();
        $sites = ProjectSite::with('client')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();

        return view('accounts.admin-ledger-detail', compact('entry', 'clients', 'sites', 'suppliers'));
    }

    /**
     * Update an existing Admin Ledger entry.
     */
    public function update(UpdateAdminLedgerRequest $request, int $id): JsonResponse
    {
        $updated = $this->service->updateEntry(
            $id,
            $request->validated(),
            $request->file('receipt_image')
        );

        if (! $updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update admin ledger entry.',
            ], 422);
        }

        $entry = $this->service->getById($id);

        return response()->json([
            'success' => true,
            'message' => 'Admin ledger entry updated successfully.',
            'entry' => $entry?->load(['client', 'projectSite', 'supplier', 'creator']),
        ]);
    }

    /**
     * Remove an Admin Ledger entry.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->service->deleteEntry($id);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Entry not found or could not be deleted.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin ledger entry deleted successfully.',
        ]);
    }

    /**
     * Get projects for a specific client.
     */
    public function getClientProjects(Request $request): JsonResponse
    {
        $clientId = $request->query('client_id');
        if (! $clientId) {
            return response()->json([
                'success' => true,
                'projects' => [],
            ]);
        }

        $projects = ProjectSite::query()
            ->where('client_id', (int) $clientId)
            ->orderBy('name')
            ->get(['id', 'name', 'location', 'status']);

        return response()->json([
            'success' => true,
            'projects' => $projects,
        ]);
    }

    /**
     * Get purchase orders filtered by supplier, supporting site_based and total scopes.
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

        $query = PurchaseOrder::with(['items', 'projectSite', 'supplier'])
            ->whereIn('status', ['Order Received', 'Order Completed']);

        if ($isSiteBased && $siteId) {
            $query->where('project_site_id', (int) $siteId);
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

        $totalPendingAmount = (float) $orders->sum(fn ($po) => (float) $po->total_amount);

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'total_pending_amount' => round($totalPendingAmount, 2),
            'pending_count' => $orders->count(),
        ]);
    }
}
