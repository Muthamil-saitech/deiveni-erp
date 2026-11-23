<?php

namespace App\Models;

use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name_en',
        'name_ta',
        'code',
        'usage',
        'status',
    ];

    /**
     * The materials using this measurement unit.
     *
     * @return HasMany<Material, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(Material::class, 'unit_id');
    }

    /**
     * Determine if the measurement unit is used by any material.
     */
    public function isUsedInMaterials(): bool
    {
        return $this->materials()->exists();
    }

    /**
     * String representation of unit defaults to English name.
     */
    public function __toString(): string
    {
        return (string) ($this->name_en ?? '');
    }
}
