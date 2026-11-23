<?php

namespace App\Services\Contracts;

use App\Models\SiteMaterialStock;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface StockTransferServiceInterface
{
    /**
     * Get paginated or filtered list of stock transfers.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<StockTransfer>
     */
    public function getTransfers(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get single transfer by ID.
     */
    public function getTransferById(int $id): ?StockTransfer;

    /**
     * Record a new stock transfer from Site A to Site B.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data, int $userId): StockTransfer;

    /**
     * Record a return of stock from Site B back to Site A against a parent transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function createReturn(int $parentTransferId, array $data, int $userId): StockTransfer;

    /**
     * Update an existing stock transfer and adjust site stocks and PO accordingly.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(int $id, array $data, int $userId): StockTransfer;

    /**
     * Delete a stock transfer.
     */
    public function deleteTransfer(int $id): bool;

    /**
     * Get available materials and their stocks/sources (PO vs Warehouse) for a specific site.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableMaterialsForSite(int $siteId): array;

    /**
     * Get current stock balance for a material at a specific site.
     */
    public function getSiteMaterialStock(int $siteId, int $materialId): float;

    /**
     * Get all material stock balances for a site.
     *
     * @return Collection<int, SiteMaterialStock>
     */
    public function getSiteStocks(int $siteId): Collection;

    /**
     * Generate sequential next transfer number.
     */
    public function getNextTransferNumber(): string;

    /**
     * Get high-level KPI stats for dashboard / list page.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStats(array $filters = []): array;
}
