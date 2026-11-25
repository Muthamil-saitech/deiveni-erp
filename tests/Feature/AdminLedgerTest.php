<?php

use App\Enums\UserRole;
use App\Models\AdminLedger;
use App\Models\Client;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::factory()->create([
        'role' => UserRole::Owner,
        'status' => 'Active',
    ]);

    $this->client = Client::create([
        'name' => 'Dr. K. Senthil Kumar',
        'phone' => '9444055667',
        'address' => 'Plot 12, Anna Nagar, Chennai',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site B - Clinic & Residence',
        'location' => 'Anna Nagar, Chennai',
        'work_type' => 'Alteration',
        'start_date' => '2026-03-01',
        'end_date' => '2026-09-30',
        'estimated_budget' => 2800000.00,
        'spent' => 0.00,
        'status' => 'Planning',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'SSS Electric Stores',
        'contact_person' => 'P. Kandasamy',
        'phone' => '9444777888',
        'status' => 'Active',
    ]);
});

test('admin ledger index page renders successfully with summary and records', function () {
    AdminLedger::create([
        'entry_date' => '2026-11-24',
        'category' => 'client_payment',
        'flow' => 'income',
        'client_id' => $this->client->id,
        'client_name' => $this->client->name,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'amount' => 150000.00,
        'payment_mode' => 'Bank Transfer',
        'description' => 'Advance stage payment',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->get('/admin-ledger');

    $response->assertStatus(200);
    $response->assertSee('Admin Ledger');
    $response->assertSee('Dr. K. Senthil Kumar');
    $response->assertSee('Site B - Clinic &amp; Residence', false);
    $response->assertSee('150,000.00');
    $response->assertSee('id="tablePaginationContainer"', false);
    $response->assertSee('id="perPageSelect"', false);
    $response->assertSee('id="paginationNav"', false);
    $response->assertSee('id="paginationSummary"', false);
});

test('client projects endpoint returns projects for selected client', function () {
    $response = $this->actingAs($this->owner)
        ->getJson("/admin-ledger/client-projects?client_id={$this->client->id}");

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    $projects = collect($response->json('projects'));
    expect($projects->pluck('id'))->toContain($this->site->id);
    expect($projects->pluck('name'))->toContain('Site B - Clinic & Residence');
});

test('recording client payment income validates and persists record', function () {
    $response = $this->actingAs($this->owner)->postJson('/admin-ledger', [
        'entry_date' => '2026-11-24',
        'category' => 'client_payment',
        'client_id' => $this->client->id,
        'project_site_id' => $this->site->id,
        'amount' => 75000.00,
        'payment_mode' => 'Cheque',
        'cheque_no' => 'CHQ-554422',
        'bank_name' => 'SBI',
        'description' => 'Stage 2 payment from client',
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $entry = AdminLedger::first();
    expect($entry)->not->toBeNull();
    expect($entry->category)->toBe('client_payment');
    expect($entry->flow)->toBe('income');
    expect((float) $entry->amount)->toBe(75000.00);
    expect($entry->client_id)->toBe($this->client->id);
    expect($entry->project_site_id)->toBe($this->site->id);
    expect($entry->cheque_no)->toBe('CHQ-554422');
});

test('recording supplier payment expense with site based scope validates site and persists record', function () {
    $po = PurchaseOrder::create([
        'po_number' => 'PO-TEST-999',
        'order_date' => '2026-11-20',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 35000.00,
        'tax_amount' => 0.00,
        'total_amount' => 35000.00,
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->postJson('/admin-ledger', [
        'entry_date' => '2026-11-24',
        'category' => 'supplier_payment',
        'supplier_payment_scope' => 'site_based',
        'supplier_id' => $this->supplier->id,
        'project_site_id' => $this->site->id,
        'purchase_order_no' => $po->po_number,
        'amount' => 35000.00,
        'payment_mode' => 'Cash',
        'description' => 'Supplier payment for electrical items',
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $entry = AdminLedger::first();
    expect($entry)->not->toBeNull();
    expect($entry->category)->toBe('supplier_payment');
    expect($entry->flow)->toBe('expense');
    expect($entry->is_site_based)->toBeTrue();
    expect((float) $entry->amount)->toBe(35000.00);
    expect($entry->supplier_id)->toBe($this->supplier->id);
    expect($entry->project_site_id)->toBe($this->site->id);
    expect($entry->purchase_order_no)->toBe('PO-TEST-999');
});

test('recording supplier payment expense with total scope allows null site and calculates pending POs across sites', function () {
    $secondSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site C - Coimbatore Complex',
        'location' => 'Coimbatore',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'status' => 'Ongoing',
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-SITE-A-101',
        'order_date' => '2026-11-20',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 20000.00,
        'tax_amount' => 0.00,
        'total_amount' => 20000.00,
        'created_by' => $this->owner->id,
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-SITE-B-102',
        'order_date' => '2026-11-21',
        'project_site_id' => $secondSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 30000.00,
        'tax_amount' => 0.00,
        'total_amount' => 30000.00,
        'created_by' => $this->owner->id,
    ]);

    // Check purchase-orders endpoint with scope=total
    $poResponse = $this->actingAs($this->owner)
        ->getJson("/admin-ledger/purchase-orders?supplier_id={$this->supplier->id}&scope=total");

    $poResponse->assertStatus(200);
    expect($poResponse->json('total_pending_amount'))->toEqual(50000.00);
    expect($poResponse->json('pending_count'))->toBe(2);

    // Record total supplier payment
    $storeResponse = $this->actingAs($this->owner)->postJson('/admin-ledger', [
        'entry_date' => '2026-11-24',
        'category' => 'supplier_payment',
        'supplier_payment_scope' => 'total',
        'supplier_id' => $this->supplier->id,
        'amount' => 50000.00,
        'payment_mode' => 'Bank Transfer',
        'description' => 'Total Supplier Payment to SSS Electric Stores: ₹50,000.00 (2 pending POs across all sites)',
    ]);

    $storeResponse->assertStatus(201);
    $entry = AdminLedger::first();
    expect($entry->is_site_based)->toBeFalse();
    expect($entry->project_site_id)->toBeNull();
    expect($entry->site_name)->toBe('All Sites (Total)');
    expect((float) $entry->amount)->toBe(50000.00);
});

test('admin ledger entry can be deleted successfully', function () {
    $entry = AdminLedger::create([
        'entry_date' => '2026-11-24',
        'category' => 'client_payment',
        'flow' => 'income',
        'client_id' => $this->client->id,
        'client_name' => $this->client->name,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'amount' => 10000.00,
        'payment_mode' => 'Cash',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->postJson("/admin-ledger/{$entry->id}/delete");

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
    expect(AdminLedger::find($entry->id))->toBeNull();
});

test('admin ledger detail view page renders successfully with transaction details', function () {
    $entry = AdminLedger::create([
        'entry_date' => '2026-11-24',
        'category' => 'client_payment',
        'flow' => 'income',
        'client_id' => $this->client->id,
        'client_name' => $this->client->name,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'amount' => 88000.00,
        'payment_mode' => 'Bank Transfer',
        'payment_reference' => 'UTR-987654321',
        'description' => 'Foundation stage milestone payout',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->get("/admin-ledger/{$entry->encrypted_id}");

    $response->assertStatus(200);
    $response->assertSee('Admin Ledger Entry Details');
    $response->assertSee('Dr. K. Senthil Kumar');
    $response->assertSee('88,000.00');
    $response->assertSee('UTR-987654321');
    $response->assertSee('Foundation stage milestone payout');
});

test('admin ledger entry can be updated successfully', function () {
    $entry = AdminLedger::create([
        'entry_date' => '2026-11-24',
        'category' => 'client_payment',
        'flow' => 'income',
        'client_id' => $this->client->id,
        'client_name' => $this->client->name,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'amount' => 50000.00,
        'payment_mode' => 'Cash',
        'created_by' => $this->owner->id,
    ]);

    $response = $this->actingAs($this->owner)->postJson("/admin-ledger/{$entry->id}/update", [
        'entry_date' => '2026-11-25',
        'category' => 'client_payment',
        'client_id' => $this->client->id,
        'project_site_id' => $this->site->id,
        'amount' => 60000.00,
        'payment_mode' => 'Bank Transfer',
        'description' => 'Updated amount to 60000',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $fresh = AdminLedger::find($entry->id);
    expect((float) $fresh->amount)->toBe(60000.00);
    expect($fresh->description)->toBe('Updated amount to 60000');
});

test('owner paying a PO in admin ledger hides that PO from site manager and admin ledger, showing only new POs', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);

    $managedSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Managed By SM',
        'location' => 'Velachery, Chennai',
        'work_type' => 'New Build',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'status' => 'In Progress',
        'created_by' => $siteManager->id,
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-OWN-PAID-001',
        'order_date' => '2026-11-20',
        'project_site_id' => $managedSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 25000.00,
        'tax_amount' => 0.00,
        'total_amount' => 25000.00,
        'created_by' => $siteManager->id,
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-NEW-UNPAID-002',
        'order_date' => '2026-11-22',
        'project_site_id' => $managedSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 15000.00,
        'tax_amount' => 0.00,
        'total_amount' => 15000.00,
        'created_by' => $siteManager->id,
    ]);

    // Owner pays PO-OWN-PAID-001 via Admin Ledger
    AdminLedger::create([
        'entry_date' => '2026-11-23',
        'category' => 'supplier_payment',
        'flow' => 'expense',
        'is_site_based' => true,
        'project_site_id' => $managedSite->id,
        'site_name' => $managedSite->name,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'purchase_order_no' => 'PO-OWN-PAID-001',
        'amount' => 25000.00,
        'payment_mode' => 'Bank Transfer',
        'created_by' => $this->owner->id,
    ]);

    // Site Manager checks Site Cash purchase orders -> must ONLY see PO-NEW-UNPAID-002
    $smResponse = $this->actingAs($siteManager)
        ->getJson("/site-cash/purchase-orders?project_site_id={$managedSite->id}&supplier_id={$this->supplier->id}&scope=site_based");

    $smResponse->assertStatus(200);
    $smOrders = collect($smResponse->json('orders'));
    expect($smOrders->pluck('po_number'))->not->toContain('PO-OWN-PAID-001');
    expect($smOrders->pluck('po_number'))->toContain('PO-NEW-UNPAID-002');
    expect($smResponse->json('total_pending_amount'))->toEqual(15000.00);

    // Owner/Accountant checks Admin Ledger purchase orders -> must ONLY see PO-NEW-UNPAID-002
    $alResponse = $this->actingAs($this->owner)
        ->getJson("/admin-ledger/purchase-orders?project_site_id={$managedSite->id}&supplier_id={$this->supplier->id}&scope=site_based");

    $alResponse->assertStatus(200);
    $alOrders = collect($alResponse->json('orders'));
    expect($alOrders->pluck('po_number'))->not->toContain('PO-OWN-PAID-001');
    expect($alOrders->pluck('po_number'))->toContain('PO-NEW-UNPAID-002');
    expect($alResponse->json('total_pending_amount'))->toEqual(15000.00);
});

test('owner paying total lump-sum in admin ledger settles existing POs and only new POs appear afterwards', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);

    $managedSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Site Total Settlement',
        'location' => 'Tambaram, Chennai',
        'work_type' => 'New Build',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'status' => 'In Progress',
        'created_by' => $siteManager->id,
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-OLD-TOTAL-1',
        'order_date' => '2026-11-18',
        'project_site_id' => $managedSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 40000.00,
        'tax_amount' => 0.00,
        'total_amount' => 40000.00,
        'created_by' => $siteManager->id,
    ]);

    // Owner pays 40,000 in Total mode (no purchase_order_no)
    AdminLedger::create([
        'entry_date' => '2026-11-20',
        'category' => 'supplier_payment',
        'flow' => 'expense',
        'is_site_based' => false,
        'project_site_id' => null,
        'site_name' => 'All Sites (Total)',
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'purchase_order_no' => null,
        'amount' => 40000.00,
        'payment_mode' => 'Bank Transfer',
        'created_by' => $this->owner->id,
    ]);

    // Before new PO: Site Manager sees 0 pending
    $beforeNewPo = $this->actingAs($siteManager)
        ->getJson("/site-cash/purchase-orders?supplier_id={$this->supplier->id}&scope=total");
    $beforeNewPo->assertStatus(200);
    expect($beforeNewPo->json('pending_count'))->toBe(0);
    expect($beforeNewPo->json('total_pending_amount'))->toEqual(0.0);

    // New PO arrives later
    PurchaseOrder::create([
        'po_number' => 'PO-NEW-TOTAL-2',
        'order_date' => '2026-11-25',
        'project_site_id' => $managedSite->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 18000.00,
        'tax_amount' => 0.00,
        'total_amount' => 18000.00,
        'created_by' => $siteManager->id,
    ]);

    // Only the new PO (18,000) appears for Site Manager & Admin Ledger
    $afterNewPoSm = $this->actingAs($siteManager)
        ->getJson("/site-cash/purchase-orders?supplier_id={$this->supplier->id}&scope=total");
    $afterNewPoSm->assertStatus(200);
    expect(collect($afterNewPoSm->json('orders'))->pluck('po_number'))->toEqual(collect(['PO-NEW-TOTAL-2']));
    expect($afterNewPoSm->json('total_pending_amount'))->toEqual(18000.00);

    $afterNewPoAl = $this->actingAs($this->owner)
        ->getJson("/admin-ledger/purchase-orders?supplier_id={$this->supplier->id}&scope=total");
    $afterNewPoAl->assertStatus(200);
    expect(collect($afterNewPoAl->json('orders'))->pluck('po_number'))->toEqual(collect(['PO-NEW-TOTAL-2']));
    expect($afterNewPoAl->json('total_pending_amount'))->toEqual(18000.00);
});

test('site manager paying a PO in site cash hides that PO from admin ledger', function () {
    PurchaseOrder::create([
        'po_number' => 'PO-SM-PAID-001',
        'order_date' => '2026-11-20',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'source_type' => 'Third-Party Supplier',
        'status' => 'Order Received',
        'subtotal' => 12000.00,
        'tax_amount' => 0.00,
        'total_amount' => 12000.00,
        'created_by' => $this->owner->id,
    ]);

    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Supplier Payment',
        'flow_type' => 'outflow',
        'amount' => 12000.00,
        'out_amount' => 12000.00,
        'is_expense' => true,
        'is_site_based' => true,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'purchase_order_no' => 'PO-SM-PAID-001',
        'bill_no' => 'PO-SM-PAID-001',
        'payment_date' => '2026-11-21',
        'payment_mode' => 'Cash',
        'paid_to' => 'Direct to Supplier',
        'created_by' => $this->owner->id,
    ]);

    $alResponse = $this->actingAs($this->owner)
        ->getJson("/admin-ledger/purchase-orders?project_site_id={$this->site->id}&supplier_id={$this->supplier->id}&scope=site_based");

    $alResponse->assertStatus(200);
    expect(collect($alResponse->json('orders'))->pluck('po_number'))->not->toContain('PO-SM-PAID-001');
    expect($alResponse->json('total_pending_amount'))->toEqual(0.0);
});

test('owner without daily ledger permission does not see daily ledger tab on admin ledger page', function () {
    $response = $this->actingAs($this->owner)->get('/admin-ledger');

    $response->assertStatus(200);
    $response->assertSee('Admin Ledger');
    // Daily Ledger navigation tab should not be rendered
    $response->assertDontSee('Daily Ledger', false);
});

test('account manager with daily ledger permission sees both daily ledger and admin ledger tabs', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($accountManager)->get('/admin-ledger');

    $response->assertStatus(200);
    $response->assertSee('Daily Ledger', false);
    $response->assertSee('Admin Ledger', false);
});

test('owner cannot access daily ledger route and is redirected to dashboard', function () {
    $response = $this->actingAs($this->owner)->get('/ledger');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('site manager cannot access daily ledger route and is redirected to dashboard', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);

    $response = $this->actingAs($siteManager)->get('/ledger');

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('owner cannot record supplier payment when pending po total is zero', function () {
    $response = $this->actingAs($this->owner)->postJson('/admin-ledger', [
        'entry_date' => '2026-11-25',
        'category' => 'supplier_payment',
        'supplier_payment_scope' => 'site_based',
        'supplier_id' => $this->supplier->id,
        'project_site_id' => $this->site->id,
        'amount' => 100.00,
        'payment_mode' => 'Cash',
        'description' => 'Should be blocked when pending total is zero',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['amount']);
    expect(AdminLedger::count())->toBe(0);
});
