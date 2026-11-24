<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\MaterialUsage;
use App\Models\MaterialUsageItem;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SiteMaterialStock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Services\Contracts\MaterialUsageServiceInterface;
use App\Services\Contracts\StockTransferServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::SiteManager]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'created_by' => $this->user->id,
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Alpha',
        'location' => 'Madurai',
        'work_type' => 'Residential',
        'start_date' => '2026-01-01',
        'end_date' => '2027-01-01',
        'estimated_budget' => 500000,
        'spent' => 0,
        'status' => 'In Progress',
        'created_by' => $this->user->id,
    ]);

    $this->unitBags = Unit::where('name_en', 'Bags')->orWhere('code', 'BAGS')->first()
        ?? Unit::create([
            'name_en' => 'Bags',
            'code' => 'BAGS',
            'name_ta' => 'மூட்டைகள்',
            'status' => 'Active',
        ]);

    $this->unitLoad = Unit::where('name_en', 'Load')->orWhere('code', 'LOAD')->first()
        ?? Unit::create([
            'name_en' => 'Load',
            'code' => 'LOAD',
            'name_ta' => 'லோடு',
            'status' => 'Active',
        ]);

    $this->cement = Material::firstOrCreate(['name_en' => 'Cement (UltraTech)'], [
        'name_ta' => 'சிமெண்ட்',
        'unit_id' => $this->unitBags->id,
        'status' => 'Active',
    ]);

    $this->sand = Material::firstOrCreate(['name_en' => 'M-Sand'], [
        'name_ta' => 'மணல்',
        'unit_id' => $this->unitLoad->id,
        'status' => 'Active',
    ]);

    // Create an ordered PO with 2 items (100 bags cement @ 500, 2 loads sand @ 8500)
    $this->po = PurchaseOrder::create([
        'po_number' => 'PO-2026-TEST',
        'order_date' => now()->toDateString(),
        'project_site_id' => $this->site->id,
        'supplier_id' => null,
        'supplier_name' => 'Test Supplier',
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'delivery_status' => 'Received',
        'subtotal' => 67000,
        'tax_amount' => 0,
        'total_amount' => 67000,
        'created_by' => $this->user->id,
    ]);

    $this->poItemCement = PurchaseOrderItem::create([
        'purchase_order_id' => $this->po->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'quantity' => 100,
        'unit' => 'Bags',
        'unit_price' => 500,
        'total_price' => 50000,
    ]);

    $this->poItemSand = PurchaseOrderItem::create([
        'purchase_order_id' => $this->po->id,
        'material_id' => $this->sand->id,
        'material_name' => 'M-Sand',
        'quantity' => 2,
        'unit' => 'Load',
        'unit_price' => 8500,
        'total_price' => 17000,
    ]);

    // Seed initial site stock
    SiteMaterialStock::create([
        'project_site_id' => $this->site->id,
        'material_id' => $this->cement->id,
        'unit' => 'Bags',
        'current_stock' => 100,
        'last_updated_at' => now(),
    ]);
});

// ── Index ─────────────────────────────────────────────────────

test('material usage index page loads for site manager', function () {
    $this->actingAs($this->user)
        ->get(route('material-usages.index'))
        ->assertOk()
        ->assertViewIs('site-management.material-usages')
        ->assertViewHas('usages')
        ->assertViewHas('stats')
        ->assertViewHas('sites')
        ->assertViewHas('purchaseOrders');
});

test('unauthenticated user is redirected from material usage page', function () {
    $this->get(route('material-usages.index'))
        ->assertRedirect(route('login'));
});

// ── Next number ───────────────────────────────────────────────

test('next usage number is generated correctly', function () {
    $this->actingAs($this->user);

    $service = app(MaterialUsageServiceInterface::class);
    $no = $service->getNextUsageNumber();

    expect($no)->toStartWith('MU-'.now()->year.'-')
        ->toEndWith('0001');
});

test('next usage number increments sequentially', function () {
    $service = app(MaterialUsageServiceInterface::class);

    MaterialUsage::create([
        'usage_no' => 'MU-'.now()->year.'-0001',
        'usage_date' => now()->toDateString(),
        'purchase_order_id' => $this->po->id,
        'project_site_id' => $this->site->id,
        'total_items_count' => 0,
        'total_used_quantity' => 0,
        'total_cost' => 0,
        'status' => 'Recorded',
        'created_by' => $this->user->id,
    ]);

    $no = $service->getNextUsageNumber();
    expect($no)->toBe('MU-'.now()->year.'-0002');
});

// ── getPOItems and getSiteMaterials endpoints ──────────────────

test('get PO items returns correct data with unit prices', function () {
    $this->actingAs($this->user)
        ->getJson(route('material-usages.po-items', $this->po->id))
        ->assertOk()
        ->assertJsonStructure(['success', 'data' => ['po_number', 'site_id', 'site_name', 'items']])
        ->assertJsonPath('data.po_number', 'PO-2026-TEST')
        ->assertJsonPath('data.items.0.material_name', 'Cement (UltraTech)')
        ->assertJsonPath('data.items.0.inward_quantity', 100)
        ->assertJsonPath('data.items.0.unit_price', 500)
        ->assertJsonPath('data.items.1.material_name', 'M-Sand')
        ->assertJsonPath('data.items.1.unit_price', 8500);
});

test('get site materials returns all materials for a site across POs', function () {
    $this->actingAs($this->user)
        ->getJson(route('material-usages.site-materials', $this->site->id))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.site_name', 'Site Alpha')
        ->assertJsonCount(2, 'data.items');
});

test('get PO items shows correct available quantity after partial usage', function () {
    // Record 40 bags cement already used
    MaterialUsageItem::create([
        'material_usage_id' => MaterialUsage::create([
            'usage_no' => 'MU-2026-0001',
            'usage_date' => now()->toDateString(),
            'purchase_order_id' => $this->po->id,
            'project_site_id' => $this->site->id,
            'total_items_count' => 1,
            'total_used_quantity' => 40,
            'total_cost' => 20000,
            'status' => 'Recorded',
            'created_by' => $this->user->id,
        ])->id,
        'purchase_order_item_id' => $this->poItemCement->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'inward_quantity' => 100,
        'used_quantity' => 40,
        'remaining_quantity' => 60,
        'unit' => 'Bags',
        'unit_price' => 500,
        'total_price' => 20000,
    ]);

    $this->actingAs($this->user)
        ->getJson(route('material-usages.po-items', $this->po->id))
        ->assertOk()
        ->assertJsonPath('data.items.0.already_used', 40)
        ->assertJsonPath('data.items.0.available_quantity', 60);
});

// ── Store with multiple materials for a site ──────────────────

test('can create a material usage record with multiple materials for a site', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'usage_date' => now()->toDateString(),
        'remarks' => 'Foundation concrete mix',
        'items' => [
            [
                'purchase_order_item_id' => $this->poItemCement->id,
                'material_id' => $this->cement->id,
                'material_name' => 'Cement (UltraTech)',
                'inward_quantity' => 100,
                'used_quantity' => 30,
                'unit' => 'Bags',
                'unit_price' => 500,
            ],
            [
                'purchase_order_item_id' => $this->poItemSand->id,
                'material_id' => $this->sand->id,
                'material_name' => 'M-Sand',
                'inward_quantity' => 2,
                'used_quantity' => 1,
                'unit' => 'Load',
                'unit_price' => 8500,
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('material_usages', [
        'project_site_id' => $this->site->id,
        'total_items_count' => 2,
        'total_used_quantity' => 31,
        'total_cost' => 23500.00, // 30 * 500 + 1 * 8500
        'status' => 'Recorded',
        'created_by' => $this->user->id,
    ]);

    $this->assertDatabaseHas('material_usage_items', [
        'material_id' => $this->cement->id,
        'used_quantity' => 30,
        'unit_price' => 500,
        'total_price' => 15000,
    ]);

    $this->assertDatabaseHas('material_usage_items', [
        'material_id' => $this->sand->id,
        'used_quantity' => 1,
        'unit_price' => 8500,
        'total_price' => 8500,
    ]);

    // Check SiteMaterialStock reduced for cement: 100 - 30 = 70
    $stock = SiteMaterialStock::where('project_site_id', $this->site->id)
        ->where('material_id', $this->cement->id)
        ->value('current_stock');
    expect((float) $stock)->toBe(70.0);
});

test('stock transfer available materials fetches materials from material usage for site', function () {
    // Record 30 bags cement used
    $this->actingAs($this->user)->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'usage_date' => now()->toDateString(),
        'items' => [
            [
                'purchase_order_item_id' => $this->poItemCement->id,
                'material_id' => $this->cement->id,
                'material_name' => 'Cement (UltraTech)',
                'inward_quantity' => 100,
                'used_quantity' => 30,
                'unit' => 'Bags',
                'unit_price' => 500,
            ],
        ],
    ])->assertOk();

    // Check StockTransferService::getAvailableMaterialsForSite
    $transferService = app(StockTransferServiceInterface::class);
    $available = $transferService->getAvailableMaterialsForSite($this->site->id);

    // Cement should have available_qty = 70.0 from the balance stock (inward 100 - used 30)
    $cementItem = collect($available)->firstWhere('material_id', $this->cement->id);
    expect($cementItem)->not->toBeNull()
        ->and((float) $cementItem['available_qty'])->toBe(70.0);
});

test('cannot use more than available quantity', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'usage_date' => now()->toDateString(),
        'items' => [
            [
                'purchase_order_item_id' => $this->poItemCement->id,
                'material_id' => $this->cement->id,
                'material_name' => 'Cement',
                'inward_quantity' => 100,
                'used_quantity' => 150, // exceeds 100
                'unit' => 'Bags',
                'unit_price' => 500,
            ],
        ],
    ]);

    $response->assertStatus(422)->assertJsonPath('success', false);
});

test('requires valid items array with quantity', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'usage_date' => now()->toDateString(),
        'items' => [],
    ]);

    $response->assertStatus(422);
});

// ── Show (View Details) ───────────────────────────────────────

test('can view material usage voucher details', function () {
    $this->actingAs($this->user);

    $usage = MaterialUsage::create([
        'usage_no' => 'MU-2026-0001',
        'usage_date' => now()->toDateString(),
        'purchase_order_id' => $this->po->id,
        'project_site_id' => $this->site->id,
        'total_items_count' => 1,
        'total_used_quantity' => 10,
        'total_cost' => 5000,
        'status' => 'Recorded',
        'created_by' => $this->user->id,
    ]);

    MaterialUsageItem::create([
        'material_usage_id' => $usage->id,
        'purchase_order_item_id' => $this->poItemCement->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'inward_quantity' => 100,
        'used_quantity' => 10,
        'remaining_quantity' => 90,
        'unit' => 'Bags',
        'unit_price' => 500,
        'total_price' => 5000,
    ]);

    $this->getJson(route('material-usages.show', $usage->id))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('usage.usage_no', 'MU-2026-0001')
        ->assertJsonCount(1, 'usage.items');
});

// ── Delete ────────────────────────────────────────────────────

test('can delete a material usage record and restore stock', function () {
    $this->actingAs($this->user);

    $usage = MaterialUsage::create([
        'usage_no' => 'MU-2026-0001',
        'usage_date' => now()->toDateString(),
        'purchase_order_id' => $this->po->id,
        'project_site_id' => $this->site->id,
        'total_items_count' => 1,
        'total_used_quantity' => 20,
        'total_cost' => 10000,
        'status' => 'Recorded',
        'created_by' => $this->user->id,
    ]);

    MaterialUsageItem::create([
        'material_usage_id' => $usage->id,
        'purchase_order_item_id' => $this->poItemCement->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement',
        'inward_quantity' => 100,
        'used_quantity' => 20,
        'remaining_quantity' => 80,
        'unit' => 'Bags',
        'unit_price' => 500,
        'total_price' => 10000,
    ]);

    $this->postJson(route('material-usages.destroy', $usage->id))
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('material_usages', ['id' => $usage->id]);
    $this->assertDatabaseMissing('material_usage_items', ['material_usage_id' => $usage->id]);
});

test('can record material usage using own warehouse stock without PO and stock reduces in warehouse', function () {
    $this->actingAs($this->user);

    $whItem = WarehouseItem::create([
        'sku' => 'WH-TEST-01',
        'name_en' => 'Wheelbarrow & Shovel',
        'name_ta' => 'வண்டி & சட்டி',
        'category' => 'Hand Tools',
        'unit_id' => $this->unitBags->id,
        'unit' => 'Nos',
        'current_stock' => 25.00,
        'min_threshold' => 5.00,
        'unit_rate' => 350.00,
        'cash_amount' => 350.00,
        'status' => 'Active',
    ]);

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'material_source' => 'own_warehouse',
        'usage_date' => now()->toDateString(),
        'remarks' => 'Warehouse tools used directly on site without PO',
        'items' => [
            [
                'warehouse_item_id' => $whItem->id,
                'material_name' => 'Wheelbarrow & Shovel',
                'source_type' => 'Own Warehouse',
                'source_reference' => 'WH-TEST-01',
                'inward_quantity' => 25.00,
                'used_quantity' => 5.00,
                'unit' => 'Nos',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 350.00,
                'total_price' => 1750.00,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $usageId = $response->json('usage.id');

    // Assert usage item created with source_type = Own Warehouse
    $this->assertDatabaseHas('material_usage_items', [
        'material_usage_id' => $usageId,
        'source_type' => 'Own Warehouse',
        'source_reference' => 'WH-TEST-01',
        'used_quantity' => 5.00,
    ]);

    // Assert warehouse item stock reduced: 25 - 5 = 20
    $whItem->refresh();
    expect((float) $whItem->current_stock)->toBe(20.0);

    // Assert warehouse movement logged
    $this->assertDatabaseHas('warehouse_movements', [
        'warehouse_item_id' => $whItem->id,
        'movement_type' => 'Issue to Site',
        'project_site_id' => $this->site->id,
        'quantity' => 5.00,
    ]);

    // Deleting this usage must restore warehouse stock to 25.00
    $this->postJson(route('material-usages.destroy', $usageId))->assertOk();

    $whItem->refresh();
    expect((float) $whItem->current_stock)->toBe(25.0);
});

test('can edit material usage to update quantity and price, syncing stock and linked PO', function () {
    $this->actingAs($this->user);

    $po = PurchaseOrder::create([
        'po_number' => 'PO-EDIT-001',
        'project_site_id' => $this->site->id,
        'supplier_name' => 'Supplier Alpha',
        'order_date' => '2026-02-01',
        'subtotal' => 1000.00,
        'total_amount' => 1000.00,
        'status' => 'Ordered',
        'created_by' => $this->user->id,
    ]);

    $poItem = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'quantity' => 10.00,
        'unit_price' => 100.00,
        'total_price' => 1000.00,
    ]);

    // Record initial usage: 4 bags @ 100 = 400
    $storeResponse = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'usage_date' => '2026-02-02',
        'remarks' => 'Initial usage',
        'items' => [
            [
                'material_id' => $this->cement->id,
                'purchase_order_item_id' => $poItem->id,
                'material_name' => 'Cement (UltraTech)',
                'source_type' => 'Supplier PO',
                'source_reference' => 'PO-EDIT-001',
                'inward_quantity' => 10.00,
                'used_quantity' => 4.00,
                'unit' => 'Bags',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 100.00,
                'total_price' => 400.00,
            ],
        ],
    ]);

    $storeResponse->assertOk();
    $usageId = $storeResponse->json('usage.id');
    $usage = MaterialUsage::with('items')->find($usageId);
    $usageItemId = $usage->items->first()->id;

    // Site stock balance should be 100 - 4 = 96
    $stock = SiteMaterialStock::where('project_site_id', $this->site->id)
        ->where('material_id', $this->cement->id)
        ->first();
    expect((float) $stock->current_stock)->toBe(96.0);

    // Now edit usage: increase quantity from 4 to 6, change price from 100 to 120
    $updateResponse = $this->postJson(route('material-usages.update', $usageId), [
        'usage_date' => '2026-02-03',
        'remarks' => 'Updated usage remarks',
        'items' => [
            [
                'id' => $usageItemId,
                'used_quantity' => 6.00,
                'unit_price' => 120.00,
            ],
        ],
    ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    // Verify usage updated: 6 bags @ 120 = 720
    $usage->refresh();
    expect((float) $usage->total_used_quantity)->toBe(6.0)
        ->and((float) $usage->total_cost)->toBe(720.0)
        ->and($usage->remarks)->toBe('Updated usage remarks');

    // Verify stock balance: 96 - (6 - 4) = 94
    $stock->refresh();
    expect((float) $stock->current_stock)->toBe(94.0);

    // Verify PO item price updated to 120
    $poItem->refresh();
    expect((float) $poItem->unit_price)->toBe(120.0)
        ->and((float) $poItem->total_price)->toBe(1200.0);

    // Verify PO total updated
    $po->refresh();
    expect((float) $po->total_amount)->toBe(1200.0);
});

test('can add and remove materials when editing material usage', function () {
    $this->actingAs($this->user);

    $helmetWh = WarehouseItem::create([
        'name_en' => 'Safety Helmet',
        'name_ta' => 'பாதுகாப்பு தொப்பி',
        'sku' => 'WH-HELMET-01',
        'current_stock' => 50.0,
        'unit' => 'Nos',
        'unit_id' => $this->unitBags->id,
        'rate' => 250.0,
        'status' => 'Active',
    ]);

    // Initial usage with 1 item
    $storeResponse = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'usage_date' => '2026-02-01',
        'remarks' => 'Original usage',
        'items' => [
            [
                'warehouse_item_id' => $helmetWh->id,
                'material_name' => 'Safety Helmet',
                'source_type' => 'Own Warehouse',
                'source_reference' => 'WH-HELMET-01',
                'inward_quantity' => 50.00,
                'used_quantity' => 10.00,
                'unit' => 'Nos',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 250.00,
            ],
        ],
    ]);

    $storeResponse->assertOk();
    $usageId = $storeResponse->json('usage.id');
    $usage = MaterialUsage::with('items')->find($usageId);
    $initialItemId = $usage->items->first()->id;

    // Check warehouse stock was 50 - 10 = 40
    $helmetWh->refresh();
    expect((float) $helmetWh->current_stock)->toBe(40.0);

    // Now edit usage: add a new item (cement), keep helmet with modified qty
    $updateResponse = $this->postJson(route('material-usages.update', $usageId), [
        'usage_date' => '2026-02-02',
        'remarks' => 'Added cement, updated helmet',
        'items' => [
            [
                'id' => $initialItemId,
                'used_quantity' => 8.00, // Reduced from 10 to 8 -> restores 2 to warehouse
                'unit_price' => 250.00,
            ],
            [
                'material_id' => $this->cement->id,
                'material_name' => 'Ultratech Cement 53',
                'source_type' => 'Third-Party Supplier',
                'source_reference' => 'PO-NEW-001',
                'inward_quantity' => 20.00,
                'used_quantity' => 5.00,
                'unit' => 'Bags',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 380.00,
            ],
        ],
    ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    $usage->refresh();
    expect($usage->items)->toHaveCount(2)
        ->and((float) $usage->total_used_quantity)->toBe(13.0)
        ->and((float) $usage->total_cost)->toBe(8 * 250.0 + 5 * 380.0);

    // Warehouse stock for helmet restored by 2: 40 + 2 = 42
    $helmetWh->refresh();
    expect((float) $helmetWh->current_stock)->toBe(42.0);

    // Now edit usage again: remove the helmet item completely, keeping only cement
    $cementItemId = $usage->items->where('material_id', $this->cement->id)->first()->id;
    $removeResponse = $this->postJson(route('material-usages.update', $usageId), [
        'usage_date' => '2026-02-02',
        'items' => [
            [
                'id' => $cementItemId,
                'used_quantity' => 5.00,
                'unit_price' => 380.00,
            ],
        ],
    ]);

    $removeResponse->assertOk()->assertJsonPath('success', true);

    $usage->refresh();
    expect($usage->items)->toHaveCount(1)
        ->and((float) $usage->total_used_quantity)->toBe(5.0)
        ->and((float) $usage->total_cost)->toBe(1900.0);
});

test('can add warehouse material without material_id when editing material usage', function () {
    $this->actingAs($this->user);

    $shovelWh = WarehouseItem::updateOrCreate(
        ['sku' => 'WH-TOOL-02'],
        [
            'name_en' => 'Spade & Iron Shovel',
            'name_ta' => 'மண்வெட்டி',
            'current_stock' => 35.0,
            'unit' => 'Nos',
            'unit_id' => $this->unitBags->id,
            'rate' => 250.0,
            'status' => 'Active',
        ]
    );

    // Initial usage with 1 PO item
    $storeResponse = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'usage_date' => '2026-02-01',
        'remarks' => 'Original usage',
        'items' => [
            [
                'material_id' => $this->cement->id,
                'material_name' => 'Ultratech Cement 53',
                'source_type' => 'Third-Party Supplier',
                'source_reference' => 'PO-001',
                'inward_quantity' => 20.00,
                'used_quantity' => 5.00,
                'unit' => 'Bags',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 380.00,
            ],
        ],
    ]);

    $storeResponse->assertOk();
    $usageId = $storeResponse->json('usage.id');
    $usage = MaterialUsage::with('items')->find($usageId);
    $initialItemId = $usage->items->first()->id;

    // Edit usage: add Own Warehouse item without material_id (reproducing user scenario)
    $updateResponse = $this->postJson(route('material-usages.update', $usageId), [
        'usage_date' => '2026-02-02',
        'remarks' => 'Added spade & shovel from warehouse',
        'items' => [
            [
                'id' => $initialItemId,
                'used_quantity' => 5.00,
                'unit_price' => 380.00,
            ],
            [
                'material_id' => null,
                'warehouse_item_id' => $shovelWh->id,
                'material_name' => 'Spade & Iron Shovel',
                'source_type' => 'Own Warehouse',
                'source_reference' => 'WH-TOOL-02',
                'inward_quantity' => 35.00,
                'used_quantity' => 1.00,
                'unit' => 'Nos',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 250.00,
            ],
        ],
    ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    $usage->refresh();
    expect($usage->items)->toHaveCount(2);

    $addedItem = $usage->items->where('material_name', 'Spade & Iron Shovel')->first();
    expect($addedItem)->not->toBeNull()
        ->and($addedItem->material_id)->not->toBeNull()
        ->and($addedItem->material_id)->toBeGreaterThan(0)
        ->and((float) $addedItem->used_quantity)->toBe(1.0);

    // Verify warehouse stock deduction: 35 - 1 = 34
    $shovelWh->refresh();
    expect((float) $shovelWh->current_stock)->toBe(34.0);
});

test('transferred stock to another site is loaded into site materials for usage and can be consumed', function () {
    $siteB = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Beta',
        'location' => 'Madurai South',
        'work_type' => 'Commercial',
        'start_date' => '2026-01-01',
        'end_date' => '2027-01-01',
        'estimated_budget' => 600000,
        'spent' => 0,
        'status' => 'In Progress',
        'created_by' => $this->user->id,
    ]);

    // Set stock at Site Alpha: 50 bags cement
    SiteMaterialStock::updateOrCreate(
        ['project_site_id' => $this->site->id, 'material_id' => $this->cement->id],
        ['unit_id' => $this->unitBags->id, 'unit' => 'Bags', 'current_stock' => 50.00, 'last_updated_at' => now()]
    );

    // Inter-site transfer from Site Alpha to Site Beta: 20 bags cement
    $transfer = StockTransfer::create([
        'transfer_no' => 'XFR-2026-0001',
        'transfer_type' => 'TRANSFER',
        'from_project_site_id' => $this->site->id,
        'to_project_site_id' => $siteB->id,
        'transfer_date' => '2026-11-20',
        'status' => 'Completed',
        'created_by' => $this->user->id,
    ]);

    $transferItem = StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'source_type' => 'PO',
        'source_reference' => 'PO-2026-TEST',
        'quantity' => 20.00,
        'unit' => 'Bags',
        'unit_id' => $this->unitBags->id,
        'unit_price' => 500.00,
        'total_price' => 10000.00,
    ]);

    // Site Beta stock updated by transfer service (or directly here)
    SiteMaterialStock::create([
        'project_site_id' => $siteB->id,
        'material_id' => $this->cement->id,
        'unit_id' => $this->unitBags->id,
        'unit' => 'Bags',
        'current_stock' => 20.00,
        'last_updated_at' => now(),
    ]);

    // 1. Verify Site Beta site-materials endpoint returns transferred stock
    $response = $this->actingAs($this->user)->getJson(route('material-usages.site-materials', $siteB->id));
    $response->assertOk();
    $data = $response->json('data');

    expect($data['transfers'])->toHaveCount(1);
    expect($data['transfers'][0]['transfer_no'])->toBe('XFR-2026-0001');

    $transferMat = collect($data['items'])->firstWhere('source_type', 'Stock Transfer');
    expect($transferMat)->not->toBeNull()
        ->and($transferMat['material_name'])->toBe('Cement (UltraTech)')
        ->and((float) $transferMat['inward_quantity'])->toBe(20.0)
        ->and((float) $transferMat['available_quantity'])->toBe(20.0);

    // 2. Consume 15 bags from transferred stock at Site Beta
    $storeResponse = $this->actingAs($this->user)->postJson(route('material-usages.store'), [
        'usage_date' => '2026-11-21',
        'project_site_id' => $siteB->id,
        'material_source' => 'stock_transfer',
        'stock_transfer_id' => $transfer->id,
        'remarks' => 'Consuming transferred cement at Site Beta',
        'items' => [
            [
                'material_id' => $this->cement->id,
                'stock_transfer_id' => $transfer->id,
                'stock_transfer_item_id' => $transferItem->id,
                'material_name' => 'Cement (UltraTech)',
                'source_type' => 'Stock Transfer',
                'source_reference' => 'XFR-2026-0001',
                'inward_quantity' => 20.00,
                'used_quantity' => 15.00,
                'unit' => 'Bags',
                'unit_id' => $this->unitBags->id,
                'unit_price' => 500.00,
            ],
        ],
    ]);

    $storeResponse->assertOk()->assertJsonPath('success', true);

    // Check SiteMaterialStock at Site Beta is decremented to 5 bags
    $siteBStock = SiteMaterialStock::where('project_site_id', $siteB->id)
        ->where('material_id', $this->cement->id)
        ->first();
    expect((float) $siteBStock->current_stock)->toBe(5.0);

    // Check MaterialUsageItem remaining quantity is 5 bags
    $usageItem = MaterialUsageItem::where('source_reference', 'XFR-2026-0001')->first();
    expect($usageItem)->not->toBeNull()
        ->and((float) $usageItem->used_quantity)->toBe(15.0)
        ->and((float) $usageItem->remaining_quantity)->toBe(5.0);

    // 3. Query site-materials again and verify available transferred quantity is now 5
    $secondResponse = $this->actingAs($this->user)->getJson(route('material-usages.site-materials', $siteB->id));
    $secondData = $secondResponse->json('data');
    $updatedTransferMat = collect($secondData['items'])->firstWhere('source_type', 'Stock Transfer');
    expect((float) $updatedTransferMat['available_quantity'])->toBe(5.0)
        ->and((float) $updatedTransferMat['already_used'])->toBe(15.0);
});

test('project site detail page displays balance stock tab, materials used balance, and inward/outward transfers', function () {
    $siteB = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Gamma',
        'location' => 'Madurai East',
        'work_type' => 'Commercial',
        'start_date' => '2026-01-01',
        'end_date' => '2027-01-01',
        'estimated_budget' => 600000,
        'spent' => 0,
        'status' => 'In Progress',
        'created_by' => $this->user->id,
    ]);

    // Set stock at site: 50 bags cement
    SiteMaterialStock::updateOrCreate(
        ['project_site_id' => $this->site->id, 'material_id' => $this->cement->id],
        ['unit_id' => $this->unitBags->id, 'unit' => 'Bags', 'current_stock' => 50.00, 'last_updated_at' => now()]
    );

    // Material usage record
    $usage = MaterialUsage::create([
        'usage_no' => 'MU-TEST-001',
        'usage_date' => '2026-11-19',
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'material_source' => 'third_party',
        'total_items_count' => 1,
        'total_used_quantity' => 20.00,
        'total_cost' => 10000.00,
        'status' => 'Recorded',
        'created_by' => $this->user->id,
    ]);

    MaterialUsageItem::create([
        'material_usage_id' => $usage->id,
        'purchase_order_item_id' => $this->poItemCement->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'source_type' => 'Third-Party Supplier',
        'source_reference' => $this->po->po_number,
        'inward_quantity' => 100.00,
        'used_quantity' => 20.00,
        'remaining_quantity' => 80.00,
        'unit' => 'Bags',
        'unit_id' => $this->unitBags->id,
        'unit_price' => 500.00,
        'total_price' => 10000.00,
    ]);

    // Outward transfer: 10 bags to Site Gamma
    $outTransfer = StockTransfer::create([
        'transfer_no' => 'XFR-OUT-001',
        'transfer_type' => 'TRANSFER',
        'from_project_site_id' => $this->site->id,
        'to_project_site_id' => $siteB->id,
        'transfer_date' => '2026-11-20',
        'status' => 'Completed',
        'created_by' => $this->user->id,
    ]);

    StockTransferItem::create([
        'stock_transfer_id' => $outTransfer->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'quantity' => 10.00,
        'unit' => 'Bags',
        'unit_price' => 500.00,
        'total_price' => 5000.00,
    ]);

    $response = $this->actingAs($this->user)->get(route('sites.show', $this->site->id));

    $response->assertOk();
    $response->assertSee('Balance Stock');
    $response->assertSee('மீதமுள்ள இருப்பு');
    $response->assertSee('OUTWARD');
    $response->assertSee('XFR-OUT-001');
    $response->assertSee('Cement (UltraTech)');
});

test('project site detail page displays supplier purchases tab with clean summary table and view button', function () {
    $response = $this->actingAs($this->user)->get(route('sites.show', $this->site->id));

    $response->assertOk();
    $response->assertSee('Supplier Purchases');
    $response->assertSee('சப்ளையர் கொள்முதல்');
    $response->assertSee($this->po->supplier_name);
    $response->assertSee('View (விவரங்கள்)');
    $response->assertSee('₹'.number_format($this->poItemCement->total_price + $this->poItemSand->total_price, 2));
});

test('supplier purchases detail page displays date-wise breakdown of materials and prices for the site', function () {
    $supKey = 'ext_'.urlencode(strtolower(trim($this->po->supplier_name)));
    $url = route('sites.supplier-purchases', [
        'encryptedId' => $this->site->id,
        'supplierKey' => $supKey,
    ]);

    $response = $this->actingAs($this->user)->get($url);

    $response->assertOk();
    $response->assertSee($this->po->supplier_name);
    $response->assertSee($this->po->po_number);
    $response->assertSee('Cement (UltraTech)');
    $response->assertSee('M-Sand');
    $response->assertSee('₹'.number_format($this->poItemCement->unit_price, 2));
    $response->assertSee('₹'.number_format($this->poItemCement->total_price, 2));
    $response->assertSee('₹'.number_format($this->poItemSand->unit_price, 2));
    $response->assertSee('Date Filter');
    $response->assertSee('Date-wise Purchase Breakdown');
    $response->assertSee('Back to Site');
});

test('cannot record material usage before PO order date', function () {
    $this->actingAs($this->user);

    // PO order date is 5 days ago
    $poDate = now()->subDays(5)->toDateString();
    $this->po->update(['order_date' => $poDate]);

    // Attempt usage 6 days ago (before PO date)
    $beforePoDate = now()->subDays(6)->toDateString();

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'usage_date' => $beforePoDate,
        'remarks' => 'Trying usage before PO date',
        'items' => [
            [
                'purchase_order_item_id' => $this->poItemCement->id,
                'material_id' => $this->cement->id,
                'material_name' => 'Cement (UltraTech)',
                'inward_quantity' => 100,
                'used_quantity' => 5,
                'unit' => 'Bags',
                'unit_price' => 500,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('cannot record material usage in the future', function () {
    $this->actingAs($this->user);

    $futureDate = now()->addDays(2)->toDateString();

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'usage_date' => $futureDate,
        'remarks' => 'Trying future usage date',
        'items' => [
            [
                'purchase_order_item_id' => $this->poItemCement->id,
                'material_id' => $this->cement->id,
                'material_name' => 'Cement (UltraTech)',
                'inward_quantity' => 100,
                'used_quantity' => 5,
                'unit' => 'Bags',
                'unit_price' => 500,
            ],
        ],
    ]);

    $response->assertStatus(422);
});

test('can record material usage on any date between PO order date and today', function () {
    $this->actingAs($this->user);

    // PO order date is 4 days ago
    $poDate = now()->subDays(4)->toDateString();
    $this->po->update(['order_date' => $poDate]);

    // Usage date is 2 days ago (between PO date and today)
    $validBetweenDate = now()->subDays(2)->toDateString();

    $response = $this->postJson(route('material-usages.store'), [
        'project_site_id' => $this->site->id,
        'purchase_order_id' => $this->po->id,
        'usage_date' => $validBetweenDate,
        'remarks' => 'Valid date between PO and today',
        'items' => [
            [
                'purchase_order_item_id' => $this->poItemCement->id,
                'material_id' => $this->cement->id,
                'material_name' => 'Cement (UltraTech)',
                'inward_quantity' => 100,
                'used_quantity' => 10,
                'unit' => 'Bags',
                'unit_price' => 500,
            ],
        ],
    ]);

    $response->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('material_usages', [
        'project_site_id' => $this->site->id,
        'usage_date' => $validBetweenDate,
    ]);
});
