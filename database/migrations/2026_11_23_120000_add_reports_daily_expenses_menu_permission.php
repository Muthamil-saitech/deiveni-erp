<?php

use App\Models\RoleMenu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('roles') && Schema::hasTable('role_menus')) {
            $ownerRole = DB::table('roles')->where('slug', 'owner')->first();
            $smRole = DB::table('roles')->where('slug', 'site-manager')->orWhere('slug', 'site_manager')->first();
            $adminRole = DB::table('roles')->where('slug', 'admin')->first();

            if ($ownerRole) {
                RoleMenu::firstOrCreate([
                    'role_id' => $ownerRole->id,
                    'menu_key' => 'reports_daily_expenses',
                ]);
            }

            if ($smRole) {
                RoleMenu::firstOrCreate([
                    'role_id' => $smRole->id,
                    'menu_key' => 'reports_daily_expenses',
                ]);
            }

            if ($adminRole) {
                RoleMenu::firstOrCreate([
                    'role_id' => $adminRole->id,
                    'menu_key' => 'reports_daily_expenses',
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
            RoleMenu::where('menu_key', 'reports_daily_expenses')->delete();
        }
    }
};
