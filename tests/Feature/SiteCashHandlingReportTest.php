<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\Supplier;
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
});

test('task 1 & 3: site cash handling report renders dynamically via new controller, service and repo', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling');

    $response->assertStatus(200);
    $response->assertViewIs('reports.site_cash_handling');
    $response->assertViewHas(['sites', 'transactions', 'kpis', 'filterMeta', 'activeFilters']);
    $response->assertDontSee('localStorage.getItem', false);
    $response->assertDontSee('Uploaded Client Period', false);
    $response->assertDontSee('uploadedLedger', false);
});

test('task 2: report fetches and displays data from site_cashes table', function () {
    $today = Carbon::today()->toDateString();

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'flow_type' => 'inflow',
        'amount' => 150000,
        'in_amount' => 150000,
        'out_amount' => 0,
        'is_expense' => false,
        'payment_date' => $today,
        'payment_time' => '10:30 AM',
        'description' => 'Initial Site Advance Received',
        'created_by' => $this->siteManager->id,
    ]);

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Material Purchase',
        'flow_type' => 'outflow',
        'amount' => 25000,
        'in_amount' => 0,
        'out_amount' => 25000,
        'is_expense' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'bill_no' => 'INV-9988',
        'payment_date' => $today,
        'payment_time' => '02:15 PM',
        'description' => 'Purchased TMT Bars & Cement',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling');

    $response->assertStatus(200);
    $response->assertSee('Initial Site Advance Received');
    $response->assertSee('Purchased TMT Bars', false);
    $response->assertSee('Annamalai Steel', false);
    $response->assertSee('INV-9988');
    $response->assertSee('Koodal Nagar Site');
});

test('task 4: filter options has correct order and last week option has computed date range', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling');

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

test('task 5: export excel streams properly formatted CSV with UTF-8 BOM, headers and totals', function () {
    $today = Carbon::today()->toDateString();

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Client Collection',
        'flow_type' => 'inflow',
        'amount' => 80000,
        'in_amount' => 80000,
        'out_amount' => 0,
        'is_expense' => false,
        'payment_date' => $today,
        'payment_time' => '11:00 AM',
        'description' => 'Milestone 1 Payment from Client',
        'created_by' => $this->siteManager->id,
    ]);

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 12000,
        'in_amount' => 0,
        'out_amount' => 12000,
        'is_expense' => true,
        'payment_date' => $today,
        'payment_time' => '05:30 PM',
        'description' => 'Mestri & Helper Daily Wage',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling/export-excel?period=all');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $response->assertHeader('Content-Disposition');

    $csvContent = $response->streamedContent();

    // Check BOM
    expect(str_starts_with($csvContent, "\xEF\xBB\xBF"))->toBeTrue();
    expect($csvContent)->toContain('DEIVENI CONSTRUCTIONS & DEVELOPERS');
    expect($csvContent)->toContain('Site Cash Handling Report');
    expect($csvContent)->toContain('Milestone 1 Payment from Client');
    expect($csvContent)->toContain('Mestri & Helper Daily Wage');
    expect($csvContent)->toContain('TOTALS');
    expect($csvContent)->toContain('NET CASH BALANCE');
});

test('report responds with json data for ajax requests', function () {
    $response = $this->actingAs($this->siteManager)
        ->getJson('/reports/site/cash-handling?period=all');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'transactions',
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

    // Purchases Inward
    $purchasesRes = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward');
    $purchasesRes->assertStatus(200);

    // Warehouse Inventory
    $whRes = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $whRes->assertStatus(200);
});

test('all past transactions are passed to view for client-side date filter switching', function () {
    $pastDate = Carbon::now()->subWeeks(3)->toDateString();

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Client Collection',
        'flow_type' => 'inflow',
        'amount' => 50000,
        'in_amount' => 50000,
        'out_amount' => 0,
        'is_expense' => false,
        'payment_date' => $pastDate,
        'description' => 'Collection 3 weeks ago',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling');

    $response->assertStatus(200);
    $viewTx = $response->viewData('transactions');
    expect(count($viewTx))->toBeGreaterThanOrEqual(1);
    expect(collect($viewTx)->pluck('desc'))->toContain('Collection 3 weeks ago');
});
