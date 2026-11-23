<?php

use App\Models\RoleMenu;
use App\Models\TransactionType;
use App\Models\User;
use App\Repositories\Contracts\TransactionTypeRepositoryInterface;
use App\Repositories\Eloquent\TransactionTypeRepository;
use App\Services\Contracts\TransactionTypeServiceInterface;
use App\Services\TransactionTypeService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);

    $this->owner = User::where('email', 'owner@deiveni.com')->first();
    $this->siteManager = User::where('email', 'sm@deiveni.com')->first();
    $this->accountManager = User::where('email', 'am@deiveni.com')->first();

    // Ensure initial permissions
    foreach ([$this->owner, $this->siteManager, $this->accountManager] as $user) {
        if ($user && $user->role_id) {
            RoleMenu::firstOrCreate([
                'role_id' => $user->role_id,
                'menu_key' => 'transaction_types',
            ]);
        }
    }
});

test('unauthenticated users cannot access transaction types page', function () {
    $response = $this->get('/transaction-types');

    $response->assertRedirect(route('login'));
});

test('owner can access transaction types master page', function () {
    $response = $this->actingAs($this->owner)->get('/transaction-types');

    $response->assertStatus(200);
    $response->assertSee('Transaction Type Directory');
    $response->assertSee('Advance Received');
    $response->assertSee('Client Collection');
    $response->assertSee('Construction');
});

test('site manager can access transaction types master page', function () {
    $response = $this->actingAs($this->siteManager)->get('/transaction-types');

    $response->assertStatus(200);
    $response->assertSee('Transaction Type Directory');
});

test('account manager can access transaction types master page', function () {
    $response = $this->actingAs($this->accountManager)->get('/transaction-types');

    $response->assertStatus(200);
    $response->assertSee('Transaction Type Directory');
});

test('service and repository interfaces are properly bound in container', function () {
    $repo = app(TransactionTypeRepositoryInterface::class);
    $service = app(TransactionTypeServiceInterface::class);

    expect($repo)->toBeInstanceOf(TransactionTypeRepository::class)
        ->and($service)->toBeInstanceOf(TransactionTypeService::class);
});

test('storing a transaction type validates required fields', function () {
    $response = $this->actingAs($this->owner)->postJson('/transaction-types', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en', 'category', 'status']);
});

test('storing a transaction type validates category enum', function () {
    $response = $this->actingAs($this->owner)->postJson('/transaction-types', [
        'name_en' => 'Test Transaction',
        'category' => 'Invalid Category',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category']);
});

test('storing a transaction type enforces unique name_en', function () {
    $response = $this->actingAs($this->owner)->postJson('/transaction-types', [
        'name_en' => 'Advance Received', // already seeded
        'category' => 'Construction',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en']);
});

test('updating a transaction type enforces unique name_en against other records', function () {
    $type = TransactionType::create([
        'name_en' => 'Unique Custom Flow',
        'category' => 'Rental',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->accountManager)->putJson("/transaction-types/{$type->id}", [
        'name_en' => 'Advance Received', // exists on another row
        'category' => 'Rental',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en']);
});

test('updating a transaction type can keep its own name_en', function () {
    $type = TransactionType::create([
        'name_en' => 'Self Kept Flow',
        'category' => 'Rental',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->accountManager)->putJson("/transaction-types/{$type->id}", [
        'name_en' => 'Self Kept Flow',
        'category' => 'Construction',
        'status' => 'Inactive',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect(TransactionType::find($type->id)->status)->toBe('Inactive');
});

test('can create a new transaction type via json request', function () {
    $response = $this->actingAs($this->owner)->postJson('/transaction-types', [
        'name_en' => 'Sub-Contractor Advance',
        'category' => 'Construction',
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $created = TransactionType::where('name_en', 'Sub-Contractor Advance')->first();
    expect($created)->not->toBeNull()
        ->and($created->category)->toBe('Construction')
        ->and($created->status)->toBe('Active');
});

test('can retrieve single transaction type via show endpoint', function () {
    $type = TransactionType::where('name_en', 'Advance Received')->first();

    $response = $this->actingAs($this->siteManager)->getJson("/transaction-types/{$type->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $type->id,
                'name_en' => 'Advance Received',
                'category' => 'Construction',
            ],
        ]);
});

test('can update a transaction type successfully', function () {
    $type = TransactionType::create([
        'name_en' => 'Old Utility Bill',
        'category' => 'Office and other expenses',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->accountManager)->putJson("/transaction-types/{$type->id}", [
        'name_en' => 'Updated Electricity Bill',
        'category' => 'Office and other expenses',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $updated = TransactionType::find($type->id);
    expect($updated->name_en)->toBe('Updated Electricity Bill')
        ->and($updated->category)->toBe('Office and other expenses');
});

test('transaction type can be deleted successfully', function () {
    $type = TransactionType::create([
        'name_en' => 'Temporary Payment Type',
        'category' => 'Office and other expenses',
        'status' => 'Active',
    ]);

    $response = $this->actingAs($this->owner)->deleteJson("/transaction-types/{$type->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(TransactionType::find($type->id))->toBeNull();
});

test('menu configuration and rbac check includes transaction_types', function () {
    $menus = config('menus.items');
    expect($menus)->toHaveKey('transaction_types')
        ->and($menus['transaction_types']['url'])->toBe('/transaction-types')
        ->and($menus['transaction_types']['section'])->toBe('MASTERS');

    // Both site manager and account manager user models pass canAccess
    expect($this->siteManager->canAccess('transaction_types'))->toBeTrue()
        ->and($this->accountManager->canAccess('transaction_types'))->toBeTrue()
        ->and($this->owner->canAccess('transaction_types'))->toBeTrue();
});
