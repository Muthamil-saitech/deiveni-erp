<?php

use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('menu_key');
            $table->timestamps();
            $table->unique(['role_id', 'menu_key']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->nullOnDelete();
        });

        // Seed default initial roles & menu access
        $allMenuKeys = array_keys(config('menus.items', []));

        $defaultRoles = [
            [
                'name' => 'Owner',
                'slug' => 'owner',
                'description' => 'System Owner with full administrative access across all modules.',
                'is_system' => true,
                'menus' => $allMenuKeys,
            ],
            [
                'name' => 'Site Manager',
                'slug' => 'site_manager',
                'description' => 'Field operations, material requests, daily labour allocations, wages, and store inventory.',
                'is_system' => true,
                'menus' => [
                    'dashboard',
                    'worker_categories',
                    'units',
                    'materials',
                    'suppliers',
                    'sites',
                    'daily_allocation',
                    'weekly_wages',
                    'site_cash',
                    'purchases',
                    'warehouse',
                    'reports',
                    'profile',
                ],
            ],
            [
                'name' => 'Account Manager',
                'slug' => 'account_manager',
                'description' => 'Financial operations, daily ledgers, banks, property rental masters, and supplier ledgers.',
                'is_system' => true,
                'menus' => [
                    'dashboard',
                    'banks',
                    'branches',
                    'properties',
                    'property_types',
                    'suppliers',
                    'ledger',
                    'reports',
                    'profile',
                ],
            ],
        ];

        foreach ($defaultRoles as $roleData) {
            $role = Role::create([
                'name' => $roleData['name'],
                'slug' => $roleData['slug'],
                'description' => $roleData['description'],
                'is_system' => $roleData['is_system'],
            ]);

            foreach ($roleData['menus'] as $menuKey) {
                RoleMenu::create([
                    'role_id' => $role->id,
                    'menu_key' => $menuKey,
                ]);
            }
        }

        // Link existing users to their corresponding roles
        $ownerRole = Role::where('slug', 'owner')->first();
        $smRole = Role::where('slug', 'site_manager')->first();
        $amRole = Role::where('slug', 'account_manager')->first();

        if ($ownerRole) {
            User::whereIn('role', ['owner', 'admin'])->update(['role_id' => $ownerRole->id]);
        }
        if ($smRole) {
            User::where('role', 'site_manager')->update(['role_id' => $smRole->id]);
        }
        if ($amRole) {
            User::where('role', 'account_manager')->update(['role_id' => $amRole->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('roles');
    }
};
