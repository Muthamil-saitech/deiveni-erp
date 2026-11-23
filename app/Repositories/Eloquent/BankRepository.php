<?php

namespace App\Repositories\Eloquent;

use App\Models\Bank;
use App\Models\Branch;
use App\Repositories\Contracts\BankRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BankRepository implements BankRepositoryInterface
{
    /**
     * Retrieve all banks ordered with newest/last added first.
     *
     * @return Collection<int, Bank>
     */
    public function all(): Collection
    {
        return Bank::query()
            ->withCount('branches')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Retrieve all active banks ordered alphabetically.
     *
     * @return Collection<int, Bank>
     */
    public function findActive(): Collection
    {
        return Bank::query()
            ->where('status', 'Active')
            ->orderBy('bank_name', 'asc')
            ->get();
    }

    /**
     * Find a bank by ID.
     */
    public function findById(int $id): ?Bank
    {
        return Bank::query()->find($id);
    }

    /**
     * Find a bank by name.
     */
    public function findByName(string $name): ?Bank
    {
        return Bank::query()
            ->where('bank_name', trim($name))
            ->first();
    }

    /**
     * Create a new bank record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Bank
    {
        return Bank::query()->create($data);
    }

    /**
     * Update an existing bank record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Bank $bank, array $data): Bank
    {
        $bank->update($data);

        return $bank->refresh();
    }

    /**
     * Delete a bank record.
     */
    public function delete(Bank $bank): bool
    {
        return (bool) $bank->delete();
    }

    /**
     * Check if a bank name exists.
     */
    public function existsByName(string $name, ?int $ignoreId = null): bool
    {
        return Bank::query()
            ->where('bank_name', trim($name))
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Check if a bank is used by any branches.
     */
    public function isUsedInBranches(int $id): bool
    {
        return Branch::query()
            ->where('bank_id', $id)
            ->exists();
    }

    /**
     * Count total bank records.
     */
    public function count(): int
    {
        return Bank::query()->count();
    }
}
