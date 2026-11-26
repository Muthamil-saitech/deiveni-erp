<?php

namespace App\Repositories\Eloquent;

use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\WeeklyWage;
use App\Models\WeeklyWageExpense;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\WeeklyWageRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WeeklyWageRepository implements WeeklyWageRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Find a weekly wage record by site, start date and end date with expenses.
     */
    public function findBySiteAndWeek(int $siteId, string $startDate, string $endDate): ?WeeklyWage
    {
        return $this->scopeByCreator(WeeklyWage::query())
            ->where('project_site_id', $siteId)
            ->whereDate('week_start_date', $startDate)
            ->whereDate('week_end_date', $endDate)
            ->with(['expenses', 'projectSite'])
            ->first();
    }

    /**
     * Sync and update or create weekly wage from daily allocations.
     */
    public function syncFromDailyAllocations(int $siteId, string $startDate, string $endDate): WeeklyWage
    {
        return DB::transaction(function () use ($siteId, $startDate, $endDate) {
            $dailyStats = $this->scopeByCreator(DailyAllocation::query())
                ->where('project_site_id', $siteId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('
                    COALESCE(SUM(total_labour_count), 0) as man_days,
                    COALESCE(SUM(total_labour_wage), 0) as labour_wages,
                    COALESCE(SUM(total_expenses), 0) as daily_expenses
                ')
                ->first();

            $totalManDays = (int) ($dailyStats->man_days ?? 0);
            $totalLabourWages = (float) ($dailyStats->labour_wages ?? 0);
            $totalDailyExpenses = (float) ($dailyStats->daily_expenses ?? 0);

            $weeklyWage = WeeklyWage::firstOrCreate(
                [
                    'project_site_id' => $siteId,
                    'week_start_date' => $startDate,
                    'week_end_date' => $endDate,
                ],
                [
                    'total_weekly_expenses' => 0.00,
                    'created_by' => Auth::id(),
                ]
            );

            // Calculate current weekly expenses
            $totalWeeklyExpenses = (float) $weeklyWage->expenses()->sum('amount');
            $netPayout = $totalLabourWages + $totalDailyExpenses + $totalWeeklyExpenses;

            $weeklyWage->update([
                'total_labour_man_days' => $totalManDays,
                'total_labour_wages' => $totalLabourWages,
                'total_daily_expenses' => $totalDailyExpenses,
                'total_weekly_expenses' => $totalWeeklyExpenses,
                'net_payout' => $netPayout,
            ]);

            return $weeklyWage->fresh(['expenses', 'projectSite']);
        });
    }

    /**
     * Save additional weekly site expenses.
     *
     * @param  array<int, array<string, mixed>>  $expenses
     */
    public function saveWeeklyExpenses(int $siteId, string $startDate, string $endDate, array $expenses): WeeklyWage
    {
        return DB::transaction(function () use ($siteId, $startDate, $endDate, $expenses) {
            // First ensure weekly wage is synced
            $weeklyWage = $this->syncFromDailyAllocations($siteId, $startDate, $endDate);

            // Re-sync expenses
            $weeklyWage->expenses()->delete();
            $totalWeeklyExp = 0.00;

            foreach ($expenses as $exp) {
                $itemName = trim($exp['item_name'] ?? ($exp['title'] ?? ''));
                $amount = (float) ($exp['amount'] ?? ($exp['cost'] ?? 0));
                if ($itemName !== '' || $amount > 0) {
                    WeeklyWageExpense::create([
                        'weekly_wage_id' => $weeklyWage->id,
                        'item_name' => $itemName,
                        'quantity' => (float) ($exp['quantity'] ?? ($exp['qty'] ?? 1)),
                        'unit_price' => (float) ($exp['unit_price'] ?? ($exp['unitPrice'] ?? $amount)),
                        'amount' => $amount,
                    ]);
                    $totalWeeklyExp += $amount;
                }
            }

            $netPayout = (float) $weeklyWage->total_labour_wages + (float) $weeklyWage->total_daily_expenses + $totalWeeklyExp;

            $weeklyWage->update([
                'total_weekly_expenses' => $totalWeeklyExp,
                'net_payout' => $netPayout,
            ]);

            return $weeklyWage->fresh(['expenses', 'projectSite']);
        });
    }

    /**
     * Approve and settle a weekly wage and automatically record the net balance in Site Cash Handling.
     */
    public function settle(int $siteId, string $startDate, string $endDate): WeeklyWage
    {
        return DB::transaction(function () use ($siteId, $startDate, $endDate) {
            $weeklyWage = $this->syncFromDailyAllocations($siteId, $startDate, $endDate);
            $weeklyWage->update([
                'status' => 'settled',
                'settled_at' => now(),
            ]);

            $this->syncWeeklySettlementToSiteCash($weeklyWage, $siteId, $startDate, $endDate);

            return $weeklyWage->fresh(['expenses', 'projectSite']);
        });
    }

    /**
     * Automatically create/update the Weekly Settlement expense in Site Cash Handling,
     * deducting Site Manager daily wage payments and Account Manager office cash payments
     * (while NOT deducting Account Manager project cash payments).
     */
    protected function syncWeeklySettlementToSiteCash(
        WeeklyWage $weeklyWage,
        int $siteId,
        string $startDate,
        string $endDate
    ): void {
        $startStr = Carbon::parse($startDate)->toDateString();
        $endStr = Carbon::parse($endDate)->toDateString();

        // 1. Sum daily Site Cash wage payments made by Site Manager in this week
        $dailyWageTxns = SiteCash::query()
            ->where('project_site_id', $siteId)
            ->whereIn('transaction_type', ['Daily Wage Payment', 'Daily Worker Wage Payment'])
            ->get();

        $dailyPaidAmount = 0.0;
        $amSiteCashPaidAmount = 0.0;
        foreach ($dailyWageTxns as $txn) {
            $resolvedDate = null;
            $billNo = (string) ($txn->bill_no ?? '');
            if (preg_match('/(\d{4}-\d{2}-\d{2})/', $billNo, $m)) {
                $resolvedDate = $m[1];
            } elseif ($txn->payment_date) {
                $resolvedDate = Carbon::parse($txn->payment_date)->toDateString();
            }

            if ($resolvedDate && $resolvedDate >= $startStr && $resolvedDate <= $endStr) {
                if ($txn->paid_by === 'Account Manager') {
                    $amSiteCashPaidAmount += (float) $txn->amount;
                } else {
                    $dailyPaidAmount += (float) $txn->amount;
                }
            }
        }

        // 2. Sum Account Manager paid items: deduct ONLY office_cash, NOT project_cash
        //    (project_cash AM items are already recorded as SiteCash AM-WAGE-* expenses and deducted via $amSiteCashPaidAmount).
        $amOfficePaidAmount = (float) DailyAllocationItem::query()
            ->whereHas('dailyAllocation', function ($q) use ($siteId, $startStr, $endStr) {
                $q->where('project_site_id', $siteId)
                    ->whereBetween('date', [$startStr, $endStr]);
            })
            ->where('paid_by', 'Account Manager')
            ->where(function ($q) {
                $q->whereNull('wage_cash_source')
                    ->orWhere('wage_cash_source', '!=', 'project_cash');
            })
            ->sum('subtotal');

        $netPayout = round((float) $weeklyWage->net_payout, 2);
        $payableAmount = max(0.0, round($netPayout - $dailyPaidAmount - $amOfficePaidAmount - $amSiteCashPaidAmount, 2));

        $existing = SiteCash::query()
            ->where('project_site_id', $siteId)
            ->where('weekly_wage_id', $weeklyWage->id)
            ->where('transaction_type', 'Weekly Settlement')
            ->first();

        if ($payableAmount > 0.01) {
            $startFormatted = Carbon::parse($startStr)->format('d M Y');
            $endFormatted = Carbon::parse($endStr)->format('d M Y');
            $payload = [
                'project_site_id' => $siteId,
                'weekly_wage_id' => $weeklyWage->id,
                'transaction_type' => 'Weekly Settlement',
                'flow_type' => 'outflow',
                'amount' => $payableAmount,
                'in_amount' => 0.00,
                'out_amount' => $payableAmount,
                'is_expense' => true,
                'payment_mode' => 'Cash',
                'paid_to' => 'Site Manager',
                'bill_no' => "WEEKLY-{$startStr}",
                'payment_date' => now()->toDateString(),
                'payment_time' => now()->format('h:i A'),
                'description' => "Weekly settlement ({$startFormatted} – {$endFormatted})",
                'created_by' => Auth::id() ?? $weeklyWage->created_by,
            ];

            if ($existing) {
                $existing->update($payload);
            } else {
                SiteCash::create($payload);
            }
        } elseif ($existing) {
            $existing->delete();
        }

        // Recalculate project_sites.spent
        $totalSpent = (float) SiteCash::query()
            ->where('project_site_id', $siteId)
            ->where('is_expense', true)
            ->sum('amount');

        ProjectSite::whereKey($siteId)->update(['spent' => round($totalSpent, 2)]);
    }

    /**
     * Get all weekly wages for a site.
     *
     * @return Collection<int, WeeklyWage>
     */
    public function getBySite(int $siteId): Collection
    {
        return $this->scopeByCreator(WeeklyWage::query())
            ->where('project_site_id', $siteId)
            ->with(['expenses'])
            ->orderBy('week_start_date', 'desc')
            ->get();
    }

    /**
     * Re-sync the Weekly Settlement Site Cash entry if a settled week covers the given date.
     */
    public function resyncSettledWeekForDate(int $siteId, string $date): void
    {
        $dateStr = Carbon::parse($date)->toDateString();

        $settledWeeks = WeeklyWage::query()
            ->where('project_site_id', $siteId)
            ->where('status', 'settled')
            ->whereDate('week_start_date', '<=', $dateStr)
            ->whereDate('week_end_date', '>=', $dateStr)
            ->get();

        foreach ($settledWeeks as $weeklyWage) {
            DB::transaction(function () use ($weeklyWage, $siteId) {
                $startStr = Carbon::parse($weeklyWage->week_start_date)->toDateString();
                $endStr = Carbon::parse($weeklyWage->week_end_date)->toDateString();
                $this->syncWeeklySettlementToSiteCash($weeklyWage, $siteId, $startStr, $endStr);
            });
        }
    }
}
