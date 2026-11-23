<?php

namespace App\Services\Contracts;

use App\Models\WorkerCategory;
use Illuminate\Database\Eloquent\Collection;

interface WorkerCategoryServiceInterface
{
    /**
     * Retrieve all worker categories.
     *
     * @return Collection<int, WorkerCategory>
     */
    public function getAllCategories(): Collection;

    /**
     * Find a worker category by ID.
     */
    public function getCategoryById(int $id): ?WorkerCategory;

    /**
     * Create a new worker category with an auto-generated unique code.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): WorkerCategory;

    /**
     * Update an existing worker category without altering the code.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(int $id, array $data): WorkerCategory;

    /**
     * Delete a worker category by ID.
     */
    public function deleteCategory(int $id): bool;

    /**
     * Generate a unique category code.
     */
    public function generateCategoryCode(?string $nameEn = null): string;
}
