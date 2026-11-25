@extends('layouts.app')

@section('title', 'Financial & Ledger Report')
@section('page_title', 'Financial & Ledger Report ')

@push('styles')
    <style>
        .pagination .page-link {
            color: #1e3a8a;
            border-color: #e2e8f0;
            border-radius: 6px;
            padding: 0.32rem 0.65rem;
            font-size: 0.825rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }

        .pagination .page-link:hover {
            background-color: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-color: #1e3a8a;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(30, 58, 138, 0.25);
        }

        .pagination .page-item.disabled .page-link {
            color: #94a3b8;
            background-color: #f8fafc;
            border-color: #e2e8f0;
        }

        .card-custom {
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        /* Dedicated Clean Print & PDF styling */
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 6mm;
            }

            html,
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 8.5pt !important;
                line-height: 1.25 !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Hide interactive elements, sidebars, buttons and headers */
            #sidebar,
            .top-navbar,
            .breadcrumb,
            #headerActionCard,
            #filterCard,
            .btn,
            .no-print,
            .modal,
            .modal-backdrop,
            footer {
                display: none !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .main-content,
            .content-wrapper,
            .container-fluid,
            #accountLedgerApp {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Dedicated Clean Print Header */
            .print-report-header {
                display: block !important;
                margin-bottom: 10px !important;
                padding-bottom: 6px !important;
                border-bottom: 2px solid #0f172a !important;
            }

            .card-custom,
            .card {
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
                margin-bottom: 10px !important;
                padding: 8px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .kpi-print-grid {
                display: flex !important;
                flex-wrap: nowrap !important;
                gap: 8px !important;
                margin-bottom: 10px !important;
            }

            .kpi-print-grid > div {
                flex: 1 !important;
                border: 1px solid #cbd5e1 !important;
                padding: 6px 8px !important;
                border-radius: 4px !important;
                background: #f8fafc !important;
            }

            .table {
                width: 100% !important;
                border-collapse: collapse !important;
                margin-bottom: 0 !important;
            }

            .table th,
            .table td {
                border: 1px solid #cbd5e1 !important;
                padding: 4px 6px !important;
                color: #000000 !important;
            }

            thead {
                display: table-header-group !important;
            }

            tfoot {
                display: table-footer-group !important;
            }

            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .badge {
                border: 1px solid #475569 !important;
                color: #000000 !important;
                background: transparent !important;
            }

            /* Avoid blank trailing pages */
            * {
                box-sizing: border-box !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="accountLedgerApp">

        <!-- Dedicated Print & PDF Header (Visible strictly in Print / PDF export) -->
        <div class="print-report-header d-none">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1 text-uppercase text-dark">DEIVENI CONSTRUCTIONS & DEVELOPERS</h4>
                    <h5 class="fw-bold mb-0 text-secondary">Financial & Ledger Report</h5>
                    <small class="text-muted">Dynamic Ledger Flow, Client Advance Receipts, Wage Disbursements, and Supplier Settlements</small>
</div>
                <div class="text-end">
                    <span class="badge border border-dark text-dark px-2 py-1 mb-1 d-inline-block">OFFICIAL LEDGER AUDIT</span>
                    <div class="small text-muted">Generated: <strong>{{ $filterMeta['current_date'] }}</strong></div>
                    <div class="small text-muted" id="printActivePeriod">Period: <strong>This Week</strong></div>
                    <div class="small text-muted" id="printActiveSite">Site: <strong>All Sites</strong></div>
</div>
</div>

            <!-- Print KPI Summary Row -->
            <div class="kpi-print-grid mt-2">
                <div>
                    <span class="text-muted small">Total Inflow</span>
                    <div class="fw-bold fs-6 text-success" id="printKpiTotalIn">₹{{ number_format($kpis['total_in'], 2) }}</div>
</div>
                <div>
                    <span class="text-muted small">Total Outflow</span>
                    <div class="fw-bold fs-6 text-danger" id="printKpiTotalOut">₹{{ number_format($kpis['total_out'], 2) }}</div>
</div>
                <div>
                    <span class="text-muted small">Net Surplus / Balance</span>
                    <div class="fw-bold fs-6 text-primary" id="printKpiNetBalance">₹{{ number_format($kpis['net_balance'], 2) }}</div>
</div>
                <div>
                    <span class="text-muted small">Total Transactions</span>
                    <div class="fw-bold fs-6 text-dark" id="printKpiCount">{{ $kpis['count'] }} Entries</div>
</div>
</div>
</div>

        <!-- Top Header & Action Toolbar -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0" id="headerActionCard">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-journal-text text-success me-2"></i>Account Manager Financial & Ledger Report

</h4>
                        @if ($isAdmin)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="bi bi-shield-lock me-1"></i> Admin View
</span>
                        @endif
</div>
</div>

                <!-- Export & Navigation Actions (Task 5: Excel, PDF, Print) -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm" id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel"></i>
                        <span>Export Excel</span>
</button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm" id="exportPdfBtn">
                        <i class="bi bi-file-earmark-pdf"></i>
                        <span>Export PDF</span>
</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm" id="printReportBtn">
                        <i class="bi bi-printer"></i>
                        <span>Print</span>
</button>
</div>
</div>
</div>

        <!-- Filter Toolbar -->
        <div class="card-custom p-3 mb-4 bg-white shadow-sm border-0" id="filterCard">
            <div class="row g-2 align-items-end">
                <!-- Date Period Filter (Task 4: Order: today, this_week, last_week (date range), this_month, custom, all) -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-calendar3 me-1 text-primary"></i> Date Period 
</label>
                    <select class="form-select form-select-sm" id="filterPeriod" name="period">
                        <option value="today" {{ ($activeFilters['period'] ?? '') === 'today' ? 'selected' : '' }}>
                            Today ({{ $filterMeta['today']['label'] }})
</option>
                        <option value="this_week" {{ ($activeFilters['period'] ?? 'this_week') === 'this_week' ? 'selected' : '' }}>
                            This Week ({{ $filterMeta['this_week']['label'] }})
</option>
                        <option value="last_week" {{ ($activeFilters['period'] ?? '') === 'last_week' ? 'selected' : '' }}>
                            Last Week ({{ $filterMeta['last_week']['label'] }})
</option>
                        <option value="this_month" {{ ($activeFilters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>
                            This Month ({{ $filterMeta['this_month']['label'] }})
</option>
                        <option value="custom" {{ ($activeFilters['period'] ?? '') === 'custom' ? 'selected' : '' }}>
                            Custom Date Range...
</option>
                        <option value="all" {{ ($activeFilters['period'] ?? '') === 'all' ? 'selected' : '' }}>
                            All Dates 
</option>
                    </select>
</div>

                <!-- Custom Date Inputs -->
                <div class="col-6 col-md-2" id="customDateStartCol" style="display: none;">
                    <label class="form-label small fw-semibold mb-1 text-muted">From Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterStartDate"
                        value="{{ $filterMeta['this_week']['start'] }}">
</div>
                <div class="col-6 col-md-2" id="customDateEndCol" style="display: none;">
                    <label class="form-label small fw-semibold mb-1 text-muted">To Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterEndDate"
                        value="{{ $filterMeta['this_week']['end'] }}">
</div>

                <!-- Project Site Filter (Task 2 & 3: Dynamic DB sites) -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-building me-1 text-primary"></i> Project Site 
</label>
                    <select class="form-select form-select-sm" id="filterSite" name="site_id">
                        <option value="all">All Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" {{ (string) ($activeFilters['site_id'] ?? '') === (string) $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
</option>
                        @endforeach
                    </select>
</div>

                <!-- Transaction Flow Type / Source Filter (5 Categories) -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-funnel me-1 text-primary"></i> Transaction Flow Type
</label>
                    <select class="form-select form-select-sm" id="filterFlowType" name="flow_type">
                        <option value="all">All Sources / Categories</option>
                        <option value="Construction">Construction</option>
                        <option value="Bank Valuation">Bank (Valuation / Inspection)</option>
                        <option value="Rental">Rental</option>
                        <option value="Supplier">Supplier</option>
                        <option value="Office and other expenses">Office and other expenses</option>
                    </select>
</div>

                <!-- Cash Flow Filter (Income / Expense) -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-arrow-left-right me-1 text-primary"></i> Cash Flow 
</label>
                    <select class="form-select form-select-sm" id="filterCashFlow" name="cash_flow">
                        <option value="all">All Cash Flows</option>
                        <option value="income">Income Only (Dr)</option>
                        <option value="expense">Expense Only (Cr)</option>
                    </select>
</div>

                <!-- Text Search -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-search me-1 text-primary"></i> Search Particulars / Supplier / Party
</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search particulars, supplier, client...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                            <i class="bi bi-x"></i>
</button>
</div>
</div>

                <!-- Reset Filter Button -->
                <div class="col-12 col-md-1">
                    <button type="button" class="btn btn-sm btn-light border text-muted w-100" id="resetFilterBtn" title="Reset all filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
</button>
</div>
</div>

            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <span class="small text-muted" id="filterMatchCountBadge">
                    Active filters: <strong>This Week</strong> &bull; <strong>All Sites</strong> &bull; <strong>All Sources</strong>
</span>
                <span class="small text-muted">Current Reference Date: <strong>{{ $filterMeta['current_date'] }}</strong></span>
</div>
</div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Inflow</div>
                            <h4 class="fw-bold mb-0 text-success mt-1" id="kpiTotalIn">₹{{ number_format($kpis['total_in'], 2) }}</h4>
                            <small class="text-muted">Client advance &amp; income receipts</small>
</div>
                        <div class="bg-success-subtle text-success p-2 rounded">
                            <i class="bi bi-arrow-down-left-circle-fill fs-5"></i>
</div>
</div>
</div>
</div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-danger h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Outflow</div>
                            <h4 class="fw-bold mb-0 text-danger mt-1" id="kpiTotalOut">₹{{ number_format($kpis['total_out'], 2) }}</h4>
                            <small class="text-muted">Wages, materials &amp; expenses</small>
</div>
                        <div class="bg-danger-subtle text-danger p-2 rounded">
                            <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
</div>
</div>
</div>
</div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-primary h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Net Cash Surplus</div>
                            <h4 class="fw-bold mb-0 text-primary mt-1" id="kpiNetBalance">₹{{ number_format($kpis['net_balance'], 2) }}</h4>
                            <small class="text-muted">Net reconciled balance</small>
</div>
                        <div class="bg-primary-subtle text-primary p-2 rounded">
                            <i class="bi bi-wallet-fill fs-5"></i>
</div>
</div>
</div>
</div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-secondary h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Transactions</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiTxnCount">{{ $kpis['count'] }}</h4>
                            <small class="text-muted">Audited ledger entries</small>
</div>
                        <div class="bg-light text-secondary p-2 rounded border">
                            <i class="bi bi-receipt fs-5"></i>
</div>
</div>
</div>
</div>
</div>

        <!-- Daily Ledger Transactions Table Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-journal-text text-success me-1"></i>
                        Daily Ledger Transaction Register 
</h6>
                    <small class="text-muted">Chronological database ledger entries with credit/debit breakdown</small>
</div>
                <span class="badge bg-light text-dark border" id="txnCountBadge">{{ $kpis['count'] }} Entries</span>
</div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="amLedgerTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 120px;">Date &amp; Time</th>
                            <th style="min-width: 140px;">Flow Type</th>
                            <th style="min-width: 160px;">Project Site</th>
                            <th style="min-width: 240px;">Particulars / Description</th>
                            <th style="min-width: 160px;">Party / Supplier</th>
                            <th class="text-end" style="min-width: 120px;">Inflow</th>
                            <th class="text-end" style="min-width: 120px;">Outflow</th>
                            <th class="text-center" style="min-width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="amLedgerTableBody" class="small">
                        <!-- Populated dynamically via JavaScript from ledgers database -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="5">Summary Total:</td>
                            <td class="text-end text-success fs-6" id="footInflow">₹0.00</td>
                            <td class="text-end text-danger fs-6" id="footOutflow">₹0.00</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">Reconciled</span></td>
                        </tr>
                    </tfoot>
                </table>
</div>

            <!-- Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 no-print" id="tablePaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: 75px;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, $kpis['count']) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $kpis['count'] }}</strong> entries
</span>
</div>
                <nav aria-label="Ledger report pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

        <!-- Office ↔ Site Cash & Wages Returns Reconciliation -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0" id="officeSiteReconciliationCard">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-left-right text-primary me-1"></i>
                        Office &harr; Site Cash &amp; Wages Reconciliation
</h6>
                    <small class="text-muted">Office Cash sent for Painter/Electrician wages vs Site Manager returns with Received / Not Received audit</small>
</div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.78rem;">
                        <i class="bi bi-building me-1"></i>Office Cash Tracking
</span>
</div>
</div>

            <!-- 3 Quick Metrics: Sent (-), Received (+), Pending (-) -->
            <div class="row g-2 mb-3">
                <div class="col-12 col-md-4">
                    <div class="p-2 p-md-3 rounded-3 bg-danger-subtle bg-opacity-25 border border-danger-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">1. Sent from Office (&minus;)</span>
                                <h5 class="fw-bold text-danger mb-0 mt-1" id="recTotalSent">&minus;₹0.00</h5>
                                <small class="text-muted" id="recCountSent">0 transactions sent</small>
</div>
                            <div class="bg-danger text-white rounded p-2">
                                <i class="bi bi-box-arrow-up-right fs-5"></i>
</div>
</div>
</div>
</div>
                <div class="col-12 col-md-4">
                    <div class="p-2 p-md-3 rounded-3 bg-success-subtle bg-opacity-25 border border-success-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">2. Received from Site</span>
                                <h5 class="fw-bold text-success mb-0 mt-1" id="recTotalReceived">+₹0.00</h5>
                                <small class="text-success fw-medium" id="recCountReceived">✓ 0 Received</small>
</div>
                            <div class="bg-success text-white rounded p-2">
                                <i class="bi bi-check2-circle fs-5"></i>
</div>
</div>
</div>
</div>
                <div class="col-12 col-md-4">
                    <div class="p-2 p-md-3 rounded-3 bg-warning-subtle bg-opacity-25 border border-warning-subtle">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">3. Pending from Site (&minus;)</span>
                                <h5 class="fw-bold text-warning-emphasis mb-0 mt-1" id="recTotalPending">&minus;₹0.00</h5>
                                <small class="text-danger fw-semibold" id="recCountPending">⏳ 0 Not Received</small>
</div>
                            <div class="bg-warning text-dark rounded p-2">
                                <i class="bi bi-clock-history fs-5"></i>
</div>
</div>
</div>
</div>
</div>

            <!-- Toggle Tabs between Detailed Wage Dispatches & Site Manager Return Receipts -->
            <ul class="nav nav-pills nav-fill mb-3 p-1 bg-light rounded-3" id="reconciliationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-2 px-3 fw-semibold small" id="tab-dispatches-btn" data-bs-toggle="tab" data-bs-target="#tab-dispatches" type="button" role="tab">
                        <i class="bi bi-send text-primary me-1"></i>Office Cash Sent &amp; Status
                        <span class="badge bg-primary ms-1" id="badgeDispatchesCount">0</span>
</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 px-3 fw-semibold small" id="tab-returns-btn" data-bs-toggle="tab" data-bs-target="#tab-returns" type="button" role="tab">
                        <i class="bi bi-box-arrow-in-left text-success me-1"></i>Site Manager Return Receipts 
                        <span class="badge bg-success ms-1" id="badgeReturnsCount">0</span>
</button>
                </li>
            </ul>

            <!-- Tab Contents -->
            <div class="tab-content" id="reconciliationTabContent">
                <!-- Tab 1: Office Cash Sent Dispatches with Received / Not Received Status -->
                <div class="tab-pane fade show active" id="tab-dispatches" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="recDispatchesTable">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th style="min-width: 110px;">Date &amp; Time</th>
                                    <th style="min-width: 140px;">Project Site</th>
                                    <th style="min-width: 260px;">Office Cash Sent Details</th>
                                    <th class="text-end" style="min-width: 120px;">Sent Amount (&minus;)</th>
                                    <th class="text-center" style="min-width: 130px;">Return Status</th>
                                    <th class="text-end" style="min-width: 130px;">Return Received</th>
                                </tr>
                            </thead>
                            <tbody id="recDispatchesTableBody" class="small">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
</div>
</div>

                <!-- Tab 2: Site Manager Return Receipts -->
                <div class="tab-pane fade" id="tab-returns" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="recReturnsTable">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th style="min-width: 110px;">Return Date &amp; Time</th>
                                    <th style="min-width: 140px;">Project Site</th>
                                    <th style="min-width: 260px;">Return Narration / Wages Covered</th>
                                    <th style="min-width: 140px;">Received By</th>
                                    <th class="text-end" style="min-width: 120px;">Returned Amount</th>
                                    <th class="text-center" style="min-width: 100px;">Audit Status</th>
                                </tr>
                            </thead>
                            <tbody id="recReturnsTableBody" class="small">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
</div>
</div>
</div>
</div>

</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Dynamic dataset loaded directly from ledgers table via AccountLedgerReportController & Service
            const rawTransactions = @json($transactions);
            const rawReconciliation = @json($officeCashReconciliation ?? ['summary' => [], 'items' => [], 'returns' => []]);
            const filterMeta = @json($filterMeta);
            const exportExcelUrl = "{{ route('reports.account.ledger.export-excel') }}";

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }

            /**
             * Check whether a transaction date falls into the selected period.
             */
            function checkDateInRange(rawDateStr) {
                if (!rawDateStr) return true;
                const period = $('#filterPeriod').val();
                if (period === 'all') return true;

                if (period === 'today') {
                    return rawDateStr === filterMeta.today?.start;
                } else if (period === 'this_week') {
                    return rawDateStr >= filterMeta.this_week?.start && rawDateStr <= filterMeta.this_week?.end;
                } else if (period === 'last_week') {
                    return rawDateStr >= filterMeta.last_week?.start && rawDateStr <= filterMeta.last_week?.end;
                } else if (period === 'this_month') {
                    return rawDateStr >= filterMeta.this_month?.start && rawDateStr <= filterMeta.this_month?.end;
                } else if (period === 'custom') {
                    const s = $('#filterStartDate').val();
                    const e = $('#filterEndDate').val();
                    if (s && rawDateStr < s) return false;
                    if (e && rawDateStr > e) return false;
                    return true;
                }
                return true;
            }

            let filteredLedgerList = [];

            function renderAccountLedgerReport() {
                const selectedSite = $('#filterSite').val();
                const selectedFlowType = $('#filterFlowType').val();
                const selectedCashFlow = $('#filterCashFlow').val();
                const searchQuery = ($('#filterSearchInput').val() || '').toLowerCase().trim();

                let totalIn = 0;
                let totalOut = 0;
                let matchCount = 0;

                filteredLedgerList = [];
                const tbody = $('#amLedgerTableBody');
                tbody.empty();

                rawTransactions.forEach(function (tx) {
                    // Site filter
                    if (selectedSite !== 'all') {
                        if (String(tx.project_site_id) !== String(selectedSite)) return;
                    }

                    // Date filter
                    if (!checkDateInRange(tx.raw_date)) return;

                    // Cash Flow Filter (Income / Expense)
                    const isIncome = tx.flow === 'income' || parseFloat(tx.inVal) > 0;
                    const isExpense = tx.flow === 'expense' || parseFloat(tx.outVal) > 0;

                    if (selectedCashFlow === 'income' && !isIncome) return;
                    if (selectedCashFlow === 'expense' && !isExpense) return;

                    // Source / Category / Flow Type Filter
                    if (selectedFlowType !== 'all') {
                        const txSource = (tx.source || tx.flow_type || '').toLowerCase();
                        const target = selectedFlowType.toLowerCase();

                        let matchType = false;
                        if (target === 'construction') {
                            matchType = txSource === 'construction';
                        } else if (target === 'bank valuation') {
                            matchType = txSource === 'bank valuation' || txSource === 'bank';
                        } else if (target === 'rental') {
                            matchType = txSource === 'rental';
                        } else if (target === 'supplier') {
                            matchType = txSource === 'supplier';
                        } else if (target === 'office and other expenses') {
                            matchType = txSource === 'office and other expenses' || txSource.includes('office') || txSource.includes('expense');
                        } else {
                            matchType = txSource === target;
                        }

                        if (!matchType) return;
                    }

                    // Text Search
                    if (searchQuery) {
                        const haystack = `${tx.date} ${tx.time || ''} ${tx.source || ''} ${tx.site || ''} ${tx.desc || ''} ${tx.party || ''} ${tx.supplier || ''}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    matchCount++;
                    filteredLedgerList.push(tx);

                    const inVal = parseFloat(tx.inVal) || (tx.flow === 'income' ? parseFloat(tx.amount) || 0 : 0);
                    const outVal = parseFloat(tx.outVal) || (tx.flow === 'expense' ? parseFloat(tx.amount) || 0 : 0);

                    totalIn += inVal;
                    totalOut += outVal;

                    let displayType = tx.source || (inVal > 0 ? 'Income' : 'Expense');
                    let typeBadge = '';
                    if (tx.transaction_type_name === 'Balance Returned' || (tx.source || '').toLowerCase().includes('balance returned') || (tx.desc || '').toLowerCase().includes('balance returned')) {
                        typeBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-arrow-return-left me-1"></i>Balance Returned</span>`;
                    } else {
                        typeBadge = inVal > 0
                            ? `<span class="badge bg-success-subtle text-success border border-success-subtle">${escapeHtml(displayType)}</span>`
                            : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">${escapeHtml(displayType)}</span>`;
                    }

                    const displaySite = (tx.site && tx.site !== '-') ? escapeHtml(tx.site) : '-';
                    const displayParty = tx.party || tx.supplier || '-';

                    const receiptBadge = tx.image
                        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" title="Receipt Attached"><i class="bi bi-paperclip"></i></span>`
                        : '';

                    let statusBadge = '<span class="badge bg-success-subtle text-success">Audited</span>';
                    if (tx.flow === 'income' && (tx.transaction_type_name === 'Balance Returned' || (tx.desc || '').toLowerCase().includes('balance returned'))) {
                        statusBadge = '<span class="badge bg-success text-white py-1 px-2 fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Received</span>';
                    } else if (tx.is_office_cash && tx.flow === 'expense') {
                        if (tx.return_status === 'received') {
                            statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 fw-semibold" title="Amount returned by Site Manager to Account Manager on ${escapeHtml(tx.return_date || '')}"><i class="bi bi-check-circle-fill me-1"></i>Received (+Returned)</span>`;
                        } else {
                            statusBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2 fw-semibold" title="Pending to be returned by Site Manager to Account Manager"><i class="bi bi-hourglass-split me-1 text-warning"></i>Not Received (Pending)</span>`;
                        }
                    }

                    tbody.append(`
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(tx.date)}</div>
                                <div class="small text-muted">${escapeHtml(tx.time || '')}</div>
                            </td>
                            <td>${typeBadge}</td>
                            <td class="fw-semibold text-dark">${displaySite}</td>
                            <td>
                                <div class="small text-dark">${escapeHtml(tx.desc || '-')}${receiptBadge}</div>
                            </td>
                            <td class="small text-muted">
                                ${displayParty !== '-' ? `<span class="fw-semibold text-primary">${escapeHtml(displayParty)}</span>` : '-'}
                            </td>
                            <td class="text-end fw-bold text-success">${inVal > 0 ? '₹' + inVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}</td>
                            <td class="text-end fw-bold text-danger">${outVal > 0 ? '₹' + outVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}</td>
                            <td class="text-center">${statusBadge}</td>
                        </tr>
                    `);
                });

                if (matchCount === 0) {
                    tbody.html('<tr><td colspan="8" class="text-center text-muted py-5"><i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i><div class="fw-semibold">No Ledger Transactions Found</div><small>Try selecting "All Dates" or adjusting your search filters.</small></td></tr>');
                }

                const netSurplus = totalIn - totalOut;

                // Update KPIs
                $('#kpiTotalIn, #printKpiTotalIn').text('₹' + totalIn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiTotalOut, #printKpiTotalOut').text('₹' + totalOut.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiNetBalance, #printKpiNetBalance').text((netSurplus >= 0 ? '+' : '') + '₹' + netSurplus.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiTxnCount').text(matchCount);
                $('#printKpiCount').text(`${matchCount} Entries`);

                // Update Footer
                $('#footInflow').text('₹' + totalIn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#footOutflow').text('₹' + totalOut.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#txnCountBadge').text(`${matchCount} Entries`);

                const pText = $('#filterPeriod option:selected').text();
                const sText = $('#filterSite option:selected').text();
                const fText = $('#filterFlowType option:selected').text();
                $('#filterMatchCountBadge').html(`Active filters: <strong>${escapeHtml(pText)}</strong> &bull; <strong>${escapeHtml(sText)}</strong> &bull; <strong>${escapeHtml(fText)}</strong>`);
                $('#printActivePeriod').html(`Period: <strong>${escapeHtml(pText)}</strong>`);
                $('#printActiveSite').html(`Site: <strong>${escapeHtml(sText)}</strong>`);

                updatePagination();
                renderOfficeCashReconciliation(selectedSite, searchQuery);
            }

            function renderOfficeCashReconciliation(selectedSite, searchQuery) {
                const items = rawReconciliation.items || [];
                const returns = rawReconciliation.returns || [];

                let totalSent = 0;
                let totalReceived = 0;
                let countSent = 0;
                let countReceived = 0;
                let countPending = 0;

                const dispatchesBody = $('#recDispatchesTableBody');
                const returnsBody = $('#recReturnsTableBody');
                dispatchesBody.empty();
                returnsBody.empty();

                let dispatchesMatched = 0;
                let returnsMatched = 0;

                // 1. Process and render dispatches
                items.forEach(function (it) {
                    if (selectedSite !== 'all' && String(it.project_site_id) !== String(selectedSite)) return;
                    if (!checkDateInRange(it.raw_date)) return;
                    if (searchQuery) {
                        const haystack = `${it.date} ${it.site_name} ${it.description} ${it.client_name}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    dispatchesMatched++;
                    const sentAmt = parseFloat(it.amount_sent) || 0;
                    totalSent += sentAmt;
                    countSent++;

                    if (it.is_received) {
                        totalReceived += sentAmt;
                        countReceived++;
                    } else {
                        countPending++;
                    }

                    const statusPill = it.is_received
                        ? `<span class="badge bg-success text-white py-1 px-2"><i class="bi bi-check-circle-fill me-1"></i>Received</span>`
                        : `<span class="badge bg-warning text-dark py-1 px-2"><i class="bi bi-hourglass-split me-1 text-dark"></i>Not Received</span>`;

                    const returnCol = it.is_received
                        ? `<strong class="text-success fs-6">+₹${sentAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                           <div class="text-muted" style="font-size: 0.74rem;"><i class="bi bi-calendar-check text-success me-1"></i>${escapeHtml(it.return_date || '')} ${it.return_ref ? `&bull; ${escapeHtml(it.return_ref)}` : ''}</div>`
                        : `<span class="text-danger fw-semibold" style="font-size: 0.85rem;">Pending &minus;₹${sentAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>`;

                    dispatchesBody.append(`
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(it.date)}</div>
                                <div class="small text-muted">${escapeHtml(it.time || '')}</div>
                            </td>
                            <td class="fw-semibold text-dark">${escapeHtml(it.site_name)}</td>
                            <td>
                                <div class="small text-dark">${escapeHtml(it.description || '-')}</div>
                            </td>
                            <td class="text-end fw-bold text-danger fs-6">&minus;₹${sentAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td class="text-center">${statusPill}</td>
                            <td class="text-end">${returnCol}</td>
                        </tr>
                    `);
                });

                if (dispatchesMatched === 0) {
                    dispatchesBody.html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-info-circle fs-4 d-block mb-1 text-secondary"></i>No Office Cash Dispatches Found for Current Filter</td></tr>');
                }

                // 2. Process and render returns
                returns.forEach(function (ret) {
                    if (selectedSite !== 'all' && String(ret.project_site_id) !== String(selectedSite)) return;
                    if (!checkDateInRange(ret.raw_date)) return;
                    if (searchQuery) {
                        const haystack = `${ret.date} ${ret.site_name} ${ret.description} ${ret.paid_to}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    returnsMatched++;
                    const retAmt = parseFloat(ret.amount) || 0;

                    returnsBody.append(`
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(ret.date)}</div>
                                <div class="small text-muted">${escapeHtml(ret.time || '')}</div>
                            </td>
                            <td class="fw-semibold text-dark">${escapeHtml(ret.site_name)}</td>
                            <td>
                                <div class="small text-dark fw-medium">${escapeHtml(ret.description || '-')}</div>
                                <div class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-receipt me-1"></i>Ref: ${escapeHtml(ret.bill_no || '')}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-person me-1 text-primary"></i>${escapeHtml(ret.paid_to || 'Account Manager')}</span></td>
                            <td class="text-end fw-bold text-success fs-6">+₹${retAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-all me-1"></i>Reconciled</span></td>
                        </tr>
                    `);
                });

                if (returnsMatched === 0) {
                    returnsBody.html('<tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-1 text-secondary"></i>No Site Manager Return Receipts Found for Current Filter</td></tr>');
                }

                const totalPending = Math.max(0, totalSent - totalReceived);

                // Update quick metrics
                $('#recTotalSent').text('-₹' + totalSent.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#recTotalReceived').text('+₹' + totalReceived.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#recTotalPending').text('-₹' + totalPending.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                $('#recCountSent').text(`${countSent} transactions sent`);
                $('#recCountReceived').html(`✓ ${countReceived} Received`);
                $('#recCountPending').html(`⏳ ${countPending} Not Received`);

                $('#badgeDispatchesCount').text(dispatchesMatched);
                $('#badgeReturnsCount').text(returnsMatched);
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function updatePagination() {
                const totalCount = filteredLedgerList.length;
                const totalPages = Math.ceil(totalCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const rows = $('#amLedgerTableBody tr');
                rows.hide();

                if (totalCount === 0) {
                    $('#tablePaginationContainer').addClass('d-none');
                    return;
                } else {
                    $('#tablePaginationContainer').removeClass('d-none');
                }

                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, totalCount);

                for (let i = startIndex; i < endIndex; i++) {
                    $(rows[i]).show();
                }

                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${totalCount}</strong> entries`;
                $('#paginationSummary').html(summaryHtml);

                renderPaginationButtons(totalPages, currentPage);
            }

            function renderPaginationButtons(totalPages, current) {
                const nav = $('#paginationNav');
                nav.empty();

                const prevDisabled = current === 1 ? 'disabled' : '';
                nav.append(`
                    <li class="page-item ${prevDisabled}">
                        <button class="page-link" type="button" data-page="${current - 1}" aria-label="Previous" title="Previous Page">
                            <i class="bi bi-chevron-left"></i>
</button>
                    </li>
                `);

                let pages = [];
                if (totalPages <= 7) {
                    for (let i = 1; i <= totalPages; i++) pages.push(i);
                } else {
                    pages.push(1);
                    if (current > 3) pages.push('...');
                    const start = Math.max(2, current - 1);
                    const end = Math.min(totalPages - 1, current + 1);
                    for (let i = start; i <= end; i++) {
                        if (!pages.includes(i)) pages.push(i);
                    }
                    if (current < totalPages - 2) pages.push('...');
                    if (!pages.includes(totalPages)) pages.push(totalPages);
                }

                pages.forEach(p => {
                    if (p === '...') {
                        nav.append(`<li class="page-item disabled"><span class="page-link border-0 bg-transparent">&hellip;</span></li>`);
                    } else {
                        const active = p === current ? 'active' : '';
                        nav.append(`
                            <li class="page-item ${active}">
                                <button class="page-link" type="button" data-page="${p}">${p}</button>
                            </li>
                        `);
                    }
                });

                const nextDisabled = current === totalPages ? 'disabled' : '';
                nav.append(`
                    <li class="page-item ${nextDisabled}">
                        <button class="page-link" type="button" data-page="${current + 1}" aria-label="Next" title="Next Page">
                            <i class="bi bi-chevron-right"></i>
</button>
                    </li>
                `);
            }

            $('#perPageSelect').on('change', function () {
                perPage = parseInt($(this).val()) || 10;
                currentPage = 1;
                updatePagination();
            });

            $(document).on('click', '#paginationNav .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    currentPage = parseInt(page);
                    updatePagination();
                }
            });

            renderAccountLedgerReport();

            // Filter Event Listeners
            $('#filterPeriod').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateStartCol, #customDateEndCol').slideDown(150);
                } else {
                    $('#customDateStartCol, #customDateEndCol').slideUp(150);
                }
                currentPage = 1;
                renderAccountLedgerReport();
            });

            $('#filterStartDate, #filterEndDate, #filterSite, #filterFlowType, #filterCashFlow').on('change', function () {
                currentPage = 1;
                renderAccountLedgerReport();
            });

            $('#filterSearchInput').on('keyup input', function () {
                currentPage = 1;
                renderAccountLedgerReport();
            });

            $('#clearSearchBtn').on('click', function () {
                $('#filterSearchInput').val('');
                currentPage = 1;
                renderAccountLedgerReport();
            });

            $('#resetFilterBtn').on('click', function () {
                $('#filterPeriod').val('this_week');
                $('#filterSite').val('all');
                $('#filterFlowType').val('all');
                $('#filterCashFlow').val('all');
                $('#filterSearchInput').val('');
                $('#customDateStartCol, #customDateEndCol').hide();
                currentPage = 1;
                renderAccountLedgerReport();
            });

            // Print Action
            $('#printReportBtn').on('click', function () {
                window.print();
            });

            // PDF Export Action (Triggers native print dialog formatted for PDF export)
            $('#exportPdfBtn').on('click', function () {
                window.print();
            });

            // Excel Export Action
            $('#exportExcelBtn').on('click', function () {
                const period = $('#filterPeriod').val();
                const siteId = $('#filterSite').val();
                const flowType = $('#filterFlowType').val();
                const cashFlow = $('#filterCashFlow').val();
                const search = $('#filterSearchInput').val();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                const params = new URLSearchParams({
                    period: period,
                    site_id: siteId,
                    flow_type: flowType,
                    cash_flow: cashFlow,
                    search: search || '',
                    start_date: (period === 'custom' ? startDate : ''),
                    end_date: (period === 'custom' ? endDate : ''),
                });

                window.location.href = `${exportExcelUrl}?${params.toString()}`;
            });
        });
    </script>
@endpush
