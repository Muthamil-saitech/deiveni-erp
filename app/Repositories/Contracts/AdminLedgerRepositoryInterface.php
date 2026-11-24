<?php

namespace App\Repositories\Contracts;

use App\Models\AdminLedger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminLedgerRepositoryInterface
{
    /**
     * Retrieve all admin ledger entries matching filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AdminLedger>
     */
    public function all(array $filters = []): Collection;

    /**
     * Retrieve paginated admin ledger entries.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AdminLedger>
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * Find an entry by ID.
     */
    public function findById(int $id): ?AdminLedger;

    /**
     * Create a new entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AdminLedger;

    /**
     * Update an existing entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete an entry.
     */
    public function delete(int $id): bool;

    /**
     * Compute summary metrics (total income, total expense, closing balance).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getSummary(array $filters = []): array;
}
