<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteCash extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'site_cashes';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_site_id',
        'transaction_type',
        'flow_type',
        'amount',
        'payment_mode',
        'payment_reference',
        'paid_to',
        'paid_by',
        'receiver_name',
        'bank_name',
        'upi_id',
        'account_holder_name',
        'in_amount',
        'out_amount',
        'is_expense',
        'supplier_id',
        'supplier_name',
        'is_site_based',
        'purchase_order_no',
        'bill_no',
        'ledger_id',
        'weekly_wage_id',
        'cash_transfer_id',
        'wage_cash_source',
        'return_site_cash_id',
        'returned_wage_ids',
        'extra_workers',
        'payment_date',
        'payment_time',
        'description',
        'receipt_image',
        'items',
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
            'project_site_id' => 'integer',
            'supplier_id' => 'integer',
            'created_by' => 'integer',
            'amount' => 'decimal:2',
            'payment_mode' => 'string',
            'payment_reference' => 'string',
            'paid_to' => 'string',
            'paid_by' => 'string',
            'ledger_id' => 'integer',
            'weekly_wage_id' => 'integer',
            'in_amount' => 'decimal:2',
            'out_amount' => 'decimal:2',
            'is_expense' => 'boolean',
            'is_site_based' => 'boolean',
            'payment_date' => 'date:Y-m-d',
            'return_site_cash_id' => 'integer',
            'returned_wage_ids' => 'array',
            'items' => 'array',
            'extra_workers' => 'array',
        ];
    }

    /**
     * Get the project site that the cash transaction belongs to.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the supplier associated with the cash transaction.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the user who recorded the cash transaction.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the return transaction that refunded/settled this wage payment.
     *
     * @return BelongsTo<SiteCash, $this>
     */
    public function returnSiteCash(): BelongsTo
    {
        return $this->belongsTo(SiteCash::class, 'return_site_cash_id');
    }

    /**
     * Get the wage transactions that were returned by this return transaction.
     *
     * @return HasMany<SiteCash, $this>
     */
    public function returnedWages(): HasMany
    {
        return $this->hasMany(SiteCash::class, 'return_site_cash_id');
    }

    /**
     * Get the linked daily ledger record if synchronized.
     *
     * @return BelongsTo<Ledger, $this>
     */
    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    /**
     * Get the linked cash transfer record if synchronized.
     *
     * @return BelongsTo<CashTransfer, $this>
     */
    public function cashTransfer(): BelongsTo
    {
        return $this->belongsTo(CashTransfer::class, 'cash_transfer_id');
    }

    /**
     * Determine if this cash transaction or its funds originated from the Account Manager / Accountant.
     */
    public function isFromAccountManager(): bool
    {
        // 1. Explicitly recorded as paid by Account Manager / Accountant / Office
        if (! empty($this->paid_by) && (
            stripos($this->paid_by, 'Account Manager') !== false ||
            stripos($this->paid_by, 'Accountant') !== false ||
            stripos($this->paid_by, 'Office') !== false
        )) {
            return true;
        }

        // 2. Project Cash received from Account Manager
        if ($this->transaction_type === 'Project Cash Received') {
            return true;
        }

        // 3. Directly linked to a CashTransfer from Office / Account Manager
        if (! empty($this->cash_transfer_id)) {
            return true;
        }

        // 4. Paid from office cash (e.g., wage payments handled by Account Manager)
        if ($this->wage_cash_source === 'office_cash') {
            return true;
        }

        // 5. Created by an Account Manager user
        if ($this->created_by) {
            $user = $this->relationLoaded('creator') ? $this->creator : $this->creator()->first();
            if ($user && method_exists($user, 'isAccountManager') && $user->isAccountManager()) {
                return true;
            }
        }

        return false;
    }
}
