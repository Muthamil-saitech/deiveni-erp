<?php

use App\Models\AdminLedger;
use App\Models\CashTransfer;
use App\Models\Client;
use App\Models\Ledger;
use App\Models\ProjectSite;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\SiteCash;
use App\Models\User;
use App\Services\CashTransferService;
use App\Services\Contracts\CashTransferServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ownerRole = Role::where('slug', 'owner')->first() ?? Role::firstOrCreate(['slug' => 'owner'], ['name' => 'Owner', 'is_system' => true]);
    $this->adminRole = Role::where('slug', 'admin')->first() ?? Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_system' => true]);
    $this->smRole = Role::where('slug', 'site_manager')->first() ?? Role::firstOrCreate(['slug' => 'site_manager'], ['name' => 'Site Manager', 'is_system' => true]);
    $this->amRole = Role::where('slug', 'account_manager')->first() ?? Role::firstOrCreate(['slug' => 'account_manager'], ['name' => 'Account Manager', 'is_system' => true]);

    RoleMenu::firstOrCreate(['role_id' => $this->ownerRole->id, 'menu_key' => 'cash_transfer']);
    RoleMenu::firstOrCreate(['role_id' => $this->adminRole->id, 'menu_key' => 'cash_transfer']);
    RoleMenu::firstOrCreate(['role_id' => $this->smRole->id, 'menu_key' => 'cash_transfer']);
    RoleMenu::firstOrCreate(['role_id' => $this->amRole->id, 'menu_key' => 'cash_transfer']);
    RoleMenu::firstOrCreate(['role_id' => $this->amRole->id, 'menu_key' => 'cash_transfer_settle']);
    RoleMenu::firstOrCreate(['role_id' => $this->ownerRole->id, 'menu_key' => 'cash_transfer_settle']);
    RoleMenu::firstOrCreate(['role_id' => $this->adminRole->id, 'menu_key' => 'cash_transfer_settle']);

    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id, 'role' => 'admin']);
    $this->siteManager = User::factory()->create(['role_id' => $this->smRole->id, 'role' => 'site_manager']);
    $this->accountManager = User::factory()->create(['role_id' => $this->amRole->id, 'role' => 'account_manager']);

    $this->client = Client::create([
        'name' => 'Karthikeyan Client',
        'phone' => '9876543210',
    ]);

    $this->site = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'Grand Residency Site',
        'location' => 'Coimbatore',
        'work_type' => 'Construction',
        'status' => 'Ongoing',
        'budget' => 5000000,
        'spent' => 0,
        'created_by' => $this->admin->id,
    ]);
});

test('cash transfer index page renders successfully and displays kpis', function () {
    $response = $this->actingAs($this->accountManager)->get('/cash-transfer');

    $response->assertStatus(200);
    $response->assertSee('Cash Transfer Management', false);
    $response->assertSee('New Cash Transfer', false);
    $response->assertSee('Office &rarr; Site Transferred', false);
    $response->assertSee('Site &rarr; Office Borrowed', false);
});

test('accountant can record office to site cash transfer and syncs to daily ledger and site cash', function () {
    $today = Carbon::today()->toDateString();

    $response = $this->actingAs($this->accountManager)->postJson('/cash-transfer', [
        'transfer_direction' => 'office_to_site',
        'project_site_id' => $this->site->id,
        'transfer_date' => $today,
        'transfer_time' => '11:00 AM',
        'amount' => 15000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Accountant (Raja AM)',
        'received_by' => 'Site Engineer (Bala)',
        'purpose' => 'Urgent site plumbing & materials advance',
    ]);

    $response->assertStatus(201);
    $response->assertJson(['success' => true]);

    $transfer = CashTransfer::first();
    expect($transfer)->not->toBeNull();
    expect((float) $transfer->amount)->toBe(15000.0);
    expect($transfer->transfer_direction)->toBe('office_to_site');

    // Verify synchronized Daily Ledger (Office Outflow / Expense)
    expect($transfer->ledger_id)->not->toBeNull();
    $ledger = Ledger::find($transfer->ledger_id);
    expect($ledger)->not->toBeNull();
    expect($ledger->flow)->toBe('expense');
    expect((float) $ledger->amount)->toBe(15000.0);
    expect($ledger->transaction_type_name)->toBe('Cash Transfer to Site');

    // Verify synchronized Site Cash (Site Inflow / Advance Received)
    expect($transfer->site_cash_id)->not->toBeNull();
    $siteCash = SiteCash::find($transfer->site_cash_id);
    expect($siteCash)->not->toBeNull();
    expect($siteCash->flow_type)->toBe('inflow');
    expect((float) $siteCash->amount)->toBe(15000.0);
    expect($siteCash->transaction_type)->toBe('Advance Received');
});

test('site manager can record site to office temporary transfer and syncs to daily ledger and site cash', function () {
    $today = Carbon::today()->toDateString();

    $response = $this->actingAs($this->siteManager)->postJson('/cash-transfer', [
        'transfer_direction' => 'site_to_office',
        'project_site_id' => $this->site->id,
        'transfer_date' => $today,
        'transfer_time' => '02:30 PM',
        'amount' => 8000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Site Manager (Senthil)',
        'received_by' => 'Accountant (Raja AM)',
        'purpose' => 'Temporary office cash shortage loan',
        'is_temporary' => true,
        'expected_return_date' => Carbon::tomorrow()->toDateString(),
    ]);

    $response->assertStatus(201);

    $transfer = CashTransfer::first();
    expect($transfer)->not->toBeNull();
    expect($transfer->transfer_direction)->toBe('site_to_office');
    expect($transfer->settlement_status)->toBe('pending');

    // Verify Daily Ledger: Office Inflow (+)
    $ledger = Ledger::find($transfer->ledger_id);
    expect($ledger)->not->toBeNull();
    expect($ledger->flow)->toBe('income');
    expect((float) $ledger->amount)->toBe(8000.0);

    // Verify Site Cash: Site Outflow (-)
    $siteCash = SiteCash::find($transfer->site_cash_id);
    expect($siteCash)->not->toBeNull();
    expect($siteCash->flow_type)->toBe('outflow');
    expect((float) $siteCash->amount)->toBe(8000.0);
});

test('temporary site to office transfer can be settled and updates status', function () {
    $transfer = app(CashTransferService::class)->createTransfer([
        'transfer_direction' => 'site_to_office',
        'project_site_id' => $this->site->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'transfer_time' => '10:00 AM',
        'amount' => 5000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Site Engineer',
        'received_by' => 'Accountant',
        'purpose' => 'Emergency fuel & office expense loan',
        'is_temporary' => true,
    ]);

    expect($transfer->settlement_status)->toBe('pending');

    $response = $this->actingAs($this->accountManager)->postJson("/cash-transfer/{$transfer->id}/settle", [
        'settlement_notes' => 'Full amount returned back to Site Manager in cash on 19 Nov',
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $fresh = $transfer->fresh();
    expect($fresh->settlement_status)->toBe('settled');
    expect($fresh->settled_at)->not->toBeNull();
    expect($fresh->settlement_notes)->toContain('Full amount returned back');

    // Verify settlement Inflow (+) was created in Site Cash
    expect($fresh->return_site_cash_id)->not->toBeNull();
    $settleSiteCash = SiteCash::find($fresh->return_site_cash_id);
    expect($settleSiteCash)->not->toBeNull();
    expect($settleSiteCash->flow_type)->toBe('inflow');
    expect((float) $settleSiteCash->in_amount)->toBe(5000.0);
    expect($settleSiteCash->description)->toContain('Refund of temporary cash borrowed by Accountant');

    // Verify settlement Outflow (-) was created in Daily Ledger
    expect($fresh->return_ledger_id)->not->toBeNull();
    $settleLedger = Ledger::find($fresh->return_ledger_id);
    expect($settleLedger)->not->toBeNull();
    expect($settleLedger->flow)->toBe('expense');
    expect((float) $settleLedger->amount)->toBe(5000.0);
});

test('deleting cash transfer cleans up linked daily ledger and site cash records', function () {
    $transfer = app(CashTransferService::class)->createTransfer([
        'transfer_direction' => 'office_to_site',
        'project_site_id' => $this->site->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'amount' => 12000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Accountant',
        'received_by' => 'Site Manager',
        'purpose' => 'Advance for weekly tools',
    ]);

    $ledgerId = $transfer->ledger_id;
    $siteCashId = $transfer->site_cash_id;

    expect(Ledger::find($ledgerId))->not->toBeNull();
    expect(SiteCash::find($siteCashId))->not->toBeNull();

    $deleteResponse = $this->actingAs($this->admin)->deleteJson("/cash-transfer/{$transfer->id}");
    $deleteResponse->assertStatus(200);

    expect(CashTransfer::find($transfer->id))->toBeNull();
    expect(Ledger::find($ledgerId))->toBeNull();
    expect(SiteCash::find($siteCashId))->toBeNull();
});

test('RBAC: site manager and account manager have access to cash transfer', function () {
    $smRes = $this->actingAs($this->siteManager)->get('/cash-transfer');
    $smRes->assertStatus(200);

    $amRes = $this->actingAs($this->accountManager)->get('/cash-transfer');
    $amRes->assertStatus(200);

    $adminRes = $this->actingAs($this->admin)->get('/cash-transfer');
    $adminRes->assertStatus(200);
});

test('RBAC: only users with cash_transfer_settle permission can settle transfers directly', function () {
    $transfer = app(CashTransferService::class)->createTransfer([
        'transfer_direction' => 'site_to_office',
        'project_site_id' => $this->site->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'transfer_time' => '10:00 AM',
        'amount' => 3000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Site Engineer',
        'received_by' => 'Accountant',
        'purpose' => 'Urgent office loan',
        'is_temporary' => true,
    ]);

    // Site Manager has no settle permission by default -> 403 Forbidden
    $smResponse = $this->actingAs($this->siteManager)->postJson("/cash-transfer/{$transfer->id}/settle", [
        'settlement_notes' => 'Attempt by site manager',
    ]);
    $smResponse->assertStatus(403);
    $smResponse->assertJson(['success' => false]);

    // Account Manager has settle permission -> 200 OK
    $amResponse = $this->actingAs($this->accountManager)->postJson("/cash-transfer/{$transfer->id}/settle", [
        'settlement_notes' => 'Settled by Account Manager Raja',
    ]);
    $amResponse->assertStatus(200);
    $amResponse->assertJson(['success' => true]);

    // If Owner grants cash_transfer_settle permission to Site Manager role, SM can now settle
    RoleMenu::create(['role_id' => $this->smRole->id, 'menu_key' => 'cash_transfer_settle']);

    $transfer2 = app(CashTransferService::class)->createTransfer([
        'transfer_direction' => 'office_to_site',
        'project_site_id' => $this->site->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'transfer_time' => '11:00 AM',
        'amount' => 4000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Accountant',
        'received_by' => 'Site Manager',
        'purpose' => 'Advance for materials',
        'is_temporary' => true,
    ]);

    $smResponseAfterGrant = $this->actingAs($this->siteManager)->postJson("/cash-transfer/{$transfer2->id}/settle", [
        'settlement_notes' => 'Settled by Site Manager after access granted by owner',
    ]);
    $smResponseAfterGrant->assertStatus(200);
    $smResponseAfterGrant->assertJson(['success' => true]);
});

test('site manager can return cash transfer via site cash balance returned and auto-syncs with daily ledger', function () {
    $transfer = app(CashTransferServiceInterface::class)->createTransfer([
        'transfer_direction' => 'office_to_site',
        'project_site_id' => $this->site->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'transfer_time' => '11:00 AM',
        'amount' => 15000,
        'payment_mode' => 'Cash',
        'handed_over_by' => 'Accountant Raja',
        'received_by' => 'Site Manager Siva',
        'purpose' => 'Urgent plumbing emergency pipes and fittings',
        'is_temporary' => true,
        'created_by' => $this->accountManager->id,
    ]);

    expect($transfer->settlement_status)->toBe('pending');

    // Endpoint provides pending cash transfers for the site
    $listResponse = $this->actingAs($this->siteManager)
        ->getJson("/site-cash/cash-transfers?project_site_id={$this->site->id}");
    $listResponse->assertStatus(200);
    $listResponse->assertJsonFragment([
        'id' => $transfer->id,
        'purpose' => 'Urgent plumbing emergency pipes and fittings',
    ]);

    // Site Manager returns the remaining balance for this Cash Transfer
    $returnResponse = $this->actingAs($this->siteManager)->postJson('/site-cash', [
        'project_site_id' => $this->site->id,
        'transaction_type' => 'Balance Returned',
        'paid_to' => 'Account Manager',
        'payment_mode' => 'Cash',
        'amount' => 15000,
        'bill_no' => 'CTR-'.$transfer->id,
        'cash_transfer_id' => $transfer->id,
        'wage_cash_source' => 'cash_transfer',
        'description' => "Balance returned to Account Manager for Cash Transfer #CTR-{$transfer->id} (Urgent plumbing emergency pipes and fittings) [Office Cash]",
    ]);

    $returnResponse->assertStatus(201);
    $returnResponse->assertJson(['success' => true]);

    // Cash transfer is settled
    $transferFresh = $transfer->fresh();
    expect($transferFresh->settlement_status)->toBe('settled');

    // Site Cash record has cash_transfer_id
    $returnSiteCash = SiteCash::where('cash_transfer_id', $transfer->id)
        ->where('transaction_type', 'Balance Returned')
        ->first();
    expect($returnSiteCash)->not->toBeNull();
    expect($returnSiteCash->flow_type)->toBe('outflow');
    expect((float) $returnSiteCash->out_amount)->toBe(15000.0);

    // Daily Ledger has synchronized Inflow (+) entry with purpose and cash_transfer_id
    $syncedLedger = Ledger::where('cash_transfer_id', $transfer->id)
        ->where('flow', 'income')
        ->first();
    expect($syncedLedger)->not->toBeNull();
    expect($syncedLedger->description)->toContain('Urgent plumbing emergency pipes and fittings');
    expect((float) $syncedLedger->amount)->toBe(15000.0);

    // Detail view of Site Cash displays connected cash transfer details and purpose
    $detailResponse = $this->actingAs($this->siteManager)->get("/site-cash/{$returnSiteCash->encrypted_id}");
    $detailResponse->assertStatus(200);
    $detailResponse->assertSee('Connected Cash Transfer Details', false);
    $detailResponse->assertSee('Urgent plumbing emergency pipes and fittings', false);

    // Detail view of Ledger displays connected cash transfer details and purpose
    $ledgerDetailResponse = $this->actingAs($this->accountManager)->get("/ledger/{$syncedLedger->encrypted_id}");
    $ledgerDetailResponse->assertStatus(200);
    $ledgerDetailResponse->assertSee('Linked Cash Transfer Details', false);
    $ledgerDetailResponse->assertSee('Urgent plumbing emergency pipes and fittings', false);
});

test('owner cash transfer is settled without settle button and records entry in admin ledger', function () {
    $owner = User::factory()->create(['role_id' => $this->ownerRole->id, 'role' => 'owner']);
    $smSite = ProjectSite::create([
        'client_id' => $this->client->id,
        'name' => 'SM Owned Site',
        'location' => 'Madurai',
        'work_type' => 'Construction',
        'status' => 'Ongoing',
        'budget' => 1000000,
        'spent' => 0,
        'created_by' => $this->siteManager->id,
    ]);

    $response = $this->actingAs($owner)->postJson('/cash-transfer', [
        'transfer_direction' => 'office_to_site',
        'project_site_id' => $smSite->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'amount' => 25000,
        'payment_mode' => 'UPI',
        'payment_reference' => 'UTR-OWNER-101',
        'bank_name' => 'HDFC',
        'handed_over_by' => $owner->name,
        'received_by' => $this->siteManager->name,
        'purpose' => 'Owner direct site advance',
        'is_temporary' => 1,
    ]);

    $response->assertStatus(201);

    $transfer = CashTransfer::latest('id')->first();
    expect($transfer)->not->toBeNull();
    expect($transfer->is_temporary)->toBeFalse();
    expect($transfer->settlement_status)->toBe('settled');
    expect($transfer->admin_ledger_id)->not->toBeNull();

    $adminLedger = AdminLedger::find($transfer->admin_ledger_id);
    expect($adminLedger)->not->toBeNull();
    expect($adminLedger->category)->toBe('cash_transfer');
    expect($adminLedger->flow)->toBe('expense');
    expect((float) $adminLedger->amount)->toBe(25000.0);
    expect($adminLedger->project_site_id)->toBe($smSite->id);
    expect($adminLedger->payment_mode)->toBe('UPI');
    expect($adminLedger->payment_reference)->toBe('UTR-OWNER-101');

    // Site Manager accepts Owner's transfer -> Site Cash records Inflow (+), AM Daily Ledger has NO expense
    $acceptSmRes = $this->actingAs($this->siteManager)->postJson("/cash-transfer/{$transfer->id}/accept");
    $acceptSmRes->assertStatus(200);
    $transfer->refresh();
    expect($transfer->ledger_id)->toBeNull();
    expect($transfer->site_cash_id)->not->toBeNull();
    $siteCash = SiteCash::find($transfer->site_cash_id);
    expect($siteCash->flow_type)->toBe('inflow');
    expect((float) $siteCash->in_amount)->toBe(25000.0);

    // Owner transfers to Account Manager -> AM accepts -> AM Daily Ledger records Income (+)
    $amTransferRes = $this->actingAs($owner)->postJson('/cash-transfer', [
        'transfer_direction' => 'office_to_site',
        'project_site_id' => $smSite->id,
        'transfer_date' => Carbon::today()->toDateString(),
        'amount' => 40000,
        'payment_mode' => 'Cash',
        'handed_over_by' => $owner->name,
        'received_by' => $this->accountManager->name,
        'purpose' => 'Owner to AM fund',
    ]);
    $amTransferRes->assertStatus(201);
    $amTransfer = CashTransfer::latest('id')->first();
    expect($amTransfer->ledger_id)->toBeNull();

    $acceptAmRes = $this->actingAs($this->accountManager)->postJson("/cash-transfer/{$amTransfer->id}/accept");
    $acceptAmRes->assertStatus(200);
    $amTransfer->refresh();
    expect($amTransfer->site_cash_id)->toBeNull();
    expect($amTransfer->ledger_id)->not->toBeNull();
    $amLedger = Ledger::find($amTransfer->ledger_id);
    expect($amLedger->flow)->toBe('income');
    expect((float) $amLedger->amount)->toBe(40000.0);
    expect($amLedger->transaction_type_name)->toBe('Cash Received from Owner');
});
