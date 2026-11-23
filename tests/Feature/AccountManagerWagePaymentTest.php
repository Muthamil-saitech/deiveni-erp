<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Models\User;
use App\Repositories\Contracts\SiteCashRepositoryInterface;
use App\Services\Contracts\WeeklyWageServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
    ]);

    $this->siteManager = User::factory()->create([
        'role' => UserRole::SiteManager,
    ]);

    $this->client = Client::create([
        'name' => 'Royal Constructions Client',
        'phone' => '9844001122',
        'address' => 'Chennai',
        'created_by' => $this->accountManager->id,
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Royal Villa Project',
        'location' => 'Chennai',
        'work_type' => 'Residential',
        'start_date' => '2026-11-01',
        'end_date' => '2027-05-31',
        'estimated_budget' => 800000.00,
        'spent' => 0.00,
        'status' => 'In Progress',
        'created_by' => $this->accountManager->id,
    ]);
});

test('account manager checks wages endpoint and sees painter and electrician auto-detected', function () {
    $date = '2026-11-18';

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 5,
        'total_labour_wage' => 5200.00,
        'total_expenses' => 0.00,
        'grand_total' => 5200.00,
        'created_by' => $this->siteManager->id,
    ]);

    // Mason: 2 x 1200 = 2400
    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-001',
        'name_ta' => 'கொத்தனார்',
        'name_en' => 'Head Mason',
        'daily_wage' => 1200.00,
        'master_wage' => 1200.00,
        'worker_count' => 2,
        'subtotal' => 2400.00,
    ]);

    // Painter: 2 x 900 = 1800
    $painter = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-007',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter / Wall Finisher',
        'daily_wage' => 900.00,
        'master_wage' => 900.00,
        'worker_count' => 2,
        'subtotal' => 1800.00,
    ]);

    // Electrician: 1 x 1000 = 1000
    $electrician = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-008',
        'name_ta' => 'எலக்ட்ரீஷியன்',
        'name_en' => 'Electrician & Plumbing Tech',
        'daily_wage' => 1000.00,
        'master_wage' => 1000.00,
        'worker_count' => 1,
        'subtotal' => 1000.00,
    ]);

    $response = $this->actingAs($this->accountManager)->getJson("/ledger/check-wages?project_site_id={$this->site->id}&entry_date={$date}");

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'found' => true,
        'has_matching_trades' => true,
        'all_paid' => false,
        'unpaid_total' => 2800.00, // 1800 + 1000
    ]);
    expect($response->json('items'))->toHaveCount(2);
});

test('account manager records daily ledger wage payment and updates project spent without deducting site cash in hand', function () {
    $date = '2026-11-18';

    // 1. Site Manager is given 10,000 cash advance from office
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Advance Received',
        'flow_type' => 'inflow',
        'amount' => 10000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
        'in_amount' => 10000.00,
        'out_amount' => 0.00,
        'is_expense' => false,
        'payment_date' => $date,
        'created_by' => $this->siteManager->id,
    ]);

    // 2. Site Manager records local expense of 3000 from site cash
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Daily Wage Payment',
        'flow_type' => 'outflow',
        'amount' => 3000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Local Mason',
        'in_amount' => 0.00,
        'out_amount' => 3000.00,
        'is_expense' => true,
        'payment_date' => $date,
        'created_by' => $this->siteManager->id,
    ]);

    // Initial check: Site manager spent 3000, has 7000 in hand
    auth()->login($this->siteManager);
    $siteCashRepo = app(SiteCashRepositoryInterface::class);
    $statsInitial = $siteCashRepo->getStats($this->site->id);
    expect($statsInitial['cash_in_hand'])->toEqual(7000.00);

    // 3. Daily allocation with Painter & Electrician: 2000 wages
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 2,
        'total_labour_wage' => 2000.00,
        'grand_total' => 2000.00,
        'created_by' => $this->siteManager->id,
    ]);

    $painterItem = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-007',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'daily_wage' => 1000.00,
        'worker_count' => 2,
        'subtotal' => 2000.00,
    ]);

    // 4. Account Manager pays Painter wages (2000) directly from office cash via Daily Ledger
    $ledgerResponse = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => $date,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 2000.00,
        'project_site_id' => $this->site->id,
        'daily_allocation_id' => $alloc->id,
        'is_am_wage_payment' => 1,
        'description' => 'Painter wages paid by Account Manager',
    ]);

    $ledgerResponse->assertStatus(201);
    $ledgerId = $ledgerResponse->json('data.id');

    // Verify daily allocation item is marked as paid by Account Manager
    $painterItem->refresh();
    expect($painterItem->paid_by)->toBe('Account Manager');
    expect($painterItem->ledger_id)->toBe($ledgerId);

    // Verify SiteCash record was created with paid_by = 'Account Manager'
    $syncedSiteCash = SiteCash::where('ledger_id', $ledgerId)->first();
    expect($syncedSiteCash)->not->toBeNull();
    expect($syncedSiteCash->paid_by)->toBe('Account Manager');
    expect($syncedSiteCash->is_expense)->toBeTrue();
    expect((float) $syncedSiteCash->amount)->toEqual(2000.00);

    // 5. PROJECT SPENT / BUDGET: Total project expenses = 3000 (mason) + 2000 (painter by AM) = 5000!
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(5000.00);

    // 6. SITE MANAGER CASH IN HAND (THE TWIST):
    // The 2000 was paid by AM from office. Site Manager still physically holds 7000 (10000 - 3000)!
    auth()->login($this->siteManager);
    $statsAfterAmPayment = $siteCashRepo->getStats($this->site->id);
    expect($statsAfterAmPayment['cash_in_hand'])->toEqual(7000.00);

    // 7. Site Manager returns remaining balance (7000) to Account Manager
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Balance Returned',
        'flow_type' => 'outflow',
        'amount' => 7000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Account Manager',
        'in_amount' => 0.00,
        'out_amount' => 7000.00,
        'is_expense' => false,
        'payment_date' => $date,
        'created_by' => $this->siteManager->id,
    ]);

    $statsFinal = $siteCashRepo->getStats($this->site->id);
    expect($statsFinal['cash_in_hand'])->toEqual(0.00);

    // Project spent remains 5000 (unaffected by balance return)
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(5000.00);
});

test('weekly wage sheet separates account manager wages and calculates net site payout correctly', function () {
    $monday = '2026-11-16';
    $sunday = '2026-11-22';

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $monday,
        'total_labour_count' => 4,
        'total_labour_wage' => 4500.00,
        'total_expenses' => 300.00,
        'grand_total' => 4800.00,
        'created_by' => $this->siteManager->id,
    ]);

    // Mason: 2 x 1200 = 2400 (Paid from Site Cash)
    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-001',
        'name_ta' => 'கொத்தனார்',
        'name_en' => 'Mason',
        'daily_wage' => 1200.00,
        'worker_count' => 2,
        'subtotal' => 2400.00,
    ]);

    // Painter: 2 x 1050 = 2100 (Paid by Account Manager)
    DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-007',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'daily_wage' => 1050.00,
        'worker_count' => 2,
        'subtotal' => 2100.00,
        'paid_by' => 'Account Manager',
    ]);

    auth()->login($this->siteManager);
    $weeklyWageService = app(WeeklyWageServiceInterface::class);
    $sheet = $weeklyWageService->getWeeklySheet($this->site->id, $monday, $sunday);

    expect($sheet['summary']['total_labour_wages'])->toEqual(4500.00);
    expect($sheet['summary']['total_am_wages'])->toEqual(2100.00);
    expect($sheet['summary']['net_site_wages'])->toEqual(2400.00);
    // Net Payout for site manager = net site wages (2400) + daily expenses (300) = 2700
    expect($sheet['summary']['net_payout'])->toEqual(2700.00);
});

test('deleting an account manager wage ledger entry reverts daily allocation items and updates site spent', function () {
    $date = '2026-11-19';

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 2,
        'total_labour_wage' => 1900.00,
        'grand_total' => 1900.00,
        'created_by' => $this->siteManager->id,
    ]);

    $painter = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-007',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'daily_wage' => 950.00,
        'worker_count' => 2,
        'subtotal' => 1900.00,
    ]);

    $res = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => $date,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 1900.00,
        'project_site_id' => $this->site->id,
        'daily_allocation_id' => $alloc->id,
        'is_am_wage_payment' => 1,
        'description' => 'Painter wages payment',
    ]);

    $ledgerId = $res->json('data.id');
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(1900.00);

    // Now delete this ledger entry
    $deleteRes = $this->actingAs($this->accountManager)->postJson("/ledger/{$ledgerId}/delete");
    $deleteRes->assertStatus(200);

    // Verify daily allocation item is reverted
    $painter->refresh();
    expect($painter->paid_by)->toBeNull();
    expect($painter->ledger_id)->toBeNull();

    // Verify SiteCash record is deleted
    expect(SiteCash::where('ledger_id', $ledgerId)->exists())->toBeFalse();

    // Verify project site spent reverted
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(0.00);
});

test('account manager wage payment with project cash deducts site manager cash in hand and tallies budget and return balance', function () {
    $siteCashRepo = app(SiteCashRepositoryInterface::class);
    $date = '2026-11-18';

    // 1. Advance: 10,000 given to Site Manager
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Advance Received',
        'flow_type' => 'inflow',
        'amount' => 10000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Site Manager',
        'in_amount' => 10000.00,
        'out_amount' => 0.00,
        'is_expense' => false,
        'payment_date' => $date,
        'created_by' => $this->siteManager->id,
    ]);

    // 2. Daily Allocation with Painter
    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 2,
        'total_labour_wage' => 2000.00,
        'grand_total' => 2000.00,
        'created_by' => $this->siteManager->id,
    ]);

    $painterItem = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-007',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'daily_wage' => 1000.00,
        'worker_count' => 2,
        'subtotal' => 2000.00,
    ]);

    // 3. Account Manager records wage payment with wage_cash_source = 'project_cash'
    $ledgerResponse = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => $date,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 2000.00,
        'project_site_id' => $this->site->id,
        'daily_allocation_id' => $alloc->id,
        'wage_cash_source' => 'project_cash',
        'is_am_wage_payment' => 1,
        'description' => 'Painter wages paid from project cash by Account Manager',
    ]);

    $ledgerResponse->assertStatus(201);
    $ledgerId = $ledgerResponse->json('data.id');

    // Verify daily allocation item has wage_cash_source = 'project_cash'
    $painterItem->refresh();
    expect($painterItem->paid_by)->toBe('Account Manager');
    expect($painterItem->wage_cash_source)->toBe('project_cash');

    // Verify synced SiteCash has wage_cash_source = 'project_cash'
    $syncedSiteCash = SiteCash::where('ledger_id', $ledgerId)->first();
    expect($syncedSiteCash)->not->toBeNull();
    expect($syncedSiteCash->wage_cash_source)->toBe('project_cash');
    expect($syncedSiteCash->is_expense)->toBeTrue();

    // Project budget/spent is incremented by 2,000
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(2000.00);

    // Site Manager's cash in hand is reduced to 8,000 (10,000 - 2,000) because it was paid from project cash!
    auth()->login($this->siteManager);
    $statsAfterProjectCashWage = $siteCashRepo->getStats($this->site->id);
    expect($statsAfterProjectCashWage['cash_in_hand'])->toEqual(8000.00);

    // Site Manager only returns the remaining 8,000 (does not need to return the 2,000 project cash)
    SiteCash::create([
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Balance Returned',
        'flow_type' => 'outflow',
        'amount' => 8000.00,
        'payment_mode' => 'Cash',
        'paid_to' => 'Account Manager',
        'in_amount' => 0.00,
        'out_amount' => 8000.00,
        'is_expense' => false,
        'payment_date' => $date,
        'created_by' => $this->siteManager->id,
    ]);

    // Cash in hand now cleanly balances to ₹0.00
    $statsFinal = $siteCashRepo->getStats($this->site->id);
    expect($statsFinal['cash_in_hand'])->toEqual(0.00);

    // Project spent remains 2,000.00
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(2000.00);
});

test('account manager records wages with extra support workers and recalculates daily allocation and spent', function () {
    $date = '2026-11-18';

    $alloc = DailyAllocation::create([
        'project_site_id' => $this->site->id,
        'date' => $date,
        'total_labour_count' => 2,
        'total_labour_wage' => 2400.00,
        'total_expenses' => 0.00,
        'grand_total' => 2400.00,
        'created_by' => $this->siteManager->id,
    ]);

    // Painter: 1 x 1100 = 1100
    $painter = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-007',
        'name_ta' => 'பெயிண்டர்',
        'name_en' => 'Painter',
        'daily_wage' => 1100.00,
        'master_wage' => 1100.00,
        'worker_count' => 1,
        'subtotal' => 1100.00,
    ]);

    // Electrician: 1 x 1300 = 1300
    $electrician = DailyAllocationItem::create([
        'daily_allocation_id' => $alloc->id,
        'category_code' => 'CAT-008',
        'name_ta' => 'எலக்ட்ரீஷியன்',
        'name_en' => 'Electrician',
        'daily_wage' => 1300.00,
        'master_wage' => 1300.00,
        'worker_count' => 1,
        'subtotal' => 1300.00,
    ]);

    // Account Manager pays 2400 base + 500 extra support worker (Murugan) = 2900 total
    $ledgerResponse = $this->actingAs($this->accountManager)->postJson('/ledger', [
        'entry_date' => $date,
        'flow' => 'expense',
        'source' => 'Construction',
        'amount' => 2900.00,
        'project_site_id' => $this->site->id,
        'daily_allocation_id' => $alloc->id,
        'wage_cash_source' => 'office_cash',
        'is_am_wage_payment' => 1,
        'extra_workers' => [
            [
                'name' => 'Murugan',
                'work' => 'Painter Helper',
                'salary' => 500.00,
            ],
        ],
        'description' => 'Painter & Electrician daily wages + Extra Worker Murugan (Painter Helper: ₹500)',
    ]);

    $ledgerResponse->assertStatus(201);
    $ledgerId = $ledgerResponse->json('data.id');

    // 1. Verify original line items are marked as paid by Account Manager
    $painter->refresh();
    expect($painter->paid_by)->toBe('Account Manager');
    expect($painter->ledger_id)->toBe($ledgerId);

    $electrician->refresh();
    expect($electrician->paid_by)->toBe('Account Manager');
    expect($electrician->ledger_id)->toBe($ledgerId);

    // 2. Verify new extra helper worker line item created in daily_allocation_items
    $extraItem = DailyAllocationItem::where('ledger_id', $ledgerId)
        ->where('category_code', 'EXTRA-HELPER')
        ->first();
    expect($extraItem)->not->toBeNull();
    expect($extraItem->name_ta)->toBe('Murugan (Painter Helper)');
    expect((float) $extraItem->daily_wage)->toEqual(500.00);
    expect($extraItem->worker_count)->toBe(1);
    expect((float) $extraItem->subtotal)->toEqual(500.00);
    expect($extraItem->paid_by)->toBe('Account Manager');

    // 3. Verify allocation totals updated
    $alloc->refresh();
    expect($alloc->total_labour_count)->toBe(3); // 2 original + 1 helper
    expect((float) $alloc->total_labour_wage)->toEqual(2900.00);
    expect((float) $alloc->grand_total)->toEqual(2900.00);

    // 4. Verify Project Site Spent updated to 2900
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(2900.00);

    // 5. Verify synced SiteCash transaction
    $syncedSiteCash = SiteCash::where('ledger_id', $ledgerId)->first();
    expect($syncedSiteCash)->not->toBeNull();
    expect((float) $syncedSiteCash->amount)->toEqual(2900.00);
    expect($syncedSiteCash->extra_workers)->toHaveCount(1);
    expect($syncedSiteCash->extra_workers[0]['name'])->toBe('Murugan');

    // 6. Delete ledger entry -> verifies rollback
    $delRes = $this->actingAs($this->accountManager)->postJson("/ledger/{$ledgerId}/delete");
    $delRes->assertStatus(200);

    // Extra worker deleted
    expect(DailyAllocationItem::where('id', $extraItem->id)->exists())->toBeFalse();

    // Original trades reverted
    $painter->refresh();
    expect($painter->paid_by)->toBeNull();

    // Allocation totals restored
    $alloc->refresh();
    expect($alloc->total_labour_count)->toBe(2);
    expect((float) $alloc->total_labour_wage)->toEqual(2400.00);

    // Spent restored to 0
    $this->site->refresh();
    expect((float) $this->site->spent)->toEqual(0.00);
});
