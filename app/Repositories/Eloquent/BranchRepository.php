<?php

namespace App\Repositories\Eloquent;

use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository implements BranchRepositoryInterface
{
    /**
     * Retrieve all branches ordered with newest/last added first.
     *
     * @return Collection<int, Branch>
     */
    public function all(): Collection
    {
        return Branch::query()
            ->with('bank')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Find a branch by ID.
     */
    public function findById(int $id): ?Branch
    {
        return Branch::query()
            ->with('bank')
            ->find($id);
    }

    /**
     * Find a branch by unique IFSC code.
     */
    public function findByIfsc(string $ifsc): ?Branch
    {
        return Branch::query()
            ->with('bank')
            ->where('ifsc_code', strtoupper($ifsc))
            ->first();
    }

    /**
     * Create a new branch record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Branch
    {
        $branch = Branch::query()->create($data);

        return $branch->load('bank');
    }

    /**
     * Update an existing branch record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->refresh()->load('bank');
    }

    /**
     * Delete a branch record.
     */
    public function delete(Branch $branch): bool
    {
        return (bool) $branch->delete();
    }

    /**
     * Check if an IFSC code already exists.
     */
    public function ifscExists(string $ifsc, ?int $ignoreId = null): bool
    {
        return Branch::query()
            ->where('ifsc_code', strtoupper($ifsc))
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Count total branch records.
     */
    public function count(): int
    {
        return Branch::query()->count();
    }
}
