<?php

namespace App\Services\Contracts;

use App\Models\DailyAllocationExpense;
use Illuminate\Database\Eloquent\Collection;

interface DailyExpensesReportServiceInterface
{
    /**
     * Get filter metadata with predefined date periods and labels.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array;

    /**
     * Get paginated report data with KPIs and active filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array;

    /**
     * Get all filtered expense records for Excel/CSV export.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DailyAllocationExpense>
     */
    public function getExportData(array $filters = []): Collection;
}
