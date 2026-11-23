<?php

namespace App\Services\Contracts;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;

interface BranchServiceInterface
{
    /**
     * Retrieve all branches.
     *
     * @return Collection<int, Branch>
     */
    public function getAllBranches(): Collection;

    /**
     * Find a branch by ID.
     */
    public function getBranchById(int $id): ?Branch;

    /**
     * Create a new branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBranch(array $data): Branch;

    /**
     * Update an existing branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBranch(int $id, array $data): Branch;

    /**
     * Delete a branch by ID.
     */
    public function deleteBranch(int $id): bool;

    /**
     * Calculate summary statistics for the branch directory.
     *
     * @return array{total_banks: int, total_branches: int, active_branches: int, inactive_branches: int, avg_fee: float, panel_approval_rate: string}
     */
    public function getBranchStats(): array;
}
