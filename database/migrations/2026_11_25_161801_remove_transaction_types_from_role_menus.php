<?php

use App\Models\RoleMenu;
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
        Schema::table('role_menus', function (Blueprint $table) {
            RoleMenu::where('menu_key', 'transaction_types')->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_menus', function (Blueprint $table) {
            // The removed permission is intentionally not restored on rollback.
        });
    }
};
