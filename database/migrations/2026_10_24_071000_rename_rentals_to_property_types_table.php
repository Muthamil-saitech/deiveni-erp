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
        if (Schema::hasTable('rentals') && ! Schema::hasTable('property_types')) {
            Schema::rename('rentals', 'property_types');
        }

        if (Schema::hasTable('property_types')) {
            Schema::table('property_types', function (Blueprint $table) {
                if (! Schema::hasColumn('property_types', 'floor_type')) {
                    $table->string('floor_type')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('property_types')) {
            Schema::table('property_types', function (Blueprint $table) {
                if (Schema::hasColumn('property_types', 'floor_type')) {
                    $table->dropColumn('floor_type');
                }
            });
        }

        if (Schema::hasTable('property_types') && ! Schema::hasTable('rentals')) {
            Schema::rename('property_types', 'rentals');
        }
    }
};
