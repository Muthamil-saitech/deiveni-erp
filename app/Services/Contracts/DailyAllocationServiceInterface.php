<?php

namespace App\Services\Contracts;

use App\Models\DailyAllocation;
use Illuminate\Database\Eloquent\Collection;

interface DailyAllocationServiceInterface
{
    /**
     * Get daily allocation for a specific site and date.
     */
    public function getAllocation(int $siteId, string $date): ?DailyAllocation;

    /**
     * Get allocations for a site across an optional date range (history).
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getAllocationHistory(int $siteId, ?string $fromDate = null, ?string $toDate = null): Collection;

    /**
     * Get dates in a week range that have saved allocations for a site.
     *
     * @return array<string, bool>
     */
    public function getWeekSavedStatus(int $siteId, string $startDate, string $endDate): array;

    /**
     * Save daily allocation and automatically sync the weekly wages record.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveAllocation(array $data): DailyAllocation;

    /**
     * Delete a daily allocation record and re-sync weekly wage.
     */
    public function deleteAllocation(int $id): bool;
}
