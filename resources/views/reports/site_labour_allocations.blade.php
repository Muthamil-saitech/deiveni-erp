@extends('layouts.app')

@section('title', 'Daily Labour Allocation & Wage Sheet Report')
@section('page_title', 'Daily Labour Allocation & Wage Sheet (தினசரி தொழிலாளர் பணி & கூலி அறிக்கை)')

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

        /* Screen styling */
        .table-custom-header th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }

        .kpi-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        }

        /* Print & PDF styling */
        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 6mm;
            }

            html,
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 9pt !important;
                line-height: 1.3 !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Completely hide screen navigation, buttons and filters */
            #sidebar,
            .top-navbar,
            .breadcrumb,
            #filterCard,
            #headerActionCard,
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
            #siteLabourAllocApp {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            /* Dedicated Clean Print Header */
            .print-report-header {
                display: block !important;
                margin-bottom: 12px !important;
                padding-bottom: 8px !important;
                border-bottom: 2px solid #0f172a !important;
            }

            .card-custom,
            .card {
                border: 1px solid #94a3b8 !important;
                box-shadow: none !important;
                margin-bottom: 12px !important;
                padding: 10px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .kpi-print-grid {
                display: flex !important;
                flex-wrap: nowrap !important;
                gap: 8px !important;
                margin-bottom: 12px !important;
            }

            .kpi-print-grid>div {
                flex: 1 !important;
                border: 1px solid #cbd5e1 !important;
                padding: 6px 10px !important;
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

            /* Avoid empty trailing pages */
            * {
                box-sizing: border-box !important;
            }
        }

        @media screen {
            .print-report-header {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="siteLabourAllocApp">

        <!-- Dedicated Print Header (Visible ONLY during Print / PDF generation) -->
        <div class="print-report-header text-center">
            <h4 class="fw-bold mb-1 text-uppercase">Deiveni Constructions & Developers</h4>
            <h5 class="fw-bold mb-1">Daily Labour Allocation & Wage Sheet Report (தினசரி பணி ஒதுக்கீடு & கூலி அறிக்கை)</h5>
            <div class="small text-muted d-flex justify-content-between mt-2 pt-1 border-top">
                <span id="printFilterRange">Period: <strong>{{ $filterMeta['this_week']['label'] }}</strong></span>
                <span id="printFilterSite">Site: <strong>All Sites</strong></span>
                <span>Generated: <strong>{{ date('d M Y, h:i A') }}</strong></span>
            </div>
        </div>

        <!-- Top Header & Action Toolbar -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0" id="headerActionCard">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-people-fill text-warning me-2"></i>Daily Labour Allocation & Wage Sheet
                            (தினசரி பணி ஒதுக்கீடு & கூலி அறிக்கை)
                        </h4>
                        @if ($isAdmin)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="bi bi-shield-lock me-1"></i> Admin View
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Export & Navigation Actions -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm"
                        id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel text-success"></i>
                        <span>Export Excel</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 shadow-sm"
                        id="exportPdfBtn">
                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                        <span>Export PDF</span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm"
                        id="printReportBtn">
                        <i class="bi bi-printer"></i>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="card-custom p-3 mb-4 bg-white shadow-sm border-0" id="filterCard">
            <div class="row g-2 align-items-end">
                <!-- Date Period Filter: ORDER => today, this week, last week (date range), this month and custom date range -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-calendar3 me-1 text-primary"></i> Date Period (தேதி வடிகட்டி)
                    </label>
                    <select class="form-select form-select-sm" id="filterPeriod" name="period">
                        <option value="today" {{ ($activeFilters['period'] ?? '') === 'today' ? 'selected' : '' }}>
                            Today (இன்று - {{ $filterMeta['today']['label'] }})
                        </option>
                        <option value="this_week" {{ ($activeFilters['period'] ?? 'this_week') === 'this_week' ? 'selected' : '' }}>
                            This Week (இந்த வாரம் - {{ $filterMeta['this_week']['label'] }})
                        </option>
                        <option value="last_week" {{ ($activeFilters['period'] ?? '') === 'last_week' ? 'selected' : '' }}>
                            Last Week (முந்தைய வாரம் - {{ $filterMeta['last_week']['label'] }})
                        </option>
                        <option value="this_month" {{ ($activeFilters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>
                            This Month (இந்த மாதம் - {{ $filterMeta['this_month']['label'] }})
                        </option>
                        <option value="custom" {{ ($activeFilters['period'] ?? '') === 'custom' ? 'selected' : '' }}>
                            Custom Date Range...
                        </option>
                        <option value="all" {{ ($activeFilters['period'] ?? '') === 'all' ? 'selected' : '' }}>
                            All Dates (அனைத்து தேதிகள்)
                        </option>
                    </select>
                </div>

                <!-- Custom Date Inputs -->
                <div class="col-6 col-md-2" id="customDateStartCol" style="{{ ($activeFilters['period'] ?? '') === 'custom' ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold mb-1 text-muted">From Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterStartDate"
                        value="{{ $activeFilters['start_date'] ?? $filterMeta['this_week']['start'] }}">
                </div>
                <div class="col-6 col-md-2" id="customDateEndCol" style="{{ ($activeFilters['period'] ?? '') === 'custom' ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold mb-1 text-muted">To Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterEndDate"
                        value="{{ $activeFilters['end_date'] ?? $filterMeta['this_week']['end'] }}">
                </div>

                <!-- Project Site Filter -->
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-building me-1 text-primary"></i> Project Site (கட்டுமான தளம்)
                    </label>
                    <select class="form-select form-select-sm" id="filterSite" name="site_id">
                        <option value="all">All Sites (அனைத்து தளங்கள்)</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" {{ (string)($activeFilters['site_id'] ?? '') === (string)$site->id ? 'selected' : '' }}>
                                {{ $site->name }} @if($site->location) ({{ $site->location }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Text Search -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-search me-1 text-primary"></i> Search Trade / Particulars (தேடல்)
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search trade, mason, site...">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>

                <!-- Reset Filter Button -->
                <div class="col-12 col-md-2">
                    <button type="button" class="btn btn-sm btn-light border text-muted w-100" id="resetFilterBtn">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                    </button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <span class="small text-muted" id="filterMatchCountBadge">
                    Active filters: <strong>This Week ({{ $filterMeta['this_week']['label'] }})</strong> &bull; <strong>All
                        Sites</strong>
                </span>
                <span class="small text-muted">Current Reference Date: {{ $filterMeta['current_date'] }}</span>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4 kpi-print-grid">
            <div class="col-6 col-md-3">
                <div class="card-custom kpi-card p-3 bg-white border-start border-4 border-warning h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Man-Days Logged</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiManDays">{{ $kpis['total_man_days'] }}</h4>
                            <small class="text-muted">Manpower deployed</small>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2 rounded no-print">
                            <i class="bi bi-people-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom kpi-card p-3 bg-white border-start border-4 border-danger h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Labour Wages</div>
                            <h4 class="fw-bold mb-0 text-danger mt-1" id="kpiLabourWage">
                                ₹{{ number_format($kpis['total_labour_wage'], 2) }}</h4>
                            <small class="text-muted">Trade worker payouts</small>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2 rounded no-print">
                            <i class="bi bi-wallet2 fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom kpi-card p-3 bg-white border-start border-4 border-info h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Site Petty & Weekly Expenses</div>
                            <h4 class="fw-bold mb-0 text-info mt-1" id="kpiExpenses">
                                ₹{{ number_format($kpis['total_expenses'], 2) }}</h4>
                            <small class="text-muted">Daily petty + weekly expenses</small>
                        </div>
                        <div class="bg-info-subtle text-info p-2 rounded no-print">
                            <i class="bi bi-receipt fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom kpi-card p-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Grand Total Day Cost / Payout</div>
                            <h4 class="fw-bold mb-0 text-success mt-1" id="kpiGrandTotal">
                                ₹{{ number_format($kpis['grand_total'], 2) }}</h4>
                            <small class="text-muted">Wages + All Expenses</small>
                        </div>
                        <div class="bg-success-subtle text-success p-2 rounded no-print">
                            <i class="bi bi-cash-coin fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Daily Labour Allocation & Wage Sheet Table Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-calendar-check text-warning me-1"></i>
                        Daily Labour Allocation & Wage Register (தினசரி பணி ஒதுக்கீடு & கூலி பட்டியல்)
                    </h6>
                    <small class="text-muted">Detailed daily site allocation records with trades, wage rates, and petty
                        expenses</small>
                </div>
                <span class="badge bg-light text-dark border" id="allocCountBadge">{{ count($allocations) }}
                    Allocations</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smAllocationsTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 110px;">Date (தேதி)</th>
                            <th style="min-width: 160px;">Project Site (கட்டுமான தளம்)</th>
                            <th style="min-width: 220px;">Trade Summary (பணி விவரம்)</th>
                            <th class="text-center" style="min-width: 90px;">Workers</th>
                            <th class="text-end" style="min-width: 110px;">Labour Wage (ரூ.)</th>
                            <th class="text-end" style="min-width: 110px;">Expenses (ரூ.)</th>
                            <th class="text-end" style="min-width: 120px;">Day Total (ரூ.)</th>
                            <th class="text-center no-print" style="min-width: 110px;">Breakdown</th>
                        </tr>
                    </thead>
                    <tbody id="smAllocationsTableBody" class="small">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="3">Total Daily Labour & Wage Disbursements:</td>
                            <td class="text-center text-primary" id="footTotalWorkers">0</td>
                            <td class="text-end text-danger" id="footTotalWage">₹0</td>
                            <td class="text-end text-info" id="footTotalExpenses">₹0</td>
                            <td class="text-end text-success fs-6" id="footGrandTotal">₹0</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Allocation Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 no-print" id="allocPaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="allocPerPageSelect" style="width: 75px;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="allocPaginationSummary">
                        Showing <strong class="text-dark" id="allocPageStart">1</strong> &minus; <strong class="text-dark" id="allocPageEnd">{{ min(10, count($allocations)) }}</strong> of <strong class="text-dark" id="allocPageTotal">{{ count($allocations) }}</strong> allocations
                    </span>
                </div>
                <nav aria-label="Labour allocation table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="allocPaginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- 2. Weekly Wages & Settlement Summary Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-wallet2 text-primary me-1"></i>
                        Weekly Wages & Settlement Sheets (வாராந்திர கூலி & தீர்வு விவரம்)
                    </h6>
                    <small class="text-muted">Consolidated weekly payouts, man-days, and additional weekly site
                        expenses</small>
                </div>
                <span class="badge bg-light text-dark border" id="weeklyCountBadge">{{ count($weeklyWages) }} Weekly
                    Sheets</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smWeeklyWagesTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 150px;">Week Cycle (வாரம்)</th>
                            <th style="min-width: 160px;">Project Site (கட்டுமான தளம்)</th>
                            <th class="text-center" style="min-width: 90px;">Man-Days</th>
                            <th class="text-end" style="min-width: 120px;">Labour Wages (ரூ.)</th>
                            <th class="text-end" style="min-width: 120px;">Daily Expenses (ரூ.)</th>
                            <th class="text-end" style="min-width: 120px;">Weekly Expenses (ரூ.)</th>
                            <th class="text-end" style="min-width: 130px;">Net Payout (ரூ.)</th>
                            <th class="text-center no-print" style="min-width: 110px;">Weekly Exp</th>
                        </tr>
                    </thead>
                    <tbody id="smWeeklyWagesTableBody" class="small">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="2">Total Weekly Settlements:</td>
                            <td class="text-center text-primary" id="footWeeklyManDays">0</td>
                            <td class="text-end text-danger" id="footWeeklyWage">₹0</td>
                            <td class="text-end text-info" id="footWeeklyDailyExp">₹0</td>
                            <td class="text-end text-secondary" id="footWeeklyAddExp">₹0</td>
                            <td class="text-end text-success fs-6" id="footWeeklyNetPayout">₹0</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Weekly Wages Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 no-print" id="weeklyPaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="weeklyPerPageSelect" style="width: 75px;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="weeklyPaginationSummary">
                        Showing <strong class="text-dark" id="weeklyPageStart">1</strong> &minus; <strong class="text-dark" id="weeklyPageEnd">{{ min(10, count($weeklyWages)) }}</strong> of <strong class="text-dark" id="weeklyPageTotal">{{ count($weeklyWages) }}</strong> sheets
                    </span>
                </div>
                <nav aria-label="Weekly wages table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="weeklyPaginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Day Breakdown Modal -->
        <div class="modal fade" id="dayBreakdownModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <div>
                            <h6 class="modal-title fw-bold mb-0 text-dark" id="modalDayTitle">Day Allocation Breakdown</h6>
                            <small class="text-muted" id="modalDaySubtitle"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <h6 class="fw-bold text-dark mb-2">Worker Trades & Rates (தொழிலாளர்கள் & கூலி)</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Trade (English & தமிழ்)</th>
                                        <th class="text-center">Count</th>
                                        <th class="text-end">Allocated Rate (ரூ.)</th>
                                        <th class="text-end">Master Rate (ரூ.)</th>
                                        <th class="text-end">Subtotal (ரூ.)</th>
                                    </tr>
                                </thead>
                                <tbody id="modalWorkersBody"></tbody>
                            </table>
                        </div>

                        <h6 class="fw-bold text-dark mb-2">Daily Site Petty Expenses (தள இதர செலவுகள்)</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item / Title</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price (ரூ.)</th>
                                        <th class="text-end">Amount (ரூ.)</th>
                                    </tr>
                                </thead>
                                <tbody id="modalExpensesBody"></tbody>
                            </table>
                        </div>

                        <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <div class="small text-muted">Allocated & logged to database records</div>
                            <div class="text-end">
                                <span class="small text-muted me-2">Grand Total:</span>
                                <span class="fw-bold fs-5 text-primary" id="modalGrandTotal">₹0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Additional Expenses Breakdown Modal -->
        <div class="modal fade" id="weeklyBreakdownModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <div>
                            <h6 class="modal-title fw-bold mb-0 text-dark" id="weeklyModalTitle">Weekly Additional Expenses
                            </h6>
                            <small class="text-muted" id="weeklyModalSubtitle"></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Description</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Unit Price (ரூ.)</th>
                                        <th class="text-end">Amount (ரூ.)</th>
                                    </tr>
                                </thead>
                                <tbody id="modalWeeklyExpensesBody"></tbody>
                            </table>
                        </div>

                        <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <div class="small text-muted">Weekly additional expense items</div>
                            <div class="text-end">
                                <span class="small text-muted me-2">Weekly Expenses Total:</span>
                                <span class="fw-bold fs-6 text-primary" id="modalWeeklyGrandTotal">₹0</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Dynamic initial datasets passed directly from Controller / Database
            let rawAllocations = @json($allocations);
            let rawWeeklyWages = @json($weeklyWages);
            const filterMeta = @json($filterMeta);

            let filteredAllocationsList = [];
            let filteredWeeklyWagesList = [];

            /**
             * Check whether a given ISO date string falls within the active date period filter.
             */
            function checkDateInRange(rawDateStr) {
                if (!rawDateStr) return true;
                const period = $('#filterPeriod').val();
                if (period === 'all') return true;

                if (period === 'today') {
                    return rawDateStr === filterMeta.today.start;
                } else if (period === 'this_week') {
                    return rawDateStr >= filterMeta.this_week.start && rawDateStr <= filterMeta.this_week.end;
                } else if (period === 'last_week') {
                    return rawDateStr >= filterMeta.last_week.start && rawDateStr <= filterMeta.last_week.end;
                } else if (period === 'this_month') {
                    return rawDateStr >= filterMeta.this_month.start && rawDateStr <= filterMeta.this_month.end;
                } else if (period === 'custom') {
                    const s = $('#filterStartDate').val();
                    const e = $('#filterEndDate').val();
                    if (s && rawDateStr < s) return false;
                    if (e && rawDateStr > e) return false;
                    return true;
                }
                return true;
            }

            /**
             * Check whether a weekly wage cycle falls within the active date period filter.
             */
            function checkWeekInRange(rawStartStr, rawEndStr) {
                const period = $('#filterPeriod').val();
                if (period === 'all') return true;

                let filterStart = null;
                let filterEnd = null;

                if (period === 'today') {
                    filterStart = filterMeta.today.start;
                    filterEnd = filterMeta.today.end;
                } else if (period === 'this_week') {
                    filterStart = filterMeta.this_week.start;
                    filterEnd = filterMeta.this_week.end;
                } else if (period === 'last_week') {
                    filterStart = filterMeta.last_week.start;
                    filterEnd = filterMeta.last_week.end;
                } else if (period === 'this_month') {
                    filterStart = filterMeta.this_month.start;
                    filterEnd = filterMeta.this_month.end;
                } else if (period === 'custom') {
                    filterStart = $('#filterStartDate').val();
                    filterEnd = $('#filterEndDate').val();
                }

                if (!filterStart && !filterEnd) return true;

                if (filterStart && filterEnd) {
                    return (rawStartStr <= filterEnd && rawEndStr >= filterStart);
                } else if (filterStart) {
                    return rawEndStr >= filterStart;
                } else if (filterEnd) {
                    return rawStartStr <= filterEnd;
                }
                return true;
            }

            /**
             * Render the Daily Allocation and Weekly Wages report tables and KPI counters dynamically.
             */
            function renderReport() {
                const selectedSite = $('#filterSite').val();
                const searchQuery = ($('#filterSearchInput').val() || '').toLowerCase().trim();

                // 1. Process Daily Allocations
                let totalWorkers = 0;
                let totalWage = 0;
                let totalDailyExpenses = 0;
                let totalGrandTotal = 0;
                let allocMatchCount = 0;

                filteredAllocationsList = [];
                const tbodyAlloc = $('#smAllocationsTableBody');
                tbodyAlloc.empty();

                rawAllocations.forEach(function (al, index) {
                    // Filter by site
                    if (selectedSite !== 'all' && String(al.project_site_id) !== String(selectedSite)) return;

                    // Filter by date
                    if (!checkDateInRange(al.raw_date)) return;

                    // Filter by search query
                    if (searchQuery) {
                        const haystack = `${al.date} ${al.site_name || ''} ${al.trade_summary || ''}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    allocMatchCount++;
                    filteredAllocationsList.push({ ...al, originalIndex: index });

                    const wCount = parseInt(al.total_labour_count) || 0;
                    const wWage = parseFloat(al.total_labour_wage) || 0;
                    const exp = parseFloat(al.total_expenses) || 0;
                    const gTotal = parseFloat(al.grand_total) || (wWage + exp);

                    totalWorkers += wCount;
                    totalWage += wWage;
                    totalDailyExpenses += exp;
                    totalGrandTotal += gTotal;

                    tbodyAlloc.append(`
                                <tr>
                                    <td class="fw-bold text-dark">${al.date}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">${al.site_name}</div>
                                    </td>
                                    <td>
                                        <div class="small text-dark">${al.trade_summary || '-'}</div>
                                    </td>
                                    <td class="text-center fw-bold text-primary">${wCount}</td>
                                    <td class="text-end fw-semibold text-danger">₹${wWage.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end text-info">₹${exp.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end fw-bold text-success">₹${gTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-center no-print">
                                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 view-day-breakdown-btn" data-index="${index}">
                                            <i class="bi bi-eye me-1"></i>View
                                        </button>
                                    </td>
                                </tr>
                            `);
                });

                if (allocMatchCount === 0) {
                    tbodyAlloc.html('<tr><td colspan="8" class="text-center text-muted py-4">No daily labour allocations found for the selected filter criteria.</td></tr>');
                }

                // 2. Process Weekly Wages
                let weeklyManDays = 0;
                let weeklyWagesTotal = 0;
                let weeklyDailyExpTotal = 0;
                let weeklyAddExpTotal = 0;
                let weeklyNetPayoutTotal = 0;
                let weeklyMatchCount = 0;

                filteredWeeklyWagesList = [];
                const tbodyWeekly = $('#smWeeklyWagesTableBody');
                tbodyWeekly.empty();

                rawWeeklyWages.forEach(function (ww, index) {
                    // Filter by site
                    if (selectedSite !== 'all' && String(ww.project_site_id) !== String(selectedSite)) return;

                    // Filter by date cycle
                    if (!checkWeekInRange(ww.raw_start_date, ww.raw_end_date)) return;

                    // Filter by search query
                    if (searchQuery) {
                        const haystack = `${ww.week_label} ${ww.site_name || ''}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    weeklyMatchCount++;
                    filteredWeeklyWagesList.push({ ...ww, originalIndex: index });

                    const mDays = parseInt(ww.total_labour_man_days) || 0;
                    const wWage = parseFloat(ww.total_labour_wages) || 0;
                    const dExp = parseFloat(ww.total_daily_expenses) || 0;
                    const addExp = parseFloat(ww.total_weekly_expenses) || 0;
                    const netPayout = parseFloat(ww.net_payout) || (wWage + dExp + addExp);

                    weeklyManDays += mDays;
                    weeklyWagesTotal += wWage;
                    weeklyDailyExpTotal += dExp;
                    weeklyAddExpTotal += addExp;
                    weeklyNetPayoutTotal += netPayout;

                    tbodyWeekly.append(`
                                <tr>
                                    <td class="fw-bold text-dark">${ww.week_label}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">${ww.site_name}</div>
                                    </td>
                                    <td class="text-center fw-bold text-primary">${mDays}</td>
                                    <td class="text-end fw-semibold text-danger">₹${wWage.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end text-info">₹${dExp.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end text-secondary">₹${addExp.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-end fw-bold text-success">₹${netPayout.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    <td class="text-center no-print">
                                        ${ww.expenses && ww.expenses.length > 0 ? `
                                            <button type="button" class="btn btn-xs btn-outline-info py-0 px-2 view-weekly-breakdown-btn" data-index="${index}">
                                                <i class="bi bi-eye me-1"></i>${ww.expenses.length} Exp
                                            </button>
                                        ` : '<span class="text-muted small">-</span>'}
                                    </td>
                                </tr>
                            `);
                });

                if (weeklyMatchCount === 0) {
                    tbodyWeekly.html('<tr><td colspan="8" class="text-center text-muted py-4">No weekly wage sheets found for the selected filter criteria.</td></tr>');
                }

                // 3. Update Top KPIs
                const combinedExpenses = totalDailyExpenses + weeklyAddExpTotal;
                const grandTotalCost = totalWage + combinedExpenses;

                $('#kpiManDays').text(totalWorkers);
                $('#kpiLabourWage').text('₹' + totalWage.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiExpenses').text('₹' + combinedExpenses.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiGrandTotal').text('₹' + grandTotalCost.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

                // 4. Update Daily Allocation Table Footers
                $('#footTotalWorkers').text(totalWorkers);
                $('#footTotalWage').text('₹' + totalWage.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#footTotalExpenses').text('₹' + totalDailyExpenses.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#footGrandTotal').text('₹' + totalGrandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#allocCountBadge').text(`${allocMatchCount} Allocations`);

                // 5. Update Weekly Wages Table Footers
                $('#footWeeklyManDays').text(weeklyManDays);
                $('#footWeeklyWage').text('₹' + weeklyWagesTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#footWeeklyDailyExp').text('₹' + weeklyDailyExpTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#footWeeklyAddExp').text('₹' + weeklyAddExpTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#footWeeklyNetPayout').text('₹' + weeklyNetPayoutTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#weeklyCountBadge').text(`${weeklyMatchCount} Weekly Sheets`);

                // 6. Update Active Filter Badges
                const pText = $('#filterPeriod option:selected').text();
                const sText = $('#filterSite option:selected').text();
                $('#filterMatchCountBadge').html(`Active filters: <strong>${pText}</strong> &bull; <strong>${sText}</strong>`);
                $('#printFilterRange').html(`Period: <strong>${pText}</strong>`);
                $('#printFilterSite').html(`Site: <strong>${sText}</strong>`);

                updateAllocPagination();
                updateWeeklyPagination();
            }

            let allocCurrentPage = 1;
            let allocPerPage = parseInt($('#allocPerPageSelect').val()) || 10;
            let weeklyCurrentPage = 1;
            let weeklyPerPage = parseInt($('#weeklyPerPageSelect').val()) || 10;

            function updateAllocPagination() {
                const totalCount = filteredAllocationsList.length;
                const totalPages = Math.ceil(totalCount / allocPerPage) || 1;

                if (allocCurrentPage > totalPages) allocCurrentPage = totalPages;
                if (allocCurrentPage < 1) allocCurrentPage = 1;

                const rows = $('#smAllocationsTableBody tr');
                rows.hide();

                if (totalCount === 0) {
                    $('#allocPaginationContainer').addClass('d-none');
                    $(rows).show();
                    return;
                } else {
                    $('#allocPaginationContainer').removeClass('d-none');
                }

                const startIndex = (allocCurrentPage - 1) * allocPerPage;
                const endIndex = Math.min(startIndex + allocPerPage, totalCount);

                for (let i = startIndex; i < endIndex; i++) {
                    $(rows[i]).show();
                }

                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${totalCount}</strong> allocations`;
                $('#allocPaginationSummary').html(summaryHtml);

                renderNavButtons($('#allocPaginationNav'), totalPages, allocCurrentPage);
            }

            function updateWeeklyPagination() {
                const totalCount = filteredWeeklyWagesList.length;
                const totalPages = Math.ceil(totalCount / weeklyPerPage) || 1;

                if (weeklyCurrentPage > totalPages) weeklyCurrentPage = totalPages;
                if (weeklyCurrentPage < 1) weeklyCurrentPage = 1;

                const rows = $('#smWeeklyWagesTableBody tr');
                rows.hide();

                if (totalCount === 0) {
                    $('#weeklyPaginationContainer').addClass('d-none');
                    $(rows).show();
                    return;
                } else {
                    $('#weeklyPaginationContainer').removeClass('d-none');
                }

                const startIndex = (weeklyCurrentPage - 1) * weeklyPerPage;
                const endIndex = Math.min(startIndex + weeklyPerPage, totalCount);

                for (let i = startIndex; i < endIndex; i++) {
                    $(rows[i]).show();
                }

                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${totalCount}</strong> sheets`;
                $('#weeklyPaginationSummary').html(summaryHtml);

                renderNavButtons($('#weeklyPaginationNav'), totalPages, weeklyCurrentPage);
            }

            function renderNavButtons(nav, totalPages, current) {
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

            $('#allocPerPageSelect').on('change', function () {
                allocPerPage = parseInt($(this).val()) || 10;
                allocCurrentPage = 1;
                updateAllocPagination();
            });

            $(document).on('click', '#allocPaginationNav .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    allocCurrentPage = parseInt(page);
                    updateAllocPagination();
                }
            });

            $('#weeklyPerPageSelect').on('change', function () {
                weeklyPerPage = parseInt($(this).val()) || 10;
                weeklyCurrentPage = 1;
                updateWeeklyPagination();
            });

            $(document).on('click', '#weeklyPaginationNav .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    weeklyCurrentPage = parseInt(page);
                    updateWeeklyPagination();
                }
            });

            renderReport();

            // Filter Event Listeners
            $('#filterPeriod').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateStartCol, #customDateEndCol').slideDown(150);
                } else {
                    $('#customDateStartCol, #customDateEndCol').slideUp(150);
                }
                allocCurrentPage = 1;
                weeklyCurrentPage = 1;
                renderReport();
            });

            $('#filterStartDate, #filterEndDate, #filterSite').on('change input', function () {
                allocCurrentPage = 1;
                weeklyCurrentPage = 1;
                renderReport();
            });

            $('#filterSearchInput').on('keyup input', function () {
                allocCurrentPage = 1;
                weeklyCurrentPage = 1;
                renderReport();
            });

            $('#clearSearchBtn').on('click', function () {
                $('#filterSearchInput').val('');
                allocCurrentPage = 1;
                weeklyCurrentPage = 1;
                renderReport();
            });

            $('#resetFilterBtn').on('click', function () {
                $('#filterPeriod').val('this_week');
                $('#filterSite').val('all');
                $('#filterSearchInput').val('');
                $('#customDateStartCol, #customDateEndCol').hide();
                allocCurrentPage = 1;
                weeklyCurrentPage = 1;
                renderReport();
            });

            // Day Allocation Breakdown Modal
            $(document).on('click', '.view-day-breakdown-btn', function () {
                const idx = $(this).data('index');
                const alloc = rawAllocations[idx];
                if (!alloc) return;

                $('#modalDayTitle').text(`Daily Allocation - ${alloc.date} (${alloc.site_name})`);
                $('#modalDaySubtitle').text(`Trade summary: ${alloc.trade_summary || '-'}`);

                const workersBody = $('#modalWorkersBody');
                workersBody.empty();
                if (alloc.workers && alloc.workers.length > 0) {
                    alloc.workers.forEach(function (w) {
                        const sub = w.subtotal || (w.count * w.rate);
                        workersBody.append(`
                                    <tr>
                                        <td class="fw-semibold text-dark">
                                            ${w.name_en} <span class="badge bg-secondary-subtle text-secondary ms-1">${w.name_ta || ''}</span>
                                        </td>
                                        <td class="text-center fw-bold">${w.count}</td>
                                        <td class="text-end text-primary fw-bold">₹${parseFloat(w.rate || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-end text-muted">₹${parseFloat(w.master_rate || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-end fw-bold text-success">₹${parseFloat(sub).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    </tr>
                                `);
                    });
                } else {
                    workersBody.html('<tr><td colspan="5" class="text-center text-muted py-2">No workers details recorded for this date.</td></tr>');
                }

                const expensesBody = $('#modalExpensesBody');
                expensesBody.empty();
                if (alloc.expenses && alloc.expenses.length > 0) {
                    alloc.expenses.forEach(function (exp) {
                        expensesBody.append(`
                                    <tr>
                                        <td class="fw-semibold text-dark">${exp.title}</td>
                                        <td class="text-center">${exp.qty}</td>
                                        <td class="text-end text-muted">₹${parseFloat(exp.unit_price || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-end fw-bold text-info">₹${parseFloat(exp.amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    </tr>
                                `);
                    });
                } else {
                    expensesBody.html('<tr><td colspan="4" class="text-center text-muted py-2">No daily site petty expenses on this date.</td></tr>');
                }

                $('#modalGrandTotal').text('₹' + parseFloat(alloc.grand_total || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                new bootstrap.Modal(document.getElementById('dayBreakdownModal')).show();
            });

            // Weekly Additional Expenses Breakdown Modal
            $(document).on('click', '.view-weekly-breakdown-btn', function () {
                const idx = $(this).data('index');
                const ww = rawWeeklyWages[idx];
                if (!ww) return;

                $('#weeklyModalTitle').text(`Weekly Site Expenses - ${ww.week_label}`);
                $('#weeklyModalSubtitle').text(`Project Site: ${ww.site_name}`);

                const weeklyExpBody = $('#modalWeeklyExpensesBody');
                weeklyExpBody.empty();
                let expTotal = 0;

                if (ww.expenses && ww.expenses.length > 0) {
                    ww.expenses.forEach(function (exp) {
                        const amt = parseFloat(exp.amount || 0);
                        expTotal += amt;
                        weeklyExpBody.append(`
                                    <tr>
                                        <td class="fw-semibold text-dark">${exp.title}</td>
                                        <td class="text-center">${exp.qty}</td>
                                        <td class="text-end text-muted">₹${parseFloat(exp.unit_price || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                        <td class="text-end fw-bold text-info">₹${amt.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                                    </tr>
                                `);
                    });
                } else {
                    weeklyExpBody.html('<tr><td colspan="4" class="text-center text-muted py-2">No weekly additional expenses logged for this week.</td></tr>');
                }

                $('#modalWeeklyGrandTotal').text('₹' + expTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                new bootstrap.Modal(document.getElementById('weeklyBreakdownModal')).show();
            });

            // Print Action
            $('#printReportBtn').on('click', function () {
                window.print();
            });

            // PDF Export Action (Triggers native browser PDF generator / print preview with dedicated landscape styling)
            $('#exportPdfBtn').on('click', function () {
                window.print();
            });

            // Excel Export Action
            $('#exportExcelBtn').on('click', function () {
                const period = $('#filterPeriod').val();
                const siteId = $('#filterSite').val();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                const params = new URLSearchParams({
                    period: period,
                    site_id: siteId,
                    start_date: (period === 'custom' ? startDate : ''),
                    end_date: (period === 'custom' ? endDate : ''),
                });

                window.location.href = `{{ route('reports.site.labour-allocations.export-excel') }}?${params.toString()}`;
            });
        });
    </script>
@endpush