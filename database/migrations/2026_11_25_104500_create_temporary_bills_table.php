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
        Schema::create('temporary_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
            $table->string('bill_number')->unique();
            $table->date('bill_date');
            $table->string('title')->nullable();
            $table->string('trade_filter', 50)->default('all');
            $table->json('items');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_bills');
    }
};
