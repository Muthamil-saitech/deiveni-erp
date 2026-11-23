<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\Contracts\ProfileServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        public ProfileServiceInterface $profileService
    ) {}

    /**
     * Display the profile page for the authenticated user.
     */
    public function index(Request $request): View|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role?->value,
                    'role_label' => $user->role?->label(),
                    'role_badge' => $user->role?->badgeClass(),
                    'avatar' => strtoupper(substr($user->name, 0, 2)),
                ],
            ]);
        }

        return view('profile.index', [
            'user' => $user,
        ]);
    }

    /**
     * Update the authenticated user's profile details.
     */
    public function update(UpdateProfileRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $user = $this->profileService->updateProfile($currentUser, $request->validated());

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile information updated successfully (சுயவிவரம் வெற்றிகரமாக மாற்றப்பட்டது)!',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role?->value,
                    'role_label' => $user->role?->label(),
                    'role_badge' => $user->role?->badgeClass(),
                    'avatar' => strtoupper(substr($user->name, 0, 2)),
                ],
            ]);
        }

        return redirect()->route('profile')
            ->with('success', 'Profile information updated successfully (சுயவிவரம் வெற்றிகரமாக மாற்றப்பட்டது)!');
    }

    /**
     * Update the authenticated user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $this->profileService->updatePassword(
            $currentUser,
            (string) $request->validated('current_password'),
            (string) $request->validated('new_password')
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully (கடவுச்சொல் வெற்றிகரமாக மாற்றப்பட்டது)!',
            ]);
        }

        return redirect()->route('profile')
            ->with('success', 'Password updated successfully (கடவுச்சொல் வெற்றிகரமாக மாற்றப்பட்டது)!');
    }
}
