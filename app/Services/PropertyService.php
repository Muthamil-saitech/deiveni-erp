<?php

namespace App\Services;

use App\Models\Property;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Services\Contracts\PropertyServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PropertyService implements PropertyServiceInterface
{
    public function __construct(
        public PropertyRepositoryInterface $propertyRepository
    ) {}

    /**
     * Retrieve all properties.
     *
     * @return Collection<int, Property>
     */
    public function getAllProperties(): Collection
    {
        return $this->propertyRepository->all();
    }

    /**
     * Retrieve all active properties.
     *
     * @return Collection<int, Property>
     */
    public function getActiveProperties(): Collection
    {
        return $this->propertyRepository->findActive();
    }

    /**
     * Find a property by ID.
     */
    public function getPropertyById(int $id): ?Property
    {
        return $this->propertyRepository->findById($id);
    }

    /**
     * Create a new property.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProperty(array $data): Property
    {
        if (isset($data['property_name'])) {
            $data['property_name'] = trim((string) $data['property_name']);
        }

        if (isset($data['property_name_ta'])) {
            $data['property_name_ta'] = trim((string) $data['property_name_ta']) ?: null;
        }

        return $this->propertyRepository->create($data);
    }

    /**
     * Update an existing property.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProperty(int $id, array $data): Property
    {
        $property = $this->propertyRepository->findById($id);

        if (! $property) {
            throw new ModelNotFoundException("Property with ID {$id} not found.");
        }

        if (isset($data['property_name'])) {
            $data['property_name'] = trim((string) $data['property_name']);
        }

        if (isset($data['property_name_ta'])) {
            $data['property_name_ta'] = trim((string) $data['property_name_ta']) ?: null;
        }

        return $this->propertyRepository->update($property, $data);
    }

    /**
     * Check if a property is currently used by any property types.
     */
    public function isPropertyInUse(int $id): bool
    {
        return $this->propertyRepository->isUsedInPropertyTypes($id);
    }

    /**
     * Delete a property by ID.
     */
    public function deleteProperty(int $id): bool
    {
        $property = $this->propertyRepository->findById($id);

        if (! $property) {
            throw new ModelNotFoundException("Property with ID {$id} not found.");
        }

        if ($this->isPropertyInUse($id)) {
            throw new \DomainException("Property \"{$property->property_name}\" cannot be deleted because it is currently used by one or more units in Property Type Master.");
        }

        return $this->propertyRepository->delete($property);
    }

    /**
     * Calculate summary statistics for the property directory.
     *
     * @return array{total_properties: int, active_properties: int, inactive_properties: int}
     */
    public function getPropertyStats(): array
    {
        $properties = $this->propertyRepository->all();
        $total = $properties->count();
        $active = $properties->where('status', 'Active')->count();
        $inactive = $total - $active;

        return [
            'total_properties' => $total,
            'active_properties' => $active,
            'inactive_properties' => $inactive,
        ];
    }
}
