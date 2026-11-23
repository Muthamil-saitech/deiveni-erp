<?php

namespace App\Services\Contracts;

use App\Models\SiteCash;
use Illuminate\Database\Eloquent\Collection;

interface SiteCashServiceInterface
{
    /**
     * Retrieve all site cash records.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SiteCash>
     */
    public function getAllTransactions(array $filters = []): Collection;

    /**
     * Find a site cash record by ID.
     */
    public function getTransactionById(int $id): ?SiteCash;

    /**
     * Create a new site cash transaction record and update the site's spent column.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransaction(array $data): SiteCash;

    /**
     * Update an existing site cash transaction record and update the site's spent column.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransaction(int $id, array $data): SiteCash;

    /**
     * Delete a site cash transaction record and update the site's spent column.
     */
    public function deleteTransaction(int $id): bool;

    /**
     * Get aggregate cash statistics.
     *
     * @return array<string, float>
     */
    public function getCashStats(?int $projectSiteId = null): array;

    /**
     * Recalculate and update the "spent" column in project_sites table for a site.
     */
    public function recalculateSiteSpent(int $projectSiteId): float;
}
