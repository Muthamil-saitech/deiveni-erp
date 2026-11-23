<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{
    public function __construct(
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Retrieve all system users with their roles.
     *
     * @return Collection<int, User>
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->all();
    }

    /**
     * Find a user by ID.
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Create a new system user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        $userData = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'Active',
            'password' => Hash::make($data['password'] ?? 'Deiveni@2026'),
        ];

        if (! empty($data['role_id'])) {
            $role = Role::find($data['role_id']);
            if ($role) {
                $userData['role_id'] = $role->id;
                $userData['role'] = $role->slug ?? $role->name;
            }
        } elseif (! empty($data['role'])) {
            $role = Role::where('slug', $data['role'])->orWhere('name', $data['role'])->first();
            if ($role) {
                $userData['role_id'] = $role->id;
                $userData['role'] = $role->slug;
            } else {
                $userData['role'] = $data['role'];
            }
        }

        return $this->userRepository->create($userData)->load('role');
    }

    /**
     * Update an existing system user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new \InvalidArgumentException("User with ID {$id} not found.");
        }

        $updateData = [
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'Active',
        ];

        if (! empty($data['role_id'])) {
            $role = Role::find($data['role_id']);
            if ($role) {
                $updateData['role_id'] = $role->id;
                $updateData['role'] = $role->slug ?? $role->name;
            }
        } elseif (! empty($data['role'])) {
            $role = Role::where('slug', $data['role'])->orWhere('name', $data['role'])->first();
            if ($role) {
                $updateData['role_id'] = $role->id;
                $updateData['role'] = $role->slug;
            } else {
                $updateData['role'] = $data['role'];
            }
        }

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        return $this->userRepository->update($user, $updateData);
    }

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            return false;
        }

        // Prevent deleting the main system owner
        if ($user->isAdmin() && $user->email === 'owner@deiveni.com') {
            throw new \InvalidArgumentException('Primary Executive Owner account cannot be deleted.');
        }

        return $this->userRepository->delete($user);
    }

    /**
     * Reset a user's password.
     */
    public function resetPassword(int $id, string $newPassword): User
    {
        $user = $this->userRepository->findById($id);

        if (! $user) {
            throw new \InvalidArgumentException("User with ID {$id} not found.");
        }

        return $this->userRepository->updatePassword($user, Hash::make($newPassword));
    }
}
