<?php

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\User;
use App\Repositories\Contracts\PropertyTypeRepositoryInterface;
use App\Repositories\Eloquent\PropertyTypeRepository;
use App\Services\Contracts\PropertyTypeServiceInterface;
use App\Services\PropertyTypeService;
use Database\Seeders\PropertySeeder;
use Database\Seeders\PropertyTypeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PropertySeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->user = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);
    $this->actingAs($this->user);
});

test('property types index page renders successfully and displays seeded items and statistics', function () {
    $response = $this->get('/property-types');

    $response->assertStatus(200);
    $response->assertSee('Property Type Master');
    $response->assertSee('Property Types & Units Directory', false);
    $response->assertSee('Medavakkam Commercial Complex');
    $response->assertSee('M/s Royal Travels');
    $response->assertSee('Pallikaranai Residential Complex');
    $response->assertSee('GST Road Showroom Plaza');
    $response->assertSee('id="statTotalProps"', false);
    $response->assertSee('id="statOccupiedUnits"', false);
    $response->assertSee('id="statTotalRent"', false);
    $response->assertSee('id="statTotalDeposit"', false);
    $response->assertSee('id="propertyTypeModal"', false);
    $response->assertSee('id="modalFloorType"', false);
});

test('property types are ordered with last added row first', function () {
    $service = app(PropertyTypeServiceInterface::class);
    $items = $service->getAllPropertyTypes();

    $ids = $items->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    $property = Property::where('property_name', 'Velachery Commercial G+1')->first() ?? Property::first();

    $newItem = $service->createPropertyType([
        'property_id' => $property->id,
        'shop_home_office_name' => 'Unit 5',
        'property_type' => 'Office Space',
        'floor_type' => 'First Floor (FF)',
        'location' => 'Bypass Road, Velachery',
        'tenant_name' => 'NextGen Software',
        'tenant_phone' => '+91 98409 99999',
        'monthly_rent' => 45000.00,
        'security_deposit' => 350000.00,
        'rent_due_day' => '1st of Month',
        'status' => 'Active',
    ]);

    $updatedItems = $service->getAllPropertyTypes();
    expect($updatedItems->first()->id)->toBe($newItem->id)
        ->and($updatedItems->first()->floor_type)->toBe('First Floor (FF)');
});

test('service and repository interfaces for property type are properly bound in service container', function () {
    $repository = app(PropertyTypeRepositoryInterface::class);
    $service = app(PropertyTypeServiceInterface::class);

    expect($repository)->toBeInstanceOf(PropertyTypeRepository::class)
        ->and($service)->toBeInstanceOf(PropertyTypeService::class);
});

test('storing a property type requires mandatory fields', function () {
    $response = $this->postJson('/property-types', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['property_id', 'property_type', 'location', 'monthly_rent', 'status']);
});

test('storing a property type validates uniqueness of shop_home_office_name with bilingual error message', function () {
    $property = Property::first();

    $response = $this->postJson('/property-types', [
        'property_id' => $property->id,
        'shop_home_office_name' => 'Shop 1 (வணிகக் கடை 1)', // existing seeded value
        'property_type' => 'Commercial Shop',
        'location' => 'Some Location, Chennai',
        'monthly_rent' => 10000,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shop_home_office_name']);

    $errors = $response->json('errors');
    expect($errors['shop_home_office_name'][0])
        ->toContain('This shop / home / office name already exists')
        ->toContain('இந்த கடை / வீடு / அலுவலக பெயர் ஏற்கனவே உள்ளது');
});

test('updating a property type validates uniqueness of shop_home_office_name against other records', function () {
    $item1 = PropertyType::where('shop_home_office_name', 'Shop 1 (வணிகக் கடை 1)')->first();
    $item2 = PropertyType::where('shop_home_office_name', 'Shop 2 (வணிகக் கடை 2)')->first();

    // Updating item1 with item2's shop_home_office_name should fail
    $response = $this->putJson("/property-types/{$item1->id}", [
        'property_id' => $item1->property_id,
        'shop_home_office_name' => $item2->shop_home_office_name,
        'property_type' => $item1->property_type,
        'location' => $item1->location,
        'monthly_rent' => $item1->monthly_rent,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['shop_home_office_name']);

    $errors = $response->json('errors');
    expect($errors['shop_home_office_name'][0])
        ->toContain('This shop / home / office name already exists')
        ->toContain('இந்த கடை / வீடு / அலுவலக பெயர் ஏற்கனவே உள்ளது');

    // Updating item1 with its own shop_home_office_name should succeed
    $successResponse = $this->putJson("/property-types/{$item1->id}", [
        'property_id' => $item1->property_id,
        'shop_home_office_name' => $item1->shop_home_office_name,
        'property_type' => $item1->property_type,
        'location' => $item1->location,
        'monthly_rent' => 9999,
        'status' => 'Active',
    ]);

    $successResponse->assertStatus(200);
});

test('database throws integrity constraint violation when inserting duplicate shop_home_office_name directly', function () {
    $property = Property::first();

    expect(function () use ($property) {
        PropertyType::create([
            'property_id' => $property->id,
            'shop_home_office_name' => 'Shop 1 (வணிகக் கடை 1)', // duplicate
            'property_type' => 'Commercial Shop',
            'location' => 'Direct DB test',
            'monthly_rent' => 12000,
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);
});

test('storing a property type with floor type persists successfully', function () {
    $property = Property::where('property_name', 'Medavakkam Commercial Complex')->first();

    $response = $this->postJson('/property-types', [
        'property_id' => $property->id,
        'shop_home_office_name' => 'Shop 3B',
        'property_type' => 'Commercial Shop',
        'floor_type' => 'First Floor (FF)',
        'location' => 'Medavakkam Main Road, Chennai',
        'tenant_name' => 'New Tenant',
        'tenant_phone' => '+91 98415 66778',
        'monthly_rent' => 15000,
        'security_deposit' => 100000,
        'rent_due_day' => '1st of Month',
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'property_id' => $property->id,
                'property_name' => 'Medavakkam Commercial Complex',
                'floor_type' => 'First Floor (FF)',
            ],
        ]);

    expect(PropertyType::where('floor_type', 'First Floor (FF)')->where('shop_home_office_name', 'Shop 3B')->exists())->toBeTrue();
});

test('storing a property type saves property_id in property_types table', function () {
    $property = Property::first();

    $response = $this->postJson('/property-types', [
        'property_id' => $property->id,
        'shop_home_office_name' => 'Shop 99',
        'property_type' => 'Commercial Shop',
        'floor_type' => 'Ground Floor (GF)',
        'location' => 'Velachery Main Road, Chennai',
        'monthly_rent' => 20000,
        'security_deposit' => 150000,
        'rent_due_day' => '1st of Month',
        'status' => 'Active',
    ]);

    $response->assertStatus(201);

    $created = PropertyType::where('shop_home_office_name', 'Shop 99')->first();
    expect($created)->not->toBeNull()
        ->and($created->property_id)->toBe($property->id)
        ->and($created->property->property_name)->toBe($property->property_name);
});

test('updating an existing property type', function () {
    $item = PropertyType::first();

    $response = $this->putJson("/property-types/{$item->id}", [
        'property_id' => $item->property_id,
        'shop_home_office_name' => 'Updated Office Unit',
        'property_type' => 'Office Space',
        'floor_type' => 'Second Floor (2F)',
        'location' => $item->location,
        'tenant_name' => 'Updated Tenant',
        'tenant_phone' => '+91 99999 88888',
        'monthly_rent' => 30000,
        'security_deposit' => 250000,
        'rent_due_day' => '5th of Month',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $item->refresh();
    expect($item->shop_home_office_name)->toBe('Updated Office Unit')
        ->and($item->floor_type)->toBe('Second Floor (2F)');
});

test('can delete a property type', function () {
    $item = PropertyType::first();

    $response = $this->deleteJson("/property-types/{$item->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(PropertyType::find($item->id))->toBeNull();
});

test('property types index page renders table pagination elements and controls', function () {
    $response = $this->get('/property-types');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
