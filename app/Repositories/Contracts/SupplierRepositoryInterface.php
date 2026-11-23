<?php

namespace App\Repositories\Contracts;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    /**
     * Retrieve all suppliers ordered by newest first.
     *
     * @return Collection<int, Supplier>
     */
    public function all(): Collection;

    /**
     * Find a supplier by ID.
     */
    public function findById(int $id): ?Supplier;

    /**
     * Create a new supplier record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier;

    /**
     * Update an existing supplier record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier;

    /**
     * Delete a supplier record.
     */
    public function delete(Supplier $supplier): bool;

    /**
     * Check if a supplier is used in any purchase orders.
     */
    public function isUsedInPurchaseOrders(int $id): bool;

    /**
     * Synchronize materials and prices for a supplier.
     *
     * @param  array<int|string, mixed>  $materials
     */
    public function syncMaterials(Supplier $supplier, array $materials): void;
}
