<?php

namespace App\Services;

use App\Models\WeeklyWage;
use App\Models\WorkerCategory;
use App\Repositories\Contracts\DailyAllocationRepositoryInterface;
use App\Repositories\Contracts\WeeklyWageRepositoryInterface;
use App\Services\Contracts\WeeklyWageServiceInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;

class WeeklyWageService implements WeeklyWageServiceInterface
{
    public function __construct(
        public WeeklyWageRepositoryInterface $weeklyWageRepo,
        public DailyAllocationRepositoryInterface $dailyAllocationRepo
    ) {}

    /**
     * Get weekly sheet details including the 7-day daily allocations breakdown,
     * weekly wage record, additional expenses, and totals.
     *
     * @return array<string, mixed>
     */
    public function getWeeklySheet(int $siteId, string $startDate, string $endDate): array
    {
        // First sync to guarantee latest daily allocations roll into weekly wage record
        $weeklyWage = $this->weeklyWageRepo->syncFromDailyAllocations($siteId, $startDate, $endDate);

        // Fetch daily allocations for that week
        $dailyAllocations = $this->dailyAllocationRepo->getBySiteAndDateRange($siteId, $startDate, $endDate)
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        // Build 7-day period (Monday to Sunday)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $period = CarbonPeriod::create($start, $end);

        $days = [];
        $todayStr = Carbon::today()->toDateString();

        foreach ($period as $dt) {
            $dateStr = $dt->toDateString();
            $dayOfWeek = $dt->format('D');
            $dateFormatted = $dt->format('d M, Y');
            $isSunday = $dt->isSunday();
            $isToday = ($dateStr === $todayStr);

            $allocation = $dailyAllocations->get($dateStr);

            if ($allocation) {
                $workers = $allocation->items->map(function ($item) {
                    $code = $item->category_code
                        ?: ($item->worker_category_id
                            ? 'CAT-'.$item->worker_category_id
                            : 'CAT-'.md5(trim(($item->name_ta ?? '').' '.($item->name_en ?? ''))));

                    return [
                        'categoryId' => $item->worker_category_id,
                        'code' => $code,
                        'cat' => $code,
                        'nameTa' => $item->name_ta,
                        'nameEn' => $item->name_en,
                        'count' => (int) $item->worker_count,
                        'rate' => (float) $item->daily_wage,
                        'masterRate' => (float) $item->master_wage,
                        'subtotal' => (float) $item->subtotal,
                        'paid_by' => $item->paid_by,
                        'is_am_paid' => ($item->paid_by === 'Account Manager'),
                    ];
                })->values()->toArray();

                $expenses = $allocation->expenses->map(function ($exp) {
                    return [
                        'title' => $exp->item_name,
                        'qty' => (float) $exp->quantity,
                        'unitPrice' => (float) $exp->unit_price,
                        'amount' => (float) $exp->amount,
                    ];
                })->values()->toArray();

                $days[] = [
                    'date' => $dateStr,
                    'day_of_week' => $dayOfWeek,
                    'date_formatted' => $dateFormatted,
                    'has_data' => true,
                    'is_sunday' => $isSunday,
                    'is_today' => $isToday,
                    'total_labour_count' => (int) $allocation->total_labour_count,
                    'total_labour_wage' => (float) $allocation->total_labour_wage,
                    'total_expenses' => (float) $allocation->total_expenses,
                    'grand_total' => (float) $allocation->grand_total,
                    'trade_summary' => $allocation->trade_summary,
                    'workers' => $workers,
                    'expenses' => $expenses,
                ];
            } else {
                $days[] = [
                    'date' => $dateStr,
                    'day_of_week' => $dayOfWeek,
                    'date_formatted' => $dateFormatted,
                    'has_data' => false,
                    'is_sunday' => $isSunday,
                    'is_today' => $isToday,
                    'total_labour_count' => 0,
                    'total_labour_wage' => 0.00,
                    'total_expenses' => 0.00,
                    'grand_total' => 0.00,
                    'trade_summary' => $isSunday ? 'Site holiday / No labour' : 'Not allocated',
                    'workers' => [],
                    'expenses' => [],
                ];
            }
        }

        // Group and summarize by worker category across the week
        $categoryTotals = [];
        foreach ($dailyAllocations as $allocation) {
            foreach ($allocation->items as $item) {
                if ($item->worker_count <= 0) {
                    continue;
                }
                $code = $item->category_code
                    ?: ($item->worker_category_id
                        ? 'CAT-'.$item->worker_category_id
                        : 'CAT-'.md5(trim(($item->name_ta ?? '').' '.($item->name_en ?? ''))));

                if (! isset($categoryTotals[$code])) {
                    $categoryTotals[$code] = [
                        'code' => $code,
                        'name_ta' => $item->name_ta,
                        'name_en' => $item->name_en,
                        'rate' => (float) $item->daily_wage,
                        'total_workers' => 0,
                        'total_amount' => 0.0,
                        'am_paid_amount' => 0.0,
                        'am_office_amount' => 0.0,
                        'am_project_amount' => 0.0,
                        'am_paid_workers' => 0,
                        'is_am_paid' => false,
                    ];
                }
                $categoryTotals[$code]['total_workers'] += (int) $item->worker_count;
                $categoryTotals[$code]['total_amount'] += (float) $item->subtotal;
                if ($item->paid_by === 'Account Manager') {
                    $categoryTotals[$code]['am_paid_amount'] += (float) $item->subtotal;
                    $categoryTotals[$code]['am_paid_workers'] += (int) $item->worker_count;
                    $categoryTotals[$code]['is_am_paid'] = true;
                    if ($item->wage_cash_source === 'project_cash') {
                        $categoryTotals[$code]['am_project_amount'] += (float) $item->subtotal;
                    } else {
                        $categoryTotals[$code]['am_office_amount'] += (float) $item->subtotal;
                    }
                }
                if ($categoryTotals[$code]['total_workers'] > 0) {
                    $categoryTotals[$code]['rate'] = round(
                        $categoryTotals[$code]['total_amount'] / $categoryTotals[$code]['total_workers'],
                        2
                    );
                }
            }
        }

        // Calculate total wages paid directly by Account Manager (split by Office Cash vs Project Cash)
        $totalAmWages = 0.0;
        $totalAmOfficeWages = 0.0;
        $totalAmProjectWages = 0.0;
        foreach ($dailyAllocations as $allocation) {
            foreach ($allocation->items as $item) {
                if ($item->paid_by === 'Account Manager') {
                    $sub = (float) $item->subtotal;
                    $totalAmWages += $sub;
                    if ($item->wage_cash_source === 'project_cash') {
                        $totalAmProjectWages += $sub;
                    } else {
                        $totalAmOfficeWages += $sub;
                    }
                }
            }
        }

        // Fallback: If no allocations recorded yet, display default active categories for table layout
        if (empty($categoryTotals)) {
            $defaultCategories = WorkerCategory::query()
                ->where('status', 'Active')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($defaultCategories as $cat) {
                $categoryTotals[$cat->code] = [
                    'code' => $cat->code,
                    'name_ta' => $cat->name_ta,
                    'name_en' => $cat->name_en,
                    'rate' => (float) $cat->daily_wage,
                    'total_workers' => 0,
                    'total_amount' => 0.0,
                    'am_paid_amount' => 0.0,
                    'am_paid_workers' => 0,
                    'is_am_paid' => false,
                ];
            }
        }

        $additionalExpenses = $weeklyWage->expenses->map(function ($exp) {
            return [
                'id' => $exp->id,
                'title' => $exp->item_name,
                'qty' => (float) $exp->quantity,
                'unitPrice' => (float) $exp->unit_price,
                'cost' => (float) $exp->amount,
            ];
        })->values()->toArray();

        // Extract and aggregate all same-day site expenses recorded in daily allocations for the week
        $dailyExpenseItems = [];
        foreach ($dailyAllocations as $allocation) {
            $allocDate = Carbon::parse($allocation->date);
            $dateFormatted = $allocDate->format('d M, Y (D)');
            foreach ($allocation->expenses as $exp) {
                $dailyExpenseItems[] = [
                    'id' => $exp->id,
                    'date' => $allocDate->toDateString(),
                    'date_formatted' => $dateFormatted,
                    'title' => $exp->item_name,
                    'qty' => (float) $exp->quantity,
                    'unitPrice' => (float) $exp->unit_price,
                    'cost' => (float) $exp->amount,
                    'is_daily_sync' => true,
                ];
            }
        }

        $grossLabourWages = (float) $weeklyWage->total_labour_wages;
        $netSiteWages = max(0.0, $grossLabourWages - $totalAmWages);
        $totalDailyExpenses = (float) $weeklyWage->total_daily_expenses;
        $totalWeeklyExpenses = (float) $weeklyWage->total_weekly_expenses;
        $netSitePayout = $netSiteWages + $totalDailyExpenses + $totalWeeklyExpenses;
        $grossTotal = $grossLabourWages + $totalDailyExpenses + $totalWeeklyExpenses;

        return [
            'weekly_wage' => $weeklyWage,
            'days' => $days,
            'categories' => array_values($categoryTotals),
            'daily_expenses' => $dailyExpenseItems,
            'additional_expenses' => $additionalExpenses,
            'summary' => [
                'total_man_days' => (int) $weeklyWage->total_labour_man_days,
                'total_labour_wages' => $grossLabourWages,
                'total_am_wages' => $totalAmWages,
                'total_am_office_wages' => $totalAmOfficeWages,
                'total_am_project_wages' => $totalAmProjectWages,
                'net_site_wages' => $netSiteWages,
                'total_daily_expenses' => $totalDailyExpenses,
                'total_weekly_expenses' => $totalWeeklyExpenses,
                'net_payout' => $netSitePayout,
                'gross_total' => $grossTotal,
            ],
        ];
    }

    /**
     * Automatically sync or create weekly wage for a given site and date.
     */
    public function syncWeekForDate(int $siteId, string $date): WeeklyWage
    {
        $dt = Carbon::parse($date);
        $startDate = $dt->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $endDate = $dt->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        return $this->weeklyWageRepo->syncFromDailyAllocations($siteId, $startDate, $endDate);
    }

    /**
     * Save additional weekly site expenses.
     *
     * @param  array<int, array<string, mixed>>  $expenses
     */
    public function saveWeeklyExpenses(int $siteId, string $startDate, string $endDate, array $expenses): WeeklyWage
    {
        return $this->weeklyWageRepo->saveWeeklyExpenses($siteId, $startDate, $endDate, $expenses);
    }

    /**
     * Approve and settle weekly wage.
     */
    public function settleWeek(int $siteId, string $startDate, string $endDate): WeeklyWage
    {
        return $this->weeklyWageRepo->settle($siteId, $startDate, $endDate);
    }

    /**
     * Get all weekly wages for a site.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getWagesBySite(int $siteId): Collection
    {
        return $this->weeklyWageRepo->getBySite($siteId);
    }
}
