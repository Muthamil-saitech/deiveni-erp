<?php

namespace App\Http\Controllers;

use App\Services\Contracts\AuditLogServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        public AuditLogServiceInterface $auditService
    ) {}

    /**
     * Display a listing of audit logs with filtering and KPI metrics.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = [
            'search' => $request->query('search', ''),
            'module' => $request->query('module', 'all'),
            'action' => $request->query('action', 'all'),
            'user' => $request->query('user', 'all'),
            'severity' => $request->query('severity', 'all'),
            'period' => $request->query('period', 'all'),
        ];

        $logs = $this->auditService->getLogs($filters, 15);
        $kpiStats = $this->auditService->getKpiStats();
        $users = $this->auditService->getDistinctUsers();

        if ($request->ajax() || $request->wantsJson()) {
            $tableHtml = view('audit-logs._rows', compact('logs'))->render();
            $paginationHtml = $logs->links('pagination::bootstrap-5')->render();

            return response()->json([
                'status' => 'success',
                'html' => $tableHtml,
                'pagination' => $paginationHtml,
                'kpi' => $kpiStats,
                'total' => $logs->total(),
                'first_item' => $logs->firstItem() ?: 0,
                'last_item' => $logs->lastItem() ?: 0,
                'has_records' => $logs->total() > 0,
            ]);
        }

        return view('audit-logs.index', compact('logs', 'kpiStats', 'users', 'filters'));
    }

    /**
     * Display the specified audit log record detail.
     */
    public function show(string|int $id): JsonResponse
    {
        $log = $this->auditService->getLogDetails($id);

        if (! $log) {
            return response()->json([
                'status' => 'error',
                'message' => 'Audit log record not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'event_id' => $log->event_id,
                'timestamp' => $log->created_at ? $log->created_at->format('d M Y, h:i:s A') : 'N/A',
                'iso_timestamp' => $log->created_at?->toIso8601String(),
                'actor' => [
                    'id' => $log->user_id,
                    'name' => $log->user_name,
                    'role' => ucwords(str_replace('_', ' ', (string) $log->user_role)),
                    'ip' => $log->ip_address ?: '127.0.0.1',
                    'user_agent' => $log->user_agent,
                ],
                'module' => $log->module,
                'target_id' => $log->target_id ?: 'N/A',
                'target_type' => $log->target_type,
                'action' => $log->action,
                'action_badge_class' => $log->action_badge_class,
                'severity' => strtoupper((string) $log->severity),
                'title' => $log->title,
                'title_ta' => $log->title_ta,
                'site_name' => $log->site_name ?: 'Head Office',
                'amount' => $log->amount,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'details' => $log->details,
                'integrity_hash' => $log->integrity_hash,
            ],
        ]);
    }

    /**
     * Export audit trail records as CSV, Excel, or JSON.
     */
    public function export(Request $request): Response
    {
        $filters = [
            'search' => $request->query('search', ''),
            'module' => $request->query('module', 'all'),
            'action' => $request->query('action', 'all'),
            'user' => $request->query('user', 'all'),
            'severity' => $request->query('severity', 'all'),
            'period' => $request->query('period', 'all'),
        ];

        $format = $request->query('format', $request->input('exportFormat', 'csv'));
        $result = $this->auditService->export($filters, (string) $format);

        return response($result['content'], 200, [
            'Content-Type' => $result['content_type'],
            'Content-Disposition' => "attachment; filename=\"{$result['filename']}\"",
        ]);
    }

    /**
     * Simulate a new realistic audit activity event.
     */
    public function simulate(): JsonResponse
    {
        $log = $this->auditService->simulateActivity();

        return response()->json([
            'status' => 'success',
            'message' => "Simulated event recorded: {$log->event_id}",
            'data' => [
                'event_id' => $log->event_id,
                'user' => $log->user_name,
                'action' => $log->action,
                'title' => $log->title,
            ],
        ]);
    }
}
