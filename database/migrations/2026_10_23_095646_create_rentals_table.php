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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->string('property_name');
            $table->string('shop_home_office_name')->nullable();
            $table->string('property_type');
            $table->string('location');
            $table->string('tenant_name')->nullable();
            $table->string('tenant_phone')->nullable();
            $table->decimal('monthly_rent', 12, 2)->default(0);
            $table->decimal('security_deposit', 12, 2)->default(0);
            $table->string('rent_due_day')->default('1st of Month');
            $table->string('status')->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
