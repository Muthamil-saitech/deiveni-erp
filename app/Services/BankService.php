<?php

namespace App\Services;

use App\Models\Bank;
use App\Repositories\Contracts\BankRepositoryInterface;
use App\Services\Contracts\BankServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BankService implements BankServiceInterface
{
    public function __construct(
        public BankRepositoryInterface $bankRepository
    ) {}

    /**
     * Retrieve all banks.
     *
     * @return Collection<int, Bank>
     */
    public function getAllBanks(): Collection
    {
        return $this->bankRepository->all();
    }

    /**
     * Retrieve all active banks.
     *
     * @return Collection<int, Bank>
     */
    public function getActiveBanks(): Collection
    {
        return $this->bankRepository->findActive();
    }

    /**
     * Find a bank by ID.
     */
    public function getBankById(int $id): ?Bank
    {
        return $this->bankRepository->findById($id);
    }

    /**
     * Create a new bank.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBank(array $data): Bank
    {
        if (isset($data['bank_name'])) {
            $data['bank_name'] = trim($data['bank_name']);
        }

        if (isset($data['bank_name_ta'])) {
            $data['bank_name_ta'] = trim($data['bank_name_ta']);
        }

        return $this->bankRepository->create($data);
    }

    /**
     * Update an existing bank.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBank(int $id, array $data): Bank
    {
        $bank = $this->bankRepository->findById($id);

        if (! $bank) {
            throw new ModelNotFoundException("Bank with ID {$id} not found.");
        }

        if (isset($data['bank_name'])) {
            $data['bank_name'] = trim($data['bank_name']);
        }

        if (isset($data['bank_name_ta'])) {
            $data['bank_name_ta'] = trim($data['bank_name_ta']);
        }

        return $this->bankRepository->update($bank, $data);
    }

    /**
     * Check if a bank is currently used by any branches.
     */
    public function isBankInUse(int $id): bool
    {
        return $this->bankRepository->isUsedInBranches($id);
    }

    /**
     * Delete a bank by ID.
     */
    public function deleteBank(int $id): bool
    {
        $bank = $this->bankRepository->findById($id);

        if (! $bank) {
            throw new ModelNotFoundException("Bank with ID {$id} not found.");
        }

        if ($this->isBankInUse($id)) {
            throw new \DomainException("Bank \"{$bank->bank_name}\" cannot be deleted because it is currently used by one or more branches in Branch Master.");
        }

        return $this->bankRepository->delete($bank);
    }

    /**
     * Calculate summary statistics for the bank directory.
     *
     * @return array{total_banks: int, active_banks: int, inactive_banks: int}
     */
    public function getBankStats(): array
    {
        $banks = $this->bankRepository->all();
        $total = $banks->count();
        $active = $banks->where('status', 'Active')->count();
        $inactive = $total - $active;

        return [
            'total_banks' => $total,
            'active_banks' => $active,
            'inactive_banks' => $inactive,
        ];
    }
}
