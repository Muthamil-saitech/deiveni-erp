<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyAllocationItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'daily_allocation_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'daily_allocation_id',
        'worker_category_id',
        'category_code',
        'name_ta',
        'name_en',
        'daily_wage',
        'master_wage',
        'worker_count',
        'subtotal',
        'paid_by',
        'wage_cash_source',
        'ledger_id',
        'site_cash_id',
        'referred_to_am',
        'paid_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_allocation_id' => 'integer',
            'worker_category_id' => 'integer',
            'daily_wage' => 'decimal:2',
            'master_wage' => 'decimal:2',
            'worker_count' => 'integer',
            'subtotal' => 'decimal:2',
            'ledger_id' => 'integer',
            'site_cash_id' => 'integer',
            'referred_to_am' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the daily allocation that owns this item.
     *
     * @return BelongsTo<DailyAllocation, $this>
     */
    public function dailyAllocation(): BelongsTo
    {
        return $this->belongsTo(DailyAllocation::class, 'daily_allocation_id');
    }

    /**
     * Get the worker category for this item.
     *
     * @return BelongsTo<WorkerCategory, $this>
     */
    public function workerCategory(): BelongsTo
    {
        return $this->belongsTo(WorkerCategory::class, 'worker_category_id');
    }

    /**
     * Get the ledger record for this payment if paid by Account Manager.
     *
     * @return BelongsTo<Ledger, $this>
     */
    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }
}
