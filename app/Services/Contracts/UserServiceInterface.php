<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    /**
     * Retrieve all system users with their roles.
     *
     * @return Collection<int, User>
     */
    public function getAllUsers(): Collection;

    /**
     * Find a user by ID.
     */
    public function getUserById(int $id): ?User;

    /**
     * Create a new system user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User;

    /**
     * Update an existing system user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUser(int $id, array $data): User;

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): bool;

    /**
     * Reset a user's password.
     */
    public function resetPassword(int $id, string $newPassword): User;
}
