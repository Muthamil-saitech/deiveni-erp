<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'user_name',
        'user_role',
        'ip_address',
        'user_agent',
        'module',
        'target_type',
        'target_id',
        'action',
        'severity',
        'title',
        'title_ta',
        'site_name',
        'amount',
        'old_values',
        'new_values',
        'details',
        'integrity_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'old_values' => 'array',
            'new_values' => 'array',
            'details' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get user initials for display.
     */
    public function getUserInitialsAttribute(): string
    {
        $name = trim((string) ($this->user_name ?: 'System'));
        $words = preg_split('/\s+/', $name) ?: [];
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }

        return $initials ?: 'SYS';
    }

    /**
     * Get avatar color class based on role/user.
     */
    public function getAvatarBgAttribute(): string
    {
        $role = strtolower((string) ($this->user_role ?: ''));
        if (str_contains($role, 'owner') || str_contains($role, 'admin')) {
            return 'bg-primary text-white';
        }
        if (str_contains($role, 'manager') || str_contains($role, 'site')) {
            return 'bg-warning text-dark';
        }
        if (str_contains($role, 'account') || str_contains($role, 'finance')) {
            return 'bg-success text-white';
        }
        if (str_contains($role, 'store') || str_contains($role, 'warehouse')) {
            return 'bg-secondary text-white';
        }

        return 'bg-info text-white';
    }

    /**
     * Get CSS badge class for action.
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match (strtoupper((string) $this->action)) {
            'CREATE' => 'badge-action-create',
            'UPDATE' => 'badge-action-update',
            'DELETE' => 'badge-action-delete',
            'APPROVE', 'RECONCILE' => 'badge-action-approve',
            'REJECT' => 'badge-action-reject',
            'CASH_TXN', 'CASH_IN', 'CASH_OUT' => 'badge-action-cash',
            'LOGIN' => 'badge-action-login',
            'LOGOUT' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
            default => 'badge-action-security',
        };
    }

    /**
     * Get icon class for action.
     */
    public function getActionIconAttribute(): string
    {
        return match (strtoupper((string) $this->action)) {
            'CREATE' => 'bi bi-plus-circle-fill',
            'UPDATE' => 'bi bi-pencil-fill',
            'DELETE' => 'bi bi-trash3-fill',
            'APPROVE' => 'bi bi-check-circle-fill',
            'REJECT' => 'bi bi-x-circle-fill',
            'CASH_TXN', 'CASH_IN', 'CASH_OUT' => 'bi bi-arrow-up-right-circle',
            'LOGIN' => 'bi bi-box-arrow-in-right',
            'LOGOUT' => 'bi bi-box-arrow-left',
            default => 'bi bi-activity',
        };
    }
}
