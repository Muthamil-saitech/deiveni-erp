<?php

namespace App\Http\Controllers;

use App\Models\ProjectSite;
use App\Models\SiteMaterialStock;
use App\Models\WarehouseItem;
use App\Services\Contracts\MaterialUsageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class MaterialUsageController extends Controller
{
    public function __construct(
        public MaterialUsageServiceInterface $materialUsageService
    ) {}

    /**
     * Display the material usage list page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->all();
        $perPage = max(15, (int) $request->input('per_page', 500));
        $usages = $this->materialUsageService->getUsages($filters, $perPage);
        $stats = $this->materialUsageService->getStats($filters);
        $sitesQuery = ProjectSite::query()->orderBy('name');
        if ($request->user()?->isSiteManager()) {
            $sitesQuery->where('created_by', $request->user()->id);
        }
        $sites = $sitesQuery->get();
        $purchaseOrders = $this->materialUsageService->getActivePurchaseOrders($filters);
        $warehouseItems = WarehouseItem::where('status', 'Active')
            ->where('current_stock', '>', 0)
            ->orderBy('name_en')
            ->get();
        $nextUsageNo = $this->materialUsageService->getNextUsageNumber();
        $siteStocks = null;
        if (! empty($filters['site_id'])) {
            $siteStocks = SiteMaterialStock::with('material.unit')
                ->where('project_site_id', (int) $filters['site_id'])
                ->get();
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'usages' => $usages,
                'stats' => $stats,
                'site_stocks' => $siteStocks,
            ]);
        }

        return view('site-management.material-usages', [
            'usages' => $usages,
            'stats' => $stats,
            'sites' => $sites,
            'purchaseOrders' => $purchaseOrders,
            'warehouseItems' => $warehouseItems,
            'nextUsageNo' => $nextUsageNo,
            'filters' => $filters,
            'siteStocks' => $siteStocks,
        ]);
    }

    /**
     * Store a newly created material usage record.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_site_id' => ['nullable', 'integer', 'exists:project_sites,id'],
            'purchase_order_id' => ['nullable', 'integer', 'exists:purchase_orders,id'],
            'material_source' => ['nullable', 'string', 'in:third_party,own_warehouse,supplier,stock_transfer'],
            'usage_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer'],
            'items.*.stock_transfer_id' => ['nullable', 'integer'],
            'items.*.stock_transfer_item_id' => ['nullable', 'integer'],
            'items.*.warehouse_item_id' => ['nullable', 'integer'],
            'items.*.material_id' => ['nullable', 'integer'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.source_type' => ['nullable', 'string', 'max:50'],
            'items.*.source_reference' => ['nullable', 'string', 'max:100'],
            'items.*.inward_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.used_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'usage_date.required' => 'Please enter the usage date (தேதி உள்ளிடவும்).',
            'usage_date.before_or_equal' => 'Usage date cannot be in the future (எதிர்கால தேதி அனுமதிக்கப்படாது).',
            'items.required' => 'Please add at least one material (குறைந்தது ஒரு பொருள் சேர்க்கவும்).',
            'items.min' => 'Please add at least one material (குறைந்தது ஒரு பொருள் சேர்க்கவும்).',
            'items.*.used_quantity.required' => 'Used quantity is required for each material.',
            'items.*.used_quantity.min' => 'Used quantity must be greater than zero.',
        ]);

        try {
            $userId = Auth::id() ?? 1;
            $usage = $this->materialUsageService->createUsage($validated, $userId);

            return response()->json([
                'success' => true,
                'message' => "Material Usage {$usage->usage_no} recorded successfully.",
                'usage' => $usage->load(['purchaseOrder', 'projectSite', 'creator', 'items']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get details of a single material usage voucher (for View Details modal).
     */
    public function show(int $id): JsonResponse
    {
        $usage = $this->materialUsageService->getUsageById($id);

        if (! $usage) {
            return response()->json([
                'success' => false,
                'message' => 'Material usage record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'usage' => $usage,
        ]);
    }

    /**
     * Update an existing material usage record.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'usage_date' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.purchase_order_item_id' => ['nullable', 'integer'],
            'items.*.stock_transfer_id' => ['nullable', 'integer'],
            'items.*.stock_transfer_item_id' => ['nullable', 'integer'],
            'items.*.warehouse_item_id' => ['nullable', 'integer'],
            'items.*.material_id' => ['nullable', 'integer'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.source_type' => ['nullable', 'string', 'max:50'],
            'items.*.source_reference' => ['nullable', 'string', 'max:100'],
            'items.*.inward_quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.used_quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'usage_date.required' => 'Please enter the usage date (தேதி உள்ளிடவும்).',
            'usage_date.before_or_equal' => 'Usage date cannot be in the future (எதிர்கால தேதி அனுமதிக்கப்படாது).',
            'items.required' => 'Please include at least one material.',
            'items.*.used_quantity.required' => 'Used quantity is required for each material.',
            'items.*.used_quantity.min' => 'Used quantity must be greater than zero.',
        ]);

        try {
            $userId = Auth::id() ?? 1;
            $usage = $this->materialUsageService->updateUsage($id, $validated, $userId);

            return response()->json([
                'success' => true,
                'message' => "Material Usage {$usage->usage_no} updated successfully.",
                'usage' => $usage,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a material usage record.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->materialUsageService->deleteUsage($id);

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material usage record not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Material usage deleted and stock restored successfully.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX: Get materials available for a site, optionally filtered by PO.
     */
    public function getSiteMaterials(int $siteId, Request $request): JsonResponse
    {
        try {
            $poId = $request->query('po_id') ? (int) $request->query('po_id') : null;
            $data = $this->materialUsageService->getSiteMaterialsForUsage($siteId, $poId);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX: Get PO items (with available quantities) for a given PO.
     */
    public function getPOItems(int $poId): JsonResponse
    {
        try {
            $data = $this->materialUsageService->getPOItemsForUsage($poId);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * AJAX: Get the next auto-generated usage number.
     */
    public function nextNumber(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'usage_no' => $this->materialUsageService->getNextUsageNumber(),
        ]);
    }

    /**
     * AJAX: Get active POs, optionally filtered by site.
     */
    public function getPurchaseOrders(Request $request): JsonResponse
    {
        $filters = $request->only(['site_id']);
        $pos = $this->materialUsageService->getActivePurchaseOrders($filters);

        return response()->json([
            'success' => true,
            'purchase_orders' => $pos,
        ]);
    }

    /**
     * AJAX: Get in-stock materials from Central Warehouse.
     */
    public function getWarehouseMaterials(): JsonResponse
    {
        $items = WarehouseItem::where('status', 'Active')
            ->where('current_stock', '>', 0)
            ->orderBy('name_en')
            ->get();

        return response()->json([
            'success' => true,
            'materials' => $items,
        ]);
    }
}
