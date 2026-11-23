<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\Contracts\ProfileServiceInterface;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('unauthenticated user cannot access profile page and is redirected to login', function () {
    $response = $this->get('/profile');

    $response->assertRedirect(route('login'));
});

test('service and repository interfaces for profile are properly bound in service container', function () {
    $repository = app(UserRepositoryInterface::class);
    $service = app(ProfileServiceInterface::class);

    expect($repository)->toBeInstanceOf(UserRepository::class)
        ->and($service)->toBeInstanceOf(ProfileService::class);
});

test('authenticated user can view profile page and sees their own data', function () {
    $user = User::factory()->create([
        'name' => 'Murugan SM',
        'email' => 'sm@deiveni.com',
        'phone' => '98400 22222',
        'role' => UserRole::SiteManager,
    ]);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertStatus(200);
    $response->assertSee('Murugan SM');
    $response->assertSee('sm@deiveni.com');
    $response->assertSee('Site Manager');
    $response->assertSee('98400 22222');
});

test('profile endpoint returns authenticated user data when requested via json', function () {
    $user = User::factory()->create([
        'name' => 'K. Deiveni',
        'email' => 'owner@deiveni.com',
        'role' => UserRole::Owner,
    ]);

    $response = $this->actingAs($user)->getJson('/profile');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'K. Deiveni',
            'email' => 'owner@deiveni.com',
            'role' => 'owner',
            'role_label' => 'Owner (Admin)',
        ],
    ]);
});

test('updating profile requires mandatory fields and validates data format', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/profile', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'email']);
});

test('updating profile validates phone format if provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->putJson('/profile', [
        'name' => 'Updated Name',
        'email' => $user->email,
        'phone' => 'invalid-phone-abc',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['phone']);
});

test('updating profile prevents duplicate email already taken by another user', function () {
    $user1 = User::factory()->create(['email' => 'first@deiveni.com']);
    $user2 = User::factory()->create(['email' => 'second@deiveni.com']);

    $response = $this->actingAs($user2)->putJson('/profile', [
        'name' => 'Second User',
        'email' => 'first@deiveni.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['email']);
});

test('updating profile with existing own email succeeds', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'myemail@deiveni.com',
        'phone' => '98400 11111',
    ]);

    $response = $this->actingAs($user)->putJson('/profile', [
        'name' => 'Updated Name',
        'email' => 'myemail@deiveni.com',
        'phone' => '98400 99999',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'data' => [
            'name' => 'Updated Name',
            'email' => 'myemail@deiveni.com',
            'phone' => '98400 99999',
        ],
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'myemail@deiveni.com',
        'phone' => '98400 99999',
    ]);
});

test('updating password requires current password and matching confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('oldpassword123'),
    ]);

    $response = $this->actingAs($user)->putJson('/profile/password', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password', 'new_password', 'new_password_confirmation']);
});

test('updating password fails when current password is incorrect', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correctpassword123'),
    ]);

    $response = $this->actingAs($user)->putJson('/profile/password', [
        'current_password' => 'wrongpassword123',
        'new_password' => 'newsecretpassword123',
        'new_password_confirmation' => 'newsecretpassword123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['current_password']);
});

test('updating password fails when new password does not meet minimum length', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correctpassword123'),
    ]);

    $response = $this->actingAs($user)->putJson('/profile/password', [
        'current_password' => 'correctpassword123',
        'new_password' => 'short',
        'new_password_confirmation' => 'short',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['new_password']);
});

test('updating password fails when new password is identical to current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correctpassword123'),
    ]);

    $response = $this->actingAs($user)->putJson('/profile/password', [
        'current_password' => 'correctpassword123',
        'new_password' => 'correctpassword123',
        'new_password_confirmation' => 'correctpassword123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['new_password']);
});

test('updating password fails when confirmation does not match', function () {
    $user = User::factory()->create([
        'password' => Hash::make('correctpassword123'),
    ]);

    $response = $this->actingAs($user)->putJson('/profile/password', [
        'current_password' => 'correctpassword123',
        'new_password' => 'brandnewpassword123',
        'new_password_confirmation' => 'mismatchpassword123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['new_password_confirmation']);
});

test('authenticated user can successfully update password and authenticate with new credentials', function () {
    $user = User::factory()->create([
        'email' => 'passwordtest@deiveni.com',
        'password' => Hash::make('oldpassword123'),
    ]);

    $response = $this->actingAs($user)->putJson('/profile/password', [
        'current_password' => 'oldpassword123',
        'new_password' => 'newpassword456',
        'new_password_confirmation' => 'newpassword456',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    $user->refresh();
    expect(Hash::check('newpassword456', $user->password))->toBeTrue();
});

test('profile update validation returns bilingual error messages for empty fields and duplicate email', function () {
    $existing = User::factory()->create(['email' => 'existing@deiveni.com']);
    $user = User::factory()->create(['email' => 'current@deiveni.com']);

    $responseEmpty = $this->actingAs($user)->putJson('/profile', []);
    $responseEmpty->assertStatus(422);
    $emptyErrors = $responseEmpty->json('errors');
    expect($emptyErrors['name'][0])->toContain('Full name is required')->toContain('முழு பெயர் அவசியம்');
    expect($emptyErrors['email'][0])->toContain('Email address is required')->toContain('மின்னஞ்சல் முகவரி அவசியம்');

    $responseDuplicate = $this->actingAs($user)->putJson('/profile', [
        'name' => 'Valid Name',
        'email' => 'existing@deiveni.com',
    ]);
    $responseDuplicate->assertStatus(422);
    $dupErrors = $responseDuplicate->json('errors');
    expect($dupErrors['email'][0])
        ->toContain('This email address is already registered')
        ->toContain('இந்த மின்னஞ்சல் ஏற்கனவே பயன்பாட்டில் உள்ளது');

    $responseInvalidPhone = $this->actingAs($user)->putJson('/profile', [
        'name' => 'Valid Name',
        'email' => 'valid@deiveni.com',
        'phone' => 'abc!@#$',
    ]);
    $responseInvalidPhone->assertStatus(422);
    $phoneErrors = $responseInvalidPhone->json('errors');
    expect($phoneErrors['phone'][0])
        ->toContain('Please enter a valid phone number')
        ->toContain('சரியான தொலைபேசி எண் பதிவிடவும்');
});

test('password update validation returns bilingual error messages', function () {
    $user = User::factory()->create([
        'password' => Hash::make('mypassword123'),
    ]);

    $responseEmpty = $this->actingAs($user)->putJson('/profile/password', []);
    $responseEmpty->assertStatus(422);
    $emptyErrors = $responseEmpty->json('errors');
    expect($emptyErrors['current_password'][0])->toContain('Current password is required')->toContain('தற்போதைய கடவுச்சொல் அவசியம்');
    expect($emptyErrors['new_password'][0])->toContain('New password is required')->toContain('புதிய கடவுச்சொல் அவசியம்');
    expect($emptyErrors['new_password_confirmation'][0])->toContain('Please confirm your new password')->toContain('கடவுச்சொல்லை மீண்டும் உள்ளிடவும்');

    $responseMismatch = $this->actingAs($user)->putJson('/profile/password', [
        'current_password' => 'mypassword123',
        'new_password' => 'newpassword123',
        'new_password_confirmation' => 'mismatchpassword456',
    ]);
    $responseMismatch->assertStatus(422);
    $mismatchErrors = $responseMismatch->json('errors');
    expect($mismatchErrors['new_password_confirmation'][0])
        ->toContain('New password and confirmation do not match')
        ->toContain('கடவுச்சொல் உறுதிப்படுத்தல் பொருந்தவில்லை');
});
