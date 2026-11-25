<?php

use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('role_menus')) {
            return;
        }

        $roles = Role::whereIn('slug', ['owner', 'admin', 'site_manager', 'account_manager'])->get();
        foreach ($roles as $role) {
            RoleMenu::firstOrCreate([
                'role_id' => $role->id,
                'menu_key' => 'temporary_billing',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('role_menus')) {
            return;
        }

        RoleMenu::where('menu_key', 'temporary_billing')->delete();
    }
};
