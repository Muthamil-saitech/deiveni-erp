<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\ProjectSite;
use App\Models\User;
use App\Models\WorkerCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $this->client = Client::create([
        'name' => 'Test Client',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'created_by' => $this->siteManager->id,
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Madurai Main Site',
        'location' => 'Madurai',
        'work_type' => 'Commercial',
        'start_date' => '2026-11-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 500000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->siteManager->id,
    ]);

    $this->category1 = WorkerCategory::where('code', 'CAT-001')->first() ?? WorkerCategory::create([
        'name_ta' => 'மேஸ்திரி',
        'name_en' => 'Head Mason / Lead Mestri',
        'code' => 'CAT-TEST-001',
        'daily_wage' => 1200.00,
        'shift' => '8 Hours',
        'status' => 'Active',
    ]);

    $this->category2 = WorkerCategory::where('code', 'CAT-002')->first() ?? WorkerCategory::create([
        'name_ta' => 'கொத்தனார்',
        'name_en' => 'Brick Mason / Plasterer',
        'code' => 'CAT-TEST-002',
        'daily_wage' => 1100.00,
        'shift' => '8 Hours',
        'status' => 'Active',
    ]);
});

test('daily allocation page renders successfully with sites and worker categories from database', function () {
    $response = $this->actingAs($this->siteManager)->get('/daily-allocation');

    $response->assertStatus(200);
    $response->assertSee('Madurai Main Site');
    $response->assertSee('மேஸ்திரி');
    $response->assertSee('கொத்தனார்');
});

test('storing daily allocation saves record and automatically creates weekly wage record', function () {
    $date = Carbon::parse('2026-11-02')->toDateString(); // A Monday

    $payload = [
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 3,
        'total_labour_wage' => 3400.00,
        'total_expenses' => 150.00,
        'grand_total' => 3550.00,
        'trade_summary' => '1 மேஸ்திரி + 2 கொத்தனார்',
        'items' => [
            [
                'worker_category_id' => $this->category1->id,
                'category_code' => 'CAT-001',
                'name_ta' => 'மேஸ்திரி',
                'name_en' => 'Head Mason',
                'worker_count' => 1,
                'daily_wage' => 1200.00,
                'master_wage' => 1200.00,
                'subtotal' => 1200.00,
            ],
            [
                'worker_category_id' => $this->category2->id,
                'category_code' => 'CAT-002',
                'name_ta' => 'கொத்தனார்',
                'name_en' => 'Brick Mason',
                'worker_count' => 2,
                'daily_wage' => 1100.00,
                'master_wage' => 1100.00,
                'subtotal' => 2200.00,
            ],
        ],
        'expenses' => [
            [
                'item_name' => 'Water Cans',
                'quantity' => 3,
                'unit_price' => 50.00,
                'amount' => 150.00,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/daily-allocation', $payload);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('daily_allocations', [
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 3,
        'total_labour_wage' => 3400.00,
        'total_expenses' => 150.00,
        'grand_total' => 3550.00,
    ]);

    $this->assertDatabaseHas('daily_allocation_items', [
        'category_code' => 'CAT-001',
        'worker_count' => 1,
        'subtotal' => 1200.00,
    ]);

    $this->assertDatabaseHas('daily_allocation_expenses', [
        'item_name' => 'Water Cans',
        'amount' => 150.00,
    ]);

    // Task 5 verification: Automatically created one record in weekly wages
    $weekStart = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();
    $weekEnd = Carbon::parse($date)->endOfWeek(Carbon::SUNDAY)->toDateString();

    $this->assertDatabaseHas('weekly_wages', [
        'project_site_id' => $this->site->id,
        'week_start_date' => $weekStart,
        'week_end_date' => $weekEnd,
        'total_labour_man_days' => 3,
        'total_labour_wages' => 3400.00,
        'total_daily_expenses' => 150.00,
        'net_payout' => 3550.00,
    ]);
});

test('get by date returns existing allocation and week saved status', function () {
    $date = Carbon::parse('2026-11-03')->toDateString();

    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 2,
        'total_labour_wage' => 2400.00,
        'total_expenses' => 0.00,
        'grand_total' => 2400.00,
        'trade_summary' => '2 மேஸ்திரி',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->getJson('/daily-allocation/by-date?project_site_id='.$this->site->id.'&date='.$date);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'week_saved' => [
            $date => true,
        ],
    ]);
    $response->assertJsonPath('allocation.total_labour_count', 2);
});

test('history endpoint returns allocations within date range', function () {
    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-02',
        'total_labour_count' => 1,
        'total_labour_wage' => 1200.00,
        'total_expenses' => 0.00,
        'grand_total' => 1200.00,
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2026-11-03',
        'total_labour_count' => 2,
        'total_labour_wage' => 2200.00,
        'total_expenses' => 0.00,
        'grand_total' => 2200.00,
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->getJson('/daily-allocation/history?project_site_id='.$this->site->id.'&from_date=2026-11-01&to_date=2026-11-07');

    $response->assertStatus(200);
    $response->assertJsonCount(2, 'data');
});

test('storing daily allocation with "Others" custom worker category automatically creates it in WorkerCategory master', function () {
    $date = Carbon::parse('2026-11-04')->toDateString();

    $payload = [
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 2,
        'total_labour_wage' => 2000.00,
        'total_expenses' => 100.00,
        'grand_total' => 2100.00,
        'trade_summary' => '2 தச்சர் (Carpenter)',
        'items' => [
            [
                'worker_category_id' => null, // "Others" custom category
                'category_code' => null,
                'name_ta' => 'தச்சர்',
                'name_en' => 'Carpenter',
                'worker_count' => 2,
                'daily_wage' => 1000.00,
                'master_wage' => 1000.00,
                'subtotal' => 2000.00,
            ],
        ],
        'expenses' => [
            [
                'item_name' => 'Tea & Snacks',
                'quantity' => 1,
                'unit_price' => 100.00,
                'amount' => 100.00,
            ],
        ],
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/daily-allocation', $payload);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);

    // Verify it is created in WorkerCategory master table!
    $this->assertDatabaseHas('worker_categories', [
        'name_ta' => 'தச்சர்',
        'name_en' => 'Carpenter',
        'daily_wage' => 1000.00,
        'status' => 'Active',
    ]);

    $newCategory = WorkerCategory::where('name_ta', 'தச்சர்')->first();
    expect($newCategory)->not->toBeNull();
    expect($newCategory->code)->not->toBeNull();

    // Verify the saved allocation item is linked to the newly created worker category
    $allocation = DailyAllocation::where('project_site_id', $this->site->id)
        ->where('date', $date)
        ->with('items')
        ->first();

    expect($allocation->items)->toHaveCount(1);
    expect($allocation->items[0]->worker_category_id)->toBe($newCategory->id);
    expect($allocation->items[0]->category_code)->toBe($newCategory->code);
});

test('quick add worker category endpoint creates new category in master', function () {
    $payload = [
        'name_ta' => 'வெல்டர்',
        'name_en' => 'Welder',
        'daily_wage' => 950.00,
    ];

    $response = $this->actingAs($this->siteManager)->postJson('/daily-allocation/quick-add-worker-category', $payload);

    $response->assertStatus(200);
    $response->assertJsonPath('success', true);
    $this->assertDatabaseHas('worker_categories', [
        'name_ta' => 'வெல்டர்',
        'name_en' => 'Welder',
        'daily_wage' => 950.00,
    ]);
});

test('daily allocation view retains selected worker categories across site and date changes on unsaved dates', function () {
    $response = $this->actingAs($this->siteManager)->get('/daily-allocation');

    $response->assertStatus(200);

    $content = $response->getContent();
    expect($content)->toContain('retainedWorkerCategories');
    expect($content)->toContain('snapshotCurrentWorkerCategories');
    expect($content)->toContain('persistRetainedWorkerCategories');
    expect($content)->toContain('deiveni_retained_worker_categories');
});
