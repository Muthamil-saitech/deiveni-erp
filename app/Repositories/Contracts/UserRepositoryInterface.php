<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Find a user by primary key ID.
     */
    public function findById(int $id): ?User;

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Retrieve all users with their roles.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Update an existing user's attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    /**
     * Update an existing user's password.
     */
    public function updatePassword(User $user, string $hashedPassword): User;

    /**
     * Delete a user record.
     */
    public function delete(User $user): bool;
}
