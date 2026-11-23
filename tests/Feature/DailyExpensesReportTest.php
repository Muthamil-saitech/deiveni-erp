<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationExpense;
use App\Models\ProjectSite;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ownerRole = Role::where('slug', 'owner')->first()
        ?? Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner', 'is_system' => true]);

    $this->smRole = Role::where('slug', 'site_manager')->first()
        ?? Role::where('slug', 'site-manager')->first()
        ?? Role::firstOrCreate(['slug' => 'site_manager'], ['name' => 'Site Manager', 'is_system' => true]);

    $this->amRole = Role::where('slug', 'account_manager')->first()
        ?? Role::where('slug', 'account-manager')->first()
        ?? Role::firstOrCreate(['slug' => 'account_manager'], ['name' => 'Account Manager', 'is_system' => true]);

    // Ensure reports_daily_expenses permission
    RoleMenu::firstOrCreate(['role_id' => $this->ownerRole->id, 'menu_key' => 'reports_daily_expenses']);
    RoleMenu::firstOrCreate(['role_id' => $this->smRole->id, 'menu_key' => 'reports_daily_expenses']);

    // Ensure AM role does NOT have reports_daily_expenses
    RoleMenu::where('role_id', $this->amRole->id)->where('menu_key', 'reports_daily_expenses')->delete();

    $this->owner = User::factory()->create([
        'role' => UserRole::Owner,
        'role_id' => $this->ownerRole->id,
    ]);

    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
        'role_id' => $this->smRole->id,
    ]);

    $this->accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
        'role_id' => $this->amRole->id,
    ]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'created_by' => $this->owner->id,
    ]);

    $this->site1 = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Alpha',
        'location' => 'Madurai',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->owner->id,
    ]);

    $this->site2 = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Beta',
        'location' => 'Chennai',
        'work_type' => 'Residential',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 300000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->owner->id,
    ]);
});

test('daily expenses report page renders successfully for site manager and owner', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/daily-expenses');
    $response->assertStatus(200);
    $response->assertSee('Daily Expenses Report');
    $response->assertSee('Site Alpha');

    $responseOwner = $this->actingAs($this->owner)->get('/reports/site/daily-expenses');
    $responseOwner->assertStatus(200);
    $responseOwner->assertSee('Daily Expenses Report');
});

test('users without reports_daily_expenses permission cannot access the report', function () {
    $response = $this->actingAs($this->accountManager)->get('/reports/site/daily-expenses');
    // Either redirected to dashboard or 403 forbidden
    expect(in_array($response->status(), [302, 403], true))->toBeTrue();
});

test('daily expenses report renders table pagination with rows per page selector and no per page in filter card', function () {
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site1->id,
        'date' => Carbon::now()->toDateString(),
        'total_labour_count' => 0,
        'total_labour_wage' => 0,
        'total_expenses' => 1500,
        'grand_total' => 1500,
        'created_by' => $this->siteManager->id,
    ]);

    // Create 15 expense items
    for ($i = 1; $i <= 15; $i++) {
        $pad = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
        DailyAllocationExpense::create([
            'daily_allocation_id' => $alloc->id,
            'item_name' => "BatchItem-{$pad}",
            'quantity' => 1,
            'unit_price' => 100,
            'amount' => 100,
        ]);
    }

    $response = $this->actingAs($this->siteManager)->get('/reports/site/daily-expenses');
    $response->assertStatus(200);

    // Verify filter card does NOT contain "Per Page"
    $response->assertDontSee('<label class="form-label small fw-semibold mb-1 text-muted">Per Page</label>', false);

    // Verify table pagination container and controls exist
    $response->assertSee('id="tablePaginationContainer"', false);
    $response->assertSee('Rows per page:');
    $response->assertSee('id="perPageSelect"', false);
    $response->assertSee('id="paginationSummary"', false);
    $response->assertSee('id="paginationNav"', false);
    $response->assertSee('15 records found');
    $response->assertSee('BatchItem-15');
    $response->assertSee('BatchItem-01');
});

test('filter by project site and search query filters daily expenses accurately', function () {
    $alloc1 = DailyAllocation::create([
        'project_site_id' => $this->site1->id,
        'date' => Carbon::now()->toDateString(),
        'total_labour_count' => 0,
        'total_labour_wage' => 0,
        'total_expenses' => 150,
        'grand_total' => 150,
        'created_by' => $this->siteManager->id,
    ]);

    $alloc2 = DailyAllocation::create([
        'project_site_id' => $this->site2->id,
        'date' => Carbon::now()->toDateString(),
        'total_labour_count' => 0,
        'total_labour_wage' => 0,
        'total_expenses' => 200,
        'grand_total' => 200,
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationExpense::create([
        'daily_allocation_id' => $alloc1->id,
        'item_name' => 'Tea and Biscuits',
        'quantity' => 5,
        'unit_price' => 30,
        'amount' => 150,
    ]);

    DailyAllocationExpense::create([
        'daily_allocation_id' => $alloc2->id,
        'item_name' => 'Drinking Water 20L',
        'quantity' => 4,
        'unit_price' => 50,
        'amount' => 200,
    ]);

    // Filter by site1 only
    $resSite1 = $this->actingAs($this->siteManager)->get('/reports/site/daily-expenses?site_id='.$this->site1->id);
    $resSite1->assertStatus(200);
    $resSite1->assertSee('Tea and Biscuits');
    $resSite1->assertDontSee('Drinking Water 20L');

    // Filter by search query
    $resSearch = $this->actingAs($this->siteManager)->get('/reports/site/daily-expenses?search=Drinking');
    $resSearch->assertStatus(200);
    $resSearch->assertSee('Drinking Water 20L');
    $resSearch->assertDontSee('Tea and Biscuits');
});

test('export excel endpoint streams CSV with daily expenses data', function () {
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site1->id,
        'date' => Carbon::now()->toDateString(),
        'total_labour_count' => 0,
        'total_labour_wage' => 0,
        'total_expenses' => 80,
        'grand_total' => 80,
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationExpense::create([
        'daily_allocation_id' => $alloc->id,
        'item_name' => 'Emergency Screws',
        'quantity' => 2,
        'unit_price' => 40,
        'amount' => 80,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/daily-expenses/export-excel');
    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Emergency Screws');
    expect($content)->toContain('Site Alpha');
    expect($content)->toContain('Daily Expenses Report');
});
