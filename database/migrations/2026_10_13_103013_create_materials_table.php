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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ta');
            $table->string('unit');
            $table->decimal('rate', 12, 2);
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        $now = now();

        DB::table('materials')->insert([
            [
                'name_en' => 'Cement (OPC 53 Grade)',
                'name_ta' => 'சிமெண்ட் (50 கிகி மூட்டை)',
                'unit' => 'Bags',
                'rate' => 410.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'M-Sand (Manufactured Sand)',
                'name_ta' => 'எம்-சாண்ட் (கட்டுமான மணல்)',
                'unit' => 'Load',
                'rate' => 8500.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => '20mm Blue Metal Aggregate',
                'name_ta' => '20mm ஜல்லி கற்கள்',
                'unit' => 'Load',
                'rate' => 6200.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'TMT 550D Steel Rods (12mm)',
                'name_ta' => 'TMT கம்பிகள் (கட்டுமானம்)',
                'unit' => 'Kg',
                'rate' => 68.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Red Bricks (1st Quality Chamber)',
                'name_ta' => 'செங்கல் (சாம்பர் செங்கல்)',
                'unit' => 'Nos',
                'rate' => 11.50,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Asian Paints Exterior Wall Primer',
                'name_ta' => 'பெயிண்ட் பிரைமர் (20L கேன்)',
                'unit' => 'Litres',
                'rate' => 145.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'PVC Pipe 4 inch 10ft (Finolex)',
                'name_ta' => 'பிவிசி பைப் (10 அடி)',
                'unit' => 'Nos',
                'rate' => 380.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Binding Wire (GI Annealed)',
                'name_ta' => 'பைண்டிங் கம்பி (ரோல்)',
                'unit' => 'Kg',
                'rate' => 90.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Concrete Mixer Machine (1 Bag)',
                'name_ta' => 'கான்கிரீட் மிக்சர் இயந்திரம்',
                'unit' => 'Day',
                'rate' => 1500.00,
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_en' => 'Needle Vibrator 2HP (Concrete)',
                'name_ta' => 'கான்கிரீட் வைப்ரேட்டர்',
                'unit' => 'Day',
                'rate' => 600.00,
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
        Schema::dropIfExists('materials');
    }
};
