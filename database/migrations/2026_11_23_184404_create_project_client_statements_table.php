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
        if (! Schema::hasTable('project_client_statements')) {
            Schema::create('project_client_statements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
                $table->string('statement_number', 50)->unique();
                $table->date('statement_date');
                $table->string('title', 255)->default('Client Expense Statement');
                $table->string('client_name', 255)->nullable();
                $table->string('client_phone', 50)->nullable();
                $table->string('client_address', 255)->nullable();
                $table->json('items')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->decimal('tax_percent', 5, 2)->default(0.00);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->decimal('discount_amount', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->string('status', 50)->default('Generated');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['project_site_id', 'statement_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_client_statements');
    }
};
