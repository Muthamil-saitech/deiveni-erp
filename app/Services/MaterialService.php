<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Unit;
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

    /**
     * Find an existing material or create a new one as type "Other".
     */
    public function findOrCreateOtherMaterial(string $rawTitle, ?int $unitId = null, ?string $customNameTa = null, bool $updateIfExists = false): Material
    {
        $rawTitle = trim($rawTitle);
        $nameEn = $rawTitle;
        $nameTa = $customNameTa ? trim($customNameTa) : $rawTitle;

        if (! $customNameTa) {
            if (str_contains($rawTitle, ' - ')) {
                $parts = explode(' - ', $rawTitle, 2);
                $p0 = trim($parts[0]);
                $p1 = trim($parts[1]);
                if ($p0 !== '' && $p1 !== '') {
                    $nameEn = $p0;
                    $nameTa = $p1;
                }
            } elseif (preg_match('/^(.*?)\s*\((.*?)\)$/u', $rawTitle, $matches)) {
                $p0 = trim($matches[1]);
                $p1 = trim($matches[2]);
                if ($p0 !== '' && $p1 !== '') {
                    $nameEn = $p0;
                    $nameTa = $p1;
                }
            }
        }

        $existing = Material::query()
            ->with('unit')
            ->where(function ($q) use ($rawTitle, $nameEn, $nameTa) {
                $q->whereRaw('LOWER(name_en) = ?', [strtolower($rawTitle)])
                    ->orWhereRaw('LOWER(name_ta) = ?', [strtolower($rawTitle)])
                    ->orWhereRaw('LOWER(name_en) = ?', [strtolower($nameEn)])
                    ->orWhereRaw('LOWER(name_ta) = ?', [strtolower($nameTa)]);
            })
            ->first();

        if (! $existing) {
            $lowerRaw = strtolower($rawTitle);
            $existing = Material::all()->first(function ($m) use ($lowerRaw) {
                $combinedDash = strtolower(trim("{$m->name_en} - {$m->name_ta}"));
                $combinedParen = strtolower(trim("{$m->name_en} ({$m->name_ta})"));

                return $combinedDash === $lowerRaw || $combinedParen === $lowerRaw;
            });
        }

        if ($existing) {
            if ($updateIfExists) {
                $dirty = false;
                if ($unitId && (int) $existing->unit_id !== $unitId) {
                    $existing->unit_id = $unitId;
                    $dirty = true;
                }
                if ($customNameTa !== null && trim($customNameTa) !== '' && $existing->name_ta !== trim($customNameTa)) {
                    $trimmedTa = trim($customNameTa);
                    if (! Material::where('name_ta', $trimmedTa)->where('id', '!=', $existing->id)->exists()) {
                        $existing->name_ta = $trimmedTa;
                        $dirty = true;
                    }
                }
                if ($dirty) {
                    $existing->save();
                    $existing->load('unit');
                }
            }

            return $existing;
        }

        if (! $unitId) {
            $defaultUnit = Unit::where('code', 'NOS')->first()
                ?? Unit::where('name_en', 'like', '%Nos%')->first()
                ?? Unit::where('status', 'Active')->first()
                ?? Unit::first();
            $unitId = $defaultUnit?->id;
        }

        $finalEn = $nameEn;
        $finalTa = $nameTa;

        if (Material::where('name_en', $finalEn)->exists()) {
            $finalEn = $finalEn.' (Custom)';
        }
        if (Material::where('name_ta', $finalTa)->exists()) {
            $finalTa = $finalTa.' (புதியது)';
        }

        return Material::create([
            'name_en' => $finalEn,
            'name_ta' => $finalTa,
            'unit_id' => $unitId,
            'type' => Material::TYPE_OTHER,
            'status' => 'Active',
        ])->load('unit');
    }
}
