<?php

namespace App\Services\Contracts;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Illuminate\Database\Eloquent\Collection;

interface WarehouseServiceInterface
{
    /**
     * Get all items.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, WarehouseItem>
     */
    public function getAllItems(array $filters = []): Collection;

    /**
     * Find item by ID.
     */
    public function getItemById(int $id): ?WarehouseItem;

    /**
     * Get items in stock.
     *
     * @return Collection<int, WarehouseItem>
     */
    public function getInStockItems(): Collection;

    /**
     * Create an item.
     *
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data): WarehouseItem;

    /**
     * Update an item.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(int $id, array $data): WarehouseItem;

    /**
     * Delete an item.
     */
    public function deleteItem(int $id): bool;

    /**
     * Record a movement (Issue to site or restock inward).
     *
     * @param  array<string, mixed>  $data
     */
    public function recordMovement(array $data): WarehouseMovement;

    /**
     * Get movements log.
     *
     * @return Collection<int, WarehouseMovement>
     */
    public function getMovements(int $limit = 50): Collection;

    /**
     * Get stats.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array;
}
