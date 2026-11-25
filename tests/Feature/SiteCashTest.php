<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WeeklyWage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'created_by' => $this->siteManager->id,
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Madurai Main Site',
        'location' => 'Madurai',
        'work_type' => 'New Build',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Balaji Blue Metals',
        'contact_person' => 'Balaji',
        'phone' => '9876500001',
        'status' => 'Active',
    ]);
});

test('site cash page renders successfully and does not have static save or load file buttons', function () {
    $response = $this->actingAs($this->siteManager)->get('/site-cash');

    $response->assertStatus(200);
    $response->assertSee('Site Manager Cash-in-Hand Book', false);
    $response->assertSee('Total Balance in Hand');
    $response->assertSee('id="kpiBalanceInHand"', false);
    $response->assertSee('Madurai Main Site');
    $response->assertDontSee('Save to File');
    $response->assertDontSee('Load File');
    $response->assertDontSee('id="exportCashFileBtn"', false);
    $response->assertDontSee('id="importCashFileBtn"', false);
});

test('recording inflow advance does not increment spent column in project_sites', function () {
    $response = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'amount' => 50000.00,
        'bill_no' => 'ADV-001',
        'description' => 'Initial owner advance for site expenses',
    ]);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('site_cashes', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'amount' => 50000.00,
        'flow_type' => 'inflow',
        'is_expense' => false,
    ]);

    // Spent column on project_sites must remain 0.00 because advances are not expenses
    expect((float) $this->site->fresh()->spent)->toBe(0.0);
});

test('recording expenses updates the spent column in project_sites with only expense sum', function () {
    // 1. Advance: 50,000 (not expense)
    $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'amount' => 50000.00,
    ])->assertStatus(201);

    expect((float) $this->site->fresh()->spent)->toBe(0.0);

    // 2. Expense 1: Daily Wage Payment of 12,000
    $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'amount' => 12000.00,
        'description' => 'Masons and helpers daily wages',
    ])->assertStatus(201);

    // project_sites.spent must now be 12,000
    expect((float) $this->site->fresh()->spent)->toBe(12000.0);

    // 3. Expense 2: Supplier Payment of 8,500
    $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'supplier_id' => $this->supplier->id,
        'amount' => 8500.00,
        'bill_no' => 'BILL-9901',
        'description' => 'Blue metal gravel delivery',
    ])->assertStatus(201);

    // project_sites.spent must now be 12,000 + 8,500 = 20,500
    expect((float) $this->site->fresh()->spent)->toBe(20500.0);

    // 4. Non-expense: Balance Returned to AM of 5,000
    $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Balance Returned',
        'amount' => 5000.00,
        'description' => 'Excess cash returned to office',
    ])->assertStatus(201);

    // project_sites.spent must STILL be 20,500 (returning balance does not increase site spent)
    expect((float) $this->site->fresh()->spent)->toBe(20500.0);
});

test('site cash detail page renders successfully with database transaction details', function () {
    $txn = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 10000.00,
        'in_amount' => 0.00,
        'out_amount' => 10000.00,
        'is_expense' => true,
        'bill_no' => 'WAGE-101',
        'payment_date' => '2026-11-04',
        'payment_time' => '05:30 PM',
        'description' => 'Labour wages for slab casting',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->get("/site-cash/{$txn->encrypted_id}");

    $response->assertStatus(200);
    $response->assertSee('Daily Wage Payment');
    $response->assertSee('Madurai Main Site');
    $response->assertSee('WAGE-101');
    $response->assertSee('10,000');
    $response->assertSee('Labour wages for slab casting');
});

test('site cash detail page allows editing transaction and changing project site with spent recalculation', function () {
    $site2 = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Trichy Branch Site',
        'location' => 'Trichy',
        'work_type' => 'Commercial',
        'status' => 'Ongoing',
        'spent' => 0.0,
        'created_by' => $this->siteManager->id,
    ]);

    $txn = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Tool Rental',
        'flow_type' => 'outflow',
        'amount' => 5000.00,
        'in_amount' => 0.00,
        'out_amount' => 5000.00,
        'is_expense' => true,
        'bill_no' => 'RENT-101',
        'payment_date' => '2026-11-04',
        'description' => 'Scaffolding rental',
        'created_by' => $this->siteManager->id,
    ]);

    $this->site->update(['spent' => 5000.0]);

    // View detail page contains edit modal
    $detailResponse = $this->actingAs($this->siteManager)->get("/site-cash/{$txn->encrypted_id}");
    $detailResponse->assertStatus(200);
    $detailResponse->assertSee('editSiteCashModal');
    $detailResponse->assertSee('Edit Entry');

    // Update transaction to site2 with new amount
    $updateResponse = $this->actingAs($this->siteManager)->postJson("/site-cash/{$txn->id}/update", [
        'project_site_id' => $site2->id,
        'transaction_type' => 'Tool Rental',
        'amount' => 6000.00,
        'payment_date' => '2026-11-05',
        'description' => 'Updated rental on new site',
    ]);

    $updateResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    // Check DB
    $this->assertDatabaseHas('site_cashes', [
        'id' => $txn->id,
        'project_site_id' => $site2->id,
        'amount' => 6000.00,
        'description' => 'Updated rental on new site',
    ]);

    // Spent on old site should be recalculated to 0, spent on site2 should be 6000
    expect((float) $this->site->fresh()->spent)->toBe(0.0);
    expect((float) $site2->fresh()->spent)->toBe(6000.0);
});

test('transactions from account manager have readonly amount on edit and preserve original amount upon update', function () {
    $txn = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Project Cash Received',
        'flow_type' => 'inflow',
        'amount' => 50000.00,
        'in_amount' => 50000.00,
        'out_amount' => 0.00,
        'is_expense' => false,
        'paid_by' => 'Account Manager',
        'paid_to' => 'Site Manager',
        'payment_date' => '2026-11-10',
        'created_by' => $this->siteManager->id,
    ]);

    expect($txn->isFromAccountManager())->toBeTrue();

    // Verify detail page has readonly amount and Account Manager warning
    $detailResponse = $this->actingAs($this->siteManager)->get("/site-cash/{$txn->encrypted_id}");
    $detailResponse->assertStatus(200);
    $detailResponse->assertSee('From Account Manager (Read-Only)');
    $detailResponse->assertSee('readonly tabindex=-1', false);

    // Attempt to update the transaction with a tampered/different amount
    $updateResponse = $this->actingAs($this->siteManager)->postJson("/site-cash/{$txn->id}/update", [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Project Cash Received',
        'amount' => 1000.00, // Attempted tamper to 1000
        'payment_date' => '2026-11-12',
        'description' => 'Updated notes only',
    ]);

    $updateResponse->assertStatus(200)
        ->assertJson(['success' => true]);

    // Amount must strictly remain the original 50,000.00
    $freshTxn = $txn->fresh();
    expect((float) $freshTxn->amount)->toBe(50000.0);
    expect((float) $freshTxn->in_amount)->toBe(50000.0);
    expect($freshTxn->description)->toBe('Updated notes only');
});

test('deleting an expense transaction recalculates project_sites spent column', function () {
    $txn = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Tool Rental',
        'flow_type' => 'outflow',
        'amount' => 4500.00,
        'in_amount' => 0.00,
        'out_amount' => 4500.00,
        'is_expense' => true,
        'payment_date' => '2026-11-04',
        'created_by' => $this->siteManager->id,
    ]);

    $this->site->update(['spent' => 4500.00]);
    expect((float) $this->site->fresh()->spent)->toBe(4500.0);

    $this->actingAs($this->siteManager)->deleteJson("/site-cash/{$txn->id}")
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('site_cashes', ['id' => $txn->id]);
    expect((float) $this->site->fresh()->spent)->toBe(0.0);
});

test('recording transaction with cheque requires reference number and persists correctly', function () {
    // Fails without reference number
    $response = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Client Advance',
        'amount' => 75000.00,
        'payment_mode' => 'Cheque',
        'paid_to' => 'Account Manager',
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payment_reference']);

    // Succeeds with cheque reference number, bank name, and receiver name
    $successResponse = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Client Advance',
        'amount' => 75000.00,
        'payment_mode' => 'Cheque',
        'payment_reference' => 'CHQ-981245',
        'bank_name' => 'State Bank of India',
        'receiver_name' => 'Account Manager (Suresh)',
        'paid_to' => 'Account Manager',
        'bill_no' => 'ADV-CHQ-01',
    ]);
    $successResponse->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('site_cashes', [
        'project_site_id' => $this->site->id,
        'payment_mode' => 'Cheque',
        'payment_reference' => 'CHQ-981245',
        'bank_name' => 'State Bank of India',
        'receiver_name' => 'Account Manager (Suresh)',
        'paid_to' => 'Account Manager',
    ]);
});

test('recording transaction with UPI requires reference number and persists correctly with account holder details', function () {
    // Fails without reference number
    $response = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'supplier_id' => $this->supplier->id,
        'amount' => 15000.00,
        'payment_mode' => 'UPI',
        'paid_to' => 'Direct to Supplier',
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['payment_reference']);

    // Succeeds with UPI reference ID, supplier upi id, and account holder name
    $successResponse = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'supplier_id' => $this->supplier->id,
        'amount' => 15000.00,
        'payment_mode' => 'UPI',
        'payment_reference' => 'UPI-4389201948',
        'upi_id' => '9876500001@upi',
        'account_holder_name' => 'Balaji Blue Metals',
        'receiver_name' => 'Balaji Blue Metals',
        'paid_to' => 'Direct to Supplier',
    ]);
    $successResponse->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('site_cashes', [
        'project_site_id' => $this->site->id,
        'payment_mode' => 'UPI',
        'payment_reference' => 'UPI-4389201948',
        'upi_id' => '9876500001@upi',
        'account_holder_name' => 'Balaji Blue Metals',
        'paid_to' => 'Direct to Supplier',
    ]);
});

test('recording cash transaction with cash mode requires no reference number', function () {
    $response = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Owner Advance',
        'amount' => 20000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('site_cashes', [
        'project_site_id' => $this->site->id,
        'payment_mode' => 'Cash',
        'payment_reference' => null,
        'paid_to' => 'Site Manager',
    ]);
});

test('daily allocations endpoint returns saved allocations for site', function () {
    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-16',
        'total_labour_count' => 5,
        'total_labour_wage' => 5200.00,
        'total_expenses' => 150.00,
        'grand_total' => 5350.00,
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/daily-allocations?project_site_id={$this->site->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonFragment(['total_labour_wage' => '5200.00']);
});

test('daily allocations endpoint excludes allocations whose wages are already recorded in site cash', function () {
    $alloc1 = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-16',
        'total_labour_count' => 10,
        'total_labour_wage' => 10500.00,
        'total_expenses' => 0.00,
        'grand_total' => 10500.00,
        'created_by' => $this->siteManager->id,
    ]);

    $alloc2 = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-18',
        'total_labour_count' => 2,
        'total_labour_wage' => 2150.00,
        'total_expenses' => 0.00,
        'grand_total' => 2150.00,
        'created_by' => $this->siteManager->id,
    ]);

    // Record site cash for 2026-11-16
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 10500.00,
        'out_amount' => 10500.00,
        'is_expense' => true,
        'bill_no' => 'WAGE-2026-11-16',
        'payment_date' => '2026-11-18',
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/daily-allocations?project_site_id={$this->site->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $allocations = collect($response->json('allocations'));

    // 2026-11-16 must be excluded because wages were already paid and not updated
    expect($allocations->pluck('id'))->not->toContain($alloc1->id);

    // 2026-11-18 must be included because wages have not yet been recorded
    expect($allocations->pluck('id'))->toContain($alloc2->id);
});

test('daily allocations endpoint includes previously recorded allocation if it was subsequently updated', function () {
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-16',
        'total_labour_count' => 10,
        'total_labour_wage' => 10500.00,
        'total_expenses' => 0.00,
        'grand_total' => 10500.00,
        'created_by' => $this->siteManager->id,
    ]);

    $cash = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 10500.00,
        'out_amount' => 10500.00,
        'is_expense' => true,
        'bill_no' => 'WAGE-2026-11-16',
        'payment_date' => '2026-11-16',
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
        'created_by' => $this->siteManager->id,
    ]);

    // Update the allocation wages (e.g. added another worker or adjusted wages)
    $alloc->total_labour_wage = 11500.00;
    $alloc->total_labour_count = 11;
    $alloc->save();

    $response = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/daily-allocations?project_site_id={$this->site->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $allocations = collect($response->json('allocations'));

    // It should reappear and have is_wage_updated true
    expect($allocations->pluck('id'))->toContain($alloc->id);
    $found = $allocations->firstWhere('id', $alloc->id);
    expect($found['is_wage_updated'])->toBeTrue();
});

test('purchase orders endpoint excludes POs already recorded in site cash', function () {
    $po1 = PurchaseOrder::create([
        'po_number' => 'PO-2026-TEST-001',
        'order_date' => '2026-11-16',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 20000.00,
        'tax_amount' => 0.00,
        'total_amount' => 20000.00,
        'created_by' => $this->siteManager->id,
    ]);

    $po2 = PurchaseOrder::create([
        'po_number' => 'PO-2026-TEST-002',
        'order_date' => '2026-11-18',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 35000.00,
        'tax_amount' => 0.00,
        'total_amount' => 35000.00,
        'created_by' => $this->siteManager->id,
    ]);

    // Record site cash transaction for PO1
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'flow_type' => 'outflow',
        'amount' => 20000.00,
        'out_amount' => 20000.00,
        'is_expense' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'purchase_order_no' => 'PO-2026-TEST-001',
        'bill_no' => 'PO-2026-TEST-001',
        'payment_date' => '2026-11-18',
        'payment_mode' => 'Cash',
        'paid_to' => 'Direct to Supplier',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/purchase-orders?project_site_id={$this->site->id}&supplier_id={$this->supplier->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $orders = collect($response->json('orders'));

    // PO1 must be excluded because it has already been paid and unmodified
    expect($orders->pluck('po_number'))->not->toContain('PO-2026-TEST-001');

    // PO2 must be included because it has not yet been paid
    expect($orders->pluck('po_number'))->toContain('PO-2026-TEST-002');
});

test('purchase orders endpoint includes previously recorded PO if it was subsequently updated', function () {
    $po = PurchaseOrder::create([
        'po_number' => 'PO-2026-TEST-003',
        'order_date' => '2026-11-16',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 25000.00,
        'tax_amount' => 0.00,
        'total_amount' => 25000.00,
        'created_by' => $this->siteManager->id,
    ]);

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'flow_type' => 'outflow',
        'amount' => 25000.00,
        'out_amount' => 25000.00,
        'is_expense' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'purchase_order_no' => 'PO-2026-TEST-003',
        'bill_no' => 'PO-2026-TEST-003',
        'payment_date' => '2026-11-16',
        'payment_mode' => 'Cash',
        'paid_to' => 'Direct to Supplier',
        'created_by' => $this->siteManager->id,
    ]);

    // Update the PO (e.g. amount changed or PO was edited)
    $po->total_amount = 28000.00;
    $po->subtotal = 28000.00;
    $po->save();

    $response = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/purchase-orders?project_site_id={$this->site->id}&supplier_id={$this->supplier->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $orders = collect($response->json('orders'));

    // It should reappear and have is_po_updated true
    expect($orders->pluck('po_number'))->toContain('PO-2026-TEST-003');
    $found = $orders->firstWhere('po_number', 'PO-2026-TEST-003');
    expect($found['is_po_updated'])->toBeTrue();
});

test('office cash wages endpoint returns only office cash wages with trade breakdown and excludes project cash', function () {
    // 1. Create an Office Cash wage payment
    $officeWage = SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 3700.00,
        'out_amount' => 3700.00,
        'is_expense' => true,
        'payment_mode' => 'Cash',
        'paid_to' => 'Painter / Electrician',
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'office_cash',
        'extra_workers' => [['name' => 'Murugan', 'work' => 'Helper', 'salary' => 500]],
        'payment_date' => '2026-11-18',
        'description' => 'Office cash painter wages',
        'created_by' => $this->siteManager->id,
    ]);

    // 2. Create a Project Cash wage payment (which must be EXCLUDED)
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 5000.00,
        'out_amount' => 5000.00,
        'is_expense' => true,
        'payment_mode' => 'Cash',
        'paid_to' => 'Painter / Electrician',
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'project_cash',
        'payment_date' => '2026-11-18',
        'description' => 'Project cash wages',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/office-cash-wages?project_site_id={$this->site->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'total_office_cash_wages' => 3700.00,
        'total_already_returned' => 0.00,
        'net_pending_return' => 3700.00,
    ]);

    $payments = $response->json('payments');
    expect(count($payments))->toBe(1);
    expect($payments[0]['amount'])->toEqual(3700.00);
    expect($payments[0]['items'])->not->toBeEmpty();
    expect($payments[0]['items'][0]['trade'])->toContain('Murugan (Helper)');

    // 3. Now record a Balance Returned transaction for 3700 selecting the wage
    $returnResponse = $this->actingAs($this->siteManager)
        ->postJson('/site-cash', [
            'project_site_id' => $this->site->id,
            'transaction_type' => 'Balance Returned',
            'amount' => 3700.00,
            'payment_mode' => 'Cash',
            'paid_to' => 'Account Manager',
            'wage_cash_source' => 'office_cash',
            'returned_wage_ids' => [$officeWage->id],
            'payment_date' => '2026-11-18',
            'description' => 'Balance returned to Account Manager',
        ]);

    $returnResponse->assertStatus(201);

    $responseAfterReturn = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/office-cash-wages?project_site_id={$this->site->id}");

    $responseAfterReturn->assertStatus(200);
    $responseAfterReturn->assertJson([
        'success' => true,
        'total_office_cash_wages' => 3700.00,
        'total_already_returned' => 3700.00,
        'net_pending_return' => 0.00,
        'payments' => [],
    ]);
    expect($responseAfterReturn->json('returned_payments'))->not->toBeEmpty();
});

test('supplier payment purchase orders endpoint supports site_based and total scope', function () {
    $secondSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Coimbatore Project Site',
        'location' => 'Coimbatore',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'status' => 'Ongoing',
        'created_by' => $this->siteManager->id,
    ]);

    // PO for Site 1
    $poSite1 = PurchaseOrder::create([
        'po_number' => 'PO-SITE1-001',
        'order_date' => '2026-11-20',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 12000.00,
        'tax_amount' => 0.00,
        'total_amount' => 12000.00,
        'created_by' => $this->siteManager->id,
    ]);

    // PO for Site 2
    $poSite2 = PurchaseOrder::create([
        'po_number' => 'PO-SITE2-002',
        'order_date' => '2026-11-21',
        'project_site_id' => $secondSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 18000.00,
        'tax_amount' => 0.00,
        'total_amount' => 18000.00,
        'created_by' => $this->siteManager->id,
    ]);

    // Create a different site manager with their own site and PO
    $otherSiteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);
    $thirdSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Madurai Project Site',
        'location' => 'Madurai',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'status' => 'Ongoing',
        'created_by' => $otherSiteManager->id,
    ]);
    PurchaseOrder::create([
        'po_number' => 'PO-SITE3-003',
        'order_date' => '2026-11-22',
        'project_site_id' => $thirdSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 45000.00,
        'tax_amount' => 0.00,
        'total_amount' => 45000.00,
        'created_by' => $otherSiteManager->id,
    ]);

    // 1. Query with Site Based scope for Site 1 -> only PO for Site 1
    $responseSiteBased = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/purchase-orders?project_site_id={$this->site->id}&supplier_id={$this->supplier->id}&scope=site_based");

    $responseSiteBased->assertStatus(200);
    $ordersSiteBased = collect($responseSiteBased->json('orders'));
    expect($ordersSiteBased->pluck('po_number'))->toContain('PO-SITE1-001');
    expect($ordersSiteBased->pluck('po_number'))->not->toContain('PO-SITE2-002');
    expect($ordersSiteBased->pluck('po_number'))->not->toContain('PO-SITE3-003');

    // 2. Query with Total scope by siteManager -> only includes POs from Site 1 and Site 2 (assigned to siteManager)
    $responseTotal = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/purchase-orders?supplier_id={$this->supplier->id}&scope=total");

    $responseTotal->assertStatus(200);
    $ordersTotal = collect($responseTotal->json('orders'));
    expect($ordersTotal->pluck('po_number'))->toContain('PO-SITE1-001');
    expect($ordersTotal->pluck('po_number'))->toContain('PO-SITE2-002');
    expect($ordersTotal->pluck('po_number'))->not->toContain('PO-SITE3-003');
    expect($responseTotal->json('total_pending_amount'))->toEqual(30000.00);
    expect($responseTotal->json('pending_count'))->toBe(2);

    // 3. Query with Total scope by otherSiteManager -> only includes PO from Site 3 (assigned to otherSiteManager)
    $responseOther = $this->actingAs($otherSiteManager)
        ->getJson("/site-cash/purchase-orders?supplier_id={$this->supplier->id}&scope=total");

    $responseOther->assertStatus(200);
    $ordersOther = collect($responseOther->json('orders'));
    expect($ordersOther->pluck('po_number'))->toContain('PO-SITE3-003');
    expect($ordersOther->pluck('po_number'))->not->toContain('PO-SITE1-001');
    expect($ordersOther->pluck('po_number'))->not->toContain('PO-SITE2-002');
    expect($responseOther->json('total_pending_amount'))->toEqual(45000.00);
    expect($responseOther->json('pending_count'))->toBe(1);
});

test('recording supplier payment persists is_site_based flag based on scope', function () {
    // 1. Site Based supplier payment
    $siteBasedResponse = $this->actingAs($this->siteManager)
        ->postJson('/site-cash', [
            'project_site_id' => $this->site->id,
            'transaction_type' => 'Supplier Payment',
            'amount' => 15000.00,
            'payment_mode' => 'Cash',
            'paid_to' => 'Direct to Supplier',
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'supplier_payment_scope' => 'site_based',
            'purchase_order_no' => 'PO-SITE-TEST',
            'payment_date' => '2026-11-20',
        ]);

    $siteBasedResponse->assertStatus(201);
    $siteBasedTxn = SiteCash::find($siteBasedResponse->json('data.id'));
    expect($siteBasedTxn->is_site_based)->toBeTrue();

    // 2. Total supplier payment (no project_site_id required)
    $totalResponse = $this->actingAs($this->siteManager)
        ->postJson('/site-cash', [
            'transaction_type' => 'Supplier Payment',
            'amount' => 45000.00,
            'payment_mode' => 'Cash',
            'paid_to' => 'Direct to Supplier',
            'supplier_id' => $this->supplier->id,
            'supplier_name' => $this->supplier->name,
            'supplier_payment_scope' => 'total',
            'payment_date' => '2026-11-20',
            'description' => 'Total Supplier Payment to supplier',
        ]);

    $totalResponse->assertStatus(201);
    $totalTxn = SiteCash::find($totalResponse->json('data.id'));
    expect($totalTxn->is_site_based)->toBeFalse();
    expect($totalTxn->project_site_id)->toBeNull();
});

test('weekly settlement endpoint returns settled weeks with daily site cash deducted and detail view marks paid days', function () {
    $weekStart = '2026-11-16';
    $weekEnd = '2026-11-22';

    // Two daily allocations in that week: Mon (5000) and Tue (4000) => total 9000
    $allocMon = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-16',
        'total_labour_count' => 5,
        'total_labour_wage' => 5000.00,
        'total_expenses' => 0.00,
        'grand_total' => 5000.00,
        'created_by' => $this->siteManager->id,
    ]);

    $allocTue = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-17',
        'total_labour_count' => 4,
        'total_labour_wage' => 4000.00,
        'total_expenses' => 0.00,
        'grand_total' => 4000.00,
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $allocTue->id,
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'worker_count' => 2,
        'daily_wage' => 1100.00,
        'master_wage' => 1100.00,
        'subtotal' => 2200.00,
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'office_cash',
    ]);

    // Settled WeeklyWage for that week (net_payout = 9000)
    $settledWeek = WeeklyWage::create([
        'project_site_id' => $this->site->id,
        'week_start_date' => $weekStart,
        'week_end_date' => $weekEnd,
        'total_labour_man_days' => 9,
        'total_labour_wages' => 9000.00,
        'total_daily_expenses' => 0.00,
        'total_weekly_expenses' => 0.00,
        'net_payout' => 9000.00,
        'status' => 'settled',
        'settled_at' => now(),
        'created_by' => $this->siteManager->id,
    ]);

    // Draft WeeklyWage should NOT appear
    WeeklyWage::create([
        'project_site_id' => $this->site->id,
        'week_start_date' => '2026-11-09',
        'week_end_date' => '2026-11-15',
        'total_labour_man_days' => 2,
        'total_labour_wages' => 2000.00,
        'net_payout' => 2000.00,
        'status' => 'draft',
        'created_by' => $this->siteManager->id,
    ]);

    // Site Manager already paid Monday's wages (5000) via daily SiteCash
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 5000.00,
        'out_amount' => 5000.00,
        'is_expense' => true,
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
        'bill_no' => 'WAGE-2026-11-16',
        'payment_date' => '2026-11-16',
        'created_by' => $this->siteManager->id,
    ]);

    // 1. Fetch weekly settlements -> only settled week appears with balance = 9000 - 5000 = 4000
    $listRes = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/weekly-settlements?project_site_id={$this->site->id}");

    $listRes->assertStatus(200);
    $settlements = collect($listRes->json('settlements'));
    expect($settlements)->toHaveCount(1);
    expect($settlements[0]['id'])->toBe($settledWeek->id);
    expect((float) $settlements[0]['net_payout'])->toEqual(9000.00);
    expect((float) $settlements[0]['daily_paid_amount'])->toEqual(5000.00);
    expect((float) $settlements[0]['balance_amount'])->toEqual(4000.00);

    // 2. View detail page (target="_blank") shows Paid status for Monday and Balance Payable ₹4,000.00
    $detailRes = $this->actingAs($this->siteManager)
        ->get("/site-cash/weekly-settlement/{$settledWeek->encrypted_id}");
    $detailRes->assertStatus(200);
    $detailRes->assertSee('Weekly Settlement Details');
    $detailRes->assertSee('Weekly Workers Summary');
    $detailRes->assertSee('Weekly Site Expenses Breakdown');
    $detailRes->assertSee('Site Manager Paid Workers');
    $detailRes->assertSee('Account Manager Paid Workers');
    $detailRes->assertSee('Total Paid by Account Manager:');
    $detailRes->assertSee('Paid (₹5,000)');
    $detailRes->assertSee('4,000.00');

    // 3. Record Weekly Settlement payment for remaining 4000 -> week disappears from pending list
    $storeRes = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Weekly Settlement',
        'weekly_wage_id' => $settledWeek->id,
        'amount' => 4000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
        'bill_no' => "WEEKLY-{$weekStart}",
        'payment_date' => '2026-11-23',
    ]);
    $storeRes->assertStatus(201);

    $afterList = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/weekly-settlements?project_site_id={$this->site->id}");
    $afterList->assertStatus(200);
    expect($afterList->json('settlements'))->toBe([]);

    // 4. Completed Weekly Settlement transaction detail page has View Weekly Details button
    $txn = SiteCash::where('weekly_wage_id', $settledWeek->id)->latest('id')->first();
    $txnDetailRes = $this->actingAs($this->siteManager)->get("/site-cash/{$txn->encrypted_id}");
    $txnDetailRes->assertStatus(200);
    $txnDetailRes->assertSee('View Weekly Details');
    $txnDetailRes->assertSee('/site-cash/weekly-settlement/');

    // 5. Once Weekly Settlement is completed, every day & item in the detail page shows Paid
    $settledDetailRes = $this->actingAs($this->siteManager)
        ->get("/site-cash/weekly-settlement/{$settledWeek->encrypted_id}");
    $settledDetailRes->assertStatus(200);
    $settledDetailRes->assertSee('Fully Paid (Site Cash Settled)');
    $settledDetailRes->assertDontSee('Unpaid');
});
