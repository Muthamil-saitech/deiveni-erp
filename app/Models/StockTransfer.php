<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_transfers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transfer_no',
        'transfer_date',
        'from_project_site_id',
        'to_project_site_id',
        'material_id',
        'unit_id',
        'unit',
        'quantity',
        'transfer_type',
        'parent_transfer_id',
        'return_expected',
        'return_status',
        'returned_quantity',
        'total_amount',
        'total_items_count',
        'vehicle_no',
        'driver_name',
        'challan_no',
        'remarks',
        'status',
        'created_by',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'encrypted_id',
    ];

    /**
     * Get the compact encrypted ID for URL routing.
     */
    public function getEncryptedIdAttribute(): string
    {
        return IdCipherService::encrypt($this->id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_project_site_id' => 'integer',
            'to_project_site_id' => 'integer',
            'material_id' => 'integer',
            'unit_id' => 'integer',
            'parent_transfer_id' => 'integer',
            'created_by' => 'integer',
            'quantity' => 'decimal:2',
            'returned_quantity' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'total_items_count' => 'integer',
            'return_expected' => 'boolean',
            'transfer_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Line items transferred in this voucher.
     *
     * @return HasMany<StockTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'stock_transfer_id');
    }

    /**
     * Source project site.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function fromSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'from_project_site_id');
    }

    /**
     * Destination project site.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function toSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'to_project_site_id');
    }

    /**
     * Transferred material.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Measurement unit.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unitRelation(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * User who created this record.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Parent transfer if this is a return transaction.
     *
     * @return BelongsTo<StockTransfer, $this>
     */
    public function parentTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'parent_transfer_id');
    }

    /**
     * Return transactions recorded against this transfer.
     *
     * @return HasMany<StockTransfer, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'parent_transfer_id');
    }

    /**
     * Calculate remaining quantity to be returned.
     */
    public function getPendingReturnQuantityAttribute(): float
    {
        if (! $this->return_expected || $this->transfer_type !== 'TRANSFER') {
            return 0.0;
        }

        $remaining = (float) $this->quantity - (float) $this->returned_quantity;

        return max(0.0, $remaining);
    }

    /**
     * Scope for pending return transfers.
     *
     * @param  Builder<StockTransfer>  $query
     * @return Builder<StockTransfer>
     */
    public function scopePendingReturn(Builder $query): Builder
    {
        return $query->where('transfer_type', 'TRANSFER')
            ->where('return_expected', true)
            ->whereIn('return_status', ['Pending Return', 'Partially Returned']);
    }
}
