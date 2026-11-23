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
        if (Schema::hasTable('warehouse_items')) {
            Schema::table('warehouse_items', function (Blueprint $table) {
                if (! Schema::hasColumn('warehouse_items', 'cash_amount')) {
                    $table->decimal('cash_amount', 15, 2)->default(0.00)->after('unit_rate');
                }
            });

            // Backfill existing cash_amount from unit_rate
            DB::table('warehouse_items')->update([
                'cash_amount' => DB::raw('unit_rate'),
            ]);
        }

        if (Schema::hasTable('warehouse_movements')) {
            Schema::table('warehouse_movements', function (Blueprint $table) {
                if (! Schema::hasColumn('warehouse_movements', 'cash_amount')) {
                    $table->decimal('cash_amount', 15, 2)->default(0.00)->after('quantity');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('warehouse_movements')) {
            Schema::table('warehouse_movements', function (Blueprint $table) {
                if (Schema::hasColumn('warehouse_movements', 'cash_amount')) {
                    $table->dropColumn('cash_amount');
                }
            });
        }

        if (Schema::hasTable('warehouse_items')) {
            Schema::table('warehouse_items', function (Blueprint $table) {
                if (Schema::hasColumn('warehouse_items', 'cash_amount')) {
                    $table->dropColumn('cash_amount');
                }
            });
        }
    }
};
