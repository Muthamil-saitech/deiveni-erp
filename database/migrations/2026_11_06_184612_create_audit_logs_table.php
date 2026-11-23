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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 40)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 120);
            $table->string('user_role', 60)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('module', 50)->index();
            $table->string('target_type', 120)->nullable();
            $table->string('target_id', 80)->nullable()->index();
            $table->string('action', 40)->index();
            $table->string('severity', 20)->default('info')->index();
            $table->text('title');
            $table->text('title_ta')->nullable();
            $table->string('site_name', 150)->nullable()->index();
            $table->decimal('amount', 15, 2)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('details')->nullable();
            $table->string('integrity_hash', 100)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
