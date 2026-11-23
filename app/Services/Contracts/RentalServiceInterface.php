<?php

namespace App\Services\Contracts;

/**
 * @deprecated Use App\Services\Contracts\PropertyTypeServiceInterface instead.
 */
interface RentalServiceInterface extends PropertyTypeServiceInterface
{
    public function getAllRentals();

    public function getRentalById(int $id);

    public function createRental(array $data);

    public function updateRental(int $id, array $data);

    public function deleteRental(int $id): bool;

    public function getRentalStats(): array;
}
