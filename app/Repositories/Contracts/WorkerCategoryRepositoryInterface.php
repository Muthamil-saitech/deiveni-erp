<?php

namespace App\Repositories\Contracts;

use App\Models\WorkerCategory;
use Illuminate\Database\Eloquent\Collection;

interface WorkerCategoryRepositoryInterface
{
    /**
     * Retrieve all worker categories ordered with newest/last added first.
     *
     * @return Collection<int, WorkerCategory>
     */
    public function all(): Collection;

    /**
     * Find a worker category by ID.
     */
    public function findById(int $id): ?WorkerCategory;

    /**
     * Find a worker category by unique code.
     */
    public function findByCode(string $code): ?WorkerCategory;

    /**
     * Create a new worker category record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkerCategory;

    /**
     * Update an existing worker category record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(WorkerCategory $workerCategory, array $data): WorkerCategory;

    /**
     * Delete a worker category record.
     */
    public function delete(WorkerCategory $workerCategory): bool;

    /**
     * Check if a category code exists.
     */
    public function codeExists(string $code, ?int $ignoreId = null): bool;

    /**
     * Count total worker categories.
     */
    public function count(): int;
}
