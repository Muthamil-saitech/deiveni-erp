<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'status' => 'Active',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Main Site',
        'location' => 'Madurai',
        'work_type' => 'Commercial',
        'start_date' => '2026-10-01',
        'status' => 'In Progress',
    ]);

    $this->supplier = Supplier::create([
        'name' => 'Test Supplier Co',
        'phone' => '9842100000',
        'status' => 'Active',
    ]);

    $this->unit = Unit::firstOrCreate(
        ['code' => 'NOS'],
        [
            'name_en' => 'Numbers',
            'name_ta' => 'எண்கள்',
            'status' => 'Active',
        ]
    );

    $this->material = Material::firstOrCreate(
        ['name_en' => 'Cement Blocks'],
        [
            'name_ta' => 'சிமெண்ட் கற்கள்',
            'unit_id' => $this->unit->id,
            'status' => 'Active',
        ]
    );
});

test('store purchase validation fails with bilingual error messages when empty', function () {
    $response = $this->actingAs($this->user)->postJson('/purchases', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['order_date', 'supplier_name', 'status', 'items']);

    $errors = $response->json('errors');
    expect($errors['order_date'][0])->toContain('Please select a purchase date')->toContain('கொள்முதல் தேதியை தேர்வு செய்க');
    expect($errors['supplier_name'][0])->toContain('Please select a supplier')->toContain('சப்ளையரை தேர்வு செய்க');
    expect($errors['status'][0])->toContain('Please select purchase order status')->toContain('கொள்முதல் நிலை தேர்வு செய்க');
    expect($errors['items'][0])->toContain('At least one material item is required')->toContain('குறைந்தது ஒரு பொருள் தேவை');
});

test('store purchase validates item fields with bilingual error messages', function () {
    $payload = [
        'order_date' => '2026-11-10',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'Ordered',
        'items' => [
            [
                'material_id' => null,
                'quantity' => 0,
                'unit' => '',
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->postJson('/purchases', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['items.0.material_id', 'items.0.quantity', 'items.0.unit']);

    $errors = $response->json('errors');
    expect($errors['items.0.material_id'][0])->toContain('Please select a material')->toContain('பொருளை தேர்வு செய்க');
    expect($errors['items.0.quantity'][0])->toContain('Quantity must be greater than zero')->toContain('அளவு 0-க்கு மேல் இருக்க வேண்டும்');
    expect($errors['items.0.unit'][0])->toContain('Unit of measurement is required')->toContain('அளவீட்டு அலகு அவசியம்');
});

test('status_reason is required when status is Order Return or Order Canceled', function () {
    $payload = [
        'order_date' => '2026-11-10',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'Order Return',
        'status_reason' => '',
        'items' => [
            [
                'material_id' => $this->material->id,
                'quantity' => 10,
                'unit' => 'NOS',
            ],
        ],
    ];

    $response = $this->actingAs($this->user)->postJson('/purchases', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['status_reason']);

    $errors = $response->json('errors');
    expect($errors['status_reason'][0])->toContain('Please enter reason for return or cancellation')->toContain('காரணம் குறிப்பிடவும்');
});

test('update purchase order validation validates unique po_number ignoring current record', function () {
    $order1 = PurchaseOrder::create([
        'po_number' => 'PO-TEST-100',
        'order_date' => '2026-11-01',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'status' => 'Ordered',
        'total_amount' => 500,
        'created_by' => $this->user->id,
    ]);

    $order2 = PurchaseOrder::create([
        'po_number' => 'PO-TEST-200',
        'order_date' => '2026-11-01',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'status' => 'Ordered',
        'total_amount' => 500,
        'created_by' => $this->user->id,
    ]);

    // Updating order2 with its own po_number should succeed
    $responseSame = $this->actingAs($this->user)->putJson("/purchases/{$order2->id}", [
        'po_number' => 'PO-TEST-200',
        'order_date' => '2026-11-02',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'Ordered',
        'items' => [
            [
                'material_id' => $this->material->id,
                'quantity' => 5,
                'unit' => 'NOS',
            ],
        ],
    ]);
    $responseSame->assertStatus(200);

    // Updating order2 with order1's po_number should fail validation with bilingual message
    $responseConflict = $this->actingAs($this->user)->putJson("/purchases/{$order2->id}", [
        'po_number' => 'PO-TEST-100',
        'order_date' => '2026-11-02',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'Ordered',
        'items' => [
            [
                'material_id' => $this->material->id,
                'quantity' => 5,
                'unit' => 'NOS',
            ],
        ],
    ]);
    $responseConflict->assertStatus(422);
    $responseConflict->assertJsonValidationErrors(['po_number']);
    expect($responseConflict->json('errors.po_number.0'))
        ->toContain('This PO / Invoice number already exists')
        ->toContain('இந்த கொள்முதல் ஆணை எண் ஏற்கனவே உள்ளது');
});

test('quick create material saves globally to materials table without supplier restriction', function () {
    $response = $this->actingAs($this->user)->postJson(route('purchases.quick-create-material'), [
        'name_en' => '2-inch PVC Pipe Premium',
        'name_ta' => '2 இன்ச் பிவிசி பைப்',
        'unit' => 'Numbers',
        'supplier_id' => $this->supplier->id,
        'price' => 350.00,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'material' => [
                'name_en' => '2-inch PVC Pipe Premium',
                'name_ta' => '2 இன்ச் பிவிசி பைப்',
                'price' => 350.00,
            ],
        ]);

    $this->assertDatabaseHas('materials', [
        'name_en' => '2-inch PVC Pipe Premium',
        'status' => 'Active',
    ]);

    $this->assertDatabaseMissing('supplier_materials', [
        'supplier_id' => $this->supplier->id,
        'material_id' => $response->json('material.id'),
    ]);
});

test('store purchase auto-creates material in Material Master when new material name is provided', function () {
    $response = $this->actingAs($this->user)->postJson('/purchases', [
        'po_number' => 'PO-AUTO-MAT-001',
        'order_date' => '2026-11-20',
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'Ordered',
        'items' => [
            [
                'material_name' => 'Brand New Auto Created Material',
                'quantity' => 10,
                'unit' => 'NOS',
                'unit_price' => 120.00,
            ],
        ],
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('materials', [
        'name_en' => 'Brand New Auto Created Material',
        'status' => 'Active',
    ]);
});
