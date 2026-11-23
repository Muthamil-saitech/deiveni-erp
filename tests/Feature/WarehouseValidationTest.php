<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WarehouseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => UserRole::Owner,
        'status' => 'Active',
    ]);
});

test('store warehouse item validation fails with bilingual error messages when fields are empty', function () {
    $response = $this->actingAs($this->user)->postJson('/warehouse/items', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'sku',
        'category',
        'name_en',
        'name_ta',
        'current_stock',
        'unit',
        'min_threshold',
        'cash_amount',
    ]);

    $errors = $response->json('errors');
    expect($errors['sku'][0])->toContain('SKU / Item code is required')->toContain('SKU குறியீடு அவசியம்');
    expect($errors['category'][0])->toContain('Please select a category')->toContain('பிரிவை தேர்வு செய்க');
    expect($errors['name_en'][0])->toContain('Item name in English is required')->toContain('பொருளின் ஆங்கிலப் பெயர் அவசியம்');
    expect($errors['name_ta'][0])->toContain('Item name in Tamil is required')->toContain('பொருளின் தமிழ் பெயர் அவசியம்');
    expect($errors['current_stock'][0])->toContain('Initial stock is required')->toContain('இருப்பு அளவு அவசியம்');
    expect($errors['unit'][0])->toContain('Unit of measure is required')->toContain('அளவீட்டு அலகு அவசியம்');
    expect($errors['min_threshold'][0])->toContain('Minimum threshold is required')->toContain('குறைந்தபட்ச இருப்பு அளவு அவசியம்');
    expect($errors['cash_amount'][0])->toContain('Cash amount / unit price is required')->toContain('விலை / அலகுத் தொகை அவசியம்');
});

test('store warehouse item validates unique sku and negative numeric values with bilingual errors', function () {
    WarehouseItem::create([
        'sku' => 'WH-EXIST-01',
        'name_en' => 'Existing Tape',
        'name_ta' => 'டேப்',
        'category' => 'Hand Tools',
        'unit' => 'Nos',
        'current_stock' => 10,
        'min_threshold' => 2,
        'cash_amount' => 100.00,
    ]);

    $payload = [
        'sku' => 'wh-exist-01', // Should trigger unique constraint regardless of casing
        'name_en' => 'New Item',
        'name_ta' => 'புதிய பொருள்',
        'category' => 'Hand Tools',
        'unit' => 'Nos',
        'current_stock' => -5,
        'min_threshold' => -1,
        'cash_amount' => -50,
    ];

    $response = $this->actingAs($this->user)->postJson('/warehouse/items', $payload);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sku', 'current_stock', 'min_threshold', 'cash_amount']);

    $errors = $response->json('errors');
    expect($errors['sku'][0])->toContain('This SKU / Item code already exists')->toContain('இந்த SKU குறியீடு ஏற்கனவே உள்ளது');
    expect($errors['current_stock'][0])->toContain('Initial stock must be 0 or more')->toContain('இருப்பு 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்');
    expect($errors['min_threshold'][0])->toContain('Minimum threshold must be 0 or more')->toContain('குறைந்தபட்ச இருப்பு 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்');
    expect($errors['cash_amount'][0])->toContain('Cash amount must be 0 or more')->toContain('விலை 0 அல்லது அதற்கு மேல் இருக்க வேண்டும்');
});

test('update warehouse item validation validates unique sku ignoring itself and fails on other existing sku', function () {
    $item1 = WarehouseItem::create([
        'sku' => 'WH-SKU-001',
        'name_en' => 'Item 1',
        'name_ta' => 'பொருள் 1',
        'category' => 'Hand Tools',
        'unit' => 'Nos',
        'current_stock' => 10,
        'min_threshold' => 2,
        'cash_amount' => 100.00,
    ]);

    $item2 = WarehouseItem::create([
        'sku' => 'WH-SKU-002',
        'name_en' => 'Item 2',
        'name_ta' => 'பொருள் 2',
        'category' => 'Hand Tools',
        'unit' => 'Nos',
        'current_stock' => 15,
        'min_threshold' => 3,
        'cash_amount' => 150.00,
    ]);

    // Updating item1 with same SKU should succeed
    $updatePayloadSameSku = [
        'sku' => 'WH-SKU-001',
        'name_en' => 'Item 1 Updated',
        'name_ta' => 'பொருள் 1 புதுப்பிக்கப்பட்டது',
        'category' => 'Hand Tools',
        'unit' => 'Nos',
        'current_stock' => 12,
        'min_threshold' => 4,
        'cash_amount' => 110.00,
    ];

    $successRes = $this->actingAs($this->user)->putJson("/warehouse/items/{$item1->id}", $updatePayloadSameSku);
    $successRes->assertStatus(200);
    $successRes->assertJsonPath('success', true);

    // Updating item1 with item2's SKU should fail with bilingual unique error
    $updatePayloadDuplicateSku = [
        'sku' => 'WH-SKU-002',
        'name_en' => 'Item 1 Conflict',
        'name_ta' => 'பொருள் 1 முரண்பாடு',
        'category' => 'Hand Tools',
        'unit' => 'Nos',
        'current_stock' => 12,
        'min_threshold' => 4,
        'cash_amount' => 110.00,
    ];

    $failRes = $this->actingAs($this->user)->putJson("/warehouse/items/{$item1->id}", $updatePayloadDuplicateSku);
    $failRes->assertStatus(422);
    $failRes->assertJsonValidationErrors(['sku']);
    expect($failRes->json('errors.sku.0'))->toContain('This SKU / Item code already exists')->toContain('இந்த SKU குறியீடு ஏற்கனவே உள்ளது');
});
