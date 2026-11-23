<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\Contracts\MaterialUsageServiceInterface;
use App\Services\Contracts\StockTransferServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ownerRole = Role::where('slug', 'owner')->first()
        ?? Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner', 'is_system' => true]);

    $this->user = User::factory()->create([
        'role' => UserRole::Owner,
        'role_id' => $this->ownerRole->id,
    ]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'created_by' => $this->user->id,
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Lake View Luxury Villa',
        'location' => 'Othakadai, Madurai',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->user->id,
    ]);

    $this->supplier = Supplier::where('name', 'Chettinad Cement Agency')->first()
        ?? Supplier::create([
            'name' => 'Chettinad Cement Agency',
            'phone' => '9876543211',
            'status' => 'Active',
        ]);

    $this->unit = Unit::firstOrCreate(
        ['name_en' => 'Bags'],
        ['name_ta' => 'மூட்டை', 'code' => 'BAG', 'status' => 'Active']
    );

    $this->material = Material::firstOrCreate(
        ['name_en' => 'Cement'],
        ['name_ta' => 'சிமெண்ட்', 'unit_id' => $this->unit->id, 'status' => 'Active']
    );
});

test('new purchase order defaults to Ordered status', function () {
    $response = $this->actingAs($this->user)->post('/purchases', [
        'po_number' => 'PO-2026-TEST-999',
        'order_date' => Carbon::now()->toDateString(),
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'status' => 'Ordered',
        'items' => [
            [
                'material_id' => $this->material->id,
                'material_name' => $this->material->name_en,
                'quantity' => 100,
                'unit' => 'Bags',
                'unit_price' => 410,
                'total_price' => 41000,
            ],
        ],
        'total_amount' => 41000,
    ]);

    $response->assertStatus(302);

    $po = PurchaseOrder::where('po_number', 'PO-2026-TEST-999')->first();
    expect($po)->not->toBeNull();
    expect($po->status)->toBe('Ordered');
});

test('purchase order in Ordered status is not accessible in other pages until confirmed as Order Received', function () {
    $po = PurchaseOrder::create([
        'po_number' => 'PO-2026-ORDERED-01',
        'order_date' => Carbon::now()->toDateString(),
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'status' => 'Ordered',
        'total_amount' => 41000,
        'created_by' => $this->user->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'material_id' => $this->material->id,
        'material_name' => 'Cement',
        'quantity' => 100,
        'unit' => 'Bags',
        'unit_price' => 410,
        'total_price' => 41000,
    ]);

    // 1. Site Cash purchase orders endpoint should NOT include the 'Ordered' PO
    $siteCashRes = $this->actingAs($this->user)->getJson('/site-cash/purchase-orders?project_site_id='.$this->site->id);
    $siteCashRes->assertStatus(200);
    $poNumbers = collect($siteCashRes->json('orders'))->pluck('po_number')->all();
    expect($poNumbers)->not->toContain('PO-2026-ORDERED-01');

    // 2. Material Usage service active POs should NOT include the 'Ordered' PO
    $muService = app(MaterialUsageServiceInterface::class);
    $activePOs = $muService->getActivePurchaseOrders(['site_id' => $this->site->id]);
    $activePoNumbers = collect($activePOs)->pluck('po_number')->all();
    expect($activePoNumbers)->not->toContain('PO-2026-ORDERED-01');

    // 3. Material Usage site materials should NOT include materials from 'Ordered' PO
    $siteMaterials = $muService->getSiteMaterialsForUsage($this->site->id);
    $matPoNumbers = collect($siteMaterials['items'] ?? [])->pluck('po_number')->all();
    expect($matPoNumbers)->not->toContain('PO-2026-ORDERED-01');

    // 4. Stock Transfer site available materials should NOT include materials from 'Ordered' PO
    $xferService = app(StockTransferServiceInterface::class);
    $xferMaterials = $xferService->getAvailableMaterialsForSite($this->site->id);
    $xferReferences = collect($xferMaterials)->pluck('reference_no')->all();
    expect($xferReferences)->not->toContain('PO-2026-ORDERED-01');

    // 5. Now update status to 'Order Received' (Confirm the PO)
    $updateStatusRes = $this->actingAs($this->user)->postJson("/purchases/{$po->id}/status", [
        'status' => 'Order Received',
    ]);
    $updateStatusRes->assertStatus(200);
    $updateStatusRes->assertJson([
        'success' => true,
        'purchase' => [
            'id' => $po->id,
            'status' => 'Order Received',
        ],
    ]);

    $po->refresh();
    expect($po->status)->toBe('Order Received');

    // 6. NOW the PO should be usable in all related pages:
    // In Site Cash
    $siteCashResAfter = $this->actingAs($this->user)->getJson('/site-cash/purchase-orders?project_site_id='.$this->site->id);
    $siteCashResAfter->assertStatus(200);
    $poNumbersAfter = collect($siteCashResAfter->json('orders'))->pluck('po_number')->all();
    expect($poNumbersAfter)->toContain('PO-2026-ORDERED-01');

    // In Material Usage active POs
    $activePOsAfter = $muService->getActivePurchaseOrders(['site_id' => $this->site->id]);
    $activePoNumbersAfter = collect($activePOsAfter)->pluck('po_number')->all();
    expect($activePoNumbersAfter)->toContain('PO-2026-ORDERED-01');

    // In Material Usage site materials
    $siteMaterialsAfter = $muService->getSiteMaterialsForUsage($this->site->id);
    $matPoNumbersAfter = collect($siteMaterialsAfter['items'] ?? [])->pluck('po_number')->all();
    expect($matPoNumbersAfter)->toContain('PO-2026-ORDERED-01');

    // In Stock Transfer available materials
    $xferMaterialsAfter = $xferService->getAvailableMaterialsForSite($this->site->id);
    $xferReferencesAfter = collect($xferMaterialsAfter)->pluck('reference_no')->all();
    expect($xferReferencesAfter)->toContain('PO-2026-ORDERED-01');
});

test('purchases index view displays interactive status dropdown with Order Received option', function () {
    $po = PurchaseOrder::create([
        'po_number' => 'PO-2026-001',
        'order_date' => Carbon::now()->toDateString(),
        'project_site_id' => $this->site->id,
        'supplier_id' => $this->supplier->id,
        'supplier_name' => $this->supplier->name,
        'status' => 'Ordered',
        'total_amount' => 41000,
        'created_by' => $this->user->id,
    ]);

    PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'material_id' => $this->material->id,
        'material_name' => 'Cement',
        'quantity' => 100,
        'unit' => 'Bags',
        'unit_price' => 410,
        'total_price' => 41000,
    ]);

    $response = $this->actingAs($this->user)->get('/purchases');
    $response->assertStatus(200);
    $response->assertSee('po-status-dropdown');
    $response->assertSee('po-change-status-btn');
    $response->assertSee('Order Received');
    $response->assertSee('Confirm as Order Received');
});
