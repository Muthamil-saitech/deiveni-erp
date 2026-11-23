<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Common URL aliases to menu keys.
     *
     * @var array<string, string>
     */
    protected array $routeAliases = [
        'bank-master*' => 'banks',
        'branch-master*' => 'branches',
        'property-master*' => 'properties',
        'property-type-master*' => 'property_types',
        'rentals*' => 'property_types',
        'rental-master*' => 'property_types',
        'transaction-type-master*' => 'transaction_types',
        'roles*' => 'users',
        'material-usages*' => 'material_usage',
        'material-usage*' => 'material_usage',
        'stock-transfers*' => 'stock_transfer',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // If unauthenticated guest, redirect to login
        if (! $user) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        // 1. Dynamic Menu Permission Check based on current request URL
        $matchedMenu = $this->resolveMenuKeyForRequest($request);

        if ($matchedMenu) {
            if ($user->canAccess($matchedMenu)) {
                return $next($request);
            }

            // Allow read-only detail/view endpoints if user has access to corresponding report
            if ($this->canAccessRelatedReport($request, $user, $matchedMenu)) {
                return $next($request);
            }

            // Unauthorized response
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action for your role (உங்கள் பங்குக்கு இந்த அணுகல் அனுமதி இல்லை).',
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that module (இந்த பகுதியை அணுக உங்கள் பங்குக்கு அனுமதி இல்லை).');
        }

        // 2. Direct role slug/name match fallback (for routes without specific menu key)
        $userRoleSlug = strtolower($user->roleValue() ?? '');
        $userRoleName = strtolower($user->roleName());

        foreach ($roles as $role) {
            $check = strtolower($role);
            if ($userRoleSlug === $check || $userRoleName === $check) {
                return $next($request);
            }

            // Also allow if $role argument passed is a menu key and user has access to it
            if ($user->canAccess($check)) {
                return $next($request);
            }
        }

        // Unauthorized response
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action for your role (உங்கள் பங்குக்கு இந்த அணுகல் அனுமதி இல்லை).',
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'You do not have permission to access that module (இந்த பகுதியை அணுக உங்கள் பங்குக்கு அனுமதி இல்லை).');
    }

    /**
     * Resolve the menu key for the incoming HTTP request.
     */
    protected function resolveMenuKeyForRequest(Request $request): ?string
    {
        // Special sub-reports dynamic permission matching:
        if ($request->is('reports/admin/profit-loss*')) {
            return 'reports_profit_loss';
        }
        if ($request->is('reports/site/labour-allocations*')) {
            return 'reports_labour_allocations';
        }
        if ($request->is('reports/site/daily-expenses*')) {
            return 'reports_daily_expenses';
        }
        if ($request->is('reports/site/cash-handling*')) {
            return 'reports_cash_handling';
        }
        if ($request->is('reports/site/purchases-inward*')) {
            return 'reports_purchases_inward';
        }
        if ($request->is('reports/site/warehouse-inventory*')) {
            return 'reports_warehouse_inventory';
        }
        if ($request->is('reports/site/stock-transfers*')) {
            return 'reports_stock_transfer';
        }
        if ($request->is('reports/account/ledger*')) {
            return 'reports_ledger';
        }

        // Check predefined alias patterns
        foreach ($this->routeAliases as $pattern => $menuKey) {
            if ($request->is($pattern)) {
                return $menuKey;
            }
        }

        // Check configured system menus
        $menus = config('menus.items', []);

        foreach ($menus as $menuKey => $item) {
            if (! empty($item['url'])) {
                $trimmed = trim(parse_url($item['url'], PHP_URL_PATH), '/');
                if (! empty($trimmed)) {
                    if ($request->is($trimmed) || $request->is($trimmed.'/*')) {
                        return $menuKey;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Determine if the request is viewing a detail record associated with a report the user has access to.
     */
    protected function canAccessRelatedReport(Request $request, $user, string $matchedMenu): bool
    {
        // Only allow GET requests (read-only view)
        if (! $request->isMethod('GET')) {
            return false;
        }

        if ($matchedMenu === 'site_cash' && $request->is('site-cash/*')) {
            return $user->canAccess('reports_cash_handling');
        }

        if ($matchedMenu === 'purchases' && $request->is('purchases/*')) {
            return $user->canAccess('reports_purchases_inward');
        }

        if ($matchedMenu === 'ledger' && $request->is('ledger/*')) {
            return $user->canAccess('reports_ledger');
        }

        if ($matchedMenu === 'sites' && $request->is('sites/*')) {
            return $user->canAccess('reports_cash_handling')
                || $user->canAccess('reports_labour_allocations')
                || $user->canAccess('reports_profit_loss');
        }

        return false;
    }
}
