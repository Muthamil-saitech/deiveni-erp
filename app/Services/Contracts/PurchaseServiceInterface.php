<?php

namespace App\Services\Contracts;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

interface PurchaseServiceInterface
{
    /**
     * Get all purchases.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PurchaseOrder>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Find purchase by ID.
     */
    public function getById(int $id): ?PurchaseOrder;

    /**
     * Find purchase by PO number.
     */
    public function getByPoNumber(string $poNumber): ?PurchaseOrder;

    /**
     * Create a purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder;

    /**
     * Update a purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseOrder;

    /**
     * Update only the status of a purchase order.
     */
    public function updateStatus(int $id, string $status, ?string $reason = null): PurchaseOrder;

    /**
     * Delete a purchase order.
     */
    public function delete(int $id): bool;

    /**
     * Get KPI statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(?int $projectSiteId = null): array;

    /**
     * Generate the next PO or Bill number.
     */
    public function generateNextPoNumber(string $type = 'PO'): string;
}
