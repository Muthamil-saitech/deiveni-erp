<?php

namespace App\Services\Contracts;

use App\Models\MaterialUsage;
use Illuminate\Pagination\LengthAwarePaginator;

interface MaterialUsageServiceInterface
{
    /**
     * Get paginated list of material usages with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<MaterialUsage>
     */
    public function getUsages(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a single material usage by ID (with items).
     */
    public function getUsageById(int $id): ?MaterialUsage;

    /**
     * Create a new material usage record and reduce stock accordingly.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUsage(array $data, int $userId): MaterialUsage;

    /**
     * Update an existing material usage record and adjust stocks and PO accordingly.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUsage(int $id, array $data, int $userId): MaterialUsage;

    /**
     * Delete a material usage and reverse any stock reductions.
     */
    public function deleteUsage(int $id): bool;

    /**
     * Generate the next sequential usage number (MU-YYYY-NNNN).
     */
    public function getNextUsageNumber(): string;

    /**
     * Get PO items available for a given purchase order, deducting already-used quantities.
     *
     * @return array<string, mixed>
     */
    public function getPOItemsForUsage(int $purchaseOrderId): array;

    /**
     * Get materials available for usage for a given site, optionally filtered by PO.
     *
     * @return array<string, mixed>
     */
    public function getSiteMaterialsForUsage(int $siteId, ?int $poId = null): array;

    /**
     * Get active purchase orders (with their site) suitable for usage recording.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getActivePurchaseOrders(array $filters = []): array;

    /**
     * KPI stats for the list page header cards.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStats(array $filters = []): array;
}
