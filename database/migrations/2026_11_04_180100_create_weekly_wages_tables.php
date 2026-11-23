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
        if (! Schema::hasTable('weekly_wages')) {
            Schema::create('weekly_wages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
                $table->date('week_start_date');
                $table->date('week_end_date');
                $table->integer('total_labour_man_days')->default(0);
                $table->decimal('total_labour_wages', 15, 2)->default(0.00);
                $table->decimal('total_daily_expenses', 15, 2)->default(0.00);
                $table->decimal('total_weekly_expenses', 15, 2)->default(0.00);
                $table->decimal('net_payout', 15, 2)->default(0.00);
                $table->timestamps();

                $table->unique(['project_site_id', 'week_start_date', 'week_end_date'], 'ww_site_week_unique');
                $table->index(['week_start_date', 'week_end_date']);
            });
        }

        if (! Schema::hasTable('weekly_wage_expenses')) {
            Schema::create('weekly_wage_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('weekly_wage_id')->constrained('weekly_wages')->cascadeOnDelete();
                $table->string('item_name');
                $table->decimal('quantity', 10, 2)->default(1.00);
                $table->decimal('unit_price', 15, 2)->default(0.00);
                $table->decimal('amount', 15, 2)->default(0.00);
                $table->timestamps();

                $table->index('weekly_wage_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_wage_expenses');
        Schema::dropIfExists('weekly_wages');
    }
};
