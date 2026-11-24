<?php

namespace App\Services\Contracts;

use App\Models\AdminLedger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface AdminLedgerServiceInterface
{
    /**
     * Retrieve all entries matching filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AdminLedger>
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Retrieve paginated entries.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AdminLedger>
     */
    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * Find entry by ID.
     */
    public function getById(int $id): ?AdminLedger;

    /**
     * Create a new entry with file handling.
     *
     * @param  array<string, mixed>  $data
     */
    public function createEntry(array $data, ?UploadedFile $image = null): AdminLedger;

    /**
     * Update an entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateEntry(int $id, array $data, ?UploadedFile $image = null): bool;

    /**
     * Delete an entry.
     */
    public function deleteEntry(int $id): bool;

    /**
     * Get summary metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getSummary(array $filters = []): array;

    /**
     * Get running balances for a list of entries.
     *
     * @param  Collection<int, AdminLedger>  $entries
     * @return array<int, float>
     */
    public function calculateRunningBalances(Collection $entries): array;
}
