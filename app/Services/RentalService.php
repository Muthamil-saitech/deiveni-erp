<?php

namespace App\Services;

use App\Models\PropertyType;
use App\Repositories\Contracts\RentalRepositoryInterface;
use App\Services\Contracts\RentalServiceInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @deprecated Use App\Services\PropertyTypeService instead.
 */
class RentalService extends PropertyTypeService implements RentalServiceInterface
{
    public function __construct(
        public RentalRepositoryInterface $rentalRepository
    ) {
        parent::__construct($this->rentalRepository);
    }

    public function getAllRentals(): Collection
    {
        return $this->getAllPropertyTypes();
    }

    public function getRentalById(int $id): ?PropertyType
    {
        return $this->getPropertyTypeById($id);
    }

    public function createRental(array $data): PropertyType
    {
        return $this->createPropertyType($data);
    }

    public function updateRental(int $id, array $data): PropertyType
    {
        return $this->updatePropertyType($id, $data);
    }

    public function deleteRental(int $id): bool
    {
        return $this->deletePropertyType($id);
    }

    public function getRentalStats(): array
    {
        return $this->getPropertyTypeStats();
    }
}
