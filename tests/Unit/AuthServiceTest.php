<?php

use App\Enums\UserRole;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\AuthService;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

test('user repository retrieves users by email and id', function () {
    $repository = app(UserRepositoryInterface::class);

    expect($repository)->toBeInstanceOf(UserRepository::class);

    $user = $repository->findByEmail('owner@deiveni.com');
    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('owner@deiveni.com')
        ->and($user->role)->toBe(UserRole::Owner);

    $foundById = $repository->findById($user->id);
    expect($foundById)->not->toBeNull()
        ->and($foundById->id)->toBe($user->id);
});

test('auth service authenticates valid user via repository and logs in', function () {
    $repository = app(UserRepositoryInterface::class);
    $authService = new AuthService($repository);

    $result = $authService->authenticate([
        'email' => 'sm@deiveni.com',
        'password' => 'password123',
    ]);

    expect($result)->toBeTrue();
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->email)->toBe('sm@deiveni.com');
    expect($authService->getAuthenticatedUser()?->email)->toBe('sm@deiveni.com');

    $authService->logout();
    expect(Auth::check())->toBeFalse();
    expect($authService->getAuthenticatedUser())->toBeNull();
});

test('auth service rejects incorrect password', function () {
    $repository = app(UserRepositoryInterface::class);
    $authService = new AuthService($repository);

    $result = $authService->authenticate([
        'email' => 'sm@deiveni.com',
        'password' => 'incorrect',
    ]);

    expect($result)->toBeFalse();
    expect(Auth::check())->toBeFalse();
});
