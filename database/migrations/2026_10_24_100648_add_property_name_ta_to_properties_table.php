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
        if (Schema::hasTable('properties') && ! Schema::hasColumn('properties', 'property_name_ta')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->string('property_name_ta')->nullable()->after('property_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('properties') && Schema::hasColumn('properties', 'property_name_ta')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropColumn('property_name_ta');
            });
        }
    }
};
