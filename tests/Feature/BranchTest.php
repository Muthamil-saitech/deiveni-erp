<?php

use App\Models\Bank;
use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;
use App\Repositories\Eloquent\BranchRepository;
use App\Services\BranchService;
use App\Services\Contracts\BranchServiceInterface;
use Database\Seeders\BankSeeder;
use Database\Seeders\BranchSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(BankSeeder::class);
    $this->seed(BranchSeeder::class);
});

test('branches index page renders successfully and displays seeded branches and statistics', function () {
    $response = $this->get('/branches');

    $response->assertStatus(200);
    $response->assertSee('Branch Master');
    $response->assertSee('Empanelled Bank Branches & Valuation Directory', false);
    $response->assertSee('State Bank of India (SBI)');
    $response->assertSee('Valasaravakkam');
    $response->assertSee('SBIN0001234');
    $response->assertSee('HDFC Bank');
    $response->assertSee('HDFC0001892');
    $response->assertSee('Indian Bank');
    $response->assertSee('Canara Bank');
    $response->assertSee('ICICI Bank');
    $response->assertSee('Axis Bank');
    $response->assertSee('UTIB0000088');
    $response->assertSee('id="statTotalBranches"', false);
    $response->assertSee('id="statActiveBranches"', false);
    $response->assertSee('id="statInactiveBranches"', false);
    $response->assertSee('id="statAvgFee"', false);
    $response->assertDontSee('Empanelled Banks');
    $response->assertDontSee('Panel Approvals');
    $response->assertSee('id="branchModal"', false);
    $response->assertSee('name="bank_id"', false);
    // Task 5: Check that Inspection Turnaround is NOT present
    $response->assertDontSee('Inspection Turnaround');

    $firstBank = Bank::first();
    $response->assertSee('value="'.$firstBank->id.'"', false);
});

test('branches table has bank_id column and does not have bank_name column', function () {
    expect(Schema::hasColumn('branches', 'bank_id'))->toBeTrue()
        ->and(Schema::hasColumn('branches', 'bank_name'))->toBeFalse();
});

test('branches are ordered with last added row first', function () {
    $service = app(BranchServiceInterface::class);
    $branches = $service->getAllBranches();

    $ids = $branches->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    $bank = Bank::firstOrCreate(['bank_name' => 'Bank of Baroda'], ['status' => 'Active']);

    $newBranch = $service->createBranch([
        'bank_id' => $bank->id,
        'branch_name' => 'Koyambedu',
        'branch_name_ta' => 'பாங்க் ஆஃப் பரோடா',
        'ifsc_code' => 'BARB0KOYAMB',
        'contact_person' => 'Mr. Sundararajan',
        'phone' => '+91 98401 11223',
        'valuation_fee' => 7000.00,
        'status' => 'Active',
    ]);

    $updatedBranches = $service->getAllBranches();
    expect($updatedBranches->first()->id)->toBe($newBranch->id)
        ->and($updatedBranches->first()->bank_id)->toBe($bank->id)
        ->and($updatedBranches->first()->bank_name)->toBe('Bank of Baroda')
        ->and($updatedBranches->first()->ifsc_code)->toBe('BARB0KOYAMB');
});

test('service and repository interfaces for branch are properly bound in service container', function () {
    $repository = app(BranchRepositoryInterface::class);
    $service = app(BranchServiceInterface::class);

    expect($repository)->toBeInstanceOf(BranchRepository::class)
        ->and($service)->toBeInstanceOf(BranchService::class);
});

test('storing a branch requires mandatory fields and does not require valuation_fee or turnaround_time', function () {
    $response = $this->postJson('/branches', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['bank_id', 'branch_name', 'ifsc_code', 'status'])
        ->assertJsonMissingValidationErrors(['valuation_fee', 'turnaround_time']);
});

test('storing a branch validates IFSC code format and converts it to uppercase', function () {
    $bank = Bank::first();

    $response = $this->postJson('/branches', [
        'bank_id' => $bank->id,
        'branch_name' => 'Velachery',
        'ifsc_code' => 'invalid-ifsc',
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['ifsc_code']);
});

test('storing a branch validates uniqueness of branch name, branch name in tamil, and ifsc code with bilingual errors', function () {
    $bank = Bank::first();

    $response = $this->postJson('/branches', [
        'bank_id' => $bank->id,
        'branch_name' => 'Valasaravakkam', // already exists in seeded data
        'branch_name_ta' => 'பாரத ஸ்டேட் வங்கி (வளசரவாக்கம்)', // already exists in seeded data
        'ifsc_code' => 'SBIN0001234', // already exists in seeded data
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['branch_name', 'branch_name_ta', 'ifsc_code']);

    $errors = $response->json('errors');
    expect($errors['branch_name'][0])->toContain('This branch name already exists')->toContain('இந்த வங்கி கிளை பெயர் ஏற்கனவே உள்ளது')
        ->and($errors['branch_name_ta'][0])->toContain('This Tamil branch name already exists')->toContain('இந்த தமிழ் வங்கி கிளை பெயர் ஏற்கனவே உள்ளது')
        ->and($errors['ifsc_code'][0])->toContain('This IFSC code is already registered for another branch')->toContain('இந்த IFSC குறியீடு ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது');
});

test('updating a branch validates uniqueness of branch name, branch name in tamil, and ifsc code against other records', function () {
    $branch1 = Branch::where('ifsc_code', 'SBIN0001234')->first();
    $branch2 = Branch::where('ifsc_code', 'HDFC0001892')->first();

    // Updating branch1 with branch2's unique values should fail
    $response = $this->putJson("/branches/{$branch1->id}", [
        'bank_id' => $branch1->bank_id,
        'branch_name' => $branch2->branch_name,
        'branch_name_ta' => $branch2->branch_name_ta,
        'ifsc_code' => $branch2->ifsc_code,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['branch_name', 'branch_name_ta', 'ifsc_code']);

    $errors = $response->json('errors');
    expect($errors['branch_name'][0])->toContain('This branch name already exists')->toContain('இந்த வங்கி கிளை பெயர் ஏற்கனவே உள்ளது')
        ->and($errors['branch_name_ta'][0])->toContain('This Tamil branch name already exists')->toContain('இந்த தமிழ் வங்கி கிளை பெயர் ஏற்கனவே உள்ளது')
        ->and($errors['ifsc_code'][0])->toContain('This IFSC code is already registered for another branch')->toContain('இந்த IFSC குறியீடு ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது');

    // Updating branch1 keeping its own branch_name, branch_name_ta, and ifsc_code should succeed (ignore current id)
    $successResponse = $this->putJson("/branches/{$branch1->id}", [
        'bank_id' => $branch1->bank_id,
        'branch_name' => $branch1->branch_name,
        'branch_name_ta' => $branch1->branch_name_ta,
        'ifsc_code' => $branch1->ifsc_code,
        'contact_person' => 'Updated Person',
        'status' => 'Active',
    ]);

    $successResponse->assertStatus(200);
});

test('database throws integrity constraint violation when inserting duplicate branch_name, branch_name_ta, or ifsc_code directly', function () {
    $bank = Bank::first();

    // Duplicate branch_name
    expect(function () use ($bank) {
        Branch::create([
            'bank_id' => $bank->id,
            'branch_name' => 'Valasaravakkam', // duplicate
            'branch_name_ta' => 'தனித்துவமான பெயர்',
            'ifsc_code' => 'TEST0123456',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);

    // Duplicate branch_name_ta
    expect(function () use ($bank) {
        Branch::create([
            'bank_id' => $bank->id,
            'branch_name' => 'Unique Branch Name',
            'branch_name_ta' => 'பாரத ஸ்டேட் வங்கி (வளசரவாக்கம்)', // duplicate
            'ifsc_code' => 'TEST0123457',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);

    // Duplicate ifsc_code
    expect(function () use ($bank) {
        Branch::create([
            'bank_id' => $bank->id,
            'branch_name' => 'Another Unique Branch',
            'branch_name_ta' => 'மற்றொரு தனித்துவமான பெயர்',
            'ifsc_code' => 'SBIN0001234', // duplicate
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);
});

test('storing a branch allows nullable valuation fee and saves bank_id in database', function () {
    $bank = Bank::firstOrCreate(['bank_name' => 'Punjab National Bank'], ['status' => 'Active']);

    $response = $this->postJson('/branches', [
        'bank_id' => $bank->id,
        'branch_name' => 'Adyar',
        'ifsc_code' => 'PUNB0998877',
        'valuation_fee' => null,
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.bank_id', $bank->id)
        ->assertJsonPath('data.bank_name', 'Punjab National Bank')
        ->assertJsonPath('data.branch_name', 'Adyar');

    $branch = Branch::where('ifsc_code', 'PUNB0998877')->first();
    expect($branch)->not->toBeNull()
        ->and($branch->bank_id)->toBe($bank->id)
        ->and((float) $branch->valuation_fee)->toBe(0.0);
});

test('can create a new branch successfully via AJAX', function () {
    $bank = Bank::firstOrCreate(['bank_name' => 'Punjab National Bank'], ['status' => 'Active']);

    $response = $this->postJson('/branches', [
        'bank_id' => $bank->id,
        'branch_name' => 'Adyar',
        'branch_name_ta' => 'பஞ்சாப் நேஷனல் வங்கி (அடையாறு)',
        'ifsc_code' => 'punb0998878',
        'contact_person' => 'Mr. P. Kumar',
        'phone' => '+91 98409 12345',
        'valuation_fee' => 7200.00,
        'status' => 'Active',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonPath('data.ifsc_code', 'PUNB0998878')
        ->assertJsonPath('data.bank_id', $bank->id)
        ->assertJsonPath('data.bank_name', 'Punjab National Bank');

    $branch = Branch::where('ifsc_code', 'PUNB0998878')->first();
    expect($branch)->not->toBeNull()
        ->and($branch->bank_id)->toBe($bank->id)
        ->and($branch->branch_name)->toBe('Adyar')
        ->and((float) $branch->valuation_fee)->toBe(7200.0);
});

test('can fetch a single branch record by ID', function () {
    $firstBranch = Branch::first();

    $response = $this->getJson("/branches/{$firstBranch->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $firstBranch->id,
                'bank_id' => $firstBranch->bank_id,
                'ifsc_code' => $firstBranch->ifsc_code,
            ],
        ]);
});

test('can update an existing branch without altering IFSC', function () {
    $branch = Branch::where('ifsc_code', 'SBIN0001234')->first();

    $response = $this->putJson("/branches/{$branch->id}", [
        'bank_id' => $branch->bank_id,
        'branch_name' => 'Valasaravakkam Main',
        'branch_name_ta' => 'பாரத ஸ்டேட் வங்கி (வளசரவாக்கம் பிரதானம்)',
        'ifsc_code' => 'SBIN0001234',
        'contact_person' => 'Mr. S. Ramanathan (AGM)',
        'phone' => '+91 98401 23456',
        'valuation_fee' => 8500.00,
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $branch->refresh();
    expect($branch->branch_name)->toBe('Valasaravakkam Main')
        ->and((float) $branch->valuation_fee)->toBe(8500.0);
});

test('can delete a branch record', function () {
    $branch = Branch::where('ifsc_code', 'UTIB0000088')->first();

    $response = $this->deleteJson("/branches/{$branch->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(Branch::find($branch->id))->toBeNull();
});

test('service calculates branch statistics accurately', function () {
    $service = app(BranchServiceInterface::class);
    $stats = $service->getBranchStats();

    expect($stats)->toHaveKeys(['total_banks', 'total_branches', 'active_branches', 'inactive_branches', 'avg_fee', 'panel_approval_rate'])
        ->and($stats['active_branches'])->toBe(6)
        ->and($stats['avg_fee'])->toBeGreaterThan(5000);
});

test('branches index page renders table pagination elements and controls', function () {
    $response = $this->get('/branches');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
