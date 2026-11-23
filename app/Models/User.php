<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role_id',
        'role',
        'phone',
        'status',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The dynamic role relationship assigned to this user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the role attribute as UserRole enum when applicable or Role model.
     */
    public function getRoleAttribute(): mixed
    {
        $raw = $this->attributes['role'] ?? null;
        if (is_string($raw)) {
            $enum = UserRole::tryFrom($raw);
            if ($enum) {
                return $enum;
            }
        }

        if ($this->relationLoaded('role')) {
            $rel = $this->getRelationValue('role');
            if ($rel) {
                return $rel;
            }
        }

        if (! empty($this->attributes['role_id'])) {
            $rel = $this->role()->first();
            if ($rel) {
                return $rel;
            }
        }

        return $raw;
    }

    /**
     * Set the role attribute dynamically, syncing role_id and role string.
     */
    public function setRoleAttribute(mixed $value): void
    {
        if ($value instanceof Role) {
            $this->attributes['role_id'] = $value->id;
            $this->attributes['role'] = $value->slug ?? Str::slug($value->name);
        } elseif ($value instanceof UserRole) {
            $this->attributes['role'] = $value->value;
            $role = Role::where('slug', $value->value)->orWhere('slug', str_replace('_', '-', $value->value))->orWhere('name', $value->value)->first();
            if ($role) {
                $this->attributes['role_id'] = $role->id;
            }
        } elseif (is_numeric($value)) {
            $this->attributes['role_id'] = (int) $value;
            $role = Role::find($value);
            if ($role) {
                $this->attributes['role'] = $role->slug ?? Str::slug($role->name);
            }
        } elseif (is_string($value)) {
            $this->attributes['role'] = $value;
            $role = Role::where('slug', $value)->orWhere('slug', str_replace('_', '-', $value))->orWhere('name', $value)->first();
            if ($role) {
                $this->attributes['role_id'] = $role->id;
            }
        }
    }

    /**
     * Get the resolved Role model if available.
     */
    public function roleModel(): ?Role
    {
        if ($this->relationLoaded('role')) {
            $rel = $this->getRelationValue('role');
            if ($rel instanceof Role) {
                return $rel;
            }
        }

        if (! empty($this->attributes['role_id'])) {
            $rel = $this->role()->first();
            if ($rel) {
                return $rel;
            }
        }

        $slug = $this->roleValue();
        if ($slug) {
            return Role::where('slug', $slug)
                ->orWhere('slug', str_replace('_', '-', $slug))
                ->orWhere('name', $slug)
                ->first();
        }

        return null;
    }

    /**
     * Get the string value of the user's role.
     */
    public function roleValue(): ?string
    {
        $role = $this->getRoleAttribute();
        if ($role instanceof UserRole) {
            return $role->value;
        }

        if ($role instanceof Role) {
            return $role->slug ?? $role->name;
        }

        if (is_string($role)) {
            return $role;
        }

        return $this->attributes['role'] ?? null;
    }

    /**
     * Get display role name.
     */
    public function roleName(): string
    {
        $model = $this->roleModel();
        if ($model) {
            return $model->name;
        }

        $role = $this->getRoleAttribute();
        if ($role instanceof UserRole) {
            return $role->label();
        }

        $raw = $this->attributes['role'] ?? 'User';

        return is_string($raw) ? ucwords(str_replace(['_', '-'], ' ', $raw)) : 'User';
    }

    /**
     * Get badge class for role.
     */
    public function roleBadgeClass(): string
    {
        $model = $this->roleModel();
        if ($model) {
            return $model->badgeClass();
        }

        $role = $this->getRoleAttribute();
        if ($role instanceof UserRole) {
            return $role->badgeClass();
        }

        return match ($this->normalizedRoleSlug()) {
            'owner', 'admin' => 'bg-primary text-white',
            'site_manager' => 'bg-warning text-dark',
            'account_manager' => 'bg-success text-white',
            default => 'bg-secondary text-white',
        };
    }

    /**
     * Normalize the user's role slug so hyphenated, underscored, and spaced variants match uniformly.
     */
    private function normalizedRoleSlug(): string
    {
        return str_replace(['-', ' '], '_', strtolower(trim($this->roleValue() ?? '')));
    }

    /**
     * Check if the user is an owner/admin.
     */
    public function isOwner(): bool
    {
        return in_array($this->normalizedRoleSlug(), ['owner', 'admin'], true);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->isOwner();
    }

    /**
     * Check if the user is a site manager.
     */
    public function isSiteManager(): bool
    {
        return $this->normalizedRoleSlug() === 'site_manager';
    }

    /**
     * Check if the user is an account manager.
     */
    public function isAccountManager(): bool
    {
        return $this->normalizedRoleSlug() === 'account_manager';
    }

    /**
     * Dynamic RBAC check: whether user has permission to access a menu key.
     */
    public function canAccess(string $menu): bool
    {
        // Owner always retains access to administration and profile
        if ($this->isOwner() && in_array($menu, ['users', 'audit_logs', 'profile'], true)) {
            return true;
        }

        // Non-owners can never access administration modules (Owner Only)
        if (! $this->isOwner() && in_array($menu, ['users', 'audit_logs'], true)) {
            return false;
        }

        $role = $this->roleModel();
        if ($role) {
            if ($role->hasMenu($menu)) {
                return true;
            }

            // Fallback for general 'reports' check
            if ($menu === 'reports') {
                return $role->hasMenu('reports')
                    || $role->hasMenu('reports_profit_loss')
                    || $role->hasMenu('reports_labour_allocations')
                    || $role->hasMenu('reports_cash_handling')
                    || $role->hasMenu('reports_purchases_inward')
                    || $role->hasMenu('reports_warehouse_inventory')
                    || $role->hasMenu('reports_ledger');
            }

            // Aliases for reports and modules
            $aliases = [
                'material-usages' => 'material_usage',
                'material-usage' => 'material_usage',
                'material_usages' => 'material_usage',
                'stock-transfers' => 'stock_transfer',
                'stock_transfers' => 'stock_transfer',
                'reports-stock-transfers' => 'reports_stock_transfer',
                'rentals' => 'property_types',
                'rental' => 'property_types',
                'profit-loss' => 'reports_profit_loss',
                'profit_loss' => 'reports_profit_loss',
                'labour-allocations' => 'reports_labour_allocations',
                'labour_allocations' => 'reports_labour_allocations',
                'cash-handling' => 'reports_cash_handling',
                'cash_handling' => 'reports_cash_handling',
                'purchases-inward' => 'reports_purchases_inward',
                'purchases_inward' => 'reports_purchases_inward',
                'warehouse-inventory' => 'reports_warehouse_inventory',
                'warehouse_inventory' => 'reports_warehouse_inventory',
                'account-ledger' => 'reports_ledger',
                'account_ledger' => 'reports_ledger',
            ];

            if (isset($aliases[$menu]) && ($role->hasMenu($aliases[$menu]) || ($aliases[$menu] === 'property_types' && $role->hasMenu('properties')))) {
                return true;
            }

            // Backwards compatibility for legacy 'reports' permission
            if ($role->hasMenu('reports')) {
                if ($this->isSiteManager() && in_array($menu, ['reports_labour_allocations', 'reports_cash_handling', 'reports_purchases_inward', 'reports_warehouse_inventory', 'reports_stock_transfer'], true)) {
                    return true;
                }
                if ($this->isAccountManager() && $menu === 'reports_ledger') {
                    return true;
                }
                if ($this->isOwner() && in_array($menu, ['reports_profit_loss', 'reports_labour_allocations', 'reports_cash_handling', 'reports_purchases_inward', 'reports_warehouse_inventory', 'reports_stock_transfer', 'reports_ledger'], true)) {
                    return true;
                }
            }

            return false;
        }

        $roleAttr = $this->role;
        $roleEnum = $roleAttr instanceof UserRole ? $roleAttr : UserRole::tryFrom((string) $roleAttr);
        if ($roleEnum) {
            return $roleEnum->canAccess($menu);
        }

        return false;
    }

    /**
     * Check if user has permission to access any of the given menu keys.
     *
     * @param  array<int, string>  $menus
     */
    public function canAccessAny(array $menus): bool
    {
        foreach ($menus as $menu) {
            if ($this->canAccess($menu)) {
                return true;
            }
        }

        return false;
    }
}
