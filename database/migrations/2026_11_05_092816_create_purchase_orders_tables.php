<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('po_number', 100)->unique();
                $table->date('order_date');
                $table->foreignId('project_site_id')->constrained('project_sites')->cascadeOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->string('supplier_name');
                $table->string('source_type', 50)->default('Third-Party Supplier'); // 'Third-Party Supplier', 'Own Warehouse'
                $table->string('status', 50)->default('Ordered'); // 'Ordered', 'Partial Received', 'Order Completed', 'Order Return', 'Order Canceled'
                $table->string('status_reason')->nullable();
                $table->string('delivery_status', 50)->default('Inward Pending'); // 'Delivered & Inwarded', 'Inward Pending'
                $table->date('delivery_date')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->decimal('tax_amount', 15, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->text('notes')->nullable();
                $table->string('receipt_image')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['project_site_id', 'status']);
                $table->index('order_date');
                $table->index('delivery_status');
            });
        }

        if (! Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('material_id')->nullable()->constrained('materials')->nullOnDelete();
                $table->string('material_name');
                $table->decimal('quantity', 12, 2)->default(1.00);
                $table->string('unit', 50)->default('Nos');
                $table->decimal('unit_price', 15, 2)->default(0.00);
                $table->decimal('total_price', 15, 2)->default(0.00);
                $table->timestamps();

                $table->index('purchase_order_id');
            });
        }

        // Seed initial purchases if project_sites exist
        $firstSite = DB::table('project_sites')->first();
        if ($firstSite) {
            $secondSite = DB::table('project_sites')->skip(1)->first() ?? $firstSite;
            $now = now();

            $seedOrders = [
                [
                    'po_number' => 'INV-2026-881',
                    'order_date' => '2026-10-10',
                    'project_site_id' => $firstSite->id,
                    'supplier_name' => 'Chettinad Cement Agency',
                    'source_type' => 'Third-Party Supplier',
                    'status' => 'Order Completed',
                    'delivery_status' => 'Delivered & Inwarded',
                    'delivery_date' => '2026-10-10',
                    'subtotal' => 82000.00,
                    'total_amount' => 82000.00,
                    'notes' => '200 Bags delivered directly to site for foundation slab casting.',
                    'items' => [
                        ['material_name' => 'Cement (OPC 53 Grade)', 'quantity' => 200, 'unit' => 'Bags', 'unit_price' => 410.00, 'total_price' => 82000.00],
                    ],
                ],
                [
                    'po_number' => 'DC-2026-441',
                    'order_date' => '2026-10-12',
                    'project_site_id' => $firstSite->id,
                    'supplier_name' => 'Sri Balaji Blue Metals & M-Sand',
                    'source_type' => 'Third-Party Supplier',
                    'status' => 'Partial Received',
                    'delivery_status' => 'Delivered & Inwarded',
                    'delivery_date' => '2026-10-12',
                    'subtotal' => 34000.00,
                    'total_amount' => 34000.00,
                    'notes' => '4 Loads M-Sand for brick masonry and plastering mortar.',
                    'items' => [
                        ['material_name' => 'M-Sand (Manufactured Sand)', 'quantity' => 4, 'unit' => 'Load', 'unit_price' => 8500.00, 'total_price' => 34000.00],
                    ],
                ],
                [
                    'po_number' => 'PO-2026-101',
                    'order_date' => '2026-10-15',
                    'project_site_id' => $firstSite->id,
                    'supplier_name' => 'SSS Electric Stores',
                    'source_type' => 'Third-Party Supplier',
                    'status' => 'Ordered',
                    'delivery_status' => 'Inward Pending',
                    'delivery_date' => null,
                    'subtotal' => 6750.00,
                    'total_amount' => 6750.00,
                    'notes' => 'Breaker rental for rock clearing and conduit pipes.',
                    'items' => [
                        ['material_name' => 'Demolition Breaker', 'quantity' => 2, 'unit' => 'Hours', 'unit_price' => 1000.00, 'total_price' => 2000.00],
                        ['material_name' => 'Electrical Conduit Pipes', 'quantity' => 50, 'unit' => 'Nos', 'unit_price' => 95.00, 'total_price' => 4750.00],
                    ],
                ],
                [
                    'po_number' => 'DC-2026-442',
                    'order_date' => '2026-10-18',
                    'project_site_id' => $secondSite->id,
                    'supplier_name' => 'Sri Balaji Blue Metals & M-Sand',
                    'source_type' => 'Third-Party Supplier',
                    'status' => 'Order Return',
                    'status_reason' => 'Quality check failed: irregular blue metal size',
                    'delivery_status' => 'Inward Pending',
                    'delivery_date' => null,
                    'subtotal' => 31000.00,
                    'total_amount' => 31000.00,
                    'notes' => '5 Loads 20mm blue metal aggregate for pillar casting.',
                    'items' => [
                        ['material_name' => '20mm Blue Metal Aggregate', 'quantity' => 5, 'unit' => 'Load', 'unit_price' => 6200.00, 'total_price' => 31000.00],
                    ],
                ],
                [
                    'po_number' => 'PO-2026-105',
                    'order_date' => '2026-10-24',
                    'project_site_id' => $firstSite->id,
                    'supplier_name' => 'Kovai Bricks & Chambers',
                    'source_type' => 'Third-Party Supplier',
                    'status' => 'Ordered',
                    'delivery_status' => 'Inward Pending',
                    'delivery_date' => null,
                    'subtotal' => 11500.00,
                    'total_amount' => 11500.00,
                    'notes' => '1st Quality Red Chamber Bricks.',
                    'items' => [
                        ['material_name' => 'Red Bricks (1st Quality Chamber)', 'quantity' => 1000, 'unit' => 'Nos', 'unit_price' => 11.50, 'total_price' => 11500.00],
                    ],
                ],
            ];

            foreach ($seedOrders as $order) {
                $items = $order['items'];
                unset($order['items']);
                $order['created_at'] = $now;
                $order['updated_at'] = $now;

                $poId = DB::table('purchase_orders')->insertGetId($order);

                foreach ($items as $item) {
                    $item['purchase_order_id'] = $poId;
                    $item['created_at'] = $now;
                    $item['updated_at'] = $now;
                    DB::table('purchase_order_items')->insert($item);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
