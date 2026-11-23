<?php

namespace App\Repositories\Contracts;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Collection;

interface PropertyTypeRepositoryInterface
{
    /**
     * Retrieve all property types ordered with newest/last added first.
     *
     * @return Collection<int, PropertyType>
     */
    public function all(): Collection;

    /**
     * Find a property type by ID.
     */
    public function findById(int $id): ?PropertyType;

    /**
     * Create a new property type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PropertyType;

    /**
     * Update an existing property type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyType $propertyType, array $data): PropertyType;

    /**
     * Delete a property type record.
     */
    public function delete(PropertyType $propertyType): bool;

    /**
     * Count total property types.
     */
    public function count(): int;

    /**
     * Count active property types.
     */
    public function countActive(): int;

    /**
     * Calculate monthly expected rent (for active properties).
     */
    public function sumMonthlyRent(): float;

    /**
     * Calculate total security deposit.
     */
    public function sumSecurityDeposit(): float;
}
