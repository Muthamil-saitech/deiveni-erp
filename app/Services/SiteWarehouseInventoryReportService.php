<?php

namespace App\Services;

use App\Repositories\Contracts\SiteWarehouseInventoryReportRepositoryInterface;
use App\Services\Contracts\SiteWarehouseInventoryReportServiceInterface;
use Carbon\Carbon;

class SiteWarehouseInventoryReportService implements SiteWarehouseInventoryReportServiceInterface
{
    public function __construct(
        public SiteWarehouseInventoryReportRepositoryInterface $reportRepo
    ) {}

    /**
     * Get computed filter date ranges and metadata.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array
    {
        $today = Carbon::today();
        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();

        return [
            'today' => [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
                'label' => $today->format('d M Y'),
            ],
            'this_week' => [
                'start' => $thisWeekStart->toDateString(),
                'end' => $thisWeekEnd->toDateString(),
                'label' => $thisWeekStart->format('d M').' - '.$thisWeekEnd->format('d M Y'),
            ],
            'last_week' => [
                'start' => $lastWeekStart->toDateString(),
                'end' => $lastWeekEnd->toDateString(),
                'label' => $lastWeekStart->format('d M').' - '.$lastWeekEnd->format('d M Y'),
            ],
            'this_month' => [
                'start' => $thisMonthStart->toDateString(),
                'end' => $thisMonthEnd->toDateString(),
                'label' => Carbon::now()->format('F Y'),
            ],
            'current_date' => $today->format('d M Y'),
        ];
    }

    /**
     * Get warehouse inventory report data including items, movements, KPIs, and filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $meta = $this->getFilterMeta();
        $period = $filters['period'] ?? 'this_week';
        $category = ! empty($filters['category']) && $filters['category'] !== 'all' ? (string) $filters['category'] : null;
        $stockStatus = ! empty($filters['stock_status']) && $filters['stock_status'] !== 'all' ? (string) $filters['stock_status'] : null;
        $movementType = ! empty($filters['movement_type']) && $filters['movement_type'] !== 'all' ? (string) $filters['movement_type'] : null;
        $search = ! empty($filters['search']) ? trim((string) $filters['search']) : null;

        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = $meta['today']['start'];
                $endDate = $meta['today']['end'];
                break;
            case 'this_week':
                $startDate = $meta['this_week']['start'];
                $endDate = $meta['this_week']['end'];
                break;
            case 'last_week':
                $startDate = $meta['last_week']['start'];
                $endDate = $meta['last_week']['end'];
                break;
            case 'this_month':
                $startDate = $meta['this_month']['start'];
                $endDate = $meta['this_month']['end'];
                break;
            case 'custom':
                $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateString() : null;
                $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->toDateString() : null;
                break;
            case 'all':
            default:
                break;
        }

        $items = $this->reportRepo->getItems($category, $stockStatus, $search);
        $movements = $this->reportRepo->getMovements($startDate, $endDate, $category, $movementType, $search);
        $categories = $this->reportRepo->getCategories();

        // Map items
        $mappedItems = [];
        $totalValuation = 0.0;
        $totalUnits = 0.0;
        $lowStockCount = 0;

        foreach ($items as $item) {
            $stock = (float) $item->current_stock;
            $unitRate = (float) ($item->cash_amount ?: $item->unit_rate);
            $val = $stock * $unitRate;
            $isLow = $item->isLowStock();

            $totalValuation += $val;
            $totalUnits += $stock;
            if ($isLow) {
                $lowStockCount++;
            }

            // Find last issued site or recent movement description
            $latestIssue = $item->movements->firstWhere('movement_type', 'Issue to Site');
            $issuedTo = 'In Warehouse';
            if ($latestIssue) {
                $siteName = $latestIssue->site_name ?: ($latestIssue->projectSite?->name ?? 'Project Site');
                $issuedTo = $siteName.' ('.(float) $latestIssue->quantity.' '.$item->unit.')';
            }

            $mappedItems[] = [
                'id' => $item->id,
                'sku' => $item->sku,
                'nameEn' => $item->name_en,
                'nameTa' => $item->name_ta,
                'category' => $item->category,
                'stock' => $stock,
                'unit' => $item->unit,
                'threshold' => (float) $item->min_threshold,
                'isLow' => $isLow,
                'unitRate' => $unitRate,
                'valuation' => $val,
                'issuedTo' => $issuedTo,
                'movementsCount' => $item->movements->count(),
            ];
        }

        // Map movements
        $mappedMovements = [];
        $issuedQty = 0.0;
        $inwardQty = 0.0;

        foreach ($movements as $m) {
            $qty = (float) $m->quantity;
            if ($m->movement_type === 'Issue to Site') {
                $issuedQty += $qty;
            } elseif ($m->movement_type === 'Stock Inward') {
                $inwardQty += $qty;
            }

            $siteName = $m->site_name ?: ($m->projectSite?->name ?? 'Central Warehouse / Store');

            $mappedMovements[] = [
                'id' => $m->id,
                'date' => $m->movement_date ? $m->movement_date->format('Y-m-d') : '',
                'formattedDate' => $m->movement_date ? $m->movement_date->format('d M Y') : '',
                'refNo' => $m->reference_no ?: 'REF-'.str_pad((string) $m->id, 4, '0', STR_PAD_LEFT),
                'itemId' => $m->warehouse_item_id,
                'itemSku' => $m->item?->sku ?? '',
                'itemNameEn' => $m->item?->name_en ?? 'Unknown Item',
                'itemNameTa' => $m->item?->name_ta ?? '',
                'itemCategory' => $m->item?->category ?? '',
                'movementType' => $m->movement_type,
                'quantity' => $qty,
                'unit' => $m->item?->unit ?? 'Nos',
                'siteId' => $m->project_site_id,
                'siteName' => $siteName,
                'creatorName' => $m->creator?->name ?? 'System Admin',
                'notes' => $m->notes ?? '',
            ];
        }

        $kpis = [
            'total_valuation' => $totalValuation,
            'sku_count' => count($mappedItems),
            'low_stock_count' => $lowStockCount,
            'total_units' => $totalUnits,
            'movements_count' => count($mappedMovements),
            'issued_qty' => $issuedQty,
            'inward_qty' => $inwardQty,
        ];

        return [
            'items' => $mappedItems,
            'movements' => $mappedMovements,
            'categories' => $categories,
            'kpis' => $kpis,
            'filter_meta' => $meta,
            'active_filters' => [
                'period' => $period,
                'category' => $category ?? 'all',
                'stock_status' => $stockStatus ?? 'all',
                'movement_type' => $movementType ?? 'all',
                'search' => $search ?? '',
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
        ];
    }
}
