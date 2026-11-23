<?php

use App\Enums\UserRole;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SiteCashService;
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

    $this->accountManager = User::firstOrCreate(
        ['email' => 'am@deiveni.com'],
        [
            'name' => 'Raja AM',
            'role' => UserRole::AccountManager,
            'phone' => '98400 33333',
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
        'name' => 'Mr. Sundaram',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'status' => 'Active',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Annamalai Nagar Site',
        'location' => 'Madurai',
        'work_type' => 'Commercial Complex',
        'start_date' => '2026-10-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 6000000,
        'status' => 'In Progress',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Kaveri Steels & Cement',
        'phone' => '98421 55667',
        'status' => 'Active',
    ]);

    $this->bank = Bank::create([
        'bank_name' => 'State Bank of India',
        'status' => 'Active',
    ]);

    $this->branch = Branch::create([
        'bank_id' => $this->bank->id,
        'branch_name' => 'Tallakulam Branch',
        'ifsc_code' => 'SBIN0001234',
        'status' => 'Active',
    ]);
});

test('task 1 & 3: account ledger report renders dynamically via new controller, service and repo', function () {
    $response = $this->actingAs($this->accountManager)->get('/reports/account/ledger');

    $response->assertStatus(200);
    $response->assertViewIs('reports.account_ledger');
    $response->assertViewHas(['sites', 'transactions', 'kpis', 'filterMeta', 'activeFilters']);
    $response->assertDontSee('localStorage.getItem', false);
    $response->assertDontSee('Uploaded Client Period', false);
    $response->assertDontSee('uploadedLedger', false);
    $response->assertDontSee('defaultLedger', false);
});

test('task 2: report fetches and displays data from ledgers table', function () {
    $today = Carbon::today()->toDateString();

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 250000,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'client_name' => $this->client->name,
        'description' => 'First Advance Received for Commercial Complex',
        'created_by' => $this->accountManager->id,
    ]);

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 45000,
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'description' => 'Cement and Steel Bulk Order Settlement',
        'created_by' => $this->accountManager->id,
    ]);

    $response = $this->actingAs($this->accountManager)->get('/reports/account/ledger');

    $response->assertStatus(200);
    $response->assertSee('First Advance Received for Commercial Complex', false);
    $response->assertSee('Cement and Steel Bulk Order Settlement', false);
    $response->assertSee('Kaveri Steels', false);
    $response->assertSee('Annamalai Nagar Site', false);
});

test('task 4: filter options has correct order and last week option has computed date range', function () {
    $response = $this->actingAs($this->accountManager)->get('/reports/account/ledger');

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

test('task 5: export options excel, pdf and print are present and excel streams structured csv', function () {
    $today = Carbon::today()->toDateString();

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 100000,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'description' => 'Client Milestone 1 Payment',
        'created_by' => $this->accountManager->id,
    ]);

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Office and other expenses',
        'amount' => 5000,
        'description' => 'Office Stationery and Refreshments',
        'created_by' => $this->accountManager->id,
    ]);

    // View has PDF, Print, and Excel export buttons
    $viewResponse = $this->actingAs($this->accountManager)->get('/reports/account/ledger');
    $viewResponse->assertStatus(200);
    $viewResponse->assertSee('id="exportExcelBtn"', false);
    $viewResponse->assertSee('id="exportPdfBtn"', false);
    $viewResponse->assertSee('id="printReportBtn"', false);
    $viewResponse->assertSee('@media print', false);

    // Test Excel route
    $csvResponse = $this->actingAs($this->accountManager)->get('/reports/account/ledger/export-excel?period=all');
    $csvResponse->assertStatus(200);
    $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $csvResponse->assertHeader('Content-Disposition');

    $csvContent = $csvResponse->streamedContent();

    // Check BOM
    expect(str_starts_with($csvContent, "\xEF\xBB\xBF"))->toBeTrue();
    expect($csvContent)->toContain('DEIVENI CONSTRUCTIONS & DEVELOPERS');
    expect($csvContent)->toContain('Account Manager Financial & Ledger Report');
    expect($csvContent)->toContain('Client Milestone 1 Payment');
    expect($csvContent)->toContain('Office Stationery and Refreshments');
    expect($csvContent)->toContain('TOTALS');
    expect($csvContent)->toContain('NET SURPLUS / BALANCE');
});

test('report responds with json data for ajax requests', function () {
    $response = $this->actingAs($this->accountManager)
        ->getJson('/reports/account/ledger?period=all');

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

test('office cash wages and site manager returns reconciliation shows received and not received status with inflow outflow', function () {
    $today = Carbon::today()->toDateString();

    // 1. Record an office cash wage ledger entry (Outflow -)
    $ledger1 = Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 4200,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'wage_cash_source' => 'office_cash',
        'description' => 'Painter & Electrician daily wages [Office Cash]',
        'created_by' => $this->accountManager->id,
    ]);

    $siteCash1 = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 4200,
        'payment_mode' => 'Cash',
        'paid_to' => 'Painter / Electrician',
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'office_cash',
        'payment_date' => $today,
        'payment_time' => '10:00 AM',
        'description' => 'Painter & Electrician daily wages [Office Cash]',
        'ledger_id' => $ledger1->id,
        'created_by' => $this->siteManager->id,
    ]);

    // 2. Record another pending wage entry (Not Received)
    $ledger2 = Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 2400,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'wage_cash_source' => 'office_cash',
        'description' => 'Electrician daily wages [Office Cash]',
        'created_by' => $this->accountManager->id,
    ]);

    $siteCash2 = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 2400,
        'payment_mode' => 'Cash',
        'paid_to' => 'Painter / Electrician',
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'office_cash',
        'payment_date' => $today,
        'payment_time' => '11:00 AM',
        'description' => 'Electrician daily wages [Office Cash]',
        'ledger_id' => $ledger2->id,
        'created_by' => $this->siteManager->id,
    ]);

    // 3. Site Manager returns cash for siteCash1 (Received)
    $siteCashReturn = app(SiteCashService::class)->createTransaction([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Balance Returned',
        'flow_type' => 'outflow',
        'amount' => 4200,
        'payment_mode' => 'Cash',
        'paid_to' => 'Account Manager',
        'wage_cash_source' => 'office_cash',
        'returned_wage_ids' => [$siteCash1->id],
        'payment_date' => $today,
        'payment_time' => '02:00 PM',
        'description' => 'Balance returned to Account Manager for Painter & Electrician wages',
    ]);

    // Check that return site cash linked siteCash1
    expect($siteCash1->fresh()->return_site_cash_id)->toBe($siteCashReturn->id);
    expect($siteCash2->fresh()->return_site_cash_id)->toBeNull();

    // Check that synced Ledger income entry was created for Account Manager
    $syncedLedger = Ledger::where('transaction_type_name', 'Balance Returned')
        ->where('amount', 4200)
        ->first();
    expect($syncedLedger)->not->toBeNull();
    expect($syncedLedger->flow)->toBe('income');

    // 4. Test Report Data
    $response = $this->actingAs($this->accountManager)->get('/reports/account/ledger');
    $response->assertStatus(200);
    $response->assertSee('Office &harr; Site Cash &amp; Wages Reconciliation', false);
    $response->assertSee('Office Cash Sent &amp; Status', false);
    $response->assertSee('Site Manager Return Receipts', false);

    // Test Ajax response structure
    $ajaxRes = $this->actingAs($this->accountManager)->getJson('/reports/account/ledger?period=all');
    $ajaxRes->assertStatus(200);

    $recData = $ajaxRes->json('data.office_cash_reconciliation');
    expect($recData)->not->toBeNull();
    expect($recData['summary']['total_sent'])->toBeGreaterThanOrEqual(6600.0);
    expect($recData['summary']['total_received'])->toBeGreaterThanOrEqual(4200.0);
    expect($recData['summary']['total_pending'])->toBeGreaterThanOrEqual(2400.0);

    $receivedItem = collect($recData['items'])->firstWhere('id', $siteCash1->id);
    expect($receivedItem['status'])->toBe('received');
    expect($receivedItem['is_received'])->toBeTrue();

    $pendingItem = collect($recData['items'])->firstWhere('id', $siteCash2->id);
    expect($pendingItem['status'])->toBe('not_received');
    expect($pendingItem['is_received'])->toBeFalse();
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
