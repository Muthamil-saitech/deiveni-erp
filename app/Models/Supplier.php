<?php

namespace App\Models;

use App\Services\IdCipherService;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'address',
        'gst_number',
        'status',
        'is_site_based',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_site_based' => 'boolean',
        ];
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
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
     * The materials supplied by this supplier with individual pricing.
     *
     * @return BelongsToMany<Material, $this>
     */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'supplier_materials')
            ->withPivot('id', 'price', 'grade')
            ->withTimestamps();
    }

    /**
     * Get the supplier material pricing records.
     *
     * @return HasMany<SupplierMaterial, $this>
     */
    public function supplierMaterials(): HasMany
    {
        return $this->hasMany(SupplierMaterial::class);
    }

    /**
     * Get the purchase orders associated with this supplier.
     *
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }
}
