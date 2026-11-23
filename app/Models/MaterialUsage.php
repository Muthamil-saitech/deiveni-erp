<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialUsage extends Model
{
    use HasFactory;

    protected $table = 'material_usages';

    protected $fillable = [
        'usage_no',
        'usage_date',
        'purchase_order_id',
        'project_site_id',
        'work_stage',
        'supervisor_name',
        'total_items_count',
        'total_used_quantity',
        'total_cost',
        'remarks',
        'status',
        'created_by',
    ];

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
            'purchase_order_id' => 'integer',
            'project_site_id' => 'integer',
            'created_by' => 'integer',
            'total_items_count' => 'integer',
            'total_used_quantity' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'usage_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Associated Purchase Order.
     *
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Associated Project Site.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * User who recorded the usage.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Individual material items consumed.
     *
     * @return HasMany<MaterialUsageItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MaterialUsageItem::class, 'material_usage_id');
    }
}
