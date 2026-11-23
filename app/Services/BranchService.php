<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;
use App\Services\Contracts\BranchServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchService implements BranchServiceInterface
{
    public function __construct(
        public BranchRepositoryInterface $branchRepository
    ) {}

    /**
     * Retrieve all branches.
     *
     * @return Collection<int, Branch>
     */
    public function getAllBranches(): Collection
    {
        return $this->branchRepository->all();
    }

    /**
     * Find a branch by ID.
     */
    public function getBranchById(int $id): ?Branch
    {
        return $this->branchRepository->findById($id);
    }

    /**
     * Create a new branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBranch(array $data): Branch
    {
        if (isset($data['ifsc_code'])) {
            $data['ifsc_code'] = strtoupper(trim($data['ifsc_code']));
        }

        if (isset($data['bank_id'])) {
            $data['bank_id'] = (int) $data['bank_id'];
            unset($data['bank_name']);
        } elseif (isset($data['bank_name'])) {
            $bank = Bank::firstOrCreate(
                ['bank_name' => trim((string) $data['bank_name'])],
                ['status' => 'Active']
            );
            $data['bank_id'] = $bank->id;
            unset($data['bank_name']);
        }

        if (isset($data['branch_name'])) {
            $data['branch_name'] = trim($data['branch_name']);
        }

        if (isset($data['branch_name_ta'])) {
            $data['branch_name_ta'] = trim($data['branch_name_ta']);
        }

        if (array_key_exists('valuation_fee', $data) && ($data['valuation_fee'] === null || $data['valuation_fee'] === '')) {
            $data['valuation_fee'] = 0;
        }

        return $this->branchRepository->create($data);
    }

    /**
     * Update an existing branch.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBranch(int $id, array $data): Branch
    {
        $branch = $this->branchRepository->findById($id);

        if (! $branch) {
            throw new ModelNotFoundException("Branch with ID {$id} not found.");
        }

        if (isset($data['ifsc_code'])) {
            $data['ifsc_code'] = strtoupper(trim($data['ifsc_code']));
        }

        if (isset($data['bank_id'])) {
            $data['bank_id'] = (int) $data['bank_id'];
            unset($data['bank_name']);
        } elseif (isset($data['bank_name'])) {
            $bank = Bank::firstOrCreate(
                ['bank_name' => trim((string) $data['bank_name'])],
                ['status' => 'Active']
            );
            $data['bank_id'] = $bank->id;
            unset($data['bank_name']);
        }

        if (isset($data['branch_name'])) {
            $data['branch_name'] = trim($data['branch_name']);
        }

        if (isset($data['branch_name_ta'])) {
            $data['branch_name_ta'] = trim($data['branch_name_ta']);
        }

        if (array_key_exists('valuation_fee', $data) && ($data['valuation_fee'] === null || $data['valuation_fee'] === '')) {
            $data['valuation_fee'] = 0;
        }

        return $this->branchRepository->update($branch, $data);
    }

    /**
     * Delete a branch by ID.
     */
    public function deleteBranch(int $id): bool
    {
        $branch = $this->branchRepository->findById($id);

        if (! $branch) {
            throw new ModelNotFoundException("Branch with ID {$id} not found.");
        }

        return $this->branchRepository->delete($branch);
    }

    /**
     * Calculate summary statistics for the branch directory.
     *
     * @return array{total_banks: int, total_branches: int, active_branches: int, inactive_branches: int, avg_fee: float, panel_approval_rate: string}
     */
    public function getBranchStats(): array
    {
        $branches = $this->branchRepository->all();

        $distinctBanks = $branches->pluck('bank_id')->filter()->unique()->count();
        $activeBranches = $branches->where('status', 'Active');
        $activeCount = $activeBranches->count();
        $inactiveCount = $branches->where('status', 'Inactive')->count();
        $avgFee = $activeCount > 0 ? (float) $activeBranches->avg('valuation_fee') : 0.0;

        return [
            'total_banks' => $distinctBanks ?: $branches->count(),
            'total_branches' => $branches->count(),
            'active_branches' => $activeCount,
            'inactive_branches' => $inactiveCount,
            'avg_fee' => round($avgFee, 2),
            'panel_approval_rate' => '100%',
        ];
    }
}
