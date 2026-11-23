<?php

namespace App\Services\Contracts;

use App\Models\CashTransfer;

interface CashTransferServiceInterface
{
    /**
     * Get cash transfers along with calculated KPI metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getTransferData(array $filters = []): array;

    /**
     * Find a cash transfer by ID.
     */
    public function getTransferById(int $id): ?CashTransfer;

    /**
     * Create and record a cash transfer, synchronizing with Ledger & Site Cash.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data): CashTransfer;

    /**
     * Update an existing cash transfer and re-sync linked records.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(int $id, array $data): CashTransfer;

    /**
     * Delete a cash transfer and clean up linked Ledger/Site Cash records.
     */
    public function deleteTransfer(int $id): bool;

    /**
     * Mark a temporary transfer as settled / returned.
     *
     * @param  array<string, mixed>  $settlementData
     */
    public function markSettled(int $id, array $settlementData): CashTransfer;
}
