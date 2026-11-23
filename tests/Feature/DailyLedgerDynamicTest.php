<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
        'status' => 'Active',
    ]);

    $this->client = Client::create([
        'name' => 'John Doe Builder',
        'phone' => '9876543210',
        'address' => 'Chennai',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Omr Luxury Villa',
        'location' => 'Chennai',
        'work_type' => 'Construction',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Sri Balaji Blue Metals',
        'contact_person' => 'Balaji',
        'phone' => '9876500001',
        'status' => 'Active',
    ]);
});

test('task 1 & 2: ledger index and show pages render dynamic database records', function () {
    $ledger = Ledger::create([
        'entry_date' => '2026-11-06',
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 75000.00,
        'project_site_id' => $this->site->id,
        'site_name' => $this->site->name,
        'client_name' => 'John Doe Builder',
        'description' => 'First stage advance from client',
        'created_by' => $this->accountManager->id,
    ]);

    $response = $this->actingAs($this->accountManager)->get('/ledger');
    $response->assertStatus(200);
    $response->assertSee('First stage advance from client');
    $response->assertSee('₹75,000.00');
    $response->assertSee('John Doe Builder');
    $response->assertSee('/ledger/', false);
    $response->assertSee('bi bi-eye', false);
    $response->assertDontSee('bi-three-dots-vertical');
    $response->assertDontSee('btn-delete-ledger');

    $detailResponse = $this->actingAs($this->accountManager)->get("/ledger/{$ledger->encrypted_id}");
    $detailResponse->assertStatus(200);
    $detailResponse->assertSee('First stage advance from client');
    $detailResponse->assertSee('₹75,000.00');
    $detailResponse->assertSee('John Doe Builder');
    $detailResponse->assertSee('Omr Luxury Villa');
});

test('task 3: construction entry auto associates site and client name with ability to edit client name', function () {
    $payload = [
        'entry_date' => '2026-11-06',
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 50000.00,
        'project_site_id' => $this->site->id,
        'client_name' => 'John Doe Builder (Custom Edited Name)',
        'cash_received_from' => 'client',
        'description' => 'Advance payment received',
    ];

    $response = $this->actingAs($this->accountManager)->postJson('/ledger', $payload);
    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('ledgers', [
        'source' => 'Construction',
        'project_site_id' => $this->site->id,
        'site_name' => 'Omr Luxury Villa',
        'client_name' => 'John Doe Builder (Custom Edited Name)',
        'cash_received_from' => 'client',
        'amount' => 50000.00,
    ]);

    $ledger = Ledger::where('client_name', 'John Doe Builder (Custom Edited Name)')->first();
    expect($ledger)->not->toBeNull();
    expect($ledger->projectSite)->not->toBeNull();
    expect($ledger->projectSite->id)->toBe($this->site->id);
});

test('task 4: supplier entry requires only supplier select box and no site or purchase order', function () {
    $payload = [
        'entry_date' => '2026-11-06',
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 12500.00,
        'supplier_id' => $this->supplier->id,
        'description' => 'Payment made to supplier',
    ];

    $response = $this->actingAs($this->accountManager)->postJson('/ledger', $payload);
    $response->assertStatus(201);

    $this->assertDatabaseHas('ledgers', [
        'source' => 'Supplier',
        'supplier_id' => $this->supplier->id,
        'supplier_name' => 'Sri Balaji Blue Metals',
        'project_site_id' => null,
        'site_name' => null,
        'amount' => 12500.00,
    ]);

    // Check that ledger blade contains supplier select without site or PO inputs inside supplier group
    $pageResponse = $this->actingAs($this->accountManager)->get('/ledger');
    $pageResponse->assertStatus(200);
    $pageResponse->assertSee('id="ledgerSupplier"', false);
    $pageResponse->assertDontSee('id="ledgerPurchaseOrder"', false);
});

test('task 5: transaction type master relation allows adding custom input and saving value for that category', function () {
    // 1. User adds a transaction type in master with category 'Bank Valuation' and name 'cash flow'
    $txnType = TransactionType::create([
        'name_en' => 'cash flow',
        'category' => 'Bank Valuation',
        'status' => 'Active',
    ]);

    // 2. Querying transaction types by category returns this transaction type
    $catResponse = $this->actingAs($this->accountManager)->getJson('/ledger/transaction-types/Bank Valuation');
    $catResponse->assertStatus(200);
    $catResponse->assertJsonFragment([
        'name_en' => 'cash flow',
        'category' => 'Bank Valuation',
    ]);

    // 3. User saves ledger entry with this transaction type input filled
    $payload = [
        'entry_date' => '2026-11-06',
        'flow' => 'income',
        'source' => 'Bank Valuation',
        'amount' => 3500.00,
        'transaction_types' => [
            $txnType->id => 'Inspection fee ref #XYZ123',
        ],
        'description' => 'Bank valuation charges',
    ];

    $saveResponse = $this->actingAs($this->accountManager)->postJson('/ledger', $payload);
    $saveResponse->assertStatus(201);

    $this->assertDatabaseHas('ledgers', [
        'source' => 'Bank Valuation',
        'transaction_type_id' => $txnType->id,
        'transaction_type_name' => 'cash flow',
        'transaction_type_value' => 'Inspection fee ref #XYZ123',
    ]);

    $ledger = Ledger::where('transaction_type_id', $txnType->id)->first();
    expect($ledger)->not->toBeNull();
    expect($ledger->transactionType)->not->toBeNull();
    expect($ledger->transactionType->name_en)->toBe('cash flow');

    // 4. Detail page displays this transaction type and custom value
    $detailResponse = $this->actingAs($this->accountManager)->get("/ledger/{$ledger->encrypted_id}");
    $detailResponse->assertStatus(200);
    $detailResponse->assertSee('cash flow');
    $detailResponse->assertSee('Inspection fee ref #XYZ123');
});

test('daily ledger opening balance shows yesterdays income and per-date income expense totals', function () {
    $yesterday = Carbon::yesterday()->toDateString();

    // Create income yesterday: 25,000
    Ledger::create([
        'entry_date' => $yesterday,
        'flow' => 'income',
        'source' => 'Construction',
        'amount' => 25000.00,
        'description' => 'Stage 1 client advance',
        'created_by' => $this->accountManager->id,
    ]);

    // Create expense yesterday: 7,000
    Ledger::create([
        'entry_date' => $yesterday,
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 7000.00,
        'description' => 'Cement purchase',
        'created_by' => $this->accountManager->id,
    ]);

    // Top common card shows yesterday's income = 25,000 and label "முந்தைய வரவு (நேற்றைய வரவு)"
    $response = $this->actingAs($this->accountManager)->get('/ledger');
    $response->assertStatus(200);
    $response->assertSee('₹25,000.00');
    $response->assertSee('முந்தைய வரவு (நேற்றைய வரவு)');
    $response->assertSee('அன்றைய மொத்த கணக்கு (Day Total)');
    $response->assertSee('+₹25,000.00');
    $response->assertSee('−₹7,000.00');
    $response->assertSee('date-summary-row');

    // JSON response returns yesterday_income in summary and day_totals
    $jsonResponse = $this->actingAs($this->accountManager)->getJson('/ledger');
    $jsonResponse->assertStatus(200);
    expect((float) $jsonResponse->json('summary.yesterday_income'))->toBe(25000.0);
    expect((float) $jsonResponse->json('summary.opening_balance'))->toBe(25000.0);
    expect((float) $jsonResponse->json("day_totals.{$yesterday}.income"))->toBe(25000.0);
    expect((float) $jsonResponse->json("day_totals.{$yesterday}.expense"))->toBe(7000.0);
});
