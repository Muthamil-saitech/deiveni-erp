<?php

namespace App\Listeners;

use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\AuditLogServiceInterface;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;

class LogAuthenticationActivity
{
    public function __construct(
        public AuditLogServiceInterface $auditService
    ) {}

    /**
     * Handle user login event.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        if (! $user instanceof User) {
            return;
        }

        $userIdFormatted = '#USER-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT);
        $roleStr = $this->resolveUserRoleString($user);
        $roleTitle = ucwords(str_replace('_', ' ', $roleStr));

        $this->auditService->log([
            'user' => $user,
            'module' => 'auth',
            'action' => 'LOGIN',
            'severity' => 'info',
            'target_type' => User::class,
            'target_id' => $userIdFormatted,
            'title' => "User {$user->name} ({$roleTitle}) logged in to the system",
            'title_ta' => "பயனாளர் {$user->name} கணினியில் வெற்றிகரமாக உள்நுழைந்தார்",
            'site_name' => 'Head Office',
            'details' => [
                'email' => $user->email,
                'role' => $roleStr,
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ],
        ]);
    }

    /**
     * Handle user logout event.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user ?? Auth::user();
        if (! $user instanceof User) {
            return;
        }

        $userIdFormatted = '#USER-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT);
        $roleStr = $this->resolveUserRoleString($user);
        $roleTitle = ucwords(str_replace('_', ' ', $roleStr));

        $this->auditService->log([
            'user' => $user,
            'module' => 'auth',
            'action' => 'LOGOUT',
            'severity' => 'info',
            'target_type' => User::class,
            'target_id' => $userIdFormatted,
            'title' => "User {$user->name} ({$roleTitle}) logged out from session",
            'title_ta' => "பயனாளர் {$user->name} அமர்விலிருந்து வெளியேறினார்",
            'site_name' => 'Head Office',
            'details' => [
                'email' => $user->email,
                'role' => $roleStr,
            ],
        ]);
    }

    /**
     * Resolve a user's role to a concise string representation.
     */
    private function resolveUserRoleString(User $user): string
    {
        $rawRole = $user->role;
        if ($rawRole instanceof \BackedEnum) {
            return (string) $rawRole->value;
        }

        if ($rawRole instanceof Role) {
            return (string) ($rawRole->name ?? $rawRole->slug ?? 'user');
        }

        return (string) ($rawRole ?? 'user');
    }
}
