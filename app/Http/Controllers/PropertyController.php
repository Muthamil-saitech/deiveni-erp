<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Services\Contracts\PropertyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(
        public PropertyServiceInterface $propertyService
    ) {}

    /**
     * Display a listing of properties.
     */
    public function index(Request $request): View|JsonResponse
    {
        $properties = $this->propertyService->getAllProperties();
        $stats = $this->propertyService->getPropertyStats();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $properties,
                'stats' => $stats,
            ]);
        }

        return view('masters.properties', [
            'properties' => $properties,
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created property.
     */
    public function store(StorePropertyRequest $request): JsonResponse|RedirectResponse
    {
        $property = $this->propertyService->createProperty($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Property \"{$property->property_name}\" added successfully!",
                'data' => $property,
                'stats' => $this->propertyService->getPropertyStats(),
            ], 201);
        }

        return redirect()->route('properties.index')
            ->with('success', "Property \"{$property->property_name}\" added successfully!");
    }

    /**
     * Display the specified property.
     */
    public function show(int $id): JsonResponse
    {
        $property = $this->propertyService->getPropertyById($id);

        if (! $property) {
            return response()->json([
                'success' => false,
                'message' => 'Property record not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $property,
        ]);
    }

    /**
     * Update the specified property.
     */
    public function update(UpdatePropertyRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $property = $this->propertyService->updateProperty($id, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Property \"{$property->property_name}\" updated successfully!",
                'data' => $property,
                'stats' => $this->propertyService->getPropertyStats(),
            ]);
        }

        return redirect()->route('properties.index')
            ->with('success', "Property \"{$property->property_name}\" updated successfully!");
    }

    /**
     * Remove the specified property.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $property = $this->propertyService->getPropertyById($id);

        if (! $property) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property record not found.',
                ], 404);
            }

            return redirect()->route('properties.index')
                ->with('error', 'Property record not found.');
        }

        if ($this->propertyService->isPropertyInUse($id)) {
            $message = "Property \"{$property->property_name}\" cannot be deleted because it is currently used in Property Type Master (இந்த சொத்து ஏற்கனவே சொத்து வகைகளில் பயன்படுத்தப்படுவதால் நீக்க முடியாது).";

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('properties.index')
                ->with('error', $message);
        }

        $propertyName = $property->property_name;
        $this->propertyService->deleteProperty($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Property \"{$propertyName}\" removed successfully.",
                'stats' => $this->propertyService->getPropertyStats(),
            ]);
        }

        return redirect()->route('properties.index')
            ->with('success', "Property \"{$propertyName}\" removed successfully.");
    }
}
