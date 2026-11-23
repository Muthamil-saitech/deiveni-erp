<?php

namespace App\Services;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\Contracts\RoleServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class RoleService implements RoleServiceInterface
{
    public function __construct(
        public RoleRepositoryInterface $roleRepository
    ) {}

    /**
     * Retrieve all roles with menus and user counts.
     *
     * @return Collection<int, Role>
     */
    public function getAllRoles(): Collection
    {
        return $this->roleRepository->all();
    }

    /**
     * Find a role by ID.
     */
    public function getRoleById(int $id): ?Role
    {
        return $this->roleRepository->findById($id);
    }

    /**
     * Create a new role with assigned menu permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role
    {
        $roleData = [
            'name' => trim($data['name']),
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ];

        $role = $this->roleRepository->create($roleData);

        $menus = $data['menus'] ?? [];
        if (is_array($menus)) {
            // Non-owner roles can never have administration access (Owner Only)
            $menus = array_values(array_diff($menus, ['users', 'audit_logs']));
            $this->roleRepository->syncMenus($role, $menus);
        }

        return $role->load('menus');
    }

    /**
     * Update an existing role with assigned menu permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRole(int $id, array $data): Role
    {
        $role = $this->roleRepository->findById($id);

        if (! $role) {
            throw new \InvalidArgumentException("Role with ID {$id} not found.");
        }

        $updateData = [
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
        ];

        if (! $role->is_system) {
            $updateData['slug'] = Str::slug($data['name']);
        }

        $role = $this->roleRepository->update($role, $updateData);

        if (isset($data['menus']) && is_array($data['menus'])) {
            $menus = $data['menus'];
            $isOwner = $role->slug === 'owner' || strtolower($role->name) === 'owner';

            if ($isOwner) {
                // Owner always retains administration access
                if (! in_array('users', $menus, true)) {
                    $menus[] = 'users';
                }
                if (! in_array('audit_logs', $menus, true)) {
                    $menus[] = 'audit_logs';
                }
            } else {
                // Non-owner roles cannot have administration access (Owner Only)
                $menus = array_values(array_diff($menus, ['users', 'audit_logs']));
            }

            $this->roleRepository->syncMenus($role, $menus);
        }

        return $role->load('menus');
    }

    /**
     * Delete a role.
     */
    public function deleteRole(int $id): bool
    {
        $role = $this->roleRepository->findById($id);

        if (! $role) {
            return false;
        }

        if ($role->is_system) {
            throw new \InvalidArgumentException("System role '{$role->name}' cannot be deleted.");
        }

        if ($role->users()->count() > 0) {
            throw new \InvalidArgumentException("Cannot delete role '{$role->name}' because active users are assigned to it.");
        }

        return $this->roleRepository->delete($role);
    }

    /**
     * Get system menus grouped by section.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getMenuSections(): array
    {
        $menus = config('menus.items', []);
        $grouped = [];

        foreach ($menus as $key => $item) {
            $section = $item['section'] ?? 'OTHER';
            $grouped[$section][$key] = $item;
        }

        return $grouped;
    }
}
