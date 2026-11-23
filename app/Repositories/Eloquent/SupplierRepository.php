<?php

namespace App\Repositories\Eloquent;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    /**
     * Retrieve all suppliers ordered with newest/last added first.
     *
     * @return Collection<int, Supplier>
     */
    public function all(): Collection
    {
        return Supplier::query()
            ->with(['materials', 'materials.unit'])
            ->withCount('purchaseOrders')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a supplier by ID.
     */
    public function findById(int $id): ?Supplier
    {
        return Supplier::query()
            ->with(['materials', 'materials.unit'])
            ->withCount('purchaseOrders')
            ->find($id);
    }

    /**
     * Create a new supplier record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        $materials = $data['materials'] ?? null;
        unset($data['materials']);

        $supplier = Supplier::query()->create($data);

        if ($materials !== null) {
            $this->syncMaterials($supplier, $materials);
        }

        return $supplier->load('materials');
    }

    /**
     * Update an existing supplier record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $materials = $data['materials'] ?? null;
        unset($data['materials']);

        $supplier->update($data);

        if ($materials !== null) {
            $this->syncMaterials($supplier, $materials);
        }

        return $supplier->refresh()->load('materials');
    }

    /**
     * Delete a supplier record.
     */
    public function delete(Supplier $supplier): bool
    {
        return (bool) $supplier->delete();
    }

    /**
     * Check if a supplier is used in any purchase orders.
     */
    public function isUsedInPurchaseOrders(int $id): bool
    {
        return Supplier::query()
            ->where('id', $id)
            ->whereHas('purchaseOrders')
            ->exists();
    }

    /**
     * Synchronize materials and prices for a supplier.
     *
     * @param  array<int|string, mixed>  $materials
     */
    public function syncMaterials(Supplier $supplier, array $materials): void
    {
        $syncData = [];

        foreach ($materials as $key => $item) {
            $grade = '1st Quality';
            if (is_array($item)) {
                $materialId = $item['material_id'] ?? $item['id'] ?? $key;
                $price = isset($item['price']) && $item['price'] !== '' ? (float) $item['price'] : 0.00;
                $grade = ! empty($item['grade']) ? (string) $item['grade'] : '1st Quality';
            } else {
                $materialId = is_numeric($key) && $key > 0 && ! is_numeric($item) ? $key : (int) $item;
                $price = is_numeric($item) ? (float) $item : 0.00;
            }

            if (is_numeric($materialId) && (int) $materialId > 0) {
                $syncData[(int) $materialId] = [
                    'price' => max(0, $price),
                    'grade' => $grade,
                ];
            }
        }

        $supplier->materials()->sync($syncData);
    }
}
