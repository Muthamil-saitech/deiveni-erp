<?php

namespace App\Repositories\Contracts;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

interface MaterialRepositoryInterface
{
    /**
     * Retrieve all materials ordered with newest/last added first.
     *
     * @return Collection<int, Material>
     */
    public function all(): Collection;

    /**
     * Find a material by ID.
     */
    public function findById(int $id): ?Material;

    /**
     * Create a new material record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Material;

    /**
     * Update an existing material record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Material $material, array $data): Material;

    /**
     * Delete a material record.
     */
    public function delete(Material $material): bool;

    /**
     * Check if a material is used by any suppliers.
     */
    public function isUsedBySuppliers(int $id): bool;

    /**
     * Count total materials.
     */
    public function count(): int;
}
