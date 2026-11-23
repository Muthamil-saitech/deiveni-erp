<?php

use App\Enums\UserRole;
use App\Models\Bank;
use App\Models\User;
use App\Repositories\Contracts\BankRepositoryInterface;
use App\Repositories\Eloquent\BankRepository;
use App\Services\BankService;
use App\Services\Contracts\BankServiceInterface;
use Database\Seeders\BankSeeder;
use Database\Seeders\BranchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(BankSeeder::class);
});

test('banks index page renders successfully and displays seeded banks and statistics', function () {
    $response = $this->get('/banks');

    $response->assertStatus(200);
    $response->assertSee('Bank Master');
    $response->assertSee('Empanelled Banks Directory', false);
    $response->assertSee('State Bank of India (SBI)');
    $response->assertSee('HDFC Bank');
    $response->assertSee('Indian Bank');
    $response->assertSee('Canara Bank');
    $response->assertSee('ICICI Bank');
    $response->assertSee('Axis Bank');
    $response->assertSee('Punjab National Bank');
    $response->assertSee('Bank of Baroda');
    $response->assertSee('id="statTotalBanks"', false);
    $response->assertSee('id="statActiveBanks"', false);
    $response->assertSee('id="statInactiveBanks"', false);
    $response->assertSee('id="bankModal"', false);
});

test('bank master page is accessible by account manager and admin but restricted for site manager', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $admin = User::factory()->create([
        'role' => UserRole::Admin,
    ]);

    // Account manager can access
    $this->actingAs($accountManager)->get('/banks')->assertStatus(200);

    // Admin can access
    $this->actingAs($admin)->get('/banks')->assertStatus(200);

    // Site manager redirected with error
    $this->actingAs($siteManager)->get('/banks')->assertRedirect(route('dashboard'));
    $this->actingAs($siteManager)->getJson('/banks')->assertStatus(403);
});

test('banks are ordered with last added row first', function () {
    $service = app(BankServiceInterface::class);

    $newBank = $service->createBank([
        'bank_name' => 'Federal Bank',
        'bank_name_ta' => 'பெடரல் வங்கி',
        'status' => 'Active',
    ]);

    $updatedBanks = $service->getAllBanks();
    expect($updatedBanks->first()->id)->toBe($newBank->id)
        ->and($updatedBanks->first()->bank_name)->toBe('Federal Bank');
});

test('service and repository interfaces for bank are properly bound in service container', function () {
    $repository = app(BankRepositoryInterface::class);
    $service = app(BankServiceInterface::class);

    expect($repository)->toBeInstanceOf(BankRepository::class)
        ->and($service)->toBeInstanceOf(BankService::class);
});

test('storing a bank requires bank_name and status', function () {
    $response = $this->postJson('/banks', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['bank_name', 'status']);
});

test('storing a bank validates uniqueness of bank name', function () {
    // State Bank of India (SBI) is already seeded
    $response = $this->postJson('/banks', [
        'bank_name' => 'State Bank of India (SBI)',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['bank_name']);
});

test('storing a bank validates status in Active or Inactive', function () {
    $response = $this->postJson('/banks', [
        'bank_name' => 'IndusInd Bank',
        'status' => 'UnknownStatus',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('can create a new bank successfully via AJAX', function () {
    $response = $this->postJson('/banks', [
        'bank_name' => 'Union Bank of India',
        'bank_name_ta' => 'யூனியன் பாங்க் ஆஃப் இந்தியா',
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonPath('data.bank_name', 'Union Bank of India')
        ->assertJsonPath('data.status', 'Active');

    $bank = Bank::where('bank_name', 'Union Bank of India')->first();
    expect($bank)->not->toBeNull()
        ->and($bank->bank_name_ta)->toBe('யூனியன் பாங்க் ஆஃப் இந்தியா');
});

test('can fetch a single bank record by ID', function () {
    $firstBank = Bank::first();

    $response = $this->getJson("/banks/{$firstBank->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $firstBank->id,
                'bank_name' => $firstBank->bank_name,
            ],
        ]);
});

test('can update an existing bank without self-uniqueness collision', function () {
    $bank = Bank::where('bank_name', 'HDFC Bank')->first();

    $response = $this->putJson("/banks/{$bank->id}", [
        'bank_name' => 'HDFC Bank Ltd',
        'bank_name_ta' => 'ஹெச்டிஎஃப்சி வங்கி லிமிடெட்',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $bank->refresh();
    expect($bank->bank_name)->toBe('HDFC Bank Ltd');
});

test('cannot delete a bank that is currently in use by branches', function () {
    $this->seed(BranchSeeder::class);

    $bank = Bank::where('bank_name', 'State Bank of India (SBI)')->first();

    $response = $this->deleteJson("/banks/{$bank->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    expect(Bank::find($bank->id))->not->toBeNull();
});

test('can delete a bank that is not in use by any branches', function () {
    $this->seed(BranchSeeder::class);

    $bank = Bank::where('bank_name', 'Kotak Mahindra Bank')->first();

    $response = $this->deleteJson("/banks/{$bank->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Bank::find($bank->id))->toBeNull();
});

test('service calculates bank statistics accurately', function () {
    $service = app(BankServiceInterface::class);
    $stats = $service->getBankStats();

    expect($stats)->toHaveKeys(['total_banks', 'active_banks', 'inactive_banks'])
        ->and($stats['total_banks'])->toBe(9)
        ->and($stats['active_banks'])->toBe(8)
        ->and($stats['inactive_banks'])->toBe(1);
});

test('banks index page renders table pagination elements and controls', function () {
    $response = $this->get('/banks');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
