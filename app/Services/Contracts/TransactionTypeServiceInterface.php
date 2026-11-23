<?php

namespace App\Services\Contracts;

use App\Models\TransactionType;
use Illuminate\Database\Eloquent\Collection;

interface TransactionTypeServiceInterface
{
    /**
     * Retrieve all transaction types.
     *
     * @return Collection<int, TransactionType>
     */
    public function getAllTransactionTypes(): Collection;

    /**
     * Retrieve active transaction types.
     *
     * @return Collection<int, TransactionType>
     */
    public function getActiveTransactionTypes(): Collection;

    /**
     * Retrieve active transaction types by category.
     *
     * @return Collection<int, TransactionType>
     */
    public function getTransactionTypesByCategory(string $category): Collection;

    /**
     * Find a transaction type by ID.
     */
    public function getTransactionTypeById(int $id): ?TransactionType;

    /**
     * Create a new transaction type.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransactionType(array $data): TransactionType;

    /**
     * Update an existing transaction type.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransactionType(int $id, array $data): TransactionType;

    /**
     * Delete a transaction type by ID.
     */
    public function deleteTransactionType(int $id): bool;

    /**
     * Get statistics summary.
     *
     * @return array<string, int>
     */
    public function getStats(): array;
}
