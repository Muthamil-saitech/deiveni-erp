<?php

namespace App\Services\Contracts;

use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;

interface PropertyServiceInterface
{
    /**
     * Retrieve all properties.
     *
     * @return Collection<int, Property>
     */
    public function getAllProperties(): Collection;

    /**
     * Retrieve all active properties.
     *
     * @return Collection<int, Property>
     */
    public function getActiveProperties(): Collection;

    /**
     * Find a property by ID.
     */
    public function getPropertyById(int $id): ?Property;

    /**
     * Create a new property.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProperty(array $data): Property;

    /**
     * Update an existing property.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProperty(int $id, array $data): Property;

    /**
     * Check if a property is currently used by any property types.
     */
    public function isPropertyInUse(int $id): bool;

    /**
     * Delete a property by ID.
     */
    public function deleteProperty(int $id): bool;

    /**
     * Calculate summary statistics for the property directory.
     *
     * @return array{total_properties: int, active_properties: int, inactive_properties: int}
     */
    public function getPropertyStats(): array;
}
