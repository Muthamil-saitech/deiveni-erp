<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use App\Services\Contracts\AuditLogServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditFormActivityMiddleware
{
    public function __construct(
        public AuditLogServiceInterface $auditService
    ) {}

    /**
     * Handle an incoming request and ensure all form-based activities are logged.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only monitor write operations (form submissions)
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        // Reset request-level audit log counter
        AuditLogService::$loggedCountInRequest = 0;

        $response = $next($request);

        // Only log for authenticated users on successful or redirect responses
        if (! Auth::check()) {
            return $response;
        }

        if (! ($response->isSuccessful() || $response->isRedirection())) {
            return $response;
        }

        $path = trim($request->path(), '/');

        // Skip internal/auth/audit endpoints that have specialized listeners
        if (
            str_starts_with($path, '_boost') ||
            str_starts_with($path, 'audit-logs') ||
            in_array($path, ['login', 'logout'], true)
        ) {
            return $response;
        }

        // If an Eloquent Model event was already captured during this request, no need for redundant log
        if (AuditLogService::$loggedCountInRequest > 0) {
            return $response;
        }

        // Capture form submission that didn't trigger a model observer
        $this->recordFormActivity($request, $path);

        return $response;
    }

    /**
     * Record a generic form activity event.
     */
    protected function recordFormActivity(Request $request, string $path): void
    {
        $segments = explode('/', $path);
        $baseSegment = $segments[0] ?? 'general';

        $moduleMap = [
            'site-cash' => 'site_cash',
            'daily-allocation' => 'daily_wages',
            'weekly-wages' => 'daily_wages',
            'purchases' => 'purchases',
            'ledger' => 'ledger',
            'sites' => 'sites',
            'warehouse' => 'warehouse',
            'users' => 'users',
            'roles' => 'users',
            'profile' => 'users',
            'worker-categories' => 'masters',
            'units' => 'masters',
            'materials' => 'masters',
            'suppliers' => 'masters',
            'banks' => 'masters',
            'branches' => 'masters',
            'properties' => 'masters',
            'property-types' => 'masters',
            'transaction-types' => 'masters',
        ];

        $module = $moduleMap[$baseSegment] ?? 'general';

        $method = $request->method();
        $action = match ($method) {
            'POST' => 'CREATE',
            'PUT', 'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE',
            default => 'ACTIVITY',
        };

        // Resolve target ID from route parameters or request input
        $targetId = null;
        if ($request->route('id')) {
            $targetId = '#ID-'.$request->route('id');
        } elseif ($request->filled('code')) {
            $targetId = '#CODE-'.$request->input('code');
        } elseif ($request->filled('name')) {
            $targetId = '#'.strtoupper($baseSegment).'-'.$request->input('name');
        } else {
            $targetId = '#'.strtoupper($baseSegment);
        }

        $formName = ucwords(str_replace(['-', '_'], ' ', $baseSegment));
        $actionVerb = match ($action) {
            'CREATE' => 'Created / Submitted',
            'UPDATE' => 'Updated form details for',
            'DELETE' => 'Deleted record via',
            default => 'Submitted',
        };

        $title = "{$actionVerb} {$formName} ({$targetId})";
        $titleTa = "{$formName} படிவ செயல்முறை ({$targetId}) பதிவு செய்யப்பட்டது";

        $sanitizedInputs = $request->except(['_token', '_method', 'password', 'password_confirmation']);

        $amount = null;
        if ($request->filled('amount')) {
            $amount = (float) $request->input('amount');
        } elseif ($request->filled('total_amount')) {
            $amount = (float) $request->input('total_amount');
        }

        $siteName = $request->input('site_name') ?? 'Head Office';

        $this->auditService->log([
            'module' => $module,
            'action' => $action,
            'severity' => in_array($action, ['UPDATE', 'DELETE'], true) ? 'warning' : 'info',
            'target_id' => $targetId,
            'title' => $title,
            'title_ta' => $titleTa,
            'site_name' => $siteName,
            'amount' => $amount,
            'old_values' => null,
            'new_values' => $sanitizedInputs,
            'details' => [
                'endpoint' => $request->path(),
                'method' => $method,
                'ip' => $request->ip(),
            ],
        ]);
    }
}
