<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WeeklyWage;
use App\Models\WorkerCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('newly created site manager sees empty main logic data while master and warehouse data remain visible', function () {
    $oldSiteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $newSiteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    // Master & Warehouse data (shared)
    $workerCategory = WorkerCategory::factory()->create([
        'status' => 'Active',
    ]);

    $warehouseItem = WarehouseItem::create([
        'sku' => 'WH-TEST-001',
        'name_en' => 'Concrete Mixer',
        'name_ta' => 'கலவை இயந்திரம்',
        'category' => 'Tools',
        'unit' => 'Nos',
        'current_stock' => 5,
        'min_threshold' => 1,
        'unit_rate' => 15000,
        'cash_amount' => 15000,
        'status' => 'Active',
    ]);

    // Old user's main logic data
    $oldClient = Client::factory()->create([
        'name' => 'Old Client',
        'created_by' => $oldSiteManager->id,
    ]);

    $oldSite = ProjectSite::factory()->create([
        'client_id' => $oldClient->id,
        'name' => 'Old User Site',
        'status' => 'In Progress',
        'created_by' => $oldSiteManager->id,
    ]);

    $today = Carbon::today()->toDateString();
    $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

    DailyAllocation::create([
        'project_site_id' => $oldSite->id,
        'date' => $today,
        'total_labour_count' => 10,
        'total_labour_wage' => 8000,
        'total_expenses' => 500,
        'grand_total' => 8500,
        'created_by' => $oldSiteManager->id,
    ]);

    WeeklyWage::create([
        'project_site_id' => $oldSite->id,
        'week_start_date' => $weekStart,
        'week_end_date' => $weekEnd,
        'total_labour_man_days' => 10,
        'total_labour_wages' => 8000,
        'total_daily_expenses' => 500,
        'total_weekly_expenses' => 0,
        'net_payout' => 8500,
        'created_by' => $oldSiteManager->id,
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-OLD-001',
        'project_site_id' => $oldSite->id,
        'supplier_name' => 'Old Supplier',
        'order_date' => $today,
        'subtotal' => 5000,
        'total_amount' => 5000,
        'status' => 'Ordered',
        'delivery_status' => 'Inward Pending',
        'created_by' => $oldSiteManager->id,
    ]);

    SiteCash::create([
        'project_site_id' => $oldSite->id,
        'transaction_type' => 'Advance Received',
        'flow_type' => 'inflow',
        'amount' => 25000,
        'in_amount' => 25000,
        'out_amount' => 0,
        'is_expense' => false,
        'payment_date' => $today,
        'created_by' => $oldSiteManager->id,
    ]);

    Ledger::create([
        'entry_date' => $today,
        'source' => 'Site',
        'flow' => 'income',
        'amount' => 50000,
        'description' => 'Old User Income',
        'project_site_id' => $oldSite->id,
        'created_by' => $oldSiteManager->id,
    ]);

    // Act as the new site manager
    $this->actingAs($newSiteManager);

    // 1. Dashboard: main logic metrics are empty/zero, master & warehouse metrics are visible
    $dashboardRes = $this->get('/dashboard');
    $dashboardRes->assertStatus(200);
    $dashboardRes->assertViewHas('activeSitesCount', 0);
    $dashboardRes->assertViewHas('labourCount', 0);
    $dashboardRes->assertViewHas('currentWeekWages', 0.0);
    $dashboardRes->assertViewHas('cashInHand', 0.0);
    $dashboardRes->assertViewHas('sevenDayIncome', 0.0);
    $dashboardRes->assertViewHas('sevenDayExpense', 0.0);
    $dashboardRes->assertViewHas('ledgerTotalIncome', 0.0);
    $dashboardRes->assertViewHas('totalPurchaseOrdersCount', 0);
    $dashboardRes->assertViewHas('workerCategoriesCount', WorkerCategory::where('status', 'Active')->count());
    $dashboardRes->assertViewHas('warehouseTotalItems', WarehouseItem::where('status', 'Active')->count());

    // 2. Sites & Clients JSON
    $sitesRes = $this->getJson('/sites');
    $sitesRes->assertStatus(200)
        ->assertJsonCount(0, 'data')
        ->assertJsonCount(0, 'clients');

    // 3. Daily Allocation
    $allocRes = $this->getJson("/daily-allocation/by-date?project_site_id={$oldSite->id}&date={$today}");
    $allocRes->assertStatus(200)
        ->assertJsonPath('allocation', null);

    // 4. Weekly Wages
    $wagesRes = $this->getJson("/weekly-wages/data?project_site_id={$oldSite->id}&start_date={$weekStart}&end_date={$weekEnd}");
    $wagesRes->assertStatus(200)
        ->assertJsonPath('data.weekly_wage.net_payout', '0.00');

    $inStockWarehouseCount = WarehouseItem::where('status', 'Active')->where('current_stock', '>', 0)->count();
    $totalWarehouseCount = WarehouseItem::where('status', 'Active')->count();

    // 5. Purchases: empty purchases, but warehouse items still present
    $purchasesRes = $this->getJson('/purchases');
    $purchasesRes->assertStatus(200)
        ->assertJsonCount(0, 'purchases')
        ->assertJsonCount(0, 'sites')
        ->assertJsonCount($inStockWarehouseCount, 'warehouse_items');

    // 6. Site Cash: empty transactions & POs, but warehouse items still present
    $siteCashRes = $this->getJson('/site-cash');
    $siteCashRes->assertStatus(200)
        ->assertJsonCount(0, 'data')
        ->assertJsonCount(0, 'sites')
        ->assertJsonCount(0, 'purchase_orders')
        ->assertJsonCount($inStockWarehouseCount, 'warehouse_items');

    // 7. Warehouse: warehouse items are visible
    $warehouseRes = $this->getJson('/warehouse');
    $warehouseRes->assertStatus(200)
        ->assertJsonCount($totalWarehouseCount, 'items');

    // 8. Reports: site operational reports are empty, warehouse report shows warehouse item
    $this->getJson('/reports/site/labour-allocations')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.allocations')
        ->assertJsonCount(0, 'data.weekly_wages');

    $this->getJson('/reports/site/cash-handling')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.transactions');

    $this->getJson('/reports/site/purchases-inward')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.purchases');

    $this->getJson('/reports/site/warehouse-inventory')
        ->assertStatus(200)
        ->assertJsonCount($totalWarehouseCount, 'data.items');
});

test('creating site and weekly wage records sets created_by and isolates data between users while admin sees all', function () {
    $admin = User::factory()->create(['role' => UserRole::Owner]);
    $siteManagerA = User::factory()->create(['role' => UserRole::SiteManager]);
    $siteManagerB = User::factory()->create(['role' => UserRole::SiteManager]);

    // Site Manager A creates a site
    $this->actingAs($siteManagerA)
        ->postJson('/sites', [
            'client_name' => 'Client A',
            'client_phone' => '9876543210',
            'client_address' => 'Chennai',
            'name' => 'Site A',
            'location' => 'Anna Nagar',
            'work_type' => 'New Build',
            'estimated_budget' => 1000000,
            'status' => 'In Progress',
        ])
        ->assertStatus(201);

    $siteA = ProjectSite::where('name', 'Site A')->firstOrFail();
    expect($siteA->created_by)->toBe($siteManagerA->id);
    expect($siteA->client->created_by)->toBe($siteManagerA->id);

    // Site Manager A creates a daily allocation which auto-syncs weekly_wages
    $today = Carbon::today()->toDateString();
    $this->actingAs($siteManagerA)
        ->postJson('/daily-allocation', [
            'project_site_id' => $siteA->id,
            'date' => $today,
            'total_labour_count' => 5,
            'total_labour_wage' => 4000,
            'total_expenses' => 200,
            'grand_total' => 4200,
        ])
        ->assertStatus(200);

    $weeklyWageA = WeeklyWage::where('project_site_id', $siteA->id)->firstOrFail();
    expect($weeklyWageA->created_by)->toBe($siteManagerA->id);

    // Site Manager B cannot see Site A
    $this->actingAs($siteManagerB)
        ->getJson('/sites')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');

    $this->actingAs($siteManagerB)
        ->getJson("/sites/{$siteA->id}")
        ->assertStatus(404);

    // Admin sees Site A
    $this->actingAs($admin)
        ->getJson('/sites')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');
});

test('account manager only sees their own ledger entries while admin sees all', function () {
    $admin = User::factory()->create(['role' => UserRole::Owner]);
    $accountManagerA = User::factory()->create(['role' => UserRole::AccountManager]);
    $accountManagerB = User::factory()->create(['role' => UserRole::AccountManager]);

    $today = Carbon::today()->toDateString();

    Ledger::create([
        'entry_date' => $today,
        'source' => 'Office',
        'flow' => 'income',
        'amount' => 12000,
        'description' => 'AM A Income',
        'created_by' => $accountManagerA->id,
    ]);

    $this->actingAs($accountManagerB)
        ->getJson('/ledger')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('summary.total_income', 0);

    $this->actingAs($accountManagerB)
        ->getJson('/reports/account/ledger')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data.transactions');

    $this->actingAs($accountManagerA)
        ->getJson('/ledger')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');

    // Owner/Admin cannot access /ledger (Daily Ledger disabled for Owner)
    $this->actingAs($admin)
        ->getJson('/ledger')
        ->assertStatus(403);
});

test('project site created by site manager is visible to account managers in ledger construction site select box while isolated between site managers', function () {
    $siteManagerA = User::factory()->create(['role' => UserRole::SiteManager]);
    $siteManagerB = User::factory()->create(['role' => UserRole::SiteManager]);
    $accountManager = User::factory()->create(['role' => UserRole::AccountManager]);

    $client = Client::factory()->create([
        'name' => 'SM Client',
        'created_by' => $siteManagerA->id,
    ]);

    $site = ProjectSite::factory()->create([
        'client_id' => $client->id,
        'name' => 'Metro Bridge Project',
        'status' => 'In Progress',
        'created_by' => $siteManagerA->id,
    ]);

    // Site Manager B cannot see Site A in /sites
    $this->actingAs($siteManagerB)
        ->getJson('/sites')
        ->assertStatus(200)
        ->assertJsonCount(0, 'data');

    // Account Manager visits /ledger and sees Metro Bridge Project in the construction site select box
    $response = $this->actingAs($accountManager)->get('/ledger');
    $response->assertStatus(200);
    $response->assertSee('Metro Bridge Project');
    $response->assertViewHas('activeProjectSites', function ($sites) use ($site) {
        return $sites->contains('id', $site->id);
    });
});
