<?php

namespace App\Repositories\Contracts;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Collection;

interface SitePurchasesInwardReportRepositoryInterface
{
    /**
     * Get purchase orders with relations filtered by optional criteria.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function getPurchases(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $status = null, ?string $search = null): Collection;

    /**
     * Get aggregate purchase statistics (Total POs, Total Value, Delivered, Pending).
     *
     * @return array<string, float|int>
     */
    public function getStats(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $status = null, ?string $search = null): array;
}
