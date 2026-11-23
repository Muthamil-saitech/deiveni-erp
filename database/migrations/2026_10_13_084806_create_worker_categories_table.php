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
        Schema::create('worker_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name_ta');
            $table->string('name_en');
            $table->string('code')->unique();
            $table->decimal('daily_wage', 10, 2);
            $table->string('shift')->default('8 Hours (8:30 AM - 5:30 PM)');
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        $now = now();

        DB::table('worker_categories')->insert([
            [
                'name_ta' => 'மேஸ்திரி',
                'name_en' => 'Head Mason / Lead Mestri',
                'code' => 'CAT-001',
                'daily_wage' => 1200.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'கொத்தனார்',
                'name_en' => 'Brick Mason / Plasterer',
                'code' => 'CAT-002',
                'daily_wage' => 1100.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'சித்தாள் (ஆண்)',
                'name_en' => 'Male Labour Helper',
                'code' => 'CAT-003',
                'daily_wage' => 900.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'பெண் சித்தாள்',
                'name_en' => 'Female Labour Helper',
                'code' => 'CAT-004',
                'daily_wage' => 850.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'கம்பிகட்டுனர்',
                'name_en' => 'Bar Bender / Steel Worker',
                'code' => 'CAT-005',
                'daily_wage' => 1250.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'தச்சர் (சென்டரிங்)',
                'name_en' => 'Carpenter & Centering',
                'code' => 'CAT-006',
                'daily_wage' => 1350.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'பெயிண்டர்',
                'name_en' => 'Painter / Wall Finisher',
                'code' => 'CAT-007',
                'daily_wage' => 1100.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name_ta' => 'எலக்ட்ரீஷியன் / பிளம்பர்',
                'name_en' => 'Electrician & Plumbing Tech',
                'code' => 'CAT-008',
                'daily_wage' => 1300.00,
                'shift' => '8 Hours (8:30 AM - 5:30 PM)',
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
        Schema::dropIfExists('worker_categories');
    }
};
