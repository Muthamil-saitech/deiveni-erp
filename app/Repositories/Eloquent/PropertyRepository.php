<?php

namespace App\Repositories\Eloquent;

use App\Models\Property;
use App\Models\PropertyType;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PropertyRepository implements PropertyRepositoryInterface
{
    /**
     * Retrieve all properties ordered with newest/last added first.
     *
     * @return Collection<int, Property>
     */
    public function all(): Collection
    {
        return Property::query()
            ->withCount('propertyTypes')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Retrieve all active properties ordered alphabetically.
     *
     * @return Collection<int, Property>
     */
    public function findActive(): Collection
    {
        return Property::query()
            ->where('status', 'Active')
            ->orderBy('property_name', 'asc')
            ->get();
    }

    /**
     * Find a property by ID.
     */
    public function findById(int $id): ?Property
    {
        return Property::query()->find($id);
    }

    /**
     * Find a property by name.
     */
    public function findByName(string $name): ?Property
    {
        return Property::query()
            ->where('property_name', trim($name))
            ->first();
    }

    /**
     * Create a new property record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Property
    {
        return Property::query()->create($data);
    }

    /**
     * Update an existing property record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Property $property, array $data): Property
    {
        $property->update($data);

        return $property->refresh();
    }

    /**
     * Delete a property record.
     */
    public function delete(Property $property): bool
    {
        return (bool) $property->delete();
    }

    /**
     * Check if a property name exists.
     */
    public function existsByName(string $name, ?int $ignoreId = null): bool
    {
        return Property::query()
            ->where('property_name', trim($name))
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Check if a property is used by any property types.
     */
    public function isUsedInPropertyTypes(int $id): bool
    {
        $property = $this->findById($id);

        if (! $property) {
            return false;
        }

        return PropertyType::query()
            ->where('property_id', $property->id)
            ->exists();
    }

    /**
     * Count total property records.
     */
    public function count(): int
    {
        return Property::query()->count();
    }
}
