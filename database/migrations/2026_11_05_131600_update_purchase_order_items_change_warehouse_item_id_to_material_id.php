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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'warehouse_item_id')) {
                $table->dropForeign(['warehouse_item_id']);
                $table->dropColumn('warehouse_item_id');
            }
            if (! Schema::hasColumn('purchase_order_items', 'material_id')) {
                $table->foreignId('material_id')->nullable()->after('purchase_order_id')->constrained('materials')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_items', 'material_id')) {
                $table->dropForeign(['material_id']);
                $table->dropColumn('material_id');
            }
            if (! Schema::hasColumn('purchase_order_items', 'warehouse_item_id')) {
                $table->foreignId('warehouse_item_id')->nullable()->after('purchase_order_id')->constrained('warehouse_items')->nullOnDelete();
            }
        });
    }
};
