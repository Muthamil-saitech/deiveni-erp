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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 30)->unique();
            $table->date('transfer_date');
            $table->foreignId('from_project_site_id')->constrained('project_sites')->cascadeOnDelete();
            $table->foreignId('to_project_site_id')->constrained('project_sites')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('transfer_type', 20)->default('TRANSFER');
            $table->foreignId('parent_transfer_id')->nullable()->constrained('stock_transfers')->nullOnDelete();
            $table->boolean('return_expected')->default(true);
            $table->string('return_status', 30)->default('Pending Return');
            $table->decimal('returned_quantity', 12, 2)->default(0.00);
            $table->string('vehicle_no', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->string('challan_no', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 30)->default('Completed');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['from_project_site_id', 'transfer_date']);
            $table->index(['to_project_site_id', 'transfer_date']);
            $table->index(['material_id']);
            $table->index(['return_status']);
        });

        // Assign 'stock_transfer' and 'reports_stock_transfer' permissions to owner and site manager roles
        if (Schema::hasTable('roles') && Schema::hasTable('role_menus')) {
            $roles = Role::whereIn('slug', ['owner', 'admin', 'site-manager', 'site_manager'])->get();
            foreach ($roles as $role) {
                RoleMenu::firstOrCreate([
                    'role_id' => $role->id,
                    'menu_key' => 'stock_transfer',
                ]);
                RoleMenu::firstOrCreate([
                    'role_id' => $role->id,
                    'menu_key' => 'reports_stock_transfer',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('role_menus')) {
            RoleMenu::whereIn('menu_key', ['stock_transfer', 'reports_stock_transfer'])->delete();
        }

        Schema::dropIfExists('stock_transfers');
    }
};
