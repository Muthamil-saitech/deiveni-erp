<?php

use App\Models\WorkerCategory;
use App\Repositories\Contracts\WorkerCategoryRepositoryInterface;
use App\Repositories\Eloquent\WorkerCategoryRepository;
use App\Services\Contracts\WorkerCategoryServiceInterface;
use App\Services\WorkerCategoryService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('worker categories index page renders successfully and displays seeded categories', function () {
    $response = $this->get('/worker-categories');

    $response->assertStatus(200);
    $response->assertSee('Worker Trade Categories');
    $response->assertSee('மேஸ்திரி');
    $response->assertSee('CAT-001');
    $response->assertSee('CAT-002');
    $response->assertSee('CAT-003');
    $response->assertSee('CAT-008');
});

test('worker categories are ordered with last added row first', function () {
    $service = app(WorkerCategoryServiceInterface::class);
    $categories = $service->getAllCategories();

    // Verify categories are sorted descending by id
    $ids = $categories->pluck('id')->all();
    $sortedIds = $ids;
    rsort($sortedIds);

    expect($ids)->toBe($sortedIds);

    // After adding a new row, it comes first in the list
    $newCategory = $service->createCategory([
        'name_ta' => 'புதிய தொழிலாளி',
        'name_en' => 'New Worker',
        'daily_wage' => 950.00,
        'shift' => '8 Hours',
        'status' => 'Active',
    ]);

    $updatedCategories = $service->getAllCategories();
    expect($updatedCategories->first()->id)->toBe($newCategory->id)
        ->and($updatedCategories->first()->name_en)->toBe('New Worker');
});

test('service and repository interfaces are properly bound in service container', function () {
    $repository = app(WorkerCategoryRepositoryInterface::class);
    $service = app(WorkerCategoryServiceInterface::class);

    expect($repository)->toBeInstanceOf(WorkerCategoryRepository::class)
        ->and($service)->toBeInstanceOf(WorkerCategoryService::class);
});

test('category code preview endpoint returns sequential code CAT-009', function () {
    $response = $this->getJson('/worker-categories/next-code');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'code' => 'CAT-009',
        ]);
});

test('storing a worker category requires mandatory fields and validates data correctly', function () {
    $response = $this->postJson('/worker-categories', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_ta', 'name_en', 'daily_wage', 'status']);

    $errors = $response->json('errors');
    expect($errors['name_ta'][0])->toContain('Tamil category name is required')->toContain('தமிழ் பெயர்')
        ->and($errors['name_en'][0])->toContain('English category name is required')->toContain('ஆங்கில பெயர்')
        ->and($errors['daily_wage'][0])->toContain('Default daily wage is required')->toContain('தினக்கூலி')
        ->and($errors['status'][0])->toContain('Please select a status')->toContain('நிலை');
});

test('storing a worker category validates daily wage is positive and valid', function () {
    $response = $this->postJson('/worker-categories', [
        'name_ta' => 'டெஸ்ட்',
        'name_en' => 'Test',
        'daily_wage' => -50,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['daily_wage']);
});

test('storing a worker category validates status value', function () {
    $response = $this->postJson('/worker-categories', [
        'name_ta' => 'டெஸ்ட்',
        'name_en' => 'Test',
        'daily_wage' => 500,
        'status' => 'InvalidStatus',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('storing a worker category validates uniqueness of category name in tamil and english', function () {
    // Existing seeded category has name_ta: 'மேஸ்திரி', name_en: 'Head Mason / Lead Mestri'
    $response = $this->postJson('/worker-categories', [
        'name_ta' => 'மேஸ்திரி',
        'name_en' => 'Head Mason / Lead Mestri',
        'daily_wage' => 1200,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_ta', 'name_en']);
});

test('database throws integrity constraint violation when inserting duplicate name_ta or name_en directly', function () {
    expect(function () {
        WorkerCategory::create([
            'name_ta' => 'மேஸ்திரி', // duplicate of CAT-001
            'name_en' => 'Unique English Name',
            'code' => 'CAT-998',
            'daily_wage' => 1000,
            'shift' => '8 Hours',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);

    expect(function () {
        WorkerCategory::create([
            'name_ta' => 'தனித்துவமான தமிழ் பெயர்',
            'name_en' => 'Head Mason / Lead Mestri', // duplicate of CAT-001
            'code' => 'CAT-999',
            'daily_wage' => 1000,
            'shift' => '8 Hours',
            'status' => 'Active',
        ]);
    })->toThrow(QueryException::class);
});

test('updating a worker category validates uniqueness against other categories', function () {
    $category1 = WorkerCategory::where('code', 'CAT-001')->first(); // மேஸ்திரி / Head Mason / Lead Mestri
    $category2 = WorkerCategory::where('code', 'CAT-002')->first(); // கொத்தனார் / Brick Mason / Plasterer

    // Attempting to update CAT-002 with CAT-001's names should fail validation
    $response = $this->putJson("/worker-categories/{$category2->id}", [
        'name_ta' => $category1->name_ta,
        'name_en' => $category1->name_en,
        'daily_wage' => 1100,
        'status' => 'Active',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name_ta', 'name_en']);

    // Updating category keeping its own existing names should succeed
    $validResponse = $this->putJson("/worker-categories/{$category2->id}", [
        'name_ta' => $category2->name_ta,
        'name_en' => $category2->name_en,
        'daily_wage' => 1150,
        'status' => 'Active',
    ]);

    $validResponse->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('category code is auto-generated as CAT-009 and continues sequentially to CAT-010', function () {
    // 1st new category -> should receive CAT-009
    $response1 = $this->postJson('/worker-categories', [
        'name_ta' => 'வெல்டர்',
        'name_en' => 'Welder Specialist',
        'code' => 'CUSTOM-HACK-CODE', // User attempting to supply custom code
        'daily_wage' => 1400.00,
        'shift' => '8 Hours (8:30 AM - 5:30 PM)',
        'status' => 'Active',
    ]);

    $response1->assertStatus(201)
        ->assertJson([
            'success' => true,
        ]);

    $newCategory1 = WorkerCategory::where('name_en', 'Welder Specialist')->first();
    expect($newCategory1)->not->toBeNull()
        ->and($newCategory1->code)->toBe('CAT-009')
        ->and((float) $newCategory1->daily_wage)->toBe(1400.0);

    // 2nd new category -> should continue to CAT-010
    $response2 = $this->postJson('/worker-categories', [
        'name_ta' => 'கிரேன் ஆபரேட்டர்',
        'name_en' => 'Crane Operator',
        'daily_wage' => 1600.00,
        'shift' => '8 Hours',
        'status' => 'Active',
    ]);

    $response2->assertStatus(201);
    $newCategory2 = WorkerCategory::where('name_en', 'Crane Operator')->first();
    expect($newCategory2)->not->toBeNull()
        ->and($newCategory2->code)->toBe('CAT-010');
});

test('can update an existing worker category without altering the category code', function () {
    $category = WorkerCategory::where('code', 'CAT-001')->first();

    $response = $this->putJson("/worker-categories/{$category->id}", [
        'name_ta' => 'தலைமை மேஸ்திரி',
        'name_en' => 'Chief Head Mason',
        'code' => 'ALTERED-CODE', // Attempt to alter code
        'daily_wage' => 1500.00,
        'shift' => '9 Hours',
        'status' => 'Active',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $category->refresh();
    expect($category->name_ta)->toBe('தலைமை மேஸ்திரி')
        ->and($category->name_en)->toBe('Chief Head Mason')
        ->and($category->code)->toBe('CAT-001') // Code remains unchanged
        ->and((float) $category->daily_wage)->toBe(1500.0);
});

test('can delete a worker category', function () {
    $category = WorkerCategory::where('code', 'CAT-008')->first();

    $response = $this->deleteJson("/worker-categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    expect(WorkerCategory::find($category->id))->toBeNull();
});

test('worker categories index page renders table pagination elements and controls', function () {
    $response = $this->get('/worker-categories');

    $response->assertStatus(200);
    $response->assertSee('tablePaginationContainer');
    $response->assertSee('perPageSelect');
    $response->assertSee('paginationNav');
    $response->assertSee('Rows per page:');
    $response->assertSee('paginationSummary');
});
