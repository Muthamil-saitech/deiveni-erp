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
        if (! Schema::hasTable('daily_allocations')) {
            Schema::create('daily_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
                $table->date('date');
                $table->integer('total_labour_count')->default(0);
                $table->decimal('total_labour_wage', 15, 2)->default(0.00);
                $table->decimal('total_expenses', 15, 2)->default(0.00);
                $table->decimal('grand_total', 15, 2)->default(0.00);
                $table->text('trade_summary')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['project_site_id', 'date']);
                $table->index('date');
            });
        }

        if (! Schema::hasTable('daily_allocation_items')) {
            Schema::create('daily_allocation_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_allocation_id')->constrained('daily_allocations')->cascadeOnDelete();
                $table->foreignId('worker_category_id')->nullable()->constrained('worker_categories')->nullOnDelete();
                $table->string('category_code', 50)->nullable();
                $table->string('name_ta');
                $table->string('name_en')->nullable();
                $table->decimal('daily_wage', 15, 2)->default(0.00);
                $table->decimal('master_wage', 15, 2)->default(0.00);
                $table->integer('worker_count')->default(0);
                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->string('paid_by', 50)->nullable();
                $table->unsignedBigInteger('ledger_id')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('wage_cash_source', 30)->default('office_cash')->nullable();
                $table->timestamps();

                $table->index('daily_allocation_id');
            });
        }

        if (! Schema::hasTable('daily_allocation_expenses')) {
            Schema::create('daily_allocation_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_allocation_id')->constrained('daily_allocations')->cascadeOnDelete();
                $table->string('item_name');
                $table->decimal('quantity', 10, 2)->default(1.00);
                $table->decimal('unit_price', 15, 2)->default(0.00);
                $table->decimal('amount', 15, 2)->default(0.00);
                $table->timestamps();

                $table->index('daily_allocation_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_allocation_expenses');
        Schema::dropIfExists('daily_allocation_items');
        Schema::dropIfExists('daily_allocations');
    }
};
