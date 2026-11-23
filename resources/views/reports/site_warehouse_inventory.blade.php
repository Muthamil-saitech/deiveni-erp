@extends('layouts.app')

@section('title', 'Central Warehouse & Store Inventory Report')
@section('page_title', 'Central Warehouse Report (மைய கிடங்கு அறிக்கை)')

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

            /* Hide interactive navbar, sidebar, filters and action buttons */
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
            #siteWarehouseApp {
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
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="siteWarehouseApp">

        <!-- Dedicated Print & PDF Header (Visible strictly in Print / PDF export) -->
        <div class="print-report-header d-none">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-0 text-dark">DEIVENI CONSTRUCTIONS & DEVELOPERS</h3>
                    <div class="fs-6 fw-semibold text-secondary">
                        Central Warehouse Report (மைய கிடங்கு அறிக்கை)
                    </div>
                </div>
                <div class="text-end small text-muted">
                    <div>Generated: <strong>{{ date('d M Y, h:i A') }}</strong></div>
                    <div id="printActivePeriod">Period:
                        <strong>{{ $filterMeta['this_week']['label'] ?? 'This Week' }}</strong>
                    </div>
                    <div id="printActiveCategory">Category: <strong>All Categories</strong></div>
                </div>
            </div>
        </div>

        <!-- Top Header & Action Toolbar -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0" id="headerActionCard">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h4 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-boxes text-secondary me-2"></i>Central Warehouse & Store Inventory Report
                            (மைய கிடங்கு அறிக்கை)
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

        <!-- Filter Toolbar -->
        <div class="card-custom p-3 mb-4 bg-white shadow-sm border-0" id="filterCard">
            <div class="row g-2 align-items-end">
                <!-- Date Period Filter (Strict Order: today, this_week, last_week with computed date range, this_month, custom) -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted" for="filterPeriod">
                        <i class="bi bi-calendar3 me-1 text-primary"></i> Report Period (கால இடைவெளி)
                    </label>
                    <select class="form-select form-select-sm" id="filterPeriod" name="period">
                        <option value="today" {{ ($activeFilters['period'] ?? '') === 'today' ? 'selected' : '' }}>
                            Today (இன்று - {{ $filterMeta['today']['label'] }})
                        </option>
                        <option value="this_week" {{ ($activeFilters['period'] ?? 'this_week') === 'this_week' ? 'selected' : '' }}>
                            This Week (இந்த வாரம் - {{ $filterMeta['this_week']['label'] }})
                        </option>
                        <option value="last_week" {{ ($activeFilters['period'] ?? '') === 'last_week' ? 'selected' : '' }}>
                            Last Week (கடந்த வாரம் - {{ $filterMeta['last_week']['label'] }})
                        </option>
                        <option value="this_month" {{ ($activeFilters['period'] ?? '') === 'this_month' ? 'selected' : '' }}>
                            This Month (நடப்பு மாதம் - {{ $filterMeta['this_month']['label'] }})
                        </option>
                        <option value="custom" {{ ($activeFilters['period'] ?? '') === 'custom' ? 'selected' : '' }}>
                            Custom Date Range...
                        </option>
                    </select>
                </div>

                <!-- Custom Start Date -->
                <div class="col-12 col-md-2" id="customDateStartCol"
                    style="{{ ($activeFilters['period'] ?? '') === 'custom' ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold mb-1 text-muted" for="filterStartDate">
                        <i class="bi bi-calendar-event me-1"></i> From Date
                    </label>
                    <input type="date" class="form-control form-control-sm" id="filterStartDate"
                        value="{{ $activeFilters['start_date'] ?? '' }}">
                </div>

                <!-- Custom End Date -->
                <div class="col-12 col-md-2" id="customDateEndCol"
                    style="{{ ($activeFilters['period'] ?? '') === 'custom' ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold mb-1 text-muted" for="filterEndDate">
                        <i class="bi bi-calendar-check me-1"></i> To Date
                    </label>
                    <input type="date" class="form-control form-control-sm" id="filterEndDate"
                        value="{{ $activeFilters['end_date'] ?? '' }}">
                </div>

                <!-- Item Category Filter -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted" for="filterCategory">
                        <i class="bi bi-tags me-1 text-primary"></i> Item Category (பிரிவு)
                    </label>
                    <select class="form-select form-select-sm" id="filterCategory" name="category">
                        <option value="all" selected>All Categories (அனைத்து பிரிவுகள்)</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ ($activeFilters['category'] ?? '') === $cat ? 'selected' : '' }}>
                                {{ $cat }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Stock Level Status Filter -->
                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold mb-1 text-muted" for="filterStockStatus">
                        <i class="bi bi-shield-exclamation me-1 text-primary"></i> Stock Status
                    </label>
                    <select class="form-select form-select-sm" id="filterStockStatus" name="stock_status">
                        <option value="all" {{ ($activeFilters['stock_status'] ?? '') === 'all' ? 'selected' : '' }}>All Items
                            (அனைத்தும்)</option>
                        <option value="low" {{ ($activeFilters['stock_status'] ?? '') === 'low' ? 'selected' : '' }}>Low Stock
                            Alert (குறைந்த இருப்பு)</option>
                        <option value="normal" {{ ($activeFilters['stock_status'] ?? '') === 'normal' ? 'selected' : '' }}>
                            Normal Stock (போதுமான இருப்பு)</option>
                    </select>
                </div>

                <!-- Text Search -->
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold mb-1 text-muted" for="filterSearchInput">
                        <i class="bi bi-search me-1 text-primary"></i> Search Item Name / SKU
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search cement, trowel, WH-MAT-01..." value="{{ $activeFilters['search'] ?? '' }}">
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
                    Active filters:
                    <strong>{{ $filterMeta[$activeFilters['period'] ?? 'this_week']['label'] ?? 'This Week' }}</strong>
                    &bull; <strong>All Categories</strong> &bull; <strong>All Items</strong>
                </span>
                <span class="small text-muted">Warehouse Central Store Inventory Register</span>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4 kpi-print-grid">
            <div class="col-6 col-lg-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Stock Valuation</div>
                            <h4 class="fw-bold mb-0 text-success mt-1" id="kpiValuation">
                                ₹{{ number_format($kpis['total_valuation'], 2) }}</h4>
                            <small class="text-muted">Total inventory worth</small>
                        </div>
                        <div class="bg-success-subtle text-success p-2 rounded no-print">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card-custom p-3 bg-white border-start border-4 border-info h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Active SKU Items</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiSkuCount">{{ $kpis['sku_count'] }}</h4>
                            <small class="text-muted">Catalog items</small>
                        </div>
                        <div class="bg-info-subtle text-info p-2 rounded no-print">
                            <i class="bi bi-boxes fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card-custom p-3 bg-white border-start border-4 border-danger h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Low Stock Items</div>
                            <h4 class="fw-bold mb-0 text-danger mt-1" id="kpiLowStock">{{ $kpis['low_stock_count'] }}</h4>
                            <small class="text-muted">Below threshold</small>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2 rounded no-print">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <div class="card-custom p-3 bg-white border-start border-4 border-primary h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Units in Warehouse</div>
                            <h4 class="fw-bold mb-0 text-primary mt-1" id="kpiTotalUnits">
                                {{ number_format($kpis['total_units'], 2) }}
                            </h4>
                            <small class="text-muted">Cumulative count</small>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2 rounded no-print">
                            <i class="bi bi-check-all fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-custom p-3 bg-white border-start border-4 border-warning h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Period Movements</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiMovementsCount">{{ $kpis['movements_count'] }}
                            </h4>
                            <small class="text-muted">Dispatched & Inward logs</small>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2 rounded no-print">
                            <i class="bi bi-arrow-left-right fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. Inventory Register Table Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-table text-secondary me-1"></i>
                        1. Warehouse Inventory Register (மைய கிடங்கு இருப்பு விவரங்கள்)
                    </h6>
                    <small class="text-muted">Current physical stock availability, safety thresholds, reorder status, and
                        valuation</small>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" id="lowStockBadge">
                        {{ $kpis['low_stock_count'] }} Low Stock
                    </span>
                    <span class="badge bg-light text-dark border" id="valuationBadge">
                        Valuation: ₹{{ number_format($kpis['total_valuation'], 2) }}
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smWarehouseTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 90px;">SKU Code</th>
                            <th style="min-width: 180px;">Item Name (English & தமிழ்)</th>
                            <th style="min-width: 130px;">Category</th>
                            <th class="text-center" style="min-width: 90px;">Stock</th>
                            <th style="min-width: 80px;">Unit</th>
                            <th class="text-center" style="min-width: 100px;">Threshold</th>
                            <th class="text-center" style="min-width: 120px;">Stock Status</th>
                            <th style="min-width: 180px;">Issued to Site</th>
                            <th class="text-end" style="min-width: 110px;">Valuation (ரூ.)</th>
                        </tr>
                    </thead>
                    <tbody id="smWarehouseTableBody" class="small">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="8">Total Warehouse Stock Valuation:</td>
                            <td class="text-end text-success fs-6" id="footValuation">
                                ₹{{ number_format($kpis['total_valuation'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Table Pagination & Summary Container -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-3 border-top mt-2" id="whPaginationContainer">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0" for="whPerPageSelect">Show</label>
                    <select class="form-select form-select-sm" id="whPerPageSelect" style="width: auto;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-muted small">entries per page</span>
                </div>
                <div class="text-muted small" id="whPaginationSummary">
                    Showing 0 to 0 of 0 entries
                </div>
                <nav aria-label="Warehouse Inventory Table Pagination">
                    <ul class="pagination pagination-sm mb-0" id="whPaginationNav">
                        <!-- Buttons rendered via JS -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- 2. Stock Movement & Dispatch Register Table Card (Task 6) -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-secondary me-1"></i>
                        2. Stock Movement & Dispatch Register (பொருட்கள் இயக்கம் & தள விநியோக அறிக்கை)
                    </h6>
                    <small class="text-muted">Item issue to construction sites, material inward shipments, and inventory
                        balance adjustments</small>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="movementsBadge">
                        0 Movements
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" id="dispatchedBadge">
                        Dispatched: 0
                    </span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle" id="inwardBadge">
                        Inward: 0
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smMovementsTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 100px;">Date</th>
                            <th style="min-width: 110px;">Ref / Voucher</th>
                            <th style="min-width: 180px;">Item SKU & Name</th>
                            <th style="min-width: 120px;">Category</th>
                            <th class="text-center" style="min-width: 120px;">Movement Type</th>
                            <th class="text-center" style="min-width: 100px;">Quantity</th>
                            <th style="min-width: 80px;">Unit</th>
                            <th style="min-width: 180px;">Destination Site / Location</th>
                            <th style="min-width: 120px;">Handled By</th>
                            <th style="min-width: 150px;">Notes / Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="smMovementsTableBody" class="small">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="5">Movement Summary in Selected Period:</td>
                            <td class="text-center text-primary" id="footTotalMovedQty">0.00</td>
                            <td colspan="4" class="text-muted small" id="footMovementSummaryText">Cumulative quantity moved
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Table Pagination & Summary Container -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-3 border-top mt-2" id="movPaginationContainer">
                <div class="d-flex align-items-center gap-2">
                    <label class="text-muted small mb-0" for="movPerPageSelect">Show</label>
                    <select class="form-select form-select-sm" id="movPerPageSelect" style="width: auto;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="text-muted small">entries per page</span>
                </div>
                <div class="text-muted small" id="movPaginationSummary">
                    Showing 0 to 0 of 0 entries
                </div>
                <nav aria-label="Stock Movement Table Pagination">
                    <ul class="pagination pagination-sm mb-0" id="movPaginationNav">
                        <!-- Buttons rendered via JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Dynamic datasets loaded directly from warehouse_items & warehouse_movements tables
            const allItems = @json($items ?? []);
            const allMovements = @json($movements ?? []);
            const filterMeta = @json($filterMeta ?? []);
            const exportExcelUrl = "{{ route('reports.site.warehouse-inventory.export-excel') }}";

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function isDateInPeriod(dateStr, period, customStart, customEnd) {
                if (!dateStr) return false;
                if (period === 'all') return true;

                if (period === 'custom') {
                    if (customStart && dateStr < customStart) return false;
                    if (customEnd && dateStr > customEnd) return false;
                    return true;
                }

                if (filterMeta && filterMeta[period]) {
                    const start = filterMeta[period].start;
                    const end = filterMeta[period].end;
                    return dateStr >= start && dateStr <= end;
                }

                return true;
            }

            function renderWarehouseReport() {
                const selectedPeriod = $('#filterPeriod').val();
                const selectedCat = $('#filterCategory').val();
                const selectedStockStatus = $('#filterStockStatus').val();
                const searchQuery = ($('#filterSearchInput').val() || '').toLowerCase().trim();
                const customStart = $('#filterStartDate').val();
                const customEnd = $('#filterEndDate').val();

                // 1. Filter Items Table
                let totalValuation = 0;
                let lowStockCount = 0;
                let totalUnits = 0;
                let itemMatchCount = 0;

                const itemTbody = $('#smWarehouseTableBody');
                itemTbody.empty();

                allItems.forEach(function (item) {
                    if (selectedCat !== 'all' && item.category !== selectedCat) return;

                    const isLow = Boolean(item.isLow);
                    if (selectedStockStatus === 'low' && !isLow) return;
                    if (selectedStockStatus === 'normal' && isLow) return;

                    if (searchQuery) {
                        const haystack = `${item.sku || ''} ${item.nameEn || ''} ${item.nameTa || ''} ${item.category || ''} ${item.issuedTo || ''}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    itemMatchCount++;
                    const stock = parseFloat(item.stock) || 0;
                    const rate = parseFloat(item.unitRate) || 0;
                    const val = parseFloat(item.valuation) || (stock * rate);
                    const unitName = (item.unit && typeof item.unit === 'object')
                        ? (item.unit.name_en || item.unit.code || '')
                        : (item.unit || item.unit_name || '');

                    totalValuation += val;
                    totalUnits += stock;
                    if (isLow) lowStockCount++;

                    const statusBadge = isLow
                        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-exclamation-triangle me-1"></i>Low Stock</span>'
                        : '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Normal</span>';

                    itemTbody.append(`
                                            <tr>
                                                <td class="font-monospace fw-bold text-dark">${escapeHtml(item.sku)}</td>
                                                <td>
                                                    <div class="fw-semibold text-dark">${escapeHtml(item.nameEn)}</div>
                                                    <div class="text-muted small">${escapeHtml(item.nameTa || '')}</div>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">${escapeHtml(item.category)}</span></td>
                                                <td class="text-center fw-bold ${isLow ? 'text-danger' : 'text-dark'}">${stock.toFixed(2)}</td>
                                                <td class="text-muted small">${escapeHtml(unitName)}</td>
                                                <td class="text-center text-muted">${parseFloat(item.threshold || 0).toFixed(2)}</td>
                                                <td class="text-center">${statusBadge}</td>
                                                <td class="small text-muted">${escapeHtml(item.issuedTo || 'In Warehouse')}</td>
                                                <td class="text-end fw-bold text-success">₹${val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            </tr>
                                        `);
                });

                if (itemMatchCount === 0) {
                    itemTbody.html('<tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-info-circle me-1"></i> No warehouse items match the selected filters.</td></tr>');
                }

                // 2. Filter Movements Table (Task 6 - same filter logic: period, category, search)
                let movementMatchCount = 0;
                let dispatchedQty = 0;
                let inwardQty = 0;
                let totalMovedQty = 0;

                const movementTbody = $('#smMovementsTableBody');
                movementTbody.empty();

                allMovements.forEach(function (m) {
                    // Filter by date period
                    if (!isDateInPeriod(m.date, selectedPeriod, customStart, customEnd)) return;

                    // Filter by category
                    if (selectedCat !== 'all' && m.itemCategory !== selectedCat) return;

                    // Filter by search text
                    if (searchQuery) {
                        const haystack = `${m.refNo || ''} ${m.itemSku || ''} ${m.itemNameEn || ''} ${m.itemCategory || ''} ${m.siteName || ''} ${m.creatorName || ''} ${m.notes || ''}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    movementMatchCount++;
                    const qty = parseFloat(m.quantity) || 0;
                    totalMovedQty += qty;
                    const unitName = (m.unit && typeof m.unit === 'object')
                        ? (m.unit.name_en || m.unit.code || '')
                        : (m.unit || m.unit_name || '');

                    let typeBadge = '<span class="badge bg-secondary">Adjustment</span>';
                    if (m.movementType === 'Issue to Site') {
                        dispatchedQty += qty;
                        typeBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-box-arrow-up-right me-1"></i>Issue to Site</span>';
                    } else if (m.movementType === 'Stock Inward') {
                        inwardQty += qty;
                        typeBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-box-arrow-in-down-left me-1"></i>Stock Inward</span>';
                    }

                    movementTbody.append(`
                                            <tr>
                                                <td class="text-nowrap">${escapeHtml(m.formattedDate || m.date)}</td>
                                                <td class="font-monospace small fw-bold text-dark">${escapeHtml(m.refNo)}</td>
                                                <td>
                                                    <div class="fw-semibold text-dark">${escapeHtml(m.itemNameEn)}</div>
                                                    <div class="text-muted font-monospace small">${escapeHtml(m.itemSku)}</div>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">${escapeHtml(m.itemCategory)}</span></td>
                                                <td class="text-center">${typeBadge}</td>
                                                <td class="text-center fw-bold text-dark">${qty.toFixed(2)}</td>
                                                <td class="text-muted small">${escapeHtml(unitName)}</td>
                                                <td class="small">
                                                    <i class="bi bi-geo-alt text-secondary me-1"></i>${escapeHtml(m.siteName)}
                                                </td>
                                                <td class="small text-muted">${escapeHtml(m.creatorName)}</td>
                                                <td class="small text-muted text-truncate" style="max-width: 200px;" title="${escapeHtml(m.notes)}">
                                                    ${escapeHtml(m.notes || '—')}
                                                </td>
                                            </tr>
                                        `);
                });

                if (movementMatchCount === 0) {
                    movementTbody.html('<tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-info-circle me-1"></i> No stock movements found for the selected period and criteria.</td></tr>');
                }

                // Update KPIs
                $('#kpiValuation').text('₹' + totalValuation.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiSkuCount').text(itemMatchCount);
                $('#kpiLowStock').text(lowStockCount);
                $('#kpiTotalUnits').text(totalUnits.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiMovementsCount').text(movementMatchCount);

                // Update Badges & Footers
                $('#footValuation').text('₹' + totalValuation.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#lowStockBadge').text(`${lowStockCount} Low Stock`);
                $('#valuationBadge').text(`Valuation: ₹${totalValuation.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);

                $('#movementsBadge').text(`${movementMatchCount} Movements`);
                $('#dispatchedBadge').text(`Dispatched: ${dispatchedQty.toFixed(2)}`);
                $('#inwardBadge').text(`Inward: ${inwardQty.toFixed(2)}`);
                $('#footTotalMovedQty').text(totalMovedQty.toFixed(2));
                $('#footMovementSummaryText').text(`Dispatched: ${dispatchedQty.toFixed(2)} | Inward: ${inwardQty.toFixed(2)}`);

                // Update Print Headers & Filter Summary
                const pText = $('#filterPeriod option:selected').text();
                const cText = $('#filterCategory option:selected').text();
                const sText = $('#filterStockStatus option:selected').text();
                $('#filterMatchCountBadge').html(`Active filters: <strong>${escapeHtml(pText)}</strong> &bull; <strong>${escapeHtml(cText)}</strong> &bull; <strong>${escapeHtml(sText)}</strong>`);
                $('#printActivePeriod').html(`Period: <strong>${escapeHtml(pText)}</strong>`);
                $('#printActiveCategory').html(`Category: <strong>${escapeHtml(cText)}</strong>`);

                // Update Pagination
                updateWhPagination();
                updateMovPagination();
            }

            // Pagination state
            let whCurrentPage = 1;
            let whPerPage = 10;
            let movCurrentPage = 1;
            let movPerPage = 10;

            function renderNavButtons(navEl, totalPages, currentPage) {
                let html = '';
                html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">&laquo;</a>
                </li>`;

                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                if (endPage - startPage < maxVisiblePages - 1) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }

                if (startPage > 1) {
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
                }

                html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">&raquo;</a>
                </li>`;

                navEl.html(html);
            }

            function updateWhPagination() {
                const rows = $('#smWarehouseTableBody tr').not(':has(td[colspan])');
                const totalRows = rows.length;
                if (totalRows === 0) {
                    $('#whPaginationContainer').hide();
                    return;
                }
                $('#whPaginationContainer').show();

                const totalPages = Math.ceil(totalRows / whPerPage) || 1;
                if (whCurrentPage > totalPages) whCurrentPage = totalPages;
                if (whCurrentPage < 1) whCurrentPage = 1;

                const startIdx = (whCurrentPage - 1) * whPerPage;
                const endIdx = startIdx + whPerPage;

                rows.each(function (idx) {
                    if (idx >= startIdx && idx < endIdx) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                const displayEnd = Math.min(endIdx, totalRows);
                $('#whPaginationSummary').text(`Showing ${startIdx + 1} to ${displayEnd} of ${totalRows} entries`);
                renderNavButtons($('#whPaginationNav'), totalPages, whCurrentPage);
            }

            function updateMovPagination() {
                const rows = $('#smMovementsTableBody tr').not(':has(td[colspan])');
                const totalRows = rows.length;
                if (totalRows === 0) {
                    $('#movPaginationContainer').hide();
                    return;
                }
                $('#movPaginationContainer').show();

                const totalPages = Math.ceil(totalRows / movPerPage) || 1;
                if (movCurrentPage > totalPages) movCurrentPage = totalPages;
                if (movCurrentPage < 1) movCurrentPage = 1;

                const startIdx = (movCurrentPage - 1) * movPerPage;
                const endIdx = startIdx + movPerPage;

                rows.each(function (idx) {
                    if (idx >= startIdx && idx < endIdx) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });

                const displayEnd = Math.min(endIdx, totalRows);
                $('#movPaginationSummary').text(`Showing ${startIdx + 1} to ${displayEnd} of ${totalRows} entries`);
                renderNavButtons($('#movPaginationNav'), totalPages, movCurrentPage);
            }

            // Pagination listeners
            $('#whPerPageSelect').on('change', function () {
                whPerPage = parseInt($(this).val(), 10) || 10;
                whCurrentPage = 1;
                updateWhPagination();
            });

            $('#movPerPageSelect').on('change', function () {
                movPerPage = parseInt($(this).val(), 10) || 10;
                movCurrentPage = 1;
                updateMovPagination();
            });

            $(document).on('click', '#whPaginationNav .page-link', function (e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'), 10);
                if (page && page !== whCurrentPage) {
                    whCurrentPage = page;
                    updateWhPagination();
                }
            });

            $(document).on('click', '#movPaginationNav .page-link', function (e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'), 10);
                if (page && page !== movCurrentPage) {
                    movCurrentPage = page;
                    updateMovPagination();
                }
            });

            // Initial render
            renderWarehouseReport();

            // Period Change Listener
            $('#filterPeriod').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateStartCol, #customDateEndCol').slideDown(150);
                } else {
                    $('#customDateStartCol, #customDateEndCol').slideUp(150);
                }
                whCurrentPage = 1;
                movCurrentPage = 1;
                renderWarehouseReport();
            });

            // Input change listeners
            $('#filterStartDate, #filterEndDate, #filterCategory, #filterStockStatus').on('change', function () {
                whCurrentPage = 1;
                movCurrentPage = 1;
                renderWarehouseReport();
            });

            $('#filterSearchInput').on('keyup input', function () {
                whCurrentPage = 1;
                movCurrentPage = 1;
                renderWarehouseReport();
            });

            $('#clearSearchBtn').on('click', function () {
                $('#filterSearchInput').val('');
                whCurrentPage = 1;
                movCurrentPage = 1;
                renderWarehouseReport();
            });

            $('#resetFilterBtn').on('click', function () {
                $('#filterPeriod').val('this_week');
                $('#filterCategory').val('all');
                $('#filterStockStatus').val('all');
                $('#filterSearchInput').val('');
                $('#filterStartDate').val('');
                $('#filterEndDate').val('');
                $('#customDateStartCol, #customDateEndCol').hide();
                whCurrentPage = 1;
                movCurrentPage = 1;
                renderWarehouseReport();
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
                const category = $('#filterCategory').val();
                const stockStatus = $('#filterStockStatus').val();
                const search = $('#filterSearchInput').val();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                const params = new URLSearchParams({
                    period: period,
                    category: category,
                    stock_status: stockStatus,
                    search: search || '',
                    start_date: (period === 'custom' ? startDate : ''),
                    end_date: (period === 'custom' ? endDate : ''),
                });

                window.location.href = `${exportExcelUrl}?${params.toString()}`;
            });
        });
    </script>
@endpush