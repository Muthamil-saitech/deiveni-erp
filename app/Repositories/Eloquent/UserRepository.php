<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Find a user by primary key ID.
     */
    public function findById(int $id): ?User
    {
        return User::query()->with('role')->find($id);
    }

    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User
    {
        return User::query()->with('role')->where('email', $email)->first();
    }

    /**
     * Retrieve all users with their roles.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::query()->with('role')->orderBy('id')->get();
    }

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update an existing user's attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh()->load('role');
    }

    /**
     * Update an existing user's password.
     */
    public function updatePassword(User $user, string $hashedPassword): User
    {
        $user->update([
            'password' => $hashedPassword,
        ]);

        return $user->refresh();
    }

    /**
     * Delete a user record.
     */
    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }
}
