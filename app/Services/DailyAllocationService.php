<?php

namespace App\Services;

use App\Models\DailyAllocation;
use App\Repositories\Contracts\DailyAllocationRepositoryInterface;
use App\Services\Contracts\DailyAllocationServiceInterface;
use App\Services\Contracts\WeeklyWageServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DailyAllocationService implements DailyAllocationServiceInterface
{
    public function __construct(
        public DailyAllocationRepositoryInterface $dailyAllocationRepo,
        public WeeklyWageServiceInterface $weeklyWageService
    ) {}

    /**
     * Get daily allocation for a specific site and date.
     */
    public function getAllocation(int $siteId, string $date): ?DailyAllocation
    {
        return $this->dailyAllocationRepo->findBySiteAndDate($siteId, $date);
    }

    /**
     * Get allocations for a site across an optional date range (history).
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getAllocationHistory(int $siteId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        return $this->dailyAllocationRepo->getHistory($siteId, $fromDate, $toDate);
    }

    /**
     * Get dates in a week range that have saved allocations for a site.
     *
     * @return array<string, bool>
     */
    public function getWeekSavedStatus(int $siteId, string $startDate, string $endDate): array
    {
        $allocations = $this->dailyAllocationRepo->getBySiteAndDateRange($siteId, $startDate, $endDate);
        $status = [];

        foreach ($allocations as $alloc) {
            $status[Carbon::parse($alloc->date)->toDateString()] = true;
        }

        return $status;
    }

    /**
     * Save daily allocation and automatically sync the weekly wages record.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveAllocation(array $data): DailyAllocation
    {
        $allocation = $this->dailyAllocationRepo->saveAllocation($data);

        // Task 5: Automatically create/sync one record on weekly wages also!
        $dateStr = Carbon::parse($allocation->date)->toDateString();
        $this->weeklyWageService->syncWeekForDate((int) $allocation->project_site_id, $dateStr);

        return $allocation;
    }

    /**
     * Delete a daily allocation record and re-sync weekly wage.
     */
    public function deleteAllocation(int $id): bool
    {
        $user = auth()->user();
        $query = DailyAllocation::query();
        if ($user && ! $user->isAdmin()) {
            $query->where('created_by', $user->id);
        }

        $allocation = $query->find($id);
        if (! $allocation) {
            return false;
        }

        $siteId = (int) $allocation->project_site_id;
        $dateStr = Carbon::parse($allocation->date)->toDateString();

        $deleted = $this->dailyAllocationRepo->delete($id);

        if ($deleted) {
            $this->weeklyWageService->syncWeekForDate($siteId, $dateStr);
        }

        return $deleted;
    }
}
