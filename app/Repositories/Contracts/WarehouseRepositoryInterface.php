<?php

namespace App\Repositories\Contracts;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseRepositoryInterface
{
    /**
     * Get all warehouse items with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, WarehouseItem>
     */
    public function all(array $filters = []): Collection;

    /**
     * Find an item by ID.
     */
    public function findById(int $id): ?WarehouseItem;

    /**
     * Find an item by SKU.
     */
    public function findBySku(string $sku): ?WarehouseItem;

    /**
     * Get all items currently in stock (> 0).
     *
     * @return Collection<int, WarehouseItem>
     */
    public function getInStockItems(): Collection;

    /**
     * Create a new warehouse item.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WarehouseItem;

    /**
     * Update an existing warehouse item.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(WarehouseItem $item, array $data): WarehouseItem;

    /**
     * Delete a warehouse item.
     */
    public function delete(WarehouseItem $item): bool;

    /**
     * Log a stock movement and adjust inventory balance.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordMovement(array $data): WarehouseMovement;

    /**
     * Get recent warehouse movements.
     *
     * @return Collection<int, WarehouseMovement>
     */
    public function getMovements(int $limit = 50): Collection;

    /**
     * Get warehouse summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;
}
