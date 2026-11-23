<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Ledger;
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
        'name' => 'Mr. Jayakumar',
        'phone' => '9876543210',
        'address' => 'Paarai Pati, Madurai',
        'status' => 'Active',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Mr. Jayakumar Sir Site',
        'location' => 'Paarai Pati',
        'work_type' => 'Commercial Building',
        'start_date' => '2026-10-01',
        'end_date' => '2027-03-31',
        'estimated_budget' => 2000000,
        'status' => 'In Progress',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'SSS Electric Stores',
        'phone' => '98421 55667',
        'status' => 'Active',
    ]);
});

test('task 1: admin profit loss report renders dynamically with full context and without static localStorage or mocks', function () {
    $response = $this->actingAs($this->admin)->get('/reports/admin/profit-loss');

    $response->assertStatus(200);
    $response->assertViewIs('reports.admin_profit_loss');
    $response->assertViewHas([
        'user',
        'isAdmin',
        'sites',
        'reportSites',
        'allSitesData',
        'kpis',
        'statement',
        'distribution',
        'health',
        'filterMeta',
        'activeFilters',
    ]);
    $response->assertDontSee('localStorage.getItem', false);
    $response->assertDontSee('Uploaded Client Period', false);
    $response->assertDontSee('uploadedLedger', false);
    $response->assertDontSee('uploadedAllocations', false);
    $response->assertDontSee('defaultSites', false);
});

test('task 2: report calculates site-wise revenue, wages, materials, suppliers and net profit from real database records', function () {
    $today = Carbon::today()->toDateString();

    // 1. Revenue: Client Advance Received
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Client Advance',
        'flow_type' => 'inflow',
        'amount' => 500000,
        'in_amount' => 500000,
        'out_amount' => 0,
        'is_expense' => false,
        'payment_date' => $today,
        'description' => 'Initial Foundation Advance',
        'created_by' => $this->admin->id,
    ]);

    // 2. Expense: Daily Wage Payment
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 50000,
        'in_amount' => 0,
        'out_amount' => 50000,
        'is_expense' => true,
        'payment_date' => $today,
        'description' => 'Wages for Mason and Helpers',
        'created_by' => $this->admin->id,
    ]);

    // 3. Expense: Material Purchase
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Material Purchase',
        'flow_type' => 'outflow',
        'amount' => 30000,
        'in_amount' => 0,
        'out_amount' => 30000,
        'is_expense' => true,
        'payment_date' => $today,
        'description' => 'Cement and Sand purchase',
        'created_by' => $this->admin->id,
    ]);

    // 4. Expense: Supplier Payment
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'flow_type' => 'outflow',
        'amount' => 20000,
        'in_amount' => 0,
        'out_amount' => 20000,
        'is_expense' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'payment_date' => $today,
        'description' => 'Electrical items invoice settlement',
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->get('/reports/admin/profit-loss?period=all');

    $response->assertStatus(200);

    $sites = $response->viewData('reportSites');
    expect($sites)->not->toBeEmpty();

    $siteData = collect($sites)->firstWhere('id', $this->site->id);
    expect($siteData)->not->toBeNull();
    expect((float) $siteData['revenue'])->toEqualWithDelta(500000.0, 0.01);
    expect((float) $siteData['labour'])->toEqualWithDelta(50000.0, 0.01);
    expect((float) $siteData['material'])->toEqualWithDelta(30000.0, 0.01);
    expect((float) $siteData['supplier'])->toEqualWithDelta(20000.0, 0.01);
    expect((float) $siteData['total_spent'])->toEqualWithDelta(100000.0, 0.01);
    expect((float) $siteData['net_profit'])->toEqualWithDelta(400000.0, 0.01);
    expect($siteData['pnl_status'])->toBe('Profitable');

    $kpis = $response->viewData('kpis');
    expect((float) $kpis['total_revenue'])->toEqualWithDelta(500000.0, 0.01);
    expect((float) $kpis['total_expense'])->toEqualWithDelta(100000.0, 0.01);
    expect((float) $kpis['net_profit'])->toEqualWithDelta(400000.0, 0.01);
});

test('task 3: ajax endpoint returns structured profit and loss data', function () {
    $response = $this->actingAs($this->admin)->getJson('/reports/admin/profit-loss');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'sites',
            'kpis' => [
                'contract_portfolio',
                'total_revenue',
                'total_expense',
                'net_profit',
                'net_margin',
                'sites_count',
            ],
            'statement' => [
                'client_advances',
                'other_inflows',
                'gross_revenue',
                'worker_wages',
                'materials_consumables',
                'tools_rentals',
                'supplier_invoices',
                'site_sundries',
                'total_expenses',
                'net_profit',
            ],
            'distribution' => [
                'labour',
                'material',
                'supplier',
                'sundry',
            ],
            'health',
            'filter_meta',
            'active_filters',
        ],
    ]);
});

test('task 4: excel export streams csv with utf-8 bom, kpis, and pnl register', function () {
    $response = $this->actingAs($this->admin)->get('/reports/admin/profit-loss/export-excel');

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $content = $response->streamedContent();
    expect($content)->toContain('DEIVENI CONSTRUCTIONS & DEVELOPERS');
    expect($content)->toContain('Executive Profit & Loss Report');
    expect($content)->toContain('EXECUTIVE FINANCIAL SUMMARY');
    expect($content)->toContain('SITE-WISE PROFIT & LOSS REGISTER');
    expect($content)->toContain('EXECUTIVE INCOME & EXPENSE STATEMENT');
});

test('task 5: role-based access control restricts non-admin users', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/admin/profit-loss');
    $response->assertRedirect(route('dashboard'));

    $exportResponse = $this->actingAs($this->siteManager)->get('/reports/admin/profit-loss/export-excel');
    $exportResponse->assertRedirect(route('dashboard'));
});

test('task 6: report calculates only site manager site cash handling and does not duplicate account manager ledger entries', function () {
    $today = Carbon::today()->toDateString();

    // Site Manager SiteCash entries
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'flow_type' => 'inflow',
        'amount' => 500000,
        'in_amount' => 500000,
        'out_amount' => 0,
        'is_expense' => false,
        'payment_date' => $today,
        'description' => 'First advance received from account manager',
        'created_by' => $this->siteManager->id,
    ]);

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'flow_type' => 'outflow',
        'amount' => 306000,
        'in_amount' => 0,
        'out_amount' => 306000,
        'is_expense' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'payment_date' => $today,
        'description' => 'Supplier payout',
        'created_by' => $this->siteManager->id,
    ]);

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 100000,
        'in_amount' => 0,
        'out_amount' => 100000,
        'is_expense' => true,
        'payment_date' => $today,
        'description' => 'Daily wages',
        'created_by' => $this->siteManager->id,
    ]);

    // Account Manager duplicate ledger entries
    Ledger::create([
        'entry_date' => $today,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 500000,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'client_name' => 'Advance From Owner',
        'description' => 'First advance amount collect from owner',
        'created_by' => $this->admin->id,
    ]);

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 306000,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'description' => 'Supplier payment ledger entry',
        'created_by' => $this->admin->id,
    ]);

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 100000,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'description' => 'Workers wages ledger entry',
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->get('/reports/admin/profit-loss?period=all');
    $response->assertStatus(200);

    $sites = $response->viewData('reportSites');
    $siteData = collect($sites)->firstWhere('id', $this->site->id);

    // Assert that values reflect ONLY the SiteCash records without adding Ledger duplicates
    expect((float) $siteData['revenue'])->toEqualWithDelta(500000.0, 0.01);
    expect((float) $siteData['supplier'])->toEqualWithDelta(306000.0, 0.01);
    expect((float) $siteData['labour'])->toEqualWithDelta(100000.0, 0.01);
    expect((float) $siteData['total_spent'])->toEqualWithDelta(406000.0, 0.01);
    expect((float) $siteData['net_profit'])->toEqualWithDelta(94000.0, 0.01);

    $kpis = $response->viewData('kpis');
    expect((float) $kpis['total_revenue'])->toEqualWithDelta(500000.0, 0.01);
    expect((float) $kpis['total_expense'])->toEqualWithDelta(406000.0, 0.01);
    expect((float) $kpis['net_profit'])->toEqualWithDelta(94000.0, 0.01);
});

test('task 7: supplier payment shows under supplier payout column even with material description and tool rental comes under material and tools column', function () {
    $today = Carbon::today()->toDateString();

    // 1. Supplier Payment for M-Sand (description mentions sand/load, but transaction type is Supplier Payment)
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'flow_type' => 'outflow',
        'amount' => 75000,
        'in_amount' => 0,
        'out_amount' => 75000,
        'is_expense' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => 'Sri Balaji Blue Metals & M-Sand',
        'payment_date' => $today,
        'description' => 'PO-2026-001: M-Sand (Manufactured Sand) 36 Load',
        'created_by' => $this->siteManager->id,
    ]);

    // 2. Tool Rental Purchase (must come under materials & tools column)
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Tool Rental',
        'flow_type' => 'outflow',
        'amount' => 15000,
        'in_amount' => 0,
        'out_amount' => 15000,
        'is_expense' => true,
        'payment_date' => $today,
        'description' => 'Breaker machine and scaffolding rent',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->admin)->get('/reports/admin/profit-loss?period=all');
    $response->assertStatus(200);

    $sites = $response->viewData('reportSites');
    $siteData = collect($sites)->firstWhere('id', $this->site->id);

    // Supplier Payment should be in supplier payout column
    expect((float) $siteData['supplier'])->toEqualWithDelta(75000.0, 0.01);

    // Tool Rental should be in material & tools column
    expect((float) $siteData['material'])->toEqualWithDelta(15000.0, 0.01);
    expect((float) $siteData['tools'])->toEqualWithDelta(15000.0, 0.01);

    // Total spent should be 75000 + 15000 = 90000
    expect((float) $siteData['total_spent'])->toEqualWithDelta(90000.0, 0.01);
});
