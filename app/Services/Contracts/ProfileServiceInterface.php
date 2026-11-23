<?php

namespace App\Services\Contracts;

use App\Models\User;

interface ProfileServiceInterface
{
    /**
     * Retrieve profile information for the given user.
     */
    public function getProfile(User $user): User;

    /**
     * Update personal profile information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User;

    /**
     * Update the user's password after verifying the current password.
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): User;
}
