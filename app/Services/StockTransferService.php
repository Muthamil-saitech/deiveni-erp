<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialUsageItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SiteMaterialStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\Contracts\StockTransferServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockTransferService implements StockTransferServiceInterface
{
    /**
     * Get paginated or filtered list of stock transfers.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<StockTransfer>
     */
    public function getTransfers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = StockTransfer::query()
            ->with(['fromSite', 'toSite', 'material.unit', 'items.material.unit', 'creator', 'parentTransfer', 'returns.creator'])
            ->latest('transfer_date')
            ->latest('id');

        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('transfer_no', 'like', "%{$s}%")
                    ->orWhere('challan_no', 'like', "%{$s}%")
                    ->orWhere('vehicle_no', 'like', "%{$s}%")
                    ->orWhere('driver_name', 'like', "%{$s}%")
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

        if (! empty($filters['start_date'])) {
            $query->whereDate('transfer_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('transfer_date', '<=', $filters['end_date']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get single transfer by ID.
     */
    public function getTransferById(int $id): ?StockTransfer
    {
        return StockTransfer::with(['fromSite', 'toSite', 'material.unit', 'items.material.unit', 'creator', 'parentTransfer', 'returns.creator'])
            ->find($id);
    }

    /**
     * Record a new stock transfer from Site A to Site B.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $fromSiteId = (int) $data['from_project_site_id'];
            $toSiteId = (int) $data['to_project_site_id'];

            if ($fromSiteId === $toSiteId) {
                throw new InvalidArgumentException('Source site and destination site cannot be the same (வழங்கும் தளம் மற்றும் பெறும் தளம் ஒன்றாக இருக்க முடியாது).');
            }

            // Normalise items list
            $items = [];
            if (! empty($data['items']) && is_array($data['items'])) {
                $items = $data['items'];
            } elseif (! empty($data['material_id']) && ! empty($data['quantity'])) {
                $material = Material::with('unit')->findOrFail((int) $data['material_id']);
                $unitName = $data['unit'] ?? ($material->unit?->name_en ?? $material->unit?->code ?? 'Units');
                $items[] = [
                    'material_id' => (int) $data['material_id'],
                    'material_name' => $material->name_en,
                    'source_type' => $data['source_type'] ?? 'PO',
                    'source_reference' => $data['source_reference'] ?? null,
                    'supplier_name' => $data['supplier_name'] ?? null,
                    'quantity' => (float) $data['quantity'],
                    'unit' => $unitName,
                    'unit_id' => $data['unit_id'] ?? $material->unit_id,
                    'unit_price' => (float) ($data['unit_price'] ?? 0.00),
                    'total_price' => (float) ($data['total_price'] ?? 0.00),
                ];
            }

            if (empty($items)) {
                throw new InvalidArgumentException('Please select at least one material to transfer (குறைந்தது ஒரு பொருளை சேர்க்கவும்).');
            }

            $transferNo = $this->getNextTransferNumber();
            $returnExpected = (bool) ($data['return_expected'] ?? true);
            $returnStatus = $returnExpected ? 'Pending Return' : 'No Return Needed';

            $firstItem = $items[0];
            $firstMatId = ! empty($firstItem['material_id']) ? (int) $firstItem['material_id'] : null;
            $firstUnitId = ! empty($firstItem['unit_id']) ? (int) $firstItem['unit_id'] : null;
            $firstUnit = $firstItem['unit'] ?? 'Units';

            $transfer = StockTransfer::create([
                'transfer_no' => $transferNo,
                'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
                'from_project_site_id' => $fromSiteId,
                'to_project_site_id' => $toSiteId,
                'material_id' => $firstMatId,
                'unit_id' => $firstUnitId,
                'unit' => $firstUnit,
                'quantity' => 0.00,
                'transfer_type' => 'TRANSFER',
                'parent_transfer_id' => null,
                'return_expected' => $returnExpected,
                'return_status' => $returnStatus,
                'returned_quantity' => 0.00,
                'total_amount' => 0.00,
                'total_items_count' => count($items),
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'challan_no' => $data['challan_no'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'Completed',
                'created_by' => $userId,
            ]);

            $totalQty = 0.00;
            $totalAmount = 0.00;

            foreach ($items as $it) {
                $matId = (int) $it['material_id'];
                $qty = (float) $it['quantity'];
                if ($qty <= 0) {
                    throw new InvalidArgumentException('Quantity for all transferred items must be greater than zero.');
                }

                $uPrice = (float) ($it['unit_price'] ?? 0.00);
                $tPrice = (float) ($it['total_price'] ?? ($qty * $uPrice));
                $uName = $it['unit'] ?? 'Units';
                $uId = ! empty($it['unit_id']) ? (int) $it['unit_id'] : null;

                $totalQty += $qty;
                $totalAmount += $tPrice;

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'material_id' => $matId,
                    'material_name' => $it['material_name'] ?? 'Material',
                    'source_type' => $it['source_type'] ?? 'PO',
                    'source_reference' => $it['source_reference'] ?? null,
                    'supplier_name' => $it['supplier_name'] ?? null,
                    'quantity' => $qty,
                    'unit' => $uName,
                    'unit_id' => $uId,
                    'unit_price' => $uPrice,
                    'total_price' => $tPrice,
                    'returned_quantity' => 0.00,
                    'return_status' => $returnStatus,
                ]);

                // Adjust live stock balances
                $this->adjustSiteStock($fromSiteId, $matId, -$qty, $uName, $uId);
                $this->adjustSiteStock($toSiteId, $matId, +$qty, $uName, $uId);
            }

            $transfer->quantity = $totalQty;
            $transfer->total_amount = $totalAmount;
            $transfer->save();

            return $transfer->load(['items.material.unit', 'fromSite', 'toSite']);
        });
    }

    /**
     * Record a return of stock from Site B back to Site A against a parent transfer.
     *
     * @param  array<string, mixed>  $data
     */
    public function createReturn(int $parentTransferId, array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($parentTransferId, $data, $userId) {
            $parent = StockTransfer::lockForUpdate()->findOrFail($parentTransferId);

            if ($parent->transfer_type !== 'TRANSFER') {
                throw new InvalidArgumentException('Returns can only be recorded against an outward transfer.');
            }

            $pending = $parent->pending_return_quantity;
            $quantity = (float) $data['quantity'];

            if ($quantity <= 0) {
                throw new InvalidArgumentException('Return quantity must be greater than zero.');
            }

            if ($quantity > ($pending + 0.0001)) {
                throw new InvalidArgumentException("Return quantity ({$quantity}) exceeds pending quantity ({$pending}).");
            }

            $returnNo = $this->getNextTransferNumber('RET');

            // The return moves stock back from Site B to Site A
            $returnTransfer = StockTransfer::create([
                'transfer_no' => $returnNo,
                'transfer_date' => $data['transfer_date'] ?? now()->toDateString(),
                'from_project_site_id' => $parent->to_project_site_id,
                'to_project_site_id' => $parent->from_project_site_id,
                'material_id' => $parent->material_id,
                'unit_id' => $parent->unit_id,
                'unit' => $parent->unit,
                'quantity' => $quantity,
                'transfer_type' => 'RETURN',
                'parent_transfer_id' => $parent->id,
                'return_expected' => false,
                'return_status' => 'No Return Needed',
                'returned_quantity' => 0.00,
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'challan_no' => $data['challan_no'] ?? null,
                'remarks' => $data['remarks'] ?? ('Returned against '.$parent->transfer_no),
                'status' => 'Completed',
                'created_by' => $userId,
            ]);

            // Update parent transfer's returned quantity & status
            $newReturned = (float) $parent->returned_quantity + $quantity;
            $parent->returned_quantity = $newReturned;
            $parent->return_status = ($newReturned >= ((float) $parent->quantity - 0.0001))
                ? 'Fully Returned'
                : 'Partially Returned';
            $parent->save();

            // Adjust live stock: Site B loses quantity, Site A gains quantity back
            $this->adjustSiteStock($parent->to_project_site_id, $parent->material_id, -$quantity, $parent->unit, $parent->unit_id);
            $this->adjustSiteStock($parent->from_project_site_id, $parent->material_id, +$quantity, $parent->unit, $parent->unit_id);

            return $returnTransfer;
        });
    }

    /**
     * Update an existing stock transfer and adjust site stocks and PO accordingly.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransfer(int $id, array $data, int $userId): StockTransfer
    {
        return DB::transaction(function () use ($id, $data) {
            $transfer = StockTransfer::lockForUpdate()->with('items')->findOrFail($id);

            if (! empty($data['transfer_date'])) {
                $transfer->transfer_date = $data['transfer_date'];
            }
            if (array_key_exists('vehicle_no', $data)) {
                $transfer->vehicle_no = $data['vehicle_no'];
            }
            if (array_key_exists('driver_name', $data)) {
                $transfer->driver_name = $data['driver_name'];
            }
            if (array_key_exists('challan_no', $data)) {
                $transfer->challan_no = $data['challan_no'];
            }
            if (array_key_exists('remarks', $data)) {
                $transfer->remarks = $data['remarks'];
            }
            if (array_key_exists('return_expected', $data)) {
                $transfer->return_expected = (bool) $data['return_expected'];
            }
            $transfer->save();

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new InvalidArgumentException('Please include at least one transferred material.');
            }

            $submittedItemIds = [];
            $existingItemsById = $transfer->items->keyBy('id');

            foreach ($itemsData as $itData) {
                $itemId = ! empty($itData['id']) ? (int) $itData['id'] : null;
                $existingItem = $itemId ? ($existingItemsById[$itemId] ?? null) : null;

                if ($existingItem) {
                    // Case 1: Existing item updated
                    $submittedItemIds[] = $existingItem->id;

                    $oldQty = (float) $existingItem->quantity;
                    $newQty = isset($itData['quantity']) ? (float) $itData['quantity'] : $oldQty;
                    $newUnitPrice = isset($itData['unit_price']) ? (float) $itData['unit_price'] : (float) $existingItem->unit_price;

                    if ($newQty <= 0) {
                        throw new InvalidArgumentException("Quantity for \"{$existingItem->material_name}\" must be greater than zero.");
                    }

                    // Check if already returned more than newQty
                    if ((float) $existingItem->returned_quantity > $newQty + 0.001) {
                        throw new InvalidArgumentException(
                            "Cannot reduce quantity below already returned quantity ({$existingItem->returned_quantity}) for \"{$existingItem->material_name}\"."
                        );
                    }

                    $deltaQty = $newQty - $oldQty;

                    // Adjust live stock balances
                    // From site transferred more => loses delta; transferred less => gains delta back
                    $this->adjustSiteStock($transfer->from_project_site_id, $existingItem->material_id, -$deltaQty, $existingItem->unit, $existingItem->unit_id);
                    // To site receives more => gains delta; receives less => loses delta
                    $this->adjustSiteStock($transfer->to_project_site_id, $existingItem->material_id, +$deltaQty, $existingItem->unit, $existingItem->unit_id);

                    $newLineTotal = round($newQty * $newUnitPrice, 2);

                    $existingItem->quantity = $newQty;
                    $existingItem->unit_price = $newUnitPrice;
                    $existingItem->total_price = $newLineTotal;

                    // Update item return status
                    if ($transfer->return_expected) {
                        $returned = (float) $existingItem->returned_quantity;
                        if ($returned >= $newQty - 0.0001) {
                            $existingItem->return_status = 'Fully Returned';
                        } elseif ($returned > 0) {
                            $existingItem->return_status = 'Partially Returned';
                        } else {
                            $existingItem->return_status = 'Pending Return';
                        }
                    } else {
                        $existingItem->return_status = 'No Return Needed';
                    }

                    $existingItem->save();

                    // Sync with PO if item references a PO
                    $this->syncPoItemPriceAndTotals($existingItem, $newUnitPrice);
                } else {
                    // Case 2: New item added
                    $newQty = (float) ($itData['quantity'] ?? 0);
                    $newUnitPrice = (float) ($itData['unit_price'] ?? 0);
                    $matName = $itData['material_name'] ?? 'Material';
                    $matId = ! empty($itData['material_id']) ? (int) $itData['material_id'] : null;
                    $unit = $itData['unit'] ?? 'Units';
                    $unitId = ! empty($itData['unit_id']) ? (int) $itData['unit_id'] : null;

                    if ($newQty <= 0) {
                        throw new InvalidArgumentException("Quantity for \"{$matName}\" must be greater than zero.");
                    }

                    // Match or create master Material if material_id is missing or invalid
                    if (empty($matId) || $matId <= 0) {
                        $foundMat = Material::where('name_en', $matName)->first();
                        if ($foundMat) {
                            $matId = $foundMat->id;
                        } else {
                            $createdMat = Material::create([
                                'name_en' => $matName,
                                'name_ta' => '',
                                'unit_id' => $unitId,
                                'status' => 'Active',
                            ]);
                            $matId = $createdMat->id;
                        }
                    }

                    $this->adjustSiteStock($transfer->from_project_site_id, $matId, -$newQty, $unit, $unitId);
                    $this->adjustSiteStock($transfer->to_project_site_id, $matId, +$newQty, $unit, $unitId);

                    $newLineTotal = round($newQty * $newUnitPrice, 2);

                    $newItem = StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'material_id' => $matId,
                        'material_name' => $matName,
                        'source_type' => $itData['source_type'] ?? 'Site Stock',
                        'source_reference' => $itData['source_reference'] ?? null,
                        'supplier_name' => $itData['supplier_name'] ?? null,
                        'quantity' => $newQty,
                        'returned_quantity' => 0.0,
                        'unit' => $unit,
                        'unit_id' => $unitId,
                        'unit_price' => $newUnitPrice,
                        'total_price' => $newLineTotal,
                        'return_status' => $transfer->return_expected ? 'Pending Return' : 'No Return Needed',
                    ]);

                    $submittedItemIds[] = $newItem->id;

                    $this->syncPoItemPriceAndTotals($newItem, $newUnitPrice);
                }
            }

            // Case 3: Removed items (were in transfer, but not in submitted list)
            foreach ($transfer->items as $oldItem) {
                if (! in_array($oldItem->id, $submittedItemIds, true)) {
                    if ((float) $oldItem->returned_quantity > 0) {
                        throw new InvalidArgumentException("Cannot remove material \"{$oldItem->material_name}\" because returns have already been recorded against it.");
                    }

                    // Reverse stock: from site gets stock back, to site loses stock
                    $this->adjustSiteStock($transfer->from_project_site_id, $oldItem->material_id, +$oldItem->quantity, $oldItem->unit, $oldItem->unit_id);
                    $this->adjustSiteStock($transfer->to_project_site_id, $oldItem->material_id, -$oldItem->quantity, $oldItem->unit, $oldItem->unit_id);

                    $oldItem->delete();
                }
            }

            // Recompute parent transfer totals
            $transfer->refresh();
            $transfer->quantity = (float) $transfer->items()->sum('quantity');
            $transfer->total_amount = (float) $transfer->items()->sum('total_price');

            // Update parent return status
            if ($transfer->return_expected) {
                $totalRet = (float) $transfer->returned_quantity;
                if ($totalRet >= (float) $transfer->quantity - 0.0001) {
                    $transfer->return_status = 'Fully Returned';
                } elseif ($totalRet > 0) {
                    $transfer->return_status = 'Partially Returned';
                } else {
                    $transfer->return_status = 'Pending Return';
                }
            } else {
                $transfer->return_status = 'No Return Needed';
            }

            $transfer->save();

            return $transfer->fresh(['items.material.unit', 'fromSite', 'toSite', 'creator']);
        });
    }

    /**
     * Synchronize price and totals to linked Purchase Order.
     */
    protected function syncPoItemPriceAndTotals(StockTransferItem $item, float $newUnitPrice): void
    {
        if (! empty($item->source_reference) && str_starts_with($item->source_reference, 'PO-')) {
            $po = PurchaseOrder::where('po_number', $item->source_reference)->first();
            if ($po) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where(function ($q) use ($item) {
                        if ($item->material_id) {
                            $q->where('material_id', $item->material_id);
                        }
                        $q->orWhere('material_name', $item->material_name);
                    })->first();

                if ($poItem) {
                    $poItem->unit_price = $newUnitPrice;
                    $poItem->total_price = round((float) $poItem->quantity * $newUnitPrice, 2);
                    $poItem->save();

                    $poSubtotal = (float) $po->items()->sum('total_price');
                    $po->subtotal = $poSubtotal;
                    $po->total_amount = $poSubtotal + (float) ($po->tax_amount ?? 0);
                    $po->save();
                }
            }
        }
    }

    /**
     * Delete a stock transfer and reverse stock adjustments.
     */
    public function deleteTransfer(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $transfer = StockTransfer::lockForUpdate()->find($id);
            if (! $transfer) {
                return false;
            }

            if ($transfer->transfer_type === 'TRANSFER') {
                // If this transfer has child returns, reject deletion
                if ($transfer->returns()->exists()) {
                    throw new InvalidArgumentException('Cannot delete transfer that already has returns recorded against it. Delete the return entries first.');
                }

                // Revert stock adjustments: Site A gets stock back, Site B loses stock
                if ($transfer->items()->exists()) {
                    foreach ($transfer->items as $it) {
                        $this->adjustSiteStock($transfer->from_project_site_id, $it->material_id, +$it->quantity, $it->unit, $it->unit_id);
                        $this->adjustSiteStock($transfer->to_project_site_id, $it->material_id, -$it->quantity, $it->unit, $it->unit_id);
                    }
                } elseif ($transfer->material_id) {
                    $this->adjustSiteStock($transfer->from_project_site_id, $transfer->material_id, +$transfer->quantity, $transfer->unit, $transfer->unit_id);
                    $this->adjustSiteStock($transfer->to_project_site_id, $transfer->material_id, -$transfer->quantity, $transfer->unit, $transfer->unit_id);
                }
            } else {
                // This is a RETURN entry
                if ($transfer->parent_transfer_id) {
                    $parent = StockTransfer::lockForUpdate()->find($transfer->parent_transfer_id);
                    if ($parent) {
                        $parent->returned_quantity = max(0.0, (float) $parent->returned_quantity - (float) $transfer->quantity);
                        if ($parent->returned_quantity <= 0.0001) {
                            $parent->return_status = 'Pending Return';
                        } else {
                            $parent->return_status = 'Partially Returned';
                        }
                        $parent->save();
                    }
                }

                // Revert stock adjustments: Site B gets stock back, Site A loses stock
                $this->adjustSiteStock($transfer->from_project_site_id, $transfer->material_id, +$transfer->quantity, $transfer->unit, $transfer->unit_id);
                $this->adjustSiteStock($transfer->to_project_site_id, $transfer->material_id, -$transfer->quantity, $transfer->unit, $transfer->unit_id);
            }

            return (bool) $transfer->delete();
        });
    }

    /**
     * Adjust live site material stock balance.
     */
    protected function adjustSiteStock(int $siteId, int $materialId, float $delta, ?string $unit = null, ?int $unitId = null): SiteMaterialStock
    {
        $stock = SiteMaterialStock::firstOrNew([
            'project_site_id' => $siteId,
            'material_id' => $materialId,
        ]);

        $stock->current_stock = (float) $stock->current_stock + $delta;
        if ($unit) {
            $stock->unit = $unit;
        }
        if ($unitId) {
            $stock->unit_id = $unitId;
        }
        $stock->last_updated_at = now();
        $stock->save();

        return $stock;
    }

    /**
     * Get available materials and their stocks/sources (Material Usage / Warehouse / Transfers) for a specific site.
     * Only materials that have been recorded in Material Usage for this site (or received via transfer)
     * are eligible to be transferred to another site.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableMaterialsForSite(int $siteId): array
    {
        $siteId = (int) $siteId;
        if ($siteId <= 0) {
            return [];
        }

        $items = [];

        // 1. Fetch materials that have been recorded in Material Usage for this site
        $usageItems = MaterialUsageItem::whereHas('materialUsage', function ($q) use ($siteId) {
            $q->where('project_site_id', $siteId)
                ->where('status', '!=', 'Cancelled');
        })
            ->with(['material.unit', 'purchaseOrderItem.purchaseOrder', 'materialUsage'])
            ->get();

        if ($usageItems->isNotEmpty()) {
            foreach ($usageItems as $uItem) {
                $matId = (int) $uItem->material_id;
                if ($matId <= 0) {
                    continue;
                }

                // If source is Site Transfer, it is handled in Step 2 with usage deduction
                if ($uItem->source_type === 'Site Transfer') {
                    continue;
                }

                $isWarehouse = ($uItem->source_type === 'Own Warehouse'
                    || $uItem->source_type === 'Warehouse'
                    || $uItem->purchaseOrderItem?->purchaseOrder?->source_type === 'Own Warehouse'
                    || $uItem->purchaseOrderItem?->purchaseOrder?->source_type === 'Warehouse');

                $sourceType = $isWarehouse ? 'Own Warehouse' : 'PO';
                $sourceRef = $uItem->source_reference ?: ($uItem->purchaseOrderItem?->purchaseOrder?->po_number ?? ($isWarehouse ? 'Own Warehouse' : 'PO'));
                $supplierName = $isWarehouse ? 'Central Warehouse' : ($uItem->purchaseOrderItem?->purchaseOrder?->supplier_name ?? null);

                $sourceLabel = $isWarehouse
                    ? 'Own Warehouse (மைய கிடங்கு இருப்பு)'
                    : "Supplier PO: {$sourceRef}".($supplierName ? " ({$supplierName})" : '');

                $key = "USAGE_{$sourceType}_{$sourceRef}_{$matId}";
                $unitPrice = (float) $uItem->unit_price;

                $inward = (float) ($uItem->purchaseOrderItem?->quantity ?? $uItem->inward_quantity);
                $used = (float) $uItem->used_quantity;
                $rem = (float) $uItem->remaining_quantity;

                if ($inward <= 0) {
                    $inward = ($rem > 0) ? ($rem + $used) : $used;
                }

                if (! isset($items[$key])) {
                    $items[$key] = [
                        'key' => $key,
                        'material_id' => $matId,
                        'material_name' => $uItem->material_name ?: ($uItem->material?->name_en ?? 'Material'),
                        'material_name_ta' => $uItem->material?->name_ta ?? '',
                        'source_type' => $sourceType,
                        'source_label' => $sourceLabel,
                        'reference_no' => $sourceRef,
                        'supplier_name' => $supplierName,
                        'unit' => $uItem->unit ?: ($uItem->material?->unit?->name_en ?? 'Units'),
                        'unit_id' => $uItem->unit_id ?: $uItem->material?->unit_id,
                        'unit_price' => $unitPrice,
                        'inward_qty' => 0.0,
                        'available_qty' => 0.0,
                        '_total_used' => 0.0,
                        '_latest_remaining' => null,
                    ];
                }

                $items[$key]['inward_qty'] = max($items[$key]['inward_qty'], round($inward, 2));
                $items[$key]['_total_used'] += $used;
                if ($rem > 0) {
                    $items[$key]['_latest_remaining'] = $rem;
                }
                if ($items[$key]['unit_price'] <= 0 && $unitPrice > 0) {
                    $items[$key]['unit_price'] = $unitPrice;
                }
            }

            // Calculate remaining balance stock available for transfer
            foreach ($items as &$it) {
                if (isset($it['_total_used'])) {
                    if ($it['_latest_remaining'] !== null && $it['_latest_remaining'] > 0) {
                        $it['available_qty'] = (float) $it['_latest_remaining'];
                    } elseif ($it['inward_qty'] > $it['_total_used']) {
                        $it['available_qty'] = round($it['inward_qty'] - $it['_total_used'], 2);
                    } else {
                        $it['available_qty'] = 0.0;
                    }
                    unset($it['_total_used'], $it['_latest_remaining']);
                }
            }
            unset($it);
        }

        // Also check Purchase Orders directly ordered for this site that haven't had usage recorded yet
        $sitePoItems = PurchaseOrderItem::whereHas('purchaseOrder', function ($q) use ($siteId) {
            $q->where('project_site_id', $siteId)
                ->where('status', '!=', 'Cancelled');
        })->with(['purchaseOrder', 'material.unit'])->get();

        foreach ($sitePoItems as $poItem) {
            $matId = (int) $poItem->material_id;
            if ($matId <= 0) {
                continue;
            }
            $poNum = $poItem->purchaseOrder?->po_number ?? 'PO';
            $key = "USAGE_PO_{$poNum}_{$matId}";

            if (isset($items[$key])) {
                continue;
            }

            $inwardQty = (float) $poItem->quantity;
            if ($inwardQty <= 0) {
                continue;
            }

            $supplierName = $poItem->purchaseOrder?->supplier_name;
            $items[$key] = [
                'key' => $key,
                'material_id' => $matId,
                'material_name' => $poItem->material_name ?: ($poItem->material?->name_en ?? 'Material'),
                'material_name_ta' => $poItem->material?->name_ta ?? '',
                'source_type' => 'PO',
                'source_label' => "Supplier PO: {$poNum}".($supplierName ? " ({$supplierName})" : ''),
                'reference_no' => $poNum,
                'supplier_name' => $supplierName,
                'unit' => $poItem->unit ?: ($poItem->material?->unit?->name_en ?? 'Units'),
                'unit_id' => $poItem->unit_id ?: $poItem->material?->unit_id,
                'unit_price' => (float) $poItem->unit_price,
                'inward_qty' => $inwardQty,
                'available_qty' => $inwardQty,
            ];
        }

        // 2. Fetch materials inwarded via inter-site stock transfers received at this site
        $transfersIn = StockTransferItem::whereHas('stockTransfer', function ($q) use ($siteId) {
            $q->where('to_project_site_id', $siteId)
                ->where('transfer_type', 'TRANSFER')
                ->where('status', '!=', 'Cancelled');
        })->with(['material.unit', 'stockTransfer.fromSite'])->get();

        foreach ($transfersIn as $trIn) {
            $matId = (int) $trIn->material_id;
            if ($matId <= 0) {
                continue;
            }
            $netRecv = max(0, (float) $trIn->quantity - (float) $trIn->returned_quantity);
            if ($netRecv <= 0) {
                continue;
            }

            $fromSiteName = $trIn->stockTransfer?->fromSite?->name ?? 'Site';
            $transferNo = $trIn->stockTransfer?->transfer_no ?? 'TRANSFER';

            // Deduct any quantity consumed in Material Usage at this site from this transfer
            $usedAtSite = (float) MaterialUsageItem::whereHas('materialUsage', function ($q) use ($siteId) {
                $q->where('project_site_id', $siteId)
                    ->where('status', '!=', 'Cancelled');
            })
                ->where('material_id', $matId)
                ->where('source_type', 'Site Transfer')
                ->where('source_reference', $transferNo)
                ->sum('used_quantity');

            $availRecv = max(0.0, round($netRecv - $usedAtSite, 2));
            if ($availRecv <= 0) {
                continue;
            }

            $key = "TR_IN_{$trIn->id}_{$matId}";

            $items[$key] = [
                'key' => $key,
                'material_id' => $matId,
                'material_name' => $trIn->material_name ?: ($trIn->material?->name_en ?? 'Material'),
                'material_name_ta' => $trIn->material?->name_ta ?? '',
                'source_type' => $trIn->source_type ?: 'Site Transfer',
                'source_label' => "From {$fromSiteName} ({$transferNo})",
                'reference_no' => $transferNo,
                'supplier_name' => $fromSiteName,
                'unit' => $trIn->unit ?: ($trIn->material?->unit?->name_en ?? 'Units'),
                'unit_id' => $trIn->unit_id ?: $trIn->material?->unit_id,
                'unit_price' => (float) $trIn->unit_price,
                'inward_qty' => $netRecv,
                'available_qty' => $availRecv,
            ];
        }

        // 3. Fallback: If no Material Usage or inward transfers exist yet (e.g. initial stock tests),
        // include items from site_material_stocks that have positive balance
        if (empty($items)) {
            $stocks = SiteMaterialStock::where('project_site_id', $siteId)
                ->where('current_stock', '>', 0)
                ->with(['material.unit'])
                ->get();

            foreach ($stocks as $st) {
                if (! $st->material) {
                    continue;
                }
                $matId = (int) $st->material_id;
                $key = "STOCK_{$matId}";
                $qty = (float) $st->current_stock;
                $items[$key] = [
                    'key' => $key,
                    'material_id' => $matId,
                    'material_name' => $st->material->name_en,
                    'material_name_ta' => $st->material->name_ta ?? '',
                    'source_type' => 'Site Stock',
                    'source_label' => 'On-Site Stock (தள இருப்பு)',
                    'reference_no' => 'STOCK-LEDGER',
                    'supplier_name' => 'Site Store',
                    'unit' => $st->unit ?: ($st->material->unit?->name_en ?? 'Units'),
                    'unit_id' => $st->unit_id ?? $st->material->unit_id,
                    'unit_price' => 0.00,
                    'inward_qty' => $qty,
                    'available_qty' => $qty,
                ];
            }
        }

        // 4. Deduct already transferred OUT quantities from this site
        $outwardItems = StockTransferItem::whereHas('stockTransfer', function ($q) use ($siteId) {
            $q->where('from_project_site_id', $siteId)
                ->where('transfer_type', 'TRANSFER')
                ->where('status', '!=', 'Cancelled');
        })->get();

        foreach ($outwardItems as $out) {
            $netOutQty = max(0, (float) $out->quantity - (float) $out->returned_quantity);
            if ($netOutQty <= 0) {
                continue;
            }

            $matId = (int) $out->material_id;
            $sourceRef = $out->source_reference;

            // 1st pass: match exact material and reference
            foreach ($items as $k => &$it) {
                if ($it['material_id'] === $matId && $it['reference_no'] === $sourceRef && $it['available_qty'] > 0) {
                    $deduct = min($it['available_qty'], $netOutQty);
                    $it['available_qty'] = max(0, round($it['available_qty'] - $deduct, 2));
                    $netOutQty -= $deduct;
                    if ($netOutQty <= 0) {
                        break;
                    }
                }
            }
            unset($it);

            // 2nd pass: fallback match by material_id if reference was different or empty
            if ($netOutQty > 0) {
                foreach ($items as $k => &$it) {
                    if ($it['material_id'] === $matId && $it['available_qty'] > 0) {
                        $deduct = min($it['available_qty'], $netOutQty);
                        $it['available_qty'] = max(0, round($it['available_qty'] - $deduct, 2));
                        $netOutQty -= $deduct;
                        if ($netOutQty <= 0) {
                            break;
                        }
                    }
                }
                unset($it);
            }
        }

        // Filter only items with available_qty > 0
        $available = array_filter($items, fn ($it) => (float) $it['available_qty'] > 0);

        return array_values($available);
    }

    /**
     * Get current stock balance for a material at a specific site.
     */
    public function getSiteMaterialStock(int $siteId, int $materialId): float
    {
        $stock = SiteMaterialStock::where('project_site_id', $siteId)
            ->where('material_id', $materialId)
            ->first();

        return $stock ? (float) $stock->current_stock : 0.0;
    }

    /**
     * Get all material stock balances for a site.
     *
     * @return Collection<int, SiteMaterialStock>
     */
    public function getSiteStocks(int $siteId): Collection
    {
        return SiteMaterialStock::with('material.unit')
            ->where('project_site_id', $siteId)
            ->get();
    }

    /**
     * Generate sequential next transfer number.
     */
    public function getNextTransferNumber(string $prefix = 'ST'): string
    {
        $year = date('Y');
        $fullPrefix = "{$prefix}-{$year}-";

        $last = StockTransfer::where('transfer_no', 'like', "{$fullPrefix}%")
            ->orderByDesc('id')
            ->value('transfer_no');

        if ($last) {
            $lastNum = (int) substr($last, strlen($fullPrefix));
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return $fullPrefix.str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get high-level KPI stats for dashboard / list page.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStats(array $filters = []): array
    {
        $query = StockTransfer::query();

        if (! empty($filters['site_id'])) {
            $siteId = (int) $filters['site_id'];
            $query->where(function ($q) use ($siteId) {
                $q->where('from_project_site_id', $siteId)
                    ->orWhere('to_project_site_id', $siteId);
            });
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('transfer_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('transfer_date', '<=', $filters['end_date']);
        }

        $allTransfers = (clone $query)->where('transfer_type', 'TRANSFER')->get();
        $allReturns = (clone $query)->where('transfer_type', 'RETURN')->get();

        $totalTransfersCount = $allTransfers->count();
        $totalTransferredQty = $allTransfers->sum('quantity');
        $totalReturnsCount = $allReturns->count();
        $totalReturnedQty = $allReturns->sum('quantity');

        $pendingTransfers = $allTransfers->whereIn('return_status', ['Pending Return', 'Partially Returned']);
        $pendingReturnsCount = $pendingTransfers->count();
        $pendingReturnsQty = $pendingTransfers->sum(fn ($t) => $t->pending_return_quantity);

        $fullyReturnedCount = $allTransfers->where('return_status', 'Fully Returned')->count();

        return [
            'total_transfers_count' => $totalTransfersCount,
            'total_transferred_qty' => (float) $totalTransferredQty,
            'total_returns_count' => $totalReturnsCount,
            'total_returned_qty' => (float) $totalReturnedQty,
            'pending_returns_count' => $pendingReturnsCount,
            'pending_returns_qty' => (float) $pendingReturnsQty,
            'fully_returned_count' => $fullyReturnedCount,
        ];
    }
}
