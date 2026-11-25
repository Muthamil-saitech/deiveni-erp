<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure default roles exist
        $ownerRole = Role::where('name', 'Owner')->orWhere('slug', 'owner')->first() ?? Role::create([
            'name' => 'Owner',
            'slug' => 'owner',
            'description' => 'System Owner with full administrative access across all modules.',
            'is_system' => true,
        ]);
        $smRole = Role::where('name', 'Site Manager')->orWhere('slug', 'site-manager')->orWhere('slug', 'site_manager')->first() ?? Role::create([
            'name' => 'Site Manager',
            'slug' => 'site-manager',
            'description' => 'Field operations, material requests, daily labour allocations, wages, and store inventory.',
            'is_system' => true,
        ]);
        $amRole = Role::where('name', 'Account Manager')->orWhere('slug', 'account-manager')->orWhere('slug', 'account_manager')->first() ?? Role::create([
            'name' => 'Account Manager',
            'slug' => 'account-manager',
            'description' => 'Financial operations, daily ledgers, banks, property rental masters, and supplier ledgers.',
            'is_system' => true,
        ]);

        // Ensure initial menu permissions (Daily Ledger is disabled for Owner & Site Manager)
        foreach (array_diff(array_keys(config('menus.items', [])), ['ledger']) as $key) {
            RoleMenu::firstOrCreate(['role_id' => $ownerRole->id, 'menu_key' => $key]);
        }

        $smMenus = [
            'dashboard', 'worker_categories', 'units', 'materials', 'suppliers',
            'sites', 'daily_allocation', 'weekly_wages', 'site_cash', 'purchases',
            'warehouse', 'reports', 'reports_labour_allocations', 'reports_daily_expenses', 'reports_cash_handling',
            'reports_purchases_inward', 'reports_warehouse_inventory', 'reports_stock_transfer', 'profile',
        ];
        foreach ($smMenus as $key) {
            RoleMenu::firstOrCreate(['role_id' => $smRole->id, 'menu_key' => $key]);
        }

        $amMenus = [
            'dashboard', 'banks', 'branches', 'properties', 'property_types',
            'suppliers', 'ledger', 'reports', 'reports_ledger', 'profile',
        ];
        foreach ($amMenus as $key) {
            RoleMenu::firstOrCreate(['role_id' => $amRole->id, 'menu_key' => $key]);
        }

        $users = [
            [
                'name' => 'K. Deiveni',
                'email' => 'owner@deiveni.com',
                'role_id' => $ownerRole->id,
                'role' => 'owner',
                'phone' => '98400 11111',
                'status' => 'Active',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Murugan SM',
                'email' => 'sm@deiveni.com',
                'role_id' => $smRole->id,
                'role' => 'site_manager',
                'phone' => '98400 22222',
                'status' => 'Active',
                'password' => Hash::make('password123'),
            ],
            [
                'name' => 'Raja AM',
                'email' => 'am@deiveni.com',
                'role_id' => $amRole->id,
                'role' => 'account_manager',
                'phone' => '98400 33333',
                'status' => 'Active',
                'password' => Hash::make('password123'),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }
    }
}
