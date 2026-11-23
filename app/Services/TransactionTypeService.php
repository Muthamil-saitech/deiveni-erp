<?php

namespace App\Services;

use App\Models\TransactionType;
use App\Repositories\Contracts\TransactionTypeRepositoryInterface;
use App\Services\Contracts\TransactionTypeServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class TransactionTypeService implements TransactionTypeServiceInterface
{
    public function __construct(
        public TransactionTypeRepositoryInterface $transactionTypeRepository
    ) {}

    /**
     * Retrieve all transaction types.
     *
     * @return Collection<int, TransactionType>
     */
    public function getAllTransactionTypes(): Collection
    {
        return $this->transactionTypeRepository->all();
    }

    /**
     * Retrieve active transaction types.
     *
     * @return Collection<int, TransactionType>
     */
    public function getActiveTransactionTypes(): Collection
    {
        return $this->transactionTypeRepository->getActive();
    }

    /**
     * Retrieve active transaction types by category.
     *
     * @return Collection<int, TransactionType>
     */
    public function getTransactionTypesByCategory(string $category): Collection
    {
        return $this->transactionTypeRepository->getByCategory($category);
    }

    /**
     * Find a transaction type by ID.
     */
    public function getTransactionTypeById(int $id): ?TransactionType
    {
        return $this->transactionTypeRepository->findById($id);
    }

    /**
     * Create a new transaction type.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransactionType(array $data): TransactionType
    {
        $typeData = [
            'name_en' => trim($data['name_en']),
            'category' => trim($data['category']),
            'status' => $data['status'] ?? 'Active',
        ];

        return $this->transactionTypeRepository->create($typeData);
    }

    /**
     * Update an existing transaction type.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransactionType(int $id, array $data): TransactionType
    {
        $type = $this->transactionTypeRepository->findById($id);

        if (! $type) {
            throw new \InvalidArgumentException("Transaction Type with ID {$id} not found.");
        }

        $updateData = [
            'name_en' => trim($data['name_en']),
            'category' => trim($data['category'] ?? $type->category),
            'status' => $data['status'] ?? 'Active',
        ];

        return $this->transactionTypeRepository->update($type, $updateData);
    }

    /**
     * Delete a transaction type by ID.
     */
    public function deleteTransactionType(int $id): bool
    {
        $type = $this->transactionTypeRepository->findById($id);

        if (! $type) {
            return false;
        }

        return $this->transactionTypeRepository->delete($type);
    }

    /**
     * Get statistics summary.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        $all = $this->getAllTransactionTypes();

        return [
            'total' => $all->count(),
            'active' => $all->where('status', 'Active')->count(),
            'inactive' => $all->where('status', 'Inactive')->count(),
            'construction' => $all->where('category', 'Construction')->count(),
            'bank_valuation' => $all->where('category', 'Bank Valuation')->count(),
            'rental' => $all->where('category', 'Rental')->count(),
            'supplier' => $all->where('category', 'Supplier')->count(),
            'office_expenses' => $all->where('category', 'Office and other expenses')->count(),
        ];
    }
}
