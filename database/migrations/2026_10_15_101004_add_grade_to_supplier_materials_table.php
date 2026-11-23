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
        Schema::table('supplier_materials', function (Blueprint $table) {
            $table->string('grade', 50)->default('1st Quality')->after('material_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_materials', function (Blueprint $table) {
            $table->dropColumn('grade');
        });
    }
};
