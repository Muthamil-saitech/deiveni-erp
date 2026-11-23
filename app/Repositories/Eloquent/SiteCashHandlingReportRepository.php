<?php

namespace App\Repositories\Eloquent;

use App\Models\SiteCash;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\SiteCashHandlingReportRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class SiteCashHandlingReportRepository implements SiteCashHandlingReportRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Scope query to records accessible by the authenticated user.
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
     * Get site cash transactions with relations filtered by optional criteria.
     *
     * @return Collection<int, SiteCash>
     */
    public function getTransactions(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $flowType = null, ?string $search = null): Collection
    {
        $query = $this->scopeForAccessibleSites(SiteCash::query())
            ->with(['projectSite', 'supplier', 'creator'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc');

        if ($siteId !== null && $siteId > 0) {
            $query->where('project_site_id', $siteId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('payment_date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $query->where('payment_date', '<=', $endDate);
        }

        if (! empty($flowType) && $flowType !== 'all') {
            if (in_array(strtolower($flowType), ['inflow', 'outflow'], true)) {
                $query->where('flow_type', strtolower($flowType));
            } else {
                $query->where('transaction_type', $flowType);
            }
        }

        if (! empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('bill_no', 'like', "%{$term}%")
                    ->orWhere('purchase_order_no', 'like', "%{$term}%")
                    ->orWhere('supplier_name', 'like', "%{$term}%")
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
     * Get aggregate cash statistics (Advances, Collections, Purchases, Wages, Returned, Net).
     *
     * @return array<string, float|int>
     */
    public function getStats(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $transactions = $this->getTransactions($siteId, $startDate, $endDate);

        $advance = 0.0;
        $collections = 0.0;
        $purchases = 0.0;
        $wages = 0.0;
        $returned = 0.0;

        foreach ($transactions as $tx) {
            $inVal = (float) $tx->in_amount;
            $outVal = (float) $tx->out_amount;
            $type = $tx->transaction_type;

            if (in_array($type, ['Advance Received', 'Owner Advance', 'Client Advance', 'Project Cash Received'], true)) {
                $advance += ($inVal > 0 ? $inVal : (float) $tx->amount);
            } elseif (in_array($type, ['Client Collection'], true)) {
                $collections += ($inVal > 0 ? $inVal : (float) $tx->amount);
            } elseif (in_array($type, ['Material Purchase', 'Supplier Payment', 'Tool Rental', 'Other Expense'], true)) {
                $purchases += ($outVal > 0 ? $outVal : (float) $tx->amount);
            } elseif ($type === 'Daily Wage Payment') {
                $wages += ($outVal > 0 ? $outVal : (float) $tx->amount);
            } elseif (in_array($type, ['Balance Returned', 'Balance Returned to AM', 'Balance Returned to Account Manager'], true)) {
                $returned += ($outVal > 0 ? $outVal : (float) $tx->amount);
            } else {
                if ($tx->flow_type === 'inflow') {
                    $advance += ($inVal > 0 ? $inVal : (float) $tx->amount);
                } else {
                    $purchases += ($outVal > 0 ? $outVal : (float) $tx->amount);
                }
            }
        }

        $totalIn = $advance + $collections;
        $totalOut = $purchases + $wages + $returned;
        $netCash = $totalIn - $totalOut;

        return [
            'advance' => $advance,
            'collections' => $collections,
            'purchases' => $purchases,
            'wages' => $wages,
            'returned' => $returned,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net_cash' => $netCash,
            'count' => count($transactions),
        ];
    }
}
