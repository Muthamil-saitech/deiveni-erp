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
        Schema::table('site_cashes', function (Blueprint $table) {
            $table->foreignId('weekly_wage_id')
                ->nullable()
                ->after('ledger_id')
                ->constrained('weekly_wages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_cashes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('weekly_wage_id');
        });
    }
};
