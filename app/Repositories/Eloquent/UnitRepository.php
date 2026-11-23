<?php

namespace App\Repositories\Eloquent;

use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UnitRepository implements UnitRepositoryInterface
{
    /**
     * Retrieve all units ordered with newest/last added first.
     *
     * @return Collection<int, Unit>
     */
    public function all(): Collection
    {
        return Unit::query()
            ->withCount('materials')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Retrieve all active units ordered by name.
     *
     * @return Collection<int, Unit>
     */
    public function getActive(): Collection
    {
        return Unit::query()
            ->where('status', 'Active')
            ->orderBy('name_en', 'asc')
            ->get();
    }

    /**
     * Find a unit by ID.
     */
    public function findById(int $id): ?Unit
    {
        return Unit::query()->find($id);
    }

    /**
     * Find a unit by unique short symbol/code.
     */
    public function findByCode(string $code): ?Unit
    {
        return Unit::query()->where('code', $code)->first();
    }

    /**
     * Create a new unit record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Unit
    {
        return Unit::query()->create($data);
    }

    /**
     * Update an existing unit record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->refresh();
    }

    /**
     * Delete a unit record.
     */
    public function delete(Unit $unit): bool
    {
        return (bool) $unit->delete();
    }

    /**
     * Check if a unit code exists.
     */
    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return Unit::query()
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Check if a unit is used by any materials.
     */
    public function isUsedInMaterials(int $id): bool
    {
        return Unit::query()
            ->where('id', $id)
            ->whereHas('materials')
            ->exists();
    }

    /**
     * Count total units.
     */
    public function count(): int
    {
        return Unit::query()->count();
    }
}
