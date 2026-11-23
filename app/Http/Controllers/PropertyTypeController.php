<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyTypeRequest;
use App\Http\Requests\UpdatePropertyTypeRequest;
use App\Services\Contracts\PropertyServiceInterface;
use App\Services\Contracts\PropertyTypeServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PropertyTypeController extends Controller
{
    public function __construct(
        public PropertyTypeServiceInterface $propertyTypeService,
        public PropertyServiceInterface $propertyService
    ) {}

    /**
     * Display a listing of the property types.
     */
    public function index(Request $request): View|JsonResponse
    {
        $propertyTypes = $this->propertyTypeService->getAllPropertyTypes();
        $stats = $this->propertyTypeService->getPropertyTypeStats();
        $propertyList = $this->propertyService->getActiveProperties();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $propertyTypes,
                'stats' => $stats,
                'properties' => $propertyList,
            ]);
        }

        return view('masters.property-types', [
            'propertyTypes' => $propertyTypes,
            'stats' => $stats,
            'propertyList' => $propertyList,
        ]);
    }

    /**
     * Store a newly created property type.
     */
    public function store(StorePropertyTypeRequest $request): JsonResponse|RedirectResponse
    {
        $propertyType = $this->propertyTypeService->createPropertyType($request->validated());
        $propertyType->load('property');

        $propTitle = $propertyType->property?->property_name ?? 'Property';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Property Type \"{$propTitle} - {$propertyType->property_type}\" added successfully!",
                'data' => $propertyType,
                'stats' => $this->propertyTypeService->getPropertyTypeStats(),
            ], 201);
        }

        return redirect()->route('property-types.index')
            ->with('success', "Property Type \"{$propTitle} - {$propertyType->property_type}\" added successfully!");
    }

    /**
     * Display the specified property type.
     */
    public function show(int $id): JsonResponse
    {
        $propertyType = $this->propertyTypeService->getPropertyTypeById($id);

        if (! $propertyType) {
            return response()->json([
                'success' => false,
                'message' => 'Property type record not found.',
            ], 404);
        }

        $propertyType->load('property');

        return response()->json([
            'success' => true,
            'data' => $propertyType,
        ]);
    }

    /**
     * Update the specified property type.
     */
    public function update(UpdatePropertyTypeRequest $request, int $id): JsonResponse|RedirectResponse
    {
        $propertyType = $this->propertyTypeService->updatePropertyType($id, $request->validated());
        $propertyType->load('property');

        $propTitle = $propertyType->property?->property_name ?? 'Property';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Property Type \"{$propTitle} - {$propertyType->property_type}\" updated successfully!",
                'data' => $propertyType,
                'stats' => $this->propertyTypeService->getPropertyTypeStats(),
            ]);
        }

        return redirect()->route('property-types.index')
            ->with('success', "Property Type \"{$propTitle} - {$propertyType->property_type}\" updated successfully!");
    }

    /**
     * Remove the specified property type.
     */
    public function destroy(int $id): JsonResponse|RedirectResponse
    {
        $propertyType = $this->propertyTypeService->getPropertyTypeById($id);

        if (! $propertyType) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Property type record not found.',
                ], 404);
            }

            return redirect()->route('property-types.index')
                ->with('error', 'Property type record not found.');
        }

        $propTitle = $propertyType->property?->property_name ?? 'Property';
        $title = "{$propTitle} - {$propertyType->property_type}";
        $this->propertyTypeService->deletePropertyType($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Property Type \"{$title}\" removed successfully.",
                'stats' => $this->propertyTypeService->getPropertyTypeStats(),
            ]);
        }

        return redirect()->route('property-types.index')
            ->with('success', "Property Type \"{$title}\" removed successfully.");
    }
}
