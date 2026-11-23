<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\Unit;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\StockTransferServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class StockTransferController extends Controller
{
    public function __construct(
        public StockTransferServiceInterface $stockTransferService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display a listing of stock transfers.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->all();
        $perPage = max(15, (int) $request->input('per_page', 500));
        $transfers = $this->stockTransferService->getTransfers($filters, $perPage);
        $sites = ProjectSite::query()->orderBy('name')->get();
        $materials = Material::where('status', 'Active')->with('unit')->orderBy('name_en')->get();
        $units = Unit::where('status', 'Active')->orderBy('name_en')->get();
        $stats = $this->stockTransferService->getStats($filters);
        $nextTransferNo = $this->stockTransferService->getNextTransferNumber();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'transfers' => $transfers,
                'stats' => $stats,
            ]);
        }

        return view('site-management.stock-transfers', [
            'transfers' => $transfers,
            'sites' => $sites,
            'materials' => $materials,
            'units' => $units,
            'stats' => $stats,
            'nextTransferNo' => $nextTransferNo,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a newly created stock transfer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_project_site_id' => ['required', 'integer', 'exists:project_sites,id', 'different:to_project_site_id'],
            'to_project_site_id' => ['required', 'integer', 'exists:project_sites,id'],
            'transfer_date' => ['required', 'date'],
            'return_expected' => ['nullable', 'boolean'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'challan_no' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array', 'min:1'],
            'items.*.material_id' => ['required_with:items', 'integer'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.source_type' => ['nullable', 'string', 'max:50'],
            'items.*.source_reference' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.total_price' => ['nullable', 'numeric', 'min:0'],
            // Backwards compatibility for single item calls:
            'material_id' => ['nullable', 'required_without:items', 'integer', 'exists:materials,id'],
            'quantity' => ['nullable', 'required_without:items', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'string', 'max:50'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
        ], [
            'from_project_site_id.different' => 'Source site and destination site cannot be the same (வழங்கும் தளம் மற்றும் பெறும் தளம் ஒன்றாக இருக்க முடியாது).',
            'from_project_site_id.required' => 'Please select the source site (வழங்கும் தளத்தை தேர்வு செய்யவும்).',
            'to_project_site_id.required' => 'Please select the destination site (பெறும் தளத்தை தேர்வு செய்யவும்).',
            'items.required_without' => 'Please add at least one material to transfer (குறைந்தது ஒரு பொருளை சேர்க்கவும்).',
            'items.min' => 'Please add at least one material to transfer (குறைந்தது ஒரு பொருளை சேர்க்கவும்).',
            'material_id.required_without' => 'Please select material to transfer (பொருளை தேர்வு செய்யவும்).',
            'quantity.required_without' => 'Please enter transfer quantity (அளவை உள்ளிடவும்).',
            'quantity.min' => 'Quantity must be greater than zero.',
        ]);

        try {
            $userId = Auth::id() ?? 1;
            $transfer = $this->stockTransferService->createTransfer($validated, $userId);

            return response()->json([
                'success' => true,
                'message' => "Stock transfer {$transfer->transfer_no} recorded successfully.",
                'transfer' => $transfer->load(['fromSite', 'toSite', 'material.unit', 'items.material.unit']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get available materials in stock at a specific source site.
     */
    public function availableMaterials(int $siteId): JsonResponse
    {
        $materials = $this->stockTransferService->getAvailableMaterialsForSite($siteId);

        return response()->json([
            'success' => true,
            'site_id' => $siteId,
            'materials' => $materials,
        ]);
    }

    /**
     * Record a return of stock against a parent transfer.
     */
    public function storeReturn(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => ['required', 'date'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'challan_no' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'quantity.required' => 'Please enter return quantity (திருப்பி கொடுக்கும் அளவை உள்ளிடவும்).',
            'quantity.min' => 'Return quantity must be greater than zero.',
        ]);

        try {
            $userId = Auth::id() ?? 1;
            $returnTransfer = $this->stockTransferService->createReturn($id, $validated, $userId);

            return response()->json([
                'success' => true,
                'message' => "Return transfer {$returnTransfer->transfer_no} recorded successfully.",
                'transfer' => $returnTransfer->load(['fromSite', 'toSite', 'material.unit']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get details of a single stock transfer.
     */
    public function show(int $id): JsonResponse
    {
        $transfer = $this->stockTransferService->getTransferById($id);

        if (! $transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Stock transfer record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'transfer' => $transfer,
        ]);
    }

    /**
     * Update an existing stock transfer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'transfer_date' => ['required', 'date'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'challan_no' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'return_expected' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.material_id' => ['nullable', 'integer'],
            'items.*.material_name' => ['nullable', 'string', 'max:255'],
            'items.*.source_type' => ['nullable', 'string', 'max:50'],
            'items.*.source_reference' => ['nullable', 'string', 'max:100'],
            'items.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'transfer_date.required' => 'Please enter the transfer date.',
            'items.required' => 'Please include at least one transferred material.',
            'items.*.quantity.required' => 'Quantity is required for each material.',
            'items.*.quantity.min' => 'Quantity must be greater than zero.',
        ]);

        try {
            $userId = Auth::id() ?? 1;
            $transfer = $this->stockTransferService->updateTransfer($id, $validated, $userId);

            return response()->json([
                'success' => true,
                'message' => "Stock Transfer {$transfer->transfer_no} updated successfully.",
                'transfer' => $transfer,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a stock transfer.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->stockTransferService->deleteTransfer($id);

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock transfer record not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock transfer deleted and stock balance reverted successfully.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check live stock of material at a specific site.
     */
    public function getSiteStock(int $siteId, int $materialId): JsonResponse
    {
        $stock = $this->stockTransferService->getSiteMaterialStock($siteId, $materialId);

        return response()->json([
            'success' => true,
            'current_stock' => $stock,
        ]);
    }

    /**
     * Get next sequential transfer number.
     */
    public function nextTransferNumber(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'transfer_no' => $this->stockTransferService->getNextTransferNumber(),
        ]);
    }
}
