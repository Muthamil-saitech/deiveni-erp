<?php

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ProfileService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

test('profile service returns user profile', function () {
    $repository = app(UserRepositoryInterface::class);
    $service = new ProfileService($repository);

    $user = $repository->findByEmail('sm@deiveni.com');
    expect($user)->not->toBeNull();

    $profile = $service->getProfile($user);
    expect($profile->id)->toBe($user->id)
        ->and($profile->email)->toBe('sm@deiveni.com');
});

test('profile service updates personal details correctly', function () {
    $repository = app(UserRepositoryInterface::class);
    $service = new ProfileService($repository);

    $user = $repository->findByEmail('sm@deiveni.com');
    expect($user)->not->toBeNull();

    $updatedUser = $service->updateProfile($user, [
        'name' => 'Murugan Senior Site Manager',
        'email' => 'murugan.sm@deiveni.com',
        'phone' => '98400 99999',
    ]);

    expect($updatedUser->name)->toBe('Murugan Senior Site Manager')
        ->and($updatedUser->email)->toBe('murugan.sm@deiveni.com')
        ->and($updatedUser->phone)->toBe('98400 99999');
});

test('profile service updates password when current password matches', function () {
    $repository = app(UserRepositoryInterface::class);
    $service = new ProfileService($repository);

    $user = $repository->findByEmail('sm@deiveni.com');
    expect($user)->not->toBeNull();

    $updatedUser = $service->updatePassword($user, 'password123', 'newsecurepassword456');

    expect(Hash::check('newsecurepassword456', $updatedUser->password))->toBeTrue();
});

test('profile service throws validation exception when current password does not match', function () {
    $repository = app(UserRepositoryInterface::class);
    $service = new ProfileService($repository);

    $user = $repository->findByEmail('sm@deiveni.com');
    expect($user)->not->toBeNull();

    $service->updatePassword($user, 'wrongpassword', 'newsecurepassword456');
})->throws(ValidationException::class);
