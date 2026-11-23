<?php

namespace App\Services;

use App\Models\DailyAllocationExpense;
use App\Services\Contracts\DailyExpensesReportServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DailyExpensesReportService implements DailyExpensesReportServiceInterface
{
    /**
     * Get filter metadata with predefined date periods and labels.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();

        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        return [
            'today' => [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
                'label' => $today->format('d M Y'),
            ],
            'yesterday' => [
                'start' => $yesterday->toDateString(),
                'end' => $yesterday->toDateString(),
                'label' => $yesterday->format('d M Y'),
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
            'last_month' => [
                'start' => $lastMonthStart->toDateString(),
                'end' => $lastMonthEnd->toDateString(),
                'label' => Carbon::now()->subMonth()->format('F Y'),
            ],
            'current_date' => $today->format('d M Y'),
        ];
    }

    /**
     * Build the filtered Eloquent query for daily expenses.
     *
     * @param  array<string, mixed>  $filters
     * @return Builder<DailyAllocationExpense>
     */
    protected function buildBaseQuery(array $filters): Builder
    {
        $meta = $this->getFilterMeta();
        $period = $filters['period'] ?? 'all';
        $siteId = ! empty($filters['site_id']) && $filters['site_id'] !== 'all' ? (int) $filters['site_id'] : null;
        $search = ! empty($filters['search']) ? trim((string) $filters['search']) : null;
        $minAmount = isset($filters['min_amount']) && is_numeric($filters['min_amount']) ? (float) $filters['min_amount'] : null;
        $maxAmount = isset($filters['max_amount']) && is_numeric($filters['max_amount']) ? (float) $filters['max_amount'] : null;

        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = $meta['today']['start'];
                $endDate = $meta['today']['end'];
                break;
            case 'yesterday':
                $startDate = $meta['yesterday']['start'];
                $endDate = $meta['yesterday']['end'];
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
            case 'last_month':
                $startDate = $meta['last_month']['start'];
                $endDate = $meta['last_month']['end'];
                break;
            case 'custom':
                $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateString() : null;
                $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->toDateString() : null;
                break;
            case 'all':
            default:
                break;
        }

        $query = DailyAllocationExpense::query()
            ->select('daily_allocation_expenses.*')
            ->join('daily_allocations', 'daily_allocation_expenses.daily_allocation_id', '=', 'daily_allocations.id')
            ->with(['dailyAllocation.projectSite', 'dailyAllocation.creator']);

        if ($startDate && $endDate) {
            $query->whereBetween('daily_allocations.date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('daily_allocations.date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('daily_allocations.date', '<=', $endDate);
        }

        if ($siteId) {
            $query->where('daily_allocations.project_site_id', $siteId);
        }

        if ($search !== null && $search !== '') {
            $query->where('daily_allocation_expenses.item_name', 'like', "%{$search}%");
        }

        if ($minAmount !== null) {
            $query->where('daily_allocation_expenses.amount', '>=', $minAmount);
        }

        if ($maxAmount !== null) {
            $query->where('daily_allocation_expenses.amount', '<=', $maxAmount);
        }

        return $query;
    }

    /**
     * Get paginated report data with KPIs and active filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $meta = $this->getFilterMeta();
        $query = $this->buildBaseQuery($filters);

        // Compute KPIs from complete filtered dataset
        $kpiQuery = clone $query;
        $totalExpensesAmount = (float) $kpiQuery->sum('daily_allocation_expenses.amount');
        $totalEntriesCount = (int) (clone $query)->count();
        $distinctSitesCount = (int) (clone $query)->distinct('daily_allocations.project_site_id')->count('daily_allocations.project_site_id');
        $avgExpenseAmount = $totalEntriesCount > 0 ? ($totalExpensesAmount / $totalEntriesCount) : 0.00;

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'date_desc';
        switch ($sortBy) {
            case 'date_asc':
                $query->orderBy('daily_allocations.date', 'asc')->orderBy('daily_allocation_expenses.id', 'asc');
                break;
            case 'amount_desc':
                $query->orderBy('daily_allocation_expenses.amount', 'desc');
                break;
            case 'amount_asc':
                $query->orderBy('daily_allocation_expenses.amount', 'asc');
                break;
            case 'item_asc':
                $query->orderBy('daily_allocation_expenses.item_name', 'asc');
                break;
            case 'date_desc':
            default:
                $query->orderBy('daily_allocations.date', 'desc')->orderBy('daily_allocation_expenses.id', 'desc');
                break;
        }

        // Expenses dataset for interactive client-side table pagination
        $expenses = $query->get();

        return [
            'expenses' => $expenses,
            'kpis' => [
                'total_amount' => $totalExpensesAmount,
                'total_count' => $totalEntriesCount,
                'sites_count' => $distinctSitesCount,
                'avg_amount' => $avgExpenseAmount,
            ],
            'filter_meta' => $meta,
            'active_filters' => array_merge([
                'period' => 'all',
                'site_id' => 'all',
                'search' => '',
                'sort_by' => 'date_desc',
                'start_date' => '',
                'end_date' => '',
            ], $filters),
        ];
    }

    /**
     * Get all filtered expense records for Excel/CSV export.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DailyAllocationExpense>
     */
    public function getExportData(array $filters = []): Collection
    {
        return $this->buildBaseQuery($filters)
            ->orderBy('daily_allocations.date', 'desc')
            ->orderBy('daily_allocation_expenses.id', 'desc')
            ->get();
    }
}
