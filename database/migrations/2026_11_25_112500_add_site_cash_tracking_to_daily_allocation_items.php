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
        Schema::table('daily_allocation_items', function (Blueprint $table) {
            $table->foreignId('site_cash_id')
                ->nullable()
                ->after('ledger_id')
                ->constrained('site_cashes')
                ->nullOnDelete();
            $table->boolean('referred_to_am')
                ->default(false)
                ->after('site_cash_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_allocation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_cash_id');
            $table->dropColumn('referred_to_am');
        });
    }
};
