@extends('layouts.app')

@section('title', 'Audit Logs & System Activity Monitor')
@section('page_title', 'Audit Logs & Activity Monitor ')

@push('styles')
<style>
    /* Live stream pulse indicator */
    .live-pulse {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #10b981;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulse-green 1.8s infinite;
    }
    .live-pulse.paused {
        background-color: #f59e0b;
        box-shadow: none;
        animation: none;
    }
    @keyframes pulse-green {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    /* Action Badges */
    .badge-action-create { background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-action-update { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-action-delete { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .badge-action-approve { background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-action-reject { background-color: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .badge-action-cash { background-color: #fefce8; color: #a16207; border: 1px solid #fef08a; }
    .badge-action-login { background-color: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; }
    .badge-action-security { background-color: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }

    /* Severity Indicators */
    .severity-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .severity-dot-info { background-color: #3b82f6; }
    .severity-dot-warning { background-color: #f59e0b; }
    .severity-dot-critical { background-color: #ef4444; }

    /* Fresh row highlight animation */
    @keyframes newRowGlow {
        0% { background-color: #dbeafe; }
        100% { background-color: transparent; }
    }
    .new-audit-row {
        animation: newRowGlow 2.5s ease-out;
    }

    /* Diff View Styling */
    .diff-box {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.82rem;
    }
    .diff-old {
        background-color: #fee2e2;
        color: #991b1b;
        text-decoration: line-through;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .diff-new {
        background-color: #dcfce7;
        color: #166534;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Row -->
    <div class="card-custom p-3 p-md-4 mb-4 bg-white">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h5 class="fw-bold mb-0 text-dark">Audit Logs & Activity Monitor</h5>
                    <span class="badge bg-light text-dark border px-2 py-1 d-inline-flex align-items-center gap-1.5" id="liveStreamStatus">
                        <span class="live-pulse" id="pulseIndicator"></span>
                        <span class="small fw-semibold" id="streamStatusText">Live Feed Active</span>
</span>
</div>
                <p class="text-muted small mb-0">
                    Comprehensive centralized audit trail recording what time, who performed the action, target entity, modified values, and activity across all modules in Deiveni Construction System.
                </p>
</div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <!-- Toggle Live Stream -->
                <button class="btn btn-outline-secondary btn-sm" id="toggleStreamBtn" title="Pause or Resume Auto Live Feed">
                    <i class="bi bi-pause-fill me-1" id="streamToggleIcon"></i> <span id="streamToggleText">Pause Stream</span>
</button>
                <!-- Simulate New Live Action -->
                <button class="btn btn-outline-primary btn-sm" id="simulateEventBtn" title="Inject a simulated real-time event">
                    <i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Simulate Activity
</button>
                <!-- Export Audit Trail -->
                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#exportAuditModal">
                    <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Export Logs
</button>
                <!-- Security Retention Policy -->
                <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#retentionModal">
                    <i class="bi bi-shield-lock-fill me-1"></i> Security & Retention
</button>
</div>
</div>
</div>

    <!-- 5 KPI Summary Cards (Dynamic) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Activity Count Today -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card-stat h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Events Tracked (Today)</span>
                    <div class="stat-icon-wrapper bg-workers-subtle">
                        <i class="bi bi-activity"></i>
</div>
</div>
                <h4 class="fw-bold text-dark mb-1" id="kpiTotalEvents">{{ number_format($kpiStats['total_today'] ?? 0) }}</h4>
                <div class="d-flex align-items-center gap-1 {{ ($kpiStats['growth_pct'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }} small">
                    <i class="bi {{ ($kpiStats['growth_pct'] ?? 0) >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                    <span class="fw-semibold" id="kpiGrowthText">{{ ($kpiStats['growth_pct'] ?? 0) >= 0 ? '+' : '' }}{{ $kpiStats['growth_pct'] ?? 0 }}% vs yesterday</span>
</div>
</div>
</div>

        <!-- 2. Financial & Cash Transactions -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card-stat h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Cash & Wage Events</span>
                    <div class="stat-icon-wrapper bg-cash-subtle">
                        <i class="bi bi-wallet2"></i>
</div>
</div>
                <h4 class="fw-bold text-success mb-1" id="kpiFinancialEvents">{{ number_format($kpiStats['financial_count'] ?? 0) }}</h4>
                <div class="text-muted small">
                    <span class="fw-semibold text-dark" id="kpiTrackedValue">₹{{ number_format($kpiStats['tracked_value'] ?? 0, 2) }}</span> tracked value
</div>
</div>
</div>

        <!-- 3. Critical Modifications & Deletions -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card-stat h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Critical / Mod Actions</span>
                    <div class="stat-icon-wrapper bg-expense-subtle">
                        <i class="bi bi-exclamation-triangle-fill text-danger"></i>
</div>
</div>
                <h4 class="fw-bold text-danger mb-1" id="kpiCriticalEvents">{{ number_format($kpiStats['critical_count'] ?? 0) }}</h4>
                <div class="d-flex align-items-center gap-1 text-muted small">
                    <span class="badge bg-danger-subtle text-danger px-1.5 py-0.5" id="kpiDeletionsText">{{ $kpiStats['deletions_count'] ?? 0 }} Deletions</span>
                    <span id="kpiUpdatesText">{{ $kpiStats['updates_count'] ?? 0 }} Updates</span>
</div>
</div>
</div>

        <!-- 4. Active Users Logged -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card-stat h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Active Operators</span>
                    <div class="stat-icon-wrapper bg-sites-subtle">
                        <i class="bi bi-person-check-fill text-warning"></i>
</div>
</div>
                <h4 class="fw-bold text-primary mb-1" id="kpiActiveUsers">{{ number_format($kpiStats['active_users_count'] ?? 0) }}</h4>
                <div class="text-muted small text-truncate" id="kpiActiveUsersSummary" style="max-width: 180px;" title="{{ $kpiStats['active_users_summary'] ?? '' }}">
                    {{ $kpiStats['active_users_summary'] ?? 'None today' }}
</div>
</div>
</div>

        <!-- 5. Tamper-evident Security Status -->
        <div class="col-6 col-md-4 col-xl">
            <div class="card-stat h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="text-muted small fw-semibold">Security & Integrity</span>
                    <div class="stat-icon-wrapper bg-income-subtle">
                        <i class="bi bi-shield-check text-success"></i>
</div>
</div>
                <h4 class="fw-bold text-success mb-1">100%</h4>
                <div class="text-muted small">
                    <i class="bi bi-check2-circle text-success me-1"></i>Immutable SHA-256 Log
</div>
</div>
</div>
</div>

    <!-- Filter & Search Toolbar Card -->
    <div class="card-custom p-3 mb-4 bg-white">
        <form id="auditFilterForm" method="GET" action="{{ route('audit-logs.index') }}" onsubmit="return false;">
            <div class="row g-2 align-items-end">
                <!-- Keyword Search -->
                <div class="col-12 col-md-4 col-lg-3">
                    <label class="form-label small fw-semibold mb-1">Search Activity / Entity / User</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control" id="auditSearchInput" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Filter by user, action, ID, IP...">
</div>
</div>

                <!-- Filter by Module -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Module</label>
                    <select class="form-select form-select-sm" id="auditModuleFilter" name="module">
                        <option value="all" {{ ($filters['module'] ?? 'all') === 'all' ? 'selected' : '' }}>All Modules</option>
                        <option value="site_cash" {{ ($filters['module'] ?? '') === 'site_cash' ? 'selected' : '' }}>Site Cash Handling</option>
                        <option value="daily_wages" {{ ($filters['module'] ?? '') === 'daily_wages' ? 'selected' : '' }}>Daily Wages & Entry</option>
                        <option value="purchases" {{ ($filters['module'] ?? '') === 'purchases' ? 'selected' : '' }}>Purchases & Inward</option>
                        <option value="ledger" {{ ($filters['module'] ?? '') === 'ledger' ? 'selected' : '' }}>Finance & Ledger</option>
                        <option value="sites" {{ ($filters['module'] ?? '') === 'sites' ? 'selected' : '' }}>Project Sites</option>
                        <option value="warehouse" {{ ($filters['module'] ?? '') === 'warehouse' ? 'selected' : '' }}>Warehouse & Store</option>
                        <option value="masters" {{ ($filters['module'] ?? '') === 'masters' ? 'selected' : '' }}>Masters</option>
                        <option value="users" {{ ($filters['module'] ?? '') === 'users' ? 'selected' : '' }}>Users & RBAC</option>
                        <option value="auth" {{ ($filters['module'] ?? '') === 'auth' ? 'selected' : '' }}>Authentication & Security</option>
                    </select>
</div>

                <!-- Filter by Action Type -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Action Type</label>
                    <select class="form-select form-select-sm" id="auditActionFilter" name="action">
                        <option value="all" {{ ($filters['action'] ?? 'all') === 'all' ? 'selected' : '' }}>All Actions</option>
                        <option value="LOGIN" {{ ($filters['action'] ?? '') === 'LOGIN' ? 'selected' : '' }}>LOGIN</option>
                        <option value="LOGOUT" {{ ($filters['action'] ?? '') === 'LOGOUT' ? 'selected' : '' }}>LOGOUT</option>
                        <option value="CREATE" {{ ($filters['action'] ?? '') === 'CREATE' ? 'selected' : '' }}>CREATE</option>
                        <option value="UPDATE" {{ ($filters['action'] ?? '') === 'UPDATE' ? 'selected' : '' }}>UPDATE</option>
                        <option value="DELETE" {{ ($filters['action'] ?? '') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                        <option value="APPROVE" {{ ($filters['action'] ?? '') === 'APPROVE' ? 'selected' : '' }}>APPROVE</option>
                        <option value="REJECT" {{ ($filters['action'] ?? '') === 'REJECT' ? 'selected' : '' }}>REJECT</option>
                        <option value="CASH_TXN" {{ ($filters['action'] ?? '') === 'CASH_TXN' ? 'selected' : '' }}>CASH_TXN</option>
                    </select>
</div>

                <!-- Filter by User / Actor (Dynamic from Database) -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small fw-semibold mb-1">Operator</label>
                    <select class="form-select form-select-sm" id="auditUserFilter" name="user">
                        <option value="all" {{ ($filters['user'] ?? 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                        @foreach($users as $user)
                            @php
                                $roleDisplay = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;
                            @endphp
                            <option value="{{ $user->id }}" {{ (string)($filters['user'] ?? '') === (string)$user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ ucwords(str_replace('_', ' ', $roleDisplay)) }})
</option>
                        @endforeach
                    </select>
</div>

                <!-- Filter by Severity -->
                <div class="col-6 col-md-3 col-lg-1">
                    <label class="form-label small fw-semibold mb-1">Severity</label>
                    <select class="form-select form-select-sm" id="auditSeverityFilter" name="severity">
                        <option value="all" {{ ($filters['severity'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="info" {{ ($filters['severity'] ?? '') === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ ($filters['severity'] ?? '') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="critical" {{ ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
</div>

                <!-- Filter by Date Period & Reset Button -->
                <div class="col-12 col-md-6 col-lg-2 d-flex gap-2">
                    <div class="flex-grow-1">
                        <label class="form-label small fw-semibold mb-1">Period</label>
                        <select class="form-select form-select-sm" id="auditPeriodFilter" name="period">
                            <option value="all" {{ ($filters['period'] ?? 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                            <option value="today" {{ ($filters['period'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ ($filters['period'] ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="last_7_days" {{ ($filters['period'] ?? '') === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="this_month" {{ ($filters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        </select>
</div>
                    <div class="align-self-end">
                        <button class="btn btn-outline-secondary btn-sm" id="resetAuditFiltersBtn" title="Clear all filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
</div>
</div>
        </form>
</div>

    <!-- Main Audit Log Feed & Data Table (Unified Table View Without Tabs) -->
    <div class="card-custom bg-white overflow-hidden mb-4">
        <div class="p-3 bg-light border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark"><i class="bi bi-journal-text text-primary me-1"></i>Activity Log Ledger</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="showingCountBadge">
                    Showing {{ $logs->firstItem() ?: 0 }} &minus; {{ $logs->lastItem() ?: 0 }} of {{ number_format($logs->total()) }} Records
</span>
</div>
            <div class="d-flex align-items-center gap-2 small text-muted">
                <span>Auto-refresh every <strong>10s</strong></span>
                <span class="border-start ps-2">System Time: <strong id="liveSystemTime">{{ now()->format('d M Y, h:i A') }}</strong></span>
</div>
</div>

        <div class="table-responsive">
            <table class="table table-custom table-hover align-middle mb-0" id="auditLogsTable">
                <thead>
                    <tr>
                        <th style="width: 170px;">Timestamp</th>
                        <th style="width: 190px;">Who / Actor</th>
                        <th style="width: 160px;">Module & Target</th>
                        <th style="width: 130px;">Action</th>
                        <th>Activity Description</th>
                        <th style="width: 150px;">Site / Location</th>
                        <th class="text-end" style="width: 90px;">Details</th>
                    </tr>
                </thead>
                <tbody id="auditLogsTableBody">
                    @include('audit-logs._rows', ['logs' => $logs])
                </tbody>
            </table>
</div>

        <!-- Pagination & Ledger Summary Footer -->
        <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="small text-muted" id="paginationSummary">
                Showing <strong>{{ $logs->firstItem() ?: 0 }} &minus; {{ $logs->lastItem() ?: 0 }}</strong> of <strong>{{ number_format($logs->total()) }}</strong> total audit records logged
</div>
            <div id="paginationLinksContainer">
                {{ $logs->links('pagination::bootstrap-5') }}
</div>
</div>
</div>

</div>

<!-- MODAL 1: Dynamic Audit Event Detail Modal -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" aria-labelledby="auditDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom pb-3">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1" id="modalEventBadge">EVENT DETAIL</span>
                    <h5 class="modal-title fw-bold text-dark" id="modalEventTitle">Audit Event Specification</h5>
</div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
            <div class="modal-body p-4">

                <!-- Meta Information Grid -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block fw-semibold">EVENT ID</small>
                        <div class="font-monospace fw-bold text-primary mt-1" id="modalEventId">--</div>
</div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block fw-semibold">DATE & TIME</small>
                        <div class="fw-semibold text-dark mt-1" id="modalEventTime">--</div>
</div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block fw-semibold">ACTOR / USER</small>
                        <div class="fw-semibold text-dark mt-1" id="modalEventActor">--</div>
</div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block fw-semibold">CLIENT IP & HOST</small>
                        <div class="font-monospace text-secondary mt-1" id="modalEventIp">--</div>
</div>
</div>

                <!-- Entity & Action Section -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Target Module</small>
                            <span class="fw-bold text-dark" id="modalEventModule">--</span>
</div>
                        <div class="col-6 col-md-4">
                            <small class="text-muted d-block">Target Entity ID</small>
                            <span class="font-monospace fw-bold text-primary" id="modalEventTargetId">--</span>
</div>
                        <div class="col-6 col-md-4">
                            <small class="text-muted d-block">Action Code & Severity</small>
                            <span class="badge badge-action-create me-1" id="modalEventActionCode">--</span>
                            <span class="badge bg-secondary" id="modalEventSeverity">INFO</span>
</div>
</div>
                    <hr class="my-2 text-muted">
                    <div>
                        <small class="text-muted d-block">Activity Description</small>
                        <div class="fw-semibold text-dark mt-1" id="modalEventDesc">--</div>
                        <small class="ta-text text-muted" id="modalEventTamilDesc"></small>
</div>
</div>

                <!-- Visual Difference Comparison -->
                <div id="modalDiffContainer">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-arrow-left-right text-primary me-2"></i>Field-Level Data Delta</h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 25%;">Field Attribute</th>
                                    <th style="width: 37.5%;">Previous Value</th>
                                    <th style="width: 37.5%;">Updated / New Value</th>
                                </tr>
                            </thead>
                            <tbody id="modalDiffTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
</div>
</div>

</div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="copyAuditIdBtn">
                    <i class="bi bi-clipboard-check me-1"></i> Copy Event ID
</button>
                <button type="button" class="btn btn-deiveni btn-sm" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

<!-- MODAL 2: Export Audit Logs Modal -->
<div class="modal fade" id="exportAuditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-file-earmark-arrow-down-fill text-success me-2"></i>Export Audit Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Download tamper-evident activity reports for statutory compliance, external audit, or management review.</p>
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Export Format</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="exportFormatRadio" id="fmtExcel" value="excel" checked>
                            <label class="btn btn-outline-secondary btn-sm w-100 py-2 text-start" for="fmtExcel">
                                <i class="bi bi-file-earmark-excel text-success fs-5 me-1 align-middle"></i>
                                <span class="fw-bold d-block text-dark">Excel (.csv)</span>
                                <small class="text-muted" style="font-size: 0.72rem;">UTF-8 Tamil encoded</small>
</label>
</div>
                        <div class="col-6">
                            <input type="radio" class="btn-check" name="exportFormatRadio" id="fmtJson" value="json">
                            <label class="btn btn-outline-secondary btn-sm w-100 py-2 text-start" for="fmtJson">
                                <i class="bi bi-file-earmark-code text-warning fs-5 me-1 align-middle"></i>
                                <span class="fw-bold d-block text-dark">JSON Archive</span>
                                <small class="text-muted" style="font-size: 0.72rem;">Cryptographic dump</small>
</label>
</div>
</div>
</div>

                <div class="alert alert-light border py-2 px-3 small text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i> Current filters (Module: <strong id="exportFilterModuleSummary">All</strong>, User: <strong id="exportFilterUserSummary">All</strong>, Period: <strong id="exportFilterPeriodSummary">All</strong>) will be applied.
</div>
</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="confirmExportBtn">
                    <i class="bi bi-download me-1"></i> Start Download
</button>
</div>
</div>
</div>
</div>

<!-- MODAL 3: Security & Retention Policy Modal -->
<div class="modal fade" id="retentionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Security & Data Retention Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
            <div class="modal-body p-4">
                <div class="alert alert-primary py-2 px-3 small mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i> Deiveni CMS complies with construction financial audit standards. All log entries are write-once, append-only, and tamper-resistant.
</div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <strong class="d-block text-dark">Financial & Cash Trail Retention</strong>
                            <span class="text-muted">Advances, wages, purchases, vouchers</span>
</div>
                        <span class="badge bg-success">7 Years (Mandatory)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <strong class="d-block text-dark">Master Data & User Modifications</strong>
                            <span class="text-muted">Rate changes, category additions, RBAC</span>
</div>
                        <span class="badge bg-primary">Indefinite (Forever)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <strong class="d-block text-dark">Authentication & Session Access Logs</strong>
                            <span class="text-muted">IP, browser headers, login times</span>
</div>
                        <span class="badge bg-secondary">365 Days</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <strong class="d-block text-dark">Cryptographic Verification</strong>
                            <span class="text-muted">Chained block hashing</span>
</div>
                        <span class="badge bg-dark">SHA-256 Enabled</span>
                    </li>
                </ul>
</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {
    let liveStreamActive = true;
    let streamInterval = null;
    let searchTimeout = null;

    // Helper to get active filter parameters
    function getFilterParams(page = 1) {
        return {
            page: page,
            search: $('#auditSearchInput').val().trim(),
            module: $('#auditModuleFilter').val(),
            action: $('#auditActionFilter').val(),
            user: $('#auditUserFilter').val(),
            severity: $('#auditSeverityFilter').val(),
            period: $('#auditPeriodFilter').val()
        };
    }

    // Load logs via AJAX
    function fetchLogs(page = 1, isBackground = false) {
        const params = getFilterParams(page);

        $.ajax({
            url: "{{ route('audit-logs.index') }}",
            type: 'GET',
            data: params,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function (res) {
                if (res.status === 'success') {
                    $('#auditLogsTableBody').html(res.html);
                    if (res.pagination) {
                        $('#paginationLinksContainer').html(res.pagination);
                    }
                    $('#showingCountBadge').text(`Showing ${res.first_item} - ${res.last_item} of ${res.total.toLocaleString()} Records`);
                    $('#paginationSummary').html(`Showing <strong>${res.first_item} &minus; ${res.last_item}</strong> of <strong>${res.total.toLocaleString()}</strong> total audit records logged`);

                    // Update KPIs dynamically
                    if (res.kpi) {
                        $('#kpiTotalEvents').text(Number(res.kpi.total_today).toLocaleString());
                        const growthSign = res.kpi.growth_pct >= 0 ? '+' : '';
                        $('#kpiGrowthText').text(`${growthSign}${res.kpi.growth_pct}% vs yesterday`);
                        $('#kpiFinancialEvents').text(Number(res.kpi.financial_count).toLocaleString());
                        $('#kpiTrackedValue').text(`₹${Number(res.kpi.tracked_value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                        $('#kpiCriticalEvents').text(Number(res.kpi.critical_count).toLocaleString());
                        $('#kpiDeletionsText').text(`${res.kpi.deletions_count} Deletions`);
                        $('#kpiUpdatesText').text(`${res.kpi.updates_count} Updates`);
                        $('#kpiActiveUsers').text(Number(res.kpi.active_users_count).toLocaleString());
                        $('#kpiActiveUsersSummary').text(res.kpi.active_users_summary).attr('title', res.kpi.active_users_summary);
                    }
                }
            },
            error: function () {
                if (!isBackground && window.showToast) {
                    window.showToast('Failed to load audit logs', 'danger');
                }
            }
        });
    }

    // Trigger filters on input/change with debouncing
    $('#auditSearchInput').on('keyup', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchLogs(1);
        }, 350);
    });

    $('#auditModuleFilter, #auditActionFilter, #auditUserFilter, #auditSeverityFilter, #auditPeriodFilter').on('change', function () {
        fetchLogs(1);
    });

    // Pagination links click handler (AJAX pagination)
    $(document).on('click', '#paginationLinksContainer a.page-link', function (e) {
        e.preventDefault();
        const url = $(this).attr('href');
        if (url && url !== '#') {
            const pageMatch = url.match(/page=(\d+)/);
            const page = pageMatch ? pageMatch[1] : 1;
            fetchLogs(page);
        }
    });

    // Reset filters
    $('#resetAuditFiltersBtn').on('click', function () {
        $('#auditSearchInput').val('');
        $('#auditModuleFilter').val('all');
        $('#auditActionFilter').val('all');
        $('#auditUserFilter').val('all');
        $('#auditSeverityFilter').val('all');
        $('#auditPeriodFilter').val('all');
        fetchLogs(1);
        if (window.showToast) {
            window.showToast('Audit filters reset', 'info');
        }
    });

    // Toggle live stream auto-refresh
    $('#toggleStreamBtn').on('click', function () {
        liveStreamActive = !liveStreamActive;
        if (liveStreamActive) {
            $('#pulseIndicator').removeClass('paused');
            $('#streamStatusText').text('Live Feed Active');
            $('#streamToggleIcon').attr('class', 'bi bi-pause-fill me-1');
            $('#streamToggleText').text('Pause Stream');
            startAutoRefresh();
            if (window.showToast) window.showToast('Audit stream monitoring resumed', 'success');
        } else {
            $('#pulseIndicator').addClass('paused');
            $('#streamStatusText').text('Stream Paused');
            $('#streamToggleIcon').attr('class', 'bi bi-play-fill me-1');
            $('#streamToggleText').text('Resume Stream');
            clearInterval(streamInterval);
            if (window.showToast) window.showToast('Audit stream monitoring paused', 'warning');
        }
    });

    // Start auto-refresh interval (every 10 seconds)
    function startAutoRefresh() {
        clearInterval(streamInterval);
        streamInterval = setInterval(() => {
            if (liveStreamActive) {
                // Update system time
                const now = new Date();
                $('#liveSystemTime').text(now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) + ', ' + now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }));
                fetchLogs(1, true);
            }
        }, 10000);
    }
    startAutoRefresh();

    // Simulate Activity Button Click
    $('#simulateEventBtn').on('click', function () {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Recording...');

        $.ajax({
            url: "{{ route('audit-logs.simulate') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Simulate Activity');
                if (res.status === 'success') {
                    fetchLogs(1);
                    if (window.showToast) {
                        window.showToast(`New Activity Logged: ${res.data.action} - ${res.data.title}`, 'success');
                    }
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="bi bi-lightning-charge-fill me-1 text-warning"></i> Simulate Activity');
                if (window.showToast) {
                    window.showToast('Failed to record simulation event', 'danger');
                }
            }
        });
    });

    // View Audit Details Modal Click Handler
    $(document).on('click', '.view-audit-btn', function () {
        const row = $(this).closest('tr');
        const dbId = $(this).data('id') || row.data('db-id') || row.data('id');

        // Fetch details from server
        $.ajax({
            url: '{{ route("audit-logs.show", ":id") }}'.replace(':id', dbId),
            type: 'GET',
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                if (res.status === 'success') {
                    const data = res.data;

                    $('#modalEventId').text(data.event_id);
                    $('#modalEventTime').text(data.timestamp);
                    $('#modalEventActor').text(`${data.actor.name} (${data.actor.role})`);
                    $('#modalEventIp').text(data.actor.ip);
                    $('#modalEventModule').text((data.module || '').toUpperCase().replace('_', ' '));
                    $('#modalEventTargetId').text(data.target_id);
                    $('#modalEventActionCode').attr('class', `badge ${data.action_badge_class || 'badge-action-create'} me-1`).text(data.action);
                    $('#modalEventSeverity').attr('class', `badge ${data.severity === 'CRITICAL' ? 'bg-danger' : (data.severity === 'WARNING' ? 'bg-warning text-dark' : 'bg-secondary')}`).text(data.severity);
                    $('#modalEventDesc').text(data.title);
                    $('#modalEventTamilDesc').text(data.title_ta || '');

                    // Populate Diff Table
                    const diffTbody = $('#modalDiffTableBody');
                    diffTbody.empty();

                    let hasDiff = false;
                    if (data.new_values && typeof data.new_values === 'object') {
                        for (const [key, newVal] of Object.entries(data.new_values)) {
                            const oldVal = (data.old_values && data.old_values[key] !== undefined) ? data.old_values[key] : 'None';
                            const oldStr = typeof oldVal === 'object' ? JSON.stringify(oldVal) : String(oldVal);
                            const newStr = typeof newVal === 'object' ? JSON.stringify(newVal) : String(newVal);

                            diffTbody.append(`
                                <tr>
                                    <td class="font-monospace fw-semibold">${key}</td>
                                    <td><span class="diff-old">${oldStr}</span></td>
                                    <td><span class="diff-new">${newStr}</span></td>
                                </tr>
                            `);
                            hasDiff = true;
                        }
                    }

                    if (!hasDiff && data.old_values && typeof data.old_values === 'object') {
                        for (const [key, oldVal] of Object.entries(data.old_values)) {
                            const oldStr = typeof oldVal === 'object' ? JSON.stringify(oldVal) : String(oldVal);
                            diffTbody.append(`
                                <tr>
                                    <td class="font-monospace fw-semibold">${key}</td>
                                    <td><span class="diff-old">${oldStr}</span></td>
                                    <td><span class="text-muted">DELETED</span></td>
                                </tr>
                            `);
                            hasDiff = true;
                        }
                    }

                    if (!hasDiff) {
                        diffTbody.append(`
                            <tr>
                                <td colspan="3" class="text-center text-muted small py-2">
                                    No attribute delta recorded for this event.
                                </td>
                            </tr>
                        `);
                    }

                    const detailModal = new bootstrap.Modal(document.getElementById('auditDetailModal'));
                    detailModal.show();
                }
            },
            error: function () {
                if (window.showToast) {
                    window.showToast('Unable to load event details', 'danger');
                }
            }
        });
    });

    // Copy Event ID button
    $('#copyAuditIdBtn').on('click', function () {
        const idText = $('#modalEventId').text();
        navigator.clipboard.writeText(idText).then(() => {
            if (window.showToast) window.showToast(`Audit ID ${idText} copied`, 'info');
        });
    });

    // Populate Export modal filter summaries
    $('#exportAuditModal').on('show.bs.modal', function () {
        $('#exportFilterModuleSummary').text($('#auditModuleFilter option:selected').text());
        $('#exportFilterUserSummary').text($('#auditUserFilter option:selected').text());
        $('#exportFilterPeriodSummary').text($('#auditPeriodFilter option:selected').text());
    });

    // Confirm Export Action
    $('#confirmExportBtn').on('click', function () {
        const format = $('input[name="exportFormatRadio"]:checked').val() || 'excel';
        const params = getFilterParams(1);
        params.format = format;

        const queryString = $.param(params);
        const exportUrl = "{{ route('audit-logs.export') }}?" + queryString;

        $('#exportAuditModal').modal('hide');
        if (window.showToast) {
            window.showToast(`Exporting audit logs as ${format.toUpperCase()}...`, 'info');
        }

        window.location.href = exportUrl;
    });
});
</script>
@endpush
