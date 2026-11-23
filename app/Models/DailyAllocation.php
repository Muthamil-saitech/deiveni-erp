<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyAllocation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'daily_allocations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_site_id',
        'date',
        'total_labour_count',
        'total_labour_wage',
        'total_expenses',
        'grand_total',
        'trade_summary',
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
            'project_site_id' => 'integer',
            'date' => 'date:Y-m-d',
            'total_labour_count' => 'integer',
            'total_labour_wage' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the project site that this daily allocation belongs to.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the user who created this allocation.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the worker category line items.
     *
     * @return HasMany<DailyAllocationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DailyAllocationItem::class, 'daily_allocation_id');
    }

    /**
     * Get the same-day site expenses.
     *
     * @return HasMany<DailyAllocationExpense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(DailyAllocationExpense::class, 'daily_allocation_id');
    }
}
