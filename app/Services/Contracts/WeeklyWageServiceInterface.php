<?php

namespace App\Services\Contracts;

use App\Models\WeeklyWage;
use Illuminate\Database\Eloquent\Collection;

interface WeeklyWageServiceInterface
{
    /**
     * Get weekly sheet details including the 7-day daily allocations breakdown,
     * weekly wage record, additional expenses, and totals.
     *
     * @return array<string, mixed>
     */
    public function getWeeklySheet(int $siteId, string $startDate, string $endDate): array;

    /**
     * Automatically sync or create weekly wage for a given site and date.
     */
    public function syncWeekForDate(int $siteId, string $date): WeeklyWage;

    /**
     * Save additional weekly site expenses.
     *
     * @param  array<int, array<string, mixed>>  $expenses
     */
    public function saveWeeklyExpenses(int $siteId, string $startDate, string $endDate, array $expenses): WeeklyWage;

    /**
     * Approve and settle weekly wage.
     */
    public function settleWeek(int $siteId, string $startDate, string $endDate): WeeklyWage;

    /**
     * Get all weekly wages for a site.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getWagesBySite(int $siteId): Collection;
}
