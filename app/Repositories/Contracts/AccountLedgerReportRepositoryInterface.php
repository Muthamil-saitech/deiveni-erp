<?php

namespace App\Repositories\Contracts;

use App\Models\Ledger;
use Illuminate\Database\Eloquent\Collection;

interface AccountLedgerReportRepositoryInterface
{
    /**
     * Get ledger records with relations filtered by optional criteria.
     *
     * @return Collection<int, Ledger>
     */
    public function getLedgers(
        ?int $siteId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $flowType = null,
        ?string $cashFlow = null,
        ?string $search = null
    ): Collection;

    /**
     * Get aggregate financial metrics (Total Income, Total Expense, Net Balance, Count).
     *
     * @return array{total_income: float, total_expense: float, net_balance: float, count: int}
     */
    public function getStats(
        ?int $siteId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $flowType = null,
        ?string $cashFlow = null,
        ?string $search = null
    ): array;
}
