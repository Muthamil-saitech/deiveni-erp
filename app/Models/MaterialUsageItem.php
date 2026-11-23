<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialUsageItem extends Model
{
    use HasFactory;

    protected $table = 'material_usage_items';

    protected $fillable = [
        'material_usage_id',
        'purchase_order_item_id',
        'material_id',
        'material_name',
        'source_type',
        'source_reference',
        'inward_quantity',
        'used_quantity',
        'remaining_quantity',
        'unit',
        'unit_id',
        'unit_price',
        'total_price',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'material_usage_id' => 'integer',
            'purchase_order_item_id' => 'integer',
            'material_id' => 'integer',
            'unit_id' => 'integer',
            'inward_quantity' => 'decimal:2',
            'used_quantity' => 'decimal:2',
            'remaining_quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Parent Material Usage record.
     *
     * @return BelongsTo<MaterialUsage, $this>
     */
    public function materialUsage(): BelongsTo
    {
        return $this->belongsTo(MaterialUsage::class, 'material_usage_id');
    }

    /**
     * Associated PO item.
     *
     * @return BelongsTo<PurchaseOrderItem, $this>
     */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    /**
     * Master material.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Master unit.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unitRelation(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
