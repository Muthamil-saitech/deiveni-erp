<?php

namespace App\Models;

use Database\Factories\PropertyTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyType extends Model
{
    /** @use HasFactory<PropertyTypeFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'property_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'property_id',
        'shop_home_office_name',
        'property_type',
        'floor_type',
        'location',
        'tenant_name',
        'tenant_phone',
        'monthly_rent',
        'security_deposit',
        'rent_due_day',
        'status',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'property_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'property_id' => 'integer',
            'monthly_rent' => 'decimal:2',
            'security_deposit' => 'decimal:2',
        ];
    }

    /**
     * Get the property that owns this property type record.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Dynamic accessor for property_name from the linked property.
     */
    public function getPropertyNameAttribute(): ?string
    {
        return $this->property?->property_name;
    }
}
