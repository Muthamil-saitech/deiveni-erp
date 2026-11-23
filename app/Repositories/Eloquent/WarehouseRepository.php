<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectSite;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    /**
     * Get all warehouse items with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, WarehouseItem>
     */
    public function all(array $filters = []): Collection
    {
        $query = WarehouseItem::with('unit');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $query->whereColumn('current_stock', '<=', 'min_threshold');
            } elseif ($filters['stock_status'] === 'normal') {
                $query->whereColumn('current_stock', '>', 'min_threshold');
            }
        }

        if (! empty($filters['search'])) {
            $term = '%'.trim((string) $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('sku', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('name_ta', 'like', $term)
                    ->orWhere('category', 'like', $term);
            });
        }

        return $query->orderBy('name_en')->get();
    }

    /**
     * Find an item by ID.
     */
    public function findById(int $id): ?WarehouseItem
    {
        return WarehouseItem::with(['movements', 'unit'])->find($id);
    }

    /**
     * Find an item by SKU.
     */
    public function findBySku(string $sku): ?WarehouseItem
    {
        return WarehouseItem::with('unit')->where('sku', $sku)->first();
    }

    /**
     * Get all items currently in stock (> 0).
     *
     * @return Collection<int, WarehouseItem>
     */
    public function getInStockItems(): Collection
    {
        return WarehouseItem::query()
            ->with('unit')
            ->where('status', 'Active')
            ->where('current_stock', '>', 0)
            ->orderBy('name_en')
            ->get();
    }

    /**
     * Create a new warehouse item.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WarehouseItem
    {
        return WarehouseItem::create($data);
    }

    /**
     * Update an existing warehouse item.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(WarehouseItem $item, array $data): WarehouseItem
    {
        $item->update($data);

        return $item->fresh();
    }

    /**
     * Delete a warehouse item.
     */
    public function delete(WarehouseItem $item): bool
    {
        return (bool) $item->delete();
    }

    /**
     * Log a stock movement and adjust inventory balance.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordMovement(array $data): WarehouseMovement
    {
        return DB::transaction(function () use ($data) {
            $itemId = (int) $data['warehouse_item_id'];
            $item = WarehouseItem::findOrFail($itemId);

            $type = $data['movement_type'] ?? 'Issue to Site';
            $qty = (float) ($data['quantity'] ?? 0);

            // Adjust stock
            if ($type === 'Issue to Site') {
                $item->current_stock = max(0, (float) $item->current_stock - $qty);
            } elseif ($type === 'Stock Inward') {
                $item->current_stock = (float) $item->current_stock + $qty;
            } elseif ($type === 'Adjustment') {
                // If direct quantity adjustment
                $item->current_stock = $qty;
            }

            $cashAmount = isset($data['cash_amount']) && $data['cash_amount'] !== '' && (float) $data['cash_amount'] >= 0
                ? (float) $data['cash_amount']
                : (float) ($item->cash_amount ?: $item->unit_rate);

            if ($type === 'Stock Inward' && isset($data['cash_amount']) && (float) $data['cash_amount'] > 0) {
                $item->cash_amount = $cashAmount;
                $item->unit_rate = $cashAmount;
            }

            $item->save();

            $siteName = $data['site_name'] ?? null;
            if (! empty($data['project_site_id']) && ! $siteName) {
                $site = ProjectSite::find($data['project_site_id']);
                $siteName = $site?->name;
            }

            return WarehouseMovement::create([
                'warehouse_item_id' => $itemId,
                'movement_type' => $type,
                'quantity' => $qty,
                'cash_amount' => $cashAmount,
                'project_site_id' => ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : null,
                'site_name' => $siteName,
                'reference_no' => $data['reference_no'] ?? null,
                'movement_date' => $data['movement_date'] ?? Carbon::today()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
        });
    }

    /**
     * Get recent warehouse movements.
     *
     * @return Collection<int, WarehouseMovement>
     */
    public function getMovements(int $limit = 50): Collection
    {
        return WarehouseMovement::with(['item.unit', 'projectSite', 'creator'])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Get warehouse summary statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        $items = WarehouseItem::where('status', 'Active')->get();

        $totalItems = $items->count();
        $totalValuation = 0.0;
        $lowStockCount = 0;

        foreach ($items as $item) {
            $rate = (float) ($item->cash_amount ?: $item->unit_rate);
            $totalValuation += ((float) $item->current_stock * $rate);
            if ($item->isLowStock()) {
                $lowStockCount++;
            }
        }

        // Distinct sites issued to today
        $today = Carbon::today()->toDateString();
        $issuedSitesCount = WarehouseMovement::query()
            ->where('movement_type', 'Issue to Site')
            ->whereDate('movement_date', $today)
            ->whereNotNull('project_site_id')
            ->distinct('project_site_id')
            ->count('project_site_id');

        return [
            'total_items' => $totalItems,
            'total_valuation' => $totalValuation,
            'low_stock_count' => $lowStockCount,
            'issued_sites_today' => $issuedSitesCount,
        ];
    }
}
