<?php

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
        if (Schema::hasTable('banks') && ! Schema::hasTable('branches')) {
            Schema::rename('banks', 'branches');
        }

        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (Schema::hasColumn('branches', 'turnaround_time')) {
                    $table->dropColumn('turnaround_time');
                }
                if (DB::getDriverName() !== 'sqlite') {
                    $table->decimal('valuation_fee', 10, 2)->default(0)->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branches') && ! Schema::hasTable('banks')) {
            Schema::rename('branches', 'banks');
        }
    }
};
