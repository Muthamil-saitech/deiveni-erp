<?php

namespace App\Repositories\Eloquent;

use App\Models\PropertyType;
use App\Repositories\Contracts\PropertyTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PropertyTypeRepository implements PropertyTypeRepositoryInterface
{
    /**
     * Retrieve all property types ordered with newest/last added first.
     *
     * @return Collection<int, PropertyType>
     */
    public function all(): Collection
    {
        return PropertyType::query()
            ->with('property')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a property type by ID.
     */
    public function findById(int $id): ?PropertyType
    {
        return PropertyType::query()
            ->with('property')
            ->find($id);
    }

    /**
     * Create a new property type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PropertyType
    {
        $propertyType = PropertyType::query()->create($data);

        return $propertyType->load('property');
    }

    /**
     * Update an existing property type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyType $propertyType, array $data): PropertyType
    {
        $propertyType->update($data);

        return $propertyType->refresh()->load('property');
    }

    /**
     * Delete a property type record.
     */
    public function delete(PropertyType $propertyType): bool
    {
        return (bool) $propertyType->delete();
    }

    /**
     * Count total property types.
     */
    public function count(): int
    {
        return PropertyType::query()->count();
    }

    /**
     * Count active property types.
     */
    public function countActive(): int
    {
        return PropertyType::query()->where('status', 'Active')->count();
    }

    /**
     * Calculate monthly expected rent (for active properties).
     */
    public function sumMonthlyRent(): float
    {
        return (float) PropertyType::query()->where('status', 'Active')->sum('monthly_rent');
    }

    /**
     * Calculate total security deposit.
     */
    public function sumSecurityDeposit(): float
    {
        return (float) PropertyType::query()->sum('security_deposit');
    }
}
