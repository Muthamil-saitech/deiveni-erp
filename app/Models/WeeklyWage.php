<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeeklyWage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'weekly_wages';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_site_id',
        'week_start_date',
        'week_end_date',
        'total_labour_man_days',
        'total_labour_wages',
        'total_daily_expenses',
        'total_weekly_expenses',
        'net_payout',
        'status',
        'settled_at',
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
     * Get the encrypted ID attribute for the weekly wage record.
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
            'project_site_id' => 'integer',
            'week_start_date' => 'date:Y-m-d',
            'week_end_date' => 'date:Y-m-d',
            'total_labour_man_days' => 'integer',
            'total_labour_wages' => 'decimal:2',
            'total_daily_expenses' => 'decimal:2',
            'total_weekly_expenses' => 'decimal:2',
            'net_payout' => 'decimal:2',
            'settled_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the user who created the weekly wage record.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the project site that this weekly wage belongs to.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the additional weekly site expenses.
     *
     * @return HasMany<WeeklyWageExpense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(WeeklyWageExpense::class, 'weekly_wage_id');
    }
}
