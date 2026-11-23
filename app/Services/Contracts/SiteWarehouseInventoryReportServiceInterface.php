<?php

namespace App\Services\Contracts;

interface SiteWarehouseInventoryReportServiceInterface
{
    /**
     * Get warehouse inventory report data including items, movements, KPIs, and filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array;

    /**
     * Get computed filter date ranges and metadata.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array;
}
