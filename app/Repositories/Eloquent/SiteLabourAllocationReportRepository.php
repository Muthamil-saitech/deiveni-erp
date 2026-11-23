<?php

namespace App\Repositories\Eloquent;

use App\Models\DailyAllocation;
use App\Models\WeeklyWage;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\SiteLabourAllocationReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SiteLabourAllocationReportRepository implements SiteLabourAllocationReportRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Get daily allocations with relations filtered by optional site and date range.
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getAllocations(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->scopeByCreator(DailyAllocation::query())
            ->with(['projectSite', 'items.workerCategory', 'expenses', 'creator']);

        if ($siteId !== null && $siteId > 0) {
            $query->where('project_site_id', $siteId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $query->where('date', '<=', $endDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get weekly wages with relations filtered by optional site and date range.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getWeeklyWages(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = $this->scopeByCreator(WeeklyWage::query())
            ->with(['projectSite', 'expenses']);

        if ($siteId !== null && $siteId > 0) {
            $query->where('project_site_id', $siteId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('week_start_date', [$startDate, $endDate])
                    ->orWhereBetween('week_end_date', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->where('week_start_date', '<=', $startDate)
                            ->where('week_end_date', '>=', $endDate);
                    });
            });
        } elseif ($startDate !== null) {
            $query->where('week_end_date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $query->where('week_start_date', '<=', $endDate);
        }

        return $query->orderBy('week_start_date', 'desc')->get();
    }
}
