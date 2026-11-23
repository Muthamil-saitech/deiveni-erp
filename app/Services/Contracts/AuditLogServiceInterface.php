<?php

namespace App\Services\Contracts;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditLogServiceInterface
{
    /**
     * Record a new audit log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(array $data): ?AuditLog;

    /**
     * Get paginated audit logs based on filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getLogs(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * Retrieve audit log record details.
     */
    public function getLogDetails(int|string $id): ?AuditLog;

    /**
     * Get dashboard KPI metrics.
     *
     * @return array<string, mixed>
     */
    public function getKpiStats(): array;

    /**
     * Get distinct users for filter dropdown.
     */
    public function getDistinctUsers(): Collection;

    /**
     * Export audit trail data.
     *
     * @param  array<string, mixed>  $filters
     * @return array{filename: string, content: string, content_type: string}
     */
    public function export(array $filters, string $format = 'csv'): array;

    /**
     * Simulate a realistic activity event for testing/demo.
     */
    public function simulateActivity(): AuditLog;
}
