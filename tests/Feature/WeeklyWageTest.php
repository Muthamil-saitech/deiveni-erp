<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationExpense;
use App\Models\DailyAllocationItem;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\Unit;
use App\Models\User;
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
});

test('weekly wages page renders successfully with sites from database', function () {
    $response = $this->actingAs($this->siteManager)->get('/weekly-wages');

    $response->assertStatus(200);
    $response->assertSee('Madurai Main Site');
    $response->assertSee('Weekly Wages Sheet', false);
});

test('weekly wages data endpoint returns synced daily allocations for the week', function () {
    $monday = '2026-11-02';
    $sunday = '2026-11-08';

    // Daily allocation on Monday
    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 5,
        'total_labour_wage' => 5500.00,
        'total_expenses' => 200.00,
        'grand_total' => 5700.00,
        'trade_summary' => '5 Workers',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->getJson('/weekly-wages/data?project_site_id='.$this->site->id.'&start_date='.$monday.'&end_date='.$sunday);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'summary' => [
                'total_man_days' => 5,
                'total_labour_wages' => 5500,
                'total_daily_expenses' => 200,
                'net_payout' => 5700,
            ],
        ],
    ]);
    $response->assertJsonCount(7, 'data.days');
});

test('saving additional weekly site expenses updates total weekly expenses and net payout', function () {
    $monday = '2026-11-02';
    $sunday = '2026-11-08';

    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 4,
        'total_labour_wage' => 4000.00,
        'total_expenses' => 100.00,
        'grand_total' => 4100.00,
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->postJson('/weekly-wages/expenses', [
        'project_site_id' => $this->site->id,
        'start_date' => $monday,
        'end_date' => $sunday,
        'expenses' => [
            [
                'item_name' => 'Mixer Machine Rent',
                'quantity' => 1,
                'unit_price' => 1500.00,
                'amount' => 1500.00,
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('weekly_wages', [
        'project_site_id' => $this->site->id,
        'week_start_date' => $monday,
        'week_end_date' => $sunday,
        'total_weekly_expenses' => 1500.00,
        'net_payout' => 5600.00, // 4000 + 100 + 1500
    ]);

    $this->assertDatabaseHas('weekly_wage_expenses', [
        'item_name' => 'Mixer Machine Rent',
        'amount' => 1500.00,
    ]);
});

test('settling weekly wage recalculates and saves settlement record', function () {
    $monday = '2026-11-02';
    $sunday = '2026-11-08';

    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 4,
        'total_labour_wage' => 4000.00,
        'total_expenses' => 100.00,
        'grand_total' => 4100.00,
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->postJson('/weekly-wages/settle', [
        'project_site_id' => $this->site->id,
        'start_date' => $monday,
        'end_date' => $sunday,
        'expenses' => [
            [
                'item_name' => 'Supervisor Weekly Allowance',
                'quantity' => 1,
                'unit_price' => 1000.00,
                'amount' => 1000.00,
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('weekly_wages', [
        'project_site_id' => $this->site->id,
        'week_start_date' => $monday,
        'week_end_date' => $sunday,
        'total_weekly_expenses' => 1000.00,
        'net_payout' => 5100.00, // 4000 + 100 + 1000
    ]);
});

test('weekly wages data returns dynamic categories including extra trades like painter', function () {
    $monday = '2026-11-02';
    $sunday = '2026-11-08';

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 6,
        'total_labour_wage' => 6700.00,
        'total_expenses' => 0.00,
        'grand_total' => 6700.00,
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-001',
        'name_ta' => 'கொத்தனார்',
        'name_en' => 'Mason',
        'daily_wage' => 1200.00,
        'master_wage' => 1200.00,
        'worker_count' => 4,
        'subtotal' => 4800.00,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-PAINT',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'daily_wage' => 950.00,
        'master_wage' => 950.00,
        'worker_count' => 2,
        'subtotal' => 1900.00,
    ]);

    $response = $this->actingAs($this->siteManager)->getJson('/weekly-wages/data?project_site_id='.$this->site->id.'&start_date='.$monday.'&end_date='.$sunday);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'categories',
            'days',
            'summary',
        ],
    ]);

    $categories = collect($response->json('data.categories'));
    expect($categories->firstWhere('name_ta', 'கொத்தனார்'))->toMatchArray([
        'name_ta' => 'கொத்தனார்',
        'total_workers' => 4,
        'rate' => 1200.0,
        'total_amount' => 4800.0,
    ]);
    expect($categories->firstWhere('name_ta', 'பெயிண்டர்'))->toMatchArray([
        'name_ta' => 'பெயிண்டர்',
        'total_workers' => 2,
        'rate' => 950.0,
        'total_amount' => 1900.0,
    ]);
});

test('weekly wages data endpoint correctly loads allocations for previous year custom date', function () {
    $pastMonday = '2025-11-10';
    $pastSunday = '2025-11-16';

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => '2025-11-10',
        'total_labour_count' => 8,
        'total_labour_wage' => 8000.00,
        'total_expenses' => 500.00,
        'grand_total' => 8500.00,
        'trade_summary' => '8 Workers',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationExpense::create([
        'daily_allocation_id' => $alloc->id,
        'item_name' => 'Construction String - சரக்கயிறு',
        'quantity' => 5.0,
        'unit_price' => 100.0,
        'amount' => 500.0,
    ]);

    $response = $this->actingAs($this->siteManager)->getJson('/weekly-wages/data?project_site_id='.$this->site->id.'&start_date='.$pastMonday.'&end_date='.$pastSunday);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'summary' => [
                'total_man_days' => 8,
                'total_labour_wages' => 8000,
                'total_daily_expenses' => 500,
                'net_payout' => 8500,
            ],
            'daily_expenses' => [
                [
                    'title' => 'Construction String - சரக்கயிறு',
                    'qty' => 5,
                    'unitPrice' => 100,
                    'cost' => 500,
                    'is_daily_sync' => true,
                ],
            ],
        ],
    ]);
    $response->assertJsonCount(7, 'data.days');
    $dayAllocations = collect($response->json('data.days'));
    expect($dayAllocations->firstWhere('date', '2025-11-10')['has_data'])->toBeTrue();
    expect($dayAllocations->firstWhere('date', '2025-11-11')['has_data'])->toBeFalse();
});

test('weekly wage can be settled and updated with extra expenses on any day', function () {
    $monday = now()->startOfWeek(Carbon::MONDAY)->toDateString();
    $sunday = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 6,
        'total_labour_wage' => 6800.00,
        'total_expenses' => 6100.00,
        'grand_total' => 12900.00,
        'created_by' => $this->siteManager->id,
    ]);

    $unit = Unit::where('code', 'NOS')->first() ?? Unit::first();

    $response = $this->actingAs($this->siteManager)->postJson('/weekly-wages/settle', [
        'project_site_id' => $this->site->id,
        'start_date' => $monday,
        'end_date' => $sunday,
        'expenses' => [
            [
                'item_name' => 'Cable - மின்சார கேபிள்',
                'unit_id' => $unit?->id,
                'quantity' => 1,
                'unit_price' => 150.00,
                'amount' => 150.00,
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('weekly_wages', [
        'project_site_id' => $this->site->id,
        'week_start_date' => $monday,
        'week_end_date' => $sunday,
        'total_weekly_expenses' => 150.00,
        'net_payout' => 13050.00,
        'status' => 'settled',
    ]);
});

test('saving weekly expenses with new material and unit stores it in materials master as type Other', function () {
    $unit = Unit::where('code', 'LTR')->first() ?? Unit::create([
        'name_en' => 'Test Litres',
        'name_ta' => 'சோதனை லிட்டர்',
        'code' => 'TLTR',
        'status' => 'Active',
    ]);

    $monday = '2026-11-02';
    $sunday = '2026-11-08';

    $response = $this->actingAs($this->siteManager)->postJson('/weekly-wages/expenses', [
        'project_site_id' => $this->site->id,
        'start_date' => $monday,
        'end_date' => $sunday,
        'expenses' => [
            [
                'item_name' => 'Generator Diesel - ஜெனரேட்டர் டீசல்',
                'unit_id' => $unit->id,
                'quantity' => 10,
                'unit_price' => 95.00,
                'amount' => 950.00,
            ],
        ],
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $this->assertDatabaseHas('materials', [
        'name_en' => 'Generator Diesel',
        'name_ta' => 'ஜெனரேட்டர் டீசல்',
        'unit_id' => $unit->id,
        'type' => Material::TYPE_OTHER,
        'status' => 'Active',
    ]);
});

test('approving weekly wage automatically adds site cash expense deducting AM office cash but not AM project cash', function () {
    $monday = '2026-10-12';
    $sunday = '2026-10-18';

    // Total labour = 6000 (2000 SM unpaid + 1500 AM office_cash + 2500 AM project_cash) + 500 daily expense = 6500
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 6,
        'total_labour_wage' => 6000.00,
        'total_expenses' => 500.00,
        'grand_total' => 6500.00,
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'name_ta' => 'கொத்தனார்',
        'name_en' => 'Mason',
        'worker_count' => 2,
        'daily_wage' => 1000.00,
        'master_wage' => 1000.00,
        'subtotal' => 2000.00,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'worker_count' => 1,
        'daily_wage' => 1500.00,
        'master_wage' => 1500.00,
        'subtotal' => 1500.00,
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'office_cash',
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'name_ta' => 'எலக்ட்ரீஷியன்',
        'name_en' => 'Electrician',
        'worker_count' => 2,
        'daily_wage' => 1250.00,
        'master_wage' => 1250.00,
        'subtotal' => 2500.00,
        'paid_by' => 'Account Manager',
        'wage_cash_source' => 'project_cash',
    ]);

    $response = $this->actingAs($this->siteManager)->postJson('/weekly-wages/settle', [
        'project_site_id' => $this->site->id,
        'start_date' => $monday,
        'end_date' => $sunday,
        'expenses' => [],
    ]);

    $response->assertStatus(200);

    // Expected Site Cash Weekly Settlement = 6500 - 1500 (AM office_cash) = 5000 (AM project_cash 2500 NOT deducted)
    $this->assertDatabaseHas('site_cashes', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Weekly Settlement',
        'flow_type' => 'outflow',
        'is_expense' => true,
        'amount' => 5000.00,
        'out_amount' => 5000.00,
        'bill_no' => "WEEKLY-{$monday}",
    ]);

    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(5000.00);
});
