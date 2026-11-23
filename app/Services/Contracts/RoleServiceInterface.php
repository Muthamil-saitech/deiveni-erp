<?php

namespace App\Services\Contracts;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface RoleServiceInterface
{
    /**
     * Retrieve all roles with menus and user counts.
     *
     * @return Collection<int, Role>
     */
    public function getAllRoles(): Collection;

    /**
     * Find a role by ID.
     */
    public function getRoleById(int $id): ?Role;

    /**
     * Create a new role with assigned menu permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role;

    /**
     * Update an existing role with assigned menu permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRole(int $id, array $data): Role;

    /**
     * Delete a role.
     */
    public function deleteRole(int $id): bool;

    /**
     * Get system menus grouped by section.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMenuSections(): array;
}
