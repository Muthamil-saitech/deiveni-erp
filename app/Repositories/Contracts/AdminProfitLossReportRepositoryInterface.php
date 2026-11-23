<?php

namespace App\Repositories\Contracts;

use App\Models\ProjectSite;
use Illuminate\Database\Eloquent\Collection;

interface AdminProfitLossReportRepositoryInterface
{
    /**
     * Get all active and existing project sites with client relation.
     *
     * @return Collection<int, ProjectSite>
     */
    public function getProjectSites(): Collection;

    /**
     * Get site-wise and aggregate financial dataset for profit and loss analysis.
     *
     * @return array<string, mixed>
     */
    public function getReportData(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $search = null): array;
}
