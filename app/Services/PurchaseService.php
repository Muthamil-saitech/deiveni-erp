<?php

namespace App\Services;

use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Services\Contracts\PurchaseServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PurchaseService implements PurchaseServiceInterface
{
    public function __construct(
        public PurchaseRepositoryInterface $purchaseRepo
    ) {}

    /**
     * Get all purchases.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PurchaseOrder>
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->purchaseRepo->all($filters);
    }

    /**
     * Find purchase by ID.
     */
    public function getById(int $id): ?PurchaseOrder
    {
        return $this->purchaseRepo->findById($id);
    }

    /**
     * Find purchase by PO number.
     */
    public function getByPoNumber(string $poNumber): ?PurchaseOrder
    {
        return $this->purchaseRepo->findByPoNumber($poNumber);
    }

    /**
     * Create a purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PurchaseOrder
    {
        $payload = $this->preparePurchaseData($data);

        return $this->purchaseRepo->create($payload);
    }

    /**
     * Update a purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): PurchaseOrder
    {
        $order = $this->purchaseRepo->findById($id);
        if (! $order) {
            throw new \InvalidArgumentException("Purchase order #{$id} not found.");
        }

        $payload = $this->preparePurchaseData($data, $order);

        return $this->purchaseRepo->update($order, $payload);
    }

    /**
     * Delete a purchase order.
     */
    public function delete(int $id): bool
    {
        $order = $this->purchaseRepo->findById($id);
        if (! $order) {
            return false;
        }

        return $this->purchaseRepo->delete($order);
    }

    /**
     * Get KPI statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(?int $projectSiteId = null): array
    {
        return $this->purchaseRepo->getStats($projectSiteId);
    }

    /**
     * Generate the next PO or Bill number.
     */
    public function generateNextPoNumber(string $type = 'PO'): string
    {
        $year = date('Y');
        $prefix = "{$type}-{$year}-";

        $latest = PurchaseOrder::where('po_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextSeq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->po_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = PurchaseOrder::count() + 1;
        }

        while (PurchaseOrder::where('po_number', $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT))->exists()) {
            $nextSeq++;
        }

        return $prefix.str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Format and resolve relations for purchase orders.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function preparePurchaseData(array $data, ?PurchaseOrder $existing = null): array
    {
        // Resolve project site
        $siteId = ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : 0;
        if ($siteId <= 0 && ! empty($data['site_name'])) {
            $site = ProjectSite::where('name', trim((string) $data['site_name']))->first();
            if ($site) {
                $siteId = $site->id;
            }
        }

        // Resolve supplier
        $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
        $supplierName = ! empty($data['supplier_name']) ? trim((string) $data['supplier_name']) : '';

        if ($supplierId && empty($supplierName)) {
            $supp = Supplier::find($supplierId);
            $supplierName = $supp?->name ?? '';
        } elseif (! $supplierId && ! empty($supplierName) && $supplierName !== 'Central Warehouse' && $supplierName !== 'Own Warehouse') {
            $supp = Supplier::where('name', $supplierName)->first();
            if ($supp) {
                $supplierId = $supp->id;
            }
        }

        // Determine source type
        $sourceType = $data['source_type'] ?? 'Third-Party Supplier';
        if (str_contains(strtolower($supplierName), 'warehouse')) {
            $sourceType = 'Own Warehouse';
        }

        $poNumber = ! empty($data['po_number'])
            ? trim((string) $data['po_number'])
            : ($existing?->po_number ?? $this->generateNextPoNumber());

        $orderDate = ! empty($data['order_date'])
            ? Carbon::parse($data['order_date'])->format('Y-m-d')
            : ($existing?->order_date ? Carbon::parse($existing->order_date)->format('Y-m-d') : Carbon::today()->format('Y-m-d'));

        $status = $data['status'] ?? ($existing?->status ?? 'Ordered');
        $deliveryStatus = in_array($status, ['Delivered & Inwarded', 'Order Completed'], true)
            ? 'Delivered & Inwarded'
            : 'Inward Pending';

        $deliveryDate = ! empty($data['delivery_date'])
            ? Carbon::parse($data['delivery_date'])->format('Y-m-d')
            : ($deliveryStatus === 'Delivered & Inwarded' ? $orderDate : null);

        // Normalize items array
        $items = $data['items'] ?? [];
        if (is_string($items)) {
            $decoded = json_decode($items, true);
            $items = is_array($decoded) ? $decoded : [];
        }

        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? ($item['qty'] ?? 1));
            $rate = (float) ($item['unit_price'] ?? ($item['rate'] ?? 0));
            $subtotal += ($qty * $rate);
        }

        $totalAmount = ! empty($data['total_amount']) ? (float) $data['total_amount'] : $subtotal;

        return [
            'po_number' => $poNumber,
            'order_date' => $orderDate,
            'project_site_id' => $siteId,
            'supplier_id' => $supplierId,
            'supplier_name' => $supplierName,
            'source_type' => $sourceType,
            'status' => $status,
            'status_reason' => $data['status_reason'] ?? null,
            'delivery_status' => $deliveryStatus,
            'delivery_date' => $deliveryDate,
            'subtotal' => $subtotal,
            'tax_amount' => (float) ($data['tax_amount'] ?? 0),
            'total_amount' => $totalAmount,
            'notes' => $data['notes'] ?? null,
            'receipt_image' => $data['receipt_image'] ?? $existing?->receipt_image,
            'created_by' => Auth::id() ?? $existing?->created_by,
            'items' => $items,
        ];
    }
}
