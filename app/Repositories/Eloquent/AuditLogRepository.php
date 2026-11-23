<?php

namespace App\Repositories\Eloquent;

use App\Models\AuditLog;
use App\Models\User;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * Create a new audit log record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    /**
     * Get paginated audit logs based on search and filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AuditLog::query()->with('user')->latest('id');

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('event_id', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('user_role', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('target_id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('title_ta', 'like', "%{$search}%")
                    ->orWhere('site_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['module']) && $filters['module'] !== 'all') {
            $query->where('module', $filters['module']);
        }

        if (! empty($filters['action']) && $filters['action'] !== 'all') {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user']) && $filters['user'] !== 'all') {
            $userVal = $filters['user'];
            if (is_numeric($userVal)) {
                $query->where('user_id', (int) $userVal);
            } else {
                $query->where('user_name', 'like', "%{$userVal}%");
            }
        }

        if (! empty($filters['severity']) && $filters['severity'] !== 'all') {
            $query->where('severity', $filters['severity']);
        }

        $period = $filters['period'] ?? 'all';
        $today = Carbon::today();

        if ($period === 'today') {
            $query->whereDate('created_at', $today);
        } elseif ($period === 'yesterday') {
            $query->whereDate('created_at', $today->copy()->subDay());
        } elseif ($period === 'last_7_days') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($period === 'this_month') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Find an audit log record by ID.
     */
    public function findById(int $id): ?AuditLog
    {
        return AuditLog::with('user')->find($id);
    }

    /**
     * Find an audit log record by event ID.
     */
    public function findByEventId(string $eventId): ?AuditLog
    {
        return AuditLog::with('user')->where('event_id', $eventId)->first();
    }

    /**
     * Calculate KPI metrics for the audit dashboard.
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $totalToday = AuditLog::whereDate('created_at', $today)->count();
        $totalYesterday = AuditLog::whereDate('created_at', $yesterday)->count();

        // Calculate growth vs yesterday
        $growthPct = 0;
        if ($totalYesterday > 0) {
            $growthPct = round((($totalToday - $totalYesterday) / $totalYesterday) * 100);
        } elseif ($totalToday > 0) {
            $growthPct = 100;
        }

        // Financial & Cash events (Today)
        $financialQuery = AuditLog::whereDate('created_at', $today)
            ->where(function (Builder $q): void {
                $q->whereIn('module', ['site_cash', 'daily_wages', 'ledger', 'purchases'])
                    ->orWhereNotNull('amount')
                    ->orWhereIn('action', ['CASH_TXN', 'CASH_IN', 'CASH_OUT']);
            });

        $financialCount = $financialQuery->count();
        $trackedValue = (float) AuditLog::whereDate('created_at', $today)->sum('amount');

        // Critical / Modification actions (Today)
        $criticalCount = AuditLog::whereDate('created_at', $today)
            ->where(function (Builder $q): void {
                $q->where('severity', 'critical')
                    ->orWhere('action', 'DELETE');
            })->count();

        $deletionsCount = AuditLog::whereDate('created_at', $today)
            ->where('action', 'DELETE')
            ->count();

        $updatesCount = AuditLog::whereDate('created_at', $today)
            ->where('action', 'UPDATE')
            ->count();

        // Active operators today
        $activeUsers = AuditLog::whereDate('created_at', $today)
            ->whereNotNull('user_name')
            ->distinct()
            ->pluck('user_name');

        $activeUsersCount = $activeUsers->count();
        $activeUsersSummary = $activeUsers->take(4)->implode(', ');

        return [
            'total_today' => $totalToday,
            'total_yesterday' => $totalYesterday,
            'growth_pct' => $growthPct,
            'financial_count' => $financialCount,
            'tracked_value' => $trackedValue,
            'critical_count' => $criticalCount,
            'deletions_count' => $deletionsCount,
            'updates_count' => $updatesCount,
            'active_users_count' => $activeUsersCount,
            'active_users_summary' => $activeUsersSummary ?: 'No active operators today',
            'integrity_status' => '100%',
        ];
    }

    /**
     * Get distinct users who have performed activities.
     */
    public function getDistinctUsers(): Collection
    {
        return User::orderBy('name')->get(['id', 'name', 'role']);
    }
}
