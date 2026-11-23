<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Services\Contracts\UnitServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(
        public UnitServiceInterface $unitService
    ) {}

    /**
     * Display a listing of the measurement units.
     */
    public function index(Request $request): View|JsonResponse
    {
        $units = $this->unitService->getAllUnits();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $units,
            ]);
        }

        return view('masters.units', [
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created measurement unit.
     */
    public function store(StoreUnitRequest $request): JsonResponse|RedirectResponse
    {
        $unit = $this->unitService->createUnit($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Unit \"{$unit->name_en} ({$unit->code})\" added successfully!",
                'data' => $unit,
            ], 201);
        }

        return redirect()->route('units.index')
            ->with('success', "Unit \"{$unit->name_en} ({$unit->code})\" added successfully!");
    }

    /**
     * Display the specified measurement unit.
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitService->getUnitById($id);

        if (! $unit) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement unit not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $unit,
        ]);
    }

    /**
     * Update the specified measurement unit.
     */
    public function update(UpdateUnitRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $unit = $this->unitService->updateUnit($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Unit \"{$unit->name_en} ({$unit->code})\" updated successfully!",
                'data' => $unit,
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', "Unit \"{$unit->name_en} ({$unit->code})\" updated successfully!");
    }

    /**
     * Remove the specified measurement unit.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $unit = $this->unitService->getUnitById($id);

        if (! $unit) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Measurement unit not found.',
                ], 404);
            }

            return redirect()->route('units.index')
                ->with('error', 'Measurement unit not found.');
        }

        if ($this->unitService->isUnitInUse($id)) {
            $message = "Measurement unit \"{$unit->name_en} ({$unit->code})\" cannot be deleted because it is currently used in Material Master.";

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('units.index')
                ->with('error', $message);
        }

        $this->unitService->deleteUnit($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Measurement unit \"{$unit->name_en} ({$unit->code})\" removed successfully.",
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', "Measurement unit \"{$unit->name_en} ({$unit->code})\" removed successfully.");
    }
}
