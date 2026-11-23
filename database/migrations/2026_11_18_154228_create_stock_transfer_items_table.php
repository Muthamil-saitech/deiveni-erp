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
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('material_id')->nullable()->change();
            $table->decimal('quantity', 12, 2)->nullable()->change();
            $table->decimal('total_amount', 14, 2)->default(0.00)->after('returned_quantity');
            $table->integer('total_items_count')->default(1)->after('total_amount');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('material_name', 255);
            $table->string('source_type', 50)->default('PO'); // 'PO', 'Own Warehouse', 'Site Stock'
            $table->string('source_reference', 100)->nullable(); // e.g. PO-2026-001 or WH-Item #
            $table->string('supplier_name', 255)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 50)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('total_price', 14, 2)->default(0.00);
            $table->decimal('returned_quantity', 12, 2)->default(0.00);
            $table->string('return_status', 30)->default('Pending Return');
            $table->timestamps();

            $table->index(['stock_transfer_id']);
            $table->index(['material_id']);
            $table->index(['source_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'total_items_count']);
        });
    }
};
