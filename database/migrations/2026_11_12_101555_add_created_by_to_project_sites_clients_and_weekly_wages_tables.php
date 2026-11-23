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
        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'created_by')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('address')->constrained('users')->nullOnDelete();
                $table->index('created_by');
            });
        }

        if (Schema::hasTable('project_sites') && ! Schema::hasColumn('project_sites', 'created_by')) {
            Schema::table('project_sites', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                $table->index('created_by');
            });
        }

        if (Schema::hasTable('weekly_wages') && ! Schema::hasColumn('weekly_wages', 'created_by')) {
            Schema::table('weekly_wages', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('net_payout')->constrained('users')->nullOnDelete();
                $table->index('created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('weekly_wages') && Schema::hasColumn('weekly_wages', 'created_by')) {
            Schema::table('weekly_wages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasTable('project_sites') && Schema::hasColumn('project_sites', 'created_by')) {
            Schema::table('project_sites', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'created_by')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
