<?php

namespace App\Repositories\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    /**
     * Create a new audit log record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AuditLog;

    /**
     * Get paginated audit logs based on search and filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFiltered(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find an audit log record by ID.
     */
    public function findById(int $id): ?AuditLog;

    /**
     * Find an audit log record by event ID.
     */
    public function findByEventId(string $eventId): ?AuditLog;

    /**
     * Calculate KPI metrics for the audit dashboard.
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(): array;

    /**
     * Get distinct users who have performed activities.
     */
    public function getDistinctUsers(): Collection;
}
