<?php

use App\Enums\UserRole;
use App\Models\User;
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
});

test('task 1 & 2: site manager has 4 dedicated pages, each with filter and export, and Project Sites Progress & Expenditure is removed', function () {
    // 1. Daily Labour Allocation & Wage Sheet
    $labourRes = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');
    $labourRes->assertStatus(200);
    $labourRes->assertSee('Daily Labour Allocation & Wage Sheet', false);
    $labourRes->assertSee('filterPeriod', false);
    $labourRes->assertSee('filterSite', false);
    $labourRes->assertSee('exportExcelBtn', false);
    $labourRes->assertSee('printReportBtn', false);
    // Task 2: Project Sites Progress & Expenditure removed
    $labourRes->assertDontSee('Project Sites Progress & Expenditure', false);

    // 2. Site Cash Handling Report
    $cashRes = $this->actingAs($this->siteManager)->get('/reports/site/cash-handling');
    $cashRes->assertStatus(200);
    $cashRes->assertSee('Site Cash Handling Report', false);
    $cashRes->assertSee('filterPeriod', false);
    $cashRes->assertSee('filterSite', false);
    $cashRes->assertSee('filterFlowType', false);
    $cashRes->assertSee('exportExcelBtn', false);
    $cashRes->assertSee('printReportBtn', false);
    $cashRes->assertDontSee('Project Sites Progress & Expenditure', false);

    // 3. Purchases & Material Inward Report
    $poRes = $this->actingAs($this->siteManager)->get('/reports/site/purchases-inward');
    $poRes->assertStatus(200);
    $poRes->assertSee('Purchases & Material Inward Report', false);
    $poRes->assertSee('filterPeriod', false);
    $poRes->assertSee('filterSite', false);
    $poRes->assertSee('filterStatus', false);
    $poRes->assertSee('exportExcelBtn', false);
    $poRes->assertSee('printReportBtn', false);
    $poRes->assertDontSee('Project Sites Progress & Expenditure', false);

    // 4. Central Warehouse & Store Inventory Report
    $whRes = $this->actingAs($this->siteManager)->get('/reports/site/warehouse-inventory');
    $whRes->assertStatus(200);
    $whRes->assertSee('Central Warehouse & Store Inventory Report', false);
    $whRes->assertSee('filterCategory', false);
    $whRes->assertSee('filterStockStatus', false);
    $whRes->assertSee('exportExcelBtn', false);
    $whRes->assertSee('printReportBtn', false);
    $whRes->assertDontSee('Project Sites Progress & Expenditure', false);
});

test('task 3: account manager has 1 dedicated report page with filter and export options', function () {
    $response = $this->actingAs($this->accountManager)->get('/reports/account/ledger');

    $response->assertStatus(200);
    $response->assertSee('Account Manager Financial & Ledger Report', false);
    $response->assertSee('Daily Ledger Transaction Register', false);
    $response->assertSee('filterPeriod', false);
    $response->assertSee('filterSite', false);
    $response->assertSee('filterFlowType', false);
    $response->assertSee('exportExcelBtn', false);
    $response->assertSee('printReportBtn', false);

    // Site manager and admin sections are not present
    $response->assertDontSee('Project Sites Progress & Expenditure', false);
    $response->assertDontSee('Executive Profit & Loss Report', false);
});

test('task 4: admin has 1 dedicated profit and loss report page with filter and export options', function () {
    $response = $this->actingAs($this->admin)->get('/reports/admin/profit-loss');

    $response->assertStatus(200);
    $response->assertSee('Executive Profit & Loss Report', false);
    $response->assertSee('Site-Wise Profit & Loss Analysis', false);
    $response->assertSee('filterPeriod', false);
    $response->assertSee('filterSite', false);
    $response->assertSee('exportExcelBtn', false);
    $response->assertSee('printReportBtn', false);

    // Verify non-admin roles cannot access admin profit loss report
    $smRes = $this->actingAs($this->siteManager)->get('/reports/admin/profit-loss');
    $smRes->assertRedirect(route('dashboard'));

    $amRes = $this->actingAs($this->accountManager)->get('/reports/admin/profit-loss');
    $amRes->assertRedirect(route('dashboard'));
});

test('task 5: admin can see site manager and account manager reports also', function () {
    // Admin can access all 4 site manager report pages
    $this->actingAs($this->admin)->get('/reports/site/labour-allocations')->assertStatus(200);
    $this->actingAs($this->admin)->get('/reports/site/cash-handling')->assertStatus(200);
    $this->actingAs($this->admin)->get('/reports/site/purchases-inward')->assertStatus(200);
    $this->actingAs($this->admin)->get('/reports/site/warehouse-inventory')->assertStatus(200);

    // Admin can access account manager report page
    $this->actingAs($this->admin)->get('/reports/account/ledger')->assertStatus(200);

    // Admin can access admin report page
    $this->actingAs($this->admin)->get('/reports/admin/profit-loss')->assertStatus(200);
});

test('role boundaries: site manager cannot access account ledger, and account manager cannot access site reports', function () {
    // Site Manager cannot access Account Ledger
    $smRes = $this->actingAs($this->siteManager)->get('/reports/account/ledger');
    $smRes->assertRedirect(route('dashboard'));

    // Account Manager cannot access Site Manager reports
    $this->actingAs($this->accountManager)->get('/reports/site/labour-allocations')->assertRedirect(route('dashboard'));
    $this->actingAs($this->accountManager)->get('/reports/site/cash-handling')->assertRedirect(route('dashboard'));
    $this->actingAs($this->accountManager)->get('/reports/site/purchases-inward')->assertRedirect(route('dashboard'));
    $this->actingAs($this->accountManager)->get('/reports/site/warehouse-inventory')->assertRedirect(route('dashboard'));
});

test('reports hub page /reports has been removed and returns 404 for all users', function () {
    $this->actingAs($this->admin)->get('/reports')->assertStatus(404);
    $this->actingAs($this->siteManager)->get('/reports')->assertStatus(404);
    $this->actingAs($this->accountManager)->get('/reports')->assertStatus(404);
});
