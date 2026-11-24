<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectClientStatement extends Model
{
    use HasFactory;

    protected $table = 'project_client_statements';

    protected $fillable = [
        'project_site_id',
        'statement_number',
        'statement_date',
        'title',
        'client_name',
        'client_phone',
        'client_address',
        'items',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'status',
        'created_by',
    ];

    protected $appends = [
        'encrypted_id',
    ];

    public function getEncryptedIdAttribute(): string
    {
        return IdCipherService::encrypt($this->id);
    }

    protected function casts(): array
    {
        return [
            'project_site_id' => 'integer',
            'statement_date' => 'date:Y-m-d',
            'items' => 'array',
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'created_by' => 'integer',
        ];
    }

    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
