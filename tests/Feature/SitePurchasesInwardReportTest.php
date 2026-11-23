<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
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
        'status' => 'In Progress',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Annamalai Steel & Hardware',
        'phone' => '98421 55667',
        'status' => 'Active',
    ]);

    $this->unit = Unit::firstOrCreate(['name_en' => 'Bags'], ['name_ta' => 'மூட்டை', 'status' => 'Active']);

    $this->material = Material::firstOrCreate(
        ['name_en' => 'OPC 53 Cement'],
        [
            'name_ta' => 'சிமெண்ட்',
            'unit_id' => $this->unit->id,
            'unit_price' => 420.00,
            'status' => 'Active',
        ]
    );
});

test('task 1 & 3: site purchases inward report renders dynamically via new controller, service and repo', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward');

    $response->assertStatus(200);
    $response->assertViewIs('reports.site_purchases_inward');
    $response->assertViewHas(['sites', 'purchases', 'kpis', 'filterMeta', 'activeFilters']);
    $response->assertDontSee('localStorage.getItem', false);
    $response->assertDontSee('Uploaded Client Period', false);
    $response->assertDontSee('uploadedPurchases', false);
});

test('task 2: report fetches and displays data from purchase_orders and purchase_order_items tables', function () {
    $today = Carbon::today()->toDateString();

    $po = PurchaseOrder::create([
        'po_number' => 'PO-TEST-9901',
        'order_date' => $today,
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Completed',
        'delivery_status' => 'Delivered & Inwarded',
        'delivery_date' => $today,
        'subtotal' => 42000.00,
        'tax_amount' => 0.00,
        'total_amount' => 42000.00,
        'notes' => '100 Bags delivered to site',
        'created_by' => $this->siteManager->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'material_id' => $this->material->id,
        'material_name' => 'OPC 53 Cement',
        'quantity' => 100,
        'unit' => 'Bags',
        'unit_price' => 420.00,
        'total_price' => 42000.00,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward?period=all');

    $response->assertStatus(200);
    $response->assertSee('PO-TEST-9901');
    $response->assertSee('Annamalai Steel', false);
    $response->assertSee('Koodal Nagar Site', false);
    $response->assertSee('OPC 53 Cement', false);
});

test('task 4: filter options has correct order and last week option has computed date range', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward');

    $response->assertStatus(200);

    $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
    $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);
    $expectedLastWeekLabel = $lastWeekStart->format('d M').' - '.$lastWeekEnd->format('d M Y');

    // Confirm last week date range is present in HTML
    $response->assertSee($expectedLastWeekLabel);

    // Verify ordering in HTML source: today -> this_week -> last_week -> this_month -> custom
    $content = $response->getContent();
    $posToday = strpos($content, 'value="today"');
    $posThisWeek = strpos($content, 'value="this_week"');
    $posLastWeek = strpos($content, 'value="last_week"');
    $posThisMonth = strpos($content, 'value="this_month"');
    $posCustom = strpos($content, 'value="custom"');

    expect($posToday)->toBeLessThan($posThisWeek);
    expect($posThisWeek)->toBeLessThan($posLastWeek);
    expect($posLastWeek)->toBeLessThan($posThisMonth);
    expect($posThisMonth)->toBeLessThan($posCustom);
});

test('task 5: report contains export options for excel, pdf, and print and streams structured csv', function () {
    $today = Carbon::today()->toDateString();

    $po = PurchaseOrder::create([
        'po_number' => 'PO-EXPORT-8899',
        'order_date' => $today,
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Ordered',
        'delivery_status' => 'Inward Pending',
        'subtotal' => 25000.00,
        'total_amount' => 25000.00,
        'created_by' => $this->siteManager->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'material_id' => $this->material->id,
        'material_name' => 'OPC 53 Cement',
        'quantity' => 50,
        'unit' => 'Bags',
        'unit_price' => 500.00,
        'total_price' => 25000.00,
    ]);

    $viewRes = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward');
    $viewRes->assertStatus(200);
    $viewRes->assertSee('exportExcelBtn', false);
    $viewRes->assertSee('exportPdfBtn', false);
    $viewRes->assertSee('printReportBtn', false);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward/export-excel?period=all');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition');

    $csvContent = $response->streamedContent();

    // Check BOM
    expect(str_starts_with($csvContent, "\xEF\xBB\xBF"))->toBeTrue();
    expect($csvContent)->toContain('DEIVENI CONSTRUCTIONS & DEVELOPERS');
    expect($csvContent)->toContain('Purchases & Material Inward Report');
    expect($csvContent)->toContain('PO-EXPORT-8899');
    expect($csvContent)->toContain('Annamalai Steel & Hardware');
    expect($csvContent)->toContain('OPC 53 Cement');
    expect($csvContent)->toContain('TOTALS');
    expect($csvContent)->toContain('Total Value (INR)');
});

test('report responds with json data for ajax requests', function () {
    $response = $this->actingAs($this->siteManager)
        ->getJson('/reports/site/purchases-inward?period=all');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'purchases',
            'kpis',
            'filter_meta',
            'active_filters',
        ],
    ]);
});

test('other report modules remain functional and unaffected', function () {
    // Admin Profit & Loss
    $adminRes = $this->actingAs($this->admin)->get('/reports/admin/profit-loss');
    $adminRes->assertStatus(200);

    // Labour Allocations Report
    $labourRes = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');
    $labourRes->assertStatus(200);

    // Cash Handling Report
    $cashRes = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling');
    $cashRes->assertStatus(200);

    // Warehouse Inventory
    $whRes = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $whRes->assertStatus(200);
});
