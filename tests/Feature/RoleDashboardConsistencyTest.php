<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('newly created site manager with hyphenated role slug sees the exact same site manager dashboard', function () {
    $role = Role::where('name', 'Site Manager')->firstOrFail();

    $legacySiteManager = User::factory()->create([
        'name' => 'Murugan SM',
        'role_id' => $role->id,
        'role' => UserRole::SiteManager->value,
    ]);

    $newSiteManager = User::factory()->create([
        'name' => 'Syed SM',
        'role_id' => $role->id,
        'role' => 'site-manager',
    ]);

    expect($legacySiteManager->isSiteManager())->toBeTrue();
    expect($newSiteManager->isSiteManager())->toBeTrue();
    expect($newSiteManager->isAccountManager())->toBeFalse();
    expect($newSiteManager->isAdmin())->toBeFalse();

    $response = $this->actingAs($newSiteManager)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertSee('Active Sites', false);
    $response->assertSee('Labour on Site', false);
    $response->assertSee('Weekly Wages', false);
    $response->assertSee('Site Cash', false);
    $response->assertSee('Worker Categories', false);
    $response->assertSee('Warehouse Store', false);
    $response->assertDontSee("Today's Income", false);
    $response->assertDontSee("Today's Expense", false);
    $response->assertDontSee('Cash in Hand', false);
});

test('newly created account manager with hyphenated role slug sees the account manager dashboard', function () {
    $role = Role::where('name', 'Account Manager')->firstOrFail();

    $newAccountManager = User::factory()->create([
        'name' => 'New AM',
        'role_id' => $role->id,
        'role' => 'account-manager',
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
    $response->assertSee('Active Suppliers', false);
    $response->assertDontSee('Cash in Hand', false);
});

test('admin user with owner role slug sees the admin dashboard', function () {
    $role = Role::where('name', 'Owner')->firstOrFail();

    $newAdmin = User::factory()->create([
        'name' => 'New Owner',
        'role_id' => $role->id,
        'role' => 'owner',
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
