<?php

namespace App\Repositories\Contracts;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    /**
     * Retrieve all roles with their menu permissions.
     *
     * @return Collection<int, Role>
     */
    public function all(): Collection;

    /**
     * Find a role by its primary key ID.
     */
    public function findById(int $id): ?Role;

    /**
     * Find a role by its unique slug.
     */
    public function findBySlug(string $slug): ?Role;

    /**
     * Create a new role record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role;

    /**
     * Update an existing role record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role;

    /**
     * Delete a role record.
     */
    public function delete(Role $role): bool;

    /**
     * Synchronize menu permissions for a role.
     *
     * @param  array<int, string>  $menus
     */
    public function syncMenus(Role $role, array $menus): void;
}
