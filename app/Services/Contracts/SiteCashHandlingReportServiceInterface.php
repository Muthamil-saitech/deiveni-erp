<?php

namespace App\Services\Contracts;

interface SiteCashHandlingReportServiceInterface
{
    /**
     * Get report data containing transactions, KPIs, and filter metadata.
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
