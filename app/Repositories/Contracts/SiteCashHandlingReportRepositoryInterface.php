<?php

namespace App\Repositories\Contracts;

use App\Models\SiteCash;
use Illuminate\Database\Eloquent\Collection;

interface SiteCashHandlingReportRepositoryInterface
{
    /**
     * Get site cash transactions with relations filtered by optional criteria.
     *
     * @return Collection<int, SiteCash>
     */
    public function getTransactions(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $flowType = null, ?string $search = null): Collection;

    /**
     * Get aggregate cash statistics (Advances, Collections, Purchases, Wages, Returned, Net).
     *
     * @return array<string, float|int>
     */
    public function getStats(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): array;
}
