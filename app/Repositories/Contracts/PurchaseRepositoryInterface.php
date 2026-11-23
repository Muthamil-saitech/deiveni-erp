<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseRepositoryInterface
{
    /**
     * Get all purchase orders with optional filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PurchaseOrder>
     */
    public function all(array $filters = []): Collection;

    /**
     * Find a purchase order by ID.
     */
    public function findById(int $id): ?PurchaseOrder;

    /**
     * Find a purchase order by PO or Bill number.
     */
    public function findByPoNumber(string $poNumber): ?PurchaseOrder;

    /**
     * Create a new purchase order with items.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder;

    /**
     * Update an existing purchase order with items.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PurchaseOrder $order, array $data): PurchaseOrder;

    /**
     * Delete a purchase order.
     */
    public function delete(PurchaseOrder $order): bool;

    /**
     * Compute KPI statistics for purchases.
     *
     * @return array<string, mixed>
     */
    public function getStats(?int $projectSiteId = null): array;
}
