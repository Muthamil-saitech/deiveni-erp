<?php

namespace App\Repositories\Eloquent;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Retrieve all roles with their menu permissions and user counts.
     *
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return Role::with(['menus', 'users'])->withCount('users')->get();
    }

    /**
     * Find a role by its primary key ID.
     */
    public function findById(int $id): ?Role
    {
        return Role::with(['menus', 'users'])->find($id);
    }

    /**
     * Find a role by its unique slug.
     */
    public function findBySlug(string $slug): ?Role
    {
        return Role::with(['menus', 'users'])->where('slug', $slug)->first();
    }

    /**
     * Create a new role record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return Role::create($data);
    }

    /**
     * Update an existing role record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $role->update($data);

        return $role->refresh();
    }

    /**
     * Delete a role record.
     */
    public function delete(Role $role): bool
    {
        return (bool) $role->delete();
    }

    /**
     * Synchronize menu permissions for a role.
     *
     * @param  array<int, string>  $menus
     */
    public function syncMenus(Role $role, array $menus): void
    {
        $role->syncMenus($menus);
    }
}
