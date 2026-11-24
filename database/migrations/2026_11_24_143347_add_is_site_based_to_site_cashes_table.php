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
        Schema::table('site_cashes', function (Blueprint $table) {
            $table->boolean('is_site_based')->default(true)->after('supplier_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_cashes', function (Blueprint $table) {
            $table->dropColumn('is_site_based');
        });
    }
};
