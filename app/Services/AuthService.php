<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        public UserRepositoryInterface $userRepository
    ) {}

    /**
     * Attempt to authenticate a user with credentials.
     *
     * @param  array{email: string, password: string}  $credentials
     *
     * @throws ValidationException
     */
    public function authenticate(array $credentials, bool $remember = false): bool
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user) {
            return false;
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return false;
        }

        if ($user->status && strtolower($user->status) !== 'active') {
            throw ValidationException::withMessages([
                'email' => __('Your account is inactive. Please contact the administrator.'),
            ]);
        }

        Auth::login($user, $remember);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        return true;
    }

    /**
     * Terminate the user session and log out.
     */
    public function logout(): void
    {
        Auth::logout();

        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    /**
     * Retrieve the currently authenticated user.
     */
    public function getAuthenticatedUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }
}
