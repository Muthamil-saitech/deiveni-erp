<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationExpense;
use App\Models\ProjectClientStatement;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WeeklyWage;
use App\Models\WeeklyWageExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $this->client = Client::create([
        'name' => 'Senthil Kumar',
        'phone' => '9876543210',
        'address' => 'KK Nagar, Madurai',
        'created_by' => $this->siteManager->id,
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Grand Villa Construction',
        'location' => 'Madurai',
        'work_type' => 'Residential',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Chettinad Cements',
        'contact_person' => 'Sundaram',
        'phone' => '9444222333',
        'status' => 'Active',
    ]);

    $this->po = PurchaseOrder::create([
        'po_number' => 'PO-2026-TEST-01',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'order_date' => '2026-11-15',
        'status' => 'Order Received',
        'subtotal' => 20500.00,
        'total_amount' => 20500.00,
        'created_by' => $this->siteManager->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $this->po->id,
        'material_name' => 'Grade 53 Cement',
        'quantity' => 50,
        'unit' => 'Bags',
        'unit_price' => 410.00,
        'total_price' => 20500.00,
    ]);
});

test('project site detail page displays client statement button and client print history section', function () {
    $response = $this->actingAs($this->siteManager)->get("/sites/{$this->site->encrypted_id}");

    $response->assertStatus(200);
    $response->assertSee('Client');
    $response->assertSee('Client Statements & Print History', false);
    $response->assertSee('No Client Statements Generated Yet');
});

test('user can open client statement preparation page with pre-loaded project expenses and editable amounts', function () {
    $response = $this->actingAs($this->siteManager)->get("/sites/{$this->site->encrypted_id}/client-statement");

    $response->assertStatus(200);
    $response->assertSee('Client Project Statement');
    $response->assertSee('Grade 53 Cement');
    $response->assertSee('20500');
    $response->assertSee('item-amount');
    $response->assertSee('Print & Save A4', false);
});

test('user can save custom edited client statement and view A4 print statement', function () {
    $saveResponse = $this->actingAs($this->siteManager)->postJson("/sites/{$this->site->encrypted_id}/client-statement", [
        'statement_number' => 'CST-2026-0099',
        'statement_date' => '2026-11-23',
        'title' => 'Foundation & Slab Casting Statement',
        'client_name' => 'Senthil Kumar',
        'client_phone' => '9876543210',
        'client_address' => 'KK Nagar, Madurai',
        'items' => [
            [
                'category' => 'Material Purchase',
                'description' => 'Grade 53 Cement (Client Rate with transport)',
                'quantity' => 50,
                'unit' => 'Bags',
                'original_amount' => 20500.00,
                'billed_amount' => 25000.00, // Custom edited amount!
            ],
            [
                'category' => 'Additional Work',
                'description' => 'Site clearance and levelling work',
                'quantity' => 1,
                'unit' => 'Job',
                'original_amount' => 0.00,
                'billed_amount' => 5000.00,
            ],
        ],
        'subtotal' => 30000.00,
        'tax_percent' => 0.00,
        'tax_amount' => 0.00,
        'discount_amount' => 0.00,
        'total_amount' => 30000.00,
        'notes' => 'Please clear the payment within 5 business days.',
    ]);

    $saveResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'statement_number' => 'CST-2026-0099',
        ]);

    $this->assertDatabaseHas('project_client_statements', [
        'statement_number' => 'CST-2026-0099',
        'project_site_id' => $this->site->id,
        'total_amount' => 30000.00,
        'client_name' => 'Senthil Kumar',
    ]);

    $statement = ProjectClientStatement::where('statement_number', 'CST-2026-0099')->first();

    // Verify A4 print view
    $printResponse = $this->actingAs($this->siteManager)->get("/sites/client-statements/{$statement->encrypted_id}/print");
    $printResponse->assertStatus(200);
    $printResponse->assertSee('DEIVENI CONSTRUCTIONS');
    $printResponse->assertSee('CST-2026-0099');
    $printResponse->assertSee('30,000.00');
    $printResponse->assertSee('Grade 53 Cement (Client Rate with transport)');
    $printResponse->assertSee('Rupees Thirty Thousand Only');

    // Verify that the saved statement appears on the project site detail page
    $siteDetailResponse = $this->actingAs($this->siteManager)->get("/sites/{$this->site->encrypted_id}");
    $siteDetailResponse->assertStatus(200);
    $siteDetailResponse->assertSee('CST-2026-0099');
    $siteDetailResponse->assertSee('30,000.00');
    $siteDetailResponse->assertSee('Senthil Kumar');
});

test('user can delete a saved client statement', function () {
    $statement = ProjectClientStatement::create([
        'project_site_id' => $this->site->id,
        'statement_number' => 'CST-DEL-01',
        'statement_date' => '2026-11-23',
        'title' => 'Temporary Statement',
        'client_name' => 'Test Client',
        'items' => [],
        'subtotal' => 1000.00,
        'total_amount' => 1000.00,
        'created_by' => $this->siteManager->id,
    ]);

    $deleteResponse = $this->actingAs($this->siteManager)->deleteJson("/sites/client-statements/{$statement->encrypted_id}");
    $deleteResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('project_client_statements', [
        'id' => $statement->id,
    ]);
});

test('client statement separates date-wise items and weekly expenses with tamil priority on items and clean headers', function () {
    // 1. Create a statement with date items and weekly expenses
    $payload = [
        'statement_number' => 'CST-2026-WEEK-01',
        'statement_date' => '2026-11-24',
        'title' => 'Weekly Site & Materials Statement',
        'client_name' => 'Senthil Kumar',
        'items' => [
            [
                'category' => 'Material Purchase',
                'description' => 'River Sand',
                'name_ta' => 'ஆற்று மணல்',
                'name_en' => 'River Sand',
                'date' => '2026-11-10',
                'quantity' => 2,
                'unit' => 'Units',
                'original_amount' => 14000.00,
                'billed_amount' => 16000.00,
                'is_weekly' => 0,
            ],
            [
                'category' => 'Weekly Expenses',
                'description' => 'Weekly Expenses (03/11/2026 to 09/11/2026)',
                'name_ta' => 'வாராந்திர தள செலவுகள்',
                'name_en' => 'Weekly Expenses',
                'start_date' => '2026-11-03',
                'end_date' => '2026-11-09',
                'date' => '2026-11-03',
                'quantity' => 1,
                'unit' => 'Week',
                'original_amount' => 4500.00,
                'billed_amount' => 5000.00,
                'is_weekly' => 1,
            ],
        ],
        'subtotal' => 21000.00,
        'tax_percent' => 0.00,
        'tax_amount' => 0.00,
        'discount_amount' => 0.00,
        'total_amount' => 21000.00,
    ];

    $response = $this->actingAs($this->siteManager)->postJson("/sites/{$this->site->encrypted_id}/client-statement", $payload);
    $response->assertStatus(200);

    $statement = ProjectClientStatement::where('statement_number', 'CST-2026-WEEK-01')->first();
    expect($statement)->not->toBeNull();

    // Verify print view renders both Date-wise items and Weekly expenses cleanly
    $printRes = $this->actingAs($this->siteManager)->get("/sites/client-statements/{$statement->encrypted_id}/print");
    $printRes->assertStatus(200);

    // Tamil priority: Tamil text present prominently
    $printRes->assertSee('ஆற்று மணல்');
    // English subtitle in small text
    $printRes->assertSee('River Sand');
    // Weekly expenses section
    $printRes->assertSee('WEEKLY SITE & OPERATIONAL EXPENSES', false);
    $printRes->assertSee('03 Nov 2026');
    $printRes->assertSee('09 Nov 2026');
    $printRes->assertSee('16,000.00');
    $printRes->assertSee('5,000.00');
    $printRes->assertSee('21,000.00');

    // Verify preparation view has Weekly Expenses section and clean headers
    $prepRes = $this->actingAs($this->siteManager)->get("/sites/{$this->site->encrypted_id}/client-statement");
    $prepRes->assertStatus(200);
    $prepRes->assertSee('Weekly Site & Operational Expenses', false);
    $prepRes->assertSee('weeklyExpensesTable');
    $prepRes->assertSee('btnAddWeeklyItem');
});

test('weekly expenses in client statement calculates only daily allocation same-day expenses and weekly wage expenses without duplicates', function () {
    // 1. Create a Daily Allocation with same-day expenses
    $dailyAlloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-16',
        'status' => 'Approved',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationExpense::create([
        'daily_allocation_id' => $dailyAlloc->id,
        'item_name' => 'Tea & Snacks for workers',
        'quantity' => 1,
        'unit_price' => 250.00,
        'amount' => 250.00,
    ]);

    // 2. Create a Weekly Wage with weekly site expenses & settlement adjustments
    $weeklyWage = WeeklyWage::create([
        'project_site_id' => $this->site->id,
        'week_start_date' => '2026-11-16',
        'week_end_date' => '2026-11-22',
        'total_labour_wages' => 5000.00,
        'total_daily_expenses' => 250.00,
        'total_weekly_expenses' => 600.00,
        'net_payout' => 5850.00,
        'created_by' => $this->siteManager->id,
    ]);

    WeeklyWageExpense::create([
        'weekly_wage_id' => $weeklyWage->id,
        'item_name' => 'Machine Breaker Rent',
        'quantity' => 1,
        'unit_price' => 600.00,
        'amount' => 600.00,
    ]);

    // 3. Open statement preparation page
    $response = $this->actingAs($this->siteManager)->get("/sites/{$this->site->encrypted_id}/client-statement");
    $response->assertStatus(200);

    // Sum should be 250 + 600 = 850 for the week 16/11/2026 to 22/11/2026
    $response->assertSee('850');
    $response->assertSee('Tea &amp; Snacks for workers', false);
    $response->assertSee('Machine Breaker Rent');

    // Confirm that the redundant textarea input is removed from the item/description column
    $response->assertDontSee('<textarea class="form-control form-control-sm item-desc" rows="1"', false);
});
