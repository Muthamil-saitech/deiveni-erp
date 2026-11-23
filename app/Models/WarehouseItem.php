<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'warehouse_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sku',
        'name_en',
        'name_ta',
        'category',
        'unit_id',
        'unit',
        'current_stock',
        'min_threshold',
        'unit_rate',
        'cash_amount',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'current_stock' => 'decimal:2',
            'min_threshold' => 'decimal:2',
            'unit_rate' => 'decimal:2',
            'cash_amount' => 'decimal:2',
        ];
    }

    /**
     * Set the unit attribute supporting both ID and string name.
     */
    public function setUnitAttribute(mixed $value): void
    {
        if (is_numeric($value)) {
            $this->attributes['unit_id'] = (int) $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $val = trim($value);
            $matched = Unit::query()
                ->whereRaw('LOWER(code) = ?', [strtolower($val)])
                ->orWhereRaw('LOWER(name_en) = ?', [strtolower($val)])
                ->orWhere('name_en', 'like', "%{$val}%")
                ->first();

            if (! $matched) {
                $matched = Unit::first();
            }

            if ($matched) {
                $this->attributes['unit_id'] = $matched->id;
            }
        }
    }

    /**
     * The measurement unit for this warehouse item.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get English unit name.
     */
    public function getUnitNameAttribute(): string
    {
        return $this->unit?->name_en ?? '';
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::saving(function (self $item) {
            // Keep cash_amount and unit_rate synchronized
            if (isset($item->attributes['cash_amount']) && ! isset($item->attributes['unit_rate'])) {
                $item->attributes['unit_rate'] = $item->attributes['cash_amount'];
            } elseif (isset($item->attributes['unit_rate']) && ! isset($item->attributes['cash_amount'])) {
                $item->attributes['cash_amount'] = $item->attributes['unit_rate'];
            }
        });
    }

    /**
     * Check if the stock is below or at minimum threshold.
     */
    public function isLowStock(): bool
    {
        return (float) $this->current_stock <= (float) $this->min_threshold;
    }

    /**
     * Get total valuation of current stock.
     */
    public function getValuationAttribute(): float
    {
        $rate = (float) ($this->cash_amount ?? $this->unit_rate ?? 0);

        return (float) $this->current_stock * $rate;
    }

    /**
     * Get movements for this warehouse item.
     *
     * @return HasMany<WarehouseMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class, 'warehouse_item_id')->latest();
    }
}
