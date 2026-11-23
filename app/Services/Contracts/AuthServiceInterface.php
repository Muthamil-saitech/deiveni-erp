<?php

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Attempt to authenticate a user with credentials.
     *
     * @param  array{email: string, password: string}  $credentials
     */
    public function authenticate(array $credentials, bool $remember = false): bool;

    /**
     * Terminate the user session and log out.
     */
    public function logout(): void;

    /**
     * Retrieve the currently authenticated user.
     */
    public function getAuthenticatedUser(): ?User;
}
