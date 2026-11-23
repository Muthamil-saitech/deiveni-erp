<?php

namespace App\Services\Contracts;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Collection;

interface BankServiceInterface
{
    /**
     * Retrieve all banks.
     *
     * @return Collection<int, Bank>
     */
    public function getAllBanks(): Collection;

    /**
     * Retrieve all active banks.
     *
     * @return Collection<int, Bank>
     */
    public function getActiveBanks(): Collection;

    /**
     * Find a bank by ID.
     */
    public function getBankById(int $id): ?Bank;

    /**
     * Create a new bank.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBank(array $data): Bank;

    /**
     * Update an existing bank.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBank(int $id, array $data): Bank;

    /**
     * Delete a bank by ID.
     */
    public function deleteBank(int $id): bool;

    /**
     * Check if a bank is currently used by any branches.
     */
    public function isBankInUse(int $id): bool;

    /**
     * Calculate summary statistics for the bank directory.
     *
     * @return array{total_banks: int, active_banks: int, inactive_banks: int}
     */
    public function getBankStats(): array;
}
