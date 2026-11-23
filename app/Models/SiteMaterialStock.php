<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMaterialStock extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_material_stocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_site_id',
        'material_id',
        'unit_id',
        'unit',
        'current_stock',
        'last_updated_at',
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
            'material_id' => 'integer',
            'unit_id' => 'integer',
            'current_stock' => 'decimal:2',
            'last_updated_at' => 'datetime',
        ];
    }

    /**
     * Get the associated project site.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the associated material.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    /**
     * Get the associated measurement unit.
     *
     * @return BelongsTo<Unit, $this>
     */
    public function unitRelation(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
