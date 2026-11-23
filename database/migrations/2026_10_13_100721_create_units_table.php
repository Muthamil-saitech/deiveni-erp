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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ta');
            $table->string('code')->unique();
            $table->string('usage')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        $now = now();

        DB::table('units')->insert([
            [
                'name_en' => 'Bags',
                'name_ta' => 'மூட்டை',
                'code' => 'BAG',
                'usage' => 'Cement (50 Kg), Tile Adhesive',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Kilograms (Kg)',
                'name_ta' => 'கிலோகிராம்',
                'code' => 'KG',
                'usage' => 'TMT 550D Steel, Binding Wire, Hardware Nails',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Litres',
                'name_ta' => 'லிட்டர்',
                'code' => 'LTR',
                'usage' => 'Asian Paints, Exterior Primer, Chemical Cans',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Load (Truck Load)',
                'name_ta' => 'லோடு (2 யூனிட்)',
                'code' => 'LOAD',
                'usage' => 'M-Sand, Plastering Sand, Blue Metal Aggregate',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Brass (100 Cu.Ft)',
                'name_ta' => 'பிராஸ்',
                'code' => 'BRASS',
                'usage' => 'Basement Filling Soil, Quarry Dust',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Nos / Pieces',
                'name_ta' => 'எண்கள் / பீஸ்',
                'code' => 'NOS',
                'usage' => 'Red Bricks (1000 Nos), PVC Pipes 10ft, Switches',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Metric Ton (Ton)',
                'name_ta' => 'டன்',
                'code' => 'TON',
                'usage' => 'Bulk TMT Steel bundles',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Square Feet (Sq.Ft)',
                'name_ta' => 'சதுர அடி',
                'code' => 'SQFT',
                'usage' => 'Vitrified Floor Tiles, Granite Slabs',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Running Feet (RFT)',
                'name_ta' => 'நீள அடி',
                'code' => 'RFT',
                'usage' => 'SS Balcony Handrails, PVC Drainage Line',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Day / Shift',
                'name_ta' => 'நாள் / ஷிப்ட்',
                'code' => 'DAY',
                'usage' => 'Rental Mixer Machine, Needle Vibrator, Scaffoldings',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
