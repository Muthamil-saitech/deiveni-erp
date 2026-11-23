<?php

use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('category');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });

        // Seed initial default categories
        $defaultTypes = [
            [
                'name_en' => 'Advance Received',
                'category' => 'Construction',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Client Collection',
                'category' => 'Construction',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Material Purchase',
                'category' => 'Supplier',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Supplier Payment',
                'category' => 'Supplier',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Daily Wage Payment',
                'category' => 'Office and other expenses',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Balance Returned',
                'category' => 'Office and other expenses',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Bank Valuation',
                'category' => 'Bank Valuation',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Rental',
                'category' => 'Rental',
                'status' => 'Active',
            ],
            [
                'name_en' => 'Office and other expenses',
                'category' => 'Office and other expenses',
                'status' => 'Active',
            ],
        ];

        $now = now();
        foreach ($defaultTypes as $type) {
            DB::table('transaction_types')->insert(array_merge($type, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        // Assign 'transaction_types' permission to existing roles in role_menus
        if (Schema::hasTable('roles') && Schema::hasTable('role_menus')) {
            $roles = Role::whereIn('slug', ['owner', 'site-manager', 'site_manager', 'account-manager', 'account_manager'])->get();
            foreach ($roles as $role) {
                RoleMenu::firstOrCreate([
                    'role_id' => $role->id,
                    'menu_key' => 'transaction_types',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_menus')) {
            RoleMenu::where('menu_key', 'transaction_types')->delete();
        }

        Schema::dropIfExists('transaction_types');
    }
};
