<?php

namespace App\Services\Contracts;

use App\Models\Material;
use Illuminate\Database\Eloquent\Collection;

interface MaterialServiceInterface
{
    /**
     * Retrieve all materials.
     *
     * @return Collection<int, Material>
     */
    public function getAllMaterials(): Collection;

    /**
     * Find a material by ID.
     */
    public function getMaterialById(int $id): ?Material;

    /**
     * Create a new material.
     *
     * @param  array<string, mixed>  $data
     */
    public function createMaterial(array $data): Material;

    /**
     * Update an existing material.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMaterial(int $id, array $data): Material;

    /**
     * Delete a material by ID.
     */
    public function deleteMaterial(int $id): bool;

    /**
     * Check if a material is currently used by any suppliers.
     */
    public function isMaterialInUse(int $id): bool;

    /**
     * Find an existing material or create a new one as type "Other".
     */
    public function findOrCreateOtherMaterial(string $rawTitle, ?int $unitId = null, ?string $customNameTa = null): Material;
}
