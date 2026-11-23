<?php

namespace App\Services;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class WarehouseService implements WarehouseServiceInterface
{
    public function __construct(
        public WarehouseRepositoryInterface $warehouseRepo
    ) {}

    /**
     * Get all items.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, WarehouseItem>
     */
    public function getAllItems(array $filters = []): Collection
    {
        return $this->warehouseRepo->all($filters);
    }

    /**
     * Find item by ID.
     */
    public function getItemById(int $id): ?WarehouseItem
    {
        return $this->warehouseRepo->findById($id);
    }

    /**
     * Get items in stock.
     *
     * @return Collection<int, WarehouseItem>
     */
    public function getInStockItems(): Collection
    {
        return $this->warehouseRepo->getInStockItems();
    }

    /**
     * Create an item.
     *
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data): WarehouseItem
    {
        if (isset($data['cash_amount']) && (! isset($data['unit_rate']) || (float) $data['unit_rate'] == 0)) {
            $data['unit_rate'] = $data['cash_amount'];
        } elseif (isset($data['unit_rate']) && (! isset($data['cash_amount']) || (float) $data['cash_amount'] == 0)) {
            $data['cash_amount'] = $data['unit_rate'];
        }

        if (empty($data['sku'])) {
            $catPrefix = match ($data['category'] ?? '') {
                'Plumbing' => 'WH-FIT-',
                'Hand Tools' => 'WH-TOOL-',
                'Measuring Tools' => 'WH-MEAS-',
                'Power Tools' => 'WH-ELEC-',
                'Safety Gear' => 'WH-SAFE-',
                'Chemicals' => 'WH-CHEM-',
                default => 'WH-MAT-',
            };
            $data['sku'] = $catPrefix.str_pad((string) (WarehouseItem::count() + 1), 2, '0', STR_PAD_LEFT);
        }

        return $this->warehouseRepo->create($data);
    }

    /**
     * Update an item.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(int $id, array $data): WarehouseItem
    {
        if (isset($data['cash_amount']) && ! isset($data['unit_rate'])) {
            $data['unit_rate'] = $data['cash_amount'];
        } elseif (isset($data['unit_rate']) && ! isset($data['cash_amount'])) {
            $data['cash_amount'] = $data['unit_rate'];
        }

        $item = $this->warehouseRepo->findById($id);
        if (! $item) {
            throw new \InvalidArgumentException("Warehouse item #{$id} not found.");
        }

        return $this->warehouseRepo->update($item, $data);
    }

    /**
     * Delete an item.
     */
    public function deleteItem(int $id): bool
    {
        $item = $this->warehouseRepo->findById($id);
        if (! $item) {
            return false;
        }

        return $this->warehouseRepo->delete($item);
    }

    /**
     * Record a movement (Issue to site or restock inward).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordMovement(array $data): WarehouseMovement
    {
        return $this->warehouseRepo->recordMovement($data);
    }

    /**
     * Get movements log.
     *
     * @return Collection<int, WarehouseMovement>
     */
    public function getMovements(int $limit = 50): Collection
    {
        return $this->warehouseRepo->getMovements($limit);
    }

    /**
     * Get stats.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return $this->warehouseRepo->getStats();
    }
}
