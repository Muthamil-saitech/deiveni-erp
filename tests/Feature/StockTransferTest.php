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
use App\Services\Contracts\RoleServiceInterface;
use App\Services\Contracts\StockTransferServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'created_by' => $this->siteManager->id,
    ]);

    $this->siteA = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site A',
        'location' => 'Madurai North',
        'work_type' => 'Residential',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $this->siteB = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site B',
        'location' => 'Madurai South',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 700000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $this->unit = Unit::firstOrCreate(['name_en' => 'Bags'], [
        'code' => 'BAGS',
        'name_ta' => 'மூட்டைகள்',
        'status' => 'Active',
    ]);

    $this->cement = Material::firstOrCreate(['name_en' => 'Cement (UltraTech)'], [
        'name_ta' => 'சிமெண்ட்',
        'unit_id' => $this->unit->id,
        'status' => 'Active',
    ]);

    // Initial stock: Site A has 100 bags of cement
    SiteMaterialStock::create([
        'project_site_id' => $this->siteA->id,
        'material_id' => $this->cement->id,
        'unit_id' => $this->unit->id,
        'unit' => 'Bags',
        'current_stock' => 100.00,
        'last_updated_at' => now(),
    ]);
});

test('stock transfer page renders successfully for site manager', function () {
    $response = $this->actingAs($this->siteManager)->get('/stock-transfers');

    $response->assertStatus(200);
    $response->assertSee('Inter-Site Stock Transfer');
    $response->assertSee('Site A');
    $response->assertSee('Site B');
    $response->assertSee('transferItemsTable');
    $response->assertDontSee('name="challan_no"');
});

test('available materials endpoint returns only materials present at source site', function () {
    $response = $this->actingAs($this->siteManager)->getJson("/stock-transfers/available-materials/{$this->siteA->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $materials = $response->json('materials');
    expect($materials)->not->toBeEmpty();
    expect($materials[0]['material_id'])->toBe($this->cement->id);
    expect((float) $materials[0]['available_qty'])->toBe(100.0);
});

test('transferring multiple materials from Site A to Site B creates items, updates balances, and tracks source', function () {
    $brick = Material::firstOrCreate(['name_en' => 'Red Bricks'], [
        'name_ta' => 'செங்கல்',
        'unit_id' => $this->unit->id,
        'status' => 'Active',
    ]);

    SiteMaterialStock::create([
        'project_site_id' => $this->siteA->id,
        'material_id' => $brick->id,
        'unit_id' => $this->unit->id,
        'unit' => 'Nos',
        'current_stock' => 500.00,
        'last_updated_at' => now(),
    ]);

    $payload = [
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'transfer_date' => '2026-11-18',
        'return_expected' => 1,
        'vehicle_no' => 'TN 45 AB 1234',
        'driver_name' => 'Murugan',
        'remarks' => 'Multiple materials inter-site transfer',
        'items' => [
            [
                'material_id' => $this->cement->id,
                'material_name' => 'Cement (UltraTech)',
                'source_type' => 'PO',
                'source_reference' => 'PO-2026-001',
                'supplier_name' => 'Sri Balaji',
                'quantity' => 10.00,
                'unit' => 'Bags',
                'unit_id' => $this->unit->id,
                'unit_price' => 450.00,
                'total_price' => 4500.00,
            ],
            [
                'material_id' => $brick->id,
                'material_name' => 'Red Bricks',
                'source_type' => 'Own Warehouse',
                'source_reference' => 'WH-001',
                'supplier_name' => 'Central Warehouse',
                'quantity' => 50.00,
                'unit' => 'Nos',
                'unit_id' => $this->unit->id,
                'unit_price' => 12.00,
                'total_price' => 600.00,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/stock-transfers', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    // Check parent transfer
    $this->assertDatabaseHas('stock_transfers', [
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'total_items_count' => 2,
        'total_amount' => 5100.00,
    ]);

    // Check child items in stock_transfer_items
    $this->assertDatabaseHas('stock_transfer_items', [
        'material_id' => $this->cement->id,
        'source_type' => 'PO',
        'source_reference' => 'PO-2026-001',
        'quantity' => 10.00,
        'total_price' => 4500.00,
    ]);

    $this->assertDatabaseHas('stock_transfer_items', [
        'material_id' => $brick->id,
        'source_type' => 'Own Warehouse',
        'source_reference' => 'WH-001',
        'quantity' => 50.00,
        'total_price' => 600.00,
    ]);

    // Stock for cement: Site A 100 - 10 = 90, Site B 0 + 10 = 10
    $stockACement = SiteMaterialStock::where('project_site_id', $this->siteA->id)->where('material_id', $this->cement->id)->first();
    expect((float) $stockACement->current_stock)->toBe(90.0);

    $stockBCement = SiteMaterialStock::where('project_site_id', $this->siteB->id)->where('material_id', $this->cement->id)->first();
    expect((float) $stockBCement->current_stock)->toBe(10.0);

    // Stock for bricks: Site A 500 - 50 = 450, Site B 0 + 50 = 50
    $stockABrick = SiteMaterialStock::where('project_site_id', $this->siteA->id)->where('material_id', $brick->id)->first();
    expect((float) $stockABrick->current_stock)->toBe(450.0);

    $stockBBrick = SiteMaterialStock::where('project_site_id', $this->siteB->id)->where('material_id', $brick->id)->first();
    expect((float) $stockBBrick->current_stock)->toBe(50.0);
});

test('transferring material from Site A to Site B updates stock balances and captures created_by', function () {
    $payload = [
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.00,
        'unit' => 'Bags',
        'unit_id' => $this->unit->id,
        'transfer_date' => '2026-11-18',
        'return_expected' => 1,
        'vehicle_no' => 'TN 45 AB 1234',
        'driver_name' => 'Murugan',
        'challan_no' => 'DC-001',
        'remarks' => 'Urgent requirement at Site B',
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/stock-transfers', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    // Assert transfer record created in stock_transfers
    $this->assertDatabaseHas('stock_transfers', [
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.00,
        'transfer_type' => 'TRANSFER',
        'return_expected' => 1,
        'return_status' => 'Pending Return',
        'created_by' => $this->siteManager->id,
    ]);

    // Site A stock should be 100 - 10 = 90
    $stockA = SiteMaterialStock::where('project_site_id', $this->siteA->id)
        ->where('material_id', $this->cement->id)
        ->first();
    expect((float) $stockA->current_stock)->toBe(90.0);

    // Site B stock should be 0 + 10 = 10
    $stockB = SiteMaterialStock::where('project_site_id', $this->siteB->id)
        ->where('material_id', $this->cement->id)
        ->first();
    expect((float) $stockB->current_stock)->toBe(10.0);
});

test('cannot transfer material to the same site', function () {
    $payload = [
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteA->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.00,
        'transfer_date' => '2026-11-18',
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/stock-transfers', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['from_project_site_id']);
});

test('returning material from Site B back to Site A updates balances and marks transfer as Fully Returned', function () {
    // 1. Initial transfer: Site A -> Site B (10 bags)
    $transfer = app(StockTransferServiceInterface::class)->createTransfer([
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.00,
        'transfer_date' => '2026-11-18',
        'return_expected' => true,
    ], $this->siteManager->id);

    expect($transfer->return_status)->toBe('Pending Return');

    // 2. Return all 10 bags from Site B to Site A
    $returnPayload = [
        'quantity' => 10.00,
        'transfer_date' => '2026-11-19',
        'vehicle_no' => 'TN 45 AB 9999',
        'remarks' => 'Returned from new load arrived at Site B',
    ];

    $response = $this->actingAs($this->siteManager)->postJson("/stock-transfers/{$transfer->id}/return", $returnPayload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // Parent transfer should now be Fully Returned
    $transfer->refresh();
    expect($transfer->return_status)->toBe('Fully Returned');
    expect((float) $transfer->returned_quantity)->toBe(10.0);

    // Site A stock should be back to 100
    $stockA = SiteMaterialStock::where('project_site_id', $this->siteA->id)
        ->where('material_id', $this->cement->id)
        ->first();
    expect((float) $stockA->current_stock)->toBe(100.0);

    // Site B stock should be 0
    $stockB = SiteMaterialStock::where('project_site_id', $this->siteB->id)
        ->where('material_id', $this->cement->id)
        ->first();
    expect((float) $stockB->current_stock)->toBe(0.0);
});

test('partial return marks parent as Partially Returned and tracks returned_quantity', function () {
    $transfer = app(StockTransferServiceInterface::class)->createTransfer([
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.00,
        'transfer_date' => '2026-11-18',
        'return_expected' => true,
    ], $this->siteManager->id);

    // Return only 4 bags
    $response = $this->actingAs($this->siteManager)->postJson("/stock-transfers/{$transfer->id}/return", [
        'quantity' => 4.00,
        'transfer_date' => '2026-11-19',
    ]);

    $response->assertStatus(200);

    $transfer->refresh();
    expect($transfer->return_status)->toBe('Partially Returned');
    expect((float) $transfer->returned_quantity)->toBe(4.0);
    expect($transfer->pending_return_quantity)->toBe(6.0);
});

test('return quantity exceeding pending balance is rejected', function () {
    $transfer = app(StockTransferServiceInterface::class)->createTransfer([
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.00,
        'transfer_date' => '2026-11-18',
        'return_expected' => true,
    ], $this->siteManager->id);

    $response = $this->actingAs($this->siteManager)->postJson("/stock-transfers/{$transfer->id}/return", [
        'quantity' => 15.00, // exceeds 10
        'transfer_date' => '2026-11-19',
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);
});

test('stock transfer report page renders and export returns csv', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/stock-transfers');
    $response->assertStatus(200);
    $response->assertSee('Inter-Site Stock Transfer Report');

    $exportResponse = $this->actingAs($this->siteManager)->get('/reports/site/stock-transfers/export-excel');
    $exportResponse->assertStatus(200);
    expect($exportResponse->headers->get('content-type'))->toContain('text/csv');
});

test('stock_transfer and reports_stock_transfer are registered in roles and permissions menu sections', function () {
    $roleService = app(RoleServiceInterface::class);
    $sections = $roleService->getMenuSections();

    $allMenuKeys = collect($sections)->flatten(1)->pluck('key')->all();

    expect($allMenuKeys)->toContain('stock_transfer');
    expect($allMenuKeys)->toContain('reports_stock_transfer');
});

test('available materials endpoint only fetches materials that were recorded in material usage for the site', function () {
    // Create new Site C
    $siteC = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site C (New Build)',
        'location' => 'Madurai East',
        'work_type' => 'Residential',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 300000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $sand = Material::firstOrCreate(['name_en' => 'M-Sand'], [
        'name_ta' => 'எம்-சாண்ட்',
        'unit_id' => $this->unit->id,
        'status' => 'Active',
    ]);

    $breaker = Material::firstOrCreate(['name_en' => 'Demolition Breaker'], [
        'name_ta' => 'மிஷின் உடைப்பு',
        'unit_id' => $this->unit->id,
        'status' => 'Active',
    ]);

    // Record Material Usage on Site C ONLY for Demolition Breaker (NOT M-Sand)
    $usage = MaterialUsage::create([
        'usage_no' => 'MU-2026-9001',
        'usage_date' => '2026-11-18',
        'project_site_id' => $siteC->id,
        'total_items_count' => 1,
        'total_used_quantity' => 15.00,
        'total_cost' => 15000.00,
        'status' => 'Completed',
        'created_by' => $this->siteManager->id,
    ]);

    MaterialUsageItem::create([
        'material_usage_id' => $usage->id,
        'material_id' => $breaker->id,
        'material_name' => 'Demolition Breaker',
        'source_type' => 'PO',
        'source_reference' => 'PO-2026-003',
        'inward_quantity' => 15.00,
        'used_quantity' => 15.00,
        'remaining_quantity' => 0.00,
        'unit' => 'Hours',
        'unit_id' => $this->unit->id,
        'unit_price' => 1000.00,
        'total_price' => 15000.00,
    ]);

    // Query available materials for Site C
    $response = $this->actingAs($this->siteManager)->getJson("/stock-transfers/available-materials/{$siteC->id}");
    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $materials = $response->json('materials');

    // ONLY Demolition Breaker must be returned, NOT M-Sand!
    expect($materials)->toHaveCount(1);
    expect($materials[0]['material_id'])->toBe($breaker->id);
    expect((float) $materials[0]['available_qty'])->toBe(15.0);
    expect($materials[0]['reference_no'])->toBe('PO-2026-003');

    // Now record a transfer of 5 hours from Site C to Site B
    $transferPayload = [
        'from_project_site_id' => $siteC->id,
        'to_project_site_id' => $this->siteB->id,
        'transfer_date' => '2026-11-18',
        'return_expected' => 1,
        'items' => [
            [
                'material_id' => $breaker->id,
                'material_name' => 'Demolition Breaker',
                'source_type' => 'PO',
                'source_reference' => 'PO-2026-003',
                'quantity' => 5.00,
                'unit' => 'Hours',
                'unit_id' => $this->unit->id,
                'unit_price' => 1000.00,
                'total_price' => 5000.00,
            ],
        ],
    ];

    $transferResp = $this->actingAs($this->siteManager)->postJson('/stock-transfers', $transferPayload);
    $transferResp->assertStatus(200);

    // Available quantity at Site C should now be reduced to 10.0
    $respAfter = $this->actingAs($this->siteManager)->getJson("/stock-transfers/available-materials/{$siteC->id}");
    $matsAfter = $respAfter->json('materials');
    expect((float) $matsAfter[0]['available_qty'])->toBe(10.0);
});

test('stock transfer available materials includes both Supplier PO and Own Warehouse materials recorded in Material Usage', function () {
    // Create new Site D
    $siteD = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site D (Commercial Hub)',
        'location' => 'Madurai Central',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 600000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $helmet = Material::firstOrCreate(['name_en' => 'Safety Helmet'], [
        'name_ta' => 'பாதுகாப்பு கவசம்',
        'unit_id' => $this->unit->id,
        'status' => 'Active',
    ]);

    // Record Usage 1: Supplier PO based
    $usagePO = MaterialUsage::create([
        'usage_no' => 'MU-2026-9002',
        'usage_date' => '2026-11-19',
        'project_site_id' => $siteD->id,
        'total_items_count' => 1,
        'total_used_quantity' => 20.00,
        'total_cost' => 9000.00,
        'status' => 'Completed',
        'created_by' => $this->siteManager->id,
    ]);

    MaterialUsageItem::create([
        'material_usage_id' => $usagePO->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'source_type' => 'PO',
        'source_reference' => 'PO-2026-004',
        'inward_quantity' => 20.00,
        'used_quantity' => 20.00,
        'remaining_quantity' => 0.00,
        'unit' => 'Bags',
        'unit_id' => $this->unit->id,
        'unit_price' => 450.00,
        'total_price' => 9000.00,
    ]);

    // Record Usage 2: Own Warehouse based (without PO)
    $usageWH = MaterialUsage::create([
        'usage_no' => 'MU-2026-9003',
        'usage_date' => '2026-11-19',
        'project_site_id' => $siteD->id,
        'total_items_count' => 1,
        'total_used_quantity' => 10.00,
        'total_cost' => 2500.00,
        'status' => 'Completed',
        'created_by' => $this->siteManager->id,
    ]);

    MaterialUsageItem::create([
        'material_usage_id' => $usageWH->id,
        'material_id' => $helmet->id,
        'material_name' => 'Safety Helmet',
        'source_type' => 'Own Warehouse',
        'source_reference' => 'WH-SAFE-01',
        'inward_quantity' => 10.00,
        'used_quantity' => 10.00,
        'remaining_quantity' => 0.00,
        'unit' => 'Nos',
        'unit_id' => $this->unit->id,
        'unit_price' => 250.00,
        'total_price' => 2500.00,
    ]);

    // Query Stock Transfer available materials endpoint for Site D
    $response = $this->actingAs($this->siteManager)->getJson("/stock-transfers/available-materials/{$siteD->id}");
    $response->assertStatus(200);

    $materials = $response->json('materials');
    expect($materials)->toHaveCount(2);

    $cementItem = collect($materials)->firstWhere('material_id', $this->cement->id);
    $helmetItem = collect($materials)->firstWhere('material_id', $helmet->id);

    expect($cementItem)->not->toBeNull()
        ->and($cementItem['source_type'])->toBe('PO')
        ->and($cementItem['source_label'])->toContain('Supplier PO: PO-2026-004')
        ->and((float) $cementItem['available_qty'])->toBe(20.0);

    expect($helmetItem)->not->toBeNull()
        ->and($helmetItem['source_type'])->toBe('Own Warehouse')
        ->and($helmetItem['source_label'])->toContain('Own Warehouse')
        ->and((float) $helmetItem['available_qty'])->toBe(10.0);

    // Transfer 4 helmets from Site D to Site B
    $transferResp = $this->actingAs($this->siteManager)->postJson('/stock-transfers', [
        'from_project_site_id' => $siteD->id,
        'to_project_site_id' => $this->siteB->id,
        'transfer_date' => '2026-11-19',
        'return_expected' => 0,
        'items' => [
            [
                'material_id' => $helmet->id,
                'material_name' => 'Safety Helmet',
                'source_type' => 'Own Warehouse',
                'source_reference' => 'WH-SAFE-01',
                'quantity' => 4.00,
                'unit' => 'Nos',
                'unit_id' => $this->unit->id,
                'unit_price' => 250.00,
                'total_price' => 1000.00,
            ],
        ],
    ]);
    $transferResp->assertStatus(200);

    // Query Site D again -> Helmets available should be 10 - 4 = 6
    $respAfter = $this->actingAs($this->siteManager)->getJson("/stock-transfers/available-materials/{$siteD->id}");
    $materialsAfter = $respAfter->json('materials');
    $helmetAfter = collect($materialsAfter)->firstWhere('material_id', $helmet->id);
    expect((float) $helmetAfter['available_qty'])->toBe(6.0);
});

test('can edit stock transfer to update quantity and price, syncing stock and linked PO', function () {
    // Initial stock setup
    SiteMaterialStock::where('project_site_id', $this->siteA->id)
        ->where('material_id', $this->cement->id)
        ->update(['current_stock' => 50.00]);

    $po = PurchaseOrder::create([
        'po_number' => 'PO-ST-EDIT-01',
        'project_site_id' => $this->siteA->id,
        'supplier_name' => 'Supplier Beta',
        'order_date' => '2026-02-01',
        'subtotal' => 5000.00,
        'total_amount' => 5000.00,
        'status' => 'Ordered',
        'created_by' => $this->siteManager->id,
    ]);

    $poItem = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Cement (UltraTech)',
        'quantity' => 50.00,
        'unit_price' => 100.00,
        'total_price' => 5000.00,
    ]);

    // Create initial transfer: 10 bags @ 100 = 1000 from Site A to Site B
    $transferResp = $this->actingAs($this->siteManager)->postJson('/stock-transfers', [
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'transfer_date' => '2026-02-02',
        'return_expected' => 1,
        'items' => [
            [
                'material_id' => $this->cement->id,
                'material_name' => 'Cement',
                'source_type' => 'PO',
                'source_reference' => 'PO-ST-EDIT-01',
                'quantity' => 10.00,
                'unit' => 'Bags',
                'unit_id' => $this->unit->id,
                'unit_price' => 100.00,
                'total_price' => 1000.00,
            ],
        ],
    ]);

    $transferResp->assertOk();
    $transferId = $transferResp->json('transfer.id');
    $transfer = StockTransfer::with('items')->find($transferId);
    $itemId = $transfer->items->first()->id;

    // Verify stocks: Site A should have 50 - 10 = 40, Site B should have 10
    $stockA = SiteMaterialStock::where('project_site_id', $this->siteA->id)->where('material_id', $this->cement->id)->first();
    $stockB = SiteMaterialStock::where('project_site_id', $this->siteB->id)->where('material_id', $this->cement->id)->first();
    expect((float) $stockA->current_stock)->toBe(40.0)
        ->and((float) $stockB->current_stock)->toBe(10.0);

    // Now edit transfer: change quantity from 10 to 15, unit price from 100 to 120
    $updateResp = $this->actingAs($this->siteManager)->postJson("/stock-transfers/{$transferId}/update", [
        'transfer_date' => '2026-02-03',
        'vehicle_no' => 'TN-58-1234',
        'driver_name' => 'Ravi',
        'challan_no' => 'CH-999',
        'remarks' => 'Updated transfer',
        'return_expected' => 1,
        'items' => [
            [
                'id' => $itemId,
                'quantity' => 15.00,
                'unit_price' => 120.00,
            ],
        ],
    ]);

    $updateResp->assertOk()->assertJsonPath('success', true);

    // Verify transfer updated
    $transfer->refresh();
    expect((float) $transfer->quantity)->toBe(15.0)
        ->and((float) $transfer->total_amount)->toBe(1800.0)
        ->and((float) $transfer->pending_return_quantity)->toBe(15.0)
        ->and($transfer->vehicle_no)->toBe('TN-58-1234');

    // Verify stock delta applied: Site A: 40 - 5 = 35; Site B: 10 + 5 = 15
    $stockA->refresh();
    $stockB->refresh();
    expect((float) $stockA->current_stock)->toBe(35.0)
        ->and((float) $stockB->current_stock)->toBe(15.0);

    // Verify PO item unit price updated to 120
    $poItem->refresh();
    expect((float) $poItem->unit_price)->toBe(120.0)
        ->and((float) $poItem->total_price)->toBe(6000.0);

    // Verify PO total updated
    $po->refresh();
    expect((float) $po->total_amount)->toBe(6000.0);
});

test('can add and remove materials when editing stock transfer', function () {
    $this->actingAs($this->siteManager);

    $sandUnit = Unit::where('name_en', 'Tonnes')->orWhere('name_ta', 'டன்')->first()
        ?? Unit::create(['name_en' => 'Tonnes', 'code' => 'TON', 'name_ta' => 'டன்', 'status' => 'Active']);

    $sand = Material::firstOrCreate(['name_en' => 'River Sand'], [
        'name_ta' => 'ஆற்று மணல்',
        'unit_id' => $sandUnit->id,
        'status' => 'Active',
    ]);

    // Initial transfer of cement (10 units) from Site A to Site B
    $transfer = StockTransfer::create([
        'transfer_no' => 'ST-TEST-ADD-REM',
        'transfer_type' => 'TRANSFER',
        'from_project_site_id' => $this->siteA->id,
        'to_project_site_id' => $this->siteB->id,
        'material_id' => $this->cement->id,
        'quantity' => 10.0,
        'unit' => 'Bags',
        'unit_id' => $this->unit->id,
        'unit_price' => 100.0,
        'total_amount' => 1000.0,
        'return_expected' => false,
        'return_status' => 'No Return Needed',
        'transfer_date' => '2026-02-01',
        'created_by' => $this->siteManager->id,
    ]);

    $initialItem = StockTransferItem::create([
        'stock_transfer_id' => $transfer->id,
        'material_id' => $this->cement->id,
        'material_name' => 'Ultratech Cement 53',
        'source_type' => 'Site Stock',
        'quantity' => 10.0,
        'returned_quantity' => 0.0,
        'unit' => 'Bags',
        'unit_id' => $this->unit->id,
        'unit_price' => 100.0,
        'total_price' => 1000.0,
        'return_status' => 'No Return Needed',
    ]);

    // Initial site stocks for cement: Site A 50 - 10 = 40, Site B 0 + 10 = 10
    SiteMaterialStock::updateOrCreate(
        ['project_site_id' => $this->siteA->id, 'material_id' => $this->cement->id],
        ['current_stock' => 40.0, 'unit' => 'Bags', 'unit_id' => $this->unit->id]
    );
    SiteMaterialStock::updateOrCreate(
        ['project_site_id' => $this->siteB->id, 'material_id' => $this->cement->id],
        ['current_stock' => 10.0, 'unit' => 'Bags', 'unit_id' => $this->unit->id]
    );

    // Initial site stocks for sand: Site A 100, Site B 0
    SiteMaterialStock::updateOrCreate(
        ['project_site_id' => $this->siteA->id, 'material_id' => $sand->id],
        ['current_stock' => 100.0, 'unit' => 'Tonnes', 'unit_id' => $sandUnit->id]
    );
    SiteMaterialStock::updateOrCreate(
        ['project_site_id' => $this->siteB->id, 'material_id' => $sand->id],
        ['current_stock' => 0.0, 'unit' => 'Tonnes', 'unit_id' => $sandUnit->id]
    );

    // Edit transfer: keep cement item, ADD sand item (5 tonnes @ 500)
    $updateResponse = $this->postJson(route('stock-transfers.update', $transfer->id), [
        'transfer_date' => '2026-02-02',
        'remarks' => 'Added sand',
        'items' => [
            [
                'id' => $initialItem->id,
                'quantity' => 10.0,
                'unit_price' => 100.0,
            ],
            [
                'material_id' => $sand->id,
                'material_name' => 'River Sand',
                'source_type' => 'Site Stock',
                'quantity' => 5.0,
                'unit' => 'Tonnes',
                'unit_id' => $sandUnit->id,
                'unit_price' => 500.0,
            ],
        ],
    ]);

    $updateResponse->assertOk()->assertJsonPath('success', true);

    $transfer->refresh();
    expect($transfer->items)->toHaveCount(2)
        ->and((float) $transfer->quantity)->toBe(15.0)
        ->and((float) $transfer->total_amount)->toBe(3500.0);

    // Sand stocks: Site A 100 - 5 = 95; Site B 0 + 5 = 5
    $sandStockA = SiteMaterialStock::where('project_site_id', $this->siteA->id)->where('material_id', $sand->id)->first();
    $sandStockB = SiteMaterialStock::where('project_site_id', $this->siteB->id)->where('material_id', $sand->id)->first();
    expect((float) $sandStockA->current_stock)->toBe(95.0)
        ->and((float) $sandStockB->current_stock)->toBe(5.0);

    // Now edit transfer again: REMOVE cement item, keep sand item
    $sandItem = $transfer->items->where('material_id', $sand->id)->first();
    $removeResponse = $this->postJson(route('stock-transfers.update', $transfer->id), [
        'transfer_date' => '2026-02-02',
        'items' => [
            [
                'id' => $sandItem->id,
                'quantity' => 5.0,
                'unit_price' => 500.0,
            ],
        ],
    ]);

    $removeResponse->assertOk()->assertJsonPath('success', true);

    $transfer->refresh();
    expect($transfer->items)->toHaveCount(1)
        ->and((float) $transfer->quantity)->toBe(5.0)
        ->and((float) $transfer->total_amount)->toBe(2500.0);

    // Cement stock reversed: Site A gets 10 back (40 + 10 = 50), Site B loses 10 (10 - 10 = 0)
    $cementStockA = SiteMaterialStock::where('project_site_id', $this->siteA->id)->where('material_id', $this->cement->id)->first();
    $cementStockB = SiteMaterialStock::where('project_site_id', $this->siteB->id)->where('material_id', $this->cement->id)->first();
    expect((float) $cementStockA->current_stock)->toBe(50.0)
        ->and((float) $cementStockB->current_stock)->toBe(0.0);
});
