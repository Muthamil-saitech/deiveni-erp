<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Services\Contracts\RoleServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct(
        public RoleServiceInterface $roleService
    ) {}

    /**
     * Display a listing of roles with menus.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleService->getAllRoles();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse|RedirectResponse
    {
        $role = $this->roleService->createRole($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Role \"{$role->name}\" created successfully with assigned menu permissions!",
                'data' => $role,
            ], 201);
        }

        return redirect()->route('users.index')
            ->with('success', "Role \"{$role->name}\" created successfully!");
    }

    /**
     * Display the specified role with its granted menu keys.
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'menus' => $role->menus->pluck('menu_key')->all(),
            ],
        ]);
    }

    /**
     * Update the specified role and its menu permissions.
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $role = $this->roleService->updateRole($id, $request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Role \"{$role->name}\" updated successfully!",
                    'data' => $role,
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', "Role \"{$role->name}\" updated successfully!");
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('users.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $deleted = $this->roleService->deleteRole($id);

            if (! $deleted) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role not found or could not be deleted.',
                    ], 404);
                }

                return redirect()->route('users.index')
                    ->with('error', 'Role not found.');
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role deleted successfully!',
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'Role deleted successfully!');
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('users.index')
                ->with('error', $e->getMessage());
        }
    }
}
