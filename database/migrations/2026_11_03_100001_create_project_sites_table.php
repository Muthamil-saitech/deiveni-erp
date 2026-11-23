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
        if (! Schema::hasTable('project_sites')) {
            Schema::create('project_sites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
                $table->string('name');
                $table->string('location');
                $table->string('work_type');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('estimated_budget', 15, 2)->default(0.00);
                $table->decimal('spent', 15, 2)->default(0.00);
                $table->string('status')->default('In Progress');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_sites');
    }
};
