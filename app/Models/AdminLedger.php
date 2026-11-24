<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminLedger extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'admin_ledgers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entry_date',
        'category',
        'flow',
        'client_id',
        'client_name',
        'project_site_id',
        'site_name',
        'is_site_based',
        'supplier_id',
        'supplier_name',
        'purchase_order_no',
        'amount',
        'payment_mode',
        'payment_reference',
        'bank_name',
        'cheque_no',
        'description',
        'receipt_image',
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
            'entry_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'is_site_based' => 'boolean',
            'client_id' => 'integer',
            'project_site_id' => 'integer',
            'supplier_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the associated client.
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
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
     * Get the associated supplier.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the creator of the entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
