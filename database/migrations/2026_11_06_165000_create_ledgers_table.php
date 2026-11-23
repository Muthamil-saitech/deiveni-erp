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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');
            $table->enum('flow', ['income', 'expense'])->default('income');
            $table->string('source');
            $table->decimal('amount', 15, 2);

            // Construction specifics (Task 3)
            $table->foreignId('project_site_id')->nullable()->constrained('project_sites')->nullOnDelete();
            $table->string('site_name')->nullable();
            $table->string('client_name')->nullable();

            // Supplier specifics (Task 4)
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_name')->nullable();

            // Bank Valuation specifics
            $table->foreignId('bank_id')->nullable()->constrained('banks')->nullOnDelete();
            $table->string('bank_name')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('branch_name')->nullable();
            $table->string('bank_customer_ref')->nullable();

            // Rental specifics
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('property_name')->nullable();
            $table->foreignId('property_type_id')->nullable()->constrained('property_types')->nullOnDelete();
            $table->string('unit_name')->nullable();

            // Office and other expense specifics
            $table->string('office_expense_details')->nullable();

            // Relation with transaction type master (Task 5)
            $table->foreignId('transaction_type_id')->nullable()->constrained('transaction_types')->nullOnDelete();
            $table->string('transaction_type_name')->nullable();
            $table->text('transaction_type_value')->nullable();
            $table->json('transaction_type_data')->nullable();

            // Common / Narrative
            $table->text('description')->nullable();
            $table->string('receipt_image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
