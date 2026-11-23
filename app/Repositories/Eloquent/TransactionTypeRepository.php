<?php

namespace App\Repositories\Eloquent;

use App\Models\TransactionType;
use App\Repositories\Contracts\TransactionTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TransactionTypeRepository implements TransactionTypeRepositoryInterface
{
    /**
     * Retrieve all transaction types ordered with newest first or by ID.
     *
     * @return Collection<int, TransactionType>
     */
    public function all(): Collection
    {
        return TransactionType::orderBy('id', 'asc')->get();
    }

    /**
     * Retrieve all active transaction types.
     *
     * @return Collection<int, TransactionType>
     */
    public function getActive(): Collection
    {
        return TransactionType::active()->orderBy('name_en', 'asc')->get();
    }

    /**
     * Retrieve active types by category.
     *
     * @return Collection<int, TransactionType>
     */
    public function getByCategory(string $category): Collection
    {
        return TransactionType::active()->byCategory($category)->orderBy('name_en', 'asc')->get();
    }

    /**
     * Find a transaction type by ID.
     */
    public function findById(int $id): ?TransactionType
    {
        return TransactionType::find($id);
    }

    /**
     * Create a new transaction type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransactionType
    {
        return TransactionType::create($data);
    }

    /**
     * Update an existing transaction type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TransactionType $transactionType, array $data): TransactionType
    {
        $transactionType->update($data);

        return $transactionType->fresh();
    }

    /**
     * Delete a transaction type record.
     */
    public function delete(TransactionType $transactionType): bool
    {
        return (bool) $transactionType->delete();
    }
}
