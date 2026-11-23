<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\PurchaseServiceInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use App\Services\IdCipherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        public PurchaseServiceInterface $purchaseService,
        public ProjectSiteServiceInterface $projectSiteService,
        public WarehouseServiceInterface $warehouseService
    ) {}

    /**
     * Display a listing of purchases & inward records.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->all();
        $purchases = $this->purchaseService->getAll($filters);
        $sites = $this->projectSiteService->getAllSites();
        $suppliers = Supplier::where('status', 'Active')
            ->where('name', 'not like', '%warehouse%')
            ->with(['materials' => function ($q) {
                $q->where('materials.status', 'Active')->with('unit');
            }])
            ->orderBy('name')
            ->get();
        $units = Unit::where('status', 'Active')->orderBy('name_en')->get();
        $allMaterials = Material::where('status', 'Active')->with('unit')->orderBy('name_en')->get();
        $warehouseItems = $this->warehouseService->getInStockItems();
        $stats = $this->purchaseService->getStats();
        $nextPoNumber = $this->purchaseService->generateNextPoNumber('PO');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'purchases' => $purchases,
                'stats' => $stats,
                'sites' => $sites,
                'suppliers' => $suppliers,
                'units' => $units,
                'all_materials' => $allMaterials,
                'warehouse_items' => $warehouseItems,
                'next_po_number' => $nextPoNumber,
            ]);
        }

        return view('purchases.index', [
            'purchases' => $purchases,
            'stats' => $stats,
            'sites' => $sites,
            'suppliers' => $suppliers,
            'units' => $units,
            'allMaterials' => $allMaterials,
            'warehouseItems' => $warehouseItems,
            'nextPoNumber' => $nextPoNumber,
        ]);
    }

    /**
     * Get the next auto-generated purchase order number.
     */
    public function nextPoNumber(): JsonResponse
    {
        $poNumber = $this->purchaseService->generateNextPoNumber('PO');

        return response()->json([
            'success' => true,
            'po_number' => $poNumber,
        ]);
    }

    /**
     * Quick-create a new material directly from Purchases & Inward.
     */
    public function quickCreateMaterial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ta' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'grade' => ['nullable', 'string', 'max:100'],
        ]);

        $nameEn = trim((string) $validated['name_en']);
        $nameTa = ! empty($validated['name_ta']) ? trim((string) $validated['name_ta']) : $nameEn;

        // Resolve unit
        $unitId = ! empty($validated['unit_id']) ? (int) $validated['unit_id'] : null;
        $unitName = 'Nos';

        if ($unitId) {
            $unitModel = Unit::find($unitId);
            $unitName = $unitModel?->name_en ?? 'Nos';
        } elseif (! empty($validated['unit'])) {
            $uVal = trim((string) $validated['unit']);
            $unitModel = Unit::where('name_en', $uVal)
                ->orWhere('code', $uVal)
                ->orWhere('name_ta', $uVal)
                ->first();
            if (! $unitModel) {
                $unitModel = Unit::where('name_en', 'like', "%{$uVal}%")->first();
            }
            if ($unitModel) {
                $unitId = $unitModel->id;
                $unitName = $unitModel->name_en;
            } else {
                $firstUnit = Unit::where('status', 'Active')->first();
                $unitId = $firstUnit?->id;
                $unitName = $firstUnit?->name_en ?? $uVal;
            }
        } else {
            $firstUnit = Unit::where('status', 'Active')->first();
            $unitId = $firstUnit?->id;
            $unitName = $firstUnit?->name_en ?? 'Nos';
        }

        // Find or create Material
        $material = Material::where('name_en', $nameEn)->first();
        if (! $material) {
            // Avoid duplicate name_ta unique error
            $existingTa = Material::where('name_ta', $nameTa)->first();
            if ($existingTa) {
                $nameTa = $nameEn;
            }

            $material = Material::create([
                'name_en' => $nameEn,
                'name_ta' => $nameTa,
                'unit_id' => $unitId,
                'status' => 'Active',
            ]);
        } else {
            if ($unitId && ! $material->unit_id) {
                $material->unit_id = $unitId;
                $material->save();
            }
        }

        $price = isset($validated['price']) ? (float) $validated['price'] : 0.0;
        $material->load('unit');
        $unitDisplay = $material->unit?->name_en ?: ($material->unit?->code ?: $unitName);

        return response()->json([
            'success' => true,
            'message' => "Material \"{$material->name_en}\" saved to Material Master successfully.",
            'material' => [
                'id' => $material->id,
                'name' => $material->name_en,
                'name_en' => $material->name_en,
                'name_ta' => $material->name_ta,
                'tamil' => $material->name_ta,
                'unit' => $unitDisplay,
                'unit_id' => $material->unit_id,
                'price' => $price,
                'grade' => ! empty($validated['grade']) ? $validated['grade'] : '1st Quality',
            ],
        ]);
    }

    /**
     * Display the specified purchase record.
     */
    public function show(Request $request, string $encryptedId): View|JsonResponse
    {
        $id = IdCipherService::decrypt($encryptedId);

        $purchase = $id
            ? $this->purchaseService->getById($id)
            : $this->purchaseService->getByPoNumber($encryptedId);

        if (! $purchase) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Purchase not found.'], 404);
            }
            abort(404, 'Purchase order not found.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'purchase' => $purchase,
            ]);
        }

        $id = $purchase->po_number;

        return view('purchases.show', compact('purchase', 'id'));
    }

    /**
     * Store a newly created purchase order / inward record.
     */
    public function store(StorePurchaseRequest $request): JsonResponse|RedirectResponse
    {
        $purchase = $this->purchaseService->create($request->validated());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order recorded successfully.',
                'purchase' => $purchase,
            ]);
        }

        return redirect()->route('purchases.index')->with('success', "Purchase order {$purchase->po_number} recorded.");
    }

    /**
     * Update an existing purchase order.
     */
    public function update(UpdatePurchaseRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $purchase = $this->purchaseService->update($id, $request->validated());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully.',
                'purchase' => $purchase,
            ]);
        }

        return redirect()->route('purchases.index')->with('success', "Purchase order {$purchase->po_number} updated.");
    }

    /**
     * Delete a purchase order.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $deleted = $this->purchaseService->delete($id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'Purchase order deleted.' : 'Purchase order not found.',
            ]);
        }

        return redirect()->route('purchases.index')->with('success', 'Purchase order deleted.');
    }
}
