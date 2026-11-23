<?php

namespace App\Services\Contracts;

interface SiteLabourAllocationReportServiceInterface
{
    /**
     * Get report data containing daily allocations, weekly wages, and summary KPIs.
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
