<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::firstOrCreate(
        ['email' => 'owner@deiveni.com'],
        [
            'name' => 'K. Deiveni',
            'role' => UserRole::Owner,
            'phone' => '98400 11111',
            'status' => 'Active',
            'password' => Hash::make('password123'),
        ]
    );

    $this->siteManager = User::firstOrCreate(
        ['email' => 'sm@deiveni.com'],
        [
            'name' => 'Murugan SM',
            'role' => UserRole::SiteManager,
            'phone' => '98400 22222',
            'status' => 'Active',
            'password' => Hash::make('password123'),
        ]
    );

    $this->client = Client::create([
        'name' => 'Mr. Shanmugam',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'status' => 'Active',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Koodal Nagar Site',
        'location' => 'Madurai',
        'work_type' => 'Residential Villa',
        'start_date' => '2026-10-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 5000000,
        'spent' => 0,
        'status' => 'In Progress',
    ]);

    $this->supplier = Supplier::firstOrCreate(
        ['name' => 'Annamalai Steel & Hardware'],
        [
            'phone' => '98421 55667',
            'status' => 'Active',
        ]
    );

    $this->unit = Unit::firstOrCreate(
        ['code' => 'KG'],
        [
            'name_en' => 'Kilograms',
            'name_ta' => 'கிலோகிராம்',
            'status' => 'Active',
        ]
    );

    $this->material = Material::firstOrCreate(
        ['name_en' => 'Steel Rods 12mm'],
        [
            'name_ta' => '12மிமீ கம்பி',
            'unit_id' => $this->unit->id,
            'status' => 'Active',
        ]
    );

    $this->supplier->materials()->syncWithoutDetaching([
        $this->material->id => [
            'price' => 65.00,
            'grade' => 'Fe 550D',
        ],
    ]);

    $this->warehouseItem = WarehouseItem::create([
        'sku' => 'WH-TEST-01',
        'name_en' => 'Test Cement Bags',
        'name_ta' => 'டெஸ்ட் சிமெண்ட்',
        'category' => 'Raw Material',
        'unit' => 'Bags',
        'current_stock' => 50,
        'min_threshold' => 10,
        'unit_rate' => 400.00,
        'status' => 'Active',
    ]);
});

test('task 1 & 2: purchases page renders dynamically with database records', function () {
    $po = PurchaseOrder::create([
        'po_number' => 'PO-TEST-001',
        'order_date' => '2026-11-01',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Ordered',
        'total_amount' => 15000.00,
    ]);

    $po->items()->create([
        'material_id' => $this->material->id,
        'material_name' => 'Steel Rods 12mm',
        'quantity' => 100,
        'unit' => 'KG',
        'unit_price' => 150.00,
        'total_price' => 15000.00,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/purchases');

    $response->assertStatus(200);
    $response->assertViewIs('purchases.index');
    $response->assertSee('PO-TEST-001');
    $response->assertSee('Annamalai Steel & Hardware');
});

test('task 1: creating a purchase order from third party supplier works', function () {
    $payload = [
        'po_number' => 'PO-NEW-101',
        'order_date' => '2026-11-05',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Ordered',
        'items' => [
            [
                'material_id' => $this->material->id,
                'material_name' => 'Steel Rods 12mm',
                'quantity' => 100,
                'unit' => 'Kg',
                'unit_price' => 65.00,
                'total_price' => 6500.00,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/purchases', $payload);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('purchase_orders', [
        'po_number' => 'PO-NEW-101',
        'total_amount' => 6500.00,
    ]);
    $this->assertDatabaseHas('purchase_order_items', [
        'material_id' => $this->material->id,
        'material_name' => 'Steel Rods 12mm',
        'quantity' => 100,
    ]);
});

test('task 1: purchase order from own warehouse auto-deducts warehouse inventory', function () {
    $initialStock = (float) $this->warehouseItem->current_stock;

    $payload = [
        'po_number' => 'WH-TRANS-001',
        'order_date' => '2026-11-05',
        'project_site_id' => $this->site->id,
        'supplier_name' => 'Central Warehouse',
        'source_type' => 'Own Warehouse',
        'status' => 'Order Completed',
        'items' => [
            [
                'warehouse_item_id' => $this->warehouseItem->id,
                'material_name' => $this->warehouseItem->name_en,
                'quantity' => 15,
                'unit' => 'Bags',
                'unit_price' => 400.00,
                'total_price' => 6000.00,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/purchases', $payload);

    $response->assertStatus(200);

    $this->warehouseItem->refresh();
    expect((float) $this->warehouseItem->current_stock)->toBe($initialStock - 15);

    $this->assertDatabaseHas('warehouse_movements', [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Issue to Site',
        'quantity' => 15,
        'project_site_id' => $this->site->id,
    ]);
});

test('task 2: warehouse page renders dynamically and records movements', function () {
    $response = $this->actingAs($this->siteManager)->get('/warehouse');

    $response->assertStatus(200);
    $response->assertViewIs('warehouse.index');
    $response->assertSee('Test Cement Bags');

    // Record movement
    $movePayload = [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Issue to Site',
        'quantity' => 5,
        'project_site_id' => $this->site->id,
        'notes' => 'Dispatched to villa site',
    ];

    $moveResponse = $this->actingAs($this->siteManager)->postJson('/warehouse/movements', $movePayload);
    $moveResponse->assertStatus(200);

    $this->warehouseItem->refresh();
    expect((float) $this->warehouseItem->current_stock)->toBe(45.0);
});

test('task 3: site cash page allows selecting warehouse materials and auto-deducts stock', function () {
    $initialStock = (float) $this->warehouseItem->current_stock;

    $cashPayload = [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'supplier_name' => 'Central Warehouse',
        'amount' => 4000.00,
        'bill_no' => 'WH-ISSUE-1001',
        'payment_date' => Carbon::today()->toDateString(),
        'description' => '10 Bags Cement from Central Warehouse',
        'items' => [
            [
                'warehouse_item_id' => $this->warehouseItem->id,
                'name' => $this->warehouseItem->name_en,
                'unit' => 'Bags',
                'qty' => 10,
                'rate' => 400.00,
                'total' => 4000.00,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/site-cash', $cashPayload);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $this->warehouseItem->refresh();
    expect((float) $this->warehouseItem->current_stock)->toBe($initialStock - 10);

    $this->site->refresh();
    expect((float) $this->site->spent)->toBe(4000.00);

    $this->assertDatabaseHas('site_cashes', [
        'bill_no' => 'WH-ISSUE-1001',
        'supplier_name' => 'Central Warehouse',
        'amount' => 4000.00,
    ]);
});

test('task 2: reports for purchases and warehouse render dynamically without localStorage hardcoding', function () {
    $repPurchases = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward');
    $repPurchases->assertStatus(200);
    $repPurchases->assertViewIs('reports.site_purchases_inward');

    $repWarehouse = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $repWarehouse->assertStatus(200);
    $repWarehouse->assertViewIs('reports.site_warehouse_inventory');
});

test('warehouse restock and delete functionality works properly', function () {
    $initialStock = (float) $this->warehouseItem->current_stock;

    // Restock with quantity and remark
    $restockPayload = [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Stock Inward',
        'quantity' => 25,
        'notes' => 'Bulk restock from main factory',
    ];

    $restockResponse = $this->actingAs($this->siteManager)->postJson('/warehouse/movements', $restockPayload);
    $restockResponse->assertStatus(200);

    $this->warehouseItem->refresh();
    expect((float) $this->warehouseItem->current_stock)->toBe($initialStock + 25);
    $this->assertDatabaseHas('warehouse_movements', [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Stock Inward',
        'quantity' => 25,
        'notes' => 'Bulk restock from main factory',
    ]);

    // Delete item
    $deleteResponse = $this->actingAs($this->siteManager)->deleteJson('/warehouse/items/'.$this->warehouseItem->id);
    $deleteResponse->assertStatus(200);
    $this->assertDatabaseMissing('warehouse_items', ['id' => $this->warehouseItem->id]);
});

test('site cash purchase orders query returns matching POs and empty array when none match', function () {
    $po = PurchaseOrder::create([
        'po_number' => 'PO-MATCH-001',
        'order_date' => '2026-11-05',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Ordered',
        'total_amount' => 12500.00,
    ]);

    // Matching query
    $response = $this->actingAs($this->siteManager)->getJson("/site-cash/purchase-orders?project_site_id={$this->site->id}&supplier_id={$this->supplier->id}");
    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    expect($response->json('orders'))->toHaveCount(1);
    expect($response->json('orders.0.po_number'))->toBe('PO-MATCH-001');

    // Non-matching query (different site)
    $responseEmpty = $this->actingAs($this->siteManager)->getJson("/site-cash/purchase-orders?project_site_id=9999&supplier_id={$this->supplier->id}");
    $responseEmpty->assertStatus(200);
    expect($responseEmpty->json('orders'))->toBeEmpty();
});

test('task 2: purchase order invoice number is autogenerated and read only endpoint works', function () {
    $response = $this->actingAs($this->siteManager)->getJson('/purchases/next-po-number');
    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    expect($response->json('po_number'))->toMatch('/^PO-\d{4}-\d{3,}$/');

    // Page view also has nextPoNumber passed
    $viewResponse = $this->actingAs($this->siteManager)->get('/purchases');
    $viewResponse->assertStatus(200);
    $viewResponse->assertViewHas('nextPoNumber');
});

test('task 1: validation for purchase orders returns 422 with field errors', function () {
    $response = $this->actingAs($this->siteManager)->postJson('/purchases', []);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order_date', 'supplier_name', 'status', 'items']);
});

test('task 1: validation for warehouse movements and items returns 422 with field errors', function () {
    // Restock with invalid/zero quantity
    $restockResponse = $this->actingAs($this->siteManager)->postJson('/warehouse/movements', [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Stock Inward',
        'quantity' => 0,
    ]);
    $restockResponse->assertStatus(422);
    $restockResponse->assertJsonValidationErrors(['quantity']);

    // Warehouse item missing required fields
    $itemResponse = $this->actingAs($this->siteManager)->postJson('/warehouse/items', []);
    $itemResponse->assertStatus(422);
    $itemResponse->assertJsonValidationErrors(['name_en', 'name_ta', 'category', 'unit', 'current_stock', 'min_threshold']);
});

test('purchase order creates items with material_id foreign key and excludes central warehouse from suppliers', function () {
    // Verify Central Warehouse is not in suppliers list on index view
    $response = $this->actingAs($this->siteManager)->get('/purchases');
    $response->assertStatus(200);
    $suppliers = $response->viewData('suppliers');
    foreach ($suppliers as $sup) {
        expect(strtolower($sup->name))->not->toContain('warehouse');
    }

    // Creating PO saves material_id in purchase_order_items table
    $poPayload = [
        'po_number' => 'PO-DYN-999',
        'order_date' => '2026-11-05',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Ordered',
        'items' => [
            [
                'material_id' => $this->material->id,
                'item_name' => $this->material->name_en,
                'quantity' => 100,
                'unit' => 'KG',
                'unit_price' => 65.00,
            ],
        ],
    ];

    $createResponse = $this->actingAs($this->siteManager)->postJson('/purchases', $poPayload);
    $createResponse->assertStatus(200);

    $this->assertDatabaseHas('purchase_order_items', [
        'material_id' => $this->material->id,
        'material_name' => 'Steel Rods 12mm',
        'quantity' => 100,
        'unit' => 'KG',
    ]);
});

test('task 1: creating and updating warehouse item with cash_amount persists to database and view', function () {
    $payload = [
        'sku' => 'WH-CASH-01',
        'name_en' => 'High Grade Wire Mesh',
        'name_ta' => 'வயர் மெஷ் கம்பி வலை',
        'category' => 'Raw Material',
        'unit' => 'Rolls',
        'current_stock' => 15,
        'min_threshold' => 3,
        'cash_amount' => 1250.50,
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/warehouse/items', $payload);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('item.cash_amount', '1250.50');

    $this->assertDatabaseHas('warehouse_items', [
        'sku' => 'WH-CASH-01',
        'cash_amount' => 1250.50,
        'unit_rate' => 1250.50,
    ]);

    $created = WarehouseItem::where('sku', 'WH-CASH-01')->firstOrFail();

    // Update item cash_amount
    $updatePayload = [
        'sku' => 'WH-CASH-01',
        'name_en' => 'High Grade Wire Mesh (Updated)',
        'name_ta' => 'வயர் மெஷ் கம்பி வலை',
        'category' => 'Raw Material',
        'unit' => 'Rolls',
        'current_stock' => 15,
        'min_threshold' => 3,
        'cash_amount' => 1350.00,
    ];

    $updateResponse = $this->actingAs($this->siteManager)->putJson('/warehouse/items/'.$created->id, $updatePayload);
    $updateResponse->assertStatus(200);

    $created->refresh();
    expect((float) $created->cash_amount)->toBe(1350.00);
    expect((float) $created->unit_rate)->toBe(1350.00);

    // Verify index page sees the cash amount
    $viewResponse = $this->actingAs($this->siteManager)->get('/warehouse');
    $viewResponse->assertStatus(200);
    $viewResponse->assertSee('1,350.00');
});

test('task 2: restock movement records cash_amount and updates item valuation rate', function () {
    $restockPayload = [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Stock Inward',
        'quantity' => 10,
        'cash_amount' => 450.00,
        'notes' => 'Restocked at higher supplier price',
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/warehouse/movements', $restockPayload);
    $response->assertStatus(200);

    $this->assertDatabaseHas('warehouse_movements', [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Stock Inward',
        'quantity' => 10,
        'cash_amount' => 450.00,
    ]);

    $this->warehouseItem->refresh();
    expect((float) $this->warehouseItem->cash_amount)->toBe(450.00);
    expect((float) $this->warehouseItem->unit_rate)->toBe(450.00);
});

test('task 2: price flows correctly from site cash -> supplier flow -> warehouse logic', function () {
    // 1. Warehouse item has cash_amount
    $this->warehouseItem->update([
        'cash_amount' => 425.00,
        'unit_rate' => 425.00,
    ]);

    // 2. Site Cash transaction using Own Warehouse source automatically uses warehouse cash_amount
    $qty = 8;
    $expectedTotal = 8 * 425.00; // 3400.00

    $cashPayload = [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'material_source' => 'own_warehouse',
        'supplier_name' => 'Central Warehouse',
        'amount' => $expectedTotal,
        'bill_no' => 'WH-FLOW-001',
        'payment_date' => Carbon::today()->toDateString(),
        'description' => '8 Bags Cement issued to site',
        'items' => [
            [
                'warehouse_item_id' => $this->warehouseItem->id,
                'name' => $this->warehouseItem->name_en,
                'unit' => 'Bags',
                'qty' => $qty,
                'rate' => (float) $this->warehouseItem->cash_amount,
                'total' => $expectedTotal,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/site-cash', $cashPayload);
    $response->assertStatus(201);

    // 3. Warehouse movement created with cash_amount
    $this->assertDatabaseHas('warehouse_movements', [
        'warehouse_item_id' => $this->warehouseItem->id,
        'movement_type' => 'Issue to Site',
        'quantity' => $qty,
        'cash_amount' => 425.00,
    ]);
});
