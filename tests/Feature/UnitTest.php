<?php

use App\Models\Unit;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Eloquent\UnitRepository;
use App\Services\Contracts\UnitServiceInterface;
use App\Services\UnitService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('units index page renders successfully and displays seeded units', function () {
    $response = $this->get('/units');

    $response->assertStatus(200);
    $response->assertSee('Units of Measurement Directory');
    $response->assertSee('Bags');
    $response->assertSee('மூட்டை');
    $response->assertSee('BAG');
    $response->assertSee('KG');
    $response->assertSee('LTR');
    $response->assertSee('DAY');
});

test('units are ordered with last added row first', function () {
    $service = app(UnitServiceInterface::class);
    $units = $service->getAllUnits();

    // Verify units are sorted descending by id
    $ids = $units->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    // After adding a new unit, it comes first in the list
    $newUnit = $service->createUnit([
        'name_en' => 'Cubic Metre',
        'name_ta' => 'கன மீட்டர்',
        'code' => 'CUM',
        'usage' => 'Concrete volume calculation',
        'status' => 'Active',
    ]);

    $updatedUnits = $service->getAllUnits();
    expect($updatedUnits->first()->id)->toBe($newUnit->id)
        ->and($updatedUnits->first()->code)->toBe('CUM');
});

test('service and repository interfaces for unit are properly bound in service container', function () {
    $repository = app(UnitRepositoryInterface::class);
    $service = app(UnitServiceInterface::class);

    expect($repository)->toBeInstanceOf(UnitRepository::class)
        ->and($service)->toBeInstanceOf(UnitService::class);
});

test('storing a unit requires mandatory fields and validates data correctly', function () {
    $response = $this->postJson('/units', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en', 'name_ta', 'code', 'status']);

    $errors = $response->json('errors');
    expect($errors['name_en'][0])->toContain('Unit English name is required')->toContain('ஆங்கில பெயர்')
        ->and($errors['name_ta'][0])->toContain('Unit Tamil name is required')->toContain('தமிழ் பெயர்')
        ->and($errors['code'][0])->toContain('Unit symbol / code is required')->toContain('குறியீடு')
        ->and($errors['status'][0])->toContain('Please select a status')->toContain('நிலை');
});

test('storing a unit enforces unique code, english name and tamil name', function () {
    // Duplicate code, name_en, name_ta (all match seeded 'Bags' / 'மூட்டை' / 'BAG')
    $response = $this->postJson('/units', [
        'name_en' => 'Bags',
        'name_ta' => 'மூட்டை',
        'code' => 'BAG',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code', 'name_en', 'name_ta']);

    $errors = $response->json('errors');
    expect($errors['name_en'][0])->toContain('Unit English name already exists')->toContain('ஆங்கில பெயர்')
        ->and($errors['name_ta'][0])->toContain('Unit Tamil name already exists')->toContain('தமிழ் பெயர்');
});

test('database throws integrity constraint violation when inserting duplicate unit name_en or name_ta directly', function () {
    expect(function () {
        Unit::create([
            'name_en' => 'Bags', // duplicate of seeded Bags
            'name_ta' => 'தனித்துவமான அலகு',
            'code' => 'UNIQUE1',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);

    expect(function () {
        Unit::create([
            'name_en' => 'Unique English Unit',
            'name_ta' => 'மூட்டை', // duplicate of seeded மூட்டை
            'code' => 'UNIQUE2',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);
});

test('updating unit validates unique name_en and name_ta against other units', function () {
    $bag = Unit::where('code', 'BAG')->first();
    $kg = Unit::where('code', 'KG')->first();

    // Attempting to update BAG with KG's name_en and name_ta should fail validation
    $response = $this->putJson("/units/{$bag->id}", [
        'name_en' => $kg->name_en,
        'name_ta' => $kg->name_ta,
        'code' => 'BAG',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en', 'name_ta']);

    // Updating BAG with its own existing names should succeed
    $validResponse = $this->putJson("/units/{$bag->id}", [
        'name_en' => $bag->name_en,
        'name_ta' => $bag->name_ta,
        'code' => 'BAG',
        'status' => 'Active',
    ]);

    $validResponse->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('storing a unit validates status value', function () {
    $response = $this->postJson('/units', [
        'name_en' => 'Box',
        'name_ta' => 'பெட்டி',
        'code' => 'BOX',
        'status' => 'Archived', // Invalid status
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('can create a unit successfully via json request', function () {
    $response = $this->postJson('/units', [
        'name_en' => 'Packets',
        'name_ta' => 'பாக்கெட்டுகள்',
        'code' => 'pkt', // Should be normalized to PKT
        'usage' => 'Small hardware fittings',
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $createdUnit = Unit::where('code', 'PKT')->first();
    expect($createdUnit)->not->toBeNull()
        ->and($createdUnit->name_en)->toBe('Packets')
        ->and($createdUnit->code)->toBe('PKT');
});

test('can retrieve single unit via show endpoint', function () {
    $unit = Unit::where('code', 'BAG')->first();

    $response = $this->getJson("/units/{$unit->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $unit->id,
                'code' => 'BAG',
                'name_en' => 'Bags',
            ],
        ]);
});

test('show returns 404 for non-existent unit', function () {
    $response = $this->getJson('/units/99999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Measurement unit not found.',
        ]);
});

test('can update an existing unit including its own code without unique constraint error', function () {
    $unit = Unit::where('code', 'BAG')->first();

    $response = $this->putJson("/units/{$unit->id}", [
        'name_en' => 'Bags (50 Kg Heavy)',
        'name_ta' => 'மூட்டை (50 கிலோ)',
        'code' => 'BAG',
        'usage' => 'Cement bags and dry mortar',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $unit->refresh();
    expect($unit->name_en)->toBe('Bags (50 Kg Heavy)')
        ->and($unit->name_ta)->toBe('மூட்டை (50 கிலோ)')
        ->and($unit->code)->toBe('BAG')
        ->and($unit->usage)->toBe('Cement bags and dry mortar');
});

test('updating unit with another existing unit code fails validation', function () {
    $bag = Unit::where('code', 'BAG')->first();

    $response = $this->putJson("/units/{$bag->id}", [
        'name_en' => 'Updated Bag',
        'name_ta' => 'மூட்டை',
        'code' => 'KG', // Already used by another unit
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

test('cannot delete a unit that is used in material master', function () {
    // BAG is used by Cement (OPC 53 Grade)
    $unit = Unit::where('code', 'BAG')->first();
    expect($unit->materials()->count())->toBeGreaterThan(0);

    $response = $this->deleteJson("/units/{$unit->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    expect(Unit::find($unit->id))->not->toBeNull();
});

test('can delete a unit that is not used in material master successfully', function () {
    // BRASS is not used by any seeded material
    $unit = Unit::where('code', 'BRASS')->first();
    expect($unit->materials()->count())->toBe(0);

    $response = $this->deleteJson("/units/{$unit->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Unit::find($unit->id))->toBeNull();
});

test('can delete a newly created unit that is not used in material master', function () {
    $unit = Unit::create([
        'name_en' => 'Square Yard',
        'name_ta' => 'சதுர கெஜம்',
        'code' => 'SQYD',
        'usage' => 'Land and plot measurement',
        'status' => 'Active',
    ]);

    $response = $this->deleteJson("/units/{$unit->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Unit::find($unit->id))->toBeNull();
});

test('units index page renders table pagination elements and controls', function () {
    $response = $this->get('/units');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
