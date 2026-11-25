<?php

namespace App\Enums;

use App\Models\Role;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case SiteManager = 'site_manager';
    case AccountManager = 'account_manager';

    /**
     * Get the human-readable display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner, self::Admin => 'Owner (Admin)',
            self::SiteManager => 'Site Manager',
            self::AccountManager => 'Account Manager',
        };
    }

    /**
     * Get the Tamil title for the role.
     */
    public function labelTa(): string
    {
        $dbRole = Role::where('slug', $this->value)->orWhere('slug', str_replace('_', '-', $this->value))->first();
        if ($dbRole) {
            return $dbRole->labelTa();
        }

        return match ($this) {
            self::Owner, self::Admin => 'உரிமையாளர் (நிர்வாகி)',
            self::SiteManager => 'தள மேலாளர்',
            self::AccountManager => 'கணக்கு மேலாளர்',
        };
    }

    /**
     * Get badge CSS classes for display.
     */
    public function badgeClass(): string
    {
        $dbRole = Role::where('slug', $this->value)->orWhere('slug', str_replace('_', '-', $this->value))->first();
        if ($dbRole) {
            return $dbRole->badgeClass();
        }

        return match ($this) {
            self::Owner, self::Admin => 'bg-primary text-white',
            self::SiteManager => 'bg-warning text-dark',
            self::AccountManager => 'bg-success text-white',
        };
    }

    /**
     * Dynamic RBAC check: evaluate against database table `role_menus`.
     */
    public function canAccess(string $menu): bool
    {
        if ($this === self::Owner || $this === self::Admin) {
            return true;
        }

        $dbRole = Role::where('slug', $this->value)->orWhere('slug', str_replace('_', '-', $this->value))->first();
        if ($dbRole) {
            if ($dbRole->hasMenu($menu)) {
                return true;
            }

            if ($menu === 'rentals' && ($dbRole->hasMenu('property_types') || $dbRole->hasMenu('properties'))) {
                return true;
            }

            return false;
        }

        // Default RBAC permissions when database roles table is unseeded
        return match ($this) {
            self::SiteManager => in_array($menu, [
                'dashboard', 'worker_categories', 'units', 'materials', 'suppliers', 'transaction_types',
                'sites', 'daily_allocation', 'weekly_wages', 'temporary_billing', 'site_cash', 'purchases', 'cash_transfer', 'material_usage', 'stock_transfer',
                'warehouse', 'reports', 'reports_labour_allocations', 'reports_daily_expenses', 'reports_cash_handling',
                'reports_purchases_inward', 'reports_warehouse_inventory', 'reports_stock_transfer', 'profile',
            ], true),
            self::AccountManager => in_array($menu, [
                'dashboard', 'banks', 'branches', 'properties', 'property_types', 'rentals',
                'transaction_types', 'suppliers', 'ledger', 'admin_ledger', 'temporary_billing', 'cash_transfer', 'cash_transfer_settle', 'reports', 'reports_ledger', 'profile',
            ], true),
            default => false,
        };
    }
}
