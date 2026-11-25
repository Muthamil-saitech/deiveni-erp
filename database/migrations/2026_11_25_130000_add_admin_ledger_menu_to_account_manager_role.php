<?php

use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = Role::where('slug', 'account_manager')->first();

        if ($role && ! $role->hasMenu('admin_ledger')) {
            RoleMenu::create([
                'role_id' => $role->id,
                'menu_key' => 'admin_ledger',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = Role::where('slug', 'account_manager')->first();

        if ($role) {
            RoleMenu::where('role_id', $role->id)
                ->where('menu_key', 'admin_ledger')
                ->delete();
        }
    }
};
