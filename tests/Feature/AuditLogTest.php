<?php

use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = User::firstOrCreate(
        ['email' => 'owner@deiveni.com'],
        [
            'name' => 'K. Deiveni',
            'role' => 'owner',
            'password' => 'password',
            'status' => 'active',
        ]
    );
});

test('task 1 & 2: audit log index page renders dynamic table without category tabs', function () {
    $response = $this->actingAs($this->owner)->get('/audit-logs');

    $response->assertStatus(200);
    $response->assertSee('Audit Logs & Activity Monitor', false);
    $response->assertSee('Activity Log Ledger', false);
    $response->assertSee('Events Tracked (Today)', false);
    $response->assertSee('Cash & Wage Events', false);
    $response->assertSee('Critical / Mod Actions', false);
    $response->assertSee('Active Operators', false);
    // Verify category tabs are NOT present
    $response->assertDontSee('id="auditCategoryTabs"', false);
    $response->assertDontSee('All Activities <span class="badge', false);
});

test('audit log index supports ajax filtering and pagination', function () {
    $response = $this->actingAs($this->owner)->getJson('/audit-logs?module=site_cash');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'status',
        'html',
        'pagination',
        'kpi' => [
            'total_today',
            'financial_count',
            'tracked_value',
            'critical_count',
            'active_users_count',
        ],
        'total',
    ]);
});

test('login and logout events are recorded in audit logs', function () {
    // Test Login
    $this->post('/login', [
        'email' => $this->owner->email,
        'password' => 'password',
    ]);

    $loginLog = AuditLog::where('action', 'LOGIN')
        ->where('user_id', $this->owner->id)
        ->latest('id')
        ->first();

    expect($loginLog)->not->toBeNull();
    expect($loginLog->module)->toBe('auth');
    expect($loginLog->user_name)->toBe($this->owner->name);

    // Test Logout
    $this->actingAs($this->owner)->post('/logout');

    $logoutLog = AuditLog::where('action', 'LOGOUT')
        ->where('user_id', $this->owner->id)
        ->latest('id')
        ->first();

    expect($logoutLog)->not->toBeNull();
    expect($logoutLog->module)->toBe('auth');
});

test('model create, update, delete operations are recorded in audit logs', function () {
    $this->actingAs($this->owner);

    // 1. Create
    $unit = Unit::create([
        'code' => 'MTR-AUD',
        'name_en' => 'Metric Ton Audit',
        'name_ta' => 'மெட்ரிக் டன்',
        'status' => 'active',
    ]);

    $createLog = AuditLog::where('target_type', Unit::class)
        ->where('action', 'CREATE')
        ->where('user_id', $this->owner->id)
        ->latest('id')
        ->first();

    expect($createLog)->not->toBeNull();
    expect($createLog->module)->toBe('masters');
    expect($createLog->new_values['name_en'])->toBe('Metric Ton Audit');

    // 2. Update
    $unit->update([
        'name_en' => 'Metric Ton Modified',
    ]);

    $updateLog = AuditLog::where('target_type', Unit::class)
        ->where('action', 'UPDATE')
        ->where('user_id', $this->owner->id)
        ->latest('id')
        ->first();

    expect($updateLog)->not->toBeNull();
    expect($updateLog->old_values['name_en'])->toBe('Metric Ton Audit');
    expect($updateLog->new_values['name_en'])->toBe('Metric Ton Modified');

    // 3. Delete
    $unit->delete();

    $deleteLog = AuditLog::where('target_type', Unit::class)
        ->where('action', 'DELETE')
        ->where('user_id', $this->owner->id)
        ->latest('id')
        ->first();

    expect($deleteLog)->not->toBeNull();
    expect($deleteLog->severity)->toBe('critical');
});

test('audit log show endpoint returns event specification details', function () {
    $log = AuditLog::first();
    if (! $log) {
        $log = AuditLog::create([
            'event_id' => 'AUD-TEST-001',
            'user_name' => 'Tester',
            'module' => 'test',
            'action' => 'CREATE',
            'severity' => 'info',
            'title' => 'Test event',
        ]);
    }

    $response = $this->actingAs($this->owner)->getJson("/audit-logs/{$log->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'data' => [
            'id' => $log->id,
            'event_id' => $log->event_id,
            'action' => $log->action,
        ],
    ]);
});

test('audit log export endpoint downloads csv and json formats', function () {
    // Export CSV
    $csvResponse = $this->actingAs($this->owner)->get('/audit-logs/export?format=csv');
    $csvResponse->assertStatus(200);
    expect($csvResponse->headers->get('content-type'))->toContain('text/csv');
    expect($csvResponse->getContent())->toContain('Event ID');

    // Export JSON
    $jsonResponse = $this->actingAs($this->owner)->get('/audit-logs/export?format=json');
    $jsonResponse->assertStatus(200);
    expect($jsonResponse->headers->get('content-type'))->toContain('application/json');
});

test('audit log simulate endpoint creates a real activity entry', function () {
    $countBefore = AuditLog::count();

    $response = $this->actingAs($this->owner)->postJson('/audit-logs/simulate');

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
    ]);

    expect(AuditLog::count())->toBeGreaterThan($countBefore);
});

test('form update via HTTP PUT creates an audit log entry', function () {
    $bank = Bank::firstOrCreate(
        ['bank_name' => 'Original Bank Name'],
        ['status' => 'Active']
    );

    $response = $this->actingAs($this->owner)->put("/banks/{$bank->id}", [
        'bank_name' => 'Form Updated Bank Name',
        'status' => 'Active',
    ]);

    $response->assertSessionHasNoErrors();

    $log = AuditLog::where('target_type', Bank::class)
        ->where('action', 'UPDATE')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->old_values['bank_name'])->toBe('Original Bank Name');
    expect($log->new_values['bank_name'])->toBe('Form Updated Bank Name');
});

test('profile update via HTTP PUT creates an audit log entry', function () {
    $response = $this->actingAs($this->owner)->put('/profile', [
        'name' => 'K. Deiveni Updated',
        'email' => 'owner@deiveni.com',
        'phone' => '98400 99999',
    ]);

    $response->assertSessionHasNoErrors();

    $log = AuditLog::where('target_type', User::class)
        ->where('action', 'UPDATE')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull();
});

test('role form update is captured even when name is unchanged', function () {
    $role = Role::firstOrCreate(
        ['slug' => 'test-role'],
        ['name' => 'Test Role', 'is_system' => false]
    );

    $countBefore = AuditLog::count();

    $response = $this->actingAs($this->owner)->put("/roles/{$role->id}", [
        'name' => 'Test Role',
        'description' => 'Updated Role Description',
        'menus' => ['banks', 'branches'],
    ]);

    $response->assertSessionHasNoErrors();
    expect(AuditLog::count())->toBeGreaterThan($countBefore);
});
