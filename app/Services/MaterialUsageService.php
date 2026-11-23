<?php

namespace App\Services;

use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MaterialUsageItem;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SiteMaterialStock;
use App\Models\StockTransfer;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Services\Contracts\MaterialUsageServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MaterialUsageService implements MaterialUsageServiceInterface
{
    /**
     * Get paginated list of material usages with optional filters.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<MaterialUsage>
     */
    public function getUsages(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->accessibleUsageQuery()
            ->with(['purchaseOrder', 'projectSite', 'creator', 'items.material.unit'])
            ->latest('usage_date')
            ->latest('id');

        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('usage_no', 'like', "%{$s}%")
                    ->orWhere('remarks', 'like', "%{$s}%")
                    ->orWhereHas('purchaseOrder', function ($pq) use ($s) {
                        $pq->where('po_number', 'like', "%{$s}%");
                    })
                    ->orWhereHas('projectSite', function ($sq) use ($s) {
                        $sq->where('name', 'like', "%{$s}%");
                    })
                    ->orWhereHas('items', function ($iq) use ($s) {
                        $iq->where('material_name', 'like', "%{$s}%")
                            ->orWhere('source_reference', 'like', "%{$s}%");
                    });
            });
        }

        if (! empty($filters['site_id'])) {
            $query->where('project_site_id', (int) $filters['site_id']);
        }

        if (! empty($filters['po_id'])) {
            $query->where('purchase_order_id', (int) $filters['po_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->whereDate('usage_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('usage_date', '<=', $filters['end_date']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get a single material usage by ID (with items loaded).
     */
    public function getUsageById(int $id): ?MaterialUsage
    {
        return $this->accessibleUsageQuery()
            ->with(['purchaseOrder.projectSite', 'projectSite', 'creator', 'items.material.unit'])
            ->find($id);
    }

    /**
     * Create a new material usage record and update stock reductions.
     * Supports both:
     *  1. PO-based materials (Third-party supplier orders)
     *  2. Direct Own Warehouse materials (Central Warehouse stock issued to site)
     *
     * @param  array<string, mixed>  $data
     */
    public function createUsage(array $data, int $userId): MaterialUsage
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = $data['items'] ?? [];

            if (empty($items)) {
                throw new InvalidArgumentException('At least one material item is required (குறைந்தது ஒரு பொருள் தேவை).');
            }

            // Resolve project_site_id from the PO or payload
            $poId = ! empty($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null;
            $po = $poId ? PurchaseOrder::with('items')->find($poId) : null;
            $projectSiteId = ! empty($data['project_site_id']) ? (int) $data['project_site_id'] : ($po?->project_site_id ?? 0);

            if ($projectSiteId <= 0) {
                throw new InvalidArgumentException('Please select a Project Site (தளத்தை தேர்வு செய்யவும்).');
            }

            $usageDate = ! empty($data['usage_date']) ? Carbon::parse($data['usage_date'])->format('Y-m-d') : now()->toDateString();
            if ($po && $po->order_date && $po->order_date->format('Y-m-d') > $usageDate) {
                throw new InvalidArgumentException(
                    "Purchase Order {$po->po_number} was ordered on {$po->order_date->format('d-m-Y')}, which is after the Usage Date (".Carbon::parse($usageDate)->format('d-m-Y').'). Materials cannot be used before their purchase date.'
                );
            }

            $this->ensureSiteManagerOwnsSite($projectSiteId);
            $site = ProjectSite::findOrFail($projectSiteId);
            $usageNo = $this->getNextUsageNumber();

            $totalUsedQty = 0.0;
            $totalCost = 0.0;
            $preparedItems = [];
            $warehouseMovementsToRecord = [];

            foreach ($items as $item) {
                $usedQty = (float) ($item['used_quantity'] ?? 0);

                if ($usedQty <= 0) {
                    continue;
                }

                $isWarehouseItem = ($item['source_type'] ?? '') === 'Own Warehouse'
                    || ! empty($item['warehouse_item_id'])
                    || (($data['material_source'] ?? '') === 'own_warehouse');

                $isTransferItem = ($item['source_type'] ?? '') === 'Stock Transfer'
                    || (($data['material_source'] ?? '') === 'stock_transfer')
                    || str_starts_with((string) ($item['source_reference'] ?? ''), 'XFR');

                if ($isWarehouseItem) {
                    // Scenario 2: Own Warehouse stock (without PO)
                    $whItemId = ! empty($item['warehouse_item_id']) ? (int) $item['warehouse_item_id'] : null;
                    $whItem = $whItemId ? WarehouseItem::find($whItemId) : null;
                    if (! $whItem && ! empty($item['material_name'])) {
                        $whItem = WarehouseItem::where('name_en', $item['material_name'])
                            ->orWhere('sku', $item['source_reference'] ?? '')
                            ->first();
                    }

                    $materialName = (string) ($item['material_name'] ?: ($whItem?->name_en ?? 'Warehouse Material'));
                    $inwardQty = $whItem ? (float) $whItem->current_stock : (float) ($item['inward_quantity'] ?? $usedQty);
                    $unitPrice = (float) ($item['unit_price'] ?? ($whItem?->cash_amount ?: ($whItem?->unit_rate ?? 0)));
                    $unitName = (string) ($item['unit'] ?? ($whItem?->unit ?? 'Units'));
                    $unitId = ! empty($item['unit_id']) ? (int) $item['unit_id'] : ($whItem?->unit_id ?? null);
                    $sourceRef = (string) ($item['source_reference'] ?? ($whItem?->sku ?? 'Own Warehouse'));
                    $sourceType = 'Own Warehouse';

                    if ($whItem && $usedQty > ((float) $whItem->current_stock + 0.001)) {
                        throw new InvalidArgumentException(
                            "Used quantity ({$usedQty}) for \"{$materialName}\" exceeds available warehouse stock ({$whItem->current_stock})."
                        );
                    }

                    // Match or create master Material
                    $matId = ! empty($item['material_id']) ? (int) $item['material_id'] : 0;
                    if ($matId <= 0) {
                        $foundMat = Material::where('name_en', $materialName)->orWhere('name_ta', $whItem?->name_ta)->first();
                        if ($foundMat) {
                            $matId = $foundMat->id;
                        } else {
                            $createdMat = Material::create([
                                'name_en' => $materialName,
                                'name_ta' => $whItem?->name_ta ?? '',
                                'unit_id' => $unitId,
                                'status' => 'Active',
                            ]);
                            $matId = $createdMat->id;
                        }
                    }
                    $materialId = $matId;

                    $remainingQty = max(0, $inwardQty - $usedQty);
                    $totalPrice = round($usedQty * $unitPrice, 2);

                    $totalUsedQty += $usedQty;
                    $totalCost += $totalPrice;

                    $preparedItems[] = [
                        'purchase_order_item_id' => null,
                        'material_id' => $materialId,
                        'material_name' => $materialName,
                        'source_type' => $sourceType,
                        'source_reference' => $sourceRef,
                        'inward_quantity' => $inwardQty,
                        'used_quantity' => $usedQty,
                        'remaining_quantity' => $remainingQty,
                        'unit' => $unitName,
                        'unit_id' => $unitId ? (int) $unitId : null,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ];

                    // Deduct stock from Central Warehouse
                    if ($whItem) {
                        $whItem->current_stock = max(0, (float) $whItem->current_stock - $usedQty);
                        $whItem->save();

                        $warehouseMovementsToRecord[] = [
                            'warehouse_item_id' => $whItem->id,
                            'movement_type' => 'Issue to Site',
                            'quantity' => $usedQty,
                            'cash_amount' => $unitPrice,
                            'project_site_id' => $projectSiteId,
                            'site_name' => $site->name,
                            'reference_no' => $usageNo,
                            'movement_date' => $data['usage_date'] ?? now()->toDateString(),
                            'notes' => "Issued for Material Usage {$usageNo} at {$site->name}",
                            'created_by' => $userId,
                        ];
                    }
                } elseif ($isTransferItem) {
                    // Scenario 3: Stock Transferred from another site
                    $materialName = (string) ($item['material_name'] ?? 'Transferred Material');
                    $materialId = (int) ($item['material_id'] ?? 0);
                    $sourceRef = (string) ($item['source_reference'] ?? ($item['po_number'] ?? 'Stock Transfer'));
                    $sourceType = 'Stock Transfer';
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    $unitName = (string) ($item['unit'] ?? 'Units');
                    $unitId = ! empty($item['unit_id']) ? (int) $item['unit_id'] : null;
                    $inwardQty = (float) ($item['inward_quantity'] ?? $usedQty);

                    // Match or find material if id missing
                    if ($materialId <= 0 && $materialName) {
                        $foundMat = Material::where('name_en', $materialName)->first();
                        if ($foundMat) {
                            $materialId = $foundMat->id;
                        } else {
                            $createdMat = Material::create([
                                'name_en' => $materialName,
                                'unit_id' => $unitId,
                                'status' => 'Active',
                            ]);
                            $materialId = $createdMat->id;
                        }
                    }

                    // Deduct already-used quantity from previous usages for this transfer reference and material
                    $xferId = ! empty($item['stock_transfer_id']) ? (int) $item['stock_transfer_id'] : null;
                    $xferObj = $xferId ? StockTransfer::find($xferId) : null;
                    if (! $xferObj && ! empty($sourceRef)) {
                        $xferObj = StockTransfer::where('transfer_no', $sourceRef)->first();
                    }
                    if ($xferObj && $xferObj->transfer_date && $xferObj->transfer_date->format('Y-m-d') > $usageDate) {
                        throw new InvalidArgumentException(
                            "Stock Transfer {$xferObj->transfer_no} was transferred on {$xferObj->transfer_date->format('d-m-Y')}, which is after the Usage Date (".Carbon::parse($usageDate)->format('d-m-Y').'). Materials cannot be used before their transfer date.'
                        );
                    }

                    $alreadyUsed = (float) MaterialUsageItem::whereHas('materialUsage', function ($q) use ($projectSiteId) {
                        $q->where('project_site_id', $projectSiteId)->where('status', '!=', 'Cancelled');
                    })
                        ->where('source_type', 'Stock Transfer')
                        ->where('source_reference', $sourceRef)
                        ->where('material_id', $materialId)
                        ->sum('used_quantity');

                    $availableQty = max(0, $inwardQty - $alreadyUsed);

                    // Also check live site stock
                    $siteStock = SiteMaterialStock::where('project_site_id', $projectSiteId)
                        ->where('material_id', $materialId)
                        ->first();

                    if ($availableQty > 0 && $usedQty > ($availableQty + 0.001)) {
                        throw new InvalidArgumentException(
                            "Used quantity ({$usedQty}) for \"{$materialName}\" exceeds available transferred stock ({$availableQty})."
                        );
                    }

                    if ($siteStock && $usedQty > ((float) $siteStock->current_stock + 0.001)) {
                        throw new InvalidArgumentException(
                            "Used quantity ({$usedQty}) for \"{$materialName}\" exceeds available site balance stock ({$siteStock->current_stock})."
                        );
                    }

                    $remainingQty = max(0, $inwardQty - $alreadyUsed - $usedQty);
                    $totalPrice = round($usedQty * $unitPrice, 2);

                    $totalUsedQty += $usedQty;
                    $totalCost += $totalPrice;

                    $preparedItems[] = [
                        'purchase_order_item_id' => null,
                        'material_id' => $materialId,
                        'material_name' => $materialName,
                        'source_type' => $sourceType,
                        'source_reference' => $sourceRef,
                        'inward_quantity' => $inwardQty,
                        'used_quantity' => $usedQty,
                        'remaining_quantity' => $remainingQty,
                        'unit' => $unitName,
                        'unit_id' => $unitId ? (int) $unitId : null,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ];
                } else {
                    // Scenario 1: PO-based materials (Third-party supplier)
                    $poItemId = ! empty($item['purchase_order_item_id']) ? (int) $item['purchase_order_item_id'] : null;
                    $poItem = $poItemId ? PurchaseOrderItem::with('purchaseOrder')->find($poItemId) : null;

                    $itemPo = $poItem ? $poItem->purchaseOrder : $po;
                    if ($itemPo && $itemPo->order_date && $itemPo->order_date->format('Y-m-d') > $usageDate) {
                        throw new InvalidArgumentException(
                            "Purchase Order {$itemPo->po_number} was ordered on {$itemPo->order_date->format('d-m-Y')}, which is after the Usage Date (".Carbon::parse($usageDate)->format('d-m-Y').'). Materials cannot be used before their purchase date.'
                        );
                    }
                    $inwardQty = $poItem ? (float) $poItem->quantity : (float) ($item['inward_quantity'] ?? $usedQty);
                    $unitPrice = $poItem ? (float) $poItem->unit_price : (float) ($item['unit_price'] ?? 0);
                    $unitName = $poItem ? (string) $poItem->unit : (string) ($item['unit'] ?? '');
                    $unitId = $poItem ? ($poItem->unit_id ?? null) : ($item['unit_id'] ?? null);
                    $materialName = $poItem ? (string) $poItem->material_name : (string) ($item['material_name'] ?? '');
                    $materialId = $poItem ? (int) $poItem->material_id : (int) ($item['material_id'] ?? 0);

                    // Deduct already-used quantity from previous usages for the same PO item
                    $alreadyUsed = 0.0;
                    if ($poItemId) {
                        $alreadyUsed = (float) MaterialUsageItem::where('purchase_order_item_id', $poItemId)
                            ->sum('used_quantity');
                    }

                    $availableQty = $inwardQty - $alreadyUsed;

                    if ($usedQty > $availableQty + 0.001) {
                        throw new InvalidArgumentException(
                            "Used quantity ({$usedQty}) for \"{$materialName}\" exceeds available quantity ({$availableQty})."
                        );
                    }

                    $remainingQty = max(0, $inwardQty - $alreadyUsed - $usedQty);
                    $totalPrice = round($usedQty * $unitPrice, 2);

                    $totalUsedQty += $usedQty;
                    $totalCost += $totalPrice;

                    $sourceRef = $itemPo?->po_number ?: ($item['source_reference'] ?? ($item['po_number'] ?? ''));
                    $sourceType = $itemPo?->source_type ?: ($item['source_type'] ?? 'Third-Party Supplier');

                    $preparedItems[] = [
                        'purchase_order_item_id' => $poItemId,
                        'material_id' => $materialId,
                        'material_name' => $materialName,
                        'source_type' => $sourceType,
                        'source_reference' => $sourceRef,
                        'inward_quantity' => $inwardQty,
                        'used_quantity' => $usedQty,
                        'remaining_quantity' => $remainingQty,
                        'unit' => $unitName,
                        'unit_id' => $unitId ? (int) $unitId : null,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ];
                }

                // Reduce stock on SiteMaterialStock if record exists
                $siteStock = SiteMaterialStock::where('project_site_id', $projectSiteId)
                    ->where('material_id', $materialId)
                    ->first();

                if ($siteStock) {
                    $siteStock->current_stock = max(0, (float) $siteStock->current_stock - $usedQty);
                    $siteStock->last_updated_at = now();
                    $siteStock->save();
                }
            }

            if (empty($preparedItems)) {
                throw new InvalidArgumentException('Please enter used quantity greater than 0 for at least one material.');
            }

            $primaryPoId = $poId ?: (! empty($preparedItems[0]['purchase_order_item_id']) ? PurchaseOrderItem::where('id', $preparedItems[0]['purchase_order_item_id'])->value('purchase_order_id') : null);

            $usage = MaterialUsage::create([
                'usage_no' => $usageNo,
                'usage_date' => $data['usage_date'],
                'purchase_order_id' => $primaryPoId,
                'project_site_id' => $projectSiteId,
                'work_stage' => null,
                'supervisor_name' => null,
                'total_items_count' => count($preparedItems),
                'total_used_quantity' => $totalUsedQty,
                'total_cost' => $totalCost,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'Recorded',
                'created_by' => $userId,
            ]);

            foreach ($preparedItems as $itemData) {
                MaterialUsageItem::create(array_merge($itemData, ['material_usage_id' => $usage->id]));
            }

            foreach ($warehouseMovementsToRecord as $movData) {
                WarehouseMovement::create($movData);
            }

            return $usage->load(['purchaseOrder', 'projectSite', 'creator', 'items']);
        });
    }

    /**
     * Update an existing material usage record and adjust stocks and PO accordingly.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUsage(int $id, array $data, int $userId): MaterialUsage
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $usage = $this->accessibleUsageQuery()
                ->with(['items', 'purchaseOrder', 'projectSite'])
                ->findOrFail($id);

            if (! empty($data['usage_date'])) {
                $usage->usage_date = $data['usage_date'];
            }
            if (array_key_exists('remarks', $data)) {
                $usage->remarks = $data['remarks'];
            }
            $usage->save();

            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new InvalidArgumentException('Please include at least one material.');
            }

            $submittedItemIds = [];
            $existingItemsById = $usage->items->keyBy('id');

            foreach ($itemsData as $itData) {
                $itemId = ! empty($itData['id']) ? (int) $itData['id'] : null;
                $existingItem = $itemId ? ($existingItemsById[$itemId] ?? null) : null;

                if ($existingItem) {
                    // Case 1: Existing item modified
                    $submittedItemIds[] = $existingItem->id;

                    $oldUsedQty = (float) $existingItem->used_quantity;
                    $newUsedQty = isset($itData['used_quantity']) ? (float) $itData['used_quantity'] : $oldUsedQty;
                    $newUnitPrice = isset($itData['unit_price']) ? (float) $itData['unit_price'] : (float) $existingItem->unit_price;

                    if ($newUsedQty <= 0) {
                        throw new InvalidArgumentException("Used quantity for \"{$existingItem->material_name}\" must be greater than zero.");
                    }

                    $deltaQty = $newUsedQty - $oldUsedQty;

                    // 1. Stock adjustment on Central Warehouse if Own Warehouse
                    if ($existingItem->source_type === 'Own Warehouse') {
                        $whItem = WarehouseItem::where('sku', $existingItem->source_reference)
                            ->orWhere('name_en', $existingItem->material_name)
                            ->first();

                        if ($whItem) {
                            if ($deltaQty > 0 && ((float) $whItem->current_stock < $deltaQty - 0.001)) {
                                throw new InvalidArgumentException(
                                    "Additional quantity ({$deltaQty}) for \"{$existingItem->material_name}\" exceeds available warehouse stock ({$whItem->current_stock})."
                                );
                            }
                            $whItem->current_stock = max(0, (float) $whItem->current_stock - $deltaQty);
                            $whItem->save();

                            $mov = WarehouseMovement::where('reference_no', $usage->usage_no)
                                ->where('warehouse_item_id', $whItem->id)
                                ->first();
                            if ($mov) {
                                $mov->quantity = $newUsedQty;
                                $mov->cash_amount = $newUnitPrice;
                                $mov->save();
                            }
                        }
                    }

                    // 2. Stock adjustment on SiteMaterialStock
                    $siteStock = SiteMaterialStock::where('project_site_id', $usage->project_site_id)
                        ->where('material_id', $existingItem->material_id)
                        ->first();

                    if ($siteStock) {
                        $siteStock->current_stock = max(0, (float) $siteStock->current_stock - $deltaQty);
                        $siteStock->last_updated_at = now();
                        $siteStock->save();
                    }

                    // 3. Update MaterialUsageItem
                    $newLineTotal = round($newUsedQty * $newUnitPrice, 2);
                    $inwardQty = (float) $existingItem->inward_quantity;
                    $existingItem->used_quantity = $newUsedQty;
                    $existingItem->unit_price = $newUnitPrice;
                    $existingItem->total_price = $newLineTotal;
                    $existingItem->remaining_quantity = max(0, $inwardQty - $newUsedQty);
                    $existingItem->save();

                    // 4. Sync with PO
                    $this->syncPoItemPriceAndTotals($existingItem, $newUnitPrice);
                } else {
                    // Case 2: New item added to usage
                    $usedQty = (float) ($itData['used_quantity'] ?? 0);
                    $unitPrice = (float) ($itData['unit_price'] ?? 0);
                    $sourceType = $itData['source_type'] ?? 'Third-Party Supplier';
                    $sourceRef = $itData['source_reference'] ?? null;
                    $matName = $itData['material_name'] ?? 'Material';
                    $matId = ! empty($itData['material_id']) ? (int) $itData['material_id'] : null;
                    $unit = $itData['unit'] ?? 'Nos';
                    $unitId = ! empty($itData['unit_id']) ? (int) $itData['unit_id'] : null;
                    $inwardQty = isset($itData['inward_quantity']) ? (float) $itData['inward_quantity'] : $usedQty;
                    $poItemId = ! empty($itData['purchase_order_item_id']) ? (int) $itData['purchase_order_item_id'] : null;

                    if ($usedQty <= 0) {
                        throw new InvalidArgumentException("Used quantity for \"{$matName}\" must be greater than zero.");
                    }

                    $whItem = null;
                    if ($sourceType === 'Own Warehouse') {
                        $whId = ! empty($itData['warehouse_item_id']) ? (int) $itData['warehouse_item_id'] : null;
                        $whItem = $whId ? WarehouseItem::find($whId) : null;
                        if (! $whItem && $sourceRef) {
                            $whItem = WarehouseItem::where('sku', $sourceRef)->orWhere('name_en', $matName)->first();
                        }
                        if ($whItem) {
                            if ((float) $whItem->current_stock < $usedQty - 0.001) {
                                throw new InvalidArgumentException("Used quantity ({$usedQty}) for \"{$matName}\" exceeds available warehouse stock ({$whItem->current_stock}).");
                            }
                            $whItem->current_stock = max(0, (float) $whItem->current_stock - $usedQty);
                            $whItem->save();

                            WarehouseMovement::create([
                                'warehouse_item_id' => $whItem->id,
                                'movement_type' => 'Issue to Site',
                                'quantity' => $usedQty,
                                'cash_amount' => $unitPrice,
                                'project_site_id' => $usage->project_site_id,
                                'site_name' => $usage->projectSite?->name ?? 'Site',
                                'reference_no' => $usage->usage_no,
                                'movement_date' => $data['usage_date'] ?? now()->toDateString(),
                                'notes' => "Issued for Material Usage {$usage->usage_no}",
                                'created_by' => $userId,
                            ]);
                        }
                    }

                    // Match or create master Material if material_id is missing or invalid
                    if (empty($matId) || $matId <= 0) {
                        $foundMat = Material::where('name_en', $matName)
                            ->when($whItem?->name_ta, function ($q, $nameTa) {
                                $q->orWhere('name_ta', $nameTa);
                            })
                            ->first();

                        if ($foundMat) {
                            $matId = $foundMat->id;
                        } else {
                            $createdMat = Material::create([
                                'name_en' => $matName,
                                'name_ta' => $whItem?->name_ta ?? '',
                                'unit_id' => $unitId,
                                'status' => 'Active',
                            ]);
                            $matId = $createdMat->id;
                        }
                    }

                    // Deduct from site stock
                    if ($matId) {
                        $siteStock = SiteMaterialStock::where('project_site_id', $usage->project_site_id)
                            ->where('material_id', $matId)
                            ->first();
                        if ($siteStock) {
                            $siteStock->current_stock = max(0, (float) $siteStock->current_stock - $usedQty);
                            $siteStock->last_updated_at = now();
                            $siteStock->save();
                        }
                    }

                    $newLineTotal = round($usedQty * $unitPrice, 2);

                    $newItem = MaterialUsageItem::create([
                        'material_usage_id' => $usage->id,
                        'purchase_order_item_id' => $poItemId,
                        'material_id' => $matId,
                        'material_name' => $matName,
                        'source_type' => $sourceType,
                        'source_reference' => $sourceRef,
                        'inward_quantity' => $inwardQty,
                        'used_quantity' => $usedQty,
                        'remaining_quantity' => max(0, $inwardQty - $usedQty),
                        'unit' => $unit,
                        'unit_id' => $unitId,
                        'unit_price' => $unitPrice,
                        'total_price' => $newLineTotal,
                    ]);

                    $submittedItemIds[] = $newItem->id;

                    $this->syncPoItemPriceAndTotals($newItem, $unitPrice);
                }
            }

            // Case 3: Removed items (were in usage, but not in submitted list)
            foreach ($usage->items as $oldItem) {
                if (! in_array($oldItem->id, $submittedItemIds, true)) {
                    // Reverse warehouse stock
                    if ($oldItem->source_type === 'Own Warehouse') {
                        $whItem = WarehouseItem::where('sku', $oldItem->source_reference)
                            ->orWhere('name_en', $oldItem->material_name)
                            ->first();
                        if ($whItem) {
                            $whItem->current_stock = (float) $whItem->current_stock + (float) $oldItem->used_quantity;
                            $whItem->save();

                            WarehouseMovement::where('reference_no', $usage->usage_no)
                                ->where('warehouse_item_id', $whItem->id)
                                ->delete();
                        }
                    }

                    // Reverse site stock
                    if ($oldItem->material_id) {
                        $siteStock = SiteMaterialStock::where('project_site_id', $usage->project_site_id)
                            ->where('material_id', $oldItem->material_id)
                            ->first();
                        if ($siteStock) {
                            $siteStock->current_stock = (float) $siteStock->current_stock + (float) $oldItem->used_quantity;
                            $siteStock->last_updated_at = now();
                            $siteStock->save();
                        }
                    }

                    $oldItem->delete();
                }
            }

            // Recompute parent usage totals
            $usage->refresh();
            $usage->total_items_count = $usage->items()->count();
            $usage->total_used_quantity = (float) $usage->items()->sum('used_quantity');
            $usage->total_cost = (float) $usage->items()->sum('total_price');
            $usage->save();

            return $usage->fresh(['purchaseOrder', 'projectSite', 'creator', 'items.material.unit']);
        });
    }

    /**
     * Synchronize price and totals to linked Purchase Order.
     */
    protected function syncPoItemPriceAndTotals(MaterialUsageItem $item, float $newUnitPrice): void
    {
        $poItem = null;
        if ($item->purchase_order_item_id) {
            $poItem = PurchaseOrderItem::with('purchaseOrder')->find($item->purchase_order_item_id);
        } elseif (! empty($item->source_reference) && str_starts_with($item->source_reference, 'PO-')) {
            $po = PurchaseOrder::where('po_number', $item->source_reference)->first();
            if ($po) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where(function ($q) use ($item) {
                        if ($item->material_id) {
                            $q->where('material_id', $item->material_id);
                        }
                        $q->orWhere('material_name', $item->material_name);
                    })->first();
            }
        }

        if ($poItem) {
            $poItem->unit_price = $newUnitPrice;
            $poItem->total_price = round((float) $poItem->quantity * $newUnitPrice, 2);
            $poItem->save();

            if ($poItem->purchaseOrder) {
                $po = $poItem->purchaseOrder;
                $poSubtotal = (float) $po->items()->sum('total_price');
                $po->subtotal = $poSubtotal;
                $po->total_amount = $poSubtotal + (float) ($po->tax_amount ?? 0);
                $po->save();
            }
        }
    }

    /**
     * Delete a material usage and reverse its stock effects.
     */
    public function deleteUsage(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $usage = $this->accessibleUsageQuery()->with('items')->find($id);

            if (! $usage) {
                return false;
            }

            // Reverse stock on site_material_stocks and warehouse items
            foreach ($usage->items as $item) {
                if ($item->source_type === 'Own Warehouse') {
                    $whItem = WarehouseItem::where('sku', $item->source_reference)
                        ->orWhere('name_en', $item->material_name)
                        ->first();

                    if ($whItem) {
                        $whItem->current_stock = (float) $whItem->current_stock + (float) $item->used_quantity;
                        $whItem->save();

                        WarehouseMovement::where('reference_no', $usage->usage_no)
                            ->where('warehouse_item_id', $whItem->id)
                            ->delete();
                    }
                }

                $siteStock = SiteMaterialStock::where('project_site_id', $usage->project_site_id)
                    ->where('material_id', $item->material_id)
                    ->first();

                if ($siteStock) {
                    $siteStock->current_stock = (float) $siteStock->current_stock + (float) $item->used_quantity;
                    $siteStock->last_updated_at = now();
                    $siteStock->save();
                }
            }

            $usage->items()->delete();
            $usage->delete();

            return true;
        });
    }

    /**
     * Generate the next sequential usage number: MU-YYYY-NNNN.
     */
    public function getNextUsageNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "MU-{$year}-";

        $last = MaterialUsage::where('usage_no', 'like', "{$prefix}%")
            ->orderByDesc('usage_no')
            ->value('usage_no');

        $seq = 1;

        if ($last) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get PO items available for a usage entry, deducting already-used quantities from prior usages.
     *
     * @return array<string, mixed>
     */
    public function getPOItemsForUsage(int $purchaseOrderId): array
    {
        $po = PurchaseOrder::with(['items.material', 'projectSite'])->find($purchaseOrderId);

        if (! $po) {
            return [];
        }

        if (! $this->siteManagerCanAccessSite((int) $po->project_site_id)) {
            return [];
        }

        $result = [];

        foreach ($po->items as $poItem) {
            $inwardQty = (float) $poItem->quantity;

            $alreadyUsed = (float) MaterialUsageItem::where('purchase_order_item_id', $poItem->id)
                ->sum('used_quantity');

            $availableQty = max(0, $inwardQty - $alreadyUsed);

            $result[] = [
                'purchase_order_id' => $po->id,
                'purchase_order_item_id' => $poItem->id,
                'po_number' => $po->po_number,
                'supplier_name' => $po->supplier_name,
                'source_type' => $po->source_type,
                'material_id' => $poItem->material_id,
                'material_name' => $poItem->material_name ?: ($poItem->material?->name_en ?? 'Material'),
                'source_reference' => $po->po_number,
                'inward_quantity' => $inwardQty,
                'already_used' => $alreadyUsed,
                'available_quantity' => $availableQty,
                'unit' => $poItem->unit,
                'unit_id' => $poItem->unit_id ?? $poItem->material?->unit_id,
                'unit_price' => (float) ($poItem->unit_price ?? 0),
            ];
        }

        return [
            'po_id' => $po->id,
            'po_number' => $po->po_number,
            'site_id' => $po->project_site_id,
            'site_name' => $po->projectSite ? $po->projectSite->name : '',
            'source_type' => $po->source_type,
            'items' => $result,
        ];
    }

    /**
     * Get materials available for usage for a given site, optionally filtered by PO.
     *
     * @return array<string, mixed>
     */
    public function getSiteMaterialsForUsage(int $siteId, ?int $poId = null): array
    {
        $site = ProjectSite::find($siteId);
        if (! $site) {
            return ['site_id' => 0, 'site_name' => '', 'pos' => [], 'items' => []];
        }

        if (! $this->siteManagerCanAccessSite($siteId)) {
            return ['site_id' => 0, 'site_name' => '', 'pos' => [], 'items' => []];
        }

        $poQuery = PurchaseOrder::where('project_site_id', $siteId)
            ->whereIn('status', ['Order Received', 'Order Completed'])
            ->with(['items.material.unit'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if ($poId) {
            $poQuery->where('id', $poId);
        }

        $pos = $poQuery->get();

        $poOptions = [];
        $items = [];

        foreach ($pos as $po) {
            $poOptions[] = [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'label' => "{$po->po_number} — ".($po->supplier_name ?: $po->source_type).' ('.($po->order_date?->format('d-m-Y') ?? '').')',
                'source_type' => $po->source_type,
                'supplier_name' => $po->supplier_name,
                'order_date' => $po->order_date?->format('d-m-Y'),
                'order_date_raw' => $po->order_date?->format('Y-m-d') ?? '',
                'items_count' => $po->items->count(),
            ];

            foreach ($po->items as $poItem) {
                $inwardQty = (float) $poItem->quantity;

                $alreadyUsed = (float) MaterialUsageItem::where('purchase_order_item_id', $poItem->id)
                    ->sum('used_quantity');

                $availableQty = max(0, $inwardQty - $alreadyUsed);

                $items[] = [
                    'purchase_order_id' => $po->id,
                    'purchase_order_item_id' => $poItem->id,
                    'po_number' => $po->po_number,
                    'supplier_name' => $po->supplier_name,
                    'source_type' => $po->source_type,
                    'order_date' => $po->order_date?->format('d-m-Y') ?? '',
                    'order_date_raw' => $po->order_date?->format('Y-m-d') ?? '',
                    'material_id' => $poItem->material_id,
                    'material_name' => $poItem->material_name ?: ($poItem->material?->name_en ?? 'Material'),
                    'material_name_ta' => $poItem->material?->name_ta ?? '',
                    'source_reference' => $po->po_number,
                    'inward_quantity' => $inwardQty,
                    'already_used' => $alreadyUsed,
                    'available_quantity' => $availableQty,
                    'unit' => $poItem->unit ?: ($poItem->material?->unit?->name_en ?? 'Units'),
                    'unit_id' => $poItem->unit_id ?? $poItem->material?->unit_id,
                    'unit_price' => (float) ($poItem->unit_price ?? 0),
                ];
            }
        }

        // Fetch Inward Stock Transfers to this site
        $transfers = StockTransfer::where('to_project_site_id', $siteId)
            ->where('transfer_type', 'TRANSFER')
            ->where('status', 'Completed')
            ->with(['items.material.unit', 'fromSite'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->get();

        $transferOptions = [];

        foreach ($transfers as $xfer) {
            $fromName = $xfer->fromSite?->name ?? 'Site #'.$xfer->from_project_site_id;
            $transferOptions[] = [
                'id' => $xfer->id,
                'transfer_id' => $xfer->id,
                'transfer_no' => $xfer->transfer_no,
                'po_number' => $xfer->transfer_no,
                'source_site_name' => $fromName,
                'label' => "{$xfer->transfer_no} — Transferred from {$fromName} (".($xfer->transfer_date?->format('d-m-Y') ?? '').')',
                'source_type' => 'Stock Transfer',
                'supplier_name' => "Transferred from {$fromName}",
                'order_date' => $xfer->transfer_date?->format('d-m-Y'),
                'order_date_raw' => $xfer->transfer_date?->format('Y-m-d') ?? '',
                'transfer_date_raw' => $xfer->transfer_date?->format('Y-m-d') ?? '',
                'items_count' => $xfer->items->count(),
                'is_transfer' => true,
            ];

            foreach ($xfer->items as $xferItem) {
                $inwardQty = (float) $xferItem->quantity;

                $alreadyUsed = (float) MaterialUsageItem::whereHas('materialUsage', function ($q) use ($siteId) {
                    $q->where('project_site_id', $siteId)->where('status', '!=', 'Cancelled');
                })
                    ->where('source_type', 'Stock Transfer')
                    ->where('source_reference', $xfer->transfer_no)
                    ->where('material_id', $xferItem->material_id)
                    ->sum('used_quantity');

                $availableQty = max(0, $inwardQty - $alreadyUsed);

                $items[] = [
                    'purchase_order_id' => 'xfer_'.$xfer->id,
                    'purchase_order_item_id' => null,
                    'stock_transfer_id' => $xfer->id,
                    'stock_transfer_item_id' => $xferItem->id,
                    'po_number' => $xfer->transfer_no,
                    'supplier_name' => "Transferred from {$fromName}",
                    'source_type' => 'Stock Transfer',
                    'order_date' => $xfer->transfer_date?->format('d-m-Y') ?? '',
                    'order_date_raw' => $xfer->transfer_date?->format('Y-m-d') ?? '',
                    'transfer_date_raw' => $xfer->transfer_date?->format('Y-m-d') ?? '',
                    'material_id' => $xferItem->material_id,
                    'material_name' => $xferItem->material_name ?: ($xferItem->material?->name_en ?? 'Material'),
                    'material_name_ta' => $xferItem->material?->name_ta ?? '',
                    'source_reference' => $xfer->transfer_no,
                    'inward_quantity' => $inwardQty,
                    'already_used' => $alreadyUsed,
                    'available_quantity' => $availableQty,
                    'unit' => $xferItem->unit ?: ($xferItem->material?->unit?->name_en ?? 'Units'),
                    'unit_id' => $xferItem->unit_id ?? $xferItem->material?->unit_id,
                    'unit_price' => (float) ($xferItem->unit_price ?? 0),
                    'is_transfer' => true,
                ];
            }
        }

        $siteStocks = SiteMaterialStock::with('material.unit')
            ->where('project_site_id', $siteId)
            ->where('current_stock', '>', 0)
            ->get()
            ->map(function ($stk) {
                return [
                    'material_id' => $stk->material_id,
                    'material_name' => $stk->material?->name_en ?? 'Material',
                    'material_name_ta' => $stk->material?->name_ta ?? '',
                    'current_stock' => max(0.0, (float) $stk->current_stock),
                    'unit' => $stk->unit ?: ($stk->material?->unit?->name_en ?? 'Units'),
                ];
            });

        return [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'pos' => $poOptions,
            'transfers' => $transferOptions,
            'items' => $items,
            'site_stocks' => $siteStocks,
        ];
    }

    /**
     * Get active/approved purchase orders suitable for usage recording.
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function getActivePurchaseOrders(array $filters = []): array
    {
        $query = PurchaseOrder::with('projectSite')
            ->whereIn('status', ['Order Received', 'Order Completed'])
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        if (Auth::user()?->isSiteManager()) {
            $query->whereHas('projectSite', function ($siteQuery) {
                $siteQuery->where('created_by', Auth::id());
            });
        }

        if (! empty($filters['site_id'])) {
            $query->where('project_site_id', (int) $filters['site_id']);
        }

        return $query->get()->map(function ($po) {
            return [
                'id' => $po->id,
                'po_number' => $po->po_number,
                'site_id' => $po->project_site_id,
                'site_name' => $po->projectSite ? $po->projectSite->name : '',
                'source_type' => $po->source_type,
                'order_date' => $po->order_date?->format('d-m-Y'),
                'label' => $po->po_number.' — '.($po->projectSite ? $po->projectSite->name : '').' ('.($po->source_type ?? '').')',
            ];
        })->all();
    }

    /**
     * KPI stats for the list page.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getStats(array $filters = []): array
    {
        $query = $this->accessibleUsageQuery();

        if (! empty($filters['site_id'])) {
            $query->where('project_site_id', (int) $filters['site_id']);
        }

        $total = (clone $query)->count();
        $thisMonth = (clone $query)->whereMonth('usage_date', now()->month)
            ->whereYear('usage_date', now()->year)
            ->count();
        $totalItems = (clone $query)->sum('total_items_count');
        $totalQty = (clone $query)->sum('total_used_quantity');

        return [
            'total_usages' => $total,
            'this_month' => $thisMonth,
            'total_items' => (int) $totalItems,
            'total_qty_used' => (float) $totalQty,
        ];
    }

    /**
     * Return material usages visible to the authenticated user.
     *
     * @return Builder<MaterialUsage>
     */
    protected function accessibleUsageQuery(): Builder
    {
        $query = MaterialUsage::query();

        if (Auth::user()?->isSiteManager()) {
            $query->whereHas('projectSite', function ($siteQuery) {
                $siteQuery->where('created_by', Auth::id());
            });
        }

        return $query;
    }

    /**
     * Prevent Site Managers from writing usage records for another user's site.
     */
    protected function ensureSiteManagerOwnsSite(int $siteId): void
    {
        if (! $this->siteManagerCanAccessSite($siteId)) {
            throw new InvalidArgumentException('You are not authorized to use this project site.');
        }
    }

    protected function siteManagerCanAccessSite(int $siteId): bool
    {
        $user = Auth::user();

        return ! $user?->isSiteManager()
            || ProjectSite::query()
                ->whereKey($siteId)
                ->where('created_by', $user->id)
                ->exists();
    }
}
