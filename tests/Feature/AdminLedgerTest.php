<?php

use App\Enums\UserRole;
use App\Models\AdminLedger;
use App\Models\Client;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
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
    $response->assertSee('Admin Ledger (நிர்வாக பேரேடு)');
    $response->assertSee('Dr. K. Senthil Kumar');
    $response->assertSee('Site B - Clinic &amp; Residence', false);
    $response->assertSee('150,000.00');
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
