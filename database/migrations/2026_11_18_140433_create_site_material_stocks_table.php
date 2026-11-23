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
        Schema::create('site_material_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('unit', 50)->nullable();
            $table->decimal('current_stock', 12, 2)->default(0.00);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['project_site_id', 'material_id'], 'site_material_stock_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_material_stocks');
    }
};
