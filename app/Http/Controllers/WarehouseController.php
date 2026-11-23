<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseMovementRequest;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\UnitServiceInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        public WarehouseServiceInterface $warehouseService,
        public ProjectSiteServiceInterface $projectSiteService,
        public UnitServiceInterface $unitService,
    ) {}

    /**
     * Display the central warehouse store & tool inventory.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->all();
        $items = $this->warehouseService->getAllItems($filters);
        $movements = $this->warehouseService->getMovements(30);
        $stats = $this->warehouseService->getStats();
        $sites = $this->projectSiteService->getAllSites();
        $units = $this->unitService->getActiveUnits();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'movements' => $movements,
                'stats' => $stats,
                'sites' => $sites,
                'units' => $units,
            ]);
        }

        return view('warehouse.index', [
            'items' => $items,
            'movements' => $movements,
            'stats' => $stats,
            'sites' => $sites,
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created warehouse item.
     */
    public function storeItem(StoreWarehouseRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        if (isset($validated['cash_amount']) && ! isset($validated['unit_rate'])) {
            $validated['unit_rate'] = $validated['cash_amount'];
        } elseif (isset($validated['unit_rate']) && ! isset($validated['cash_amount'])) {
            $validated['cash_amount'] = $validated['unit_rate'];
        }

        $item = $this->warehouseService->createItem($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Warehouse item added successfully.',
                'item' => $item,
            ]);
        }

        return redirect()->route('warehouse.index')->with('success', "Item {$item->name_en} added successfully.");
    }

    /**
     * Update an existing warehouse item.
     */
    public function updateItem(UpdateWarehouseRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        if (isset($validated['cash_amount']) && ! isset($validated['unit_rate'])) {
            $validated['unit_rate'] = $validated['cash_amount'];
        } elseif (isset($validated['unit_rate']) && ! isset($validated['cash_amount'])) {
            $validated['cash_amount'] = $validated['unit_rate'];
        }

        $item = $this->warehouseService->updateItem($id, $validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Warehouse item updated successfully.',
                'item' => $item,
            ]);
        }

        return redirect()->route('warehouse.index')->with('success', "Item {$item->name_en} updated successfully.");
    }

    /**
     * Delete a warehouse item.
     */
    public function destroyItem(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $deleted = $this->warehouseService->deleteItem($id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? 'Item deleted.' : 'Item not found.',
            ]);
        }

        return redirect()->route('warehouse.index')->with('success', 'Item deleted.');
    }

    /**
     * Record a stock movement (Issue to site or restock inward).
     */
    public function recordMovement(StoreWarehouseMovementRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $movement = $this->warehouseService->recordMovement($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock movement recorded successfully.',
                'movement' => $movement->load('item'),
            ]);
        }

        return redirect()->route('warehouse.index')->with('success', 'Stock movement recorded.');
    }

    /**
     * Get items in stock (for API / dropdowns).
     */
    public function inStockItems(): JsonResponse
    {
        $items = $this->warehouseService->getInStockItems();

        return response()->json([
            'success' => true,
            'items' => $items,
        ]);
    }
}
