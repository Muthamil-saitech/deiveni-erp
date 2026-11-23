<?php

namespace App\Repositories\Eloquent;

use App\Models\SiteCash;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\SiteCashRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SiteCashRepository implements SiteCashRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Scope query to records accessible by the authenticated user.
     * Non-admins can see:
     * 1) Records they created directly, OR
     * 2) Records belonging to project sites they manage/created, OR
     * 3) Records linked to cash transfers for their project site or where they are receiver.
     *
     * @param  Builder<SiteCash>  $query
     * @return Builder<SiteCash>
     */
    protected function scopeForAccessibleSites(Builder $query): Builder
    {
        $user = Auth::user();

        if ($user && $user->isSiteManager()) {
            $query->whereHas('projectSite', function ($siteQuery) use ($user) {
                $siteQuery->where('created_by', $user->id);
            });
        } elseif ($user && ! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('projectSite', function ($sq) use ($user) {
                        $sq->where('created_by', $user->id);
                    })
                    ->orWhereHas('cashTransfer', function ($cq) use ($user) {
                        $cq->where('created_by', $user->id)
                            ->orWhere('received_by', 'like', "%{$user->name}%")
                            ->orWhere('received_by', 'like', '%Site Manager%')
                            ->orWhere('received_by', 'like', '%Site Engineer%');
                    });

            });
        }

        return $query;
    }

    /**
     * Retrieve all site cash records with relations ordered newest first.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, SiteCash>
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->scopeForAccessibleSites(SiteCash::query())
            ->with(['projectSite', 'supplier', 'creator'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc');

        if (! empty($filters['project_site_id'])) {
            $query->where('project_site_id', $filters['project_site_id']);
        }

        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('bill_no', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%")
                    ->orWhere('bank_name', 'like', "%{$search}%")
                    ->orWhere('upi_id', 'like', "%{$search}%")
                    ->orWhere('account_holder_name', 'like', "%{$search}%")
                    ->orWhere('payment_mode', 'like', "%{$search}%")
                    ->orWhere('paid_to', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%")
                    ->orWhereHas('projectSite', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->get();
    }

    /**
     * Find a site cash record by ID with relations.
     */
    public function findById(int $id): ?SiteCash
    {
        return $this->scopeForAccessibleSites(SiteCash::query())
            ->with(['projectSite', 'supplier', 'creator'])
            ->find($id);
    }

    /**
     * Create a new site cash transaction record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SiteCash
    {
        return SiteCash::query()->create($data)->load(['projectSite', 'supplier', 'creator']);
    }

    /**
     * Update an existing site cash transaction record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SiteCash $siteCash, array $data): SiteCash
    {
        $siteCash->update($data);

        return $siteCash->fresh(['projectSite', 'supplier', 'creator']);
    }

    /**
     * Delete a site cash transaction record.
     */
    public function delete(SiteCash $siteCash): bool
    {
        return (bool) $siteCash->delete();
    }

    /**
     * Get aggregate cash statistics (Advances, Collections, Purchases, Wages, Returned, Cash-in-Hand).
     *
     * @return array<string, float>
     */
    public function getStats(?int $projectSiteId = null): array
    {
        $query = $this->scopeForAccessibleSites(SiteCash::query());
        if ($projectSiteId) {
            $query->where('project_site_id', $projectSiteId);
        }

        $all = $query->get();

        $advance = 0.0;
        $collections = 0.0;
        $purchases = 0.0;
        $wages = 0.0;
        $returned = 0.0;

        foreach ($all as $item) {
            // Direct Account Manager wage expenses:
            // 1) Office Cash: Paid from Head Office funds; does NOT reduce Site Manager cash-in-hand (Site Manager returns unspent advance)
            // 2) Project Cash: Paid from Site/Project cash; DOES reduce Site Manager cash-in-hand (Site Manager does not return this)
            if ($item->flow_type === 'outflow' && $item->paid_by === 'Account Manager') {
                if ($item->wage_cash_source === 'office_cash' || empty($item->wage_cash_source)) {
                    continue;
                }
            }

            $inVal = (float) $item->in_amount;
            $outVal = (float) $item->out_amount;
            $type = $item->transaction_type;

            if (in_array($type, ['Advance Received', 'Owner Advance', 'Project Cash Received'])) {
                $advance += $inVal;
            } elseif (in_array($type, ['Client Collection', 'Client Advance'])) {
                $collections += $inVal;
            } elseif (in_array($type, ['Material Purchase', 'Supplier Payment', 'Tool Rental', 'Other Expense'])) {
                $purchases += $outVal;
            } elseif ($type === 'Daily Wage Payment') {
                $wages += $outVal;
            } elseif ($type === 'Balance Returned') {
                $returned += $outVal;
            } else {
                // Generic fallback according to flow_type
                if ($item->flow_type === 'inflow') {
                    $advance += $inVal;
                } else {
                    $purchases += $outVal;
                }
            }
        }

        $cashInHand = ($advance + $collections) - ($purchases + $wages + $returned);

        return [
            'advance' => $advance,
            'collections' => $collections,
            'purchases' => $purchases,
            'wages' => $wages,
            'returned' => $returned,
            'cash_in_hand' => $cashInHand,
            'total_spent' => (float) $all->where('is_expense', true)->sum('amount'),
        ];
    }

    /**
     * Get total expense sum for a specific project site.
     */
    public function getExpensesSumForSite(int $projectSiteId): float
    {
        return (float) $this->scopeForAccessibleSites(SiteCash::query())
            ->where('project_site_id', $projectSiteId)
            ->where('is_expense', true)
            ->sum('amount');
    }
}
