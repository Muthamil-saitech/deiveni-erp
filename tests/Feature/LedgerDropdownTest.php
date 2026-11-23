<?php

use App\Enums\UserRole;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\ProjectSite;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->accountManager = User::factory()->create([
        'role' => UserRole::AccountManager,
        'status' => 'Active',
    ]);
});

test('ledger page loads successfully with bank and property dropdown structures', function () {
    $response = $this->actingAs($this->accountManager)->get('/ledger');

    $response->assertStatus(200);
    $response->assertSee('id="ledgerBankSelect"', false);
    $response->assertSee('id="branchSelectContainer"', false);
    $response->assertSee('id="ledgerBranchSelect"', false);
    $response->assertSee('id="propertySelectContainer"', false);
    $response->assertSee('id="ledgerPropertySelect"', false);
    $response->assertSee('id="propertyTypeSelectContainer"', false);
    $response->assertSee('id="ledgerPropertyTypeSelect"', false);
});

test('task 1: ledger bank select shows only active banks and branch container shows assigned active branches', function () {
    $activeBank = Bank::factory()->create([
        'bank_name' => 'Active Test Bank',
        'bank_name_ta' => 'செயலில் உள்ள வங்கி',
        'status' => 'Active',
    ]);

    $inactiveBank = Bank::factory()->create([
        'bank_name' => 'Inactive Test Bank',
        'bank_name_ta' => 'செயலற்ற வங்கி',
        'status' => 'Inactive',
    ]);

    $activeBranch = Branch::factory()->create([
        'bank_id' => $activeBank->id,
        'branch_name' => 'Active Branch A',
        'status' => 'Active',
        'valuation_fee' => 5000,
    ]);

    $inactiveBranch = Branch::factory()->create([
        'bank_id' => $activeBank->id,
        'branch_name' => 'Inactive Branch B',
        'status' => 'Inactive',
        'valuation_fee' => 3000,
    ]);

    $response = $this->actingAs($this->accountManager)->get('/ledger');

    $response->assertStatus(200);

    // Active bank must be displayed in the select box
    $response->assertSee('Active Test Bank');

    // Inactive bank must NOT be displayed in the select box
    $response->assertDontSee('Inactive Test Bank');

    // View data must only contain active bank and its active branches
    $response->assertViewHas('activeBanks', function ($banks) use ($activeBank, $inactiveBank, $activeBranch, $inactiveBranch) {
        $containsActive = $banks->contains('id', $activeBank->id);
        $notContainsInactive = ! $banks->contains('id', $inactiveBank->id);

        $bankModel = $banks->firstWhere('id', $activeBank->id);
        $branchIds = $bankModel->branches->pluck('id')->all();

        return $containsActive && $notContainsInactive
            && in_array($activeBranch->id, $branchIds)
            && ! in_array($inactiveBranch->id, $branchIds);
    });
});

test('task 2: ledger property select shows only active properties and type container shows assigned active property types', function () {
    $activeProp = Property::factory()->create([
        'property_name' => 'Active Test Property',
        'property_name_ta' => 'செயலில் உள்ள சொத்து',
        'status' => 'Active',
    ]);

    $inactiveProp = Property::factory()->create([
        'property_name' => 'Inactive Test Property',
        'property_name_ta' => 'செயலற்ற சொத்து',
        'status' => 'Inactive',
    ]);

    $activeType = PropertyType::factory()->create([
        'property_id' => $activeProp->id,
        'shop_home_office_name' => 'Shop 101 Active',
        'property_type' => 'Commercial Shop',
        'status' => 'Active',
        'monthly_rent' => 12000,
    ]);

    $inactiveType = PropertyType::factory()->create([
        'property_id' => $activeProp->id,
        'shop_home_office_name' => 'Shop 102 Inactive',
        'property_type' => 'Commercial Shop',
        'status' => 'Inactive',
        'monthly_rent' => 8000,
    ]);

    $response = $this->actingAs($this->accountManager)->get('/ledger');

    $response->assertStatus(200);

    // Active property must be displayed in the select box
    $response->assertSee('Active Test Property');

    // Inactive property must NOT be displayed in the select box
    $response->assertDontSee('Inactive Test Property');

    // View data must only contain active property and its active types
    $response->assertViewHas('activeProperties', function ($properties) use ($activeProp, $inactiveProp, $activeType, $inactiveType) {
        $containsActive = $properties->contains('id', $activeProp->id);
        $notContainsInactive = ! $properties->contains('id', $inactiveProp->id);

        $propModel = $properties->firstWhere('id', $activeProp->id);
        $typeIds = $propModel->propertyTypes->pluck('id')->all();

        return $containsActive && $notContainsInactive
            && in_array($activeType->id, $typeIds)
            && ! in_array($inactiveType->id, $typeIds);
    });
});

test('task 1: associated site dropdown lists real sites from project_sites table excluding completed projects and without dummy sites', function () {
    $activeSite = ProjectSite::factory()->create([
        'name' => 'Real Active Project Site',
        'status' => 'In Progress',
    ]);
    $completedSite = ProjectSite::factory()->create([
        'name' => 'Finished Completed Project Site',
        'status' => 'Completed',
    ]);

    $response = $this->actingAs($this->accountManager)->get('/ledger');

    $response->assertStatus(200);
    $response->assertSee('Real Active Project Site');
    $response->assertDontSee('Finished Completed Project Site');
    $response->assertDontSee('Site A - Villa 102');
    $response->assertDontSee('Site B - Renovation');
});

test('task 2: ledger page uses active suppliers from master and contains no static hardcoded table data', function () {
    $activeSupplier = Supplier::factory()->create([
        'name' => 'Active Cement & Sand Supplier',
        'status' => 'Active',
    ]);

    $inactiveSupplier = Supplier::factory()->create([
        'name' => 'Inactive Brick Agency',
        'status' => 'Inactive',
    ]);

    $response = $this->actingAs($this->accountManager)->get('/ledger');

    $response->assertStatus(200);

    // Active supplier must be in the view & dropdown
    $response->assertSee('Active Cement & Sand Supplier');
    $response->assertDontSee('Inactive Brick Agency');

    // Summary cards default to zero
    $response->assertSee('id="ledgerOpeningBal">₹0<', false);
    $response->assertSee('id="ledgerTotalIncome">₹0<', false);
    $response->assertSee('id="ledgerTotalExpense">₹0<', false);
    $response->assertSee('id="ledgerClosingBal">₹0<', false);

    // Empty state row is present
    $response->assertSee('id="ledgerNoDataRow"', false);

    // Static fake data from previously hardcoded table rows must be absent
    $response->assertDontSee('Valasaravakkam site stage 2 client payment received');
    $response->assertDontSee('Kumaran Timber Centering sheet hire for Site B renovation');
    $response->assertDontSee('data-id="1"', false);
    $response->assertDontSee('data-id="5"', false);
    $response->assertDontSee('1,398');
});
