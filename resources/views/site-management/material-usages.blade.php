@extends('layouts.app')

@section('title', 'Material Usage (பொருள் பயன்பாடு)')
@section('page_title', 'Material Usage (பொருட்களின் பயன்பாடு பதிவு)')

@push('styles')
    <style>
        .pagination .page-link {
            color: #1e3a8a;
            border-color: #e2e8f0;
            border-radius: 6px;
            margin: 0 2px;
            font-size: 0.8rem;
            padding: 4px 10px;
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

        .po-badge {
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .source-po { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .source-wh { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }

        /* Modal Table Styling */
        #muMaterialsTable { font-size: 0.83rem; }
        #muMaterialsTable thead th {
            background: #f1f5f9;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
            padding: 9px 10px;
            color: #475569;
        }
        #muMaterialsTable tbody td { vertical-align: middle; padding: 7px 10px; }
        .mu-qty-input {
            width: 105px;
            font-weight: 600;
        }
        .avail-badge {
            font-size: 0.72rem;
            padding: 2px 7px;
            border-radius: 12px;
            font-weight: 600;
        }
        .avail-ok  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .avail-nil { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* List Table Material Mini-Table */
        .mat-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 3px 0;
            border-bottom: 1px dashed #f1f5f9;
            font-size: 0.8rem;
        }
        .mat-item-row:last-child { border-bottom: none; }
        .mat-item-name { font-weight: 600; color: #1e293b; }
        .mat-item-po { font-size: 0.72rem; color: #64748b; }
        .mat-item-qty { font-weight: 600; color: #0284c7; }
        .mat-item-price { font-size: 0.75rem; color: #475569; }

        /* Custom Multi-PO Dropdown Button */
        #poDropdownBtn {
            background-color: #ffffff !important;
            border: 1px solid #ced4da !important;
            color: #212529 !important;
            box-shadow: none !important;
            font-size: 0.84rem;
            height: 31px;
            cursor: pointer;
        }
        #poDropdownBtn:focus, #poDropdownBtn:hover:not(:disabled) {
            border-color: #86b7fe !important;
        }
        #poDropdownBtn:disabled {
            background-color: #e9ecef !important;
            cursor: not-allowed;
            color: #6c757d !important;
        }

        /* Custom Multi-PO Dropdown Menu */
        #poDropdownMenu {
            width: 380px;
            max-width: 95vw;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            padding: 10px;
            z-index: 1060;
        }

        /* Clean PO Checkbox Item inside Dropdown (Fixes clipped checkboxes) */
        .po-dropdown-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 9px 12px;
            margin-bottom: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .po-dropdown-item:last-child {
            margin-bottom: 0;
        }
        .po-dropdown-item:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }
        .po-dropdown-item.checked {
            background-color: #f0f9ff;
            border-color: #38bdf8;
        }
        .po-dropdown-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 2px 0 0 0 !important;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: #0284c7;
        }

        /* Selected PO Chips */
        .po-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 14px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .po-chip .btn-close-chip {
            cursor: pointer;
            color: #0369a1;
            font-size: 0.95rem;
            line-height: 1;
            font-weight: bold;
        }
        .po-chip .btn-close-chip:hover {
            color: #b91c1c;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid px-2 px-md-4 py-3">

    {{-- Top Header --}}
    <div class="row align-items-center mb-3 g-2">
        <div class="col-12 col-md-7">
            <h4 class="fw-bold mb-1 text-dark">
                <i class="bi bi-clipboard2-check text-primary me-2"></i>Material Usage
                <span class="text-muted fw-normal">(பொருள் பயன்பாடு)</span>
            </h4>
            <p class="text-muted small mb-0">
                Record material consumption at project sites from one or multiple Purchase Orders with auto-fetched quantity and pricing.
            </p>
        </div>
        <div class="col-12 col-md-5 text-md-end">
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" id="newUsageBtn">
                <i class="bi bi-plus-circle me-1"></i> New Material Usage
            </button>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card-stat h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Usages</span>
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-clipboard2-data"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0 text-dark">{{ $stats['total_usages'] }}</h4>
                <div class="small text-muted mt-1">All-time usage records</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card-stat h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">This Month</span>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0 text-success">{{ $stats['this_month'] }}</h4>
                <div class="small text-muted mt-1">{{ now()->format('F Y') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card-stat h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Materials Used</span>
                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-boxes"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0 text-warning">{{ $stats['total_items'] }}</h4>
                <div class="small text-muted mt-1">Material line items</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card-stat h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Total Qty Used</span>
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
                <h4 class="fw-bold mb-0 text-info">{{ number_format($stats['total_qty_used'], 2) }}</h4>
                <div class="small text-muted mt-1">Total quantity consumed</div>
            </div>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('material-usages.index') }}" class="row g-2 align-items-end" id="usageFilterForm">
                <div class="col-12 col-md-4">
                    <input type="text" name="search" id="usageSearchInput" class="form-control form-control-sm"
                        placeholder="Search usage no, PO number, material…" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <select name="site_id" id="usageSiteFilter" class="form-select form-select-sm">
                        <option value="">— All Sites (அனைத்து தளங்கள்) —</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" {{ ($filters['site_id'] ?? '') == $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="start_date" id="usageStartDateFilter" class="form-control form-control-sm"
                        value="{{ $filters['start_date'] ?? '' }}" placeholder="From">
                </div>
                <div class="col-6 col-md-2">
                    <input type="date" name="end_date" id="usageEndDateFilter" class="form-control form-control-sm"
                        value="{{ $filters['end_date'] ?? '' }}" placeholder="To">
                </div>
                <div class="col-6 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Filter">
                        <i class="bi bi-funnel"></i>
                    </button>
                    <a href="{{ route('material-usages.index') }}" id="usageFilterResetBtn" class="btn btn-outline-secondary btn-sm" title="Reset">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Active Site Balance Stock Banner (if site filter selected) --}}
    @if (!empty($siteStocks) && $siteStocks->isNotEmpty())
        @php
            $currentSite = $sites->firstWhere('id', (int) ($filters['site_id'] ?? 0));
        @endphp
        <div class="card shadow-sm border-0 mb-3 border-start border-4 border-info">
            <div class="card-body py-2 px-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-stack fs-5 text-info"></i>
                        <div>
                            <strong class="text-dark">{{ $currentSite?->name ?? 'Project Site' }}</strong>
                            <span class="text-muted small ms-1">— Current Site Balance Stock (தற்போது தளத்தில் உள்ள மீதமுள்ள இருப்பு)</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($siteStocks as $sStock)
                            <span class="badge bg-light text-dark border px-2 py-1">
                                {{ $sStock->material?->name_en ?? 'Material' }}:
                                <strong class="{{ (float) $sStock->current_stock > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format((float) $sStock->current_stock, 2) }} {{ $sStock->unit ?: ($sStock->material?->unit?->name_en ?? 'Nos') }}
                                </strong>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- List table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="usagesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width:40px">#</th>
                            <th>USAGE NO &amp; DATE</th>
                            <th>SITE (தளம்)</th>
                            <th>SOURCE / PO (ஆதாரம்)</th>
                            <th>MATERIALS, SOURCE &amp; PRICE (பொருட்கள், ஆதாரம் &amp; விலை)</th>
                            <th class="text-end">TOTAL QTY</th>
                            <th class="text-end">TOTAL COST</th>
                            <th>RECORDED BY</th>
                            <th class="text-end pe-3" style="width:70px">ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="usagesTableBody">
                        @forelse ($usages as $usage)
                            @php
                                $rowSearchText = strtolower(implode(' ', array_filter([
                                    $usage->usage_no,
                                    $usage->usage_date?->format('d-m-Y'),
                                    $usage->usage_date?->format('Y-m-d'),
                                    $usage->projectSite?->name,
                                    $usage->creator?->name,
                                    $usage->remarks,
                                    $usage->purchaseOrder?->po_number,
                                    $usage->items->pluck('material_name')->implode(' '),
                                    $usage->items->pluck('source_reference')->implode(' '),
                                    $usage->items->pluck('source_type')->implode(' '),
                                ])));
                            @endphp
                            <tr class="usage-row" data-id="{{ $usage->id }}"
                                data-search="{{ $rowSearchText }}"
                                data-site-id="{{ $usage->project_site_id ?? '' }}"
                                data-date="{{ $usage->usage_date?->format('Y-m-d') ?? '' }}">
                                <td class="ps-3 text-muted fw-semibold serial-cell">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="fw-semibold text-dark" style="font-size:0.85rem;">{{ $usage->usage_no }}</div>
                                    <div class="text-muted small">{{ $usage->usage_date?->format('d-m-Y') }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $usage->projectSite?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    @php
                                        $hasWarehouse = $usage->items->where('source_type', 'Own Warehouse')->count() > 0;
                                        $hasTransfer = $usage->items->filter(fn($it) => $it->source_type === 'Stock Transfer' || str_starts_with($it->source_reference ?? '', 'XFR'))->count() > 0;
                                        $poNumbers = $usage->items->where('source_type', '!=', 'Own Warehouse')->where('source_type', '!=', 'Stock Transfer')->filter(fn($it) => !str_starts_with($it->source_reference ?? '', 'XFR'))->pluck('source_reference')->filter()->unique();
                                        $transferNumbers = $usage->items->filter(fn($it) => $it->source_type === 'Stock Transfer' || str_starts_with($it->source_reference ?? '', 'XFR'))->pluck('source_reference')->filter()->unique();
                                    @endphp
                                    @if ($hasWarehouse)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle mb-1"><i class="bi bi-boxes me-1"></i>Own Warehouse</span><br>
                                    @endif
                                    @if ($hasTransfer)
                                        @foreach ($transferNumbers as $xferNum)
                                            <span class="badge border mb-1" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;"><i class="bi bi-arrow-left-right me-1"></i>{{ $xferNum }}</span><br>
                                        @endforeach
                                    @endif
                                    @if ($poNumbers->count() > 0)
                                        @foreach ($poNumbers as $poNum)
                                            <span class="po-badge source-po me-1 mb-1">{{ $poNum }}</span>
                                        @endforeach
                                    @elseif (!$hasWarehouse && !$hasTransfer && $usage->purchaseOrder)
                                        <span class="po-badge source-po">{{ $usage->purchaseOrder->po_number }}</span>
                                    @elseif (!$hasWarehouse && !$hasTransfer)
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td style="min-width: 280px;">
                                    @foreach ($usage->items as $item)
                                        <div class="mat-item-row">
                                            <div>
                                                <span class="mat-item-name">{{ $item->material_name }}</span>
                                                @if ($item->source_type === 'Own Warehouse')
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.68rem;"><i class="bi bi-boxes me-1"></i>Own Warehouse</span>
                                                @elseif ($item->source_type === 'Stock Transfer' || str_starts_with($item->source_reference ?? '', 'XFR'))
                                                    <span class="badge border ms-1" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;font-size:0.68rem;"><i class="bi bi-arrow-left-right me-1"></i>{{ $item->source_reference ?: 'Transfer' }}</span>
                                                @else
                                                    <span class="mat-item-po badge bg-light text-secondary border ms-1">{{ $item->source_reference ?: 'PO' }}</span>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <span class="mat-item-qty">{{ number_format((float) $item->used_quantity, 2) }} {{ $item->unit }}</span>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.68rem;" title="Balance stock remaining after this usage">
                                                    Bal: {{ number_format((float) $item->remaining_quantity, 2) }} {{ $item->unit }}
                                                </span>
                                                <span class="mat-item-price ms-1 text-muted">@ ₹{{ number_format((float) $item->unit_price, 2) }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="text-end fw-semibold small text-dark">
                                    {{ number_format((float) $usage->total_used_quantity, 2) }}
                                </td>
                                <td class="text-end fw-semibold small text-success">
                                    ₹{{ number_format((float) $usage->total_cost, 2) }}
                                </td>
                                <td class="small text-muted">{{ $usage->creator?->name ?? '—' }}</td>
                                <td class="text-end pe-3">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item view-usage-btn" type="button" data-id="{{ $usage->id }}"><i class="bi bi-eye text-info me-2"></i>View Details</button></li>
                                            <li><button class="dropdown-item edit-usage-btn" type="button" data-id="{{ $usage->id }}"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li><button class="dropdown-item text-danger delete-usage-btn" type="button" data-id="{{ $usage->id }}" data-no="{{ $usage->usage_no }}"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyUsagesRow">
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-clipboard2-x display-5 d-block mb-2 opacity-25"></i>
                                    No material usage records yet<br>
                                    <small>Click "+ New Material Usage" to record material consumption.</small>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching material usage records found for your search or filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3" id="tablePaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: 75px;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, $usages->count()) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $usages->count() }}</strong> material usages
                    </span>
                </div>
                <nav aria-label="Material usage table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════
     RECORD NEW MATERIAL USAGE MODAL
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="newUsageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-clipboard2-plus me-2"></i>Record Material Usage (பொருள் பயன்பாடு பதிவு)
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body px-3 py-3">

                <div class="alert alert-info d-flex gap-2 py-2 small mb-3">
                    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                    <span>
                        1. Select <strong>Project Site</strong>. &nbsp;|&nbsp; 
                        2. Choose <strong>Third-Party Supplier (PO)</strong> or <strong>Own Warehouse</strong> stock. &nbsp;|&nbsp; 
                        3. Add materials with auto-fetched pricing and enter the quantity <strong>actually used</strong>.
                    </span>
                </div>

                <form id="usageForm" novalidate>
                    @csrf

                    {{-- Header Inputs: Usage No | Date | Site --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">
                                Usage No <span class="text-muted fw-normal">(Auto)</span>
                            </label>
                            <input type="text" class="form-control form-control-sm bg-light fw-semibold"
                                id="usageNoField" value="{{ $nextUsageNo }}" readonly>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">
                                Usage Date (தேதி) <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-sm"
                                id="usageDateField" name="usage_date"
                                max="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback">Select a valid date.</div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">
                                Project Site (தளம்) <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm" id="siteSelect" name="project_site_id" required>
                                <option value="">— Select Site (தளம்) —</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a Project Site.</div>
                        </div>
                    </div>

                    {{-- Site Live Balance Stock Bar --}}
                    <div id="siteLiveStockBar" class="p-2 mb-3 bg-white border rounded shadow-sm d-none">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-bold text-dark">
                                <i class="bi bi-stack text-info me-1"></i>Site Live Balance Stock (தளத்தில் உள்ள மீதமுள்ள இருப்பு):
                            </span>
                            <span class="badge bg-light text-secondary border small" id="siteLiveStockCount">0 materials</span>
                        </div>
                        <div id="siteLiveStockChips" class="d-flex flex-wrap gap-1"></div>
                    </div>

                    {{-- Material / Supplier Source Selection (Image 2 style) --}}
                    <div class="p-3 rounded-3 bg-light border mb-3" id="materialSourceContainer" style="display: none;">
                        <label class="form-label small fw-semibold text-dark mb-1">
                            <i class="bi bi-box-arrow-in-down text-primary me-1"></i>Material / Supplier Source (பொருள் பெறும் ஆதாரம்) <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex flex-wrap gap-4 align-items-center mt-1">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="material_source" id="sourceThirdParty" value="third_party" checked>
                                <label class="form-check-label small fw-semibold text-dark" for="sourceThirdParty">
                                    Third-Party Supplier (வெளி சப்ளையர் - PO)
                                </label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="material_source" id="sourceStockTransfer" value="stock_transfer">
                                <label class="form-check-label small fw-semibold text-purple" for="sourceStockTransfer" style="color:#7e22ce;">
                                    <i class="bi bi-arrow-left-right me-1"></i>Transferred from Other Site (தள பரிமாற்றம்)
                                </label>
                            </div>
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="radio" name="material_source" id="sourceWarehouse" value="own_warehouse">
                                <label class="form-check-label small fw-semibold text-primary" for="sourceWarehouse">
                                    Own Warehouse (மைய கிடங்கு இருப்பு)
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Scenario 1: Third-Party Supplier PO Selection --}}
                    <div id="supplierPoSection" class="mb-3" style="display: none;">
                        <div class="p-3 border rounded bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold text-dark mb-0">
                                    <i class="bi bi-file-earmark-text text-primary me-1"></i>Select Purchase Order(s) (கொள்முதல் ஆணை) <span class="text-danger">*</span>
                                </label>
                                <span class="badge bg-primary-subtle text-primary border small" id="poCountBadge" style="display:none;">0 POs</span>
                            </div>
                            <div class="text-muted small mb-2" style="font-size:0.75rem;">
                                Choose one or more Purchase Orders for this site to load materials with inward quantity and pricing.
                            </div>
                            <div class="dropdown" id="poDropdownWrapper">
                                <button class="form-select form-select-sm text-start" 
                                    type="button" id="poDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" data-bs-auto-close="outside" style="background-color: #ffffff !important; border: 1px solid #ced4da;">
                                    <span id="poDropdownText" class="text-truncate small text-muted">— Select Purchase Order(s) —</span>
                                </button>
                                <div class="dropdown-menu shadow w-100" id="poDropdownMenu" style="max-width: 600px;">
                                    <div class="text-muted small p-2" id="poDropdownEmpty">
                                        Please select a Project Site first.
                                    </div>
                                    <div id="poCheckboxContainer" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom px-2">
                                            <span class="small fw-bold text-dark">Available POs</span>
                                            <div>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2 small fw-semibold text-primary" id="selectAllPOsBtn">Select All</button>
                                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-danger" id="clearAllPOsBtn">Clear</button>
                                            </div>
                                        </div>
                                        <div id="poCheckboxList" style="max-height: 240px; overflow-y: auto; padding-right: 2px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="invalid-feedback d-block" id="poValidationFeedback" style="display:none;">
                                Please select at least one Purchase Order.
                            </div>
                            <div id="poDateMismatchFeedback" class="small mt-1 fw-semibold text-danger" style="display:none;"></div>

                            {{-- Selected PO Chips Area --}}
                            <div id="selectedPOChipsRow" class="mt-2" style="display: none;">
                                <div class="d-flex align-items-center flex-wrap gap-2 p-2 bg-white border rounded">
                                    <span class="small text-muted fw-bold"><i class="bi bi-cart-check text-primary me-1"></i>Selected POs:</span>
                                    <div id="poChipsContainer" class="d-flex flex-wrap gap-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Scenario 2: Own Warehouse Materials Selection --}}
                    <div id="ownWarehouseSection" class="mb-3" style="display: none;">
                        <div class="p-3 border rounded bg-light-subtle">
                            <label class="form-label small fw-bold text-dark mb-1">
                                <i class="bi bi-boxes text-primary me-1"></i>Select In-Stock Materials from Central Warehouse (மைய கிடங்கு இருப்பு பொருட்கள்) <span class="text-danger">*</span>
                            </label>
                            <div class="text-muted small mb-2" style="font-size:0.75rem;">
                                Directly record consumption of tools, safety equipment, and materials from Central Warehouse stock for this site.
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-9">
                                    <select class="form-select form-select-sm" id="warehouseItemPicker">
                                        <option value="" selected disabled>-- Select Material from Central Warehouse (கிடங்கு பொருள் தேர்வு) --</option>
                                        @foreach ($warehouseItems as $whItem)
                                            <option value="{{ $whItem->id }}"
                                                data-id="{{ $whItem->id }}"
                                                data-sku="{{ $whItem->sku }}"
                                                data-name="{{ $whItem->name_en }}"
                                                data-tamil="{{ $whItem->name_ta }}"
                                                data-stock="{{ $whItem->current_stock }}"
                                                data-unit="{{ $whItem->unit ?: 'Nos' }}"
                                                data-unit-id="{{ $whItem->unit_id }}"
                                                data-rate="{{ $whItem->cash_amount ?: ($whItem->unit_rate ?: 0) }}">
                                                [{{ $whItem->sku }}] {{ $whItem->name_en }}@if($whItem->name_ta) ({{ $whItem->name_ta }})@endif · Stock: {{ (float) $whItem->current_stock }} {{ $whItem->unit ?: 'Nos' }} @ ₹{{ number_format((float) ($whItem->cash_amount ?: $whItem->unit_rate), 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-3">
                                    <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold" id="addWarehouseItemBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Material
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Loading Spinner --}}
                    <div id="poLoadingSpinner" class="text-center py-4" style="display:none;">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                        <span class="text-muted small">Loading PO materials…</span>
                    </div>

                    {{-- Empty State (Before items are selected) --}}
                    <div id="materialsEmptyState" class="border rounded p-4 text-center text-muted bg-light mb-3">
                        <i class="bi bi-box-arrow-in-down fs-2 text-primary d-block mb-1 opacity-75"></i>
                        <span class="small fw-semibold" id="emptyStateMessage">Please select a Project Site above to get started.</span>
                    </div>

                    {{-- Multiple Materials Table View --}}
                    <div id="materialsSection" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-semibold mb-0 small text-uppercase text-muted letter-spacing-1">
                                <i class="bi bi-table me-1 text-primary"></i>Materials to Record for Usage (பயன்படுத்தப்படும் பொருட்கள்)
                            </h6>
                            <span class="badge bg-light text-secondary border" id="itemCountBadge">0 materials</span>
                        </div>

                        <div class="table-responsive border rounded bg-white shadow-sm">
                            <table class="table table-sm table-bordered align-middle mb-0" id="muMaterialsTable">
                                <thead>
                                    <tr>
                                        <th style="width:34px" class="text-center">#</th>
                                        <th>Material (பொருள்)</th>
                                        <th>Source / PO (ஆதாரம்)</th>
                                        <th class="text-center">Inward / Stock</th>
                                        <th class="text-center">Already Used</th>
                                        <th class="text-center">Available Stock</th>
                                        <th class="text-center" style="width:130px">
                                            Used Qty (அளவு) <span class="text-danger">*</span>
                                        </th>
                                        <th class="text-center" style="width:130px">
                                            Balance Stock (மீதமுள்ள இருப்பு)
                                        </th>
                                        <th class="text-center">Unit</th>
                                        <th class="text-end">Unit Price (விலை ₹)</th>
                                        <th class="text-end">Total (தொகை ₹)</th>
                                        <th class="text-center" style="width:36px">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="muMaterialsBody">
                                    {{-- populated by JS --}}
                                </tbody>
                                <tfoot>
                                    <tr class="table-secondary fw-semibold">
                                        <td colspan="6" class="text-end small pe-3">Grand Total:</td>
                                        <td class="text-center text-primary fw-bold" id="grandTotalQty">0.00</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-end text-success fw-bold" id="grandTotalPrice">₹0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 text-muted small">
                            <span><i class="bi bi-info-circle me-1"></i>Price is auto-fetched from PO or Warehouse. Total is calculated automatically.</span>
                            <span class="fst-italic">Click ✕ to remove materials not needed.</span>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    <div class="mt-3">
                        <label class="form-label small fw-semibold mb-1">Remarks / Work Notes (குறிப்புகள்)</label>
                        <textarea class="form-control form-control-sm" name="remarks" rows="2"
                            placeholder="e.g. Consumed for first floor slab casting / pillar work"></textarea>
                    </div>

                </form>
            </div>

            <div class="modal-footer py-2 px-3 justify-content-between">
                <div class="small text-muted" id="modalFooterHint"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" id="saveUsageBtn">
                        <i class="bi bi-check-circle me-1"></i> Save Material Usage
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     VIEW DETAILS MODAL
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewUsageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light py-2 px-3">
                <h6 class="modal-title fw-bold text-dark mb-0">
                    <i class="bi bi-receipt me-2 text-primary"></i>Material Usage Voucher Details
                </h6>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-3" id="viewUsageContent">
                {{-- Loaded via AJAX --}}
                <div class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     EDIT MATERIAL USAGE MODAL
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editUsageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Edit Material Usage (பொருள் பயன்பாடு திருத்துதல்) — <span id="editUsageNoTitle"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 py-3">
                <form id="editUsageForm" novalidate>
                    <input type="hidden" id="editUsageId">

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Usage No</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-semibold" id="editUsageNoField" readonly>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Usage Date (தேதி) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" id="editUsageDateField" name="usage_date" max="{{ date('Y-m-d') }}" required>
                            <div class="invalid-feedback">Select a valid date.</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Project Site (தளம்)</label>
                            <input type="text" class="form-control form-control-sm bg-light fw-semibold" id="editUsageSiteField" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="fw-semibold mb-0 small text-uppercase text-muted">
                                    <i class="bi bi-boxes text-primary me-1"></i>Materials Consumed (பயன்படுத்திய பொருட்கள்)
                                </h6>
                                <span class="text-muted small">Update quantity or price below. Linked PO & stock will update automatically.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="toggleEditUsageAddMaterialBtn">
                                <i class="bi bi-plus-circle me-1"></i> Add Material
                            </button>
                        </div>

                        {{-- Add Material Inline Box in Edit Modal --}}
                        <div id="editUsageAddMaterialBox" class="p-2 mb-3 border rounded bg-light-subtle shadow-sm" style="display: none;">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-7">
                                    <label class="form-label small fw-semibold text-dark mb-1">
                                        <i class="bi bi-box-seam text-primary me-1"></i>Select Material from Site POs or Warehouse (பொருள் தேர்வு)
                                    </label>
                                    <select class="form-select form-select-sm" id="editUsageMaterialPicker">
                                        <option value="">-- Choose Material to Add --</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small fw-semibold text-dark mb-1">Used Qty (அளவு) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-center fw-semibold" id="editUsageNewItemQty" placeholder="Qty">
                                </div>
                                <div class="col-6 col-md-2 d-flex gap-1">
                                    <button type="button" class="btn btn-primary btn-sm flex-grow-1 fw-semibold" id="confirmAddUsageMaterialBtn">
                                        <i class="bi bi-plus-lg me-1"></i> Add
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelAddUsageMaterialBtn" title="Close">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0" id="editMuMaterialsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:34px" class="text-center">#</th>
                                        <th>Material (பொருள்)</th>
                                        <th>Source / PO (ஆதாரம்)</th>
                                        <th class="text-center" style="width:105px">Inward Stock</th>
                                        <th class="text-center" style="width:130px">Used Qty (அளவு) <span class="text-danger">*</span></th>
                                        <th class="text-center" style="width:115px">Balance Stock (மீதமுள்ள இருப்பு)</th>
                                        <th class="text-center" style="width:80px">Unit</th>
                                        <th class="text-end" style="width:130px">Unit Price (₹) <span class="text-danger">*</span></th>
                                        <th class="text-end" style="width:130px">Line Total (₹)</th>
                                        <th class="text-center" style="width:40px">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editMuMaterialsBody">
                                    {{-- Dynamically loaded --}}
                                </tbody>
                                <tfoot class="table-secondary fw-semibold">
                                    <tr>
                                        <td colspan="4" class="text-end">Total:</td>
                                        <td class="text-center text-primary fw-bold" id="editMuTotalQty">0.00</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-end text-success fw-bold" id="editMuTotalCost">₹0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">Remarks / Work Details (குறிப்புகள்)</label>
                        <textarea class="form-control form-control-sm" id="editUsageRemarks" rows="2" placeholder="e.g. Columns concreting, brickwork ground floor..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-3" id="saveEditUsageBtn">
                    <i class="bi bi-check-circle me-1"></i> Update Material Usage
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const newModal = new bootstrap.Modal(document.getElementById('newUsageModal'));
    const viewModal = new bootstrap.Modal(document.getElementById('viewUsageModal'));
    const editModal = new bootstrap.Modal(document.getElementById('editUsageModal'));

    // Cache of site POs, transfers, stocks and their materials
    let sitePOs = [];
    let siteTransfers = [];
    let siteStocks = [];
    let siteAllMaterials = [];
    let selectedPOIds = new Set();
    let selectedTransferIds = new Set();
    let displayedItems = [];

    // ── Open modal ────────────────────────────────────────────
    document.getElementById('newUsageBtn').addEventListener('click', function () {
        resetForm();
        newModal.show();
    });

    function resetForm() {
        document.getElementById('usageForm').reset();
        document.getElementById('usageDateField').value = '{{ date('Y-m-d') }}';
        document.getElementById('siteSelect').value = '';
        
        sitePOs = [];
        siteTransfers = [];
        siteStocks = [];
        siteAllMaterials = [];
        selectedPOIds.clear();
        selectedTransferIds.clear();
        displayedItems = [];

        document.getElementById('sourceThirdParty').checked = true;
        document.getElementById('materialSourceContainer').style.display = 'none';
        document.getElementById('supplierPoSection').style.display = 'none';
        document.getElementById('ownWarehouseSection').style.display = 'none';

        const stockBar = document.getElementById('siteLiveStockBar');
        if (stockBar) {
            stockBar.classList.add('d-none');
            document.getElementById('siteLiveStockChips').innerHTML = '';
            document.getElementById('siteLiveStockCount').textContent = '0 materials';
        }

        document.getElementById('poDropdownBtn').disabled = true;
        document.getElementById('poDropdownText').textContent = '— Select Site First —';
        document.getElementById('poDropdownText').className = 'text-truncate small text-muted';
        document.getElementById('poDropdownEmpty').textContent = 'Please select a Project Site first.';
        document.getElementById('poDropdownEmpty').style.display = 'block';
        document.getElementById('poCheckboxContainer').style.display = 'none';
        document.getElementById('poCheckboxList').innerHTML = '';
        document.getElementById('selectedPOChipsRow').style.display = 'none';
        document.getElementById('poChipsContainer').innerHTML = '';

        document.getElementById('materialsSection').style.display = 'none';
        document.getElementById('materialsEmptyState').style.display = 'block';
        document.getElementById('emptyStateMessage').textContent = 'Please select a Project Site above to get started.';
        document.getElementById('poLoadingSpinner').style.display = 'none';
        document.getElementById('muMaterialsBody').innerHTML = '';
        document.getElementById('grandTotalQty').textContent = '0.00';
        document.getElementById('grandTotalPrice').textContent = '₹0.00';
        document.getElementById('itemCountBadge').textContent = '0 materials';
        document.getElementById('modalFooterHint').textContent = '';
        document.getElementById('poValidationFeedback').style.display = 'none';
        document.querySelectorAll('#usageForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    // ── When Site changes ─────────────────────────────────────
    document.getElementById('siteSelect').addEventListener('change', function () {
        const siteId = this.value;
        
        // Reset selections and table state
        selectedPOIds.clear();
        selectedTransferIds.clear();
        displayedItems = [];
        document.getElementById('selectedPOChipsRow').style.display = 'none';
        document.getElementById('poChipsContainer').innerHTML = '';
        document.getElementById('materialsSection').style.display = 'none';
        document.getElementById('muMaterialsBody').innerHTML = '';
        document.getElementById('poValidationFeedback').style.display = 'none';

        if (!siteId) {
            document.getElementById('materialSourceContainer').style.display = 'none';
            document.getElementById('supplierPoSection').style.display = 'none';
            document.getElementById('ownWarehouseSection').style.display = 'none';
            const stockBar = document.getElementById('siteLiveStockBar');
            if (stockBar) stockBar.classList.add('d-none');
            document.getElementById('materialsEmptyState').style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = 'Please select a Project Site above to get started.';
            return;
        }

        // Show Material / Supplier Source Box and load site data
        document.getElementById('materialSourceContainer').style.display = 'block';
        loadSiteMaterialsData(siteId);
    });

    // ── When Material Source Radio changes (Image 2 style) ─────
    document.querySelectorAll('input[name="material_source"]').forEach(radio => {
        radio.addEventListener('change', function () {
            const siteId = document.getElementById('siteSelect').value;
            if (!siteId) return;
            syncSourceView();
        });
    });

    // ── Load POs & Transfers for Site ─────────────────────────
    function loadSiteMaterialsData(siteId) {
        document.getElementById('poDropdownBtn').disabled = true;
        document.getElementById('poDropdownText').textContent = 'Loading site data…';
        document.getElementById('poDropdownText').className = 'text-truncate small text-muted';
        document.getElementById('poLoadingSpinner').style.display = 'block';
        document.getElementById('materialsEmptyState').style.display = 'none';

        fetch(`{{ url('/material-usages/site-materials') }}/${siteId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('poLoadingSpinner').style.display = 'none';

            if (!data.success || !data.data) {
                showToast('Failed to load materials for this site.', 'danger');
                return;
            }

            sitePOs = data.data.pos || [];
            siteTransfers = data.data.transfers || [];
            siteStocks = data.data.site_stocks || [];
            siteAllMaterials = data.data.items || [];

            // Populate Site Live Stock Bar
            const stockBar = document.getElementById('siteLiveStockBar');
            const stockChips = document.getElementById('siteLiveStockChips');
            const stockCount = document.getElementById('siteLiveStockCount');
            if (stockBar && stockChips) {
                stockBar.classList.remove('d-none');
                if (siteStocks.length > 0) {
                    stockCount.textContent = `${siteStocks.length} materials in stock`;
                    stockChips.innerHTML = siteStocks.map(s => `
                        <span class="badge border" style="background:#f8fafc;color:#1e293b;border-color:#cbd5e1!important;font-size:0.75rem;padding:4px 8px;">
                            <strong>${esc(s.material_name)}</strong>: <span class="text-success fw-bold">${parseFloat(s.current_stock || 0).toFixed(2)}</span> ${esc(s.unit || '')}
                        </span>
                    `).join('');
                } else {
                    stockCount.textContent = '0 materials';
                    stockChips.innerHTML = '<span class="text-muted small">No live balance stock currently recorded for this site.</span>';
                }
            }

            syncSourceView();
        })
        .catch((err) => {
            console.error('loadSiteMaterialsData error:', err);
            document.getElementById('poLoadingSpinner').style.display = 'none';
            showToast('Network error while loading site data.', 'danger');
        });
    }

    function syncSourceView() {
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';
        selectedPOIds.clear();
        selectedTransferIds.clear();
        displayedItems = [];
        document.getElementById('selectedPOChipsRow').style.display = 'none';
        document.getElementById('poChipsContainer').innerHTML = '';
        document.getElementById('materialsSection').style.display = 'none';
        document.getElementById('muMaterialsBody').innerHTML = '';
        document.getElementById('poValidationFeedback').style.display = 'none';
        updateGrandTotals();

        if (currentSource === 'own_warehouse') {
            document.getElementById('supplierPoSection').style.display = 'none';
            document.getElementById('ownWarehouseSection').style.display = 'block';
            document.getElementById('materialsEmptyState').style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = 'Please choose an in-stock material from Central Warehouse above and click "Add Material".';
        } else if (currentSource === 'stock_transfer') {
            document.getElementById('ownWarehouseSection').style.display = 'none';
            document.getElementById('supplierPoSection').style.display = 'block';
            renderTransferCheckboxes();
        } else {
            document.getElementById('ownWarehouseSection').style.display = 'none';
            document.getElementById('supplierPoSection').style.display = 'block';
            renderPOCheckboxes();
        }
    }

    // Helper to get YYYY-MM-DD from PO or Transfer
    function getPoDateYmd(item) {
        if (!item) return '';
        if (item.order_date_raw) return item.order_date_raw;
        if (item.transfer_date_raw) return item.transfer_date_raw;
        const dStr = item.order_date || item.transfer_date;
        if (dStr && typeof dStr === 'string' && dStr.includes('-')) {
            const parts = dStr.trim().split('-');
            if (parts[0].length === 4) return dStr.trim();
            if (parts.length === 3 && parts[2].length === 4) {
                return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
            }
        }
        return '';
    }

    function formatDmy(ymdStr) {
        if (!ymdStr) return '';
        const parts = String(ymdStr).trim().split('-');
        if (parts.length === 3 && parts[0].length === 4) {
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }
        return ymdStr;
    }

    function getSelectedUsageDate() {
        return document.getElementById('usageDateField')?.value || '{{ date('Y-m-d') }}';
    }

    // ── Render PO Items inside Dropdown ───────────────────────
    function renderPOCheckboxes() {
        const poBtn = document.getElementById('poDropdownBtn');
        const emptyDiv = document.getElementById('poDropdownEmpty');
        const container = document.getElementById('poCheckboxContainer');
        const listDiv = document.getElementById('poCheckboxList');
        const feedbackDiv = document.getElementById('poDateMismatchFeedback');
        const usageDate = getSelectedUsageDate();

        listDiv.innerHTML = '';
        if (feedbackDiv) {
            feedbackDiv.style.display = 'none';
            feedbackDiv.innerHTML = '';
        }

        if (!sitePOs.length) {
            poBtn.disabled = true;
            document.getElementById('poDropdownText').textContent = 'No POs Available for Site';
            document.getElementById('poDropdownText').className = 'text-truncate small text-muted';
            emptyDiv.textContent = 'No active Purchase Orders found for this site.';
            emptyDiv.style.display = 'block';
            container.style.display = 'none';
            document.getElementById('materialsEmptyState').style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = 'No active Purchase Orders found for this site.';
            return;
        }

        // Filter: For the past Usage Date, Current date / future POs should NOT show and NOT selectable
        const eligiblePOs = sitePOs.filter(po => {
            const poYmd = getPoDateYmd(po);
            return !poYmd || !usageDate || poYmd <= usageDate;
        });
        const futureCount = sitePOs.length - eligiblePOs.length;

        if (!eligiblePOs.length) {
            poBtn.disabled = true;
            document.getElementById('poDropdownText').textContent = 'No POs Available on or before ' + formatDmy(usageDate);
            document.getElementById('poDropdownText').className = 'text-truncate small text-danger fw-semibold';
            emptyDiv.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>No Purchase Orders available on or before ${formatDmy(usageDate)}.</span>`;
            emptyDiv.style.display = 'block';
            container.style.display = 'none';

            if (feedbackDiv) {
                feedbackDiv.style.display = 'block';
                feedbackDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>No POs available on or before ${formatDmy(usageDate)}. (பயன்பாட்டு தேதிக்கு பிந்தைய கொள்முதல் ஆணைகளை பயன்படுத்த முடியாது.)`;
            }

            document.getElementById('materialsEmptyState').style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = `No active Purchase Orders found on or before ${formatDmy(usageDate)}.`;
            return;
        }

        poBtn.disabled = false;
        emptyDiv.style.display = 'none';
        container.style.display = 'block';
        updatePODropdownLabel();

        // Show error / warning notice below PO select if some POs are excluded due to past usage date
        if (futureCount > 0 && feedbackDiv) {
            feedbackDiv.style.display = 'block';
            feedbackDiv.innerHTML = `<i class="bi bi-exclamation-circle text-danger me-1"></i><span class="text-danger fw-semibold">POs dated after usage date are hidden. (பயன்பாட்டு தேதிக்கு பிந்தைய கொள்முதல் ஆணைகள் மறைக்கப்பட்டுள்ளன.)</span>`;
        }

        eligiblePOs.forEach(po => {
            const isChecked = selectedPOIds.has(po.id);
            const label = document.createElement('label');
            label.className = 'po-dropdown-item' + (isChecked ? ' checked' : '');
            label.htmlFor = `po_chk_${po.id}`;
            label.id = `po_item_wrap_${po.id}`;
            
            const sourceBadge = po.source_type === 'Own Warehouse'
                ? '<span class="badge bg-warning-subtle text-dark border" style="font-size:0.65rem;">Warehouse</span>'
                : '<span class="badge bg-primary-subtle text-primary border" style="font-size:0.65rem;">Supplier PO</span>';

            label.innerHTML = `
                <input type="checkbox" class="po-checkbox" value="${po.id}" id="po_chk_${po.id}" ${isChecked ? 'checked' : ''}>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-primary small">${esc(po.po_number)}</strong>
                        ${sourceBadge}
                    </div>
                    <div class="small text-dark fw-semibold text-truncate mb-1" style="max-width: 280px;" title="${esc(po.supplier_name || 'Central Warehouse')}">
                        ${esc(po.supplier_name || 'Central Warehouse')}
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.7rem;">
                        <span><i class="bi bi-calendar3 me-1"></i>${esc(po.order_date || '—')}</span>
                        <span class="badge bg-light text-secondary border">${po.items_count || 1} material(s)</span>
                    </div>
                </div>
            `;
            listDiv.appendChild(label);
        });

        listDiv.querySelectorAll('.po-checkbox').forEach(chk => {
            chk.addEventListener('change', function () {
                const poId = parseInt(this.value, 10);
                const wrap = document.getElementById(`po_item_wrap_${poId}`);
                if (this.checked) {
                    selectedPOIds.add(poId);
                    if (wrap) wrap.classList.add('checked');
                } else {
                    selectedPOIds.delete(poId);
                    if (wrap) wrap.classList.remove('checked');
                }
                syncPOSelections();
            });
        });

        document.getElementById('materialsEmptyState').style.display = selectedPOIds.size > 0 ? 'none' : 'block';
        document.getElementById('emptyStateMessage').textContent = `Found ${eligiblePOs.length} Purchase Order(s) eligible on or before ${formatDmy(usageDate)}. Please click "Select Purchase Order(s)" above to pick.`;
    }

    // ── Render Stock Transfer Items inside Dropdown ───────────
    function renderTransferCheckboxes() {
        const poBtn = document.getElementById('poDropdownBtn');
        const emptyDiv = document.getElementById('poDropdownEmpty');
        const container = document.getElementById('poCheckboxContainer');
        const listDiv = document.getElementById('poCheckboxList');
        const feedbackDiv = document.getElementById('poDateMismatchFeedback');
        const usageDate = getSelectedUsageDate();

        listDiv.innerHTML = '';
        if (feedbackDiv) {
            feedbackDiv.style.display = 'none';
            feedbackDiv.innerHTML = '';
        }

        if (!siteTransfers.length) {
            poBtn.disabled = true;
            document.getElementById('poDropdownText').textContent = 'No Transferred Stock Available';
            document.getElementById('poDropdownText').className = 'text-truncate small text-muted';
            emptyDiv.textContent = 'No stock transfers received for this site from other project sites.';
            emptyDiv.style.display = 'block';
            container.style.display = 'none';
            document.getElementById('materialsEmptyState').style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = 'No stock transfers received for this site from other project sites.';
            return;
        }

        // Filter: Transferred stock strictly on or before the selected Usage Date
        const eligibleTransfers = siteTransfers.filter(xfr => {
            const xfrYmd = getPoDateYmd(xfr);
            return !xfrYmd || !usageDate || xfrYmd <= usageDate;
        });
        const futureCount = siteTransfers.length - eligibleTransfers.length;

        if (!eligibleTransfers.length) {
            poBtn.disabled = true;
            document.getElementById('poDropdownText').textContent = 'No Transferred Stock on or before ' + formatDmy(usageDate);
            document.getElementById('poDropdownText').className = 'text-truncate small text-danger fw-semibold';
            emptyDiv.innerHTML = `<span class="text-danger fw-semibold"><i class="bi bi-x-circle me-1"></i>No stock transfers available on or before ${formatDmy(usageDate)}.</span>`;
            emptyDiv.style.display = 'block';
            container.style.display = 'none';

            if (feedbackDiv) {
                feedbackDiv.style.display = 'block';
                feedbackDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>No stock transfers available on or before ${formatDmy(usageDate)}. (பயன்பாட்டு தேதிக்கு பிந்தைய பரிமாற்றங்களை பயன்படுத்த முடியாது.)`;
            }

            document.getElementById('materialsEmptyState').style.display = 'block';
            document.getElementById('emptyStateMessage').textContent = `No stock transfers received on or before ${formatDmy(usageDate)}.`;
            return;
        }

        poBtn.disabled = false;
        emptyDiv.style.display = 'none';
        container.style.display = 'block';
        updatePODropdownLabel();

        if (futureCount > 0 && feedbackDiv) {
            feedbackDiv.style.display = 'block';
            feedbackDiv.innerHTML = `<i class="bi bi-exclamation-circle text-danger me-1"></i><span class="text-danger fw-semibold">Transfers dated after usage date are hidden. (பயன்பாட்டு தேதிக்கு பிந்தைய பரிமாற்றங்கள் மறைக்கப்பட்டுள்ளன.)</span>`;
        }

        eligibleTransfers.forEach(xfr => {
            const isChecked = selectedTransferIds.has(xfr.id);
            const label = document.createElement('label');
            label.className = 'po-dropdown-item' + (isChecked ? ' checked' : '');
            label.htmlFor = `xfr_chk_${xfr.id}`;
            label.id = `xfr_item_wrap_${xfr.id}`;
            
            const sourceBadge = '<span class="badge border" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;font-size:0.65rem;"><i class="bi bi-arrow-left-right me-1"></i>Stock Transfer</span>';

            label.innerHTML = `
                <input type="checkbox" class="transfer-checkbox" value="${xfr.id}" id="xfr_chk_${xfr.id}" ${isChecked ? 'checked' : ''}>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="small" style="color:#7e22ce;">${esc(xfr.transfer_no)}</strong>
                        ${sourceBadge}
                    </div>
                    <div class="small text-dark fw-semibold text-truncate mb-1" style="max-width: 280px;" title="From ${esc(xfr.source_site_name || 'Other Site')}">
                        From: ${esc(xfr.source_site_name || 'Other Site')}
                    </div>
                    <div class="d-flex justify-content-between align-items-center text-muted" style="font-size:0.7rem;">
                        <span><i class="bi bi-calendar3 me-1"></i>${esc(xfr.transfer_date || '—')}</span>
                        <span class="badge bg-light text-secondary border">${xfr.items_count || 1} material(s)</span>
                    </div>
                </div>
            `;
            listDiv.appendChild(label);
        });

        listDiv.querySelectorAll('.transfer-checkbox').forEach(chk => {
            chk.addEventListener('change', function () {
                const xfrId = parseInt(this.value, 10);
                const wrap = document.getElementById(`xfr_item_wrap_${xfrId}`);
                if (this.checked) {
                    selectedTransferIds.add(xfrId);
                    if (wrap) wrap.classList.add('checked');
                } else {
                    selectedTransferIds.delete(xfrId);
                    if (wrap) wrap.classList.remove('checked');
                }
                syncPOSelections();
            });
        });

        document.getElementById('materialsEmptyState').style.display = selectedTransferIds.size > 0 ? 'none' : 'block';
        document.getElementById('emptyStateMessage').textContent = `Found ${eligibleTransfers.length} Stock Transfer(s) eligible on or before ${formatDmy(usageDate)}. Please click "Select Purchase Order(s)" above to pick transferred materials.`;
    }

    // ── Select All / Clear All POs / Transfers ────────────────
    document.getElementById('selectAllPOsBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';
        if (currentSource === 'stock_transfer') {
            document.querySelectorAll('#poCheckboxList .transfer-checkbox').forEach(chk => {
                chk.checked = true;
                const xfrId = parseInt(chk.value, 10);
                selectedTransferIds.add(xfrId);
                const wrap = document.getElementById(`xfr_item_wrap_${xfrId}`);
                if (wrap) wrap.classList.add('checked');
            });
        } else {
            document.querySelectorAll('#poCheckboxList .po-checkbox').forEach(chk => {
                chk.checked = true;
                const poId = parseInt(chk.value, 10);
                selectedPOIds.add(poId);
                const wrap = document.getElementById(`po_item_wrap_${poId}`);
                if (wrap) wrap.classList.add('checked');
            });
        }
        syncPOSelections();
    });

    document.getElementById('clearAllPOsBtn').addEventListener('click', function (e) {
        e.stopPropagation();
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';
        if (currentSource === 'stock_transfer') {
            document.querySelectorAll('#poCheckboxList .transfer-checkbox').forEach(chk => {
                chk.checked = false;
                const xfrId = parseInt(chk.value, 10);
                const wrap = document.getElementById(`xfr_item_wrap_${xfrId}`);
                if (wrap) wrap.classList.remove('checked');
            });
            selectedTransferIds.clear();
        } else {
            document.querySelectorAll('#poCheckboxList .po-checkbox').forEach(chk => {
                chk.checked = false;
                const poId = parseInt(chk.value, 10);
                const wrap = document.getElementById(`po_item_wrap_${poId}`);
                if (wrap) wrap.classList.remove('checked');
            });
            selectedPOIds.clear();
        }
        syncPOSelections();
    });

    // ── When Usage Date Changes: auto-filter and deselect future POs ───
    document.getElementById('usageDateField').addEventListener('change', function () {
        const newUsageDate = this.value;
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';
        let deselectedCount = 0;

        if (currentSource === 'stock_transfer') {
            Array.from(selectedTransferIds).forEach(xfrId => {
                const xfr = siteTransfers.find(x => x.id === xfrId);
                const xfrYmd = xfr ? getPoDateYmd(xfr) : '';
                if (xfrYmd && newUsageDate && xfrYmd > newUsageDate) {
                    selectedTransferIds.delete(xfrId);
                    deselectedCount++;
                }
            });
            renderTransferCheckboxes();
        } else if (currentSource === 'third_party') {
            Array.from(selectedPOIds).forEach(poId => {
                const po = sitePOs.find(p => p.id === poId);
                const poYmd = po ? getPoDateYmd(po) : '';
                if (poYmd && newUsageDate && poYmd > newUsageDate) {
                    selectedPOIds.delete(poId);
                    deselectedCount++;
                }
            });
            renderPOCheckboxes();
        }

        syncPOSelections();

        if (deselectedCount > 0) {
            showToast('Items dated after the usage date were deselected.', 'warning');
        }
    });

    // ── Synchronize PO / Transfer selections & update Table ──
    function syncPOSelections() {
        updatePODropdownLabel();
        renderPOChips();
        rebuildMaterialsTableFromSelectedPOs();
    }

    // ── Update Dropdown Button Text ──────────────────────────
    function updatePODropdownLabel() {
        const textSpan = document.getElementById('poDropdownText');
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';

        if (currentSource === 'stock_transfer') {
            const count = selectedTransferIds.size;
            if (count === 0) {
                textSpan.textContent = '— Select Stock Transfer(s) —';
                textSpan.className = 'text-truncate small text-muted';
                document.getElementById('poValidationFeedback').style.display = 'none';
            } else if (count === 1) {
                const firstId = Array.from(selectedTransferIds)[0];
                const xfr = siteTransfers.find(x => x.id === firstId);
                textSpan.textContent = xfr ? `${xfr.transfer_no} (1 selected)` : '1 Transfer selected';
                textSpan.className = 'text-truncate small fw-bold text-purple';
                textSpan.style.color = '#7e22ce';
                document.getElementById('poValidationFeedback').style.display = 'none';
            } else {
                textSpan.textContent = `${count} Transfers Selected`;
                textSpan.className = 'text-truncate small fw-bold text-purple';
                textSpan.style.color = '#7e22ce';
                document.getElementById('poValidationFeedback').style.display = 'none';
            }
        } else {
            const count = selectedPOIds.size;
            if (count === 0) {
                textSpan.textContent = '— Select Purchase Order(s) —';
                textSpan.className = 'text-truncate small text-muted';
                document.getElementById('poValidationFeedback').style.display = 'none';
            } else if (count === 1) {
                const firstId = Array.from(selectedPOIds)[0];
                const po = sitePOs.find(p => p.id === firstId);
                textSpan.textContent = po ? `${po.po_number} (1 selected)` : '1 PO selected';
                textSpan.className = 'text-truncate small fw-bold text-primary';
                document.getElementById('poValidationFeedback').style.display = 'none';
            } else {
                textSpan.textContent = `${count} POs Selected`;
                textSpan.className = 'text-truncate small fw-bold text-primary';
                document.getElementById('poValidationFeedback').style.display = 'none';
            }
        }
    }

    // ── Render Selected Chips ────────────────────────────────
    function renderPOChips() {
        const chipsContainer = document.getElementById('poChipsContainer');
        const chipsRow = document.getElementById('selectedPOChipsRow');
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';
        chipsContainer.innerHTML = '';

        if (currentSource === 'stock_transfer') {
            if (selectedTransferIds.size === 0) {
                chipsRow.style.display = 'none';
                return;
            }
            chipsRow.style.display = 'block';
            selectedTransferIds.forEach(xfrId => {
                const xfr = siteTransfers.find(x => x.id === xfrId);
                if (!xfr) return;
                const chip = document.createElement('span');
                chip.className = 'po-chip';
                chip.style.borderColor = '#c084fc';
                chip.style.backgroundColor = '#faf5ff';
                chip.innerHTML = `
                    <i class="bi bi-arrow-left-right text-purple" style="color:#7e22ce;"></i> <span style="color:#7e22ce;">${esc(xfr.transfer_no)}</span>
                    <span class="btn-close-chip" title="Remove ${esc(xfr.transfer_no)}" data-id="${xfr.id}">&times;</span>
                `;
                chip.querySelector('.btn-close-chip').addEventListener('click', function () {
                    const removeId = parseInt(this.dataset.id, 10);
                    selectedTransferIds.delete(removeId);
                    const chk = document.getElementById(`xfr_chk_${removeId}`);
                    if (chk) chk.checked = false;
                    const wrap = document.getElementById(`xfr_item_wrap_${removeId}`);
                    if (wrap) wrap.classList.remove('checked');
                    syncPOSelections();
                });
                chipsContainer.appendChild(chip);
            });
        } else {
            if (selectedPOIds.size === 0) {
                chipsRow.style.display = 'none';
                return;
            }
            chipsRow.style.display = 'block';
            selectedPOIds.forEach(poId => {
                const po = sitePOs.find(p => p.id === poId);
                if (!po) return;
                const chip = document.createElement('span');
                chip.className = 'po-chip';
                chip.innerHTML = `
                    <i class="bi bi-file-earmark-check"></i> ${esc(po.po_number)}
                    <span class="btn-close-chip" title="Remove ${esc(po.po_number)}" data-id="${po.id}">&times;</span>
                `;
                chip.querySelector('.btn-close-chip').addEventListener('click', function () {
                    const removeId = parseInt(this.dataset.id, 10);
                    selectedPOIds.delete(removeId);
                    const chk = document.getElementById(`po_chk_${removeId}`);
                    if (chk) chk.checked = false;
                    const wrap = document.getElementById(`po_item_wrap_${removeId}`);
                    if (wrap) wrap.classList.remove('checked');
                    syncPOSelections();
                });
                chipsContainer.appendChild(chip);
            });
        }
    }

    // ── Rebuild Materials Table for all Selected POs / Transfers
    function rebuildMaterialsTableFromSelectedPOs() {
        const tbody = document.getElementById('muMaterialsBody');
        const emptyState = document.getElementById('materialsEmptyState');
        const section = document.getElementById('materialsSection');
        const currentSource = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';

        if (currentSource === 'stock_transfer') {
            if (selectedTransferIds.size === 0) {
                section.style.display = 'none';
                emptyState.style.display = 'block';
                emptyState.querySelector('#emptyStateMessage').textContent = 'Please check one or more Stock Transfers above to load transferred materials.';
                tbody.innerHTML = '';
                updateGrandTotals();
                document.getElementById('modalFooterHint').textContent = '';
                return;
            }
            displayedItems = siteAllMaterials.filter(item => (item.source_type === 'Stock Transfer' || item.stock_transfer_id) && selectedTransferIds.has(parseInt(item.stock_transfer_id, 10)));
        } else {
            if (selectedPOIds.size === 0) {
                section.style.display = 'none';
                emptyState.style.display = 'block';
                emptyState.querySelector('#emptyStateMessage').textContent = 'Please check one or more Purchase Orders above to add materials.';
                tbody.innerHTML = '';
                updateGrandTotals();
                document.getElementById('modalFooterHint').textContent = '';
                return;
            }
            displayedItems = siteAllMaterials.filter(item => item.source_type !== 'Stock Transfer' && selectedPOIds.has(parseInt(item.purchase_order_id, 10)));
        }

        emptyState.style.display = 'none';
        section.style.display = 'block';
        tbody.innerHTML = '';

        if (!displayedItems.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" class="text-center text-muted small py-4">
                        <i class="bi bi-box-seam d-block fs-3 mb-1 opacity-50"></i>
                        No materials found with available stock in the selected selection.
                    </td>
                </tr>`;
            updateGrandTotals();
            return;
        }

        displayedItems.forEach((item, idx) => {
            const inward    = parseFloat(item.inward_quantity    || 0);
            const used      = parseFloat(item.already_used       || 0);
            const avail     = parseFloat(item.available_quantity || 0);
            const unitPrice = parseFloat(item.unit_price         || 0);

            const availClass = avail <= 0 ? 'avail-nil' : 'avail-ok';
            const defaultQty = avail > 0 ? avail : 0;
            const defaultBal = Math.max(0, avail - defaultQty);

            let sourceBadgeHtml = '';
            if (item.source_type === 'Stock Transfer' || item.stock_transfer_id) {
                sourceBadgeHtml = `
                    <span class="badge border" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;font-size:0.75rem;">
                        <i class="bi bi-arrow-left-right me-1"></i>${esc(item.source_reference || item.po_number || 'Transfer')}
                    </span>
                    ${item.source_site_name ? `<div class="text-muted" style="font-size:0.7rem;">From: ${esc(item.source_site_name)}</div>` : ''}
                `;
            } else if (item.source_type === 'Own Warehouse') {
                sourceBadgeHtml = `<span class="po-badge source-wh">${esc(item.po_number || 'WH')}</span>`;
            } else {
                sourceBadgeHtml = `
                    <span class="po-badge source-po">${esc(item.po_number || 'PO')}</span>
                    ${item.supplier_name ? `<div class="text-muted" style="font-size:0.7rem;">${esc(item.supplier_name)}</div>` : ''}
                `;
            }

            const tr = document.createElement('tr');
            tr.dataset.rowId = idx;
            tr.innerHTML = `
                <td class="text-muted text-center row-num">${idx + 1}
                    <input type="hidden" name="items[${idx}][material_id]"            value="${item.material_id}">
                    <input type="hidden" name="items[${idx}][material_name]"           value="${esc(item.material_name)}">
                    <input type="hidden" name="items[${idx}][purchase_order_id]"       value="${item.purchase_order_id || ''}">
                    <input type="hidden" name="items[${idx}][purchase_order_item_id]"  value="${item.purchase_order_item_id || ''}">
                    <input type="hidden" name="items[${idx}][stock_transfer_id]"       value="${item.stock_transfer_id || ''}">
                    <input type="hidden" name="items[${idx}][stock_transfer_item_id]"  value="${item.stock_transfer_item_id || ''}">
                    <input type="hidden" name="items[${idx}][po_number]"               value="${esc(item.po_number || '')}">
                    <input type="hidden" name="items[${idx}][source_reference]"        value="${esc(item.source_reference || item.po_number || '')}">
                    <input type="hidden" name="items[${idx}][source_type]"             value="${esc(item.source_type || 'PO')}">
                    <input type="hidden" name="items[${idx}][inward_quantity]"         value="${inward}">
                    <input type="hidden" name="items[${idx}][unit]"                    value="${esc(item.unit || '')}">
                    <input type="hidden" name="items[${idx}][unit_id]"                 value="${item.unit_id || ''}">
                    <input type="hidden" name="items[${idx}][unit_price]"              value="${unitPrice}">
                </td>
                <td>
                    <div class="fw-semibold text-dark">${esc(item.material_name)}</div>
                    ${item.material_name_ta ? `<div class="text-muted small">${esc(item.material_name_ta)}</div>` : ''}
                </td>
                <td>${sourceBadgeHtml}</td>
                <td class="text-center font-monospace">${inward.toFixed(2)}</td>
                <td class="text-center text-muted font-monospace">${used > 0 ? used.toFixed(2) : '—'}</td>
                <td class="text-center">
                    <span class="avail-badge ${availClass}">${avail.toFixed(2)}</span>
                </td>
                <td class="text-center">
                    <input type="number"
                        class="form-control form-control-sm mu-qty-input text-center fw-bold"
                        name="items[${idx}][used_quantity]"
                        value="${defaultQty}"
                        min="0" max="${avail}" step="0.01"
                        data-max="${avail}"
                        data-unit-price="${unitPrice}"
                        data-row="${idx}"
                        ${avail <= 0 ? 'disabled title="Stock fully consumed"' : ''}>
                </td>
                <td class="text-center font-monospace" id="rowBalance_${idx}">
                    <span class="badge ${defaultBal > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border'}">
                        ${defaultBal.toFixed(2)}
                    </span>
                </td>
                <td class="text-center text-muted">${esc(item.unit || '—')}</td>
                <td class="text-end fw-semibold">₹${unitPrice.toFixed(2)}</td>
                <td class="text-end fw-bold text-success" id="rowTotal_${idx}">₹${(defaultQty * unitPrice).toFixed(2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-0 px-1 remove-mat-row-btn" title="Remove this material" data-row="${idx}">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        tbody.querySelectorAll('.mu-qty-input').forEach(inp => inp.addEventListener('input', onQtyChange));
        
        tbody.querySelectorAll('.remove-mat-row-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const tr = this.closest('tr');
                if (tr) {
                    tr.remove();
                    updateRowNumbersAndTotals();
                }
            });
        });

        const selectionCount = currentSource === 'stock_transfer' ? selectedTransferIds.size : selectedPOIds.size;
        const selectionTypeLabel = currentSource === 'stock_transfer' ? 'Transfer(s)' : 'PO(s)';
        document.getElementById('itemCountBadge').textContent = `${displayedItems.length} materials from ${selectionCount} ${selectionTypeLabel}`;
        document.getElementById('modalFooterHint').textContent = `${displayedItems.length} materials loaded across ${selectionCount} selected ${selectionTypeLabel}`;
        updateGrandTotals();
    }

    // ── Add Material from Own Warehouse ───────────────────────
    document.getElementById('addWarehouseItemBtn').addEventListener('click', function () {
        const picker = document.getElementById('warehouseItemPicker');
        const selectedOpt = picker.options[picker.selectedIndex];
        if (!picker.value || !selectedOpt) {
            showToast('Please select a material from Central Warehouse first.', 'warning');
            return;
        }

        const whId = parseInt(selectedOpt.dataset.id, 10);
        const sku = selectedOpt.dataset.sku || 'WH';
        const name = selectedOpt.dataset.name || 'Material';
        const tamil = selectedOpt.dataset.tamil || '';
        const stock = parseFloat(selectedOpt.dataset.stock) || 0;
        const unit = selectedOpt.dataset.unit || 'Nos';
        const unitId = selectedOpt.dataset.unitId || '';
        const rate = parseFloat(selectedOpt.dataset.rate) || 0;

        let existingRow = document.querySelector(`#muMaterialsBody tr[data-wh-id="${whId}"]`);
        if (!existingRow) {
            existingRow = Array.from(document.querySelectorAll('#muMaterialsBody tr')).find(r => {
                const rName = r.querySelector('input[name*="[material_name]"]')?.value;
                return rName && rName.trim().toLowerCase() === name.trim().toLowerCase();
            });
        }
        if (existingRow) {
            existingRow.classList.add('table-warning');
            setTimeout(() => existingRow.classList.remove('table-warning'), 1500);
            const qtyInp = existingRow.querySelector('.mu-qty-input');
            if (qtyInp) qtyInp.focus();
            showToast(`"${name}" is already in the list. Adjust its quantity if needed.`, 'warning');
            return;
        }

        document.getElementById('materialsEmptyState').style.display = 'none';
        document.getElementById('materialsSection').style.display = 'block';

        const tbody = document.getElementById('muMaterialsBody');
        const rowIdx = tbody.querySelectorAll('tr').length;
        const defaultQty = stock > 0 ? (stock >= 1 ? 1 : stock) : 0;
        const defaultBal = Math.max(0, stock - defaultQty);

        const tr = document.createElement('tr');
        tr.dataset.type = 'own_warehouse';
        tr.dataset.whId = whId;
        tr.dataset.rowId = rowIdx;
        tr.innerHTML = `
            <td class="text-muted text-center row-num">${rowIdx + 1}
                <input type="hidden" name="items[${rowIdx}][warehouse_item_id]" value="${whId}">
                <input type="hidden" name="items[${rowIdx}][material_id]" value="">
                <input type="hidden" name="items[${rowIdx}][material_name]" value="${esc(name)}">
                <input type="hidden" name="items[${rowIdx}][source_type]" value="Own Warehouse">
                <input type="hidden" name="items[${rowIdx}][source_reference]" value="${esc(sku)}">
                <input type="hidden" name="items[${rowIdx}][inward_quantity]" value="${stock}">
                <input type="hidden" name="items[${rowIdx}][unit]" value="${esc(unit)}">
                <input type="hidden" name="items[${rowIdx}][unit_id]" value="${unitId}">
                <input type="hidden" name="items[${rowIdx}][unit_price]" value="${rate}">
            </td>
            <td>
                <div class="fw-semibold text-dark">${esc(name)}</div>
                ${tamil ? `<div class="text-muted small">${esc(tamil)}</div>` : ''}
            </td>
            <td>
                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                    <i class="bi bi-boxes me-1"></i>Own Warehouse
                </span>
                <div class="small text-muted font-monospace" style="font-size:0.7rem;">${esc(sku)}</div>
            </td>
            <td class="text-center font-monospace">${stock.toFixed(2)}</td>
            <td class="text-center text-muted">—</td>
            <td class="text-center fw-semibold text-success font-monospace">${stock.toFixed(2)}</td>
            <td class="text-center">
                <input type="number"
                    class="form-control form-control-sm mu-qty-input text-center fw-bold"
                    name="items[${rowIdx}][used_quantity]"
                    value="${defaultQty}"
                    min="0.01" max="${stock}" step="any"
                    data-max="${stock}"
                    data-unit-price="${rate}"
                    data-row="${rowIdx}">
            </td>
            <td class="text-center font-monospace" id="rowBalance_${rowIdx}">
                <span class="badge ${defaultBal > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border'}">
                    ${defaultBal.toFixed(2)}
                </span>
            </td>
            <td class="text-center text-muted">${esc(unit)}</td>
            <td class="text-end fw-semibold">₹${rate.toFixed(2)}</td>
            <td class="text-end fw-bold text-success" id="rowTotal_${rowIdx}">₹${(defaultQty * rate).toFixed(2)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-0 px-1 remove-mat-row-btn" title="Remove this material" data-row="${rowIdx}">
                    <i class="bi bi-x"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);

        tr.querySelector('.mu-qty-input').addEventListener('input', onQtyChange);
        tr.querySelector('.remove-mat-row-btn').addEventListener('click', function () {
            tr.remove();
            updateRowNumbersAndTotals();
        });

        picker.selectedIndex = 0;
        updateRowNumbersAndTotals();
        showToast(`Added ${name} from Central Warehouse.`, 'success');
    });

    // ── Qty change handler ────────────────────────────────────
    function onQtyChange(e) {
        const inp   = e.target;
        let   val   = parseFloat(inp.value) || 0;
        const max   = parseFloat(inp.dataset.max) || 0;
        const price = parseFloat(inp.dataset.unitPrice) || 0;
        const idx   = inp.dataset.row;

        if (val < 0) { val = 0; inp.value = 0; }
        if (max > 0 && val > max) {
            inp.classList.add('is-invalid');
            inp.value = max;
            val = max;
        } else {
            inp.classList.remove('is-invalid');
        }

        const bal = Math.max(0, max - val);
        const balCell = document.getElementById(`rowBalance_${idx}`);
        if (balCell) {
            balCell.innerHTML = `<span class="badge ${bal > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border'}">${bal.toFixed(2)}</span>`;
        }

        const cell = document.getElementById(`rowTotal_${idx}`);
        if (cell) cell.textContent = `₹${(val * price).toFixed(2)}`;

        updateGrandTotals();
    }

    // ── Update row numbers & grand totals after row deletion ──
    function updateRowNumbersAndTotals() {
        const rows = document.querySelectorAll('#muMaterialsBody tr');
        rows.forEach((tr, i) => {
            const numCell = tr.querySelector('.row-num');
            if (numCell) numCell.childNodes[0].nodeValue = (i + 1) + ' ';
        });
        const badge = document.getElementById('itemCountBadge');
        if (badge) badge.textContent = `${rows.length} materials remaining`;
        updateGrandTotals();
    }

    function updateGrandTotals() {
        let qty = 0, price = 0;
        document.querySelectorAll('.mu-qty-input:not(:disabled)').forEach(inp => {
            const v = parseFloat(inp.value) || 0;
            const p = parseFloat(inp.dataset.unitPrice) || 0;
            qty   += v;
            price += v * p;
        });
        const qtyEl = document.getElementById('grandTotalQty');
        const priceEl = document.getElementById('grandTotalPrice');
        if (qtyEl) qtyEl.textContent   = qty.toFixed(2);
        if (priceEl) priceEl.textContent = `₹${price.toFixed(2)}`;
    }

    // ── Save Material Usage ───────────────────────────────────
    document.getElementById('saveUsageBtn').addEventListener('click', function () {
        const form       = document.getElementById('usageForm');
        const siteSelect = document.getElementById('siteSelect');
        const dateFld    = document.getElementById('usageDateField');

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.getElementById('poValidationFeedback').style.display = 'none';
        let valid = true;

        if (!siteSelect.value) { siteSelect.classList.add('is-invalid'); valid = false; }
        if (!dateFld.value)    { dateFld.classList.add('is-invalid');    valid = false; }

        const source = document.querySelector('input[name="material_source"]:checked')?.value || 'third_party';

        if (source === 'stock_transfer' && selectedTransferIds.size === 0) {
            document.getElementById('poValidationFeedback').textContent = 'Please select at least one Stock Transfer.';
            document.getElementById('poValidationFeedback').style.display = 'block';
            valid = false;
        } else if (source === 'third_party' && selectedPOIds.size === 0) {
            document.getElementById('poValidationFeedback').textContent = 'Please select at least one Purchase Order.';
            document.getElementById('poValidationFeedback').style.display = 'block';
            valid = false;
        }

        if (source === 'third_party' && selectedPOIds.size > 0) {
            const invalidDatePos = [];
            selectedPOIds.forEach(poId => {
                const po = sitePOs.find(p => p.id === poId);
                const poYmd = po ? getPoDateYmd(po) : '';
                if (poYmd && dateFld.value && poYmd > dateFld.value) {
                    invalidDatePos.push(po.po_number);
                }
            });
            if (invalidDatePos.length > 0) {
                const feedbackDiv = document.getElementById('poDateMismatchFeedback');
                if (feedbackDiv) {
                    feedbackDiv.style.display = 'block';
                    feedbackDiv.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>Purchase Order(s) ${invalidDatePos.join(', ')} were ordered after the Usage Date (${formatDmy(dateFld.value)}). Please select POs on or before the Usage Date.`;
                }
                showToast(`Selected PO(s) cannot be used for past Usage Date (${formatDmy(dateFld.value)}).`, 'danger');
                valid = false;
            }
        }

        const rows = document.querySelectorAll('#muMaterialsBody tr');
        if (rows.length === 0) {
            showToast(source === 'own_warehouse' 
                ? 'Please add at least one material from Central Warehouse.' 
                : (source === 'stock_transfer' ? 'Please select at least one Stock Transfer to load materials.' : 'Please select at least one Purchase Order to load materials.'), 'danger');
            valid = false;
        }

        if (!valid) {
            if (source === 'stock_transfer' && selectedTransferIds.size === 0) {
                showToast('Please select at least one Stock Transfer.', 'danger');
            } else if (source === 'third_party' && selectedPOIds.size === 0) {
                showToast('Please select at least one Purchase Order.', 'danger');
            } else if (!siteSelect.value || !dateFld.value) {
                showToast('Please select Project Site and Usage Date.', 'danger');
            }
            return;
        }

        const inputs = [...document.querySelectorAll('.mu-qty-input:not(:disabled)')];
        const hasQty = inputs.some(i => (parseFloat(i.value) || 0) > 0);

        if (!hasQty) {
            showToast('Enter used quantity greater than 0 for at least one material (குறைந்தது ஒரு பொருளுக்கு அளவு உள்ளிடவும்).', 'danger');
            return;
        }

        const formData = new FormData(form);

        if (source === 'stock_transfer') {
            Array.from(selectedTransferIds).forEach(xfrId => {
                formData.append('stock_transfer_ids[]', xfrId);
            });
            if (selectedTransferIds.size > 0) {
                formData.set('stock_transfer_id', Array.from(selectedTransferIds)[0]);
            }
        } else {
            Array.from(selectedPOIds).forEach(poId => {
                formData.append('purchase_order_ids[]', poId);
            });
            if (selectedPOIds.size > 0) {
                formData.set('purchase_order_id', Array.from(selectedPOIds)[0]);
            }
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

        fetch('{{ route('material-usages.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                Accept:             'application/json',
            },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Material Usage';

            if (data.success) {
                showToast(data.message || 'Material usage recorded successfully!', 'success');
                newModal.hide();
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(data.message || 'Failed to save material usage.', 'danger');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Material Usage';
            showToast('Network error — please try again.', 'danger');
        });
    });

    // ── View Details Modal ─────────────────────────────────────
    document.querySelectorAll('.view-usage-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const container = document.getElementById('viewUsageContent');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
            viewModal.show();

            fetch(`{{ url('/material-usages') }}/${id}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.usage) {
                    container.innerHTML = '<div class="alert alert-danger">Failed to load usage details.</div>';
                    return;
                }

                const u = data.usage;
                let rowsHtml = '';
                (u.items || []).forEach((it, idx) => {
                    const isWh = it.source_type === 'Own Warehouse';
                    const isXfr = it.source_type === 'Stock Transfer' || (it.source_reference && String(it.source_reference).startsWith('XFR'));
                    const srcBadge = isWh
                        ? `<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-boxes me-1"></i>Own Warehouse (${esc(it.source_reference || 'WH')})</span>`
                        : (isXfr
                            ? `<span class="badge border" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;"><i class="bi bi-arrow-left-right me-1"></i>${esc(it.source_reference || 'Transfer')}</span>`
                            : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-file-earmark-text me-1"></i>${esc(it.source_reference || 'PO')}</span>`);

                    rowsHtml += `
                        <tr>
                            <td class="text-center">${idx + 1}</td>
                            <td class="fw-semibold">${esc(it.material_name)}</td>
                            <td>${srcBadge}</td>
                            <td class="text-center font-monospace">${parseFloat(it.inward_quantity || 0).toFixed(2)}</td>
                            <td class="text-center fw-bold text-primary font-monospace">${parseFloat(it.used_quantity || 0).toFixed(2)}</td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    ${parseFloat(it.remaining_quantity || 0).toFixed(2)}
                                </span>
                            </td>
                            <td class="text-center text-muted">${esc(it.unit || '—')}</td>
                            <td class="text-end">₹${parseFloat(it.unit_price || 0).toFixed(2)}</td>
                            <td class="text-end fw-bold text-success">₹${parseFloat(it.total_price || 0).toFixed(2)}</td>
                        </tr>
                    `;
                });

                container.innerHTML = `
                    <div class="row g-2 mb-3 bg-light p-2 rounded border">
                        <div class="col-6 col-md-3">
                            <span class="text-muted small">Usage No:</span>
                            <div class="fw-bold">${esc(u.usage_no)}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small">Date:</span>
                            <div class="fw-semibold">${esc(u.usage_date)}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small">Project Site:</span>
                            <div class="fw-semibold">${esc(u.project_site?.name || '—')}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted small">Recorded By:</span>
                            <div class="fw-semibold">${esc(u.creator?.name || '—')}</div>
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-2 small text-uppercase text-muted">Materials Consumed (பயன்படுத்திய பொருட்கள்)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Material</th>
                                    <th>Source / Reference</th>
                                    <th class="text-center">Inward / Stock</th>
                                    <th class="text-center">Used Qty</th>
                                    <th class="text-center">Balance Stock (மீதமுள்ள இருப்பு)</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Price (₹)</th>
                                    <th class="text-end">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHtml}</tbody>
                            <tfoot class="table-secondary fw-semibold">
                                <tr>
                                    <td colspan="4" class="text-end">Total:</td>
                                    <td class="text-center text-primary fw-bold">${parseFloat(u.total_used_quantity || 0).toFixed(2)}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-end text-success fw-bold">₹${parseFloat(u.total_cost || 0).toFixed(2)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    ${u.remarks ? `<div class="mt-2 text-muted small"><strong>Remarks:</strong> ${esc(u.remarks)}</div>` : ''}
                `;
            })
            .catch(() => {
                container.innerHTML = '<div class="alert alert-danger">Network error loading details.</div>';
            });
        });
    });

    // ── Edit Usage ──────────────────────────────────────────────
    function recalcEditTotals() {
        let totalQty = 0;
        let totalCost = 0;
        document.querySelectorAll('#editMuMaterialsBody tr').forEach(row => {
            const qtyInput = row.querySelector('.edit-item-qty');
            const priceInput = row.querySelector('.edit-item-price');
            const totalSpan = row.querySelector('.edit-item-total');
            const balSpan = row.querySelector('.edit-row-bal');
            const inward = parseFloat(row.dataset.inward || 0);
            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
            const lineTotal = qty * price;
            totalQty += qty;
            totalCost += lineTotal;
            if (totalSpan) {
                totalSpan.textContent = '₹' + lineTotal.toFixed(2);
            }
            if (balSpan) {
                const bal = Math.max(0, inward - qty);
                balSpan.textContent = bal.toFixed(2);
            }
        });
        const totalQtyEl = document.getElementById('editMuTotalQty');
        const totalCostEl = document.getElementById('editMuTotalCost');
        if (totalQtyEl) totalQtyEl.textContent = totalQty.toFixed(2);
        if (totalCostEl) totalCostEl.textContent = '₹' + totalCost.toFixed(2);
    }

    function updateEditMuRowNumbers() {
        document.querySelectorAll('#editMuMaterialsBody tr').forEach((row, i) => {
            const numCell = row.querySelector('.edit-row-num');
            if (numCell) numCell.textContent = (i + 1);
        });
    }

    // Toggle Add Material inline box in edit modal
    const toggleEditUsageAddBtn = document.getElementById('toggleEditUsageAddMaterialBtn');
    const editUsageAddBox = document.getElementById('editUsageAddMaterialBox');
    const cancelAddUsageBtn = document.getElementById('cancelAddUsageMaterialBtn');

    if (toggleEditUsageAddBtn && editUsageAddBox) {
        toggleEditUsageAddBtn.addEventListener('click', function () {
            const isHidden = editUsageAddBox.style.display === 'none' || !editUsageAddBox.style.display;
            editUsageAddBox.style.display = isHidden ? 'block' : 'none';
            if (isHidden) {
                document.getElementById('editUsageMaterialPicker')?.focus();
            }
        });
    }

    if (cancelAddUsageBtn && editUsageAddBox) {
        cancelAddUsageBtn.addEventListener('click', function () {
            editUsageAddBox.style.display = 'none';
        });
    }

    // Load available materials into Edit Picker
    function loadEditUsageMaterialsPicker(siteId) {
        const picker = document.getElementById('editUsageMaterialPicker');
        if (!picker) return;

        picker.innerHTML = '<option value="">-- Loading available materials... --</option>';

        Promise.all([
            fetch(`{{ url('/material-usages/site-materials') }}/${siteId}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
            }).then(r => r.json()).catch(() => ({ data: { items: [] } })),
            fetch(`{{ url('/material-usages/warehouse-materials') }}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }
            }).then(r => r.json()).catch(() => ({ materials: [] }))
        ]).then(([siteRes, whRes]) => {
            const editUsageDate = document.getElementById('editUsageDateField')?.value || '';
            const allItems = (siteRes && siteRes.data && siteRes.data.items) ? siteRes.data.items : [];
            const poItems = allItems.filter(it => {
                if (it.source_type === 'Stock Transfer' || String(it.source_reference || '').startsWith('XFR')) return false;
                const itYmd = getPoDateYmd(it);
                return !itYmd || !editUsageDate || itYmd <= editUsageDate;
            });
            const transferItems = allItems.filter(it => {
                if (!(it.source_type === 'Stock Transfer' || it.stock_transfer_id || String(it.source_reference || '').startsWith('XFR'))) return false;
                const itYmd = getPoDateYmd(it);
                return !itYmd || !editUsageDate || itYmd <= editUsageDate;
            });
            const whItems = (whRes && whRes.materials) ? whRes.materials : [];

            let html = '<option value="">-- Choose Material to Add --</option>';

            if (poItems.length > 0) {
                html += '<optgroup label="Purchase Orders for this Site (தள கொள்முதல் ஆணைகள்)">';
                poItems.forEach(it => {
                    const avail = parseFloat(it.available_quantity || 0);
                    const price = parseFloat(it.unit_price || 0);
                    const optKey = `PO_${it.purchase_order_item_id || it.material_id}`;
                    html += `
                        <option value="${optKey}"
                            data-source-type="Third-Party Supplier"
                            data-source-ref="${esc(it.po_number || '')}"
                            data-po-item-id="${it.purchase_order_item_id || ''}"
                            data-mat-id="${it.material_id || ''}"
                            data-mat-name="${esc(it.material_name)}"
                            data-inward="${parseFloat(it.inward_quantity || 0)}"
                            data-avail="${avail}"
                            data-unit="${esc(it.unit || 'Nos')}"
                            data-unit-id="${it.unit_id || ''}"
                            data-price="${price}">
                            [${esc(it.po_number || 'PO')}] ${esc(it.material_name)} · Avail: ${avail.toFixed(2)} ${esc(it.unit || '')} @ ₹${price.toFixed(2)}
                        </option>
                    `;
                });
                html += '</optgroup>';
            }

            if (transferItems.length > 0) {
                html += '<optgroup label="Stock Transferred from Other Sites (தள பரிமாற்றம்)">';
                transferItems.forEach(it => {
                    const avail = parseFloat(it.available_quantity || 0);
                    const price = parseFloat(it.unit_price || 0);
                    const optKey = `XFR_${it.stock_transfer_item_id || it.material_id}`;
                    html += `
                        <option value="${optKey}"
                            data-source-type="Stock Transfer"
                            data-source-ref="${esc(it.source_reference || it.po_number || 'Transfer')}"
                            data-transfer-item-id="${it.stock_transfer_item_id || ''}"
                            data-mat-id="${it.material_id || ''}"
                            data-mat-name="${esc(it.material_name)}"
                            data-inward="${parseFloat(it.inward_quantity || 0)}"
                            data-avail="${avail}"
                            data-unit="${esc(it.unit || 'Nos')}"
                            data-unit-id="${it.unit_id || ''}"
                            data-price="${price}">
                            [${esc(it.source_reference || 'Transfer')}] ${esc(it.material_name)} · Avail: ${avail.toFixed(2)} ${esc(it.unit || '')} @ ₹${price.toFixed(2)}
                        </option>
                    `;
                });
                html += '</optgroup>';
            }

            if (whItems.length > 0) {
                html += '<optgroup label="Central Warehouse Stock (மைய கிடங்கு இருப்பு)">';
                whItems.forEach(wh => {
                    const stock = parseFloat(wh.current_stock || 0);
                    const rate = parseFloat(wh.cash_amount || wh.unit_rate || 0);
                    const optKey = `WH_${wh.id}`;
                    html += `
                        <option value="${optKey}"
                            data-source-type="Own Warehouse"
                            data-source-ref="${esc(wh.sku || '')}"
                            data-wh-id="${wh.id}"
                            data-mat-id="${wh.material_id || ''}"
                            data-mat-name="${esc(wh.name_en)}"
                            data-inward="${stock}"
                            data-avail="${stock}"
                            data-unit="${esc(wh.unit || 'Nos')}"
                            data-unit-id="${wh.unit_id || ''}"
                            data-price="${rate}">
                            [${esc(wh.sku || 'WH')}] ${esc(wh.name_en)} · Stock: ${stock.toFixed(2)} ${esc(wh.unit || 'Nos')} @ ₹${rate.toFixed(2)}
                        </option>
                    `;
                });
                html += '</optgroup>';
            }

            picker.innerHTML = html;
        });
    }

    // When material selected in picker, default quantity
    const editMatPicker = document.getElementById('editUsageMaterialPicker');
    if (editMatPicker) {
        editMatPicker.addEventListener('change', function () {
            const opt = this.selectedOptions[0];
            const qtyInput = document.getElementById('editUsageNewItemQty');
            if (opt && opt.value) {
                const avail = parseFloat(opt.dataset.avail || 0);
                if (qtyInput) qtyInput.value = avail > 0 ? (avail >= 1 ? 1 : avail) : 1;
            }
        });
    }

    // Confirm Add Material in Edit Modal
    const confirmAddUsageBtn = document.getElementById('confirmAddUsageMaterialBtn');
    if (confirmAddUsageBtn) {
        confirmAddUsageBtn.addEventListener('click', function () {
            const picker = document.getElementById('editUsageMaterialPicker');
            const opt = picker?.selectedOptions[0];
            if (!opt || !opt.value) {
                showToast('Please select a material from the dropdown first.', 'warning');
                return;
            }

            const qtyInput = document.getElementById('editUsageNewItemQty');
            const qty = parseFloat(qtyInput?.value || 0);
            if (isNaN(qty) || qty <= 0) {
                showToast('Please enter a quantity greater than zero.', 'warning');
                qtyInput?.focus();
                return;
            }

            const srcType = opt.dataset.sourceType;
            const srcRef = opt.dataset.sourceRef || '';
            const poItemId = opt.dataset.poItemId || '';
            const transferItemId = opt.dataset.transferItemId || '';
            const whId = opt.dataset.whId || '';
            const matId = opt.dataset.matId || '';
            const matName = opt.dataset.matName;
            const inward = parseFloat(opt.dataset.inward || opt.dataset.avail || qty);
            const unit = opt.dataset.unit || 'Nos';
            const unitId = opt.dataset.unitId || '';
            const price = parseFloat(opt.dataset.price || 0);

            // ── DUPLICATE CHECK: Verify same material is not added multiple times ──
            const existingRow = Array.from(document.querySelectorAll('#editMuMaterialsBody tr')).find(r => {
                if (poItemId && r.dataset.poItemId && r.dataset.poItemId === String(poItemId)) return true;
                if (transferItemId && r.dataset.transferItemId && r.dataset.transferItemId === String(transferItemId)) return true;
                if (whId && r.dataset.whId && r.dataset.whId === String(whId)) return true;
                if (matId && r.dataset.matId && String(r.dataset.matId) === String(matId) && r.dataset.sourceRef === srcRef) return true;
                if (r.dataset.matName && r.dataset.matName.trim().toLowerCase() === matName.trim().toLowerCase() && r.dataset.sourceType === srcType) return true;
                return false;
            });

            if (existingRow) {
                existingRow.classList.add('table-primary');
                setTimeout(() => existingRow.classList.remove('table-primary'), 1200);
                const existingQtyInp = existingRow.querySelector('.edit-item-qty');
                if (existingQtyInp) existingQtyInp.focus();
                showToast(`"${matName}" is already in the list. Adjust its quantity if needed.`, 'info');
                return;
            }

            // Append new row
            const tbody = document.getElementById('editMuMaterialsBody');
            const rowCount = tbody.querySelectorAll('tr').length;
            const isWh = srcType === 'Own Warehouse';
            const isXfr = srcType === 'Stock Transfer' || String(srcRef).startsWith('XFR');
            const sourceBadge = isWh
                ? '<span class="badge bg-info-subtle text-info border">Own Warehouse</span>'
                : (isXfr
                    ? `<span class="badge border" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;"><i class="bi bi-arrow-left-right me-1"></i>${esc(srcRef || 'Transfer')}</span>`
                    : `<span class="badge bg-light text-secondary border">${esc(srcRef || 'PO')}</span>`);

            const bal = Math.max(0, inward - qty);

            const tr = document.createElement('tr');
            tr.setAttribute('data-item-id', '');
            tr.setAttribute('data-mat-id', matId);
            tr.setAttribute('data-po-item-id', poItemId);
            tr.setAttribute('data-transfer-item-id', transferItemId);
            tr.setAttribute('data-wh-id', whId);
            tr.setAttribute('data-mat-name', matName);
            tr.setAttribute('data-source-type', srcType);
            tr.setAttribute('data-source-ref', srcRef);
            tr.setAttribute('data-inward', inward);
            tr.setAttribute('data-unit', unit);
            tr.setAttribute('data-unit-id', unitId);

            tr.innerHTML = `
                <td class="text-center text-muted small edit-row-num">${rowCount + 1}</td>
                <td>
                    <div class="fw-semibold text-dark">${esc(matName)}</div>
                </td>
                <td>${sourceBadge}</td>
                <td class="text-center text-muted small font-monospace">${inward.toFixed(2)}</td>
                <td>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-center fw-semibold edit-item-qty" value="${qty}" required>
                </td>
                <td class="text-center font-monospace small">
                    <span class="badge bg-success-subtle text-success border border-success-subtle edit-row-bal">${bal.toFixed(2)}</span>
                </td>
                <td class="text-center text-muted small">${esc(unit)}</td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">₹</span>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end edit-item-price" value="${price.toFixed(2)}" required>
                    </div>
                </td>
                <td class="text-end fw-semibold text-success edit-item-total">
                    ₹${(qty * price).toFixed(2)}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm p-0 remove-edit-usage-item" style="width:26px; height:26px;" title="Remove this material">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);

            tr.querySelectorAll('.edit-item-qty, .edit-item-price').forEach(input => {
                input.addEventListener('input', recalcEditTotals);
            });

            updateEditMuRowNumbers();
            recalcEditTotals();

            // Reset and close inline box
            picker.value = '';
            if (qtyInput) qtyInput.value = '';
            if (editUsageAddBox) editUsageAddBox.style.display = 'none';

            showToast(`Added "${matName}" to material list.`, 'success');
        });
    }

    // Row removal delegation in Edit Modal
    document.getElementById('editMuMaterialsBody')?.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.remove-edit-usage-item');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            if (row) {
                row.remove();
                updateEditMuRowNumbers();
                recalcEditTotals();
                // Reload picker so removed material becomes available again
                if (window._editUsageSiteId) {
                    loadEditUsageMaterialsPicker(window._editUsageSiteId);
                }
            }
        }
    });

    document.querySelectorAll('.edit-usage-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const tbody = document.getElementById('editMuMaterialsBody');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading usage details...</td></tr>';
            
            if (editUsageAddBox) editUsageAddBox.style.display = 'none';
            editModal.show();

            fetch(`{{ url('/material-usages') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
            .then(r => r.json())
            .then(res => {
                if (!res.success || !res.usage) {
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-3 text-danger">Failed to load material usage details.</td></tr>';
                    return;
                }
                const u = res.usage;
                document.getElementById('editUsageId').value = u.id;
                document.getElementById('editUsageNoTitle').textContent = u.usage_no || ('MU-' + u.id);
                document.getElementById('editUsageNoField').value = u.usage_no || ('MU-' + u.id);
                document.getElementById('editUsageDateField').value = u.usage_date;
                document.getElementById('editUsageSiteField').value = u.project_site ? u.project_site.name : '—';
                document.getElementById('editUsageRemarks').value = u.remarks || '';

                // Load available materials for this site in the Add picker
                if (u.project_site_id) {
                    window._editUsageSiteId = u.project_site_id;
                    loadEditUsageMaterialsPicker(u.project_site_id);
                }

                let rowsHtml = '';
                (u.items || []).forEach((it, idx) => {
                    const isWh = it.source_type === 'Own Warehouse';
                    const isXfr = it.source_type === 'Stock Transfer' || (it.source_reference && String(it.source_reference).startsWith('XFR'));
                    const sourceLabel = isWh 
                        ? '<span class="badge bg-info-subtle text-info border">Own Warehouse</span>' 
                        : (isXfr 
                            ? `<span class="badge border" style="background:#f3e8ff;color:#7e22ce;border-color:#e9d5ff!important;"><i class="bi bi-arrow-left-right me-1"></i>${esc(it.source_reference || 'Transfer')}</span>`
                            : `<span class="badge bg-light text-secondary border">${esc(it.source_reference || 'PO')}</span>`);

                    const inQty = parseFloat(it.inward_quantity || 0);
                    const uQty = parseFloat(it.used_quantity || 0);
                    const bal = Math.max(0, inQty - uQty);

                    rowsHtml += `
                        <tr data-item-id="${it.id}"
                            data-mat-id="${it.material_id || ''}"
                            data-po-item-id="${it.purchase_order_item_id || ''}"
                            data-wh-id="${it.warehouse_item_id || ''}"
                            data-mat-name="${esc(it.material_name)}"
                            data-source-type="${esc(it.source_type)}"
                            data-source-ref="${esc(it.source_reference || '')}"
                            data-inward="${inQty}"
                            data-unit="${esc(it.unit || '')}"
                            data-unit-id="${it.unit_id || ''}">
                            <td class="text-center text-muted small edit-row-num">${idx + 1}</td>
                            <td>
                                <div class="fw-semibold text-dark">${esc(it.material_name)}</div>
                            </td>
                            <td>${sourceLabel}</td>
                            <td class="text-center text-muted small font-monospace">${inQty.toFixed(2)}</td>
                            <td>
                                <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-center fw-semibold edit-item-qty" value="${uQty}" required>
                            </td>
                            <td class="text-center font-monospace small">
                                <span class="badge bg-success-subtle text-success border border-success-subtle edit-row-bal">${bal.toFixed(2)}</span>
                            </td>
                            <td class="text-center text-muted small">${esc(it.unit || '')}</td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end edit-item-price" value="${parseFloat(it.unit_price || 0).toFixed(2)}" required>
                                </div>
                            </td>
                            <td class="text-end fw-semibold text-success edit-item-total">
                                ₹${parseFloat(it.total_price || 0).toFixed(2)}
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm p-0 remove-edit-usage-item" style="width:26px; height:26px;" title="Remove this material">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });

                tbody.innerHTML = rowsHtml;

                // Bind input listeners for recalculation
                tbody.querySelectorAll('.edit-item-qty, .edit-item-price').forEach(input => {
                    input.addEventListener('input', recalcEditTotals);
                });

                recalcEditTotals();
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-3 text-danger">Network error loading details.</td></tr>';
            });
        });
    });

    document.getElementById('editUsageDateField')?.addEventListener('change', function () {
        if (window._editUsageSiteId) {
            loadEditUsageMaterialsPicker(window._editUsageSiteId);
        }
    });

    const saveEditUsageBtn = document.getElementById('saveEditUsageBtn');
    if (saveEditUsageBtn) {
        saveEditUsageBtn.addEventListener('click', function () {
            const usageId = document.getElementById('editUsageId').value;
            const usageDate = document.getElementById('editUsageDateField').value;
            const remarks = document.getElementById('editUsageRemarks').value;

            if (!usageDate) {
                showToast('Please select a valid date.', 'danger');
                return;
            }

            const items = [];
            let valid = true;
            document.querySelectorAll('#editMuMaterialsBody tr').forEach(row => {
                const itemId = row.getAttribute('data-item-id');
                const qtyInput = row.querySelector('.edit-item-qty');
                const priceInput = row.querySelector('.edit-item-price');
                const qty = parseFloat(qtyInput ? qtyInput.value : 0);
                const price = parseFloat(priceInput ? priceInput.value : 0);

                if (isNaN(qty) || qty <= 0) {
                    valid = false;
                    if (qtyInput) qtyInput.classList.add('is-invalid');
                } else if (qtyInput) {
                    qtyInput.classList.remove('is-invalid');
                }

                if (isNaN(price) || price < 0) {
                    valid = false;
                    if (priceInput) priceInput.classList.add('is-invalid');
                } else if (priceInput) {
                    priceInput.classList.remove('is-invalid');
                }

                const itemPayload = {
                    used_quantity: qty,
                    unit_price: price,
                };

                if (itemId && parseInt(itemId) > 0) {
                    itemPayload.id = parseInt(itemId);
                } else {
                    itemPayload.material_id = row.dataset.matId ? parseInt(row.dataset.matId) : null;
                    itemPayload.purchase_order_item_id = row.dataset.poItemId ? parseInt(row.dataset.poItemId) : null;
                    itemPayload.stock_transfer_item_id = row.dataset.transferItemId ? parseInt(row.dataset.transferItemId) : null;
                    itemPayload.warehouse_item_id = row.dataset.whId ? parseInt(row.dataset.whId) : null;
                    itemPayload.material_name = row.dataset.matName;
                    itemPayload.source_type = row.dataset.sourceType;
                    itemPayload.source_reference = row.dataset.sourceRef;
                    itemPayload.inward_quantity = parseFloat(row.dataset.inward) || qty;
                    itemPayload.unit = row.dataset.unit;
                    itemPayload.unit_id = row.dataset.unitId ? parseInt(row.dataset.unitId) : null;
                }

                items.push(itemPayload);
            });

            if (!valid || items.length === 0) {
                showToast('Please ensure all items have valid quantities (> 0) and prices (>= 0).', 'danger');
                return;
            }

            saveEditUsageBtn.disabled = true;
            saveEditUsageBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Updating...';

            fetch(`{{ url('/material-usages') }}/${usageId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    usage_date: usageDate,
                    remarks: remarks,
                    items: items,
                }),
            })
            .then(r => r.json())
            .then(data => {
                saveEditUsageBtn.disabled = false;
                saveEditUsageBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Material Usage';

                if (data.success) {
                    editModal.hide();
                    showToast(data.message || 'Material usage updated successfully.', 'success');
                    setTimeout(() => location.reload(), 700);
                } else {
                    showToast(data.message || 'Failed to update material usage.', 'danger');
                }
            })
            .catch(() => {
                saveEditUsageBtn.disabled = false;
                saveEditUsageBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Update Material Usage';
                showToast('Network error while updating material usage.', 'danger');
            });
        });
    }

    // ── Delete ─────────────────────────────────────────────────
    document.querySelectorAll('.delete-usage-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const no = this.dataset.no;
            const doDelete = () => {
                fetch(`{{ url('/material-usages') }}/${id}/delete`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept:             'application/json',
                    },
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Deleted successfully.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Delete failed.', 'danger');
                    }
                })
                .catch(() => showToast('Network error.', 'danger'));
            };

            if (typeof window.confirmDelete === 'function') {
                window.confirmDelete(`Delete usage voucher "${no}"? This will restore material stocks.`, doDelete);
            } else if (confirm(`Delete "${no}"? This will restore material stocks.`)) {
                doDelete();
            }
        });
    });

    // ── Table Pagination & Client-Side Filtering ───────────────
    let currentPage = 1;
    let perPage = parseInt($('#perPageSelect').val()) || 10;

    function getMatchingRows() {
        const q = ($('#usageSearchInput').val() || '').toLowerCase().trim();
        const site = $('#usageSiteFilter').val() || '';
        const start = $('#usageStartDateFilter').val() || '';
        const end = $('#usageEndDateFilter').val() || '';

        const matching = [];
        $('#usagesTableBody tr.usage-row').each(function () {
            const row = $(this);
            const searchVal = String(row.data('search') || '');
            const rowSite = String(row.data('site-id') || '');
            const rowDate = String(row.data('date') || '');

            const matchesQuery = !q || searchVal.includes(q);
            const matchesSite = !site || rowSite === site;
            const matchesStart = !start || (rowDate && rowDate >= start);
            const matchesEnd = !end || (rowDate && rowDate <= end);

            if (matchesQuery && matchesSite && matchesStart && matchesEnd) {
                matching.push(this);
            }
        });
        return matching;
    }

    function updatePagination() {
        const matchingRows = getMatchingRows();
        const totalCount = $('#usagesTableBody tr.usage-row').length;
        const filteredCount = matchingRows.length;
        const totalPages = Math.ceil(filteredCount / perPage) || 1;

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        // Hide all usage data rows
        $('#usagesTableBody tr.usage-row').hide();

        if (totalCount === 0) {
            $('#noResultsRow').addClass('d-none');
            $('#tablePaginationContainer').addClass('d-none');
            return;
        }

        if (filteredCount === 0) {
            $('#noResultsRow').removeClass('d-none');
            $('#tablePaginationContainer').addClass('d-none');
            return;
        } else {
            $('#noResultsRow').addClass('d-none');
            $('#tablePaginationContainer').removeClass('d-none');
        }

        const startIndex = (currentPage - 1) * perPage;
        const endIndex = Math.min(startIndex + perPage, filteredCount);

        for (let i = startIndex; i < endIndex; i++) {
            $(matchingRows[i]).show();
        }

        // Re-index serial cell across all matching rows
        matchingRows.forEach(function (row, idx) {
            $(row).find('.serial-cell').text(String(idx + 1).padStart(2, '0'));
        });

        let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
        if (filteredCount !== totalCount) {
            summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
        }
        summaryHtml += ' material usages';
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

    $('#usageSearchInput, #usageSiteFilter, #usageStartDateFilter, #usageEndDateFilter').on('input change', function () {
        currentPage = 1;
        updatePagination();
    });

    $('#usageFilterForm').on('submit', function (e) {
        e.preventDefault();
        currentPage = 1;
        updatePagination();
    });

    $('#usageFilterResetBtn').on('click', function (e) {
        if (!window.location.search) {
            e.preventDefault();
            $('#usageSearchInput').val('');
            $('#usageSiteFilter').val('');
            $('#usageStartDateFilter').val('');
            $('#usageEndDateFilter').val('');
            currentPage = 1;
            updatePagination();
        }
    });

    // Run initial pagination
    updatePagination();

    // ── Utility ────────────────────────────────────────────────
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = String(str || '');
        return d.innerHTML;
    }

}());
</script>
@endpush
