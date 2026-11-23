<?php

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Repositories\Eloquent\PropertyRepository;
use App\Services\Contracts\PropertyServiceInterface;
use App\Services\PropertyService;
use Database\Seeders\PropertySeeder;
use Database\Seeders\PropertyTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PropertySeeder::class);
});

test('properties index page renders successfully and displays seeded properties and statistics', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $response = $this->actingAs($accountManager)->get('/properties');

    $response->assertStatus(200);
    $response->assertSee('Property Master');
    $response->assertSee('Properties Directory', false);
    $response->assertSee('Medavakkam Commercial Complex');
    $response->assertSee('Pallikaranai Residential Complex');
    $response->assertSee('Velachery Commercial G+1');
    $response->assertSee('Tambaram Warehouse Space');
    $response->assertSee('GST Road Showroom Plaza');
    $response->assertSee('id="statTotalProperties"', false);
    $response->assertSee('id="statActiveProperties"', false);
    $response->assertSee('id="statInactiveProperties"', false);
    $response->assertSee('id="propertyModal"', false);
    $response->assertSee('id="modalPropertyNameTa"', false);
    $response->assertSee('Property Name in Tamil', false);
    $response->assertSee('மேடவாக்கம் வணிக வளாகம்', false);
});

test('property master page is accessible by account manager and admin but restricted for site manager', function () {
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
    $this->actingAs($accountManager)->get('/properties')->assertStatus(200);

    // Admin can access
    $this->actingAs($admin)->get('/properties')->assertStatus(200);

    // Site manager redirected with error / 403 json
    $this->actingAs($siteManager)->get('/properties')->assertRedirect(route('dashboard'));
    $this->actingAs($siteManager)->getJson('/properties')->assertStatus(403);
});

test('properties are ordered with last added row first', function () {
    $service = app(PropertyServiceInterface::class);

    $newProperty = $service->createProperty([
        'property_name' => 'Koyambedu Commercial Hub',
        'status' => 'Active',
    ]);

    $updatedProperties = $service->getAllProperties();
    expect($updatedProperties->first()->id)->toBe($newProperty->id)
        ->and($updatedProperties->first()->property_name)->toBe('Koyambedu Commercial Hub');
});

test('service and repository interfaces for property are properly bound in service container', function () {
    $repository = app(PropertyRepositoryInterface::class);
    $service = app(PropertyServiceInterface::class);

    expect($repository)->toBeInstanceOf(PropertyRepository::class)
        ->and($service)->toBeInstanceOf(PropertyService::class);
});

test('storing a property requires property_name and status', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $response = $this->actingAs($accountManager)->postJson('/properties', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['property_name', 'status']);
});

test('storing a property validates uniqueness of property name', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    // Medavakkam Commercial Complex is already seeded
    $response = $this->actingAs($accountManager)->postJson('/properties', [
        'property_name' => 'Medavakkam Commercial Complex',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['property_name']);
});

test('storing a property succeeds with valid data including tamil name', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $response = $this->actingAs($accountManager)->postJson('/properties', [
        'property_name' => 'Adyar River View Tower',
        'property_name_ta' => 'அடையாறு ரிவர் வியூ டவர்',
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'property_name' => 'Adyar River View Tower',
                'property_name_ta' => 'அடையாறு ரிவர் வியூ டவர்',
                'status' => 'Active',
            ],
        ]);

    $created = Property::where('property_name', 'Adyar River View Tower')->first();
    expect($created)->not->toBeNull()
        ->and($created->property_name_ta)->toBe('அடையாறு ரிவர் வியூ டவர்');
});

test('can update an existing property including tamil name', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $property = Property::where('property_name', 'Medavakkam Commercial Complex')->first();

    $response = $this->actingAs($accountManager)->putJson("/properties/{$property->id}", [
        'property_name' => 'Medavakkam Commercial Complex Phase 1',
        'property_name_ta' => 'மேடவாக்கம் வணிக வளாகம் கட்டம் 1',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $property->refresh();
    expect($property->property_name)->toBe('Medavakkam Commercial Complex Phase 1')
        ->and($property->property_name_ta)->toBe('மேடவாக்கம் வணிக வளாகம் கட்டம் 1');
});

test('cannot delete a property that is currently in use by property types', function () {
    $this->seed(PropertyTypeSeeder::class);

    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    // Medavakkam Commercial Complex is in use in PropertyTypeSeeder
    $property = Property::where('property_name', 'Medavakkam Commercial Complex')->first();

    $response = $this->actingAs($accountManager)->deleteJson("/properties/{$property->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    expect(Property::find($property->id))->not->toBeNull();
});

test('can delete a property that is not in use by any property types', function () {
    $this->seed(PropertyTypeSeeder::class);

    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    // Tambaram South Bypass Shed is not in use
    $property = Property::where('property_name', 'Tambaram South Bypass Shed')->first();

    $response = $this->actingAs($accountManager)->deleteJson("/properties/{$property->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Property::find($property->id))->toBeNull();
});

test('service calculates property statistics accurately', function () {
    $service = app(PropertyServiceInterface::class);
    $stats = $service->getPropertyStats();

    expect($stats)->toHaveKeys(['total_properties', 'active_properties', 'inactive_properties'])
        ->and($stats['total_properties'])->toBe(9)
        ->and($stats['active_properties'])->toBe(8)
        ->and($stats['inactive_properties'])->toBe(1);
});

test('properties index page renders table pagination elements and controls', function () {
    $accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $response = $this->actingAs($accountManager)->get('/properties');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
