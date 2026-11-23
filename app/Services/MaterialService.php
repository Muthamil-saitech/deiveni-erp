<?php

namespace App\Services;

use App\Models\Material;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Services\Contracts\MaterialServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class MaterialService implements MaterialServiceInterface
{
    public function __construct(
        public MaterialRepositoryInterface $materialRepository
    ) {}

    /**
     * Retrieve all materials.
     *
     * @return Collection<int, Material>
     */
    public function getAllMaterials(): Collection
    {
        return $this->materialRepository->all();
    }

    /**
     * Find a material by ID.
     */
    public function getMaterialById(int $id): ?Material
    {
        return $this->materialRepository->findById($id);
    }

    /**
     * Create a new material.
     *
     * @param  array<string, mixed>  $data
     */
    public function createMaterial(array $data): Material
    {
        return $this->materialRepository->create($data);
    }

    /**
     * Update an existing material.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMaterial(int $id, array $data): Material
    {
        $material = $this->materialRepository->findById($id);

        if (! $material) {
            throw new ModelNotFoundException("Material with ID {$id} not found.");
        }

        return $this->materialRepository->update($material, $data);
    }

    /**
     * Check if a material is currently used by any suppliers.
     */
    public function isMaterialInUse(int $id): bool
    {
        return $this->materialRepository->isUsedBySuppliers($id);
    }

    /**
     * Delete a material by ID.
     */
    public function deleteMaterial(int $id): bool
    {
        $material = $this->materialRepository->findById($id);

        if (! $material) {
            throw new ModelNotFoundException("Material with ID {$id} not found.");
        }

        if ($this->isMaterialInUse($id)) {
            throw ValidationException::withMessages([
                'material' => ["Material \"{$material->name_en}\" cannot be deleted because it is currently used in Supplier Master."],
            ]);
        }

        return $this->materialRepository->delete($material);
    }
}
