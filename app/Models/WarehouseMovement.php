<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMovement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'warehouse_movements';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'warehouse_item_id',
        'movement_type',
        'quantity',
        'cash_amount',
        'project_site_id',
        'site_name',
        'reference_no',
        'movement_date',
        'notes',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'warehouse_item_id' => 'integer',
            'project_site_id' => 'integer',
            'created_by' => 'integer',
            'quantity' => 'decimal:2',
            'cash_amount' => 'decimal:2',
            'movement_date' => 'date:Y-m-d',
        ];
    }

    /**
     * Get the associated warehouse item.
     *
     * @return BelongsTo<WarehouseItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(WarehouseItem::class, 'warehouse_item_id');
    }

    /**
     * Get the destination project site if issued to a site.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the user who recorded this movement.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
