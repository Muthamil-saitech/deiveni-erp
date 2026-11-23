<?php

namespace App\Services\Contracts;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

interface SupplierServiceInterface
{
    /**
     * Retrieve all suppliers.
     *
     * @return Collection<int, Supplier>
     */
    public function getAllSuppliers(): Collection;

    /**
     * Find a supplier by ID.
     */
    public function getSupplierById(int $id): ?Supplier;

    /**
     * Create a new supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSupplier(array $data): Supplier;

    /**
     * Update an existing supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSupplier(int $id, array $data): Supplier;

    /**
     * Delete a supplier by ID.
     */
    public function deleteSupplier(int $id): bool;

    /**
     * Check if a supplier is currently used in any purchase orders.
     */
    public function isSupplierInUse(int $id): bool;

    /**
     * Synchronize materials with pricing for a supplier.
     *
     * @param  array<int|string, mixed>  $materials
     */
    public function syncSupplierMaterials(int $id, array $materials): Supplier;
}
