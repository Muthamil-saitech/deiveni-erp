<?php

namespace App\Repositories\Contracts;

use App\Models\CashTransfer;
use Illuminate\Database\Eloquent\Collection;

interface CashTransferRepositoryInterface
{
    /**
     * Retrieve all cash transfers with relations and filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, CashTransfer>
     */
    public function getTransfers(array $filters = []): Collection;

    /**
     * Find a cash transfer by ID.
     */
    public function findById(int $id): ?CashTransfer;

    /**
     * Create a new cash transfer record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CashTransfer;

    /**
     * Update an existing cash transfer record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CashTransfer $transfer, array $data): CashTransfer;

    /**
     * Delete a cash transfer record.
     */
    public function delete(CashTransfer $transfer): bool;
}
