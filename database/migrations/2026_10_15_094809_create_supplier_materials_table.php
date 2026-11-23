<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('suppliers', 'materials_supplied')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('materials_supplied');
            });
        }

        Schema::create('supplier_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['supplier_id', 'material_id']);
        });

        // Seed initial supplier materials if suppliers and materials exist
        $now = now();
        $supplier1 = DB::table('suppliers')->where('id', 1)->first();
        $mat1 = DB::table('materials')->where('id', 1)->first();

        if ($supplier1 && $mat1) {
            DB::table('supplier_materials')->insert([
                ['supplier_id' => 1, 'material_id' => 2, 'price' => 8500.00, 'created_at' => $now, 'updated_at' => $now],
                ['supplier_id' => 1, 'material_id' => 3, 'price' => 6200.00, 'created_at' => $now, 'updated_at' => $now],
                ['supplier_id' => 2, 'material_id' => 1, 'price' => 410.00, 'created_at' => $now, 'updated_at' => $now],
                ['supplier_id' => 3, 'material_id' => 4, 'price' => 68.00, 'created_at' => $now, 'updated_at' => $now],
                ['supplier_id' => 3, 'material_id' => 8, 'price' => 90.00, 'created_at' => $now, 'updated_at' => $now],
                ['supplier_id' => 5, 'material_id' => 6, 'price' => 145.00, 'created_at' => $now, 'updated_at' => $now],
                ['supplier_id' => 6, 'material_id' => 5, 'price' => 11.50, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_materials');

        if (! Schema::hasColumn('suppliers', 'materials_supplied')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->string('materials_supplied', 500)->nullable();
            });
        }
    }
};
