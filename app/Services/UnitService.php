<?php

namespace App\Services;

use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Services\Contracts\UnitServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class UnitService implements UnitServiceInterface
{
    public function __construct(
        public UnitRepositoryInterface $unitRepository
    ) {}

    /**
     * Retrieve all units.
     *
     * @return Collection<int, Unit>
     */
    public function getAllUnits(): Collection
    {
        return $this->unitRepository->all();
    }

    /**
     * Retrieve all active units.
     *
     * @return Collection<int, Unit>
     */
    public function getActiveUnits(): Collection
    {
        return $this->unitRepository->getActive();
    }

    /**
     * Find a unit by ID.
     */
    public function getUnitById(int $id): ?Unit
    {
        return $this->unitRepository->findById($id);
    }

    /**
     * Create a new unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUnit(array $data): Unit
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim((string) $data['code']));
        }

        if (isset($data['name_en'])) {
            $data['name_en'] = trim((string) $data['name_en']);
        }

        if (isset($data['name_ta'])) {
            $data['name_ta'] = trim((string) $data['name_ta']);
        }

        if (isset($data['usage'])) {
            $data['usage'] = trim((string) $data['usage']);
        }

        return $this->unitRepository->create($data);
    }

    /**
     * Update an existing unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUnit(int $id, array $data): Unit
    {
        $unit = $this->unitRepository->findById($id);

        if (! $unit) {
            throw new ModelNotFoundException("Unit with ID {$id} not found.");
        }

        if (isset($data['code'])) {
            $data['code'] = strtoupper(trim((string) $data['code']));
        }

        if (isset($data['name_en'])) {
            $data['name_en'] = trim((string) $data['name_en']);
        }

        if (isset($data['name_ta'])) {
            $data['name_ta'] = trim((string) $data['name_ta']);
        }

        if (isset($data['usage'])) {
            $data['usage'] = trim((string) $data['usage']);
        }

        return $this->unitRepository->update($unit, $data);
    }

    /**
     * Check if a unit is currently used by any materials.
     */
    public function isUnitInUse(int $id): bool
    {
        return $this->unitRepository->isUsedInMaterials($id);
    }

    /**
     * Delete a unit by ID.
     */
    public function deleteUnit(int $id): bool
    {
        $unit = $this->unitRepository->findById($id);

        if (! $unit) {
            throw new ModelNotFoundException("Unit with ID {$id} not found.");
        }

        if ($this->isUnitInUse($id)) {
            throw ValidationException::withMessages([
                'unit' => ["Measurement unit \"{$unit->name_en} ({$unit->code})\" cannot be deleted because it is currently used in Material Master."],
            ]);
        }

        return $this->unitRepository->delete($unit);
    }
}
