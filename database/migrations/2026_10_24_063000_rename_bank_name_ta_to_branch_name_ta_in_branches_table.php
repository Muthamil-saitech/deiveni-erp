<?php

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
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'bank_name_ta') && ! Schema::hasColumn('branches', 'branch_name_ta')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->renameColumn('bank_name_ta', 'branch_name_ta');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branches') && Schema::hasColumn('branches', 'branch_name_ta') && ! Schema::hasColumn('branches', 'bank_name_ta')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->renameColumn('branch_name_ta', 'bank_name_ta');
            });
        }
    }
};
