<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);
    $this->actingAs($this->user);
});

test('storing a supplier requires at least one material', function () {
    $response = $this->postJson('/suppliers', [
        'name' => 'No Material Supplier',
        'phone' => '98765 43210',
        'status' => 'Active',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['materials']);

    $emptyArrayResponse = $this->postJson('/suppliers', [
        'name' => 'Empty Material Supplier',
        'phone' => '98765 43210',
        'status' => 'Active',
        'materials' => [],
    ]);

    $emptyArrayResponse->assertStatus(422);
    $emptyArrayResponse->assertJsonValidationErrors(['materials']);
});

test('storing a supplier requires material_id and price for each material set while grade is optional', function () {
    $material1 = Material::find(1) ?? Material::factory()->create();
    $material2 = Material::find(2) ?? Material::factory()->create();

    // Missing material_id in set 0 and missing price in set 1
    $invalidResponse = $this->postJson('/suppliers', [
        'name' => 'Partial Material Supplier',
        'phone' => '98765 43210',
        'status' => 'Active',
        'materials' => [
            ['material_id' => '', 'grade' => '', 'price' => ''],
            ['material_id' => $material1->id, 'grade' => '', 'price' => ''],
        ],
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors([
        'materials.0.material_id',
        'materials.0.price',
        'materials.1.price',
    ]);
    $invalidResponse->assertJsonMissingValidationErrors(['materials.0.grade', 'materials.1.grade']);

    // Grade is optional when material_id and price are provided
    $validResponse = $this->postJson('/suppliers', [
        'name' => 'Valid Optional Grade Supplier',
        'phone' => '98765 43211',
        'status' => 'Active',
        'materials' => [
            ['material_id' => $material1->id, 'grade' => '', 'price' => 450],
            ['material_id' => $material2->id, 'price' => 1200.50],
        ],
    ]);

    $validResponse->assertStatus(201)
        ->assertJson(['success' => true]);
});

test('updating a supplier also requires at least one material with optional grade', function () {
    $supplier = Supplier::first();
    $material = Material::find(1) ?? Material::factory()->create();

    $invalidResponse = $this->postJson("/suppliers/{$supplier->id}/update", [
        'name' => $supplier->name,
        'phone' => $supplier->phone,
        'status' => 'Active',
        'materials' => [],
    ]);

    $invalidResponse->assertStatus(422);
    $invalidResponse->assertJsonValidationErrors(['materials']);

    $validResponse = $this->postJson("/suppliers/{$supplier->id}/update", [
        'name' => $supplier->name,
        'phone' => $supplier->phone,
        'status' => 'Active',
        'materials' => [
            ['material_id' => $material->id, 'grade' => null, 'price' => 500],
        ],
    ]);

    $validResponse->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('supplier used in a purchase order cannot be deleted and shows usage badge on index page', function () {
    $supplier = Supplier::first();
    $client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'status' => 'Active',
    ]);
    $site = ProjectSite::create([
        'client_id' => $client->id,
        'name' => 'Main Site',
        'location' => 'Madurai',
        'work_type' => 'Commercial',
        'start_date' => '2026-10-01',
        'status' => 'In Progress',
    ]);

    PurchaseOrder::create([
        'po_number' => 'PO-TEST-001',
        'order_date' => now()->toDateString(),
        'project_site_id' => $site->id,
        'supplier_id' => $supplier->id,
        'supplier_name' => $supplier->name,
        'source_type' => 'Supplier',
        'status' => 'Pending',
        'delivery_status' => 'Pending',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'total_amount' => 1000,
    ]);

    $indexResponse = $this->get('/suppliers');
    $indexResponse->assertStatus(200);
    $indexResponse->assertSee('1 purchase order');
    $indexResponse->assertSee('In Use');

    $deleteResponse = $this->postJson("/suppliers/{$supplier->id}/delete");
    $deleteResponse->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => "Supplier \"{$supplier->name}\" cannot be deleted because it is currently used in Purchase Orders.",
        ]);

    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
});

test('supplier not used in any purchase order can be deleted', function () {
    $supplier = Supplier::create([
        'name' => 'Unused Test Supplier',
        'phone' => '98765 00001',
        'status' => 'Active',
    ]);

    $deleteResponse = $this->postJson("/suppliers/{$supplier->id}/delete");
    $deleteResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Supplier removed successfully.',
        ]);

    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
});
