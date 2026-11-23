<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('site manager sidebar shows only checked report menus and hides unchecked report menus', function () {
    $role = Role::where('slug', 'site-manager')->orWhere('name', 'Site Manager')->firstOrFail();

    // Assign only cash handling, uncheck labour allocations
    $role->syncMenus([
        'dashboard',
        'reports_cash_handling',
    ]);

    $user = User::factory()->create([
        'name' => 'Site Manager Test',
        'role_id' => $role->id,
        'role' => $role->slug,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    // Checked report must be visible
    $response->assertSee('reports_cash_handling');
    $response->assertSee('Site Cash Handling');

    // Unchecked report must NOT be visible
    $response->assertDontSee('reports_labour_allocations');
    $response->assertDontSee('data-menu="reports_labour_allocations"', false);

    // Now check labour allocations as well
    $role->syncMenus([
        'dashboard',
        'reports_cash_handling',
        'reports_labour_allocations',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSee('data-menu="reports_labour_allocations"', false);
    $response->assertSee('data-menu="reports_cash_handling"', false);
});

test('account manager sidebar shows only checked report menus and hides unchecked report menus', function () {
    $role = Role::where('slug', 'account-manager')->orWhere('name', 'Account Manager')->firstOrFail();

    // Uncheck reports_ledger
    $role->syncMenus([
        'dashboard',
        'ledger',
    ]);

    $user = User::factory()->create([
        'name' => 'Account Manager Test',
        'role_id' => $role->id,
        'role' => $role->slug,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertDontSee('reports_ledger');
    $response->assertDontSee('data-menu="reports_ledger"', false);

    // Now check reports_ledger
    $role->syncMenus([
        'dashboard',
        'ledger',
        'reports_ledger',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSee('data-menu="reports_ledger"', false);
    $response->assertSee('Financial & Ledger', false);
});

test('reports section heading is completely hidden when all report menus are unchecked', function () {
    $role = Role::where('slug', 'site-manager')->orWhere('name', 'Site Manager')->firstOrFail();

    // No report menus checked
    $role->syncMenus([
        'dashboard',
        'sites',
    ]);

    $user = User::factory()->create([
        'name' => 'Site Manager No Reports',
        'role_id' => $role->id,
        'role' => $role->slug,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertDontSee('REPORTS & ANALYTICS');
    $response->assertDontSee('data-role-section="reports"', false);
});
