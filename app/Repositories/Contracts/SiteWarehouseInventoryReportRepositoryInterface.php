<?php

namespace App\Repositories\Contracts;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use Illuminate\Database\Eloquent\Collection;

interface SiteWarehouseInventoryReportRepositoryInterface
{
    /**
     * Get warehouse items filtered by category, stock status, and search query.
     *
     * @return Collection<int, WarehouseItem>
     */
    public function getItems(?string $category = null, ?string $stockStatus = null, ?string $search = null): Collection;

    /**
     * Get warehouse movements with item, project site, and creator relations.
     *
     * @return Collection<int, WarehouseMovement>
     */
    public function getMovements(?string $startDate = null, ?string $endDate = null, ?string $category = null, ?string $movementType = null, ?string $search = null): Collection;

    /**
     * Get distinct item categories.
     *
     * @return array<int, string>
     */
    public function getCategories(): array;

    /**
     * Get aggregated inventory and movement statistics.
     *
     * @return array<string, float|int>
     */
    public function getStats(?string $category = null, ?string $stockStatus = null, ?string $search = null, ?string $startDate = null, ?string $endDate = null): array;
}
