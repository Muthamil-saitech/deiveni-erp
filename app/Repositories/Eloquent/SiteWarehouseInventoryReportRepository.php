<?php

namespace App\Repositories\Eloquent;

use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Repositories\Contracts\SiteWarehouseInventoryReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SiteWarehouseInventoryReportRepository implements SiteWarehouseInventoryReportRepositoryInterface
{
    /**
     * Get warehouse items filtered by category, stock status, and search query.
     *
     * @return Collection<int, WarehouseItem>
     */
    public function getItems(?string $category = null, ?string $stockStatus = null, ?string $search = null): Collection
    {
        $query = WarehouseItem::query()
            ->with(['movements.projectSite'])
            ->where('status', 'Active')
            ->orderBy('name_en');

        if (! empty($category) && $category !== 'all') {
            $query->where('category', $category);
        }

        if (! empty($stockStatus) && $stockStatus !== 'all') {
            if ($stockStatus === 'low') {
                $query->whereColumn('current_stock', '<=', 'min_threshold');
            } elseif ($stockStatus === 'normal') {
                $query->whereColumn('current_stock', '>', 'min_threshold');
            }
        }

        if (! empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                    ->orWhere('name_en', 'like', "%{$term}%")
                    ->orWhere('name_ta', 'like', "%{$term}%")
                    ->orWhere('category', 'like', "%{$term}%");
            });
        }

        return $query->get();
    }

    /**
     * Get warehouse movements with item, project site, and creator relations.
     *
     * @return Collection<int, WarehouseMovement>
     */
    public function getMovements(?string $startDate = null, ?string $endDate = null, ?string $category = null, ?string $movementType = null, ?string $search = null): Collection
    {
        $query = WarehouseMovement::query()
            ->with(['item', 'projectSite', 'creator'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc');

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('movement_date', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('movement_date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $query->where('movement_date', '<=', $endDate);
        }

        if (! empty($category) && $category !== 'all') {
            $query->whereHas('item', function ($q) use ($category) {
                $q->where('category', $category);
            });
        }

        if (! empty($movementType) && $movementType !== 'all') {
            $query->where('movement_type', $movementType);
        }

        if (! empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('reference_no', 'like', "%{$term}%")
                    ->orWhere('site_name', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhereHas('item', function ($iq) use ($term) {
                        $iq->where('sku', 'like', "%{$term}%")
                            ->orWhere('name_en', 'like', "%{$term}%")
                            ->orWhere('name_ta', 'like', "%{$term}%")
                            ->orWhere('category', 'like', "%{$term}%");
                    })
                    ->orWhereHas('projectSite', function ($sq) use ($term) {
                        $sq->where('name', 'like', "%{$term}%")
                            ->orWhere('location', 'like', "%{$term}%");
                    })
                    ->orWhereHas('creator', function ($cq) use ($term) {
                        $cq->where('name', 'like', "%{$term}%");
                    });
            });
        }

        return $query->get();
    }

    /**
     * Get distinct item categories.
     *
     * @return array<int, string>
     */
    public function getCategories(): array
    {
        $categories = WarehouseItem::where('status', 'Active')
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->all();

        if (empty($categories)) {
            return [
                'Raw Material',
                'Plumbing',
                'Hand Tools',
                'Measuring Tools',
                'Power Tools',
                'Safety Gear',
                'Chemicals',
            ];
        }

        return $categories;
    }

    /**
     * Get aggregated inventory and movement statistics.
     *
     * @return array<string, float|int>
     */
    public function getStats(?string $category = null, ?string $stockStatus = null, ?string $search = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $items = $this->getItems($category, $stockStatus, $search);
        $movements = $this->getMovements($startDate, $endDate, $category, null, $search);

        $totalValuation = 0.0;
        $totalUnits = 0.0;
        $lowStockCount = 0;

        foreach ($items as $item) {
            $currentStock = (float) $item->current_stock;
            $unitRate = (float) ($item->cash_amount ?: $item->unit_rate);
            $totalValuation += ($currentStock * $unitRate);
            $totalUnits += $currentStock;

            if ($item->isLowStock()) {
                $lowStockCount++;
            }
        }

        $issuedQty = 0.0;
        $inwardQty = 0.0;

        foreach ($movements as $m) {
            $qty = (float) $m->quantity;
            if ($m->movement_type === 'Issue to Site') {
                $issuedQty += $qty;
            } elseif ($m->movement_type === 'Stock Inward') {
                $inwardQty += $qty;
            }
        }

        return [
            'total_valuation' => $totalValuation,
            'sku_count' => $items->count(),
            'low_stock_count' => $lowStockCount,
            'total_units' => $totalUnits,
            'movements_count' => $movements->count(),
            'issued_qty' => $issuedQty,
            'inward_qty' => $inwardQty,
        ];
    }
}
