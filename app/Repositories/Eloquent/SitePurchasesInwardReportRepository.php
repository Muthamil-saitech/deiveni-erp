<?php

namespace App\Repositories\Eloquent;

use App\Models\PurchaseOrder;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\SitePurchasesInwardReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SitePurchasesInwardReportRepository implements SitePurchasesInwardReportRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Get purchase orders with relations filtered by optional criteria.
     *
     * @return Collection<int, PurchaseOrder>
     */
    public function getPurchases(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $status = null, ?string $search = null): Collection
    {
        $query = $this->scopeByCreator(PurchaseOrder::query())
            ->with(['projectSite', 'supplier', 'items.material', 'creator'])
            ->orderBy('order_date', 'desc')
            ->orderBy('id', 'desc');

        if ($siteId !== null && $siteId > 0) {
            $query->where('project_site_id', $siteId);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $query->where('order_date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $query->where('order_date', '<=', $endDate);
        }

        if (! empty($status) && $status !== 'all') {
            if ($status === 'Delivered & Inwarded') {
                $query->where(function ($q) {
                    $q->where('delivery_status', 'Delivered & Inwarded')
                        ->orWhere('status', 'Order Completed');
                });
            } elseif ($status === 'Inward Pending') {
                $query->where(function ($q) {
                    $q->where('delivery_status', '!=', 'Delivered & Inwarded')
                        ->orWhereNull('delivery_status');
                })->where('status', '!=', 'Order Completed');
            } else {
                $query->where(function ($q) use ($status) {
                    $q->where('delivery_status', $status)
                        ->orWhere('status', $status);
                });
            }
        }

        if (! empty($search)) {
            $term = trim($search);
            $query->where(function ($q) use ($term) {
                $q->where('po_number', 'like', "%{$term}%")
                    ->orWhere('supplier_name', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhere('status_reason', 'like', "%{$term}%")
                    ->orWhereHas('projectSite', function ($sq) use ($term) {
                        $sq->where('name', 'like', "%{$term}%")
                            ->orWhere('location', 'like', "%{$term}%");
                    })
                    ->orWhereHas('supplier', function ($sq) use ($term) {
                        $sq->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('items', function ($sq) use ($term) {
                        $sq->where('material_name', 'like', "%{$term}%");
                    });
            });
        }

        return $query->get();
    }

    /**
     * Get aggregate purchase statistics (Total POs, Total Value, Delivered, Pending).
     *
     * @return array<string, float|int>
     */
    public function getStats(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $status = null, ?string $search = null): array
    {
        $purchases = $this->getPurchases($siteId, $startDate, $endDate, $status, $search);

        $poCount = $purchases->count();
        $totalValue = (float) $purchases->sum('total_amount');
        $deliveredCount = 0;
        $pendingCount = 0;

        foreach ($purchases as $po) {
            $isDelivered = ($po->delivery_status === 'Delivered & Inwarded' || $po->status === 'Order Completed');
            if ($isDelivered) {
                $deliveredCount++;
            } else {
                $pendingCount++;
            }
        }

        return [
            'po_count' => $poCount,
            'total_value' => $totalValue,
            'delivered_count' => $deliveredCount,
            'pending_count' => $pendingCount,
        ];
    }
}
