<?php

namespace App\Repositories\Eloquent;

use App\Models\AdminLedger;
use App\Repositories\Contracts\AdminLedgerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminLedgerRepository implements AdminLedgerRepositoryInterface
{
    /**
     * Retrieve all admin ledger entries matching filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, AdminLedger>
     */
    public function all(array $filters = []): Collection
    {
        return $this->applyFilters(AdminLedger::query(), $filters)
            ->with(['client', 'projectSite', 'supplier', 'creator'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Retrieve paginated admin ledger entries.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<AdminLedger>
     */
    public function paginate(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->applyFilters(AdminLedger::query(), $filters)
            ->with(['client', 'projectSite', 'supplier', 'creator'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find an entry by ID.
     */
    public function findById(int $id): ?AdminLedger
    {
        return AdminLedger::with(['client', 'projectSite', 'supplier', 'creator'])->find($id);
    }

    /**
     * Create a new entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AdminLedger
    {
        return AdminLedger::create($data);
    }

    /**
     * Update an existing entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        $entry = AdminLedger::find($id);
        if (! $entry) {
            return false;
        }

        return $entry->update($data);
    }

    /**
     * Delete an entry.
     */
    public function delete(int $id): bool
    {
        $entry = AdminLedger::find($id);
        if (! $entry) {
            return false;
        }

        return (bool) $entry->delete();
    }

    /**
     * Compute summary metrics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, float>
     */
    public function getSummary(array $filters = []): array
    {
        $query = $this->applyFilters(AdminLedger::query(), $filters);

        $income = (float) (clone $query)->where('flow', 'income')->sum('amount');
        $expense = (float) (clone $query)->where('flow', 'expense')->sum('amount');
        $netBalance = $income - $expense;

        // Today metrics
        $todayQuery = AdminLedger::query()->whereDate('entry_date', now()->toDateString());
        $todayIncome = (float) (clone $todayQuery)->where('flow', 'income')->sum('amount');
        $todayExpense = (float) (clone $todayQuery)->where('flow', 'expense')->sum('amount');

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'net_balance' => $netBalance,
            'today_income' => $todayIncome,
            'today_expense' => $todayExpense,
        ];
    }

    /**
     * Apply filter criteria to the query.
     *
     * @param  Builder<AdminLedger>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<AdminLedger>
     */
    protected function applyFilters(Builder $query, array $filters = []): Builder
    {
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['flow'])) {
            $query->where('flow', $filters['flow']);
        }

        if (! empty($filters['client_id'])) {
            $query->where('client_id', (int) $filters['client_id']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (! empty($filters['project_site_id'])) {
            $query->where('project_site_id', (int) $filters['project_site_id']);
        }

        if (! empty($filters['date'])) {
            $query->whereDate('entry_date', $filters['date']);
        } elseif (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('entry_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (! empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('client_name', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term)
                    ->orWhere('site_name', 'like', $term)
                    ->orWhere('purchase_order_no', 'like', $term)
                    ->orWhere('payment_reference', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        return $query;
    }
}
