<?php

namespace App\Services\Contracts;

interface StockTransferReportServiceInterface
{
    /**
     * Get aggregated and filtered report data.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array;
}
