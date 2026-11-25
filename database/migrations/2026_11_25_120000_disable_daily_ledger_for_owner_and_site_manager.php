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
        // Remove 'ledger' permission from Owner and Site Manager roles
        $targetRoles = Role::whereIn('slug', ['owner', 'admin', 'site_manager', 'site-manager'])
            ->orWhere('name', 'Owner')
            ->orWhere('name', 'Site Manager')
            ->pluck('id');

        if ($targetRoles->isNotEmpty()) {
            RoleMenu::whereIn('role_id', $targetRoles)
                ->where('menu_key', 'ledger')
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-assign 'ledger' to Owner role if rolled back
        $ownerRole = Role::where('slug', 'owner')->first();
        if ($ownerRole) {
            RoleMenu::firstOrCreate([
                'role_id' => $ownerRole->id,
                'menu_key' => 'ledger',
            ]);
        }
    }
};
