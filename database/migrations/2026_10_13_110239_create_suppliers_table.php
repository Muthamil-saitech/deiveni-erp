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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone');
            $table->string('address', 500)->nullable();
            $table->string('gst_number', 50)->nullable();
            $table->string('materials_supplied', 500)->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        $now = now();

        DB::table('suppliers')->insert([
            [
                'name' => 'Sri Balaji Blue Metals & M-Sand',
                'contact_person' => 'Bala Murugan',
                'phone' => '94441 11222',
                'address' => 'Kundrathur Main Road, Chennai - 69',
                'gst_number' => '33AAAAA0000A1Z5',
                'materials_supplied' => 'M-Sand, Blue Metal, Gravel, Dust',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Chettinad Cement Agency',
                'contact_person' => 'R. Sundaram',
                'phone' => '94442 22333',
                'address' => 'GST Road, Tambaram Sanatorium',
                'gst_number' => '33BBBBB1111B2Z6',
                'materials_supplied' => 'Cement, White Cement, Waterproofing',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Jindal Steels & Hardware',
                'contact_person' => 'K. Praveen',
                'phone' => '94443 33444',
                'address' => 'Industrial Estate, Guindy, Chennai',
                'gst_number' => '33CCCCC2222C3Z7',
                'materials_supplied' => 'TMT Steel Rods, Binding Wire, Structural Steel',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Kumaran Timber & Plywoods',
                'contact_person' => 'M. Kumaran',
                'phone' => '94444 44555',
                'address' => 'Station Road, Chromepet',
                'gst_number' => '33DDDDD3333D4Z8',
                'materials_supplied' => 'Centering Sheets, Plywood, Teak Wood',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Sri Krishna Paint World',
                'contact_person' => 'V. Krishna',
                'phone' => '94445 55666',
                'address' => 'Velachery Main Road, Medavakkam',
                'gst_number' => '33EEEEE4444E5Z9',
                'materials_supplied' => 'Asian Paints, Primer, Wall Putty, Brushes',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Kovai Bricks & Chambers',
                'contact_person' => 'P. Kandasamy',
                'phone' => '94447 77888',
                'address' => 'Poonamallee High Road, Maduravoyal',
                'gst_number' => '33FFFFF5555F6Z1',
                'materials_supplied' => 'Red Bricks, Solid Concrete Blocks',
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
        Schema::dropIfExists('suppliers');
    }
};
