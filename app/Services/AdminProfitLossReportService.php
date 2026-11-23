<?php

namespace App\Services;

use App\Repositories\Contracts\AdminProfitLossReportRepositoryInterface;
use App\Services\Contracts\AdminProfitLossReportServiceInterface;
use Carbon\Carbon;

class AdminProfitLossReportService implements AdminProfitLossReportServiceInterface
{
    public function __construct(
        public AdminProfitLossReportRepositoryInterface $reportRepo
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
     * Get report data containing sites analysis, KPIs, financial statement, and filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $meta = $this->getFilterMeta();
        $period = $filters['period'] ?? 'this_week';
        $siteId = ! empty($filters['site_id']) && $filters['site_id'] !== 'all' ? (int) $filters['site_id'] : null;
        $search = ! empty($filters['search']) ? trim((string) $filters['search']) : null;

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
                // No date boundary
                break;
        }

        $report = $this->reportRepo->getReportData($siteId, $startDate, $endDate, $search);

        return array_merge($report, [
            'filter_meta' => $meta,
            'active_filters' => [
                'period' => $period,
                'site_id' => $siteId,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
