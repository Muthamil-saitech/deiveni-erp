@extends('layouts.app')

@section('title', 'Site Cash Handling Report')
@section('page_title', 'Site Cash Handling Report (தள பணப் புழக்கம் & செலவுகள் அறிக்கை)')

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
                font-size: 8.5pt !important;
                line-height: 1.25 !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Hide interactive elements, sidebars and headers */
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
            #siteCashHandlingApp {
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

            .formula-print-box {
                background: #f8fafc !important;
                border: 1px solid #94a3b8 !important;
                padding: 6px 10px !important;
                margin-bottom: 10px !important;
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

            /* Avoid blank trailing pages */
            * {
                box-sizing: border-box !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="siteCashHandlingApp">

        <!-- Dedicated Print & PDF Header (Visible strictly in Print / PDF export) -->
        <div class="print-report-header d-none">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1 text-uppercase text-dark">DEIVENI CONSTRUCTIONS &amp; DEVELOPERS</h4>
                    <h5 class="fw-bold mb-0 text-secondary">Site Cash Handling Report (தள பணப் புழக்கம் &amp; செலவுகள்
                        அறிக்கை)</h5>
                </div>
                <div class="text-end small">
                    <div>Generated: <strong>{{ date('d M Y, h:i A') }}</strong></div>
                    <div id="printActivePeriod">Period: <strong>{{ $filterMeta['this_week']['label'] }}</strong></div>
                    <div id="printActiveSite">Site: <strong>All Sites</strong></div>
                </div>
            </div>
        </div>

        <!-- Top Header & Action Toolbar -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0" id="headerActionCard">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-cash-stack text-success me-2"></i>Site Cash Handling Report
                            (தள பணப் புழக்கம் &amp; செலவுகள் அறிக்கை)
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

        <!-- Live Cash Formula Banner (Synchronized with Site Cash Handling Module) -->
        <div class="card-custom p-3 p-md-3 mb-4 bg-white border-start border-4 border-success shadow-sm formula-print-box">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold mb-1">
                        <i class="bi bi-calculator me-1"></i> Site Cash Formula (தள ரொக்க சூத்திரம்)
                    </span>
                    <h6 class="fw-bold text-dark mb-1">Cash-in-Hand = Advance + Collections &minus; Purchases &minus; Wages
                        &minus; Returned</h6>
                    <p class="text-muted small mb-0 font-monospace" id="reportFormulaDisplay">
                        ₹0 (Adv) + ₹0 (Col) &minus; ₹0 (Pur) &minus; ₹0 (Wage) &minus; ₹0 (Ret) = <strong
                            class="text-success">₹0</strong>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <div class="badge bg-light text-dark border p-2" id="chipAdvance">
                        <i class="bi bi-plus-circle text-primary me-1"></i>Adv: <strong>₹0</strong>
                    </div>
                    <div class="badge bg-light text-dark border p-2" id="chipCollections">
                        <i class="bi bi-cash-stack text-success me-1"></i>Col: <strong>₹0</strong>
                    </div>
                    <div class="badge bg-light text-dark border p-2" id="chipPurchases">
                        <i class="bi bi-cart-dash text-danger me-1"></i>Pur: <strong>₹0</strong>
                    </div>
                    <div class="badge bg-light text-dark border p-2" id="chipWages">
                        <i class="bi bi-people text-danger me-1"></i>Wage: <strong>₹0</strong>
                    </div>
                    <div class="badge bg-light text-dark border p-2" id="chipReturned">
                        <i class="bi bi-arrow-left-right text-warning me-1"></i>Ret: <strong>₹0</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="card-custom p-3 mb-4 bg-white shadow-sm border-0" id="filterCard">
            <div class="row g-2 align-items-end">
                <!-- Date Period Filter (Task 4: Order: today, this_week, last_week (date range), this_month, custom) -->
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
                            Last Week ({{ $filterMeta['last_week']['label'] }})
                        </option>
                        <option value="this_month" {{ ($activeFilters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>
                            This Month (நடப்பு மாதம் - {{ $filterMeta['this_month']['label'] }})
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
                            <option value="{{ $site->id }}" {{ (string) ($activeFilters['site_id'] ?? '') === (string) $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cash Flow Type Filter -->
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-funnel me-1 text-primary"></i> Flow Type (வகை)
                    </label>
                    <select class="form-select form-select-sm" id="filterFlowType" name="flow_type">
                        <option value="all">All Flow Types (அனைத்தும்)</option>
                        <option value="Advance Received">Advance Received (+)</option>
                        <option value="Client Collection">Client Collection (+)</option>
                        <option value="Material Purchase">Material Purchase (-)</option>
                        <option value="Supplier Payment">Supplier Payment (-)</option>
                        <option value="Daily Wage Payment">Daily Wage Payment (-)</option>
                        <option value="Balance Returned">Balance Returned (-)</option>
                        <option value="Tool Rental">Tool Rental (-)</option>
                        <option value="Other Expense">Other Expense (-)</option>
                    </select>
                </div>

                <!-- Text Search -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-search me-1 text-primary"></i> Search Particulars / Payee / Bill No
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search payee, bill no, material, notes...">
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
                    Active filters: <strong>This Week</strong> &bull; <strong>All Sites</strong>
                </span>
                <span class="small text-muted">
                    <i class="bi bi-database-check text-success me-1"></i>Data Source: Site Cash Handling Register
                </span>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4 kpi-print-grid">
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Cash Inflow (வரவு)</div>
                            <h4 class="fw-bold mb-0 text-success mt-1" id="kpiCashIn">₹0</h4>
                            <small class="text-muted" id="kpiCashInSub">Advance + Collections</small>
                        </div>
                        <div class="bg-success-subtle text-success p-2 rounded no-print">
                            <i class="bi bi-arrow-down-left-circle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-danger h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Cash Outflow (பற்று)</div>
                            <h4 class="fw-bold mb-0 text-danger mt-1" id="kpiCashOut">₹0</h4>
                            <small class="text-muted" id="kpiCashOutSub">Purchases, Wages &amp; Returned</small>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2 rounded no-print">
                            <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-primary h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Net Site Cash Balance</div>
                            <h4 class="fw-bold mb-0 text-primary mt-1" id="kpiNetCash">₹0</h4>
                            <small class="text-muted">Current cash-in-hand at site</small>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2 rounded no-print">
                            <i class="bi bi-wallet-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-secondary h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Cash Entries</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiTxnCount">0</h4>
                            <small class="text-muted">Filtered transactions</small>
                        </div>
                        <div class="bg-light text-secondary p-2 rounded border no-print">
                            <i class="bi bi-receipt fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site Cash Handling Table Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-journal-check text-success me-1"></i>
                        Site Cash Register (தள பணப் புழக்க விவரங்கள்)
                    </h6>
                    <small class="text-muted">Chronological site cash transactions with supplier details, bills, and cash
                        in/out breakdown</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-success-subtle text-success border border-success-subtle"
                        id="siteCashNetBadge">Net Cash: ₹0</span>
                    <span class="badge bg-light text-dark border" id="siteCashCountBadge">0 Entries</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smSiteCashTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 120px;">Date &amp; Time</th>
                            <th style="min-width: 150px;">Project Site</th>
                            <th style="min-width: 140px;">Cash Flow Type</th>
                            <th style="min-width: 220px;">Particulars / Description</th>
                            <th style="min-width: 140px;">Payee / Supplier</th>
                            <th style="min-width: 110px;">Bill / Ref No</th>
                            <th class="text-end" style="min-width: 110px;">Cash In (+ரூ.)</th>
                            <th class="text-end" style="min-width: 110px;">Cash Out (-ரூ.)</th>
                            <th class="text-center no-print" style="min-width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="smSiteCashTableBody" class="small">
                        <!-- Populated dynamically via JS from site_cashes database -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="6">Site Cash Total Balance:</td>
                            <td class="text-end text-success fs-6" id="footCashIn">₹0</td>
                            <td class="text-end text-danger fs-6" id="footCashOut">₹0</td>
                            <td class="text-center text-primary fs-6" id="footNetCash">₹0</td>
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
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, count($transactions ?? [])) }}</strong> of <strong class="text-dark" id="pageTotal">{{ count($transactions ?? []) }}</strong> entries
                    </span>
                </div>
                <nav aria-label="Site cash report pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Dynamic dataset loaded directly from site_cashes table via SiteCashHandlingReportController & Service
            const rawTransactions = @json($transactions ?? []);
            const filterMeta = @json($filterMeta ?? []);
            const baseUrl = "{{ url('/site-cash') }}";
            const exportExcelUrl = "{{ route('reports.site.cash-handling.export-excel') }}";

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
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

            let filteredTransactionsList = [];

            function renderSiteCashReport() {
                const selectedSite = $('#filterSite').val();
                const selectedFlowType = $('#filterFlowType').val();
                const searchQuery = ($('#filterSearchInput').val() || '').toLowerCase().trim();

                let adv = 0, col = 0, pur = 0, wage = 0, ret = 0;
                let totalIn = 0;
                let totalOut = 0;
                let matchCount = 0;

                filteredTransactionsList = [];
                const tbody = $('#smSiteCashTableBody');
                tbody.empty();

                rawTransactions.forEach(function (tx) {
                    // Site filter
                    if (selectedSite !== 'all' && String(tx.project_site_id) !== String(selectedSite)) return;

                    // Flow Type filter
                    if (selectedFlowType !== 'all') {
                        if (tx.type !== selectedFlowType && tx.flow_type !== selectedFlowType.toLowerCase()) return;
                    }

                    // Date filter
                    if (!checkDateInRange(tx.raw_date)) return;

                    const payeeOrSupplier = (tx.supplier || tx.person || '');
                    const billNo = (tx.bill && tx.bill !== 'N/A' ? tx.bill : '');

                    // Search filter
                    if (searchQuery) {
                        let itemsText = '';
                        if (Array.isArray(tx.items)) {
                            itemsText = tx.items.map(i => `${i.materialName || i.material || ''} ${i.qty || ''}`).join(' ');
                        }
                        const haystack = `${tx.date} ${tx.time || ''} ${tx.type || ''} ${tx.site || ''} ${tx.desc || ''} ${payeeOrSupplier} ${billNo} ${itemsText}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    matchCount++;
                    filteredTransactionsList.push(tx);

                    const type = tx.type;
                    const inVal = parseFloat(tx.inVal) || 0;
                    const outVal = parseFloat(tx.outVal) || 0;

                    if (['Advance Received', 'Owner Advance', 'Client Advance'].includes(type)) {
                        adv += inVal;
                    } else if (type === 'Client Collection') {
                        col += inVal;
                    } else if (['Material Purchase', 'Supplier Payment', 'Tool Rental', 'Other Expense'].includes(type)) {
                        pur += outVal;
                    } else if (type === 'Daily Wage Payment') {
                        wage += outVal;
                    } else if (['Balance Returned', 'Balance Returned to AM', 'Balance Returned to Account Manager'].includes(type)) {
                        ret += outVal;
                    } else {
                        if (tx.flow_type === 'inflow') {
                            adv += inVal;
                        } else {
                            pur += outVal;
                        }
                    }

                    totalIn += inVal;
                    totalOut += outVal;

                    // Badge styling matching main Site Cash handling page
                    let badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                    if (type === 'Client Collection') {
                        badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                    } else if (['Material Purchase', 'Supplier Payment', 'Daily Wage Payment', 'Tool Rental', 'Other Expense'].includes(type)) {
                        badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                    } else if (['Balance Returned', 'Balance Returned to AM'].includes(type)) {
                        badgeClass = 'bg-warning-subtle text-dark border border-warning-subtle';
                    }

                    // Material items badge breakdown if present
                    let itemsBadgeHtml = '';
                    if (Array.isArray(tx.items) && tx.items.length > 0) {
                        itemsBadgeHtml = `<div class="mt-1 d-flex flex-wrap gap-1">` +
                            tx.items.map(it => `<span class="badge bg-light text-dark border py-0 px-1" style="font-size:0.72rem;"><i class="bi bi-box-seam text-secondary me-1"></i>${it.qty || 1} ${it.unit || 'Unit'} ${escapeHtml(it.materialName || it.material || '')}</span>`).join('') +
                            `</div>`;
                    }

                    const supplierCellHtml = payeeOrSupplier
                        ? `<div><span class="fw-semibold text-primary"><i class="bi bi-truck-front-fill me-1"></i>${escapeHtml(payeeOrSupplier)}</span></div>`
                        : `<span class="text-muted">-</span>`;

                    const imageBadge = tx.image
                        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" title="Receipt image attached"><i class="bi bi-image"></i></span>`
                        : '';

                    let modeBadge = '';
                    if (tx.payment_mode === 'Cheque') {
                        modeBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-1 py-0 me-1" style="font-size:0.72rem;"><i class="bi bi-card-text me-1"></i>Cheque</span>`;
                    } else if (tx.payment_mode === 'UPI') {
                        modeBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-1 py-0 me-1" style="font-size:0.72rem;"><i class="bi bi-qr-code me-1"></i>UPI</span>`;
                    } else {
                        modeBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-1 py-0 me-1" style="font-size:0.72rem;"><i class="bi bi-cash-stack me-1"></i>Cash</span>`;
                    }
                    const refHtml = tx.payment_reference ? `<div class="small font-monospace text-dark fw-semibold mt-1">#${escapeHtml(tx.payment_reference)}</div>` : '';
                    const bankHtml = tx.bank_name ? `<div class="small text-muted"><i class="bi bi-bank me-1"></i>${escapeHtml(tx.bank_name)}</div>` : '';
                    const upiIdHtml = tx.upi_id ? `<div class="small font-monospace text-muted"><i class="bi bi-phone me-1"></i>${escapeHtml(tx.upi_id)}</div>` : '';
                    const acHolderHtml = tx.account_holder_name ? `<div class="small text-dark fw-semibold"><i class="bi bi-person-check me-1 text-success"></i>A/C: ${escapeHtml(tx.account_holder_name)}</div>` : '';
                    const receiverHtml = tx.receiver_name ? `<div class="small text-primary"><i class="bi bi-person me-1"></i>To: ${escapeHtml(tx.receiver_name)}</div>` : '';
                    const paidToHtml = tx.paid_to ? `<div class="badge bg-light text-secondary border small mt-1 d-block text-truncate" style="max-width: 130px;"><i class="bi bi-person me-1"></i>${escapeHtml(tx.paid_to)}</div>` : '';

                    const billCellHtml = `<div>${modeBadge}${imageBadge}</div>${refHtml}${bankHtml}${upiIdHtml}${acHolderHtml}${receiverHtml}${billNo ? `<div class="font-monospace text-muted small mt-1">Bill: ${escapeHtml(billNo)}</div>` : ''}`;

                    const viewActionHtml = tx.id
                        ? `<a href="${baseUrl}/${tx.id}" class="btn btn-outline-primary btn-sm px-2 py-1" title="View Details in Site Cash (தள விவரங்கள்)">
                                           <i class="bi bi-eye me-1"></i>View
                                       </a>`
                        : `<span class="text-muted small">-</span>`;

                    tbody.append(`
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">${escapeHtml(tx.date)}</div>
                                            <div class="small text-muted">${escapeHtml(tx.time || '')}</div>
                                        </td>
                                        <td class="fw-semibold text-dark">${escapeHtml(tx.site || '-')}</td>
                                        <td>
                                            <span class="badge ${badgeClass}">${escapeHtml(tx.type)}</span>
                                            ${paidToHtml}
                                        </td>
                                        <td>
                                            <div class="text-dark small">${escapeHtml(tx.desc || '-')}</div>
                                            ${itemsBadgeHtml}
                                        </td>
                                        <td>${supplierCellHtml}</td>
                                        <td>${billCellHtml}</td>
                                        <td class="text-end fw-bold text-success">${inVal > 0 ? '+₹' + inVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}</td>
                                        <td class="text-end fw-bold text-danger">${outVal > 0 ? '-₹' + outVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '-'}</td>
                                        <td class="text-center no-print">${viewActionHtml}</td>
                                    </tr>
                                `);
                });

                if (matchCount === 0) {
                    tbody.html('<tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-wallet2 fs-2 d-block mb-2 text-secondary"></i><div class="fw-semibold">No Site Cash Transactions Found</div><small>Try selecting "All Dates" or adjusting your search query.</small></td></tr>');
                }

                const netCash = totalIn - totalOut;

                // Update Live Formula Banner & Breakdown Chips
                $('#reportFormulaDisplay').html(`
                                ₹${adv.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (Adv) + ₹${col.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (Col) &minus; ₹${pur.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (Pur) &minus; ₹${wage.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (Wage) &minus; ₹${ret.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} (Ret) = <strong class="text-success">₹${netCash.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                            `);
                $('#chipAdvance').html(`<i class="bi bi-plus-circle text-primary me-1"></i>Adv: <strong>₹${adv.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                $('#chipCollections').html(`<i class="bi bi-cash-stack text-success me-1"></i>Col: <strong>₹${col.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                $('#chipPurchases').html(`<i class="bi bi-cart-dash text-danger me-1"></i>Pur: <strong>₹${pur.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                $('#chipWages').html(`<i class="bi bi-people text-danger me-1"></i>Wage: <strong>₹${wage.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);
                $('#chipReturned').html(`<i class="bi bi-arrow-left-right text-warning me-1"></i>Ret: <strong>₹${ret.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);

                // Update KPIs
                $('#kpiCashIn').text('₹' + totalIn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiCashInSub').text(`Adv: ₹${adv.toLocaleString('en-IN')} | Col: ₹${col.toLocaleString('en-IN')}`);
                $('#kpiCashOut').text('₹' + totalOut.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiCashOutSub').text(`Pur: ₹${pur.toLocaleString('en-IN')} | Wage: ₹${wage.toLocaleString('en-IN')} | Ret: ₹${ret.toLocaleString('en-IN')}`);
                $('#kpiNetCash').text('₹' + netCash.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiTxnCount').text(matchCount);

                // Update Footer
                $('#footCashIn').text('+₹' + totalIn.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#footCashOut').text('-₹' + totalOut.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#footNetCash').text('₹' + netCash.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                $('#siteCashNetBadge').text(`Net Cash: ₹${netCash.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
                $('#siteCashCountBadge').text(`${matchCount} Entries`);

                const pText = $('#filterPeriod option:selected').text();
                const sText = $('#filterSite option:selected').text();
                const fText = $('#filterFlowType option:selected').text();
                $('#filterMatchCountBadge').html(`Active filters: <strong>${pText}</strong> &bull; <strong>${sText}</strong> &bull; <strong>${fText}</strong>`);

                $('#printActivePeriod').html(`Period: <strong>${pText}</strong>`);
                $('#printActiveSite').html(`Site: <strong>${sText}</strong>`);

                updatePagination();
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function updatePagination() {
                const totalCount = filteredTransactionsList.length;
                const totalPages = Math.ceil(totalCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const rows = $('#smSiteCashTableBody tr');
                rows.hide();

                if (totalCount === 0) {
                    $('#tablePaginationContainer').addClass('d-none');
                    $(rows).show();
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

            renderSiteCashReport();

            // Filter Event Listeners
            $('#filterPeriod').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateStartCol, #customDateEndCol').slideDown(150);
                } else {
                    $('#customDateStartCol, #customDateEndCol').slideUp(150);
                }
                currentPage = 1;
                renderSiteCashReport();
            });

            $('#filterStartDate, #filterEndDate, #filterSite, #filterFlowType').on('change input', function () {
                currentPage = 1;
                renderSiteCashReport();
            });

            $('#filterSearchInput').on('keyup input', function () {
                currentPage = 1;
                renderSiteCashReport();
            });

            $('#clearSearchBtn').on('click', function () {
                $('#filterSearchInput').val('');
                currentPage = 1;
                renderSiteCashReport();
            });

            $('#resetFilterBtn').on('click', function () {
                $('#filterPeriod').val('this_week');
                $('#filterSite').val('all');
                $('#filterFlowType').val('all');
                $('#filterSearchInput').val('');
                $('#customDateStartCol, #customDateEndCol').hide();
                currentPage = 1;
                renderSiteCashReport();
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
                const flowType = $('#filterFlowType').val();
                const search = $('#filterSearchInput').val();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                const params = new URLSearchParams({
                    period: period,
                    site_id: siteId,
                    flow_type: flowType,
                    search: search || '',
                    start_date: (period === 'custom' ? startDate : ''),
                    end_date: (period === 'custom' ? endDate : ''),
                });

                window.location.href = `${exportExcelUrl}?${params.toString()}`;
            });
        });
    </script>
@endpush