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
                $table->string('payment_mode')->nullable();
                $table->string('payment_reference')->nullable();
                $table->string('paid_to')->nullable();
                $table->string('paid_by')->nullable();
                $table->string('receiver_name')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('upi_id')->nullable();
                $table->string('account_holder_name')->nullable();
                $table->unsignedBigInteger('ledger_id')->nullable();
                $table->unsignedBigInteger('cash_transfer_id')->nullable();
                $table->string('wage_cash_source')->nullable();
                $table->unsignedBigInteger('return_site_cash_id')->nullable();
                $table->json('extra_workers')->nullable();
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
