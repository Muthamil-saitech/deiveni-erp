<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Services\Contracts\MaterialServiceInterface;
use App\Services\Contracts\UnitServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function __construct(
        public MaterialServiceInterface $materialService,
        public UnitServiceInterface $unitService,
    ) {}

    /**
     * Display a listing of the materials.
     */
    public function index(Request $request): View|JsonResponse
    {
        $materials = $this->materialService->getAllMaterials();
        $units = $this->unitService->getActiveUnits();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $materials,
                'units' => $units,
            ]);
        }

        return view('masters.materials', [
            'materials' => $materials,
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created material.
     */
    public function store(StoreMaterialRequest $request): JsonResponse|RedirectResponse
    {
        $material = $this->materialService->createMaterial($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Material \"{$material->name_en}\" added successfully!",
                'data' => $material,
            ], 201);
        }

        return redirect()->route('materials.index')
            ->with('success', "Material \"{$material->name_en}\" added successfully!");
    }

    /**
     * Display the specified material.
     */
    public function show(int $id): JsonResponse
    {
        $material = $this->materialService->getMaterialById($id);

        if (! $material) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $material,
        ]);
    }

    /**
     * Update the specified material.
     */
    public function update(UpdateMaterialRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $material = $this->materialService->updateMaterial($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Material \"{$material->name_en}\" updated successfully!",
                'data' => $material,
            ]);
        }

        return redirect()->route('materials.index')
            ->with('success', "Material \"{$material->name_en}\" updated successfully!");
    }

    /**
     * Remove the specified material.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $material = $this->materialService->getMaterialById($id);

        if (! $material) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Material not found.',
                ], 404);
            }

            return redirect()->route('materials.index')
                ->with('error', 'Material not found.');
        }

        if ($this->materialService->isMaterialInUse($id)) {
            $message = "Material \"{$material->name_en}\" cannot be deleted because it is currently used by one or more suppliers in Supplier Master.";

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('materials.index')
                ->with('error', $message);
        }

        $this->materialService->deleteMaterial($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Material \"{$material->name_en}\" removed successfully.",
            ]);
        }

        return redirect()->route('materials.index')
            ->with('success', "Material \"{$material->name_en}\" removed successfully.");
    }
}
