<?php

namespace App\Services;

use App\Models\WorkerCategory;
use App\Repositories\Contracts\WorkerCategoryRepositoryInterface;
use App\Services\Contracts\WorkerCategoryServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WorkerCategoryService implements WorkerCategoryServiceInterface
{
    public function __construct(
        public WorkerCategoryRepositoryInterface $workerCategoryRepository
    ) {}

    /**
     * Retrieve all worker categories.
     *
     * @return Collection<int, WorkerCategory>
     */
    public function getAllCategories(): Collection
    {
        return $this->workerCategoryRepository->all();
    }

    /**
     * Find a worker category by ID.
     */
    public function getCategoryById(int $id): ?WorkerCategory
    {
        return $this->workerCategoryRepository->findById($id);
    }

    /**
     * Create a new worker category with an auto-generated unique code.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): WorkerCategory
    {
        // Enforce business rule: Category Code is strictly auto-generated; user cannot supply or override it.
        unset($data['code']);
        $data['code'] = $this->generateCategoryCode($data['name_en'] ?? null);

        return $this->workerCategoryRepository->create($data);
    }

    /**
     * Update an existing worker category without altering the code.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(int $id, array $data): WorkerCategory
    {
        $category = $this->workerCategoryRepository->findById($id);

        if (! $category) {
            throw new ModelNotFoundException("Worker category with ID {$id} not found.");
        }

        // Enforce business rule: Category Code cannot be altered by user.
        unset($data['code']);

        return $this->workerCategoryRepository->update($category, $data);
    }

    /**
     * Delete a worker category by ID.
     */
    public function deleteCategory(int $id): bool
    {
        $category = $this->workerCategoryRepository->findById($id);

        if (! $category) {
            throw new ModelNotFoundException("Worker category with ID {$id} not found.");
        }

        return $this->workerCategoryRepository->delete($category);
    }

    /**
     * Generate a unique sequential category code in the format "CAT-001", "CAT-002", etc.
     */
    public function generateCategoryCode(?string $nameEn = null): string
    {
        $maxNumber = 0;
        $allCategories = $this->workerCategoryRepository->all();

        foreach ($allCategories as $category) {
            if (preg_match('/^CAT-(\d+)$/i', $category->code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $next = $maxNumber + 1;

        do {
            $candidate = sprintf('CAT-%03d', $next);
            $next++;
        } while ($this->workerCategoryRepository->codeExists($candidate));

        return $candidate;
    }
}
