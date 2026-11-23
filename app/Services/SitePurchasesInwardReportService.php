<?php

namespace App\Services;

use App\Repositories\Contracts\SitePurchasesInwardReportRepositoryInterface;
use App\Services\Contracts\SitePurchasesInwardReportServiceInterface;
use Carbon\Carbon;

class SitePurchasesInwardReportService implements SitePurchasesInwardReportServiceInterface
{
    public function __construct(
        public SitePurchasesInwardReportRepositoryInterface $reportRepo
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
     * Get report data containing purchases, KPIs, and filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $meta = $this->getFilterMeta();
        $period = $filters['period'] ?? 'this_week';
        $siteId = ! empty($filters['site_id']) && $filters['site_id'] !== 'all' ? (int) $filters['site_id'] : null;
        $status = ! empty($filters['status']) && $filters['status'] !== 'all' ? (string) $filters['status'] : null;
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
                // No date restriction
                break;
        }

        $orders = $this->reportRepo->getPurchases($siteId, $startDate, $endDate, $status, $search);

        $mappedPurchases = [];
        $totalPoCount = $orders->count();
        $totalPurchaseValue = 0.0;
        $deliveredCount = 0;
        $pendingCount = 0;

        foreach ($orders as $po) {
            $firstItem = $po->items->first();
            $matNames = $po->items->pluck('material_name')->filter()->implode(', ');
            $resolvedStatus = $po->delivery_status ?: ($po->status === 'Order Completed' ? 'Delivered & Inwarded' : 'Inward Pending');

            $poTotal = (float) $po->total_amount;
            $totalPurchaseValue += $poTotal;

            if ($resolvedStatus === 'Delivered & Inwarded') {
                $deliveredCount++;
            } else {
                $pendingCount++;
            }

            $itemsData = $po->items->map(function ($it) {
                return [
                    'id' => $it->id,
                    'material_id' => $it->material_id,
                    'material_name' => $it->material_name,
                    'quantity' => (float) $it->quantity,
                    'unit' => $it->unit,
                    'unit_price' => (float) $it->unit_price,
                    'total_price' => (float) $it->total_price,
                ];
            })->values()->all();

            $mappedPurchases[] = [
                'id' => $po->id,
                'poNumber' => $po->po_number,
                'date' => $po->order_date ? $po->order_date->format('Y-m-d') : '',
                'formattedDate' => $po->order_date ? $po->order_date->format('d M Y') : '',
                'siteId' => $po->project_site_id,
                'site' => $po->projectSite?->name ?? 'Site',
                'siteLocation' => $po->projectSite?->location ?? '',
                'supplierId' => $po->supplier_id,
                'supplier' => $po->supplier_name,
                'sourceType' => $po->source_type,
                'material' => $matNames ?: ($firstItem?->material_name ?? 'Material'),
                'qty' => $firstItem ? (float) $firstItem->quantity : 1,
                'unit' => $firstItem ? $firstItem->unit : 'Nos',
                'rate' => $firstItem ? (float) $firstItem->unit_price : $poTotal,
                'total' => $poTotal,
                'status' => $po->status ?: $resolvedStatus,
                'poStatus' => $po->status,
                'delivery_status' => $po->delivery_status,
                'statusReason' => $po->status_reason,
                'notes' => $po->notes,
                'items' => $itemsData,
            ];
        }

        $kpis = [
            'po_count' => $totalPoCount,
            'total_value' => $totalPurchaseValue,
            'delivered_count' => $deliveredCount,
            'pending_count' => $pendingCount,
        ];

        return [
            'purchases' => $mappedPurchases,
            'kpis' => $kpis,
            'filter_meta' => $meta,
            'active_filters' => [
                'period' => $period,
                'site_id' => $siteId ? (string) $siteId : 'all',
                'status' => $status ?? 'all',
                'search' => $search ?? '',
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
            ],
        ];
    }
}
