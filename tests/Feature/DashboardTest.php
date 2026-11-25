<?php

use App\Enums\UserRole;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WeeklyWage;
use App\Models\WorkerCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $this->client = Client::create([
        'name' => 'Karthik Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Villa Sapphire Site',
        'location' => 'Anna Nagar',
        'work_type' => 'New Build',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 4500000.00,
        'spent' => 25000.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $this->category = WorkerCategory::where('name_ta', 'மேஸ்திரி')->first() ?? WorkerCategory::create([
        'name_ta' => 'மேஸ்திரி',
        'name_en' => 'Head Mason',
        'code' => 'CAT-DASH-01',
        'daily_wage' => 1200.00,
        'shift' => '8 Hours',
        'status' => 'Active',
    ]);

    $this->warehouseItem = WarehouseItem::create([
        'sku' => 'WH-MAT-DASH-01',
        'name_en' => 'Cement Bag',
        'name_ta' => 'சிமெண்ட்',
        'category' => 'Materials',
        'unit' => 'Bags',
        'current_stock' => 50,
        'min_threshold' => 10,
        'unit_rate' => 380.00,
        'status' => 'Active',
    ]);

    $this->admin = User::factory()->create([
        'role' => UserRole::Owner,
    ]);

    $this->accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);
});

test('site manager dashboard renders dynamic metrics, sites and wages correctly', function () {
    // Create daily allocation
    $today = Carbon::today()->toDateString();
    $allocation = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $today,
        'total_labour_count' => 3,
        'total_labour_wage' => 3600.00,
        'total_expenses' => 400.00,
        'grand_total' => 4000.00,
        'trade_summary' => '3 மேஸ்திரி',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $allocation->id,
        'worker_category_id' => $this->category->id,
        'category_code' => $this->category->code,
        'name_ta' => $this->category->name_ta,
        'name_en' => $this->category->name_en,
        'daily_wage' => 1200.00,
        'master_wage' => 1200.00,
        'worker_count' => 3,
        'subtotal' => 3600.00,
    ]);

    // Create site cash transaction
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'flow_type' => 'inflow',
        'amount' => 50000.00,
        'in_amount' => 50000.00,
        'out_amount' => 0.00,
        'is_expense' => false,
        'payment_date' => $today,
        'created_by' => $this->siteManager->id,
    ]);

    // Create weekly wage
    WeeklyWage::create([
        'project_site_id' => $this->site->id,
        'week_start_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
        'week_end_date' => Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString(),
        'total_labour_man_days' => 3,
        'total_labour_wages' => 3600.00,
        'total_daily_expenses' => 400.00,
        'total_weekly_expenses' => 0.00,
        'net_payout' => 4000.00,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Villa Sapphire Site');
    $response->assertSee('Anna Nagar');
    $response->assertSee('Karthik Client');
    $response->assertSeeTextInOrder(['3', 'Present']);
    $response->assertSee('1 Site');
    $response->assertSee('50,000');
    $response->assertSee('4,000');
    $response->assertSee('Items');
    $response->assertViewHas('activeSitesCount', 1);
    $response->assertViewHas('labourCount', 3);
    $response->assertViewHas('cashInHand', 50000.0);
});

test('admin can access dashboard view with data', function () {
    $response = $this->actingAs($this->admin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Dashboard Overview');
});

test('account manager can access dashboard view with data', function () {
    $response = $this->actingAs($this->accountManager)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Dashboard Overview');
});

test('account manager dashboard renders dynamic ledger metrics, bank accounts, rental units, suppliers and recent ledger entries', function () {
    $today = Carbon::today()->toDateString();

    // 1. Bank and branch master
    $bank = Bank::create([
        'bank_name' => 'Canara Bank Master',
        'bank_name_ta' => 'கனரா வங்கி',
        'status' => 'Active',
    ]);
    Branch::create([
        'bank_id' => $bank->id,
        'branch_name' => 'Madurai Main',
        'branch_name_ta' => 'மதுரை மெயின்',
        'ifsc_code' => 'CNRB0001234',
        'contact_person' => 'Manager G',
        'phone' => '9840199999',
        'valuation_fee' => 4500.00,
        'status' => 'Active',
    ]);

    // 2. Property & Rental units
    $property = Property::create([
        'property_name' => 'Deiveni Tower Plaza',
        'property_name_ta' => 'தெய்வேனி டவர் பிளாசா',
        'status' => 'Active',
    ]);
    PropertyType::create([
        'property_id' => $property->id,
        'shop_home_office_name' => 'Suite 101 Ground Floor',
        'property_type' => 'Commercial Shop',
        'location' => 'Town Hall Road',
        'tenant_name' => 'Kannan Textiles',
        'tenant_phone' => '9840288888',
        'monthly_rent' => 25000.00,
        'security_deposit' => 100000.00,
        'rent_due_day' => '5th',
        'status' => 'Active',
    ]);

    // 3. Supplier and Purchase Order
    $initialSuppliersCount = Supplier::where('status', 'Active')->count();
    $supplier = Supplier::create([
        'name' => 'Apex Steel Supplies',
        'contact_person' => 'Ramesh Head',
        'phone' => '9840377777',
        'address' => 'Industrial Area',
        'status' => 'Active',
    ]);
    PurchaseOrder::create([
        'po_number' => 'PO-DASH-999',
        'order_date' => $today,
        'project_site_id' => $this->site->id,
        'supplier_id' => $supplier->id,
        'supplier_name' => $supplier->name,
        'source_type' => 'supplier',
        'status' => 'Pending',
        'delivery_status' => 'Pending',
        'subtotal' => 40000.00,
        'tax_amount' => 0.00,
        'total_amount' => 40000.00,
        'created_by' => $this->accountManager->id,
    ]);

    // 4. Ledger income and expense
    Ledger::create([
        'entry_date' => $today,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 75000.00,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'client_name' => $this->client->name,
        'description' => 'Milestone 1 Advance Payment Received',
        'created_by' => $this->accountManager->id,
    ]);

    Ledger::create([
        'entry_date' => $today,
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 18500.00,
        'supplier_id' => $supplier->id,
        'supplier_name' => $supplier->name,
        'description' => 'Apex Steel Material Advance Bill',
        'created_by' => $this->accountManager->id,
    ]);

    $response = $this->actingAs($this->accountManager)->get('/dashboard');

    $response->assertStatus(200);

    // Assert dynamic KPI values are passed
    $response->assertViewHas('todayLedgerIncome', 75000.0);
    $response->assertViewHas('todayLedgerExpense', 18500.0);
    $response->assertViewHas('ledgerClosingBalance', 56500.0);
    $response->assertViewHas('activeBanksCount', 1);
    $response->assertViewHas('activeBranchesCount', 1);
    $response->assertViewHas('activePropertiesCount', 1);
    $response->assertViewHas('activeRentalUnitsCount', 1);
    $response->assertViewHas('totalMonthlyRent', 25000.0);
    $response->assertViewHas('activeSuppliersCount', $initialSuppliersCount + 1);
    $response->assertViewHas('pendingSupplierPayables', 40000.0);

    // Assert rendered HTML displays dynamic values
    $response->assertSee('75,000');
    $response->assertSee('18,500');
    $response->assertSee('56,500');
    $response->assertSee('Canara Bank Master');
    $response->assertSee('Madurai Main');
    $response->assertSee('25,000');
    $response->assertSee('40,000');
    $response->assertSee('Milestone 1 Advance Payment Received');
    $response->assertSee('Apex Steel Material Advance Bill');
    $response->assertSee('Apex Steel Supplies');

    // Assert static mock data is removed
    $response->assertDontSee('₹15,398');
    $response->assertDontSee('₹8,800');
    $response->assertDontSee('₹7,996');
    $response->assertDontSee('HDFC Current A/C');
});

test('newly created site manager with hyphenated role slug sees site manager dashboard', function () {
    $role = Role::where('slug', 'site_manager')->orWhere('name', 'Site Manager')->firstOrFail();

    $newSiteManager = User::factory()->create([
        'name' => 'Syed SM',
        'role_id' => $role->id,
        'role' => $role->slug,
    ]);

    expect($newSiteManager->isSiteManager())->toBeTrue();
    expect($newSiteManager->isAccountManager())->toBeFalse();
    expect($newSiteManager->isAdmin())->toBeFalse();

    $response = $this->actingAs($newSiteManager)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Weekly Wages');
    $response->assertSee('Site Cash');
    $response->assertSee('Warehouse Store');
    $response->assertDontSee("Today's Income");
    $response->assertDontSee("Today's Expense");
    $response->assertDontSee('Cash in Hand');
});

test('newly created account manager with hyphenated role slug sees account manager dashboard', function () {
    $role = Role::where('slug', 'account_manager')->orWhere('name', 'Account Manager')->firstOrFail();

    $newAccountManager = User::factory()->create([
        'name' => 'New AM',
        'role_id' => $role->id,
        'role' => $role->slug,
    ]);

    expect($newAccountManager->isAccountManager())->toBeTrue();
    expect($newAccountManager->isSiteManager())->toBeFalse();
    expect($newAccountManager->isAdmin())->toBeFalse();

    $response = $this->actingAs($newAccountManager)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee("Today's Income", false);
    $response->assertSee("Today's Expense", false);
    $response->assertSee('Running Balance', false);
    $response->assertSee('Bank Accounts', false);
    $response->assertSee('Rental Units', false);
    $response->assertDontSee('Cash in Hand', false);
});

test('admin user with owner role slug sees admin dashboard', function () {
    $role = Role::where('slug', 'owner')->orWhere('name', 'Owner')->firstOrFail();

    $newAdmin = User::factory()->create([
        'name' => 'New Owner',
        'role_id' => $role->id,
        'role' => $role->slug,
    ]);

    expect($newAdmin->isAdmin())->toBeTrue();
    expect($newAdmin->isSiteManager())->toBeFalse();
    expect($newAdmin->isAccountManager())->toBeFalse();

    $response = $this->actingAs($newAdmin)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee("Today's Income", false);
    $response->assertSee("Today's Expense", false);
    $response->assertSee('Active Sites', false);
    $response->assertSee('Cash in Hand', false);
    $response->assertSee('Expense Distribution', false);
    $response->assertSee('Supplier Payables', false);
});

test('admin can filter dashboard data by period tabs and custom dates', function (string $period) {
    $admin = User::where('role', 'owner')->first() ?? User::factory()->create([
        'name' => 'Owner Admin',
        'role' => 'owner',
    ]);

    $response = $this->actingAs($admin)->get('/dashboard?period='.$period);

    $response->assertStatus(200);
    $response->assertSee(match ($period) {
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'this_year' => 'This Year',
        'all' => 'All Time',
        default => 'Today',
    }, false);
})->with(['today', 'this_week', 'last_week', 'this_month', 'this_year', 'all']);

test('admin can filter dashboard by custom date range', function () {
    $admin = User::where('role', 'owner')->first() ?? User::factory()->create([
        'name' => 'Owner Admin',
        'role' => 'owner',
    ]);

    $response = $this->actingAs($admin)->get('/dashboard?period=custom&start_date=2026-11-01&end_date=2026-11-20');

    $response->assertStatus(200);
    $response->assertSee('01 Nov - 20 Nov 2026', false);
});

test('admin dashboard metrics, po orders, and ledgers strictly filter according to active period', function () {
    $admin = User::where('role', 'owner')->first() ?? User::factory()->create([
        'name' => 'Owner Admin',
        'role' => 'owner',
    ]);

    $today = Carbon::today()->toDateString();
    $tenDaysAgo = Carbon::today()->subDays(10)->toDateString();

    $supplier = Supplier::first() ?? Supplier::create([
        'name' => 'Period Test Supplier',
        'contact_person' => 'Supplier Contact',
        'phone' => '9840123456',
        'status' => 'Active',
    ]);

    // Transaction for today
    Ledger::create([
        'entry_date' => $today,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 5000.00,
        'description' => 'Today Payment Received',
        'created_by' => $admin->id,
    ]);

    // Transaction 10 days ago
    Ledger::create([
        'entry_date' => $tenDaysAgo,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 15000.00,
        'description' => 'Past Payment Received',
        'created_by' => $admin->id,
    ]);

    // Purchase order 10 days ago
    PurchaseOrder::create([
        'po_number' => 'PO-PERIOD-10D',
        'order_date' => $tenDaysAgo,
        'project_site_id' => $this->site->id,
        'supplier_id' => $supplier->id,
        'supplier_name' => $supplier->name,
        'source_type' => 'supplier',
        'status' => 'Pending',
        'delivery_status' => 'Pending',
        'subtotal' => 8500.00,
        'tax_amount' => 0.00,
        'total_amount' => 8500.00,
        'created_by' => $admin->id,
    ]);

    // 1. When filtering by today
    $todayRes = $this->actingAs($admin)->get('/dashboard?period=today');
    $todayRes->assertStatus(200);
    $todayRes->assertViewHas('periodIncome', 5000.0);
    $todayRes->assertViewHas('periodPurchasesTotal', 0.0);
    $todayRes->assertSee('Today Payment Received');
    $todayRes->assertDontSee('Past Payment Received');

    // 2. When filtering by custom dates covering 10 days ago
    $customRes = $this->actingAs($admin)->get("/dashboard?period=custom&start_date={$tenDaysAgo}&end_date={$tenDaysAgo}");
    $customRes->assertStatus(200);
    $customRes->assertViewHas('periodIncome', 15000.0);
    $customRes->assertViewHas('periodPurchasesTotal', 8500.0);
    $customRes->assertSee('Past Payment Received');
    $customRes->assertDontSee('Today Payment Received');

    // 3. When filtering by 'all' (all-time covering past, present, all years)
    $allRes = $this->actingAs($admin)->get('/dashboard?period=all');
    $allRes->assertStatus(200);
    $allRes->assertViewHas('periodIncome', 20000.0);
    $allRes->assertViewHas('periodPurchasesTotal', 8500.0);
    $allRes->assertSee('Today Payment Received');
    $allRes->assertSee('Past Payment Received');
});

test('admin dashboard all period captures multi-year entries and displays years in chart', function () {
    $admin = User::where('role', 'owner')->first() ?? User::factory()->create([
        'name' => 'Owner Admin',
        'role' => 'owner',
    ]);

    // Create entry in 2021
    Ledger::create([
        'entry_date' => '2021-05-10',
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 75000.00,
        'description' => '2021 Old Payment',
        'created_by' => $admin->id,
    ]);

    // Create entry in 2026
    Ledger::create([
        'entry_date' => '2026-03-15',
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 25000.00,
        'description' => '2026 Current Payment',
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard?period=all');
    $response->assertStatus(200);
    $response->assertViewHas('periodIncome', fn ($amount) => $amount >= 100000.0);
    $response->assertViewHas('chartLabels', fn ($labels) => in_array('2021', $labels) && in_array('2026', $labels) && ! in_array('Jan', $labels));
});
