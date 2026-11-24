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
        if (! Schema::hasTable('cash_transfers')) {
            Schema::create('cash_transfers', function (Blueprint $table) {
                $table->id();
                $table->string('transfer_direction', 30)->index();
                $table->unsignedBigInteger('project_site_id')->nullable()->index();
                $table->date('transfer_date')->index();
                $table->string('transfer_time', 20)->nullable();
                $table->decimal('amount', 14, 2);
                $table->string('payment_mode', 30)->default('Cash');
                $table->string('payment_reference')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('handed_over_by');
                $table->string('received_by');
                $table->text('purpose')->nullable();
                $table->boolean('is_temporary')->default(true);
                $table->date('expected_return_date')->nullable();
                $table->string('settlement_status', 20)->default('pending')->index();
                $table->dateTime('settled_at')->nullable();
                $table->unsignedBigInteger('settled_by')->nullable();
                $table->text('settlement_notes')->nullable();
                $table->string('acceptance_status', 20)->default('accepted')->index();
                $table->dateTime('accepted_at')->nullable();
                $table->unsignedBigInteger('accepted_by')->nullable();
                $table->text('acceptance_notes')->nullable();
                $table->string('receipt_image')->nullable();
                $table->unsignedBigInteger('ledger_id')->nullable()->index();
                $table->unsignedBigInteger('site_cash_id')->nullable()->index();
                $table->unsignedBigInteger('return_site_cash_id')->nullable();
                $table->unsignedBigInteger('return_ledger_id')->nullable();
                $table->unsignedBigInteger('created_by')->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('ledgers')) {
            Schema::table('ledgers', function (Blueprint $table) {
                if (! Schema::hasColumn('ledgers', 'cash_transfer_id')) {
                    $table->unsignedBigInteger('cash_transfer_id')->nullable()->after('project_site_id');
                }
                if (! Schema::hasColumn('ledgers', 'wage_cash_source')) {
                    $table->string('wage_cash_source', 50)->nullable()->after('amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ledgers') && Schema::hasColumn('ledgers', 'cash_transfer_id')) {
            Schema::table('ledgers', function (Blueprint $table) {
                $table->dropColumn('cash_transfer_id');
            });
        }

        Schema::dropIfExists('cash_transfers');
    }
};
