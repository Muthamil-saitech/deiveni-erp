<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkerCategory;
use App\Repositories\Contracts\WorkerCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WorkerCategoryRepository implements WorkerCategoryRepositoryInterface
{
    /**
     * Retrieve all worker categories ordered with newest/last added first.
     *
     * @return Collection<int, WorkerCategory>
     */
    public function all(): Collection
    {
        return WorkerCategory::query()
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a worker category by ID.
     */
    public function findById(int $id): ?WorkerCategory
    {
        return WorkerCategory::query()->find($id);
    }

    /**
     * Find a worker category by unique code.
     */
    public function findByCode(string $code): ?WorkerCategory
    {
        return WorkerCategory::query()->where('code', $code)->first();
    }

    /**
     * Create a new worker category record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkerCategory
    {
        return WorkerCategory::query()->create($data);
    }

    /**
     * Update an existing worker category record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(WorkerCategory $workerCategory, array $data): WorkerCategory
    {
        $workerCategory->update($data);

        return $workerCategory->refresh();
    }

    /**
     * Delete a worker category record.
     */
    public function delete(WorkerCategory $workerCategory): bool
    {
        return (bool) $workerCategory->delete();
    }

    /**
     * Check if a category code exists.
     */
    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return WorkerCategory::query()
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Count total worker categories.
     */
    public function count(): int
    {
        return WorkerCategory::query()->count();
    }
}
