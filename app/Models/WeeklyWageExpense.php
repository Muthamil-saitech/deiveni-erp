<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyWageExpense extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'weekly_wage_expenses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'weekly_wage_id',
        'item_name',
        'quantity',
        'unit_price',
        'amount',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekly_wage_id' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the weekly wage that owns this expense.
     *
     * @return BelongsTo<WeeklyWage, $this>
     */
    public function weeklyWage(): BelongsTo
    {
        return $this->belongsTo(WeeklyWage::class, 'weekly_wage_id');
    }
}
