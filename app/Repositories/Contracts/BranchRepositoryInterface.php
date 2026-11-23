<?php

namespace App\Repositories\Contracts;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

interface BranchRepositoryInterface
{
    /**
     * Retrieve all branches ordered with newest/last added first.
     *
     * @return Collection<int, Branch>
     */
    public function all(): Collection;

    /**
     * Find a branch by ID.
     */
    public function findById(int $id): ?Branch;

    /**
     * Find a branch by unique IFSC code.
     */
    public function findByIfsc(string $ifsc): ?Branch;

    /**
     * Create a new branch record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch;

    /**
     * Update an existing branch record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Branch $branch, array $data): Branch;

    /**
     * Delete a branch record.
     */
    public function delete(Branch $branch): bool;

    /**
     * Check if an IFSC code already exists.
     */
    public function ifscExists(string $ifsc, ?int $ignoreId = null): bool;

    /**
     * Count total branch records.
     */
    public function count(): int;
}
