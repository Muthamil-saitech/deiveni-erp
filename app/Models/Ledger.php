<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ledger extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ledgers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'entry_date',
        'flow',
        'source',
        'amount',
        'project_site_id',
        'site_name',
        'client_name',
        'cash_received_from',
        'supplier_id',
        'supplier_name',
        'bank_id',
        'bank_name',
        'branch_id',
        'branch_name',
        'bank_customer_ref',
        'property_id',
        'property_name',
        'property_type_id',
        'unit_name',
        'office_expense_details',
        'transaction_type_id',
        'transaction_type_name',
        'transaction_type_value',
        'transaction_type_data',
        'description',
        'receipt_image',
        'daily_allocation_id',
        'cash_transfer_id',
        'wage_item_ids',
        'extra_workers',
        'wage_cash_source',
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
            'project_site_id' => 'integer',
            'supplier_id' => 'integer',
            'bank_id' => 'integer',
            'branch_id' => 'integer',
            'property_id' => 'integer',
            'property_type_id' => 'integer',
            'transaction_type_id' => 'integer',
            'transaction_type_data' => 'array',
            'daily_allocation_id' => 'integer',
            'wage_item_ids' => 'array',
            'extra_workers' => 'array',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the daily allocation associated with the wage payment.
     *
     * @return BelongsTo<DailyAllocation, $this>
     */
    public function dailyAllocation(): BelongsTo
    {
        return $this->belongsTo(DailyAllocation::class, 'daily_allocation_id');
    }

    /**
     * Get the project site associated with the entry.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the supplier associated with the entry.
     *
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Get the bank associated with the entry.
     *
     * @return BelongsTo<Bank, $this>
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    /**
     * Get the bank branch associated with the entry.
     *
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get the property associated with the entry.
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Get the property type associated with the entry.
     *
     * @return BelongsTo<PropertyType, $this>
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /**
     * Get the transaction type master record associated with the entry.
     *
     * @return BelongsTo<TransactionType, $this>
     */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    /**
     * Get the user who recorded the entry.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * Scope query to income entries.
     */
    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('flow', 'income');
    }

    /**
     * Scope query to expense entries.
     */
    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('flow', 'expense');
    }

    /**
     * Scope query by source/category.
     */
    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }
}
