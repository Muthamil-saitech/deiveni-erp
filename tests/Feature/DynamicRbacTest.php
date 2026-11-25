<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);

    $this->admin = User::where('email', 'owner@deiveni.com')->first();
    $this->siteManager = User::where('email', 'sm@deiveni.com')->first();
    $this->accountManager = User::where('email', 'am@deiveni.com')->first();
});

test('task 1 & 2: admin can create a new dynamic role with specific menu checkboxes', function () {
    $response = $this->actingAs($this->admin)->postJson('/roles', [
        'name' => 'Field Engineer',
        'description' => 'Site inspections and material verification',
        'menus' => ['dashboard', 'sites', 'materials', 'daily_allocation'],
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseHas('roles', [
        'name' => 'Field Engineer',
        'slug' => 'field-engineer',
    ]);

    $role = Role::where('slug', 'field-engineer')->first();
    expect($role)->not->toBeNull();
    expect($role->menus()->pluck('menu_key')->all())->toEqualCanonicalizing([
        'dashboard',
        'sites',
        'materials',
        'daily_allocation',
    ]);
});

test('task 2: admin can update role permissions dynamically', function () {
    $role = Role::create([
        'name' => 'Quality Inspector',
        'slug' => 'quality-inspector',
        'description' => 'QA auditor',
        'is_system' => false,
    ]);
    $role->syncMenus(['dashboard', 'materials']);

    $response = $this->actingAs($this->admin)->putJson("/roles/{$role->id}", [
        'name' => 'Quality Inspector',
        'description' => 'Updated QA auditor role',
        'menus' => ['dashboard', 'materials', 'purchases', 'warehouse'],
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect($role->fresh()->menus()->pluck('menu_key')->all())->toEqualCanonicalizing([
        'dashboard',
        'materials',
        'purchases',
        'warehouse',
    ]);
});

test('task 2 & 3: admin can create a user with a dynamic role and login displays only allowed menus', function () {
    // 1. Create a custom role with only materials & units
    $customRole = Role::create([
        'name' => 'Inventory Auditor',
        'slug' => 'inventory-auditor',
        'description' => 'Only audits warehouse and materials',
        'is_system' => false,
    ]);
    $customRole->syncMenus(['dashboard', 'materials', 'warehouse']);

    // 2. Create user with this role
    $userResponse = $this->actingAs($this->admin)->postJson('/users', [
        'name' => 'John Auditor',
        'email' => 'auditor@deiveni.com',
        'phone' => '98400 99999',
        'role_id' => $customRole->id,
        'status' => 'Active',
        'password' => 'Auditor@123',
    ]);

    $userResponse->assertStatus(201)
        ->assertJson(['success' => true]);

    $createdUser = User::where('email', 'auditor@deiveni.com')->first();
    expect($createdUser)->not->toBeNull();
    expect($createdUser->role_id)->toBe($customRole->id);

    // 3. Login as created user and verify menus on app.blade.php sidebar
    $pageResponse = $this->actingAs($createdUser)->get('/dashboard');
    $pageResponse->assertStatus(200);

    // Permitted menus should be visible in the sidebar
    $pageResponse->assertSee('data-menu="dashboard"', false);
    $pageResponse->assertSee('data-menu="materials"', false);
    $pageResponse->assertSee('data-menu="warehouse"', false);

    // Restricted menus should NOT be visible in the sidebar
    $pageResponse->assertDontSee('data-menu="worker_categories"', false);
    $pageResponse->assertDontSee('data-menu="units"', false);
    $pageResponse->assertDontSee('data-menu="banks"', false);
    $pageResponse->assertDontSee('data-menu="branches"', false);
    $pageResponse->assertDontSee('data-menu="properties"', false);
    $pageResponse->assertDontSee('data-menu="property_types"', false);
    $pageResponse->assertDontSee('data-menu="sites"', false);
    $pageResponse->assertDontSee('data-menu="daily_allocation"', false);
    $pageResponse->assertDontSee('data-menu="weekly_wages"', false);
    $pageResponse->assertDontSee('data-menu="site_cash"', false);
    $pageResponse->assertDontSee('data-menu="purchases"', false);
    $pageResponse->assertDontSee('data-menu="ledger"', false);
    $pageResponse->assertDontSee('data-menu="users"', false);
    $pageResponse->assertDontSee('data-menu="audit_logs"', false);
});

test('task 3: site manager sees only permitted menus based on role_menus table', function () {
    $response = $this->actingAs($this->siteManager)->get('/dashboard');
    $response->assertStatus(200);

    // Permitted
    $response->assertSee(url('/worker-categories'));
    $response->assertSee(url('/units'));
    $response->assertSee(url('/materials'));
    $response->assertSee(url('/suppliers'));
    $response->assertSee(url('/sites'));
    $response->assertSee(url('/daily-allocation'));
    $response->assertSee(url('/weekly-wages'));
    $response->assertSee(url('/site-cash'));
    $response->assertSee(url('/purchases'));
    $response->assertSee(url('/warehouse'));

    // Hidden
    $response->assertDontSee(url('/banks'));
    $response->assertDontSee(url('/branches'));
    $response->assertDontSee(url('/properties'));
    $response->assertDontSee(url('/property-types'));
    $response->assertDontSee(url('/ledger'));
    $response->assertDontSee(url('/users'));
    $response->assertDontSee(url('/audit-logs'));
});

test('task 3: account manager sees only permitted menus based on role_menus table', function () {
    $response = $this->actingAs($this->accountManager)->get('/dashboard');
    $response->assertStatus(200);

    // Permitted
    $response->assertSee(url('/banks'));
    $response->assertSee(url('/branches'));
    $response->assertSee(url('/properties'));
    $response->assertSee(url('/property-types'));
    $response->assertSee(url('/suppliers'));
    $response->assertSee(url('/ledger'));

    // Hidden
    $response->assertDontSee(url('/worker-categories'));
    $response->assertDontSee(url('/units'));
    $response->assertDontSee(url('/materials'));
    $response->assertDontSee(url('/sites'));
    $response->assertDontSee(url('/daily-allocation'));
    $response->assertDontSee(url('/weekly-wages'));
    $response->assertDontSee(url('/site-cash'));
    $response->assertDontSee(url('/purchases'));
    $response->assertDontSee(url('/warehouse'));
    $response->assertDontSee(url('/users'));
    $response->assertDontSee(url('/audit-logs'));
});

test('task 3: admin/owner sees all menus without restriction', function () {
    $response = $this->actingAs($this->admin)->get('/dashboard');
    $response->assertStatus(200);

    $response->assertSee('Dashboard');
    $response->assertSee(url('/worker-categories'));
    $response->assertSee(url('/units'));
    $response->assertSee(url('/materials'));
    $response->assertSee(url('/suppliers'));
    $response->assertSee(url('/banks'));
    $response->assertSee(url('/branches'));
    $response->assertSee(url('/properties'));
    $response->assertSee(url('/property-types'));
    $response->assertSee(url('/sites'));
    $response->assertSee(url('/daily-allocation'));
    $response->assertSee(url('/weekly-wages'));
    $response->assertSee(url('/site-cash'));
    $response->assertSee(url('/purchases'));
    $response->assertDontSee('data-menu="ledger"', false);
    $response->assertSee(url('/admin-ledger'));
    $response->assertSee(url('/warehouse'));
    $response->assertSee(url('/users'));
    $response->assertSee(url('/audit-logs'));
});

test('admin can enable worker category master for account manager role and account manager can access route', function () {
    $amRole = Role::where('slug', 'account_manager')->first();

    // 1. Initially, account manager cannot access worker-categories
    $initialResponse = $this->actingAs($this->accountManager)->get('/worker-categories');
    $initialResponse->assertRedirect(route('dashboard'));
    $initialResponse->assertSessionHas('error');

    // 2. Admin enables worker_categories for Account Manager role in database
    $currentMenus = $amRole->menus()->pluck('menu_key')->all();
    $currentMenus[] = 'worker_categories';
    $amRole->syncMenus($currentMenus);

    // 3. Now Account Manager can access worker-categories route without permission error
    $allowedResponse = $this->actingAs($this->accountManager)->get('/worker-categories');
    $allowedResponse->assertStatus(200);
    $allowedResponse->assertSee('Worker Categories');
});

test('role creation modal displays all individual report menu options under REPORTS section and reset password modal has eye button', function () {
    $response = $this->actingAs($this->admin)->get('/users');
    $response->assertStatus(200);

    // All individual report checkboxes must be present
    $response->assertSee('id="menu_reports_profit_loss"', false);
    $response->assertSee('id="menu_reports_labour_allocations"', false);
    $response->assertSee('id="menu_reports_cash_handling"', false);
    $response->assertSee('id="menu_reports_purchases_inward"', false);
    $response->assertSee('id="menu_reports_warehouse_inventory"', false);
    $response->assertSee('id="menu_reports_ledger"', false);

    // Report names should be rendered
    $response->assertSee('Profit &amp; Loss', false);
    $response->assertSee('Labour Allocation', false);
    $response->assertSee('Site Cash Handling', false);
    $response->assertSee('Purchases &amp; Inward', false);
    $response->assertSee('Warehouse Stock', false);
    $response->assertSee('Financial &amp; Ledger', false);

    // Reset password modal should have eye button
    $response->assertSee('id="toggleResetPasswordBtn"', false);
    $response->assertSee('id="toggleResetPasswordIcon"', false);
    $response->assertSee('id="newTempPassword"', false);
});

test('admin can assign specific individual report to role and user can only access that assigned report', function () {
    // 1. Create a role with ONLY labour allocations report and dashboard
    $reportRole = Role::create([
        'name' => 'Site Labour Auditor',
        'slug' => 'site-labour-auditor',
        'description' => 'Audits labour allocations only',
        'is_system' => false,
    ]);
    $reportRole->syncMenus(['dashboard', 'reports_labour_allocations']);

    $user = User::factory()->create([
        'role_id' => $reportRole->id,
        'role' => 'site-labour-auditor',
    ]);

    // 2. Can access dashboard and labour allocations report
    $this->actingAs($user)->get('/reports/site/labour-allocations')->assertStatus(200);

    // 3. Cannot access profit-loss, ledger, or other reports
    $this->actingAs($user)->get('/reports/admin/profit-loss')->assertRedirect(route('dashboard'));
    $this->actingAs($user)->get('/reports/account/ledger')->assertRedirect(route('dashboard'));
    $this->actingAs($user)->get('/reports/site/warehouse-inventory')->assertRedirect(route('dashboard'));
});

test('task 1 & 2: owner menus can be customized and unchecking menus restricts owner view and access', function () {
    $ownerRole = Role::where('slug', 'owner')->first();

    // 1. Admin edits Owner role, keeping ONLY dashboard and reports_profit_loss (administration is auto-retained)
    $response = $this->actingAs($this->admin)->putJson("/roles/{$ownerRole->id}", [
        'name' => 'Owner',
        'description' => 'System Owner with customized menus',
        'menus' => ['dashboard', 'reports_profit_loss'], // unchecks all masters, site ops, ledger, warehouse
    ]);

    $response->assertStatus(200);

    // 2. Owner should have dashboard, reports_profit_loss, AND retained administration access (users, audit_logs)
    $ownerMenus = $ownerRole->fresh()->menus()->pluck('menu_key')->all();
    expect($ownerMenus)->toContain('dashboard');
    expect($ownerMenus)->toContain('reports_profit_loss');
    expect($ownerMenus)->toContain('users');
    expect($ownerMenus)->toContain('audit_logs');
    expect($ownerMenus)->not->toContain('worker_categories');
    expect($ownerMenus)->not->toContain('materials');
    expect($ownerMenus)->not->toContain('ledger');
    expect($ownerMenus)->not->toContain('warehouse');

    // 3. Visiting dashboard: only permitted menus appear in sidebar
    $dashboardRes = $this->actingAs($this->admin)->get('/dashboard');
    $dashboardRes->assertStatus(200);
    $dashboardRes->assertSee('data-menu="dashboard"', false);
    $dashboardRes->assertSee('data-menu="reports_profit_loss"', false);
    $dashboardRes->assertSee('data-menu="users"', false);
    $dashboardRes->assertSee('data-menu="audit_logs"', false);

    // Unchecked menus MUST NOT appear in the sidebar
    $dashboardRes->assertDontSee('data-menu="worker_categories"', false);
    $dashboardRes->assertDontSee('data-menu="units"', false);
    $dashboardRes->assertDontSee('data-menu="materials"', false);
    $dashboardRes->assertDontSee('data-menu="suppliers"', false);
    $dashboardRes->assertDontSee('data-menu="banks"', false);
    $dashboardRes->assertDontSee('data-menu="ledger"', false);
    $dashboardRes->assertDontSee('data-menu="warehouse"', false);

    // 4. Visiting unchecked routes directly redirects with error
    $wcRes = $this->actingAs($this->admin)->get('/worker-categories');
    $wcRes->assertRedirect(route('dashboard'));
    $wcRes->assertSessionHas('error');

    $matRes = $this->actingAs($this->admin)->get('/materials');
    $matRes->assertRedirect(route('dashboard'));
    $matRes->assertSessionHas('error');

    // 5. Permitted routes are accessible
    $this->actingAs($this->admin)->get('/reports/admin/profit-loss')->assertStatus(200);
    $this->actingAs($this->admin)->get('/users')->assertStatus(200);
    $this->actingAs($this->admin)->get('/audit-logs')->assertStatus(200);
});

test('task 2: non-owner roles cannot have administration access (users or audit_logs)', function () {
    // Attempt to create a role with administration menus
    $res = $this->actingAs($this->admin)->postJson('/roles', [
        'name' => 'Supervisor',
        'description' => 'Site supervisor',
        'menus' => ['dashboard', 'sites', 'users', 'audit_logs'],
    ]);

    $res->assertStatus(201);

    $createdRole = Role::where('name', 'Supervisor')->first();
    $menus = $createdRole->menus()->pluck('menu_key')->all();

    // users and audit_logs must be excluded
    expect($menus)->not->toContain('users');
    expect($menus)->not->toContain('audit_logs');
    expect($menus)->toContain('dashboard');
    expect($menus)->toContain('sites');
});

test('task 1: role creation validation returns 422 with error messages and view has validation feedback elements', function () {
    // 1. Role validation check for required and unique name
    $resEmpty = $this->actingAs($this->admin)->postJson('/roles', [
        'name' => '',
    ]);
    $resEmpty->assertStatus(422)
        ->assertJsonValidationErrors(['name']);

    $resDuplicate = $this->actingAs($this->admin)->postJson('/roles', [
        'name' => 'Owner',
    ]);
    $resDuplicate->assertStatus(422)
        ->assertJsonValidationErrors(['name']);

    // 2. View check for novalidate and validation feedback containers
    $viewRes = $this->actingAs($this->admin)->get('/users');
    $viewRes->assertStatus(200);
    $viewRes->assertSee('id="roleForm"', false);
    $viewRes->assertSee('novalidate', false);
    $viewRes->assertSee('id="errRoleName"', false);
    $viewRes->assertSee('id="errRoleDescription"', false);
    $viewRes->assertSee('id="errRoleMenus"', false);
    $viewRes->assertSee('id="saveRoleSpinner"', false);
    $viewRes->assertSee('id="saveRoleBtnText"', false);
});

test('task 1: user creation validation returns 422 with error messages and view has validation feedback elements', function () {
    // 1. User validation check for required fields, unique email, valid role
    $resEmpty = $this->actingAs($this->admin)->postJson('/users', []);
    $resEmpty->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'phone', 'role_id', 'status']);

    $resDuplicateEmail = $this->actingAs($this->admin)->postJson('/users', [
        'name' => 'Test User',
        'email' => 'owner@deiveni.com',
        'phone' => '9840012345',
        'role_id' => $this->admin->role_id,
        'status' => 'Active',
    ]);
    $resDuplicateEmail->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    // 2. View check for user form validation elements
    $viewRes = $this->actingAs($this->admin)->get('/users');
    $viewRes->assertStatus(200);
    $viewRes->assertSee('id="userForm"', false);
    $viewRes->assertSee('id="errUserName"', false);
    $viewRes->assertSee('id="errUserEmail"', false);
    $viewRes->assertSee('id="errUserPhone"', false);
    $viewRes->assertSee('id="errUserRole"', false);
    $viewRes->assertSee('id="errUserStatus"', false);
    $viewRes->assertSee('id="errUserPassword"', false);
    $viewRes->assertSee('id="saveUserSpinner"', false);
    $viewRes->assertSee('id="saveUserBtnText"', false);
});
