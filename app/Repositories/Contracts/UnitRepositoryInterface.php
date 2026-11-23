<?php

namespace App\Repositories\Contracts;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Collection;

interface UnitRepositoryInterface
{
    /**
     * Retrieve all units ordered with newest/last added first.
     *
     * @return Collection<int, Unit>
     */
    public function all(): Collection;

    /**
     * Retrieve all active units ordered by name.
     *
     * @return Collection<int, Unit>
     */
    public function getActive(): Collection;

    /**
     * Find a unit by ID.
     */
    public function findById(int $id): ?Unit;

    /**
     * Find a unit by unique short symbol/code.
     */
    public function findByCode(string $code): ?Unit;

    /**
     * Create a new unit record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Unit;

    /**
     * Update an existing unit record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, array $data): Unit;

    /**
     * Delete a unit record.
     */
    public function delete(Unit $unit): bool;

    /**
     * Check if a unit code exists.
     */
    public function codeExists(string $code, ?int $ignoreId = null): bool;

    /**
     * Check if a unit is used by any materials.
     */
    public function isUsedInMaterials(int $id): bool;

    /**
     * Count total units.
     */
    public function count(): int;
}
