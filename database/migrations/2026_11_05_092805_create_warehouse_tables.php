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
        if (! Schema::hasTable('warehouse_items')) {
            Schema::create('warehouse_items', function (Blueprint $table) {
                $table->id();
                $table->string('sku', 50)->unique();
                $table->string('name_en');
                $table->string('name_ta');
                $table->string('category', 100)->default('Raw Material');
                $table->string('unit', 50)->default('Nos');
                $table->decimal('current_stock', 12, 2)->default(0.00);
                $table->decimal('min_threshold', 12, 2)->default(0.00);
                $table->decimal('unit_rate', 15, 2)->default(0.00);
                $table->string('status', 20)->default('Active');
                $table->timestamps();

                $table->index(['status', 'category']);
                $table->index('sku');
            });
        }

        if (! Schema::hasTable('warehouse_movements')) {
            Schema::create('warehouse_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_item_id')->constrained('warehouse_items')->cascadeOnDelete();
                $table->string('movement_type', 50); // 'Issue to Site', 'Stock Inward', 'Adjustment'
                $table->decimal('quantity', 12, 2)->default(0.00);
                $table->foreignId('project_site_id')->nullable()->constrained('project_sites')->nullOnDelete();
                $table->string('site_name')->nullable();
                $table->string('reference_no')->nullable();
                $table->date('movement_date');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['warehouse_item_id', 'movement_type']);
                $table->index('movement_date');
            });
        }

        // Seed preset warehouse inventory items
        $now = now();
        $initialItems = [
            [
                'sku' => 'WH-MAT-01',
                'name_en' => 'Cement (OPC 53 Grade)',
                'name_ta' => 'சிமெண்ட் மூட்டை',
                'category' => 'Raw Material',
                'unit' => 'Bags',
                'current_stock' => 18,
                'min_threshold' => 30,
                'unit_rate' => 410.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-FIT-02',
                'name_en' => 'PVC Pipe 4 inch (10ft)',
                'name_ta' => 'பிவிசி பைப் 4 அங்குலம்',
                'category' => 'Plumbing',
                'unit' => 'Nos',
                'current_stock' => 4,
                'min_threshold' => 10,
                'unit_rate' => 380.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-TOOL-01',
                'name_en' => 'Mason Trowel & Leveller',
                'name_ta' => 'மேஸ்திரி கரண்டி & மட்டம்',
                'category' => 'Hand Tools',
                'unit' => 'Sets',
                'current_stock' => 24,
                'min_threshold' => 5,
                'unit_rate' => 500.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-TOOL-02',
                'name_en' => 'Spade & Iron Shovel',
                'name_ta' => 'மண்வெட்டி & சட்டி',
                'category' => 'Hand Tools',
                'unit' => 'Nos',
                'current_stock' => 35,
                'min_threshold' => 10,
                'unit_rate' => 250.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-SAFE-01',
                'name_en' => 'Safety Helmets & High-Vis Jackets',
                'name_ta' => 'பாதுகாப்பு கவசம்',
                'category' => 'Safety Gear',
                'unit' => 'Sets',
                'current_stock' => 40,
                'min_threshold' => 15,
                'unit_rate' => 450.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-CHEM-01',
                'name_en' => 'Dr. Fixit Waterproofing Chemical (20L)',
                'name_ta' => 'வாட்டர்ப்ரூப் திரவம்',
                'category' => 'Chemicals',
                'unit' => 'Cans (20L)',
                'current_stock' => 12,
                'min_threshold' => 4,
                'unit_rate' => 1850.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-TOOL-03',
                'name_en' => 'Measuring Tape 50m / 5m',
                'name_ta' => 'அளவு டேப்',
                'category' => 'Measuring Tools',
                'unit' => 'Nos',
                'current_stock' => 15,
                'min_threshold' => 3,
                'unit_rate' => 300.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'sku' => 'WH-ELEC-01',
                'name_en' => 'Demolition Breaker Power Tool',
                'name_ta' => 'மிஷன் உடைப்பு கருவி',
                'category' => 'Power Tools',
                'unit' => 'Units',
                'current_stock' => 2,
                'min_threshold' => 1,
                'unit_rate' => 22500.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($initialItems as $item) {
            DB::table('warehouse_items')->updateOrInsert(
                ['sku' => $item['sku']],
                $item
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_movements');
        Schema::dropIfExists('warehouse_items');
    }
};
