<?php

namespace App\Repositories\Contracts;

use App\Models\Ledger;
use Illuminate\Database\Eloquent\Collection;

interface LedgerRepositoryInterface
{
    /**
     * Retrieve all ledger records matching optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Ledger>
     */
    public function all(array $filters = []): Collection;

    /**
     * Find a ledger record by ID with relations.
     */
    public function findById(int $id): ?Ledger;

    /**
     * Create a new ledger entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Ledger;

    /**
     * Update an existing ledger entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Ledger $ledger, array $data): Ledger;

    /**
     * Delete a ledger entry.
     */
    public function delete(Ledger $ledger): bool;

    /**
     * Calculate summary statistics (total income, total expense, closing balance, yesterday's profit / opening balance).
     *
     * @return array{total_income: float, total_expense: float, closing_balance: float, opening_balance: float, yesterday_profit: float, yesterday_date: string}
     */
    public function getTotals(): array;

    /**
     * Compute chronological running balances for ledger entries.
     *
     * @return array<int, float>
     */
    public function getRunningBalances(): array;
}
