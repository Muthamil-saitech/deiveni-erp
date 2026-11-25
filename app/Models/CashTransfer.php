<?php

namespace App\Models;

use App\Services\IdCipherService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransfer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cash_transfers';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transfer_direction',
        'project_site_id',
        'transfer_date',
        'transfer_time',
        'amount',
        'payment_mode',
        'payment_reference',
        'bank_name',
        'handed_over_by',
        'received_by',
        'purpose',
        'is_temporary',
        'expected_return_date',
        'settlement_status',
        'settled_at',
        'settled_by',
        'settlement_notes',
        'acceptance_status',
        'accepted_at',
        'accepted_by',
        'acceptance_notes',
        'receipt_image',
        'ledger_id',
        'admin_ledger_id',
        'site_cash_id',
        'return_site_cash_id',
        'return_ledger_id',
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
            'amount' => 'decimal:2',
            'transfer_date' => 'date:Y-m-d',
            'expected_return_date' => 'date:Y-m-d',
            'is_temporary' => 'boolean',
            'settled_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the project site associated with this transfer.
     *
     * @return BelongsTo<ProjectSite, $this>
     */
    public function projectSite(): BelongsTo
    {
        return $this->belongsTo(ProjectSite::class, 'project_site_id');
    }

    /**
     * Get the user who recorded this transfer.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who marked this transfer as settled.
     *
     * @return BelongsTo<User, $this>
     */
    public function settler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * Get the user who accepted this transfer.
     *
     * @return BelongsTo<User, $this>
     */
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /**
     * Get the linked Daily Ledger record.
     *
     * @return BelongsTo<Ledger, $this>
     */
    public function ledger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'ledger_id');
    }

    /**
     * Get the linked Site Cash record.
     *
     * @return BelongsTo<SiteCash, $this>
     */
    public function siteCash(): BelongsTo
    {
        return $this->belongsTo(SiteCash::class, 'site_cash_id');
    }

    /**
     * Get the return/settlement Site Cash record.
     *
     * @return BelongsTo<SiteCash, $this>
     */
    public function returnSiteCash(): BelongsTo
    {
        return $this->belongsTo(SiteCash::class, 'return_site_cash_id');
    }

    /**
     * Get the return/settlement Daily Ledger record.
     *
     * @return BelongsTo<Ledger, $this>
     */
    public function returnLedger(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'return_ledger_id');
    }

    /**
     * Check if this is an Office to Site transfer.
     */
    public function isOfficeToSite(): bool
    {
        return $this->transfer_direction === 'office_to_site';
    }

    /**
     * Check if this is a Project Cash transfer.
     */
    public function isProjectCash(): bool
    {
        return $this->transfer_direction === 'project_cash';
    }

    /**
     * Check if this is a Site to Office transfer.
     */
    public function isSiteToOffice(): bool
    {
        if ($this->transfer_direction === 'site_to_office') {
            return true;
        }

        $handed = strtolower(trim((string) $this->handed_over_by));
        $received = strtolower(trim((string) $this->received_by));

        $isHandedBySite = str_contains($handed, 'site') || str_contains($handed, 'engineer');
        $isReceivedByOffice = str_contains($received, 'account') || str_contains($received, 'office') || str_contains($received, 'raja');

        return $isHandedBySite && $isReceivedByOffice;
    }

    /**
     * Check if this transfer is settled.
     */
    public function isSettled(): bool
    {
        return $this->settlement_status === 'settled';
    }

    /**
     * Check if this transfer is pending settlement.
     */
    public function isPending(): bool
    {
        return $this->settlement_status === 'pending';
    }

    /**
     * Get human readable direction label.
     */
    public function directionLabel(): string
    {
        return match ($this->transfer_direction) {
            'project_cash' => 'Project Cash to Site',
            'site_to_office' => 'Site ➔ Office Loan',
            default => 'Office ➔ Site Advance',
        };
    }

    /**
     * Get CSS badge class for transfer direction.
     */
    public function directionBadgeClass(): string
    {
        return match ($this->transfer_direction) {
            'project_cash' => 'bg-success-subtle text-success border border-success-subtle',
            'site_to_office' => 'bg-warning-subtle text-dark border-warning-subtle',
            default => 'bg-primary-subtle text-primary border-primary-subtle',
        };
    }

    /**
     * Get Bootstrap icon name for transfer direction.
     */
    public function directionIcon(): string
    {
        return match ($this->transfer_direction) {
            'project_cash' => 'bi-wallet-fill',
            'site_to_office' => 'bi-arrow-left-circle',
            default => 'bi-arrow-right-circle',
        };
    }

    /**
     * Check if this transfer is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->acceptance_status === 'accepted';
    }

    /**
     * Check if this transfer is pending acceptance.
     */
    public function isAcceptancePending(): bool
    {
        return $this->acceptance_status === 'pending';
    }

    /**
     * Determine if a given user can accept this transfer.
     */
    public function canBeAcceptedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (! $this->isAcceptancePending()) {
            return false;
        }

        // Admin/Owner can accept any transfer on behalf of parties
        if ($user->isAdmin()) {
            return true;
        }

        // Site to Office or any transfer sent from Site to Office / Account Manager
        if ($this->isSiteToOffice()) {
            // Sender (Site Manager) cannot accept their own sent money
            if ($this->created_by === $user->id) {
                return false;
            }

            // Account Manager can accept
            if ($user->isAccountManager()) {
                return true;
            }

            // Name match on received_by
            if (! empty($this->received_by) && (str_contains(strtolower($this->received_by), strtolower($user->name)) || str_contains(strtolower($this->received_by), 'accountant') || str_contains(strtolower($this->received_by), 'account manager') || str_contains(strtolower($this->received_by), 'office'))) {
                return true;
            }

            return false;
        }

        // Office to Site or Project Cash sent to Site: Sent to Site Manager / Site Engineer
        // Sender (Account Manager) cannot accept their own sent money
        if ($this->created_by === $user->id) {
            return false;
        }

        // Site Manager can accept
        if ($user->isSiteManager()) {
            return true;
        }

        // Name match on received_by
        if (! empty($this->received_by) && (str_contains(strtolower($this->received_by), strtolower($user->name)) || str_contains(strtolower($this->received_by), 'site manager') || str_contains(strtolower($this->received_by), 'site engineer'))) {
            return true;
        }

        return false;
    }

    /**
     * Get human readable acceptance label.
     */
    public function acceptanceLabel(): string
    {
        return $this->isAccepted() ? 'Accepted / Received' : 'Pending Acceptance';
    }

    /**
     * Get CSS badge class for acceptance status.
     */
    public function acceptanceBadgeClass(): string
    {
        return $this->isAccepted()
            ? 'bg-success-subtle text-success border border-success-subtle'
            : 'bg-warning-subtle text-dark border border-warning-subtle';
    }

    /**
     * Get human readable settlement label.
     */
    public function settlementLabel(): string
    {
        return $this->isSettled() ? 'Settled' : 'Pending Return';
    }

    /**
     * Get CSS badge class for settlement status.
     */
    public function settlementBadgeClass(): string
    {
        return $this->isSettled()
            ? 'bg-success-subtle text-success border border-success-subtle'
            : 'bg-danger-subtle text-danger border border-danger-subtle';
    }
}
