<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\ProjectSite;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Repositories\Contracts\SiteWarehouseInventoryReportRepositoryInterface;
use App\Services\Contracts\SiteWarehouseInventoryReportServiceInterface;
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
        'name' => 'Koodal Nagar Villa Site',
        'location' => 'Madurai',
        'work_type' => 'Residential Villa',
        'start_date' => '2026-10-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 5000000,
        'status' => 'In Progress',
    ]);

    $this->item1 = WarehouseItem::create([
        'sku' => 'WH-TEST-01',
        'name_en' => 'High Grade Portland Cement',
        'name_ta' => 'போர்ட்லேண்ட் சிமெண்ட்',
        'category' => 'Raw Material',
        'unit' => 'Bags',
        'current_stock' => 120,
        'min_threshold' => 30,
        'unit_rate' => 420.00,
        'status' => 'Active',
    ]);

    $this->item2 = WarehouseItem::create([
        'sku' => 'WH-TEST-02',
        'name_en' => 'Industrial Safety Helmet',
        'name_ta' => 'பாதுகாப்பு தொப்பி',
        'category' => 'Safety Gear',
        'unit' => 'Sets',
        'current_stock' => 5,
        'min_threshold' => 15,
        'unit_rate' => 350.00,
        'status' => 'Active',
    ]);

    $this->movement1 = WarehouseMovement::create([
        'warehouse_item_id' => $this->item1->id,
        'movement_type' => 'Issue to Site',
        'quantity' => 25,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'reference_no' => 'WH-ISS-1001',
        'movement_date' => Carbon::today()->toDateString(),
        'notes' => 'Dispatched for foundation slab work',
        'created_by' => $this->siteManager->id,
    ]);

    $this->movement2 = WarehouseMovement::create([
        'warehouse_item_id' => $this->item2->id,
        'movement_type' => 'Stock Inward',
        'quantity' => 10,
        'project_site_id' => null,
        'site_name' => 'Central Warehouse',
        'reference_no' => 'WH-INW-2001',
        'movement_date' => Carbon::now()->subDays(2)->toDateString(),
        'notes' => 'Received fresh shipment from vendor',
        'created_by' => $this->admin->id,
    ]);
});

test('task 1 & 3: site warehouse inventory report renders dynamically via new controller, service and repo', function () {
    expect(app()->bound(SiteWarehouseInventoryReportRepositoryInterface::class))->toBeTrue();
    expect(app()->bound(SiteWarehouseInventoryReportServiceInterface::class))->toBeTrue();

    $response = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $response->assertStatus(200);
    $response->assertViewIs('reports.site_warehouse_inventory');
    $response->assertSee('Central Warehouse &amp; Store Inventory Report', false);
});

test('task 2: report fetches and displays data from warehouse_items and warehouse_movements tables', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $response->assertStatus(200);

    // Verify warehouse_items data
    $response->assertSee('WH-TEST-01', false);
    $response->assertSee('High Grade Portland Cement', false);
    $response->assertSee('WH-TEST-02', false);
    $response->assertSee('Industrial Safety Helmet', false);

    // Verify warehouse_movements data
    $response->assertSee('WH-ISS-1001', false);
    $response->assertSee('Koodal Nagar Villa Site', false);
    $response->assertSee('WH-INW-2001', false);
});

test('task 4: filter options has correct order and last week option has computed date range', function () {
    $service = app(SiteWarehouseInventoryReportServiceInterface::class);
    $meta = $service->getFilterMeta();

    $response = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $response->assertStatus(200);

    $content = $response->getContent();

    // Verify the strict dropdown order: today, this_week, last_week, this_month, custom
    $posToday = strpos($content, 'value="today"');
    $posThisWeek = strpos($content, 'value="this_week"');
    $posLastWeek = strpos($content, 'value="last_week"');
    $posThisMonth = strpos($content, 'value="this_month"');
    $posCustom = strpos($content, 'value="custom"');

    expect($posToday)->toBeGreaterThan(0);
    expect($posThisWeek)->toBeGreaterThan($posToday);
    expect($posLastWeek)->toBeGreaterThan($posThisWeek);
    expect($posThisMonth)->toBeGreaterThan($posLastWeek);
    expect($posCustom)->toBeGreaterThan($posThisMonth);

    // Verify last_week computed date range is present
    expect($meta['last_week']['label'])->not->toBeEmpty();
    $response->assertSee($meta['last_week']['label'], false);
});

test('task 5: report contains export options for excel, pdf, and print and streams structured csv', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $response->assertStatus(200);

    // Verify all 3 export action buttons exist
    $response->assertSee('id="exportExcelBtn"', false);
    $response->assertSee('id="exportPdfBtn"', false);
    $response->assertSee('id="printReportBtn"', false);

    // Test Excel streaming endpoint
    $csvRes = $this->actingAs($this->siteManager)->get(route('reports.site.warehouse-inventory.export-excel'));
    $csvRes->assertStatus(200);
    $csvRes->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csvContent = $csvRes->streamedContent();

    // Verify BOM and Company Headers
    expect($csvContent)->toContain(chr(0xEF).chr(0xBB).chr(0xBF));
    expect($csvContent)->toContain('DEIVENI CONSTRUCTIONS & DEVELOPERS');
    expect($csvContent)->toContain('Central Warehouse & Store Inventory Report');

    // Verify both sections exist and contain data
    expect($csvContent)->toContain('--- 1. CURRENT WAREHOUSE INVENTORY REGISTER ---');
    expect($csvContent)->toContain('WH-TEST-01');
    expect($csvContent)->toContain('High Grade Portland Cement');

    expect($csvContent)->toContain('--- 2. STOCK MOVEMENT & DISPATCH REGISTER ---');
    expect($csvContent)->toContain('WH-ISS-1001');
    expect($csvContent)->toContain('Koodal Nagar Villa Site');
});

test('task 6: stock movement report table renders with same filter logic', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $response->assertStatus(200);

    // Verify stock movement register table elements
    $response->assertSee('id="smMovementsTable"', false);
    $response->assertSee('id="smMovementsTableBody"', false);
    $response->assertSee('Stock Movement & Dispatch Register', false);
    $response->assertSee('Destination Site / Location', false);
});

test('report responds with json data for ajax requests', function () {
    $response = $this->actingAs($this->siteManager)->getJson('/reports/site/warehouse-inventory?period=this_week');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'items',
            'movements',
            'categories',
            'kpis' => [
                'total_valuation',
                'sku_count',
                'low_stock_count',
                'total_units',
                'movements_count',
                'issued_qty',
                'inward_qty',
            ],
            'filter_meta',
            'active_filters',
        ],
    ]);
});

test('other report modules remain functional and unaffected', function () {
    $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward')->assertStatus(200);
    $this->actingAs($this->siteManager)->get('/reports/site/cash-handling')->assertStatus(200);
    $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations')->assertStatus(200);
    $this->actingAs($this->admin)->get('/reports/admin/profit-loss')->assertStatus(200);
    $this->actingAs($this->admin)->get('/reports/account/ledger')->assertStatus(200);
});
