<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\TemporaryBill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);

    $this->client = Client::create([
        'name' => 'R. Karthikeyan',
        'phone' => '9840011223',
        'address' => 'Adyar, Chennai',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Adyar Residence',
        'location' => 'Adyar, Chennai',
        'work_type' => 'New Build',
        'start_date' => '2026-11-01',
        'end_date' => '2027-05-01',
        'estimated_budget' => 3000000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);
});

test('temporary billing index and create pages render successfully', function () {
    $indexResponse = $this->actingAs($this->siteManager)->get('/temporary-billing');
    $indexResponse->assertStatus(200);
    $indexResponse->assertSee('Temporary Billing');
    $indexResponse->assertSee('Create Temporary Bill');

    $createResponse = $this->actingAs($this->siteManager)->get('/temporary-billing/create');
    $createResponse->assertStatus(200);
    $createResponse->assertSee('Date-Wise Painter &amp; Electrician Payments', false);
});

test('site payments endpoint returns date-wise painter and electrician payments and excludes other trades', function () {
    $alloc1 = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-20',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc1->id,
        'category_code' => 'CAT-007',
        'name_en' => 'Painter',
        'name_ta' => 'பெயிண்டர்',
        'worker_count' => 3,
        'daily_wage' => 1100.00,
        'subtotal' => 3300.00,
        'paid_by' => 'Site Manager',
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc1->id,
        'category_code' => 'CAT-001',
        'name_en' => 'Mason',
        'name_ta' => 'கொத்தனார்',
        'worker_count' => 5,
        'daily_wage' => 1200.00,
        'subtotal' => 6000.00,
        'paid_by' => 'Site Manager',
    ]);

    $alloc2 = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-22',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc2->id,
        'category_code' => 'CAT-008',
        'name_en' => 'Electrician',
        'name_ta' => 'எலக்ட்ரீஷியன்',
        'worker_count' => 2,
        'daily_wage' => 1300.00,
        'subtotal' => 2600.00,
        'paid_by' => 'Account Manager',
    ]);

    $response = $this->actingAs($this->siteManager)
        ->getJson("/temporary-billing/site-payments?project_site_id={$this->site->id}&trade_filter=all");

    $response->assertStatus(200);
    $items = collect($response->json('items'));
    expect($items)->toHaveCount(2);
    expect($items[0]['date'])->toBe('2026-11-20');
    expect($items[0]['trade'])->toBe('Painter');
    expect($items[1]['date'])->toBe('2026-11-22');
    expect($items[1]['trade'])->toBe('Electrician');
    expect($response->json('total_amount'))->toEqual(5900.00);
});

test('storing temporary bill with edited payment saves record and allows reprint and delete from table', function () {
    $storeRes = $this->actingAs($this->siteManager)->postJson('/temporary-billing', [
        'project_site_id' => $this->site->id,
        'bill_date' => '2026-11-25',
        'title' => 'Painter & Electrician Weekly Temp Bill',
        'trade_filter' => 'all',
        'notes' => 'Temporary voucher for site verification',
        'items' => [
            [
                'date' => '2026-11-20',
                'trade' => 'Painter',
                'description' => 'Painter (3 workers)',
                'worker_count' => 3,
                'daily_wage' => 1100.00,
                'paid_by' => 'Site Manager',
                'original_amount' => 3300.00,
                'billed_amount' => 3500.00, // edited amount
            ],
            [
                'date' => '2026-11-22',
                'trade' => 'Electrician',
                'description' => 'Electrician (2 workers)',
                'worker_count' => 2,
                'daily_wage' => 1300.00,
                'paid_by' => 'Account Manager',
                'original_amount' => 2600.00,
                'billed_amount' => 2500.00, // edited amount
            ],
        ],
    ]);

    $storeRes->assertStatus(201)
        ->assertJson(['success' => true]);

    $bill = TemporaryBill::first();
    expect($bill)->not->toBeNull();
    expect($bill->bill_number)->toBe('TB-2026-001');
    expect((float) $bill->total_amount)->toBe(6000.00);

    // Table shows saved bill with Reprint button
    $indexRes = $this->actingAs($this->siteManager)->get('/temporary-billing');
    $indexRes->assertStatus(200);
    $indexRes->assertSee('TB-2026-001');
    $indexRes->assertSee('6,000.00');
    $indexRes->assertSee('Reprint');

    // Print / Reprint page renders cleanly
    $printRes = $this->actingAs($this->siteManager)->get("/temporary-billing/{$bill->encrypted_id}/print");
    $printRes->assertStatus(200);
    $printRes->assertSee('TEMPORARY BILL');
    $printRes->assertSee('TB-2026-001');
    $printRes->assertSee('Adyar Residence');
    $printRes->assertSee('6,000.00');

    // Update bill amount
    $updateRes = $this->actingAs($this->siteManager)->postJson("/temporary-billing/{$bill->encrypted_id}/update", [
        'project_site_id' => $this->site->id,
        'bill_date' => '2026-11-25',
        'title' => 'Updated Temp Bill',
        'trade_filter' => 'painter',
        'items' => [
            [
                'date' => '2026-11-20',
                'trade' => 'Painter',
                'description' => 'Painter (3 workers)',
                'worker_count' => 3,
                'daily_wage' => 1100.00,
                'original_amount' => 3300.00,
                'billed_amount' => 4000.00,
            ],
        ],
    ]);
    $updateRes->assertStatus(200);
    expect((float) $bill->fresh()->total_amount)->toBe(4000.00);

    // Delete bill
    $delRes = $this->actingAs($this->siteManager)->postJson("/temporary-billing/{$bill->encrypted_id}/delete");
    $delRes->assertStatus(200);
    expect(TemporaryBill::find($bill->id))->toBeNull();
});

test('temporary billing strictly scopes sites by user and returns empty when site has no painter or electrician', function () {
    $otherSm = User::factory()->create([
        'role' => UserRole::SiteManager,
        'status' => 'Active',
    ]);

    $otherSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Other SM Site',
        'location' => 'Guindy, Chennai',
        'work_type' => 'New Build',
        'start_date' => '2026-11-01',
        'end_date' => '2027-05-01',
        'estimated_budget' => 1000000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $otherSm->id,
    ]);

    // Site Manager 1 does NOT see Other SM Site in create dropdown
    $createRes = $this->actingAs($this->siteManager)->get('/temporary-billing/create');
    $createRes->assertStatus(200);
    $createRes->assertSee('Adyar Residence');
    $createRes->assertDontSee('Other SM Site');

    // Site Manager 1 cannot fetch site payments for Other SM Site (403)
    $forbiddenRes = $this->actingAs($this->siteManager)
        ->getJson("/temporary-billing/site-payments?project_site_id={$otherSite->id}&trade_filter=all");
    $forbiddenRes->assertStatus(403);

    // Site with ONLY Mason (no Painter/Electrician) returns empty items
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-24',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-001',
        'name_en' => 'Mason',
        'name_ta' => 'கொத்தனார்',
        'worker_count' => 4,
        'daily_wage' => 1200.00,
        'subtotal' => 4800.00,
        'paid_by' => 'Site Manager',
    ]);

    $emptyRes = $this->actingAs($this->siteManager)
        ->getJson("/temporary-billing/site-payments?project_site_id={$this->site->id}&trade_filter=all");
    $emptyRes->assertStatus(200);
    expect($emptyRes->json('items'))->toBe([]);
    expect($emptyRes->json('total_amount'))->toEqual(0.0);
});
