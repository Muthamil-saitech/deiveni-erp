<?php

namespace App\Services;

use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\StockTransfer;
use App\Services\Contracts\StockTransferReportServiceInterface;
use Carbon\Carbon;

class StockTransferReportService implements StockTransferReportServiceInterface
{
    /**
     * Get aggregated and filtered report data.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $period = $filters['period'] ?? 'all';
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        // Resolve date period bounds
        $resolvedDates = $this->resolvePeriodDates($period, $startDate, $endDate);
        $startDate = $resolvedDates['start'];
        $endDate = $resolvedDates['end'];

        $query = StockTransfer::query()
            ->with(['fromSite', 'toSite', 'material.unit', 'creator', 'parentTransfer', 'returns.creator'])
            ->latest('transfer_date')
            ->latest('id');

        if ($startDate) {
            $query->whereDate('transfer_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('transfer_date', '<=', $endDate);
        }

        if (! empty($filters['from_site_id'])) {
            $query->where('from_project_site_id', (int) $filters['from_site_id']);
        }
        if (! empty($filters['to_site_id'])) {
            $query->where('to_project_site_id', (int) $filters['to_site_id']);
        }
        if (! empty($filters['site_id'])) {
            $siteId = (int) $filters['site_id'];
            $query->where(function ($q) use ($siteId) {
                $q->where('from_project_site_id', $siteId)
                    ->orWhere('to_project_site_id', $siteId);
            });
        }
        if (! empty($filters['material_id'])) {
            $query->where('material_id', (int) $filters['material_id']);
        }
        if (! empty($filters['transfer_type'])) {
            $query->where('transfer_type', $filters['transfer_type']);
        }
        if (! empty($filters['return_status'])) {
            $query->where('return_status', $filters['return_status']);
        }
        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%{$s}%")
                    ->orWhere('challan_no', 'like', "%{$s}%")
                    ->orWhere('vehicle_no', 'like', "%{$s}%")
                    ->orWhereHas('material', function ($mq) use ($s) {
                        $mq->where('name_en', 'like', "%{$s}%")
                            ->orWhere('name_ta', 'like', "%{$s}%");
                    })
                    ->orWhereHas('fromSite', function ($sq) use ($s) {
                        $sq->where('site_name', 'like', "%{$s}%");
                    })
                    ->orWhereHas('toSite', function ($sq) use ($s) {
                        $sq->where('site_name', 'like', "%{$s}%");
                    });
            });
        }

        $transfers = $query->get();

        // Calculate KPIs
        $outwardTransfers = $transfers->where('transfer_type', 'TRANSFER');
        $returnTransfers = $transfers->where('transfer_type', 'RETURN');

        $totalTransfersCount = $outwardTransfers->count();
        $totalTransferredQty = (float) $outwardTransfers->sum('quantity');

        $totalReturnsCount = $returnTransfers->count();
        $totalReturnedQty = (float) $returnTransfers->sum('quantity');

        $pendingTransfers = $outwardTransfers->whereIn('return_status', ['Pending Return', 'Partially Returned']);
        $totalPendingQty = (float) $pendingTransfers->sum(fn ($t) => $t->pending_return_quantity);
        $pendingTransfersCount = $pendingTransfers->count();

        $fullyReturnedCount = $outwardTransfers->where('return_status', 'Fully Returned')->count();

        // Site-to-Site Lending Balance Matrix (Who owes what to whom)
        $siteBalances = [];
        foreach ($outwardTransfers as $tr) {
            $fromId = $tr->from_project_site_id;
            $toId = $tr->to_project_site_id;
            $matId = $tr->material_id;
            $key = "{$fromId}_{$toId}_{$matId}";

            if (! isset($siteBalances[$key])) {
                $siteBalances[$key] = [
                    'from_site_id' => $fromId,
                    'from_site_name' => $tr->fromSite?->site_name ?? 'Site #'.$fromId,
                    'to_site_id' => $toId,
                    'to_site_name' => $tr->toSite?->site_name ?? 'Site #'.$toId,
                    'material_id' => $matId,
                    'material_name_en' => $tr->material?->name_en ?? 'Material #'.$matId,
                    'material_name_ta' => $tr->material?->name_ta ?? '',
                    'unit' => $tr->unit,
                    'transferred_qty' => 0.0,
                    'returned_qty' => 0.0,
                    'pending_qty' => 0.0,
                    'transfers_count' => 0,
                ];
            }

            $siteBalances[$key]['transferred_qty'] += (float) $tr->quantity;
            $siteBalances[$key]['returned_qty'] += (float) $tr->returned_quantity;
            $siteBalances[$key]['pending_qty'] += (float) $tr->pending_return_quantity;
            $siteBalances[$key]['transfers_count']++;
        }

        // Sort matrix: ones with highest pending quantity first
        usort($siteBalances, fn ($a, $b) => $b['pending_qty'] <=> $a['pending_qty']);

        return [
            'transfers' => $transfers,
            'kpis' => [
                'total_transfers_count' => $totalTransfersCount,
                'total_transferred_qty' => $totalTransferredQty,
                'total_returns_count' => $totalReturnsCount,
                'total_returned_qty' => $totalReturnedQty,
                'pending_transfers_count' => $pendingTransfersCount,
                'total_pending_qty' => $totalPendingQty,
                'fully_returned_count' => $fullyReturnedCount,
            ],
            'site_balances' => array_values($siteBalances),
            'filter_meta' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'from_site_id' => $filters['from_site_id'] ?? null,
                'to_site_id' => $filters['to_site_id'] ?? null,
                'site_id' => $filters['site_id'] ?? null,
                'material_id' => $filters['material_id'] ?? null,
                'transfer_type' => $filters['transfer_type'] ?? null,
                'return_status' => $filters['return_status'] ?? null,
                'search' => $filters['search'] ?? null,
            ],
            'active_filters' => array_filter([
                'period' => $period !== 'all' ? ucfirst(str_replace('_', ' ', $period)) : null,
                'from_site' => ! empty($filters['from_site_id']) ? ProjectSite::find($filters['from_site_id'])?->site_name : null,
                'to_site' => ! empty($filters['to_site_id']) ? ProjectSite::find($filters['to_site_id'])?->site_name : null,
                'material' => ! empty($filters['material_id']) ? Material::find($filters['material_id'])?->name_en : null,
                'return_status' => $filters['return_status'] ?? null,
            ]),
        ];
    }

    /**
     * Resolve date bounds based on standard period options.
     *
     * @return array{start: ?string, end: ?string}
     */
    protected function resolvePeriodDates(string $period, ?string $customStart, ?string $customEnd): array
    {
        $today = Carbon::today();

        return match ($period) {
            'today' => [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
            ],
            'this_week' => [
                'start' => $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'end' => $today->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
            ],
            'this_month' => [
                'start' => $today->copy()->startOfMonth()->toDateString(),
                'end' => $today->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'start' => $today->copy()->subMonth()->startOfMonth()->toDateString(),
                'end' => $today->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
            'custom' => [
                'start' => $customStart,
                'end' => $customEnd,
            ],
            default => [
                'start' => null,
                'end' => null,
            ],
        };
    }
}
