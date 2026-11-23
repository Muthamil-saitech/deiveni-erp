<?php

namespace App\Repositories\Contracts;

use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Collection;

interface TransactionTypeRepositoryInterface
{
    /**
     * Retrieve all transaction types ordered by id.
     *
     * @return Collection<int, TransactionType>
     */
    public function all(): Collection;

    /**
     * Retrieve all active transaction types.
     *
     * @return Collection<int, TransactionType>
     */
    public function getActive(): Collection;

    /**
     * Retrieve active types by category.
     *
     * @return Collection<int, TransactionType>
     */
    public function getByCategory(string $category): Collection;

    /**
     * Find a transaction type by ID.
     */
    public function findById(int $id): ?TransactionType;

    /**
     * Create a new transaction type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TransactionType;

    /**
     * Update an existing transaction type record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(TransactionType $transactionType, array $data): TransactionType;

    /**
     * Delete a transaction type record.
     */
    public function delete(TransactionType $transactionType): bool;
}
