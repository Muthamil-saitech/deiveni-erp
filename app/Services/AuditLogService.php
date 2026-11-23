<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ProjectSite;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Services\Contracts\AuditLogServiceInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogService implements AuditLogServiceInterface
{
    /**
     * Guard flag to prevent recursive audit logging.
     */
    public static bool $isAuditing = false;

    /**
     * Counter of events logged in current request cycle.
     */
    public static int $loggedCountInRequest = 0;

    /**
     * In-memory cache to prevent duplicate events within the same request.
     *
     * @var array<string, bool>
     */
    protected static array $loggedThisRequest = [];

    public function __construct(
        public AuditLogRepositoryInterface $repository
    ) {}

    /**
     * Record a new audit log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(array $data): ?AuditLog
    {
        if (self::$isAuditing) {
            return null;
        }

        try {
            if (! Schema::hasTable('audit_logs')) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        self::$isAuditing = true;

        try {
            $actor = $data['user'] ?? Auth::user();
            $userId = $actor instanceof User ? $actor->id : ($data['user_id'] ?? null);
            $userName = $actor instanceof User ? $actor->name : ($data['user_name'] ?? 'System');

            $userRole = 'system';
            if ($actor instanceof User) {
                $userRole = $this->resolveRoleString($actor->role);
            } elseif (! empty($data['user_role'])) {
                $userRole = $this->resolveRoleString($data['user_role']);
            }
            $userRole = Str::limit($userRole ?: 'system', 60, '');

            $ipAddress = $data['ip_address'] ?? (request() ? request()->ip() : '127.0.0.1');
            $userAgent = $data['user_agent'] ?? (request() ? request()->userAgent() : null);

            $datePrefix = Carbon::now()->format('Ymd');
            $randomSuffix = strtoupper(Str::random(4));
            $eventId = $data['event_id'] ?? "AUD-{$datePrefix}-{$randomSuffix}";

            $module = $data['module'] ?? 'general';
            $action = strtoupper((string) ($data['action'] ?? 'ACTIVITY'));
            $severity = $data['severity'] ?? 'info';
            $title = $data['title'] ?? 'System Activity Logged';
            $titleTa = $data['title_ta'] ?? null;
            $targetType = $data['target_type'] ?? null;
            $targetId = $data['target_id'] ?? null;
            $siteName = $data['site_name'] ?? null;
            $amount = isset($data['amount']) ? (float) $data['amount'] : null;
            $oldValues = $data['old_values'] ?? null;
            $newValues = $data['new_values'] ?? null;
            $details = $data['details'] ?? null;

            // In-request deduplication: ignore exact same payload triggered twice in same request
            $signature = md5($userName.'|'.$action.'|'.$module.'|'.($targetId ?? '').'|'.json_encode($oldValues).'|'.json_encode($newValues));
            if (isset(self::$loggedThisRequest[$signature])) {
                return null;
            }
            self::$loggedThisRequest[$signature] = true;
            self::$loggedCountInRequest++;

            // Generate SHA-256 tamper-evident integrity hash
            $hashPayload = json_encode([
                'event_id' => $eventId,
                'user' => $userName,
                'action' => $action,
                'module' => $module,
                'target' => $targetId,
                'time' => Carbon::now()->toIso8601String(),
                'old' => $oldValues,
                'new' => $newValues,
            ]);
            $integrityHash = 'sha256:'.hash('sha256', (string) $hashPayload);

            return $this->repository->create([
                'event_id' => $eventId,
                'user_id' => $userId,
                'user_name' => $userName,
                'user_role' => $userRole,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'module' => $module,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'action' => $action,
                'severity' => $severity,
                'title' => $title,
                'title_ta' => $titleTa,
                'site_name' => $siteName,
                'amount' => $amount,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'details' => $details,
                'integrity_hash' => $integrityHash,
            ]);
        } finally {
            self::$isAuditing = false;
        }
    }

    /**
     * Get paginated audit logs based on filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getLogs(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getFiltered($filters, $perPage);
    }

    /**
     * Retrieve audit log record details.
     */
    public function getLogDetails(int|string $id): ?AuditLog
    {
        if (is_numeric($id)) {
            $log = $this->repository->findById((int) $id);
            if ($log) {
                return $log;
            }
        }

        return $this->repository->findByEventId((string) $id);
    }

    /**
     * Get dashboard KPI metrics.
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(): array
    {
        return $this->repository->getKpiStats();
    }

    /**
     * Get distinct users for filter dropdown.
     */
    public function getDistinctUsers(): Collection
    {
        return $this->repository->getDistinctUsers();
    }

    /**
     * Export audit trail data in CSV, Excel, or JSON format.
     *
     * @param  array<string, mixed>  $filters
     * @return array{filename: string, content: string, content_type: string}
     */
    public function export(array $filters, string $format = 'csv'): array
    {
        // Unlimit pagination for export
        $logs = $this->repository->getFiltered($filters, 10000);
        $dateStr = Carbon::now()->format('Y-m-d_His');

        if (strtolower($format) === 'json') {
            $jsonData = $logs->map(function (AuditLog $log): array {
                return [
                    'event_id' => $log->event_id,
                    'timestamp' => $log->created_at?->toIso8601String(),
                    'actor' => [
                        'user_id' => $log->user_id,
                        'name' => $log->user_name,
                        'role' => $log->user_role,
                        'ip' => $log->ip_address,
                    ],
                    'action' => $log->action,
                    'module' => $log->module,
                    'target_id' => $log->target_id,
                    'severity' => $log->severity,
                    'title' => $log->title,
                    'title_ta' => $log->title_ta,
                    'site_name' => $log->site_name,
                    'amount' => $log->amount,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'details' => $log->details,
                    'integrity_hash' => $log->integrity_hash,
                ];
            })->all();

            return [
                'filename' => "audit_trail_{$dateStr}.json",
                'content' => json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'content_type' => 'application/json',
            ];
        }

        // CSV or Excel format
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel Tamil support
        $headers = [
            'Event ID',
            'Timestamp',
            'Operator / User',
            'Role',
            'IP Address',
            'Module',
            'Target ID',
            'Action',
            'Severity',
            'Activity Description',
            'Tamil Description',
            'Site / Location',
            'Tracked Amount (INR)',
            'SHA-256 Integrity Hash',
        ];

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->event_id,
                $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                $log->user_name,
                $log->user_role,
                $log->ip_address,
                $log->module,
                $log->target_id,
                $log->action,
                strtoupper((string) $log->severity),
                $log->title,
                $log->title_ta,
                $log->site_name,
                $log->amount ? number_format((float) $log->amount, 2) : '',
                $log->integrity_hash,
            ]);
        }

        rewind($handle);
        $csv .= stream_get_contents($handle);
        fclose($handle);

        $isExcel = in_array(strtolower($format), ['excel', 'xlsx', 'xls'], true);

        return [
            'filename' => $isExcel ? "audit_trail_{$dateStr}.csv" : "audit_trail_{$dateStr}.csv",
            'content' => $csv,
            'content_type' => 'text/csv; charset=UTF-8',
        ];
    }

    /**
     * Simulate a realistic activity event for testing/demo.
     */
    public function simulateActivity(): AuditLog
    {
        $site = ProjectSite::first();
        $siteName = $site ? ($site->name ?? $site->site_name ?? 'Site A - Villa 102') : 'Site A - Villa 102';

        $scenarios = [
            [
                'module' => 'site_cash',
                'action' => 'CASH_TXN',
                'severity' => 'info',
                'target_id' => '#TXN-'.Carbon::now()->format('md').'-'.rand(100, 999),
                'title' => 'Disbursed Daily Cash Wages to Mason Gang (₹12,400)',
                'title_ta' => 'கொத்தனார் அணிக்கு தள ரொக்கத்திலிருந்து தினக்கூலி பட்டுவாடா செய்யப்பட்டது',
                'amount' => 12400.00,
                'site_name' => $siteName,
                'old_values' => ['cash_balance' => 24800],
                'new_values' => ['cash_balance' => 12400, 'disbursed' => 12400],
            ],
            [
                'module' => 'daily_wages',
                'action' => 'CREATE',
                'severity' => 'info',
                'target_id' => '#WAGE-'.Carbon::now()->format('md').'-'.rand(10, 99),
                'title' => 'Recorded Daily Labor Muster Sheet: 8 Masons, 12 Helpers',
                'title_ta' => 'தினசரி தொழிலாளர் வருகை மற்றும் வேலை ஒதுக்கீடு பதிவு செய்யப்பட்டது',
                'amount' => 19800.00,
                'site_name' => $siteName,
                'old_values' => null,
                'new_values' => ['masons' => 8, 'helpers' => 12, 'total_wage' => 19800],
            ],
            [
                'module' => 'purchases',
                'action' => 'CREATE',
                'severity' => 'info',
                'target_id' => '#PO-'.Carbon::now()->format('md').'-'.rand(1000, 9999),
                'title' => 'Generated Purchase Order for 150 Bags Cement',
                'title_ta' => '150 மூட்டை சிமெண்ட் கொள்முதல் ஆணை உருவாக்கப்பட்டது',
                'amount' => 58500.00,
                'site_name' => 'Central Warehouse',
                'old_values' => null,
                'new_values' => ['item' => 'Cement OPC 53', 'quantity' => 150, 'rate' => 390],
            ],
            [
                'module' => 'masters',
                'action' => 'UPDATE',
                'severity' => 'critical',
                'target_id' => '#CAT-001',
                'title' => 'Revised Standard Wage Rate for Senior Mason (₹850 -> ₹900)',
                'title_ta' => 'மூத்த கொத்தனார் தினக்கூலி அடிப்படை விகிதம் உயர்த்தப்பட்டது',
                'amount' => null,
                'site_name' => 'Head Office',
                'old_values' => ['daily_rate' => 850],
                'new_values' => ['daily_rate' => 900],
            ],
        ];

        $choice = $scenarios[array_rand($scenarios)];

        return $this->log($choice);
    }

    /**
     * Resolve a raw role value (enum, Role model, or string) to a concise string.
     */
    protected function resolveRoleString(mixed $rawRole): string
    {
        if ($rawRole instanceof \BackedEnum) {
            return (string) $rawRole->value;
        }

        if ($rawRole instanceof Role) {
            return (string) ($rawRole->name ?? $rawRole->slug ?? 'user');
        }

        return (string) ($rawRole ?? 'system');
    }
}
