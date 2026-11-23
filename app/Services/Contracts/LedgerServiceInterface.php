<?php

namespace App\Services\Contracts;

use App\Models\Ledger;
use Illuminate\Database\Eloquent\Collection;

interface LedgerServiceInterface
{
    /**
     * Retrieve all ledger records matching optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ledger>
     */
    public function getAllLedgers(array $filters = []): Collection;

    /**
     * Find a ledger record by ID with relations.
     */
    public function getLedgerById(int $id): ?Ledger;

    /**
     * Create a new ledger entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function createLedger(array $data): Ledger;

    /**
     * Update an existing ledger entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateLedger(int $id, array $data): Ledger;

    /**
     * Delete a ledger entry by ID.
     */
    public function deleteLedger(int $id): bool;

    /**
     * Get ledger financial summary metrics.
     *
     * @return array{total_income: float, total_expense: float, closing_balance: float, opening_balance: float, yesterday_profit: float, yesterday_date: string}
     */
    public function getSummary(): array;
}
