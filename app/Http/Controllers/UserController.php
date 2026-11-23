<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\Contracts\RoleServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        public UserServiceInterface $userService,
        public RoleServiceInterface $roleService
    ) {}

    /**
     * Display a listing of system users and dynamic roles.
     */
    public function index(Request $request): View|JsonResponse
    {
        $users = $this->userService->getAllUsers();
        $roles = $this->roleService->getAllRoles();
        $menuSections = $this->roleService->getMenuSections();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'users' => $users,
                    'roles' => $roles,
                ],
            ]);
        }

        return view('users.index', [
            'users' => $users,
            'roles' => $roles,
            'menuSections' => $menuSections,
        ]);
    }

    /**
     * Store a newly created system user.
     */
    public function store(StoreUserRequest $request): JsonResponse|RedirectResponse
    {
        $user = $this->userService->createUser($request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "User \"{$user->name}\" created successfully with role \"{$user->roleName()}\"!",
                'data' => $user,
            ], 201);
        }

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" created successfully!");
    }

    /**
     * Display the specified user details.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role_id' => $user->role_id,
                'role' => $user->roleValue(),
                'status' => $user->status,
            ],
        ]);
    }

    /**
     * Update the specified user account.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "User \"{$user->name}\" updated successfully!",
                    'data' => $user,
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', "User \"{$user->name}\" updated successfully!");
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
     * Remove the specified user.
     */
    public function destroy(Request $request, int $id): JsonResponse|RedirectResponse
    {
        try {
            $deleted = $this->userService->deleteUser($id);

            if (! $deleted) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found or could not be deleted.',
                    ], 404);
                }

                return redirect()->route('users.index')
                    ->with('error', 'User not found.');
            }

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User account deleted successfully!',
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', 'User account deleted successfully!');
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
     * Reset the specified user password.
     */
    public function resetPassword(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6'],
        ]);

        try {
            $user = $this->userService->resetPassword($id, $validated['password']);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Password for \"{$user->name}\" reset successfully!",
                ]);
            }

            return redirect()->route('users.index')
                ->with('success', "Password for \"{$user->name}\" reset successfully!");
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
