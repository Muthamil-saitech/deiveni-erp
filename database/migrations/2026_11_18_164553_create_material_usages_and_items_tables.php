<?php

use App\Models\Role;
use App\Models\RoleMenu;
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
        Schema::create('material_usages', function (Blueprint $table) {
            $table->id();
            $table->string('usage_no', 30)->unique();
            $table->date('usage_date');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
            $table->string('work_stage', 100)->nullable();
            $table->string('supervisor_name', 100)->nullable();
            $table->integer('total_items_count')->default(1);
            $table->decimal('total_used_quantity', 12, 2)->default(0.00);
            $table->decimal('total_cost', 14, 2)->default(0.00);
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('Recorded');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['project_site_id', 'usage_date']);
            $table->index(['purchase_order_id']);
        });

        Schema::create('material_usage_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_usage_id')->constrained('material_usages')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('material_name', 255);
            $table->string('source_type', 50)->default('PO'); // 'PO', 'Own Warehouse'
            $table->string('source_reference', 100)->nullable(); // e.g. PO-2026-001
            $table->decimal('inward_quantity', 12, 2)->default(0.00);
            $table->decimal('used_quantity', 12, 2);
            $table->decimal('remaining_quantity', 12, 2)->default(0.00);
            $table->string('unit', 50)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('total_price', 14, 2)->default(0.00);
            $table->timestamps();

            $table->index(['material_usage_id']);
            $table->index(['material_id']);
            $table->index(['purchase_order_item_id']);
        });

        // Assign 'material_usage' permission to owner, admin and site manager roles
        if (Schema::hasTable('roles') && Schema::hasTable('role_menus')) {
            $roles = Role::whereIn('slug', ['owner', 'admin', 'site-manager', 'site_manager'])->get();
            foreach ($roles as $role) {
                RoleMenu::firstOrCreate([
                    'role_id' => $role->id,
                    'menu_key' => 'material_usage',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_usage_items');
        Schema::dropIfExists('material_usages');
    }
};
