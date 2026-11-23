<?php

use App\Models\Material;
use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Eloquent\SupplierRepository;
use App\Services\Contracts\SupplierServiceInterface;
use App\Services\IdCipherService;
use App\Services\SupplierService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('suppliers index page renders successfully and displays seeded suppliers and material pricing', function () {
    $response = $this->get('/suppliers');

    $response->assertStatus(200);
    $response->assertSee('Material Suppliers Directory');
    $response->assertSee('Sri Balaji Blue Metals & M-Sand');
    $response->assertSee('Chettinad Cement Agency');
    $response->assertSee('Jindal Steels & Hardware');
    $response->assertSee('33AAAAA0000A1Z5');
    $response->assertSee('id="supplierStatusFilter"', false);
    $response->assertSee('<th>Status</th>', false);
    $response->assertSee('<th>Materials</th>', false);
    $response->assertSee('view-supplier-btn', false);
    $response->assertDontSee('Material Types Supplied');
    $response->assertDontSee('Total Registered Suppliers:');
});

test('suppliers are ordered with last added row first', function () {
    $service = app(SupplierServiceInterface::class);
    $suppliers = $service->getAllSuppliers();

    // Verify suppliers are sorted descending by id
    $ids = $suppliers->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    // After adding a new supplier, it comes first in the list
    $newSupplier = $service->createSupplier([
        'name' => 'Apex Tiles & Granites',
        'contact_person' => 'R. Ramesh',
        'phone' => '98401 23456',
        'address' => 'Poonamallee, Chennai',
        'gst_number' => '33ZZZZZ9999Z1Z1',
        'status' => 'Active',
        'materials' => [
            ['material_id' => 1, 'price' => 430.00],
        ],
    ]);

    $updatedSuppliers = $service->getAllSuppliers();
    expect($updatedSuppliers->first()->id)->toBe($newSupplier->id)
        ->and($updatedSuppliers->first()->name)->toBe('Apex Tiles & Granites')
        ->and($updatedSuppliers->first()->materials)->toHaveCount(1)
        ->and((float) $updatedSuppliers->first()->materials->first()->pivot->price)->toBe(430.00);
});

test('service and repository interfaces for supplier are properly bound in service container', function () {
    $repository = app(SupplierRepositoryInterface::class);
    $service = app(SupplierServiceInterface::class);

    expect($repository)->toBeInstanceOf(SupplierRepository::class)
        ->and($service)->toBeInstanceOf(SupplierService::class);
});

test('storing a supplier requires mandatory fields and validates data correctly', function () {
    $response = $this->postJson('/suppliers', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'phone']);

    $errors = $response->json('errors');
    expect($errors['name'][0])->toContain('Supplier / company name is required')->toContain('சப்ளையர் பெயர்')
        ->and($errors['phone'][0])->toContain('Phone number is required')->toContain('தொலைபேசி எண்');
});

test('storing a supplier enforces unique name and unique gst number', function () {
    $response = $this->postJson('/suppliers', [
        'name' => 'Sri Balaji Blue Metals & M-Sand', // seeded name
        'phone' => '94441 99999',
        'gst_number' => '33AAAAA0000A1Z5', // seeded gst
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'gst_number']);

    $errors = $response->json('errors');
    expect($errors['name'][0])->toContain('This supplier / company name already exists')->toContain('சப்ளையர் பெயர் ஏற்கனவே உள்ளது')
        ->and($errors['gst_number'][0])->toContain('This GST number is already registered')->toContain('GST எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது');
});

test('storing a supplier validates gst number format strictly according to indian gst standards', function () {
    // Too short / invalid size
    $response1 = $this->postJson('/suppliers', [
        'name' => 'New Valid Supplier 1',
        'phone' => '94441 99991',
        'gst_number' => 'INVALID123',
    ]);

    $response1->assertStatus(422);
    $response1->assertJsonValidationErrors(['gst_number']);

    $errors1 = $response1->json('errors');
    expect($errors1['gst_number'][0])->toContain('GSTIN must be exactly 15 characters')->toContain('சரியாக 15 எழுத்துகளாக');

    // 15 chars but wrong format (e.g. invalid state or characters)
    $response2 = $this->postJson('/suppliers', [
        'name' => 'New Valid Supplier 2',
        'phone' => '94441 99992',
        'gst_number' => 'AA1234567890123',
    ]);

    $response2->assertStatus(422);
    $response2->assertJsonValidationErrors(['gst_number']);

    $errors2 = $response2->json('errors');
    expect($errors2['gst_number'][0])->toContain('Please enter a valid 15-digit GSTIN')->toContain('சரியான 15 இலக்க GST');

    // Valid 15-character GST format should pass
    $response3 = $this->postJson('/suppliers', [
        'name' => 'New Valid Supplier 3',
        'phone' => '94441 99993',
        'gst_number' => '33ABCDE1234F1Z9',
    ]);

    $response3->assertStatus(201);
});

test('database throws integrity constraint violation when inserting duplicate supplier name or gst_number directly', function () {
    expect(function () {
        Supplier::create([
            'name' => 'Sri Balaji Blue Metals & M-Sand', // duplicate name
            'phone' => '91111 22222',
            'gst_number' => '33ZZZZZ1111Z1Z1',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);

    expect(function () {
        Supplier::create([
            'name' => 'Completely Unique Supplier',
            'phone' => '91111 22223',
            'gst_number' => '33AAAAA0000A1Z5', // duplicate GST
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);
});

test('updating supplier validates unique name and gst against other suppliers but allows keeping own values', function () {
    $supplier1 = Supplier::where('gst_number', '33AAAAA0000A1Z5')->first();
    $supplier2 = Supplier::where('gst_number', '33BBBBB1111B2Z6')->first();

    // Trying to update supplier2 with supplier1's name and GST fails
    $response = $this->putJson("/suppliers/{$supplier2->id}", [
        'name' => $supplier1->name,
        'phone' => '94442 22333',
        'gst_number' => $supplier1->gst_number,
        'status' => 'Active',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'gst_number']);

    // Keeping own name and GST succeeds
    $validResponse = $this->putJson("/suppliers/{$supplier2->id}", [
        'name' => $supplier2->name,
        'phone' => '94442 22999',
        'gst_number' => $supplier2->gst_number,
        'status' => 'Active',
    ]);

    $validResponse->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('storing a supplier with invalid phone format fails validation', function () {
    $response = $this->postJson('/suppliers', [
        'name' => 'Test Supplier',
        'phone' => 'invalid-phone-abc',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['phone']);
});

test('storing a supplier with invalid material price fails validation', function () {
    $response = $this->postJson('/suppliers', [
        'name' => 'Test Supplier',
        'phone' => '98765 43210',
        'materials' => [
            ['material_id' => 1, 'price' => -10],
        ],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['materials.0.price']);
});

test('storing a valid supplier creates record, syncs materials with price and returns 201 json', function () {
    $material1 = Material::find(1) ?? Material::factory()->create();
    $material2 = Material::find(2) ?? Material::factory()->create();

    $payload = [
        'name' => 'Metro Cement Corporation',
        'contact_person' => 'A. Kumar',
        'phone' => '98765 43210',
        'address' => 'Anna Nagar, Chennai',
        'gst_number' => '33ABCDE1234F1Z5',
        'status' => 'Active',
        'materials' => [
            ['material_id' => $material1->id, 'grade' => '1st Quality', 'price' => 425.50],
            ['material_id' => $material2->id, 'grade' => '2nd Quality', 'price' => 8600.00],
        ],
    ];

    $response = $this->postJson('/suppliers', $payload);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Metro Cement Corporation',
                'phone' => '98765 43210',
                'status' => 'Active',
            ],
        ]);

    $supplierId = $response->json('data.id');

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplierId,
        'name' => 'Metro Cement Corporation',
        'phone' => '98765 43210',
    ]);

    $this->assertDatabaseHas('supplier_materials', [
        'supplier_id' => $supplierId,
        'material_id' => $material1->id,
        'grade' => '1st Quality',
        'price' => 425.50,
    ]);

    $this->assertDatabaseHas('supplier_materials', [
        'supplier_id' => $supplierId,
        'material_id' => $material2->id,
        'grade' => '2nd Quality',
        'price' => 8600.00,
    ]);
});

test('show endpoint returns supplier data with materials or 404 if not found', function () {
    $firstSupplier = Supplier::with('materials')->first();

    $response = $this->getJson('/suppliers/'.$firstSupplier->encrypted_id);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $firstSupplier->id,
                'name' => $firstSupplier->name,
            ],
        ]);

    // A non-existent but validly-encrypted ID should 404 (supplier not found)
    $notFoundResponse = $this->getJson('/suppliers/'.IdCipherService::encrypt(99999));
    $notFoundResponse->assertStatus(404)
        ->assertJson([
            'success' => false,
            'message' => 'Supplier not found.',
        ]);

    // An invalid/plain-text segment must also 404
    $invalidResponse = $this->getJson('/suppliers/99999');
    $invalidResponse->assertStatus(404);
});

test('supplier detail page renders successfully with full supplier information, grades and pricing', function () {
    $firstSupplier = Supplier::with('materials')->first();

    $response = $this->get('/suppliers/'.$firstSupplier->encrypted_id);

    $response->assertStatus(200);
    $response->assertSee('Supplier Details');
    $response->assertSee($firstSupplier->name);
    $response->assertSee($firstSupplier->phone);
    $response->assertSee('Materials Supplied');
    $response->assertSee('Back to Suppliers');

    // Non-existent encrypted ID → 404
    $notFoundHtml = $this->get('/suppliers/'.IdCipherService::encrypt(99999));
    $notFoundHtml->assertStatus(404);
});

test('updating a supplier modifies database record and updates material pricing', function () {
    $supplier = Supplier::first();
    $material1 = Material::find(1) ?? Material::factory()->create();

    $updatePayload = [
        'name' => 'Sri Balaji Blue Metals Pvt Ltd',
        'contact_person' => 'Bala Murugan S',
        'phone' => '94441 99999',
        'address' => 'Updated Address, Chennai',
        'gst_number' => '33AAAAA0000A1Z5',
        'status' => 'Active',
        'materials' => [
            ['material_id' => $material1->id, 'grade' => 'Premium', 'price' => 435.00],
        ],
    ];

    $response = $this->putJson("/suppliers/{$supplier->id}", $updatePayload);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $supplier->id,
                'name' => 'Sri Balaji Blue Metals Pvt Ltd',
                'contact_person' => 'Bala Murugan S',
                'phone' => '94441 99999',
            ],
        ]);

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'Sri Balaji Blue Metals Pvt Ltd',
        'phone' => '94441 99999',
    ]);

    $this->assertDatabaseHas('supplier_materials', [
        'supplier_id' => $supplier->id,
        'material_id' => $material1->id,
        'grade' => 'Premium',
        'price' => 435.00,
    ]);
});

test('deleting a supplier removes record and cascades supplier materials from database', function () {
    $supplier = Supplier::factory()->create([
        'name' => 'Temporary Supplier To Delete',
    ]);

    $material = Material::first();
    $supplier->materials()->attach($material->id, ['price' => 500.00]);

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
    ]);

    $this->assertDatabaseHas('supplier_materials', [
        'supplier_id' => $supplier->id,
        'material_id' => $material->id,
    ]);

    $response = $this->deleteJson("/suppliers/{$supplier->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Supplier removed successfully.',
        ]);

    $this->assertDatabaseMissing('suppliers', [
        'id' => $supplier->id,
    ]);

    $this->assertDatabaseMissing('supplier_materials', [
        'supplier_id' => $supplier->id,
    ]);
});

test('suppliers index page renders table pagination elements and controls', function () {
    $response = $this->get('/suppliers');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
