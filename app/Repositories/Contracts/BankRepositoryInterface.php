<?php

namespace App\Repositories\Contracts;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Collection;

interface BankRepositoryInterface
{
    /**
     * Retrieve all banks ordered with newest first.
     *
     * @return Collection<int, Bank>
     */
    public function all(): Collection;

    /**
     * Retrieve all active banks.
     *
     * @return Collection<int, Bank>
     */
    public function findActive(): Collection;

    /**
     * Find a bank by ID.
     */
    public function findById(int $id): ?Bank;

    /**
     * Find a bank by name.
     */
    public function findByName(string $name): ?Bank;

    /**
     * Create a new bank record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bank;

    /**
     * Update an existing bank record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Bank $bank, array $data): Bank;

    /**
     * Delete a bank record.
     */
    public function delete(Bank $bank): bool;

    /**
     * Check if a bank name exists.
     */
    public function existsByName(string $name, ?int $ignoreId = null): bool;

    /**
     * Check if a bank is used by any branches.
     */
    public function isUsedInBranches(int $id): bool;

    /**
     * Count total bank records.
     */
    public function count(): int;
}
