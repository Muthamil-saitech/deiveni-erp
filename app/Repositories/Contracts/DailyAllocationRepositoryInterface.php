<?php

namespace App\Repositories\Contracts;

use App\Models\DailyAllocation;
use Illuminate\Database\Eloquent\Collection;

interface DailyAllocationRepositoryInterface
{
    /**
     * Find a daily allocation by site and date with items and expenses.
     */
    public function findBySiteAndDate(int $siteId, string $date): ?DailyAllocation;

    /**
     * Get allocations for a site within an optional date range.
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getHistory(int $siteId, ?string $fromDate = null, ?string $toDate = null): Collection;

    /**
     * Get allocations for a specific week range (Monday to Sunday) for a site.
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getBySiteAndDateRange(int $siteId, string $startDate, string $endDate): Collection;

    /**
     * Create or update a daily allocation with its line items and expenses.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveAllocation(array $data): DailyAllocation;

    /**
     * Delete a daily allocation by ID.
     */
    public function delete(int $id): bool;
}
