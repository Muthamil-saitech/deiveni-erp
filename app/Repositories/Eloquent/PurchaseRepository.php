<?php

namespace App\Repositories\Eloquent;

use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MaterialUsageItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Repositories\Concerns\ScopesByCreator;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseRepository implements PurchaseRepositoryInterface
{
    use ScopesByCreator;

    /**
     * Get all purchase orders with optional filtering.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PurchaseOrder>
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->scopeByCreator(
            PurchaseOrder::with(['projectSite', 'supplier', 'items.material'])
        );

        if (! empty($filters['project_site_id']) && $filters['project_site_id'] !== 'all') {
            $query->where('project_site_id', $filters['project_site_id']);
        }

        if (! empty($filters['site_name']) && $filters['site_name'] !== 'all') {
            $query->whereHas('projectSite', function ($q) use ($filters) {
                $q->where('name', $filters['site_name']);
            });
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['delivery_status']) && $filters['delivery_status'] !== 'all') {
            $query->where('delivery_status', $filters['delivery_status']);
        }

        if (! empty($filters['supplier_name']) && $filters['supplier_name'] !== 'all') {
            $query->where('supplier_name', $filters['supplier_name']);
        }

        if (! empty($filters['source_type']) && $filters['source_type'] !== 'all') {
            $query->where('source_type', $filters['source_type']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween('order_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (! empty($filters['search'])) {
            $term = '%'.trim((string) $filters['search']).'%';
            $query->where(function ($q) use ($term) {
                $q->where('po_number', 'like', $term)
                    ->orWhere('supplier_name', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('items', function ($iq) use ($term) {
                        $iq->where('material_name', 'like', $term);
                    })
                    ->orWhereHas('projectSite', function ($sq) use ($term) {
                        $sq->where('name', 'like', $term);
                    });
            });
        }

        return $query->orderByDesc('order_date')->orderByDesc('id')->get();
    }

    /**
     * Find a purchase order by ID.
     */
    public function findById(int $id): ?PurchaseOrder
    {
        return $this->scopeByCreator(
            PurchaseOrder::with(['projectSite', 'supplier', 'items.material', 'creator'])
        )->find($id);
    }

    /**
     * Find a purchase order by PO or Bill number.
     */
    public function findByPoNumber(string $poNumber): ?PurchaseOrder
    {
        return $this->scopeByCreator(
            PurchaseOrder::with(['projectSite', 'supplier', 'items.material', 'creator'])
        )
            ->where('po_number', $poNumber)
            ->first();
    }

    /**
     * Create a new purchase order with items.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $itemsData = $data['items'] ?? [];
            unset($data['items']);

            // Calculate totals if not passed
            $subtotal = 0.0;
            foreach ($itemsData as $it) {
                $qty = (float) ($it['quantity'] ?? 1);
                $rate = (float) ($it['unit_price'] ?? ($it['rate'] ?? 0));
                $subtotal += ($qty * $rate);
            }

            if (! isset($data['total_amount']) || (float) $data['total_amount'] <= 0) {
                $data['subtotal'] = $subtotal;
                $data['total_amount'] = $subtotal;
            }

            // Sync delivery status with status
            if (! isset($data['delivery_status'])) {
                $data['delivery_status'] = in_array($data['status'] ?? '', ['Delivered & Inwarded', 'Order Completed'], true)
                    ? 'Delivered & Inwarded'
                    : 'Inward Pending';
            }

            $order = PurchaseOrder::create($data);

            // Create items & deduct stock if source is Own Warehouse
            $isOwnWarehouse = ($order->source_type === 'Own Warehouse' || str_contains(strtolower($order->supplier_name), 'warehouse'));

            foreach ($itemsData as $it) {
                $qty = (float) ($it['quantity'] ?? ($it['qty'] ?? 1));
                $rate = (float) ($it['unit_price'] ?? ($it['rate'] ?? 0));
                $lineTotal = (float) ($it['total_price'] ?? ($it['total'] ?? ($qty * $rate)));
                $matName = $it['material_name'] ?? ($it['item_name'] ?? ($it['material'] ?? ''));
                $unit = $it['unit'] ?? 'Nos';
                $materialId = ! empty($it['material_id']) ? (int) $it['material_id'] : null;

                if (! $materialId && ! empty($it['material']) && is_numeric($it['material'])) {
                    $materialId = (int) $it['material'];
                }

                if ($materialId && empty($matName)) {
                    $mat = Material::find($materialId);
                    $matName = $mat?->name_en ?? 'Material';
                } elseif (! $materialId && ! empty($matName)) {
                    $mat = Material::where('name_en', $matName)->first();
                    if (! $mat) {
                        $uModel = Unit::where('name_en', $unit)->orWhere('code', $unit)->first() ?? Unit::where('status', 'Active')->first();
                        $mat = Material::create([
                            'name_en' => $matName,
                            'name_ta' => $it['name_ta'] ?? $matName,
                            'unit_id' => $uModel?->id,
                            'status' => 'Active',
                        ]);
                    }
                    $materialId = $mat?->id;
                }

                if (empty($matName)) {
                    $matName = 'Material';
                }

                $orderItem = PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'material_id' => $materialId,
                    'material_name' => $matName,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'unit_price' => $rate,
                    'total_price' => $lineTotal,
                ]);

                // Auto-link to supplier materials if not already linked
                if ($order->supplier_id && $materialId && ! $isOwnWarehouse) {
                    $supplier = Supplier::find($order->supplier_id);
                    if ($supplier && ! $supplier->materials()->where('materials.id', $materialId)->exists()) {
                        $supplier->materials()->attach($materialId, [
                            'price' => $rate,
                            'grade' => 'Standard',
                        ]);
                    }
                }

                // Auto deduct stock if Own Warehouse
                if ($isOwnWarehouse) {
                    $whItem = WarehouseItem::where('name_en', $matName)->first();
                    if ($whItem) {
                        $whItem->current_stock = max(0, (float) $whItem->current_stock - $qty);
                        $whItem->save();

                        WarehouseMovement::create([
                            'warehouse_item_id' => $whItem->id,
                            'movement_type' => 'Issue to Site',
                            'quantity' => $qty,
                            'cash_amount' => $rate ?: (float) ($whItem->cash_amount ?: $whItem->unit_rate),
                            'project_site_id' => $order->project_site_id,
                            'site_name' => $order->projectSite?->name,
                            'reference_no' => $order->po_number,
                            'movement_date' => $order->order_date,
                            'notes' => "Issued via Purchase/Inward Order {$order->po_number}",
                            'created_by' => $order->created_by,
                        ]);
                    }
                }
            }

            return $order->load(['projectSite', 'supplier', 'items.material']);
        });
    }

    /**
     * Update an existing purchase order with items.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(PurchaseOrder $order, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $itemsData = $data['items'] ?? null;
            unset($data['items']);

            $order->update($data);

            $isOwnWarehouse = ($order->source_type === 'Own Warehouse' || str_contains(strtolower($order->supplier_name), 'warehouse'));

            if (is_array($itemsData)) {
                $order->items()->delete();

                foreach ($itemsData as $it) {
                    $qty = (float) ($it['quantity'] ?? ($it['qty'] ?? 1));
                    $rate = (float) ($it['unit_price'] ?? ($it['rate'] ?? 0));
                    $lineTotal = (float) ($it['total_price'] ?? ($it['total'] ?? ($qty * $rate)));
                    $matName = $it['material_name'] ?? ($it['item_name'] ?? ($it['material'] ?? ''));
                    $unit = $it['unit'] ?? 'Nos';
                    $materialId = ! empty($it['material_id']) ? (int) $it['material_id'] : null;

                    if (! $materialId && ! empty($it['material']) && is_numeric($it['material'])) {
                        $materialId = (int) $it['material'];
                    }

                    if ($materialId && empty($matName)) {
                        $mat = Material::find($materialId);
                        $matName = $mat?->name_en ?? 'Material';
                    } elseif (! $materialId && ! empty($matName)) {
                        $mat = Material::where('name_en', $matName)->first();
                        if (! $mat) {
                            $uModel = Unit::where('name_en', $unit)->orWhere('code', $unit)->first() ?? Unit::where('status', 'Active')->first();
                            $mat = Material::create([
                                'name_en' => $matName,
                                'name_ta' => $it['name_ta'] ?? $matName,
                                'unit_id' => $uModel?->id,
                                'status' => 'Active',
                            ]);
                        }
                        $materialId = $mat?->id;
                    }

                    if (empty($matName)) {
                        $matName = 'Material';
                    }

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $order->id,
                        'material_id' => $materialId,
                        'material_name' => $matName,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'unit_price' => $rate,
                        'total_price' => $lineTotal,
                    ]);

                    // Auto-link to supplier materials if not already linked
                    if ($order->supplier_id && $materialId && ! $isOwnWarehouse) {
                        $supplier = Supplier::find($order->supplier_id);
                        if ($supplier && ! $supplier->materials()->where('materials.id', $materialId)->exists()) {
                            $supplier->materials()->attach($materialId, [
                                'price' => $rate,
                                'grade' => 'Standard',
                            ]);
                        }
                    }

                    // Sync any material usage items referencing this PO
                    if ($materialId) {
                        MaterialUsageItem::where(function ($q) use ($order, $materialId) {
                            $q->where('source_reference', $order->po_number)
                                ->where('material_id', $materialId);
                        })->update([
                            'unit_price' => $rate,
                            'total_price' => DB::raw("used_quantity * {$rate}"),
                        ]);

                        // Sync any stock transfer items referencing this PO
                        StockTransferItem::where(function ($q) use ($order, $materialId) {
                            $q->where('source_reference', $order->po_number)
                                ->where('material_id', $materialId);
                        })->update([
                            'unit_price' => $rate,
                            'total_price' => DB::raw("quantity * {$rate}"),
                        ]);
                    }
                }

                $affectedUsages = MaterialUsage::where('purchase_order_id', $order->id)
                    ->orWhereHas('items', function ($q) use ($order) {
                        $q->where('source_reference', $order->po_number);
                    })->get();
                foreach ($affectedUsages as $u) {
                    $u->total_cost = (float) $u->items()->sum('total_price');
                    $u->save();
                }

                $affectedTransfers = StockTransfer::whereHas('items', function ($q) use ($order) {
                    $q->where('source_reference', $order->po_number);
                })->get();
                foreach ($affectedTransfers as $st) {
                    $st->total_amount = (float) $st->items()->sum('total_price');
                    $st->save();
                }
            }

            return $order->fresh(['projectSite', 'supplier', 'items.material']);
        });
    }

    /**
     * Delete a purchase order.
     */
    public function delete(PurchaseOrder $order): bool
    {
        return (bool) $order->delete();
    }

    /**
     * Compute KPI statistics for purchases.
     *
     * @return array<string, mixed>
     */
    public function getStats(?int $projectSiteId = null): array
    {
        $query = $this->scopeByCreator(PurchaseOrder::query());

        if ($projectSiteId) {
            $query->where('project_site_id', $projectSiteId);
        }

        $orders = $query->with('items')->get();

        $totalValue = (float) $orders->sum('total_amount');
        $totalCount = $orders->count();
        $deliveredCount = $orders->filter(fn ($o) => $o->delivery_status === 'Delivered & Inwarded' || $o->status === 'Order Completed')->count();
        $pendingCount = $orders->filter(fn ($o) => $o->delivery_status === 'Inward Pending' || in_array($o->status, ['Ordered', 'Partial Received'], true))->count();

        $distinctSitesCount = $orders->pluck('project_site_id')->unique()->count();
        $distinctSuppliersCount = $orders->pluck('supplier_name')->unique()->count();

        return [
            'total_value' => $totalValue,
            'total_count' => $totalCount,
            'delivered_count' => $deliveredCount,
            'pending_count' => $pendingCount,
            'distinct_sites_count' => $distinctSitesCount,
            'distinct_suppliers_count' => $distinctSuppliersCount,
        ];
    }
}
