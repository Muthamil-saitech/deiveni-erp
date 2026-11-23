<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        public AuthServiceInterface $authService
    ) {}

    /**
     * Show the login form as the index page.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if ($this->authService->getAuthenticatedUser() !== null) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = [
            'email' => (string) $request->validated('email'),
            'password' => (string) $request->validated('password'),
        ];

        $remember = $request->boolean('remember');

        if (! $this->authService->authenticate($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => __('These credentials do not match our records.'),
                ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login');
    }
}
