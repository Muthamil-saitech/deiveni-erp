<?php

namespace App\Repositories\Contracts;

use App\Models\DailyAllocation;
use App\Models\WeeklyWage;
use Illuminate\Database\Eloquent\Collection;

interface SiteLabourAllocationReportRepositoryInterface
{
    /**
     * Get daily allocations with relations filtered by optional site and date range.
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getAllocations(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Get weekly wages with relations filtered by optional site and date range.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getWeeklyWages(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): Collection;
}
