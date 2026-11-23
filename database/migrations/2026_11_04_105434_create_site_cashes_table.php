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
        if (! Schema::hasTable('site_cashes')) {
            Schema::create('site_cashes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
                $table->string('transaction_type');
                $table->string('flow_type', 20)->default('outflow'); // 'inflow' or 'outflow'
                $table->decimal('amount', 15, 2)->default(0.00);
                $table->decimal('in_amount', 15, 2)->default(0.00);
                $table->decimal('out_amount', 15, 2)->default(0.00);
                $table->boolean('is_expense')->default(false); // true for site expenses (used to update project_sites.spent)
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('supplier_name')->nullable();
                $table->string('purchase_order_no')->nullable();
                $table->string('bill_no')->nullable();
                $table->date('payment_date');
                $table->string('payment_time', 20)->nullable();
                $table->text('description')->nullable();
                $table->string('receipt_image')->nullable();
                $table->json('items')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['project_site_id', 'is_expense']);
                $table->index('payment_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_cashes');
    }
};
