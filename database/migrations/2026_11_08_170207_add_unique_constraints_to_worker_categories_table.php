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
        Schema::table('worker_categories', function (Blueprint $table) {
            $table->unique('name_ta');
            $table->unique('name_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_categories', function (Blueprint $table) {
            $table->dropUnique(['name_ta']);
            $table->dropUnique(['name_en']);
        });
    }
};
