<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
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
        $materials = $this->materialService->getAllMaterials();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $suppliers,
                'materials' => $materials,
            ]);
        }

        return view('masters.suppliers', [
            'suppliers' => $suppliers,
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

        return view('masters.supplier-detail', [
            'supplier' => $supplier,
        ]);
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
