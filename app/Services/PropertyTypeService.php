<?php

namespace App\Services;

use App\Models\PropertyType;
use App\Repositories\Contracts\PropertyTypeRepositoryInterface;
use App\Services\Contracts\PropertyTypeServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PropertyTypeService implements PropertyTypeServiceInterface
{
    public function __construct(
        public PropertyTypeRepositoryInterface $propertyTypeRepository
    ) {}

    /**
     * Retrieve all property types.
     *
     * @return Collection<int, PropertyType>
     */
    public function getAllPropertyTypes(): Collection
    {
        return $this->propertyTypeRepository->all();
    }

    /**
     * Find a property type by ID.
     */
    public function getPropertyTypeById(int $id): ?PropertyType
    {
        return $this->propertyTypeRepository->findById($id);
    }

    /**
     * Create a new property type.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPropertyType(array $data): PropertyType
    {
        if (! isset($data['security_deposit']) || $data['security_deposit'] === null || $data['security_deposit'] === '') {
            $data['security_deposit'] = 0;
        }

        if (! isset($data['rent_due_day']) || empty($data['rent_due_day'])) {
            $data['rent_due_day'] = '1st of Month';
        }

        if (isset($data['floor_type'])) {
            $data['floor_type'] = trim($data['floor_type']);
        }

        return $this->propertyTypeRepository->create($data);
    }

    /**
     * Update an existing property type.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePropertyType(int $id, array $data): PropertyType
    {
        $propertyType = $this->propertyTypeRepository->findById($id);

        if (! $propertyType) {
            throw new ModelNotFoundException("Property type with ID {$id} not found.");
        }

        if (! isset($data['security_deposit']) || $data['security_deposit'] === null || $data['security_deposit'] === '') {
            $data['security_deposit'] = 0;
        }

        if (isset($data['floor_type'])) {
            $data['floor_type'] = trim($data['floor_type']);
        }

        return $this->propertyTypeRepository->update($propertyType, $data);
    }

    /**
     * Delete a property type by ID.
     */
    public function deletePropertyType(int $id): bool
    {
        $propertyType = $this->propertyTypeRepository->findById($id);

        if (! $propertyType) {
            throw new ModelNotFoundException("Property type with ID {$id} not found.");
        }

        return $this->propertyTypeRepository->delete($propertyType);
    }

    /**
     * Retrieve computed property type directory statistics.
     *
     * @return array{total_properties: int, active_properties: int, monthly_expected_rent: float, total_security_deposit: float}
     */
    public function getPropertyTypeStats(): array
    {
        return [
            'total_properties' => $this->propertyTypeRepository->count(),
            'active_properties' => $this->propertyTypeRepository->countActive(),
            'monthly_expected_rent' => $this->propertyTypeRepository->sumMonthlyRent(),
            'total_security_deposit' => $this->propertyTypeRepository->sumSecurityDeposit(),
        ];
    }
}
