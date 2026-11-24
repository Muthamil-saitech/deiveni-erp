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
        if (! Schema::hasTable('admin_ledgers')) {
            Schema::create('admin_ledgers', function (Blueprint $table) {
                $table->id();
                $table->date('entry_date');
                $table->string('category', 50); // 'supplier_payment' or 'client_payment'
                $table->string('flow', 20); // 'expense' or 'income'
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->string('client_name')->nullable();
                $table->foreignId('project_site_id')->nullable()->constrained('project_sites')->nullOnDelete();
                $table->string('site_name')->nullable();
                $table->boolean('is_site_based')->default(true);
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('supplier_name')->nullable();
                $table->string('purchase_order_no')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('payment_mode', 50)->default('Cash');
                $table->string('payment_reference')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('cheque_no')->nullable();
                $table->text('description')->nullable();
                $table->string('receipt_image')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();

                $table->index(['entry_date', 'category']);
                $table->index('client_id');
                $table->index('project_site_id');
                $table->index('supplier_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_ledgers');
    }
};
