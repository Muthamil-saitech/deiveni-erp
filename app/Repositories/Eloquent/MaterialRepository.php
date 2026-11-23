<?php

namespace App\Repositories\Eloquent;

use App\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaterialRepository implements MaterialRepositoryInterface
{
    /**
     * Retrieve all materials ordered with newest/last added first.
     *
     * @return Collection<int, Material>
     */
    public function all(): Collection
    {
        return Material::query()
            ->with('unit')
            ->withCount('suppliers')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a material by ID.
     */
    public function findById(int $id): ?Material
    {
        return Material::query()->with('unit')->find($id);
    }

    /**
     * Create a new material record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Material
    {
        return Material::query()->create($data)->load('unit');
    }

    /**
     * Update an existing material record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Material $material, array $data): Material
    {
        $material->update($data);

        return $material->refresh()->load('unit');
    }

    /**
     * Delete a material record.
     */
    public function delete(Material $material): bool
    {
        return (bool) $material->delete();
    }

    /**
     * Check if a material is used by any suppliers.
     */
    public function isUsedBySuppliers(int $id): bool
    {
        return Material::query()
            ->where('id', $id)
            ->whereHas('suppliers')
            ->exists();
    }

    /**
     * Count total materials.
     */
    public function count(): int
    {
        return Material::query()->count();
    }
}
