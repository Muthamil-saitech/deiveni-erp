<?php

use App\Models\Material;
use App\Models\Unit;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Repositories\Eloquent\MaterialRepository;
use App\Services\Contracts\MaterialServiceInterface;
use App\Services\MaterialService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('materials index page renders successfully and displays seeded materials', function () {
    $response = $this->get('/materials');

    $response->assertStatus(200);
    $response->assertSee('Materials Catalog');
    $response->assertSee('Cement (OPC 53 Grade)');
    $response->assertSee('சிமெண்ட் (50 கிகி மூட்டை)');
    $response->assertSee('M-Sand (Manufactured Sand)');
    $response->assertSee('20mm Blue Metal Aggregate');
    $response->assertSee('TMT 550D Steel Rods (12mm)');
    $response->assertSee('Needle Vibrator 2HP (Concrete)');
});

test('unit of measurement dropdown shows active units from unit master and excludes inactive units', function () {
    $activeUnit = Unit::create([
        'name_en' => 'Active Bundle',
        'name_ta' => 'செயலில் உள்ள கட்டு',
        'code' => 'BNDL_ACT',
        'status' => 'Active',
    ]);

    Unit::create([
        'name_en' => 'Inactive Decommissioned Unit',
        'name_ta' => 'நிறுத்தப்பட்ட அலகு',
        'code' => 'DEC_INACT',
        'status' => 'Inactive',
    ]);

    $response = $this->get('/materials');

    $response->assertStatus(200);
    // Active unit must appear in the dropdown option with its ID as value
    $response->assertSee('Active Bundle');
    $response->assertSee('value="'.$activeUnit->id.'"', false);
    // Inactive unit must NOT appear in the dropdown option
    $response->assertDontSee('Inactive Decommissioned Unit');
});

test('service and repository interfaces are properly bound in service container', function () {
    $repository = app(MaterialRepositoryInterface::class);
    $service = app(MaterialServiceInterface::class);

    expect($repository)->toBeInstanceOf(MaterialRepository::class)
        ->and($service)->toBeInstanceOf(MaterialService::class);
});

test('materials are ordered with last added row first', function () {
    $service = app(MaterialServiceInterface::class);
    $materials = $service->getAllMaterials();

    // Verify materials are sorted descending by id
    $ids = $materials->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    // After adding a new row, it comes first in the list
    $unit = Unit::first() ?? Unit::create([
        'name_en' => 'Litres',
        'name_ta' => 'லிட்டர்',
        'code' => 'LTR',
        'status' => 'Active',
    ]);

    $newMaterial = $service->createMaterial([
        'name_en' => 'Waterproofing Chemical (Dr. Fixit)',
        'name_ta' => 'வாட்டர்ப்ரூபிங் கெமிக்கல்',
        'unit_id' => $unit->id,
        'status' => 'Active',
    ]);

    $updatedMaterials = $service->getAllMaterials();
    expect($updatedMaterials->first()->id)->toBe($newMaterial->id)
        ->and($updatedMaterials->first()->name_en)->toBe('Waterproofing Chemical (Dr. Fixit)');
});

test('storing a material requires mandatory fields and validates data correctly', function () {
    $response = $this->postJson('/materials', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en', 'name_ta', 'unit_id', 'status']);

    $errors = $response->json('errors');
    expect($errors['name_en'][0])->toContain('Material English name is required')->toContain('ஆங்கில பெயர்')
        ->and($errors['name_ta'][0])->toContain('Material Tamil name is required')->toContain('தமிழ் பெயர்')
        ->and($errors['unit_id'][0])->toContain('Please select a unit of measurement')->toContain('அளவீட்டு அலகு')
        ->and($errors['status'][0])->toContain('Please select a status')->toContain('நிலை');
});

test('storing a material enforces unique english and tamil material names', function () {
    $unit = Unit::first();

    $response = $this->postJson('/materials', [
        'name_en' => 'Cement (OPC 53 Grade)', // seeded name_en
        'name_ta' => 'சிமெண்ட் (50 கிகி மூட்டை)', // seeded name_ta
        'unit_id' => $unit->id,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en', 'name_ta']);

    $errors = $response->json('errors');
    expect($errors['name_en'][0])->toContain('Material English name already exists')->toContain('ஆங்கில பெயர்')
        ->and($errors['name_ta'][0])->toContain('Material Tamil name already exists')->toContain('தமிழ் பெயர்');
});

test('database throws integrity constraint violation when inserting duplicate material name_en or name_ta directly', function () {
    $unit = Unit::first();

    expect(function () use ($unit) {
        Material::create([
            'name_en' => 'Cement (OPC 53 Grade)', // duplicate of seeded Cement
            'name_ta' => 'தனித்துவமான கட்டுமான பொருள்',
            'unit_id' => $unit->id,
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);

    expect(function () use ($unit) {
        Material::create([
            'name_en' => 'Unique English Construction Material',
            'name_ta' => 'சிமெண்ட் (50 கிகி மூட்டை)', // duplicate of seeded சிமெண்ட்
            'unit_id' => $unit->id,
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);
});

test('updating material validates unique name_en and name_ta against other materials', function () {
    $cement = Material::where('name_en', 'Cement (OPC 53 Grade)')->first();
    $sand = Material::where('name_en', 'M-Sand (Manufactured Sand)')->first();
    $unit = Unit::first();

    // Updating sand with cement's names should fail validation
    $response = $this->putJson("/materials/{$sand->id}", [
        'name_en' => $cement->name_en,
        'name_ta' => $cement->name_ta,
        'unit_id' => $unit->id,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_en', 'name_ta']);

    // Updating sand keeping its own names should succeed
    $validResponse = $this->putJson("/materials/{$sand->id}", [
        'name_en' => $sand->name_en,
        'name_ta' => $sand->name_ta,
        'unit_id' => $unit->id,
        'status' => 'Active',
    ]);

    $validResponse->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('storing a material validates status value', function () {
    $unit = Unit::first();

    $response = $this->postJson('/materials', [
        'name_en' => 'River Sand',
        'name_ta' => 'ஆற்று மணல்',
        'unit_id' => $unit?->id ?? 1,
        'status' => 'InvalidStatus',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('storing a material with invalid unit_id fails validation', function () {
    $response = $this->postJson('/materials', [
        'name_en' => 'River Sand',
        'name_ta' => 'ஆற்று மணல்',
        'unit_id' => 999999,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['unit_id']);
});

test('can store a new material successfully via AJAX', function () {
    $bagUnit = Unit::where('code', 'BAG')->first() ?? Unit::create([
        'name_en' => 'Bags',
        'name_ta' => 'மூட்டை',
        'code' => 'BAG',
        'status' => 'Active',
    ]);

    $response = $this->postJson('/materials', [
        'name_en' => 'Wall Putty (Birla White)',
        'name_ta' => 'வால் புட்டி',
        'unit_id' => $bagUnit->id,
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name_en' => 'Wall Putty (Birla White)',
                'name_ta' => 'வால் புட்டி',
                'unit_id' => $bagUnit->id,
                'status' => 'Active',
            ],
        ]);

    $this->assertDatabaseHas('materials', [
        'name_en' => 'Wall Putty (Birla White)',
        'unit_id' => $bagUnit->id,
    ]);
});

test('can view a specific material via show endpoint', function () {
    $material = Material::first();

    $response = $this->getJson("/materials/{$material->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $material->id,
                'name_en' => $material->name_en,
            ],
        ]);
});

test('viewing a non-existent material returns 404', function () {
    $response = $this->getJson('/materials/999999');

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Material not found.',
        ]);
});

test('can update an existing material successfully', function () {
    $material = Material::where('name_en', 'Cement (OPC 53 Grade)')->first();
    $bagUnit = Unit::where('code', 'BAG')->first() ?? Unit::first();

    $response = $this->putJson("/materials/{$material->id}", [
        'name_en' => 'Cement (UltraTech OPC 53 Grade)',
        'name_ta' => 'அல்ட்ராடெக் சிமெண்ட் (50 கிகி)',
        'unit_id' => $bagUnit->id,
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'name_en' => 'Cement (UltraTech OPC 53 Grade)',
            ],
        ]);

    $material->refresh();
    expect($material->name_en)->toBe('Cement (UltraTech OPC 53 Grade)')
        ->and($material->unit_id)->toBe($bagUnit->id);
});

test('cannot delete a material that is used by a supplier in supplier master', function () {
    // Cement (OPC 53 Grade) is linked to supplier 2
    $material = Material::where('name_en', 'Cement (OPC 53 Grade)')->first();
    expect($material->suppliers()->count())->toBeGreaterThan(0);

    $response = $this->deleteJson("/materials/{$material->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    expect(Material::find($material->id))->not->toBeNull();
});

test('can delete a material that is not used by any supplier successfully', function () {
    $material = Material::where('name_en', 'Needle Vibrator 2HP (Concrete)')->first();
    expect($material->suppliers()->count())->toBe(0);

    $response = $this->deleteJson("/materials/{$material->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => "Material \"{$material->name_en}\" removed successfully.",
        ]);

    expect(Material::find($material->id))->toBeNull();
});

test('can delete a newly created material that has no suppliers', function () {
    $unit = Unit::first();
    $material = Material::create([
        'name_en' => 'Unused Test Material',
        'name_ta' => 'பயன்படுத்தப்படாத பொருள்',
        'unit_id' => $unit->id,
        'status' => 'Active',
    ]);

    $response = $this->deleteJson("/materials/{$material->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Material::find($material->id))->toBeNull();
});

test('materials index page renders table pagination elements and controls', function () {
    $response = $this->get('/materials');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
