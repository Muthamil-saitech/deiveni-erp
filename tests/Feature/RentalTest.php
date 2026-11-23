<?php

use App\Models\Property;
use App\Models\Rental;
use App\Models\User;
use App\Repositories\Contracts\RentalRepositoryInterface;
use App\Repositories\Eloquent\RentalRepository;
use App\Services\Contracts\RentalServiceInterface;
use App\Services\RentalService;
use Database\Seeders\RentalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RentalSeeder::class);
    // Create and authenticate an account manager user (required by middleware)
    $this->user = User::factory()->create([
        'role' => 'account_manager',
    ]);
    $this->actingAs($this->user);
});

test('rentals index page renders successfully and displays seeded rentals and statistics', function () {
    $response = $this->get('/rentals');

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
});

test('rentals are ordered with last added row first', function () {
    $service = app(RentalServiceInterface::class);
    $rentals = $service->getAllRentals();

    $ids = $rentals->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    $property = Property::first();

    // After adding a new rental, it comes first in the list
    $newRental = $service->createRental([
        'property_id' => $property->id,
        'shop_home_office_name' => 'ஐடி பார்க் தளம் 5',
        'property_type' => 'Office Space',
        'location' => 'OMR, Thoraipakkam, Chennai',
        'tenant_name' => 'NextGen Software',
        'tenant_phone' => '+91 98409 99999',
        'monthly_rent' => 45000.00,
        'security_deposit' => 350000.00,
        'rent_due_day' => '1st of Month',
        'status' => 'Active',
    ]);

    $updatedRentals = $service->getAllRentals();
    expect($updatedRentals->first()->id)->toBe($newRental->id)
        ->and($updatedRentals->first()->property_id)->toBe($property->id);
});

test('service and repository interfaces are properly bound in service container', function () {
    $repository = app(RentalRepositoryInterface::class);
    $service = app(RentalServiceInterface::class);

    expect($repository)->toBeInstanceOf(RentalRepository::class)
        ->and($service)->toBeInstanceOf(RentalService::class);
});

test('storing a rental requires mandatory fields and validates data correctly', function () {
    $response = $this->postJson('/rentals', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['property_id', 'property_type', 'location', 'monthly_rent', 'status']);
});

test('storing a rental validates property_type value', function () {
    $property = Property::first();

    $response = $this->postJson('/rentals', [
        'property_id' => $property->id,
        'property_type' => 'Invalid Type',
        'location' => 'Chennai',
        'monthly_rent' => 10000,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['property_type']);
});

test('storing a rental validates monthly_rent is non-negative', function () {
    $property = Property::first();

    $response = $this->postJson('/rentals', [
        'property_id' => $property->id,
        'property_type' => 'Commercial Shop',
        'location' => 'Chennai',
        'monthly_rent' => -500,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['monthly_rent']);
});

test('storing a rental validates status is Active or Inactive', function () {
    $property = Property::first();

    $response = $this->postJson('/rentals', [
        'property_id' => $property->id,
        'property_type' => 'Commercial Shop',
        'location' => 'Chennai',
        'monthly_rent' => 10000,
        'status' => 'Unknown',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('can store a newly created rental property successfully', function () {
    $property = Property::first();

    $response = $this->postJson('/rentals', [
        'property_id' => $property->id,
        'shop_home_office_name' => 'தொழில்நுட்ப மையம் 101',
        'property_type' => 'Office Space',
        'location' => 'Perungudi, Chennai',
        'tenant_name' => 'Apex Cloud Labs',
        'tenant_phone' => '+91 98401 22334',
        'monthly_rent' => 28000.00,
        'security_deposit' => 200000.00,
        'rent_due_day' => '5th of Month',
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $prop = Rental::where('shop_home_office_name', 'தொழில்நுட்ப மையம் 101')->first();
    expect($prop)->not->toBeNull()
        ->and((float) $prop->monthly_rent)->toBe(28000.0)
        ->and($prop->location)->toBe('Perungudi, Chennai')
        ->and($prop->property_id)->toBe($property->id);
});

test('can update an existing rental property', function () {
    $rental = Rental::first();

    $response = $this->putJson("/rentals/{$rental->id}", [
        'property_id' => $rental->property_id,
        'shop_home_office_name' => 'வணிகக் கடை 1 புதுப்பிக்கப்பட்டது',
        'property_type' => 'Commercial Shop',
        'location' => 'Medavakkam Main Road, Chennai - 100',
        'tenant_name' => 'Royal Logistics & Travels',
        'tenant_phone' => '+91 98410 11223',
        'monthly_rent' => 9500.00,
        'security_deposit' => 90000.00,
        'rent_due_day' => '5th of Month',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $rental->refresh();
    expect($rental->shop_home_office_name)->toBe('வணிகக் கடை 1 புதுப்பிக்கப்பட்டது')
        ->and((float) $rental->monthly_rent)->toBe(9500.0);
});

test('can delete a rental property', function () {
    $rental = Rental::first();

    $response = $this->deleteJson("/rentals/{$rental->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Rental::find($rental->id))->toBeNull();
});

test('computed statistics calculate correctly', function () {
    $service = app(RentalServiceInterface::class);
    $stats = $service->getRentalStats();

    expect($stats['total_properties'])->toBe(6)
        ->and($stats['active_properties'])->toBe(5)
        ->and((float) $stats['monthly_expected_rent'])->toBe(86398.0)
        ->and((float) $stats['total_security_deposit'])->toBe(950000.0);
});
