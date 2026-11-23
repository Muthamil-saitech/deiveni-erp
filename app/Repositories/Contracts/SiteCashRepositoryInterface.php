<?php

namespace App\Repositories\Contracts;

use App\Models\SiteCash;
use Illuminate\Database\Eloquent\Collection;

interface SiteCashRepositoryInterface
{
    /**
     * Retrieve all site cash records with relations ordered newest first.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SiteCash>
     */
    public function all(array $filters = []): Collection;

    /**
     * Find a site cash record by ID with relations.
     */
    public function findById(int $id): ?SiteCash;

    /**
     * Create a new site cash transaction record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SiteCash;

    /**
     * Update an existing site cash transaction record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SiteCash $siteCash, array $data): SiteCash;

    /**
     * Delete a site cash transaction record.
     */
    public function delete(SiteCash $siteCash): bool;

    /**
     * Get aggregate cash statistics (Advances, Collections, Purchases, Wages, Returned, Cash-in-Hand).
     *
     * @return array<string, float>
     */
    public function getStats(?int $projectSiteId = null): array;

    /**
     * Get total expense sum for a specific project site.
     */
    public function getExpensesSumForSite(int $projectSiteId): float;
}
