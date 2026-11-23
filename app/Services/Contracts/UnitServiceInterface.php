<?php

namespace App\Services\Contracts;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

interface UnitServiceInterface
{
    /**
     * Retrieve all units.
     *
     * @return Collection<int, Unit>
     */
    public function getAllUnits(): Collection;

    /**
     * Retrieve all active units.
     *
     * @return Collection<int, Unit>
     */
    public function getActiveUnits(): Collection;

    /**
     * Find a unit by ID.
     */
    public function getUnitById(int $id): ?Unit;

    /**
     * Create a new unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUnit(array $data): Unit;

    /**
     * Update an existing unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUnit(int $id, array $data): Unit;

    /**
     * Delete a unit by ID.
     */
    public function deleteUnit(int $id): bool;

    /**
     * Check if a unit is currently used by any materials.
     */
    public function isUnitInUse(int $id): bool;
}
