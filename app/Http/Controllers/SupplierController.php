<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Ledger;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Services\Contracts\MaterialServiceInterface;
use App\Services\Contracts\SupplierServiceInterface;
use App\Services\IdCipherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        public SupplierServiceInterface $supplierService,
        public MaterialServiceInterface $materialService,
    ) {}

    /**
     * Display a listing of suppliers.
     */
    public function index(Request $request): View|JsonResponse
    {
        $suppliers = $this->supplierService->getAllSuppliers();
        $financialSummaries = $this->supplierService->getFinancialSummaries();
        $materials = $this->materialService->getAllMaterials();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'financial_summaries' => $financialSummaries,
                'materials' => $materials,
            ]);
        }

        return view('masters.suppliers', [
            'suppliers' => $suppliers,
            'financialSummaries' => $financialSummaries,
            'materials' => $materials,
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse|RedirectResponse
    {
        $supplier = $this->supplierService->createSupplier($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier \"{$supplier->name}\" registered successfully!",
                'data' => $supplier,
                'encrypted_id' => IdCipherService::encrypt($supplier->id),
            ], 201);
        }

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier \"{$supplier->name}\" registered successfully!");
    }

    /**
     * Display the specified supplier.
     */
    public function show(Request $request, string $encryptedId): View|JsonResponse
    {
        $id = IdCipherService::decrypt($encryptedId);

        if (! $id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier not found.',
                ], 404);
            }

            abort(404, 'Supplier not found.');
        }

        $supplier = $this->supplierService->getSupplierById($id);

        if (! $supplier) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier not found.',
                ], 404);
            }

            abort(404, 'Supplier not found.');
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $supplier,
            ]);
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        // All non-cancelled POs for this supplier
        $allOrders = PurchaseOrder::with(['projectSite.client', 'items.material.unit'])
            ->where('supplier_id', $supplier->id)
            ->where('status', '!=', 'Cancelled')
            ->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $filteredOrders = $allOrders;
        if ($fromDate) {
            $filteredOrders = $filteredOrders->filter(fn ($po) => $po->order_date && $po->order_date->format('Y-m-d') >= $fromDate);
        }
        if ($toDate) {
            $filteredOrders = $filteredOrders->filter(fn ($po) => $po->order_date && $po->order_date->format('Y-m-d') <= $toDate);
        }

        // Ledger & SiteCash payments made to this supplier
        $ledgerPayments = Ledger::where('supplier_id', $supplier->id)
            ->where(function ($q) {
                $q->where('flow', 'expense')->orWhere('flow', 'out');
            })
            ->when($fromDate, fn ($q) => $q->where('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->where('entry_date', '<=', $toDate))
            ->orderBy('entry_date', 'desc')
            ->get();

        $siteCashPayments = SiteCash::where('supplier_id', $supplier->id)
            ->where(function ($q) {
                $q->where('flow_type', 'outflow')
                    ->orWhere('flow_type', 'out')
                    ->orWhere('out_amount', '>', 0);
            })
            ->whereNull('ledger_id') // Avoid duplicate counting if mirrored from ledger
            ->when($fromDate, fn ($q) => $q->where('payment_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->where('payment_date', '<=', $toDate))
            ->orderBy('payment_date', 'desc')
            ->get();

        $totalPurchased = (float) $filteredOrders->sum('total_amount');
        $totalPaid = (float) $ledgerPayments->sum('amount') + (float) $siteCashPayments->sum(fn ($sc) => (float) ($sc->out_amount ?: $sc->amount));
        $balancePayable = round($totalPurchased - $totalPaid, 2);

        // Flatten all line items
        $allPoItems = $filteredOrders->flatMap(function ($po) {
            return $po->items->map(function ($item) use ($po) {
                return [
                    'item_id' => $item->id,
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                    'po_encrypted_id' => $po->encrypted_id,
                    'order_date' => $po->order_date ? $po->order_date->format('d M Y') : 'N/A',
                    'order_date_raw' => $po->order_date ? $po->order_date->format('Y-m-d') : '',
                    'project_site_id' => $po->project_site_id,
                    'site_name' => $po->projectSite?->name ?? 'General / Other Site',
                    'site_encrypted_id' => $po->projectSite?->encrypted_id,
                    'site_location' => $po->projectSite?->location,
                    'material_id' => $item->material_id,
                    'material_name' => $item->material_name ?: ($item->material?->name_en ?? 'Material'),
                    'material_name_ta' => $item->material?->name_ta ?? '',
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit ?: ($item->material?->unit?->name_en ?? 'Units'),
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ];
            });
        });

        // 1. Overall Materials Summary (Across All Sites)
        $totalMaterialsSummary = $allPoItems->groupBy('material_name')->map(function ($items, $matName) {
            $totalQty = (float) $items->sum('quantity');
            $totalAmt = (float) $items->sum('total_price');
            $unit = $items->first()['unit'];
            $matTa = $items->first()['material_name_ta'];
            $avgPrice = $totalQty > 0 ? round($totalAmt / $totalQty, 2) : 0.0;
            $sites = $items->pluck('site_name')->unique()->values()->all();

            return [
                'material_name' => $matName,
                'material_name_ta' => $matTa,
                'total_quantity' => $totalQty,
                'unit' => $unit,
                'average_price' => $avgPrice,
                'total_amount' => $totalAmt,
                'orders_count' => $items->pluck('po_number')->unique()->count(),
                'sites_count' => count($sites),
                'sites' => $sites,
            ];
        })->sortByDesc('total_amount')->values();

        // 2. Site-based Breakdown
        $siteBasedBreakdown = $allPoItems->groupBy('project_site_id')->map(function ($items, $siteId) {
            $firstItem = $items->first();
            $totalSiteAmount = (float) $items->sum('total_price');
            $totalSiteQty = (float) $items->sum('quantity');
            $siteName = $firstItem['site_name'];
            $siteEncryptedId = $firstItem['site_encrypted_id'];
            $siteLocation = $firstItem['site_location'];

            // Materials summary for this site
            $siteMaterials = $items->groupBy('material_name')->map(function ($mItems, $mName) {
                $mQty = (float) $mItems->sum('quantity');
                $mAmt = (float) $mItems->sum('total_price');
                $mUnit = $mItems->first()['unit'];
                $mTa = $mItems->first()['material_name_ta'];
                $mAvg = $mQty > 0 ? round($mAmt / $mQty, 2) : 0.0;

                return [
                    'material_name' => $mName,
                    'material_name_ta' => $mTa,
                    'quantity' => $mQty,
                    'unit' => $mUnit,
                    'average_price' => $mAvg,
                    'total_price' => $mAmt,
                ];
            })->values();

            return [
                'project_site_id' => $siteId,
                'site_name' => $siteName,
                'site_encrypted_id' => $siteEncryptedId,
                'site_location' => $siteLocation,
                'total_amount' => $totalSiteAmount,
                'total_quantity' => $totalSiteQty,
                'orders_count' => $items->pluck('po_number')->unique()->count(),
                'materials_count' => $siteMaterials->count(),
                'materials' => $siteMaterials->all(),
                'line_items' => $items->values()->all(),
            ];
        })->sortByDesc('total_amount')->values();

        // Financial stats
        $financialStats = [
            'total_purchased' => $totalPurchased,
            'total_paid' => $totalPaid,
            'balance_payable' => $balancePayable,
            'orders_count' => $filteredOrders->count(),
            'all_time_orders_count' => $allOrders->count(),
            'sites_count' => $siteBasedBreakdown->count(),
            'materials_count' => $totalMaterialsSummary->count(),
            'total_quantity_supplied' => (float) $allPoItems->sum('quantity'),
        ];

        $projectSitesList = $siteBasedBreakdown->map(function ($s) {
            return [
                'id' => $s['project_site_id'],
                'name' => $s['site_name'],
                'location' => $s['site_location'],
                'total_amount' => $s['total_amount'],
                'materials_count' => $s['materials_count'],
            ];
        })->values();

        return view('masters.supplier-detail', compact(
            'supplier',
            'financialStats',
            'totalMaterialsSummary',
            'siteBasedBreakdown',
            'projectSitesList',
            'allPoItems',
            'filteredOrders',
            'ledgerPayments',
            'siteCashPayments',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $supplier = $this->supplierService->updateSupplier($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Supplier \"{$supplier->name}\" updated successfully!",
                'data' => $supplier,
                'encrypted_id' => IdCipherService::encrypt($supplier->id),
            ]);
        }

        return redirect()->route('suppliers.index')
            ->with('success', "Supplier \"{$supplier->name}\" updated successfully!");
    }

    /**
     * Toggle the site-based payment mode of a supplier.
     */
    public function toggleSiteBased(Request $request, int $id): JsonResponse
    {
        $supplier = $this->supplierService->getSupplierById($id);

        if (! $supplier) {
            return response()->json([
                'success' => false,
                'message' => 'Supplier not found.',
            ], 404);
        }

        $isSiteBased = $request->has('is_site_based')
            ? filter_var($request->input('is_site_based'), FILTER_VALIDATE_BOOLEAN)
            : ! (bool) $supplier->is_site_based;

        $supplier->update(['is_site_based' => $isSiteBased]);

        $modeLabel = $isSiteBased ? 'Site Based' : 'Total';

        return response()->json([
            'success' => true,
            'message' => "Supplier \"{$supplier->name}\" payment mode updated to {$modeLabel}.",
            'is_site_based' => $isSiteBased,
            'data' => $supplier,
        ]);
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $supplier = $this->supplierService->getSupplierById($id);

        if (! $supplier) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supplier not found.',
                ], 404);
            }

            return redirect()->route('suppliers.index')
                ->with('error', 'Supplier not found.');
        }

        if ($this->supplierService->isSupplierInUse($id)) {
            $message = "Supplier \"{$supplier->name}\" cannot be deleted because it is currently used in Purchase Orders.";

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('suppliers.index')
                ->with('error', $message);
        }

        $this->supplierService->deleteSupplier($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Supplier removed successfully.',
            ]);
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier removed successfully.');
    }
}
