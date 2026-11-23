<?php

namespace App\Repositories\Contracts;

use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;

interface PropertyRepositoryInterface
{
    /**
     * Retrieve all properties ordered with newest first.
     *
     * @return Collection<int, Property>
     */
    public function all(): Collection;

    /**
     * Retrieve all active properties.
     *
     * @return Collection<int, Property>
     */
    public function findActive(): Collection;

    /**
     * Find a property by ID.
     */
    public function findById(int $id): ?Property;

    /**
     * Find a property by name.
     */
    public function findByName(string $name): ?Property;

    /**
     * Create a new property record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Property;

    /**
     * Update an existing property record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Property $property, array $data): Property;

    /**
     * Delete a property record.
     */
    public function delete(Property $property): bool;

    /**
     * Check if a property name exists.
     */
    public function existsByName(string $name, ?int $ignoreId = null): bool;

    /**
     * Check if a property is used by any property types.
     */
    public function isUsedInPropertyTypes(int $id): bool;

    /**
     * Count total property records.
     */
    public function count(): int;
}
