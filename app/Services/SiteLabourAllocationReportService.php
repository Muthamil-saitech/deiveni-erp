<?php

namespace App\Services;

use App\Repositories\Contracts\SiteLabourAllocationReportRepositoryInterface;
use App\Services\Contracts\SiteLabourAllocationReportServiceInterface;
use Carbon\Carbon;

class SiteLabourAllocationReportService implements SiteLabourAllocationReportServiceInterface
{
    public function __construct(
        public SiteLabourAllocationReportRepositoryInterface $reportRepo
    ) {}

    /**
     * Get computed filter date ranges and metadata.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array
    {
        $today = Carbon::today();
        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();

        return [
            'today' => [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
                'label' => $today->format('d M Y'),
            ],
            'this_week' => [
                'start' => $thisWeekStart->toDateString(),
                'end' => $thisWeekEnd->toDateString(),
                'label' => $thisWeekStart->format('d M').' - '.$thisWeekEnd->format('d M Y'),
            ],
            'last_week' => [
                'start' => $lastWeekStart->toDateString(),
                'end' => $lastWeekEnd->toDateString(),
                'label' => $lastWeekStart->format('d M').' - '.$lastWeekEnd->format('d M Y'),
            ],
            'this_month' => [
                'start' => $thisMonthStart->toDateString(),
                'end' => $thisMonthEnd->toDateString(),
                'label' => Carbon::now()->format('F Y'),
            ],
            'current_date' => $today->format('d M Y'),
        ];
    }

    /**
     * Get report data containing daily allocations, weekly wages, and summary KPIs.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $meta = $this->getFilterMeta();
        $period = $filters['period'] ?? 'this_week';
        $siteId = ! empty($filters['site_id']) && $filters['site_id'] !== 'all' ? (int) $filters['site_id'] : null;

        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = $meta['today']['start'];
                $endDate = $meta['today']['end'];
                break;
            case 'this_week':
                $startDate = $meta['this_week']['start'];
                $endDate = $meta['this_week']['end'];
                break;
            case 'last_week':
                $startDate = $meta['last_week']['start'];
                $endDate = $meta['last_week']['end'];
                break;
            case 'this_month':
                $startDate = $meta['this_month']['start'];
                $endDate = $meta['this_month']['end'];
                break;
            case 'custom':
                $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateString() : null;
                $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->toDateString() : null;
                break;
            case 'all':
            default:
                // No date restriction
                break;
        }

        $allocationModels = $this->reportRepo->getAllocations($siteId, $startDate, $endDate);
        $weeklyWageModels = $this->reportRepo->getWeeklyWages($siteId, $startDate, $endDate);

        $allocations = [];
        $totalManDays = 0;
        $totalLabourWage = 0.00;
        $totalDailyExpenses = 0.00;

        foreach ($allocationModels as $alloc) {
            $wCount = (int) $alloc->total_labour_count;
            $wWage = (float) $alloc->total_labour_wage;
            $dExp = (float) $alloc->total_expenses;
            $gTotal = (float) $alloc->grand_total;

            $totalManDays += $wCount;
            $totalLabourWage += $wWage;
            $totalDailyExpenses += $dExp;

            $workerItems = [];
            foreach ($alloc->items as $item) {
                $workerItems[] = [
                    'category_code' => $item->category_code,
                    'name_ta' => $item->name_ta,
                    'name_en' => $item->name_en ?? $item->name_ta,
                    'count' => (int) $item->worker_count,
                    'rate' => (float) $item->daily_wage,
                    'master_rate' => (float) $item->master_wage,
                    'subtotal' => (float) $item->subtotal,
                ];
            }

            $expenseItems = [];
            foreach ($alloc->expenses as $exp) {
                $expenseItems[] = [
                    'title' => $exp->item_name,
                    'qty' => (float) $exp->quantity,
                    'unit_price' => (float) $exp->unit_price,
                    'amount' => (float) $exp->amount,
                ];
            }

            $allocations[] = [
                'id' => $alloc->id,
                'project_site_id' => $alloc->project_site_id,
                'site_name' => $alloc->projectSite?->name ?? 'Unknown Site',
                'site_location' => $alloc->projectSite?->location ?? '',
                'date' => Carbon::parse($alloc->date)->format('d M Y'),
                'raw_date' => Carbon::parse($alloc->date)->toDateString(),
                'trade_summary' => $alloc->trade_summary ?? '',
                'total_labour_count' => $wCount,
                'total_labour_wage' => $wWage,
                'total_expenses' => $dExp,
                'grand_total' => $gTotal,
                'workers' => $workerItems,
                'expenses' => $expenseItems,
            ];
        }

        $weeklyWages = [];
        $totalWeeklyExpenses = 0.00;

        foreach ($weeklyWageModels as $ww) {
            $wwExp = (float) $ww->total_weekly_expenses;
            $totalWeeklyExpenses += $wwExp;

            $weeklyExpenseItems = [];
            foreach ($ww->expenses as $item) {
                $weeklyExpenseItems[] = [
                    'title' => $item->item_name,
                    'qty' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'amount' => (float) $item->amount,
                ];
            }

            $weeklyWages[] = [
                'id' => $ww->id,
                'project_site_id' => $ww->project_site_id,
                'site_name' => $ww->projectSite?->name ?? 'Unknown Site',
                'week_start_date' => Carbon::parse($ww->week_start_date)->format('d M Y'),
                'week_end_date' => Carbon::parse($ww->week_end_date)->format('d M Y'),
                'raw_start_date' => Carbon::parse($ww->week_start_date)->toDateString(),
                'raw_end_date' => Carbon::parse($ww->week_end_date)->toDateString(),
                'week_label' => Carbon::parse($ww->week_start_date)->format('d M').' - '.Carbon::parse($ww->week_end_date)->format('d M Y'),
                'total_labour_man_days' => (int) $ww->total_labour_man_days,
                'total_labour_wages' => (float) $ww->total_labour_wages,
                'total_daily_expenses' => (float) $ww->total_daily_expenses,
                'total_weekly_expenses' => $wwExp,
                'net_payout' => (float) $ww->net_payout,
                'expenses' => $weeklyExpenseItems,
            ];
        }

        $grandTotalCost = $totalLabourWage + $totalDailyExpenses + $totalWeeklyExpenses;

        $kpis = [
            'total_man_days' => $totalManDays,
            'total_labour_wage' => $totalLabourWage,
            'total_daily_expenses' => $totalDailyExpenses,
            'total_weekly_expenses' => $totalWeeklyExpenses,
            'total_expenses' => $totalDailyExpenses + $totalWeeklyExpenses,
            'grand_total' => $grandTotalCost,
            'allocations_count' => count($allocations),
            'weekly_wages_count' => count($weeklyWages),
        ];

        return [
            'allocations' => $allocations,
            'weekly_wages' => $weeklyWages,
            'kpis' => $kpis,
            'filter_meta' => $meta,
            'active_filters' => [
                'period' => $period,
                'site_id' => $siteId ?? 'all',
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }
}
