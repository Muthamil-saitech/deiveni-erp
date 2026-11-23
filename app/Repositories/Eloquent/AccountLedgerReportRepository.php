<?php

namespace App\Repositories\Eloquent;

use App\Models\Ledger;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\AccountLedgerReportRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AccountLedgerReportRepository implements AccountLedgerReportRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Scope query by creator or accepted cash transfers for Account Managers.
     */
    protected function scopeByCreator(Builder $query, string $column = 'created_by'): Builder
    {
        $user = Auth::user();

        if ($user && ! $user->isAdmin()) {
            if ($user->isAccountManager()) {
                $query->where(function ($q) use ($user, $column) {
                    $q->where($column, $user->id)
                        ->orWhereHas('cashTransfer', function ($ctq) use ($user) {
                            $ctq->where('accepted_by', $user->id)
                                ->orWhere('created_by', $user->id)
                                ->orWhere('transfer_direction', 'site_to_office');
                        });
                });
            } else {
                $query->where($column, $user->id);
            }
        }

        return $query;
    }

    /**
     * Get ledger records with relations filtered by optional criteria.
     *
     * @return Collection<int, Ledger>
     */
    public function getLedgers(
        ?int $siteId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $flowType = null,
        ?string $cashFlow = null,
        ?string $search = null
    ): Collection {
        $query = $this->scopeByCreator(Ledger::query())
            ->with([
                'projectSite.client',
                'supplier',
                'bank',
                'branch',
                'property',
                'propertyType',
                'transactionType',
                'creator',
            ])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($siteId !== null && $siteId > 0) {
            $query->where('project_site_id', $siteId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('entry_date', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('entry_date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $query->where('entry_date', '<=', $endDate);
        }

        if (! empty($flowType) && $flowType !== 'all') {
            $query->where('source', $flowType);
        }

        if (! empty($cashFlow) && $cashFlow !== 'all') {
            $cleanCashFlow = strtolower($cashFlow);
            if (in_array($cleanCashFlow, ['income', 'expense'], true)) {
                $query->where('flow', $cleanCashFlow);
            }
        }

        if (! empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('source', 'like', "%{$term}%")
                    ->orWhere('site_name', 'like', "%{$term}%")
                    ->orWhere('client_name', 'like', "%{$term}%")
                    ->orWhere('supplier_name', 'like', "%{$term}%")
                    ->orWhere('bank_name', 'like', "%{$term}%")
                    ->orWhere('branch_name', 'like', "%{$term}%")
                    ->orWhere('bank_customer_ref', 'like', "%{$term}%")
                    ->orWhere('property_name', 'like', "%{$term}%")
                    ->orWhere('unit_name', 'like', "%{$term}%")
                    ->orWhere('office_expense_details', 'like', "%{$term}%")
                    ->orWhere('transaction_type_name', 'like', "%{$term}%")
                    ->orWhere('transaction_type_value', 'like', "%{$term}%")
                    ->orWhereHas('projectSite', function ($sq) use ($term) {
                        $sq->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('supplier', function ($sq) use ($term) {
                        $sq->where('name', 'like', "%{$term}%");
                    });
            });
        }

        return $query->get();
    }

    /**
     * Get aggregate financial metrics (Total Income, Total Expense, Net Balance, Count).
     *
     * @return array{total_income: float, total_expense: float, net_balance: float, count: int}
     */
    public function getStats(
        ?int $siteId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $flowType = null,
        ?string $cashFlow = null,
        ?string $search = null
    ): array {
        $ledgers = $this->getLedgers($siteId, $startDate, $endDate, $flowType, $cashFlow, $search);

        $income = 0.0;
        $expense = 0.0;

        foreach ($ledgers as $ledger) {
            $amount = (float) $ledger->amount;
            if ($ledger->flow === 'income') {
                $income += $amount;
            } elseif ($ledger->flow === 'expense') {
                $expense += $amount;
            }
        }

        return [
            'total_income' => $income,
            'total_expense' => $expense,
            'net_balance' => $income - $expense,
            'count' => count($ledgers),
        ];
    }
}
