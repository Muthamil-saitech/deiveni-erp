<?php

namespace App\Services\Contracts;

use App\Models\User;

interface DashboardServiceInterface
{
    /**
     * Get aggregated dashboard data for the authenticated user based on role.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(?User $user = null): array;

    /**
     * Get dynamic site manager dashboard metrics, chart datasets, and tables.
     *
     * @return array<string, mixed>
     */
    public function getSiteManagerDashboardData(): array;

    /**
     * Get dynamic account manager dashboard metrics, master summaries, and recent ledgers.
     *
     * @return array<string, mixed>
     */
    public function getAccountManagerDashboardData(): array;

    /**
     * Get 7-day income, expense and net cashflow data from the Ledger table.
     *
     * @return array<string, mixed>
     */
    public function getLedger7DayChartData(): array;
}
