<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

test('index page displays the login view', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('DEIVENI CONSTRUCTION');
    $response->assertSee('Sign In to Workspace');
    $response->assertSee('owner@deiveni.com');
});

test('login page has remember me unchecked by default on initial page load', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    expect($response->getContent())->toContain('name="remember" value="1"')
        ->not->toContain('name="remember" value="1"'."\n".'                                checked');
});

test('login uri redirects to the index login page', function () {
    $response = $this->get('/login');

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot access dashboard and is redirected to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect(route('login'));
});

test('unauthenticated user cannot access any protected page and is redirected to login', function (string $url) {
    $response = $this->get($url);

    $response->assertRedirect(route('login'));
})->with([
    '/dashboard',
    '/profile',
    '/worker-categories',
    '/units',
    '/materials',
    '/suppliers',
    '/banks',
    '/branches',
    '/properties',
    '/property-types',
    '/sites',
    '/daily-allocation',
    '/weekly-wages',
    '/purchases',
    '/site-cash',
    '/ledger',
    '/warehouse',
    '/reports/admin/profit-loss',
    '/reports/site/labour-allocations',
    '/reports/site/cash-handling',
    '/reports/site/purchases-inward',
    '/reports/site/warehouse-inventory',
    '/reports/account/ledger',
    '/roles',
    '/users',
    '/audit-logs',
]);

test('user seeder creates the three required roles', function () {
    $owner = User::where('email', 'owner@deiveni.com')->first();
    $siteManager = User::where('email', 'sm@deiveni.com')->first();
    $accountManager = User::where('email', 'am@deiveni.com')->first();

    expect($owner)->not->toBeNull()
        ->and($owner->role)->toBe(UserRole::Owner)
        ->and($siteManager)->not->toBeNull()
        ->and($siteManager->role)->toBe(UserRole::SiteManager)
        ->and($accountManager)->not->toBeNull()
        ->and($accountManager->role)->toBe(UserRole::AccountManager);
});

test('owner can authenticate and access dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->role)->toBe(UserRole::Owner);

    $dashResponse = $this->get('/dashboard');
    $dashResponse->assertStatus(200);
    $dashResponse->assertSee('K. Deiveni');
});

test('site manager can authenticate and access dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'sm@deiveni.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->role)->toBe(UserRole::SiteManager);

    $dashResponse = $this->get('/dashboard');
    $dashResponse->assertStatus(200);
    $dashResponse->assertSee('Murugan SM');
});

test('account manager can authenticate and access dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'am@deiveni.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->role)->toBe(UserRole::AccountManager);

    $dashResponse = $this->get('/dashboard');
    $dashResponse->assertStatus(200);
    $dashResponse->assertSee('Raja AM');
});

test('login fails with invalid credentials', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Auth::check())->toBeFalse();
});

test('authenticated user visiting index page is redirected to dashboard', function () {
    $user = User::where('email', 'owner@deiveni.com')->first();
    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertRedirect(route('dashboard'));
});

test('user can log out successfully', function () {
    $user = User::where('email', 'sm@deiveni.com')->first();
    $this->actingAs($user);

    $response = $this->post('/logout');
    $response->assertRedirect(route('login'));
    expect(Auth::check())->toBeFalse();
});

test('login with remember sets remember cookie and remember token in database', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'password123',
        'remember' => '1',
    ]);

    $response->assertRedirect(route('dashboard'));
    $recallerName = Auth::guard()->getRecallerName();
    $response->assertCookie($recallerName);

    $owner = User::where('email', 'owner@deiveni.com')->first();
    expect($owner->getRememberToken())->not->toBeEmpty();
});

test('login without remember does not set remember cookie', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $recallerName = Auth::guard()->getRecallerName();
    $response->assertCookieMissing($recallerName);
});

test('user is re-authenticated via remember cookie when session expires', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'password123',
        'remember' => '1',
    ]);

    $recallerName = Auth::guard()->getRecallerName();
    $recallerCookie = $response->getCookie($recallerName);
    expect($recallerCookie)->not->toBeNull();

    // Simulate session expired / new browser session with the recaller cookie
    $this->flushSession();

    // Access dashboard sending the remember cookie
    $dashResponse = $this->withCookie($recallerName, $recallerCookie->getValue())
        ->get('/dashboard');

    $dashResponse->assertStatus(200);
    $dashResponse->assertSee('K. Deiveni');
});

test('user with remember cookie visiting login page is redirected to dashboard', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'password123',
        'remember' => '1',
    ]);

    $recallerName = Auth::guard()->getRecallerName();
    $recallerCookie = $response->getCookie($recallerName);
    expect($recallerCookie)->not->toBeNull();

    $this->flushSession();

    $response = $this->withCookie($recallerName, $recallerCookie->getValue())
        ->get('/');

    $response->assertRedirect(route('dashboard'));
});

test('login page remembers checkbox state when validation fails with remember checked', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'wrongpassword',
        'remember' => '1',
    ]);

    $response->assertSessionHasErrors('email');
    $follow = $this->get('/');
    expect($follow->getContent())->toContain('name="remember" value="1"')
        ->toContain('checked');
});

test('login page unchecks checkbox when validation fails with remember unchecked', function () {
    $response = $this->post('/login', [
        'email' => 'owner@deiveni.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');
    $follow = $this->get('/');
    expect($follow->getContent())->toContain('name="remember" value="1"');
    // Does it contain checked?
    expect($follow->getContent())->not->toContain('name="remember" value="1"'."\n".'                                checked');
});
