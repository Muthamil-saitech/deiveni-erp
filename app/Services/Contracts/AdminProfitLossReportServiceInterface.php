<?php

namespace App\Services\Contracts;

interface AdminProfitLossReportServiceInterface
{
    /**
     * Get computed filter date ranges and metadata.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array;

    /**
     * Get report data containing sites analysis, KPIs, financial statement, and filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array;
}
