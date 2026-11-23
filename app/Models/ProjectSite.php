<?php

namespace App\Models;

use App\Services\IdCipherService;
use Database\Factories\ProjectSiteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectSite extends Model
{
    /** @use HasFactory<ProjectSiteFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_sites';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'name',
        'location',
        'work_type',
        'start_date',
        'end_date',
        'estimated_budget',
        'spent',
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
        'site_name',
    ];

    /**
     * Compatibility accessor for site name.
     */
    public function getSiteNameAttribute(): string
    {
        return (string) ($this->attributes['name'] ?? '');
    }

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
            'client_id' => 'integer',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'estimated_budget' => 'decimal:2',
            'spent' => 'decimal:2',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the user who created the project site.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the client that owns the project site.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Get the site cash transactions for the project site.
     *
     * @return HasMany<SiteCash, $this>
     */
    public function siteCashes(): HasMany
    {
        return $this->hasMany(SiteCash::class, 'project_site_id');
    }

    /**
     * Get the daily allocations for the project site.
     *
     * @return HasMany<DailyAllocation, $this>
     */
    public function dailyAllocations(): HasMany
    {
        return $this->hasMany(DailyAllocation::class, 'project_site_id');
    }

    /**
     * Get the weekly wages for the project site.
     *
     * @return HasMany<WeeklyWage, $this>
     */
    public function weeklyWages(): HasMany
    {
        return $this->hasMany(WeeklyWage::class, 'project_site_id');
    }

    /**
     * Get the material usages for the project site.
     *
     * @return HasMany<MaterialUsage, $this>
     */
    public function materialUsages(): HasMany
    {
        return $this->hasMany(MaterialUsage::class, 'project_site_id');
    }

    /**
     * Get outward stock transfers from this site.
     *
     * @return HasMany<StockTransfer, $this>
     */
    public function outwardStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_project_site_id');
    }

    /**
     * Get inward stock transfers to this site.
     *
     * @return HasMany<StockTransfer, $this>
     */
    public function inwardStockTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_project_site_id');
    }
}
