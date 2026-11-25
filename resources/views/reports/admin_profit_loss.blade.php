@extends('layouts.app')

@section('title', 'Admin Executive Profit & Loss Report')
@section('page_title', 'Executive Profit & Loss Report ')

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
            #adminPnlApp {
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

            .kpi-print-grid>div {
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

            * {
                box-sizing: border-box !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="adminPnlApp">

        <!-- Dedicated Print & PDF Header (Visible strictly in Print / PDF export) -->
        <div class="print-report-header d-none">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1 text-uppercase text-dark">DEIVENI CONSTRUCTIONS & DEVELOPERS</h4>
                    <h5 class="fw-bold mb-0 text-secondary">Executive Profit & Loss Report</h5>
                    <small class="text-muted">High-level financial performance: Project-wise revenue collections vs
                        operating expenditures, net profit margins, cost analysis, and financial health.</small>
</div>
                <div class="text-end">
                    <span class="badge border border-dark text-dark px-2 py-1 mb-1 d-inline-block">CONFIDENTIAL EXECUTIVE
                        AUDIT</span>
                    <div class="small text-muted">Generated: <strong>{{ $filterMeta['current_date'] }}</strong></div>
                    <div class="small text-muted" id="printActivePeriod">Period:
                        <strong>{{ ucfirst(str_replace('_', ' ', $activeFilters['period'] ?? 'this_week')) }}</strong>
</div>
                    <div class="small text-muted" id="printActiveSite">Site: <strong>All Sites</strong></div>
</div>
</div>

            <!-- Print KPI Summary Row -->
            <div class="kpi-print-grid mt-2">
                <div>
                    <span class="text-muted small">Total Contract Portfolio</span>
                    <div class="fw-bold fs-6 text-dark" id="printKpiContractValue">
                        ₹{{ number_format($kpis['contract_portfolio'], 2) }}</div>
</div>
                <div>
                    <span class="text-muted small">Total Revenue Inflow</span>
                    <div class="fw-bold fs-6 text-success" id="printKpiRevenue">
                        ₹{{ number_format($kpis['total_revenue'], 2) }}</div>
</div>
                <div>
                    <span class="text-muted small">Total Project Cost</span>
                    <div class="fw-bold fs-6 text-danger" id="printKpiExpense">
                        ₹{{ number_format($kpis['total_expense'], 2) }}</div>
</div>
                <div>
                    <span class="text-muted small">Net Operating Profit</span>
                    <div class="fw-bold fs-6 text-primary" id="printKpiNetProfit">
                        ₹{{ number_format($kpis['net_profit'], 2) }}</div>
</div>
</div>
</div>

        <!-- Top Header & Action Toolbar -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0" id="headerActionCard">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-graph-up-arrow text-primary me-2"></i>Executive Profit & Loss Report

</h4>
</div>
</div>

                <!-- Action Buttons: Export Excel, PDF & Print -->
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-success btn-sm" id="exportExcelBtn">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="exportPdfBtn">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="printReportBtn">
                        <i class="bi bi-printer me-1"></i> Print
</button>
</div>
</div>
</div>

        <!-- Filter Toolbar -->
        <div class="card-custom p-3 mb-4 bg-white shadow-sm border-0" id="filterCard">
            <div class="row g-2 align-items-end">
                <!-- Date Period Filter -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-calendar3 me-1 text-primary"></i> Date Period 
</label>
                    <select class="form-select form-select-sm" id="filterPeriod">
                        <option value="this_week" selected>This Week ({{ $filterMeta['this_week']['label'] }})</option>
                        <option value="last_week">Last Week ({{ $filterMeta['last_week']['label'] }})</option>
                        <option value="today">Today ({{ $filterMeta['today']['label'] }})</option>
                        <option value="this_month">This Month ({{ $filterMeta['this_month']['label'] }})</option>
                        <option value="all">All Dates</option>
                        <option value="custom">Custom Date Range...</option>
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

                <!-- Project Site Filter -->
                <div class="col-6 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-building me-1 text-primary"></i> Project Site 
</label>
                    <select class="form-select form-select-sm" id="filterSite">
                        <option value="all">All Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
</div>

                <!-- Text Search -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-search me-1 text-primary"></i> Quick Search 
</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search site, client, location, work type...">
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
                <span class="small text-muted" id="activeDateBadge">Current Date:
                    <strong>{{ $filterMeta['current_date'] }}</strong></span>
</div>
</div>

        <!-- Admin P&L KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-info h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Contract Portfolio</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiContractValue">
                                ₹{{ number_format($kpis['contract_portfolio'], 2) }}</h4>
                            <small class="text-muted">Total estimate / pipeline</small>
</div>
                        <div class="bg-info-subtle text-info p-2 rounded">
                            <i class="bi bi-building-check fs-5"></i>
</div>
</div>
</div>
</div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Revenue Inflow</div>
                            <h4 class="fw-bold mb-0 text-success mt-1" id="kpiRevenue">
                                ₹{{ number_format($kpis['total_revenue'], 2) }}</h4>
                            <small class="text-muted">Client advance receipts</small>
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
                            <div class="text-muted small fw-semibold">Total Project Cost</div>
                            <h4 class="fw-bold mb-0 text-danger mt-1" id="kpiExpense">
                                ₹{{ number_format($kpis['total_expense'], 2) }}</h4>
                            <small class="text-muted">Wages, materials, suppliers</small>
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
                            <div class="text-muted small fw-semibold">Net Profit / Surplus</div>
                            <h4 class="fw-bold mb-0 text-primary mt-1" id="kpiNetProfit">
                                ₹{{ number_format($kpis['net_profit'], 2) }}</h4>
                            <small class="fw-semibold text-primary" id="kpiMarginBadge">{{ $kpis['net_margin'] }}% Net
                                Margin</small>
</div>
                        <div class="bg-primary-subtle text-primary p-2 rounded">
                            <i class="bi bi-piggy-bank-fill fs-5"></i>
</div>
</div>
</div>
</div>
</div>

        <!-- Site-by-Site Profit & Loss Table Card -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-table me-1 text-primary"></i> Site-Wise Profit & Loss Analysis 
</h6>
                    <small class="text-muted">Revenue collections, trade wages, materials, and operating margins for each
                        site.</small>
</div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-primary-subtle text-primary border" id="sitesCountBadge">{{ count($reportSites) }}
                        Sites</span>
                    <span class="badge bg-success-subtle text-success border" id="overallProfitBadge">Overall Profit:
                        ₹{{ number_format($kpis['net_profit'], 2) }}</span>
</div>
</div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="adminPnlSitesTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 180px;">Project Site & Client</th>
                            <th class="text-end" style="min-width: 110px;">Contract Estimate</th>
                            <th class="text-end" style="min-width: 110px;">Revenue (Inflow)</th>
                            <th class="text-end" style="min-width: 100px;">Labour Wages</th>
                            <th class="text-end" style="min-width: 110px;">Materials & Tools</th>
                            <th class="text-end" style="min-width: 100px;">Supplier Payouts</th>
                            <th class="text-end" style="min-width: 90px;">Sundries</th>
                            <th class="text-end" style="min-width: 100px;">Total Spent</th>
                            <th class="text-end" style="min-width: 120px;">Net Profit / (Loss)</th>
                            <th class="text-center" style="min-width: 90px;">Margin %</th>
                            <th style="min-width: 140px;">Budget Progress</th>
                            <th class="text-center" style="min-width: 90px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="adminPnlSitesTableBody" class="small">
                        <!-- Populated dynamically via render -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td>Totals :</td>
                            <td class="text-end text-dark" id="footEstimate">₹0.00</td>
                            <td class="text-end text-success" id="footRevenue">₹0.00</td>
                            <td class="text-end text-danger" id="footLabour">₹0.00</td>
                            <td class="text-end text-danger" id="footMaterial">₹0.00</td>
                            <td class="text-end text-danger" id="footSupplier">₹0.00</td>
                            <td class="text-end text-danger" id="footSundries">₹0.00</td>
                            <td class="text-end text-danger" id="footTotalExpense">₹0.00</td>
                            <td class="text-end" id="footNetProfit">₹0.00</td>
                            <td class="text-center text-primary" id="footMargin">0%</td>
                            <td colspan="2" class="text-center text-muted">-</td>
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
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, count($reportSites)) }}</strong> of <strong class="text-dark" id="pageTotal">{{ count($reportSites) }}</strong> sites
</span>
</div>
                <nav aria-label="Admin PnL table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

        <!-- Profit & Loss Statement & Cost Breakdown Cards Row -->
        <div class="row g-3">
            <!-- Left: Formal P&L Statement -->
            <div class="col-12 col-lg-7">
                <div class="card-custom p-3 p-md-4 bg-white shadow-sm border-0 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-file-earmark-spreadsheet me-1 text-primary"></i> Executive Income & Expense
                            Statement 
</h6>
                        <span class="badge bg-light text-muted border">Filtered Period</span>
</div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                <tr class="table-light">
                                    <td colspan="2" class="fw-bold text-uppercase text-success small">
                                        <i class="bi bi-plus-circle me-1"></i> A. Operating Revenue / Income 
                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Client Advance Payments </td>
                                    <td class="text-end fw-semibold text-success" id="stmtClientAdvance">
                                        ₹{{ number_format($statement['client_advances'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Stage Billing & Other Inflows </td>
                                    <td class="text-end fw-semibold text-success" id="stmtOtherInflows">
                                        ₹{{ number_format($statement['other_inflows'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold border-top border-bottom">
                                    <td class="text-dark">Gross Revenue :</td>
                                    <td class="text-end text-success fs-6" id="stmtTotalRevenue">
                                        ₹{{ number_format($statement['gross_revenue'], 2) }}</td>
                                </tr>

                                <tr class="table-light">
                                    <td colspan="2" class="fw-bold text-uppercase text-danger small pt-3">
                                        <i class="bi bi-dash-circle me-1"></i> B. Cost of Construction & Operating Expenses

                                    </td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Daily Worker Wages Disbursed </td>
                                    <td class="text-end fw-semibold text-danger" id="stmtWages">
                                        ₹{{ number_format($statement['worker_wages'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Material Purchases & Consumables </td>
                                    <td class="text-end fw-semibold text-danger" id="stmtMaterials">
                                        ₹{{ number_format($statement['materials_consumables'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Power Tools & Machinery Rentals </td>
                                    <td class="text-end fw-semibold text-danger" id="stmtTools">
                                        ₹{{ number_format($statement['tools_rentals'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Supplier Invoices Settled </td>
                                    <td class="text-end fw-semibold text-danger" id="stmtSuppliers">
                                        ₹{{ number_format($statement['supplier_invoices'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ps-3 text-muted">Site Handling & Sundry Expenses </td>
                                    <td class="text-end fw-semibold text-danger" id="stmtSundries">
                                        ₹{{ number_format($statement['site_sundries'], 2) }}</td>
                                </tr>
                                <tr class="fw-bold border-top border-bottom">
                                    <td class="text-dark">Total Construction & Operating Expenses :</td>
                                    <td class="text-end text-danger fs-6" id="stmtTotalExpenses">
                                        ₹{{ number_format($statement['total_expenses'], 2) }}</td>
                                </tr>

                                <tr class="table-primary fw-bold">
                                    <td class="fs-6 text-dark py-2">
                                        <i class="bi bi-award me-1 text-primary"></i> NET OPERATING PROFIT / (LOSS):
                                    </td>
                                    <td class="text-end fs-5 py-2 text-primary" id="stmtNetProfit">
                                        ₹{{ number_format($statement['net_profit'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
</div>
</div>
</div>

            <!-- Right: Cost Allocation & Financial Health -->
            <div class="col-12 col-lg-5">
                <div class="card-custom p-3 p-md-4 bg-white shadow-sm border-0 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="bi bi-pie-chart me-1 text-primary"></i> Expense Distribution 
</h6>
                        <span class="badge bg-light text-muted border">Cost Breakdown</span>
</div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-semibold mb-1">
                            <span><i class="bi bi-people me-1 text-warning"></i> Labour Wages</span>
                            <span id="distLabourText">₹{{ number_format($distribution['labour']['amount'], 2) }}
                                ({{ $distribution['labour']['pct'] }}%)</span>
</div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-warning" id="distLabourBar"
                                style="width: {{ $distribution['labour']['pct'] }}%;"></div>
</div>
</div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-semibold mb-1">
                            <span><i class="bi bi-boxes me-1 text-info"></i> Materials & Tools</span>
                            <span id="distMaterialText">₹{{ number_format($distribution['material']['amount'], 2) }}
                                ({{ $distribution['material']['pct'] }}%)</span>
</div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-info" id="distMaterialBar"
                                style="width: {{ $distribution['material']['pct'] }}%;"></div>
</div>
</div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small fw-semibold mb-1">
                            <span><i class="bi bi-truck me-1 text-primary"></i> Supplier Payments</span>
                            <span id="distSupplierText">₹{{ number_format($distribution['supplier']['amount'], 2) }}
                                ({{ $distribution['supplier']['pct'] }}%)</span>
</div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" id="distSupplierBar"
                                style="width: {{ $distribution['supplier']['pct'] }}%;"></div>
</div>
</div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between small fw-semibold mb-1">
                            <span><i class="bi bi-receipt me-1 text-secondary"></i> Site Petty & Sundries</span>
                            <span id="distSundryText">₹{{ number_format($distribution['sundry']['amount'], 2) }}
                                ({{ $distribution['sundry']['pct'] }}%)</span>
</div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-secondary" id="distSundryBar"
                                style="width: {{ $distribution['sundry']['pct'] }}%;"></div>
</div>
</div>

                    <!-- Financial Health Alert Box -->
                    <div class="card p-3 border-0 bg-light rounded-3" id="healthAlertBox">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi {{ $health['icon'] }} fs-5" id="healthIcon"></i>
                            <h6 class="fw-bold mb-0 text-dark" id="healthTitle">{{ $health['title'] }}</h6>
</div>
                        <p class="small text-muted mb-0" id="healthDesc">
                            {{ $health['desc'] }}
                        </p>
</div>
</div>
</div>
</div>

</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const rawAllSites = @json($allSitesData);
            const initialReportSites = @json($reportSites);
            const initialKpis = @json($kpis);
            const initialStatement = @json($statement);
            const initialDistribution = @json($distribution);
            const initialHealth = @json($health);
            const exportExcelBaseUrl = "{{ route('reports.admin.profit-loss.export-excel') }}";
            const ajaxReportUrl = "{{ route('reports.admin.profit-loss') }}";

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function formatInr(val) {
                const num = parseFloat(val) || 0;
                return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function renderPnlView(reportData) {
                const sites = reportData.sites || [];
                const kpis = reportData.kpis || {};
                const stmt = reportData.statement || {};
                const dist = reportData.distribution || {};
                const health = reportData.health || {};

                const tbody = $('#adminPnlSitesTableBody');
                tbody.empty();

                let totEstimate = 0;
                let totRevenue = 0;
                let totLabour = 0;
                let totMaterial = 0;
                let totSupplier = 0;
                let totSundries = 0;
                let totSpent = 0;
                let totNetProfit = 0;

                if (sites.length === 0) {
                    tbody.html('<tr><td colspan="12" class="text-center text-muted py-5"><i class="bi bi-folder-x fs-2 d-block mb-2 text-secondary"></i><div class="fw-semibold">No project sites matched the selected filters.</div><small>Try selecting "All Dates" or adjusting your search filters.</small></td></tr>');
                } else {
                    sites.forEach(function (site) {
                        const est = parseFloat(site.estimate) || 0;
                        const rev = parseFloat(site.revenue) || 0;
                        const lab = parseFloat(site.labour) || 0;
                        const mat = parseFloat(site.material) || 0;
                        const sup = parseFloat(site.supplier) || 0;
                        const sun = parseFloat(site.sundries) || 0;
                        const spent = parseFloat(site.total_spent) || (lab + mat + sup + sun);
                        const netProfit = parseFloat(site.net_profit) || (rev - spent);
                        const marginPct = site.margin_pct !== undefined ? site.margin_pct : (rev > 0 ? ((netProfit / rev) * 100).toFixed(1) : (spent > 0 ? -100.0 : 0.0));
                        const budgetPct = site.budget_progress !== undefined ? site.budget_progress : (est > 0 ? Math.min(100, Math.round((spent / est) * 100)) : 0);

                        totEstimate += est;
                        totRevenue += rev;
                        totLabour += lab;
                        totMaterial += mat;
                        totSupplier += sup;
                        totSundries += sun;
                        totSpent += spent;
                        totNetProfit += netProfit;

                        const profitClass = netProfit > 0 ? 'text-success' : (netProfit < 0 ? 'text-danger' : 'text-muted');
                        const statusBadge = netProfit > 0
                            ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Profitable</span>'
                            : (netProfit === 0 ? '<span class="badge bg-light text-muted border">Break-even</span>'
                                : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Deficit</span>');

                        tbody.append(`
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">${escapeHtml(site.name)}</div>
                                            <div class="text-muted small">${escapeHtml(site.client || 'Client')} &bull; ${escapeHtml(site.location || '')}</div>
                                        </td>
                                        <td class="text-end fw-semibold text-dark">${formatInr(est)}</td>
                                        <td class="text-end fw-bold text-success">${formatInr(rev)}</td>
                                        <td class="text-end text-danger">${formatInr(lab)}</td>
                                        <td class="text-end text-danger">${formatInr(mat)}</td>
                                        <td class="text-end text-danger">${formatInr(sup)}</td>
                                        <td class="text-end text-danger">${formatInr(sun)}</td>
                                        <td class="text-end fw-bold text-danger">${formatInr(spent)}</td>
                                        <td class="text-end fw-bold ${profitClass}">${formatInr(netProfit)}</td>
                                        <td class="text-center fw-semibold ${profitClass}">${marginPct}%</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <div class="progress flex-grow-1" style="height: 6px;">
                                                    <div class="progress-bar ${budgetPct > 85 ? 'bg-danger' : 'bg-primary'}" style="width: ${budgetPct}%;"></div>
</div>
                                                <span class="small text-muted">${budgetPct}%</span>
</div>
                                        </td>
                                        <td class="text-center">${statusBadge}</td>
                                    </tr>
                                `);
                    });
                }

                // Update Table Footer
                $('#footEstimate').text(formatInr(totEstimate));
                $('#footRevenue').text(formatInr(totRevenue));
                $('#footLabour').text(formatInr(totLabour));
                $('#footMaterial').text(formatInr(totMaterial));
                $('#footSupplier').text(formatInr(totSupplier));
                $('#footSundries').text(formatInr(totSundries));
                $('#footTotalExpense').text(formatInr(totSpent));
                $('#footNetProfit').text(formatInr(totNetProfit));
                const overallMargin = totRevenue > 0 ? ((totNetProfit / totRevenue) * 100).toFixed(1) : '0.0';
                $('#footMargin').text(overallMargin + '%');

                // Update KPI Cards & Print Grid
                const kpiEstimate = kpis.contract_portfolio !== undefined ? kpis.contract_portfolio : totEstimate;
                const kpiRev = kpis.total_revenue !== undefined ? kpis.total_revenue : totRevenue;
                const kpiExp = kpis.total_expense !== undefined ? kpis.total_expense : totSpent;
                const kpiNet = kpis.net_profit !== undefined ? kpis.net_profit : totNetProfit;
                const kpiMargin = kpis.net_margin !== undefined ? kpis.net_margin : overallMargin;

                $('#kpiContractValue, #printKpiContractValue').text(formatInr(kpiEstimate));
                $('#kpiRevenue, #printKpiRevenue').text(formatInr(kpiRev));
                $('#kpiExpense, #printKpiExpense').text(formatInr(kpiExp));
                $('#kpiNetProfit, #printKpiNetProfit').text(formatInr(kpiNet));
                $('#kpiMarginBadge').text(`${kpiMargin}% Net Margin`);

                $('#sitesCountBadge').text(`${sites.length} Sites`);
                $('#overallProfitBadge').text(`Overall Profit: ${formatInr(kpiNet)}`);

                // Update Income & Expense Statement
                $('#stmtClientAdvance').text(formatInr(stmt.client_advances !== undefined ? stmt.client_advances : totRevenue));
                $('#stmtOtherInflows').text(formatInr(stmt.other_inflows || 0));
                $('#stmtTotalRevenue').text(formatInr(stmt.gross_revenue !== undefined ? stmt.gross_revenue : kpiRev));
                $('#stmtWages').text(formatInr(stmt.worker_wages !== undefined ? stmt.worker_wages : totLabour));
                $('#stmtMaterials').text(formatInr(stmt.materials_consumables !== undefined ? stmt.materials_consumables : totMaterial));
                $('#stmtTools').text(formatInr(stmt.tools_rentals || 0));
                $('#stmtSuppliers').text(formatInr(stmt.supplier_invoices !== undefined ? stmt.supplier_invoices : totSupplier));
                $('#stmtSundries').text(formatInr(stmt.site_sundries !== undefined ? stmt.site_sundries : totSundries));
                $('#stmtTotalExpenses').text(formatInr(stmt.total_expenses !== undefined ? stmt.total_expenses : kpiExp));
                $('#stmtNetProfit').text(formatInr(stmt.net_profit !== undefined ? stmt.net_profit : kpiNet));

                // Update Expense Distribution
                const labAmt = dist.labour ? dist.labour.amount : totLabour;
                const labPct = dist.labour ? dist.labour.pct : (kpiExp > 0 ? Math.round((totLabour / kpiExp) * 100) : 0);
                $('#distLabourText').text(`${formatInr(labAmt)} (${labPct}%)`);
                $('#distLabourBar').css('width', `${labPct}%`);

                const matAmt = dist.material ? dist.material.amount : totMaterial;
                const matPct = dist.material ? dist.material.pct : (kpiExp > 0 ? Math.round((totMaterial / kpiExp) * 100) : 0);
                $('#distMaterialText').text(`${formatInr(matAmt)} (${matPct}%)`);
                $('#distMaterialBar').css('width', `${matPct}%`);

                const supAmt = dist.supplier ? dist.supplier.amount : totSupplier;
                const supPct = dist.supplier ? dist.supplier.pct : (kpiExp > 0 ? Math.round((totSupplier / kpiExp) * 100) : 0);
                $('#distSupplierText').text(`${formatInr(supAmt)} (${supPct}%)`);
                $('#distSupplierBar').css('width', `${supPct}%`);

                const sunAmt = dist.sundry ? dist.sundry.amount : totSundries;
                const sunPct = dist.sundry ? dist.sundry.pct : (kpiExp > 0 ? Math.round((totSundries / kpiExp) * 100) : 0);
                $('#distSundryText').text(`${formatInr(sunAmt)} (${sunPct}%)`);
                $('#distSundryBar').css('width', `${sunPct}%`);

                // Update Financial Health Indicator
                if (health.title) {
                    $('#healthTitle').text(health.title);
                    $('#healthDesc').text(health.desc);
                    $('#healthIcon').attr('class', `bi ${health.icon} fs-5`);
                }

                // Active Filter Badges
                const pText = $('#filterPeriod option:selected').text();
                const sText = $('#filterSite option:selected').text();
                $('#filterMatchCountBadge').html(`Active filters: <strong>${escapeHtml(pText)}</strong> &bull; <strong>${escapeHtml(sText)}</strong>`);
                $('#printActivePeriod').html(`Period: <strong>${escapeHtml(pText)}</strong>`);
                $('#printActiveSite').html(`Site: <strong>${escapeHtml(sText)}</strong>`);

                updatePagination(sites.length);
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function updatePagination(totalCount) {
                if (totalCount === undefined) {
                    totalCount = $('#adminPnlSitesTableBody tr').length;
                }
                const totalPages = Math.ceil(totalCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const rows = $('#adminPnlSitesTableBody tr');
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

                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${totalCount}</strong> sites`;
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

            // Initial render
            renderPnlView({
                sites: initialReportSites,
                kpis: initialKpis,
                statement: initialStatement,
                distribution: initialDistribution,
                health: initialHealth
            });

            // Fetch live report via AJAX or filter client dataset
            function refreshReport() {
                currentPage = 1;
                const period = $('#filterPeriod').val();
                const siteId = $('#filterSite').val();
                const search = ($('#filterSearchInput').val() || '').trim();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                $.ajax({
                    url: ajaxReportUrl,
                    type: 'GET',
                    data: {
                        period: period,
                        site_id: siteId,
                        search: search,
                        start_date: (period === 'custom' ? startDate : ''),
                        end_date: (period === 'custom' ? endDate : '')
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res && res.success && res.data) {
                            renderPnlView(res.data);
                        }
                    },
                    error: function () {
                        // Client-side fallback filter on rawAllSites
                        filterClientSide();
                    }
                });
            }

            function filterClientSide() {
                const selectedSiteId = $('#filterSite').val();
                const searchQuery = ($('#filterSearchInput').val() || '').toLowerCase().trim();

                const filtered = rawAllSites.filter(function (site) {
                    if (selectedSiteId !== 'all' && String(site.id) !== String(selectedSiteId)) return false;
                    if (searchQuery) {
                        const hay = `${site.name} ${site.client || ''} ${site.location || ''} ${site.work_type || ''}`.toLowerCase();
                        if (!hay.includes(searchQuery)) return false;
                    }
                    return true;
                });

                renderPnlView({
                    sites: filtered,
                    kpis: initialKpis,
                    statement: initialStatement,
                    distribution: initialDistribution,
                    health: initialHealth
                });
            }

            // Event Listeners
            $('#filterPeriod').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateStartCol, #customDateEndCol').slideDown(150);
                } else {
                    $('#customDateStartCol, #customDateEndCol').slideUp(150);
                }
                refreshReport();
            });

            $('#filterStartDate, #filterEndDate, #filterSite').on('change', function () {
                refreshReport();
            });

            let searchDebounceTimer;
            $('#filterSearchInput').on('keyup input', function () {
                clearTimeout(searchDebounceTimer);
                searchDebounceTimer = setTimeout(refreshReport, 250);
            });

            $('#clearSearchBtn').on('click', function () {
                $('#filterSearchInput').val('');
                refreshReport();
            });

            $('#resetFilterBtn').on('click', function () {
                $('#filterPeriod').val('this_week');
                $('#filterSite').val('all');
                $('#filterSearchInput').val('');
                $('#customDateStartCol, #customDateEndCol').hide();
                refreshReport();
            });

            // Print & PDF Export
            $('#printReportBtn').on('click', function () {
                window.print();
            });

            $('#exportPdfBtn').on('click', function () {
                window.print();
            });

            // Server-Side Excel Export
            $('#exportExcelBtn').on('click', function () {
                const period = $('#filterPeriod').val();
                const siteId = $('#filterSite').val();
                const search = $('#filterSearchInput').val();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                const params = new URLSearchParams({
                    period: period,
                    site_id: siteId,
                    search: search || '',
                    start_date: (period === 'custom' ? startDate : ''),
                    end_date: (period === 'custom' ? endDate : '')
                });

                window.location.href = `${exportExcelBaseUrl}?${params.toString()}`;
            });
        });
    </script>
@endpush