<?php

namespace App\Repositories\Eloquent;

use App\Models\DailyAllocation;
use App\Models\DailyAllocationExpense;
use App\Models\DailyAllocationItem;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\DailyAllocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DailyAllocationRepository implements DailyAllocationRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Find a daily allocation by site and date with items and expenses.
     */
    public function findBySiteAndDate(int $siteId, string $date): ?DailyAllocation
    {
        return $this->scopeByCreator(DailyAllocation::query())
            ->where('project_site_id', $siteId)
            ->whereDate('date', $date)
            ->with(['items.workerCategory', 'expenses', 'projectSite'])
            ->first();
    }

    /**
     * Get allocations for a site within an optional date range.
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getHistory(int $siteId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $query = $this->scopeByCreator(DailyAllocation::query())
            ->where('project_site_id', $siteId)
            ->with(['items', 'expenses']);

        if ($fromDate && $toDate) {
            $query->whereBetween('date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->where('date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->where('date', '<=', $toDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get allocations for a specific week range (Monday to Sunday) for a site.
     *
     * @return Collection<int, DailyAllocation>
     */
    public function getBySiteAndDateRange(int $siteId, string $startDate, string $endDate): Collection
    {
        return $this->scopeByCreator(DailyAllocation::query())
            ->where('project_site_id', $siteId)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['items.workerCategory', 'expenses'])
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Create or update a daily allocation with its line items and expenses.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveAllocation(array $data): DailyAllocation
    {
        return DB::transaction(function () use ($data) {
            $allocation = DailyAllocation::updateOrCreate(
                [
                    'project_site_id' => $data['project_site_id'],
                    'date' => $data['date'],
                ],
                [
                    'total_labour_count' => $data['total_labour_count'] ?? 0,
                    'total_labour_wage' => $data['total_labour_wage'] ?? 0.00,
                    'total_expenses' => $data['total_expenses'] ?? 0.00,
                    'grand_total' => $data['grand_total'] ?? 0.00,
                    'trade_summary' => $data['trade_summary'] ?? null,
                    'created_by' => $data['created_by'] ?? auth()->id(),
                ]
            );

            // Preserve any items already paid by Account Manager
            $existingPaidItems = $allocation->items()
                ->whereNotNull('paid_by')
                ->get()
                ->keyBy(function ($item) {
                    return $item->worker_category_id ?: ($item->category_code ?: $item->name_ta);
                });

            // Re-sync line items
            $allocation->items()->delete();
            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $key = $item['worker_category_id'] ?? ($item['category_code'] ?? ($item['name_ta'] ?? ''));
                    $priorPaid = $existingPaidItems->get($key);

                    DailyAllocationItem::create([
                        'daily_allocation_id' => $allocation->id,
                        'worker_category_id' => $item['worker_category_id'] ?? null,
                        'category_code' => $item['category_code'] ?? ($item['cat'] ?? null),
                        'name_ta' => $item['name_ta'] ?? ($item['nameTa'] ?? ''),
                        'name_en' => $item['name_en'] ?? ($item['nameEn'] ?? null),
                        'daily_wage' => $item['daily_wage'] ?? ($item['rate'] ?? 0),
                        'master_wage' => $item['master_wage'] ?? ($item['masterRate'] ?? ($item['rate'] ?? 0)),
                        'worker_count' => $item['worker_count'] ?? ($item['count'] ?? 0),
                        'subtotal' => $item['subtotal'] ?? 0,
                        'paid_by' => $item['paid_by'] ?? ($priorPaid?->paid_by),
                        'wage_cash_source' => $item['wage_cash_source'] ?? ($priorPaid?->wage_cash_source),
                        'ledger_id' => $item['ledger_id'] ?? ($priorPaid?->ledger_id),
                        'paid_at' => $item['paid_at'] ?? ($priorPaid?->paid_at),
                    ]);
                }
            }

            // Re-sync expenses
            $allocation->expenses()->delete();
            if (! empty($data['expenses']) && is_array($data['expenses'])) {
                foreach ($data['expenses'] as $exp) {
                    $itemName = trim($exp['item_name'] ?? ($exp['title'] ?? ''));
                    $amount = (float) ($exp['amount'] ?? 0);
                    if ($itemName !== '' || $amount > 0) {
                        DailyAllocationExpense::create([
                            'daily_allocation_id' => $allocation->id,
                            'item_name' => $itemName,
                            'quantity' => (float) ($exp['quantity'] ?? ($exp['qty'] ?? 1)),
                            'unit_price' => (float) ($exp['unit_price'] ?? ($exp['unitPrice'] ?? $amount)),
                            'amount' => $amount,
                        ]);
                    }
                }
            }

            return $allocation->fresh(['items.workerCategory', 'expenses', 'projectSite']);
        });
    }

    /**
     * Delete a daily allocation by ID.
     */
    public function delete(int $id): bool
    {
        $allocation = $this->scopeByCreator(DailyAllocation::query())->find($id);
        if ($allocation) {
            return (bool) $allocation->delete();
        }

        return false;
    }
}
