<?php

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Services\Contracts\SupplierServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class SupplierService implements SupplierServiceInterface
{
    public function __construct(
        public SupplierRepositoryInterface $supplierRepository
    ) {}

    /**
     * Retrieve all suppliers.
     *
     * @return Collection<int, Supplier>
     */
    public function getAllSuppliers(): Collection
    {
        return $this->supplierRepository->all();
    }

    /**
     * Find a supplier by ID.
     */
    public function getSupplierById(int $id): ?Supplier
    {
        return $this->supplierRepository->findById($id);
    }

    /**
     * Create a new supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function createSupplier(array $data): Supplier
    {
        $data['status'] = $data['status'] ?? 'Active';

        return $this->supplierRepository->create($data);
    }

    /**
     * Update an existing supplier.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSupplier(int $id, array $data): Supplier
    {
        $supplier = $this->supplierRepository->findById($id);

        if (! $supplier) {
            throw new ModelNotFoundException("Supplier with ID {$id} not found.");
        }

        return $this->supplierRepository->update($supplier, $data);
    }

    /**
     * Check if a supplier is currently used in any purchase orders.
     */
    public function isSupplierInUse(int $id): bool
    {
        return $this->supplierRepository->isUsedInPurchaseOrders($id);
    }

    /**
     * Delete a supplier by ID.
     */
    public function deleteSupplier(int $id): bool
    {
        $supplier = $this->supplierRepository->findById($id);

        if (! $supplier) {
            throw new ModelNotFoundException("Supplier with ID {$id} not found.");
        }

        if ($this->isSupplierInUse($id)) {
            throw ValidationException::withMessages([
                'supplier' => ["Supplier \"{$supplier->name}\" cannot be deleted because it is currently used in Purchase Orders."],
            ]);
        }

        return $this->supplierRepository->delete($supplier);
    }

    /**
     * Synchronize materials with pricing for a supplier.
     *
     * @param  array<int|string, mixed>  $materials
     */
    public function syncSupplierMaterials(int $id, array $materials): Supplier
    {
        $supplier = $this->supplierRepository->findById($id);

        if (! $supplier) {
            throw new ModelNotFoundException("Supplier with ID {$id} not found.");
        }

        $this->supplierRepository->syncMaterials($supplier, $materials);

        return $supplier->refresh()->load('materials');
    }
}
