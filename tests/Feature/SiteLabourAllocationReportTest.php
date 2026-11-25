<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationExpense;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\User;
use App\Models\WeeklyWage;
use App\Models\WeeklyWageExpense;
use App\Models\WorkerCategory;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::firstOrCreate(
        ['email' => 'owner@deiveni.com'],
        [
            'name' => 'K. Deiveni',
            'role' => UserRole::Owner,
            'phone' => '98400 11111',
            'status' => 'Active',
            'password' => Hash::make('password123'),
        ]
    );

    $this->siteManager = User::firstOrCreate(
        ['email' => 'sm@deiveni.com'],
        [
            'name' => 'Murugan SM',
            'role' => UserRole::SiteManager,
            'phone' => '98400 22222',
            'status' => 'Active',
            'password' => Hash::make('password123'),
        ]
    );

    $this->client = Client::create([
        'name' => 'Mr. Shanmugam',
        'phone' => '9876543210',
        'address' => 'Madurai',
        'status' => 'Active',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Koodal Nagar Site',
        'location' => 'Madurai',
        'work_type' => 'Residential Villa',
        'start_date' => '2026-10-01',
        'end_date' => '2027-04-30',
        'estimated_budget' => 5000000,
        'status' => 'In Progress',
    ]);

    $this->category = WorkerCategory::firstOrCreate(
        ['code' => 'CAT-001'],
        [
            'name_ta' => 'மேஸ்திரி',
            'name_en' => 'Lead Mestri',
            'daily_wage' => 1200,
            'status' => 'Active',
        ]
    );
});

test('task 1 & 3: site labour allocation report renders dynamically via new controller and service', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');

    $response->assertStatus(200);
    $response->assertViewIs('reports.site_labour_allocations');
    $response->assertViewHas(['sites', 'allocations', 'weeklyWages', 'kpis', 'filterMeta']);
    $response->assertDontSee('localStorage.getItem', false);
    $response->assertDontSee('Uploaded Client Period', false);
});

test('task 2: report fetches and displays daily allocations and weekly wages data from database', function () {
    $today = Carbon::today()->toDateString();
    $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $today,
        'total_labour_count' => 5,
        'total_labour_wage' => 6000,
        'total_expenses' => 150,
        'grand_total' => 6150,
        'trade_summary' => '5 Lead Mestri',
        'created_by' => $this->siteManager->id,
    ]);

    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'worker_category_id' => $this->category->id,
        'category_code' => 'CAT-001',
        'name_ta' => 'மேஸ்திரி',
        'name_en' => 'Lead Mestri',
        'daily_wage' => 1200,
        'master_wage' => 1200,
        'worker_count' => 5,
        'subtotal' => 6000,
    ]);

    DailyAllocationExpense::create([
        'daily_allocation_id' => $alloc->id,
        'item_name' => 'Tea & Water',
        'quantity' => 1,
        'unit_price' => 150,
        'amount' => 150,
    ]);

    $weeklyWage = WeeklyWage::create([
        'project_site_id' => $this->site->id,
        'week_start_date' => $weekStart,
        'week_end_date' => $weekEnd,
        'total_labour_man_days' => 5,
        'total_labour_wages' => 6000,
        'total_daily_expenses' => 150,
        'total_weekly_expenses' => 200,
        'net_payout' => 6350,
    ]);

    WeeklyWageExpense::create([
        'weekly_wage_id' => $weeklyWage->id,
        'item_name' => 'Site Scaffolding Rent',
        'quantity' => 1,
        'unit_price' => 200,
        'amount' => 200,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');

    $response->assertStatus(200);
    $response->assertSee('Koodal Nagar Site', false);
    $response->assertSee('5 Lead Mestri', false);
    $response->assertSee('smWeeklyWagesTable', false);
    $response->assertSee('smAllocationsTable', false);
});

test('task 4: filter options has correct order: today, this week, last week with date range, this month and custom', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');

    $response->assertStatus(200);

    $content = $response->getContent();

    $todayPos = strpos($content, 'value="today"');
    $thisWeekPos = strpos($content, 'value="this_week"');
    $lastWeekPos = strpos($content, 'value="last_week"');
    $thisMonthPos = strpos($content, 'value="this_month"');
    $customPos = strpos($content, 'value="custom"');

    expect($todayPos)->toBeLessThan($thisWeekPos);
    expect($thisWeekPos)->toBeLessThan($lastWeekPos);
    expect($lastWeekPos)->toBeLessThan($thisMonthPos);
    expect($thisMonthPos)->toBeLessThan($customPos);

    // Verify last week label contains calculated date range (e.g. 26 Oct - 01 Nov 2026)
    $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->format('d M');
    $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY)->format('d M Y');
    $expectedLastWeekRange = $lastWeekStart.' - '.$lastWeekEnd;

    $response->assertSee('Last Week ('.$expectedLastWeekRange.')', false);
});

test('task 5: report contains export options for excel, pdf, and print and streams structured csv', function () {
    $response = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');

    $response->assertStatus(200);
    $response->assertSee('exportExcelBtn', false);
    $response->assertSee('exportPdfBtn', false);
    $response->assertSee('printReportBtn', false);
    $response->assertSee('@media print', false);

    // Test Excel export route
    $exportResponse = $this->actingAs($this->siteManager)->get(route('reports.site.labour-allocations.export-excel'));

    $exportResponse->assertStatus(200);
    $exportResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('ajax request returns dynamic report json dataset with allocations, weekly wages and kpis', function () {
    $response = $this->actingAs($this->siteManager)->getJson('/reports/site/labour-allocations?period=this_week');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            'allocations',
            'weekly_wages',
            'kpis' => [
                'total_man_days',
                'total_labour_wage',
                'total_daily_expenses',
                'total_weekly_expenses',
                'total_expenses',
                'grand_total',
            ],
            'filter_meta',
            'active_filters',
        ],
    ]);
});

test('all past allocations and weekly wages are passed to view for client-side date filter switching', function () {
    $pastDate = Carbon::now()->subWeeks(2)->toDateString();

    DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $pastDate,
        'total_labour_count' => 3,
        'total_labour_wage' => 3600,
        'total_expenses' => 100,
        'grand_total' => 3700,
        'trade_summary' => '3 Past Workers',
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($this->siteManager)->get('/reports/site/labour-allocations');

    $response->assertStatus(200);
    $viewAllocations = $response->viewData('allocations');
    expect(count($viewAllocations))->toBeGreaterThanOrEqual(1);
    expect(collect($viewAllocations)->pluck('trade_summary'))->toContain('3 Past Workers');
});
