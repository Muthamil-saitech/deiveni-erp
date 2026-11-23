<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'stock_transfer_id',
        'material_id',
        'material_name',
        'source_type',
        'source_reference',
        'supplier_name',
        'quantity',
        'unit',
        'unit_id',
        'unit_price',
        'total_price',
        'returned_quantity',
        'return_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'returned_quantity' => 'decimal:2',
        ];
    }

    /**
     * Parent transfer voucher.
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    /**
     * Related material master.
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Related unit master.
     */
    public function unitRelation(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Calculate remaining quantity to return for this item.
     */
    public function getPendingReturnQuantityAttribute(): float
    {
        $pending = (float) $this->quantity - (float) $this->returned_quantity;

        return max(0, round($pending, 2));
    }
}
