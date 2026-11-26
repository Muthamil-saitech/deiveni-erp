<?php

namespace App\Repositories\Contracts;

use App\Models\WeeklyWage;
use Illuminate\Database\Eloquent\Collection;

interface WeeklyWageRepositoryInterface
{
    /**
     * Find a weekly wage record by site, start date and end date with expenses.
     */
    public function findBySiteAndWeek(int $siteId, string $startDate, string $endDate): ?WeeklyWage;

    /**
     * Sync and update or create weekly wage from daily allocations.
     */
    public function syncFromDailyAllocations(int $siteId, string $startDate, string $endDate): WeeklyWage;

    /**
     * Save additional weekly site expenses.
     *
     * @param  array<int, array<string, mixed>>  $expenses
     */
    public function saveWeeklyExpenses(int $siteId, string $startDate, string $endDate, array $expenses): WeeklyWage;

    /**
     * Approve and settle a weekly wage.
     */
    public function settle(int $siteId, string $startDate, string $endDate): WeeklyWage;

    /**
     * Get all weekly wages for a site.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getBySite(int $siteId): Collection;

    /**
     * Re-sync the Weekly Settlement Site Cash entry if a settled week covers the given date.
     */
    public function resyncSettledWeekForDate(int $siteId, string $date): void;
}
