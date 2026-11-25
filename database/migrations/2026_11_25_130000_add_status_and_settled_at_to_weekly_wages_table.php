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
        Schema::table('weekly_wages', function (Blueprint $table) {
            if (! Schema::hasColumn('weekly_wages', 'status')) {
                $table->string('status', 20)->default('draft')->after('net_payout')->index();
            }
            if (! Schema::hasColumn('weekly_wages', 'settled_at')) {
                $table->timestamp('settled_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_wages', function (Blueprint $table) {
            if (Schema::hasColumn('weekly_wages', 'settled_at')) {
                $table->dropColumn('settled_at');
            }
            if (Schema::hasColumn('weekly_wages', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
