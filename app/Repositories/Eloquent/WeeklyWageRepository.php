<?php

namespace App\Repositories\Eloquent;

use App\Models\DailyAllocation;
use App\Models\WeeklyWage;
use App\Models\WeeklyWageExpense;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\WeeklyWageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WeeklyWageRepository implements WeeklyWageRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Find a weekly wage record by site, start date and end date with expenses.
     */
    public function findBySiteAndWeek(int $siteId, string $startDate, string $endDate): ?WeeklyWage
    {
        return $this->scopeByCreator(WeeklyWage::query())
            ->where('project_site_id', $siteId)
            ->whereDate('week_start_date', $startDate)
            ->whereDate('week_end_date', $endDate)
            ->with(['expenses', 'projectSite'])
            ->first();
    }

    /**
     * Sync and update or create weekly wage from daily allocations.
     */
    public function syncFromDailyAllocations(int $siteId, string $startDate, string $endDate): WeeklyWage
    {
        return DB::transaction(function () use ($siteId, $startDate, $endDate) {
            $dailyStats = $this->scopeByCreator(DailyAllocation::query())
                ->where('project_site_id', $siteId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('
                    COALESCE(SUM(total_labour_count), 0) as man_days,
                    COALESCE(SUM(total_labour_wage), 0) as labour_wages,
                    COALESCE(SUM(total_expenses), 0) as daily_expenses
                ')
                ->first();

            $totalManDays = (int) ($dailyStats->man_days ?? 0);
            $totalLabourWages = (float) ($dailyStats->labour_wages ?? 0);
            $totalDailyExpenses = (float) ($dailyStats->daily_expenses ?? 0);

            $weeklyWage = WeeklyWage::firstOrCreate(
                [
                    'project_site_id' => $siteId,
                    'week_start_date' => $startDate,
                    'week_end_date' => $endDate,
                ],
                [
                    'total_weekly_expenses' => 0.00,
                    'created_by' => Auth::id(),
                ]
            );

            // Calculate current weekly expenses
            $totalWeeklyExpenses = (float) $weeklyWage->expenses()->sum('amount');
            $netPayout = $totalLabourWages + $totalDailyExpenses + $totalWeeklyExpenses;

            $weeklyWage->update([
                'total_labour_man_days' => $totalManDays,
                'total_labour_wages' => $totalLabourWages,
                'total_daily_expenses' => $totalDailyExpenses,
                'total_weekly_expenses' => $totalWeeklyExpenses,
                'net_payout' => $netPayout,
            ]);

            return $weeklyWage->fresh(['expenses', 'projectSite']);
        });
    }

    /**
     * Save additional weekly site expenses.
     *
     * @param  array<int, array<string, mixed>>  $expenses
     */
    public function saveWeeklyExpenses(int $siteId, string $startDate, string $endDate, array $expenses): WeeklyWage
    {
        return DB::transaction(function () use ($siteId, $startDate, $endDate, $expenses) {
            // First ensure weekly wage is synced
            $weeklyWage = $this->syncFromDailyAllocations($siteId, $startDate, $endDate);

            // Re-sync expenses
            $weeklyWage->expenses()->delete();
            $totalWeeklyExp = 0.00;

            foreach ($expenses as $exp) {
                $itemName = trim($exp['item_name'] ?? ($exp['title'] ?? ''));
                $amount = (float) ($exp['amount'] ?? ($exp['cost'] ?? 0));
                if ($itemName !== '' || $amount > 0) {
                    WeeklyWageExpense::create([
                        'weekly_wage_id' => $weeklyWage->id,
                        'item_name' => $itemName,
                        'quantity' => (float) ($exp['quantity'] ?? ($exp['qty'] ?? 1)),
                        'unit_price' => (float) ($exp['unit_price'] ?? ($exp['unitPrice'] ?? $amount)),
                        'amount' => $amount,
                    ]);
                    $totalWeeklyExp += $amount;
                }
            }

            $netPayout = (float) $weeklyWage->total_labour_wages + (float) $weeklyWage->total_daily_expenses + $totalWeeklyExp;

            $weeklyWage->update([
                'total_weekly_expenses' => $totalWeeklyExp,
                'net_payout' => $netPayout,
            ]);

            return $weeklyWage->fresh(['expenses', 'projectSite']);
        });
    }

    /**
     * Approve and settle a weekly wage.
     */
    public function settle(int $siteId, string $startDate, string $endDate): WeeklyWage
    {
        return DB::transaction(function () use ($siteId, $startDate, $endDate) {
            $weeklyWage = $this->syncFromDailyAllocations($siteId, $startDate, $endDate);

            return $weeklyWage->fresh(['expenses', 'projectSite']);
        });
    }

    /**
     * Get all weekly wages for a site.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getBySite(int $siteId): Collection
    {
        return $this->scopeByCreator(WeeklyWage::query())
            ->where('project_site_id', $siteId)
            ->with(['expenses'])
            ->orderBy('week_start_date', 'desc')
            ->get();
    }
}
