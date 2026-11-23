<?php

use App\Enums\UserRole;
use App\Models\Ledger;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
        'status' => 'Active',
    ]);
});

test('store ledger validation returns bilingual error messages for required fields', function () {
    $response = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => '',
        'flow' => '',
        'source' => '',
        'amount' => '',
        'description' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'entry_date',
        'flow',
        'source',
        'amount',
        'description',
    ]);

    $errors = $response->json('errors');
    expect($errors['entry_date'][0])->toContain('Entry date is required (பதிவு தேதி அவசியம்)');
    expect($errors['amount'][0])->toContain('Amount is required (தொகை அவசியம்)');
    expect($errors['description'][0])->toContain('Description / narration is required (விவரம் / குறிப்பு அவசியம்)');
});

test('store ledger validation validates bank when source is bank valuation and bank field is present', function () {
    $response = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => '2026-11-10',
        'flow' => 'income',
        'source' => 'Bank Valuation',
        'amount' => 500,
        'description' => 'Test bank valuation',
        'bank_id' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['bank_id']);
    $errors = $response->json('errors');
    expect($errors['bank_id'][0])->toContain('Please select a bank (வங்கியை தேர்வு செய்யவும்)');
});

test('store ledger validation validates supplier when source is supplier and supplier field is present', function () {
    $response = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => '2026-11-10',
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 1200,
        'description' => 'Supplier bill payment',
        'supplier_id' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['supplier_id']);
    $errors = $response->json('errors');
    expect($errors['supplier_id'][0])->toContain('Please select a supplier (சப்ளையரை தேர்வு செய்யவும்)');
});

test('update ledger validation validates required fields and updates successfully', function () {
    $supplier = Supplier::factory()->create(['name' => 'Original Metals']);
    $ledger = Ledger::create([
        'entry_date' => '2026-11-10',
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 1000,
        'supplier_id' => $supplier->id,
        'description' => 'Original description',
    ]);

    // Test validation failure on empty amount
    $response = $this->actingAs($this->accountManager)->putJson("/ledger/{$ledger->id}", [
        'entry_date' => '2026-11-10',
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => '',
        'description' => 'Updated description',
        'supplier_id' => $supplier->id,
    ]);
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['amount']);
    expect($response->json('errors.amount.0'))->toContain('Amount is required (தொகை அவசியம்)');

    // Test successful update
    $successResponse = $this->actingAs($this->accountManager)->putJson("/ledger/{$ledger->id}", [
        'entry_date' => '2026-11-10',
        'flow' => 'expense',
        'source' => 'Supplier',
        'amount' => 1800,
        'description' => 'Updated description',
        'supplier_id' => $supplier->id,
    ]);
    $successResponse->assertStatus(200);
    $successResponse->assertJson(['success' => true]);

    $this->assertDatabaseHas('ledgers', [
        'id' => $ledger->id,
        'amount' => 1800,
        'description' => 'Updated description',
    ]);
});
