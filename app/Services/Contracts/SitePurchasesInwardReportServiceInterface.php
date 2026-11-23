<?php

namespace App\Services\Contracts;

interface SitePurchasesInwardReportServiceInterface
{
    /**
     * Get report data containing purchases, KPIs, and filter metadata.
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
