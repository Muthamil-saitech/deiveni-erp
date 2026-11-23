@extends('layouts.app')

@section('title', 'Purchases & Material Inward Report')
@section('page_title', 'Purchases & Material Inward Report (பொருட்கள் கொள்முதல் & தள வரவு அறிக்கை)')

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
            #sitePurchasesApp {
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

            /* Avoid blank trailing pages */
            * {
                box-sizing: border-box !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="sitePurchasesApp">

        <!-- Dedicated Print & PDF Header (Visible strictly in Print / PDF export) -->
        <div class="print-report-header d-none">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1 text-uppercase text-dark">DEIVENI CONSTRUCTIONS &amp; DEVELOPERS</h4>
                    <h5 class="fw-bold mb-0 text-secondary">Purchases & Material Inward Report (பொருட்கள் கொள்முதல் & தள
                        வரவு அறிக்கை)</h5>
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
                            <i class="bi bi-cart-check-fill text-info me-2"></i>Purchases & Material Inward Report
                            (பொருட்கள் கொள்முதல் & தள வரவு அறிக்கை)
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
                <div class="col-6 col-md-2" id="customDateStartCol"
                    style="{{ ($activeFilters['period'] ?? '') === 'custom' ? '' : 'display: none;' }}">
                    <label class="form-label small fw-semibold mb-1 text-muted">From Date</label>
                    <input type="date" class="form-control form-control-sm" id="filterStartDate"
                        value="{{ $activeFilters['start_date'] ?? $filterMeta['this_week']['start'] }}">
                </div>
                <div class="col-6 col-md-2" id="customDateEndCol"
                    style="{{ ($activeFilters['period'] ?? '') === 'custom' ? '' : 'display: none;' }}">
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
                            <option value="{{ $site->id }}" {{ ($activeFilters['site_id'] ?? 'all') == (string) $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Text Search -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold mb-1 text-muted">
                        <i class="bi bi-search me-1 text-primary"></i> Search Material / Supplier / PO
                    </label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="filterSearchInput"
                            placeholder="Search cement, bricks, PO-2026..." value="{{ $activeFilters['search'] ?? '' }}">
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
                    Active filters: <strong>{{ $filterMeta['this_week']['label'] }}</strong> &bull; <strong>All
                        Sites</strong>
                </span>
                <span class="small text-muted">Current Reference Date: {{ $filterMeta['current_date'] }}</span>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4 kpi-print-grid">
            <div class="col-6 col-md-6">
                <div class="card-custom p-3 bg-white border-start border-4 border-info h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Purchase Orders</div>
                            <h4 class="fw-bold mb-0 text-dark mt-1" id="kpiPoCount">{{ $kpis['po_count'] ?? 0 }}</h4>
                            <small class="text-muted">Orders logged</small>
                        </div>
                        <div class="bg-info-subtle text-info p-2 rounded no-print">
                            <i class="bi bi-file-earmark-text fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="card-custom p-3 bg-white border-start border-4 border-danger h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">Total Purchase Value</div>
                            <h4 class="fw-bold mb-0 text-danger mt-1" id="kpiTotalValue">
                                ₹{{ number_format((float) ($kpis['total_value'] ?? 0), 2) }}</h4>
                            <small class="text-muted">Procurement commitments</small>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2 rounded no-print">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchases Table Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-table text-info me-1"></i>
                        Purchases & Material Inward Register (பொருட்கள் கொள்முதல் விவரங்கள்)
                    </h6>
                    <small class="text-muted">Purchase orders, supplier deliveries, units, rates, and verified inward
                        status</small>
                </div>
                <span class="badge bg-light text-dark border" id="poCountBadge">{{ $kpis['po_count'] ?? 0 }} Purchase
                    Orders</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="smPurchasesTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 110px;">PO Number</th>
                            <th style="min-width: 100px;">Order Date</th>
                            <th style="min-width: 150px;">Project Site</th>
                            <th style="min-width: 160px;">Supplier Name</th>
                            <th style="min-width: 180px;">Material / Equipment</th>
                            <th class="text-center" style="min-width: 110px;">Qty &amp; Unit</th>
                            <th class="text-end" style="min-width: 100px;">Rate (ரூ.)</th>
                            <th class="text-end" style="min-width: 115px;">Total (ரூ.)</th>
                            <th class="text-center" style="min-width: 130px;">Delivery Status</th>
                        </tr>
                    </thead>
                    <tbody id="smPurchasesTableBody" class="small">
                        <!-- Populated dynamically via JavaScript with database records -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="7">Total Materials Purchased &amp; Inwarded:</td>
                            <td class="text-end text-danger fs-6" id="footTotalPurchases">
                                ₹{{ number_format((float) ($kpis['total_value'] ?? 0), 2) }}</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">Verified</span></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 no-print"
                id="tablePaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: 75px;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark"
                            id="pageEnd">{{ min(10, $kpis['po_count'] ?? 0) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $kpis['po_count'] ?? 0 }}</strong> purchase orders
                    </span>
                </div>
                <nav aria-label="Purchases report pagination">
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
            // Dynamic dataset loaded directly from purchase_orders & purchase_order_items tables via SitePurchasesInwardReportController & Service
            const allPurchases = @json($purchases);
            const filterMeta = @json($filterMeta);
            const exportExcelUrl = "{{ route('reports.site.purchases-inward.export-excel') }}";

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function checkDateInRange(dateStr) {
                if (!dateStr) return true;
                const period = $('#filterPeriod').val();
                if (period === 'all') return true;

                if (period === 'custom') {
                    const s = $('#filterStartDate').val();
                    const e = $('#filterEndDate').val();
                    if (s && dateStr < s) return false;
                    if (e && dateStr > e) return false;
                    return true;
                }

                if (filterMeta[period]) {
                    const start = filterMeta[period].start;
                    const end = filterMeta[period].end;
                    return dateStr >= start && dateStr <= end;
                }

                return true;
            }

            let filteredPurchasesList = [];

            function getStatusBadge(status, reason) {
                const s = String(status || 'Ordered').trim();
                let badgeClass = 'bg-secondary';

                if (s === 'Order Completed' || s === 'Delivered & Inwarded') {
                    badgeClass = 'bg-success';
                } else if (s === 'Partial Received') {
                    badgeClass = 'bg-warning text-dark';
                } else if (s === 'Order Return') {
                    badgeClass = 'bg-danger';
                } else if (s === 'Order Canceled') {
                    badgeClass = 'bg-dark';
                } else if (s === 'Ordered') {
                    badgeClass = 'bg-secondary';
                } else {
                    badgeClass = 'bg-info text-dark';
                }

                let html = `<span class="badge ${badgeClass}">${escapeHtml(s)}</span>`;
                if (reason) {
                    html += `<div class="small text-muted mt-1 text-truncate" style="max-width: 140px;" title="${escapeHtml(reason)}"><i class="bi bi-info-circle me-1 text-danger"></i>${escapeHtml(reason)}</div>`;
                }
                return html;
            }

            function renderPurchasesReport() {
                const selectedSite = $('#filterSite').val();
                const searchQuery = ($('#filterSearchInput').val() || '').toLowerCase().trim();

                let totalValue = 0;
                let orderMatchCount = 0;

                filteredPurchasesList = [];
                const tbody = $('#smPurchasesTableBody');
                tbody.empty();

                allPurchases.forEach(function (po) {
                    if (selectedSite !== 'all' && String(po.siteId) !== String(selectedSite)) return;
                    if (!checkDateInRange(po.date)) return;

                    const dbStatus = po.poStatus || po.status || 'Ordered';
                    const dbReason = po.statusReason || po.status_reason || '';

                    if (searchQuery) {
                        const itemsHaystack = (po.items || []).map(it => it.material_name).join(' ');
                        const haystack = `${po.poNumber || ''} ${po.date || ''} ${po.formattedDate || ''} ${po.site || ''} ${po.supplier || ''} ${po.material || ''} ${itemsHaystack} ${dbStatus} ${dbReason} ${po.notes || ''}`.toLowerCase();
                        if (!haystack.includes(searchQuery)) return;
                    }

                    orderMatchCount++;
                    filteredPurchasesList.push(po);

                    const poVal = parseFloat(po.total) || 0;
                    totalValue += poVal;

                    const statusBadge = getStatusBadge(dbStatus, dbReason);

                    // If PO has items list, display clean itemized details
                    if (po.items && po.items.length > 0) {
                        if (po.items.length === 1) {
                            const it = po.items[0];
                            const itemQty = parseFloat(it.quantity) || 0;
                            const itemRate = parseFloat(it.unit_price) || 0;
                            const itemTotal = parseFloat(it.total_price) || (itemQty * itemRate);

                            tbody.append(`
                                            <tr>
                                                <td class="fw-bold text-dark font-monospace">${escapeHtml(po.poNumber)}</td>
                                                <td>
                                                    <div class="fw-bold text-dark">${escapeHtml(po.formattedDate || po.date)}</div>
                                                </td>
                                                <td class="fw-semibold text-dark">${escapeHtml(po.site)}</td>
                                                <td class="small text-dark">${escapeHtml(po.supplier)}</td>
                                                <td class="fw-semibold text-dark">${escapeHtml(it.material_name)}</td>
                                                <td class="text-center">${itemQty} <span class="text-muted small">${escapeHtml(it.unit || '')}</span></td>
                                                <td class="text-end text-muted">₹${itemRate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td class="text-end fw-bold text-danger">₹${itemTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td class="text-center">${statusBadge}</td>
                                            </tr>
                                        `);
                        } else {
                            // Multiple items: show order header row and itemized lines for clear unstructured data avoidance
                            let itemsBadges = '';
                            po.items.forEach(function (it) {
                                const q = parseFloat(it.quantity) || 0;
                                const r = parseFloat(it.unit_price) || 0;
                                const t = parseFloat(it.total_price) || (q * r);
                                itemsBadges += `<div class="d-flex justify-content-between align-items-center py-1 border-bottom border-light">
                                                <span>&bull; ${escapeHtml(it.material_name)} (${q} ${escapeHtml(it.unit)})</span>
                                                <span class="text-muted font-monospace">₹${r.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} &times; ${q} = ₹${t.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                            </div>`;
                            });

                            tbody.append(`
                                            <tr>
                                                <td class="fw-bold text-dark font-monospace align-top">${escapeHtml(po.poNumber)}</td>
                                                <td class="align-top">
                                                    <div class="fw-bold text-dark">${escapeHtml(po.formattedDate || po.date)}</div>
                                                </td>
                                                <td class="fw-semibold text-dark align-top">${escapeHtml(po.site)}</td>
                                                <td class="small text-dark align-top">${escapeHtml(po.supplier)}</td>
                                                <td class="fw-semibold text-dark align-top" colspan="3">
                                                    <div class="mb-1 text-primary fw-semibold">${escapeHtml(po.material)} <span class="badge bg-light text-secondary border ms-1">${po.items.length} items</span></div>
                                                    <div class="small bg-light-subtle p-2 rounded border border-light">
                                                        ${itemsBadges}
                                                    </div>
                                                </td>
                                                <td class="text-end fw-bold text-danger align-top">₹${poVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                                <td class="text-center align-top">${statusBadge}</td>
                                            </tr>
                                        `);
                        }
                    } else {
                        // Fallback row for simple purchase order record
                        const qty = parseFloat(po.qty) || 1;
                        const rate = parseFloat(po.rate) || poVal;

                        tbody.append(`
                                        <tr>
                                            <td class="fw-bold text-dark font-monospace">${escapeHtml(po.poNumber)}</td>
                                            <td>
                                                <div class="fw-bold text-dark">${escapeHtml(po.formattedDate || po.date)}</div>
                                            </td>
                                            <td class="fw-semibold text-dark">${escapeHtml(po.site)}</td>
                                            <td class="small text-dark">${escapeHtml(po.supplier)}</td>
                                            <td class="fw-semibold text-dark">${escapeHtml(po.material)}</td>
                                            <td class="text-center">${qty} <span class="text-muted small">${escapeHtml(po.unit || '')}</span></td>
                                            <td class="text-end text-muted">₹${rate.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            <td class="text-end fw-bold text-danger">₹${poVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                                            <td class="text-center">${statusBadge}</td>
                                        </tr>
                                    `);
                    }
                });

                if (orderMatchCount === 0) {
                    tbody.html('<tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-cart-x fs-2 d-block mb-2 text-secondary"></i><div class="fw-semibold">No Purchases or Inwards Found</div><small>Try selecting "All Dates" or adjusting your search query.</small></td></tr>');
                }

                // Update KPIs
                $('#kpiPoCount').text(orderMatchCount);
                $('#kpiTotalValue').text('₹' + totalValue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                // Update Footer & Badges
                $('#footTotalPurchases').text('₹' + totalValue.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#poCountBadge').text(`${orderMatchCount} Purchase Orders`);

                const pText = $('#filterPeriod option:selected').text();
                const sText = $('#filterSite option:selected').text();
                $('#filterMatchCountBadge').html(`Active filters: <strong>${escapeHtml(pText)}</strong> &bull; <strong>${escapeHtml(sText)}</strong>`);

                $('#printActivePeriod').html(`Period: <strong>${escapeHtml(pText)}</strong>`);
                $('#printActiveSite').html(`Site: <strong>${escapeHtml(sText)}</strong>`);

                updatePagination();
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function updatePagination() {
                const totalCount = $('#smPurchasesTableBody tr').not(':has(td[colspan])').length;
                const totalPages = Math.ceil(totalCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const rows = $('#smPurchasesTableBody tr').not(':has(td[colspan])');
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

                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${totalCount}</strong> purchase orders`;
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

            renderPurchasesReport();

            // Filter Event Listeners
            $('#filterPeriod').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateStartCol, #customDateEndCol').slideDown(150);
                } else {
                    $('#customDateStartCol, #customDateEndCol').slideUp(150);
                }
                currentPage = 1;
                renderPurchasesReport();
            });

            $('#filterStartDate, #filterEndDate, #filterSite').on('change', function () {
                currentPage = 1;
                renderPurchasesReport();
            });

            $('#filterSearchInput').on('keyup input', function () {
                currentPage = 1;
                renderPurchasesReport();
            });

            $('#clearSearchBtn').on('click', function () {
                $('#filterSearchInput').val('');
                currentPage = 1;
                renderPurchasesReport();
            });

            $('#resetFilterBtn').on('click', function () {
                $('#filterPeriod').val('this_week');
                $('#filterSite').val('all');
                $('#filterSearchInput').val('');
                $('#customDateStartCol, #customDateEndCol').hide();
                currentPage = 1;
                renderPurchasesReport();
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
                const search = $('#filterSearchInput').val();
                const startDate = $('#filterStartDate').val();
                const endDate = $('#filterEndDate').val();

                const params = new URLSearchParams({
                    period: period,
                    site_id: siteId,
                    search: search || '',
                    start_date: (period === 'custom' ? startDate : ''),
                    end_date: (period === 'custom' ? endDate : ''),
                });

                window.location.href = `${exportExcelUrl}?${params.toString()}`;
            });
        });
    </script>
@endpush