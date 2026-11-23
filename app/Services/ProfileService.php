<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\ProfileServiceInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileService implements ProfileServiceInterface
{
    public function __construct(
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Retrieve profile information for the given user.
     */
    public function getProfile(User $user): User
    {
        return $user;
    }

    /**
     * Update personal profile information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = trim((string) $data['name']);
        }

        if (array_key_exists('email', $data)) {
            $attributes['email'] = strtolower(trim((string) $data['email']));
        }

        if (array_key_exists('phone', $data)) {
            $attributes['phone'] = $data['phone'] !== null ? trim((string) $data['phone']) : null;
        }

        return $this->userRepository->update($user, $attributes);
    }

    /**
     * Update the user's password after verifying the current password.
     *
     * @throws ValidationException
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): User
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The provided current password does not match our records.'),
            ]);
        }

        return $this->userRepository->updatePassword($user, Hash::make($newPassword));
    }
}
