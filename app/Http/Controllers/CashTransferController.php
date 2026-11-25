<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashTransferRequest;
use App\Http\Requests\UpdateCashTransferRequest;
use App\Models\AdminLedger;
use App\Models\CashTransfer;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\Role;
use App\Models\SiteCash;
use App\Models\User;
use App\Services\Contracts\CashTransferServiceInterface;
use App\Services\IdCipherService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CashTransferController extends Controller
{
    public function __construct(
        public CashTransferServiceInterface $transferService
    ) {}

    /**
     * Display a listing of cash transfers.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $sitesQuery = ProjectSite::with('client')->orderBy('name');
        if ($user?->isSiteManager()) {
            $sitesQuery->where('created_by', $user->id);
        }
        $sites = $sitesQuery->get();
        $siteIds = $sites->pluck('id')->all();

        $adminLedgerIncomeBySite = AdminLedger::query()
            ->whereIn('project_site_id', $siteIds)
            ->where('category', 'client_payment')
            ->where('flow', 'income')
            ->selectRaw('project_site_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('project_site_id')
            ->pluck('total', 'project_site_id');

        $ledgerIncomeBySite = Ledger::query()
            ->whereIn('project_site_id', $siteIds)
            ->where('flow', 'income')
            ->whereNull('cash_transfer_id')
            ->whereNotIn('transaction_type_name', [
                'Cash Transfer from Site',
                'Project Cash Return from Site',
                'Balance Returned (Cash Transfer)',
                'Balance Returned',
            ])
            ->selectRaw('project_site_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('project_site_id')
            ->pluck('total', 'project_site_id');

        $siteCashClientBySite = SiteCash::query()
            ->whereIn('project_site_id', $siteIds)
            ->where('flow_type', 'inflow')
            ->where('transaction_type', 'Client Collection')
            ->selectRaw('project_site_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('project_site_id')
            ->pluck('total', 'project_site_id');

        $transferredBySite = CashTransfer::query()
            ->whereIn('project_site_id', $siteIds)
            ->where(function ($q) {
                $q->where('transfer_direction', 'project_cash')
                    ->orWhere(function ($sub) {
                        $sub->where('transfer_direction', 'office_to_site')
                            ->where('settlement_status', '!=', 'settled');
                    });
            })
            ->selectRaw('project_site_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('project_site_id')
            ->pluck('total', 'project_site_id');

        $siteFundsMap = [];
        foreach ($sites as $s) {
            $received = (float) ($adminLedgerIncomeBySite[$s->id] ?? 0)
                + (float) ($ledgerIncomeBySite[$s->id] ?? 0)
                + (float) ($siteCashClientBySite[$s->id] ?? 0);
            $transferred = (float) ($transferredBySite[$s->id] ?? 0);
            $siteFundsMap[$s->id] = [
                'site_name' => $s->name,
                'client_name' => $s->client?->name ?? 'N/A',
                'created_by' => $s->created_by,
                'received_amount' => round($received, 2),
                'transferred_amount' => round($transferred, 2),
                'available_amount' => round(max(0.0, $received - $transferred), 2),
            ];
        }

        $roles = Role::query()
            ->whereNotIn('slug', ['owner'])
            ->where('name', '!=', 'Owner')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $transferUsers = User::query()
            ->with('role:id,name,slug')
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'role_id'])
            ->map(function (User $u): array {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role_id' => $u->role_id,
                    'role_slug' => $u->roleValue(),
                ];
            })
            ->values();

        $filters = [
            'direction' => $request->get('direction', 'all'),
            'site_id' => $request->get('site_id', 'all'),
            'status' => $request->get('status', 'all'),
            'search' => $request->get('search'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $data = $this->transferService->getTransferData($filters);
        $canSettle = $user?->canAccess('cash_transfer_settle') ?? false;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
                'can_settle' => $canSettle,
                'site_funds_map' => $siteFundsMap,
            ]);
        }

        return view('cash-transfers.index', [
            'user' => $user,
            'canSettle' => $canSettle,
            'transfers' => $data['transfers'],
            'kpis' => $data['kpis'],
            'sites' => $sites,
            'roles' => $roles,
            'transferUsers' => $transferUsers,
            'siteFundsMap' => $siteFundsMap,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created cash transfer.
     */
    public function store(StoreCashTransferRequest $request): JsonResponse
    {
        $transfer = $this->transferService->createTransfer($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cash transfer recorded and synchronized successfully.',
            'data' => $transfer,
        ], 201);
    }

    /**
     * Display the specified cash transfer.
     */
    public function show(string|int $id): JsonResponse
    {
        $numericId = is_numeric($id) ? (int) $id : IdCipherService::decrypt($id);
        $transfer = $this->transferService->getTransferById($numericId);

        if (! $transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Cash transfer not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transfer,
        ]);
    }

    /**
     * Update the specified cash transfer.
     */
    public function update(UpdateCashTransferRequest $request, string|int $id): JsonResponse
    {
        $numericId = is_numeric($id) ? (int) $id : IdCipherService::decrypt($id);

        $transfer = $this->transferService->updateTransfer($numericId, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Cash transfer updated successfully.',
            'data' => $transfer,
        ]);
    }

    /**
     * Mark a temporary transfer as settled / returned.
     */
    public function settle(Request $request, string|int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $user?->canAccess('cash_transfer_settle')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not have permission to settle cash transfers. Only users with settlement access granted by Owner can perform this action.',
            ], 403);
        }

        $numericId = is_numeric($id) ? (int) $id : IdCipherService::decrypt($id);

        $request->validate([
            'settlement_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer = $this->transferService->markSettled($numericId, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cash transfer marked as settled/returned successfully.',
            'data' => $transfer,
        ]);
    }

    /**
     * Accept a pending cash transfer by the recipient.
     */
    public function accept(Request $request, string|int $id): JsonResponse
    {
        $numericId = is_numeric($id) ? (int) $id : IdCipherService::decrypt($id);

        $request->validate([
            'acceptance_notes' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $transfer = $this->transferService->acceptTransfer($numericId, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cash transfer accepted successfully and synchronized with accounts (ரொக்கம் வெற்றிகரமாக ஏற்றுக்கொள்ளப்பட்டு கணக்கில் ஏற்றப்பட்டது).',
                'data' => $transfer,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified cash transfer.
     */
    public function destroy(string|int $id): JsonResponse
    {
        $numericId = is_numeric($id) ? (int) $id : IdCipherService::decrypt($id);

        $deleted = $this->transferService->deleteTransfer($numericId);

        if (! $deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Cash transfer could not be found or deleted.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cash transfer deleted successfully.',
        ]);
    }
}
