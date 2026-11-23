<?php

namespace App\Services\Contracts;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Collection;

interface PropertyTypeServiceInterface
{
    /**
     * Retrieve all property types.
     *
     * @return Collection<int, PropertyType>
     */
    public function getAllPropertyTypes(): Collection;

    /**
     * Find a property type by ID.
     */
    public function getPropertyTypeById(int $id): ?PropertyType;

    /**
     * Create a new property type.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPropertyType(array $data): PropertyType;

    /**
     * Update an existing property type.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePropertyType(int $id, array $data): PropertyType;

    /**
     * Delete a property type by ID.
     */
    public function deletePropertyType(int $id): bool;

    /**
     * Retrieve computed property type directory statistics.
     *
     * @return array{total_properties: int, active_properties: int, monthly_expected_rent: float, total_security_deposit: float}
     */
    public function getPropertyTypeStats(): array;
}
