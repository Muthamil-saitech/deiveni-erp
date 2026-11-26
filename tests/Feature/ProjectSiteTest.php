<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\User;
use Database\Seeders\ProjectSiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ProjectSiteSeeder::class);
});

test('project sites page renders successfully and displays seeded sites and clients', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $response = $this->actingAs($siteManager)->get('/sites');

    $response->assertStatus(200);
    $response->assertSee('Project Sites');
    $response->assertSee('Client & Project Sites', false);
    $response->assertSee('Site A - Duplex Villa');
    $response->assertSee('Mr. R. Balaji');
    $response->assertSee('Porur, Chennai');
    $response->assertSee('Site B - Clinic &amp; Residence', false);
    $response->assertSee('Dr. K. Senthil Kumar');
    $response->assertDontSee('Save to File');
    $response->assertDontSee('Load File');
});

test('project sites index via json returns sites, clients and stats', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $response = $this->actingAs($siteManager)
        ->getJson('/sites');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data',
        'clients',
        'stats',
    ]);
    $response->assertJson([
        'success' => true,
    ]);
});

test('store project site creates client and project site in single form', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $payload = [
        'name' => 'Site C - Modern Villa',
        'location' => 'Madipakkam, Chennai',
        'work_type' => 'New Build',
        'start_date' => '2026-04-01',
        'end_date' => '2026-11-30',
        'estimated_budget' => 4500000,
        'status' => 'In Progress',
        'client_option' => 'new',
        'client_name' => 'P. Ramesh Kumar',
        'client_phone' => '98840 99887',
        'client_address' => '12, Bazaar Road, Madipakkam',
    ];

    $response = $this->actingAs($siteManager)
        ->postJson('/sites', $payload);

    $response->assertStatus(201);
    $response->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('clients', [
        'name' => 'P. Ramesh Kumar',
        'phone' => '98840 99887',
    ]);

    $client = Client::where('phone', '98840 99887')->first();
    expect($client)->not->toBeNull();

    $this->assertDatabaseHas('project_sites', [
        'name' => 'Site C - Modern Villa',
        'client_id' => $client->id,
        'location' => 'Madipakkam, Chennai',
        'work_type' => 'New Build',
        'status' => 'In Progress',
    ]);
});

test('store project site linking existing client connects without duplicate client', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $existingClient = Client::where('phone', '98400 11223')->first();
    expect($existingClient)->not->toBeNull();
    $initialClientCount = Client::count();

    $payload = [
        'name' => 'Site D - Second Home Project',
        'location' => 'Kovalam, ECR',
        'work_type' => 'New Build',
        'start_date' => '2026-05-01',
        'estimated_budget' => 8000000,
        'status' => 'Planning',
        'client_option' => 'existing',
        'existing_client_id' => $existingClient->id,
    ];

    $response = $this->actingAs($siteManager)
        ->postJson('/sites', $payload);

    $response->assertStatus(201);

    expect(Client::count())->toBe($initialClientCount);

    $this->assertDatabaseHas('project_sites', [
        'name' => 'Site D - Second Home Project',
        'client_id' => $existingClient->id,
    ]);
});

test('store project site fails validation when required fields are missing', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $response = $this->actingAs($siteManager)
        ->postJson('/sites', [
            'name' => '',
            'location' => '',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'location', 'work_type', 'estimated_budget', 'status', 'client_name', 'client_phone']);
});

test('store project site fails validation when existing client option is chosen without client_id', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $response = $this->actingAs($siteManager)
        ->postJson('/sites', [
            'name' => 'Site E',
            'location' => 'Guindy, Chennai',
            'work_type' => 'New Build',
            'estimated_budget' => 500000,
            'status' => 'In Progress',
            'client_option' => 'existing',
            'client_id' => null,
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['client_id']);
});

test('store project site fails validation when site name or client phone already exists', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    // In seeded data: 'Site A - Duplex Villa' and '98400 11223' exist
    $response = $this->actingAs($siteManager)
        ->postJson('/sites', [
            'name' => 'Site A - Duplex Villa',
            'location' => 'Porur, Chennai',
            'work_type' => 'New Build',
            'estimated_budget' => 5000000,
            'status' => 'In Progress',
            'client_option' => 'new',
            'client_name' => 'New Person',
            'client_phone' => '98400 11223',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'client_phone']);
    $response->assertJsonFragment([
        'name' => ['Project site name has already been taken (தள பெயர் ஏற்கனவே உள்ளது).'],
    ]);
    $response->assertJsonFragment([
        'client_phone' => ['Client phone number has already been taken (தொலைபேசி எண் ஏற்கனவே உள்ளது).'],
    ]);
});

test('update project site fails validation when site name or client phone duplicates another existing record', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    // Site B exists with Dr. K. Senthil Kumar (phone: 94440 55667)
    // Site A exists with Mr. R. Balaji (phone: 98400 11223)
    $siteB = ProjectSite::where('name', 'Site B - Clinic & Residence')->first();
    expect($siteB)->not->toBeNull();

    // Try to update Site B to have Site A's name and Site A's client phone
    $response = $this->actingAs($siteManager)
        ->putJson("/sites/{$siteB->id}", [
            'name' => 'Site A - Duplex Villa',
            'location' => 'Anna Nagar, Chennai',
            'work_type' => 'Alteration',
            'estimated_budget' => 2800000,
            'status' => 'Planning',
            'client_option' => 'new',
            'client_name' => 'Dr. K. Senthil Kumar',
            'client_phone' => '98400 11223',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'client_phone']);
});

test('update project site allows keeping its own name and client phone', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $siteA = ProjectSite::where('name', 'Site A - Duplex Villa')->first();
    expect($siteA)->not->toBeNull();

    $response = $this->actingAs($siteManager)
        ->putJson("/sites/{$siteA->id}", [
            'name' => 'Site A - Duplex Villa',
            'location' => 'Porur DLF, Chennai',
            'work_type' => 'New Build',
            'estimated_budget' => 6500000,
            'status' => 'In Progress',
            'client_name' => 'Mr. R. Balaji',
            'client_phone' => '98400 11223',
        ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
});

test('show project site returns site details with client', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $site = ProjectSite::first();
    expect($site)->not->toBeNull();
    $site->update(['created_by' => $siteManager->id]);

    $response = $this->actingAs($siteManager)
        ->getJson("/sites/{$site->encrypted_id}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'id' => $site->id,
            'name' => $site->name,
        ],
    ]);
});

test('show project site view page renders successfully with site and client details', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $site = ProjectSite::with('client')->first();
    expect($site)->not->toBeNull();
    $site->update(['created_by' => $siteManager->id]);

    $response = $this->actingAs($siteManager)
        ->get("/sites/{$site->encrypted_id}");

    $response->assertStatus(200);
    $response->assertViewIs('sites.show');
    $response->assertSee($site->name);
    $response->assertSee($site->location);
    if ($site->client) {
        $response->assertSee($site->client->name);
    }
});

test('show project site view page displays its site cash transactions', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $site = ProjectSite::first();
    expect($site)->not->toBeNull();
    $site->update(['created_by' => $siteManager->id]);

    // Create a site cash transaction for this site
    SiteCash::create([
        'project_site_id' => $site->id,
        'transaction_type' => 'Material Purchase',
        'flow_type' => 'outflow',
        'amount' => 15000.00,
        'in_amount' => 0.00,
        'out_amount' => 15000.00,
        'is_expense' => true,
        'bill_no' => 'MAT-5544',
        'description' => 'River sand and cement bags',
        'payment_date' => '2026-11-04',
        'payment_time' => '11:00 AM',
    ]);

    $response = $this->actingAs($siteManager)
        ->get("/sites/{$site->encrypted_id}");

    $response->assertStatus(200);
    $response->assertSee('Site Cash Transactions');
    $response->assertSee('Material Purchase');
    $response->assertSee('MAT-5544');
    $response->assertSee('River sand and cement bags');
    $response->assertSee('15,000.00');
});

test('update project site updates site and client details successfully', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $site = ProjectSite::first();
    expect($site)->not->toBeNull();

    $updatePayload = [
        'name' => 'Site A - Premium Duplex Villa',
        'location' => 'Porur DLF, Chennai',
        'work_type' => 'New Build',
        'start_date' => '2026-01-15',
        'end_date' => '2026-12-31',
        'estimated_budget' => 7000000,
        'status' => 'Completed',
        'client_name' => 'Mr. R. Balaji Updated',
        'client_phone' => '98400 99999',
        'client_address' => 'Updated Address, Porur',
    ];

    $response = $this->actingAs($siteManager)
        ->putJson("/sites/{$site->id}", $updatePayload);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('project_sites', [
        'id' => $site->id,
        'name' => 'Site A - Premium Duplex Villa',
        'status' => 'Completed',
    ]);

    $this->assertDatabaseHas('clients', [
        'id' => $site->client_id,
        'name' => 'Mr. R. Balaji Updated',
        'phone' => '98400 99999',
    ]);
});

test('delete project site route is not available', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $site = ProjectSite::first();
    expect($site)->not->toBeNull();

    $response = $this->actingAs($siteManager)
        ->deleteJson("/sites/{$site->id}");

    $response->assertStatus(405);
});

test('show project site view renders weekly report tab with 7 monday to sunday rows per week', function () {
    $siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $client = Client::create([
        'name' => 'Weekly Report Client',
        'phone' => '9876500111',
    ]);

    $site = ProjectSite::create([
        'client_id' => $client->id,
        'name' => 'Weekly Report Test Site',
        'location' => 'Chennai',
        'work_type' => 'Construction',
        'status' => 'In Progress',
        'created_by' => $siteManager->id,
    ]);

    DailyAllocation::create([
        'project_site_id' => $site->id,
        'date' => '2026-11-18', // Wednesday of 16 Nov - 22 Nov 2026
        'total_labour_count' => 3,
        'total_labour_wage' => 3500.00,
        'total_expenses' => 250.00,
        'grand_total' => 3750.00,
        'created_by' => $siteManager->id,
    ]);

    $response = $this->actingAs($siteManager)->get("/sites/{$site->encrypted_id}");
    $response->assertStatus(200);
    $response->assertSee('Weekly Report');
    $response->assertSee('16 Nov 2026 – 22 Nov 2026 (Monday – Sunday)');
    $response->assertSee('Showing Week 1 of 1 (7 rows: Monday – Sunday)');
    $response->assertSee('Monday');
    $response->assertSee('Sunday');
    $response->assertSee('3,500.00');
});
