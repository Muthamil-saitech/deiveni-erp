<?php

namespace App\Services;

use App\Models\Ledger;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Services\Contracts\SupplierServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection as BaseCollection;
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
     * Retrieve grouped financial totals for all suppliers without issuing queries per supplier.
     *
     * @return BaseCollection<int, array{total_purchased: float, total_paid: float, balance_payable: float}>
     */
    public function getFinancialSummaries(): BaseCollection
    {
        $purchased = PurchaseOrder::query()
            ->selectRaw('supplier_id, SUM(total_amount) as total_purchased')
            ->whereNotNull('supplier_id')
            ->where('status', '!=', 'Cancelled')
            ->groupBy('supplier_id')
            ->pluck('total_purchased', 'supplier_id');

        $ledgerPaid = Ledger::query()
            ->selectRaw('supplier_id, SUM(amount) as total_paid')
            ->whereNotNull('supplier_id')
            ->where(function ($query) {
                $query->where('flow', 'expense')->orWhere('flow', 'out');
            })
            ->groupBy('supplier_id')
            ->pluck('total_paid', 'supplier_id');

        $siteCashPaid = SiteCash::query()
            ->selectRaw('supplier_id, SUM(CASE WHEN out_amount > 0 THEN out_amount ELSE amount END) as total_paid')
            ->whereNotNull('supplier_id')
            ->whereNull('ledger_id')
            ->where(function ($query) {
                $query->where('flow_type', 'outflow')
                    ->orWhere('flow_type', 'out')
                    ->orWhere('out_amount', '>', 0);
            })
            ->groupBy('supplier_id')
            ->pluck('total_paid', 'supplier_id');

        return $purchased->keys()
            ->merge($ledgerPaid->keys())
            ->merge($siteCashPaid->keys())
            ->unique()
            ->mapWithKeys(function ($supplierId) use ($purchased, $ledgerPaid, $siteCashPaid) {
                $totalPurchased = (float) ($purchased[$supplierId] ?? 0);
                $totalPaid = (float) ($ledgerPaid[$supplierId] ?? 0) + (float) ($siteCashPaid[$supplierId] ?? 0);

                return [(int) $supplierId => [
                    'total_purchased' => $totalPurchased,
                    'total_paid' => $totalPaid,
                    'balance_payable' => round($totalPurchased - $totalPaid, 2),
                ]];
            });
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
