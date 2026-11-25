@extends('layouts.app')

@section('title', 'Stock Transfer ')
@section('page_title', 'Stock Transfer ')

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

        .transfer-arrow-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
        }
        .site-chip {
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 600;
        }
        .site-from {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .site-to {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-2 px-md-4 py-3">

        <!-- Top Header & Actions -->
        <div class="row align-items-center mb-3 g-2">
            <div class="col-12 col-md-6">
                <h4 class="fw-bold mb-1 text-dark">
                    <i class="bi bi-arrow-left-right text-primary me-2"></i>Inter-Site Stock Transfer
</h4>
                <p class="text-muted small mb-0">
                    Transfer materials between sites (e.g. 100 bags at Site A &rarr; 10 bags to Site B) with real-time stock balance tracking.
                </p>
</div>
            <div class="col-12 col-md-6 text-md-end">
                <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" id="newTransferBtn">
                    <i class="bi bi-plus-circle me-1"></i> New Stock Transfer
</button>
                <a href="{{ route('reports.site.stock-transfers') }}" class="btn btn-outline-secondary btn-sm px-3 ms-1">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Transfer Report
</a>
</div>
</div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Transfers</span>
                        <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                            <i class="bi bi-box-arrow-right"></i>
</div>
</div>
                    <h4 class="fw-bold mb-0 text-dark">{{ (int) ($stats['total_transfers_count'] ?? 0) }}</h4>
                    <div class="small text-muted mt-1">Total: <strong class="text-dark">{{ number_format((float) ($stats['total_transferred_qty'] ?? 0), 2) }}</strong> items</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Transfer Value</span>
                        <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                            <i class="bi bi-currency-rupee"></i>
</div>
</div>
                    <h4 class="fw-bold mb-0 text-success">₹{{ number_format((float) ($stats['total_transfer_value'] ?? 0), 2) }}</h4>
                    <div class="small text-muted mt-1">Estimated transfer worth</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Active Sites</span>
                        <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-buildings"></i>
</div>
</div>
                    <h4 class="fw-bold mb-0 text-warning">{{ (int) ($stats['sites_count'] ?? 0) }}</h4>
                    <div class="small text-muted mt-1">Sites exchanging material</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Completed Transfers</span>
                        <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                            <i class="bi bi-check2-all"></i>
</div>
</div>
                    <h4 class="fw-bold mb-0 text-info">{{ (int) ($stats['completed_transfers_count'] ?? ($stats['total_transfers_count'] ?? 0)) }}</h4>
                    <div class="small text-muted mt-1">Successfully transferred</div>
</div>
</div>
</div>

        <!-- Filter & Search Card -->
        <div class="card-custom p-3 mb-3 bg-white">
            <form method="GET" action="{{ route('stock-transfers.index') }}" id="filterForm">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" id="filterSearch"
                                placeholder="Search by transfer #, vehicle, driver..." value="{{ $filters['search'] ?? '' }}">
</div>
</div>
                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" name="site_id" id="filterSite">
                            <option value="">-- All Sites --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}" {{ ($filters['site_id'] ?? '') == $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}@if(!empty($site->location)) ({{ $site->location }})@endif
</option>
                            @endforeach
                        </select>
</div>
                    <div class="col-6 col-md-3">
                        <select class="form-select form-select-sm" name="material_id" id="filterMaterial">
                            <option value="">-- All Materials --</option>
                            @foreach ($materials as $mat)
                                <option value="{{ $mat->id }}" {{ ($filters['material_id'] ?? '') == $mat->id ? 'selected' : '' }}>
                                    {{ $mat->name_en }} {{ $mat->name_ta ? "({$mat->name_ta})" : '' }}
</option>
                            @endforeach
                        </select>
</div>
                    <div class="col-12 col-md-2 text-md-end">
                        <button type="submit" class="btn btn-dark btn-sm px-3">
                            <i class="bi bi-funnel me-1"></i> Filter
</button>
                        <a href="{{ route('stock-transfers.index') }}" id="filterResetBtn" class="btn btn-outline-secondary btn-sm px-2 ms-1" title="Reset Filters">
                            <i class="bi bi-arrow-clockwise"></i> Reset
</a>
</div>
</div>
            </form>
</div>

        <!-- Transfers Table Card -->
        <div class="card-custom bg-white p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="transfersTable">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase fw-bold" style="font-size:0.75rem;">
                            <th class="ps-3" style="width: 40px;">#</th>
                            <th style="width: 140px;">Transfer # &amp; Date</th>
                            <th style="min-width: 220px;">Movement</th>
                            <th style="min-width: 180px;">Material &amp; Unit</th>
                            <th class="text-center" style="width: 110px;">Quantity</th>
                            <th style="min-width: 140px;">Vehicle &amp; Ref</th>
                            <th style="min-width: 120px;">Created By</th>
                            <th class="text-end pe-3" style="width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="transfersTableBody">
                        @forelse ($transfers as $tr)
                            @php
                                $trSearchText = strtolower(implode(' ', array_filter([
                                    $tr->transfer_no,
                                    $tr->transfer_date?->format('d M Y'),
                                    $tr->transfer_date?->format('Y-m-d'),
                                    $tr->fromSite?->site_name,
                                    $tr->toSite?->site_name,
                                    $tr->material?->name_en,
                                    $tr->material?->name_ta,
                                    $tr->vehicle_no,
                                    $tr->driver_name,
                                    $tr->challan_no,
                                    $tr->creator?->name,
                                    $tr->remarks,
                                    $tr->items->pluck('material_name')->implode(' '),
                                    $tr->items->pluck('source_reference')->implode(' '),
                                ])));
                                $allMatIds = array_filter(array_merge([$tr->material_id], $tr->items->pluck('material_id')->toArray()));
                            @endphp
                            <tr class="transfer-row" data-id="{{ $tr->id }}" data-json='@json($tr)'
                                data-search="{{ $trSearchText }}"
                                data-from-site="{{ $tr->from_project_site_id }}"
                                data-to-site="{{ $tr->to_project_site_id }}"
                                data-material-ids="{{ implode(',', $allMatIds) }}"
                                data-date="{{ $tr->transfer_date?->format('Y-m-d') ?? '' }}">
                                <td class="ps-3 text-muted fw-semibold serial-cell">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="fw-bold text-dark font-monospace" style="font-size:0.85rem;">{{ $tr->transfer_no }}</div>
                                    <div class="text-muted small" style="font-size:0.75rem;">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $tr->transfer_date?->format('d M Y') }}
</div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size:0.68rem;">
                                        <i class="bi bi-box-arrow-right me-1"></i>TRANSFER
</span>
                                </td>
                                <td>
                                    <div class="transfer-arrow-badge">
                                        <span class="site-chip site-from" title="Source Site ">
                                            {{ $tr->fromSite?->site_name ?? 'Site #'.$tr->from_project_site_id }}
</span>
                                        <i class="bi bi-arrow-right text-muted fw-bold"></i>
                                        <span class="site-chip site-to" title="Destination Site ">
                                            {{ $tr->toSite?->site_name ?? 'Site #'.$tr->to_project_site_id }}
</span>
</div>
                                    @if ($tr->parentTransfer)
                                        <div class="small text-muted mt-1" style="font-size:0.72rem;">
                                            <i class="bi bi-link-45deg me-1"></i>Against: <span class="fw-semibold">{{ $tr->parentTransfer->transfer_no }}</span>
</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($tr->items->count() > 1)
                                        <div class="fw-bold text-dark">
                                            <i class="bi bi-boxes text-primary me-1"></i>{{ $tr->items->count() }} Materials
</div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            @foreach ($tr->items->take(2) as $it)
                                                <span class="badge bg-light text-dark border py-1" style="font-size:0.7rem;">
                                                    {{ $it->material_name }} ({{ (float) $it->quantity }} {{ $it->unit }})
</span>
                                            @endforeach
                                            @if ($tr->items->count() > 2)
                                                <span class="badge bg-secondary-subtle text-secondary py-1" style="font-size:0.7rem;">+{{ $tr->items->count() - 2 }} more</span>
                                            @endif
</div>
                                    @elseif ($tr->items->count() === 1)
                                        @php $singleItem = $tr->items->first(); @endphp
                                        <div class="fw-semibold text-dark">{{ $singleItem->material_name }}</div>
                                        <div class="small text-muted mt-1" style="font-size:0.72rem;">
                                            <span class="badge {{ $singleItem->source_type === 'Own Warehouse' ? 'bg-info-subtle text-info border-info-subtle' : 'bg-primary-subtle text-primary border-primary-subtle' }} border py-0" style="font-size:0.68rem;">{{ $singleItem->source_reference ?: $singleItem->source_type }}</span>
</div>
                                    @else
                                        <div class="fw-semibold text-dark">{{ $tr->material?->name_en ?? 'Material' }}</div>
                                        @if ($tr->material?->name_ta)
                                            <small class="text-muted ta-text" style="font-size:0.75rem;">{{ $tr->material->name_ta }}</small>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold fs-6 text-dark">{{ number_format((float) $tr->quantity, 2) }}</div>
                                    <div class="text-muted small" style="font-size:0.72rem;">{{ $tr->unit ?? 'Items' }}</div>
                                    @if ((float) $tr->total_amount > 0)
                                        <div class="small text-primary fw-semibold" style="font-size:0.72rem;">₹{{ number_format((float) $tr->total_amount, 2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($tr->vehicle_no)
                                        <div class="small text-dark fw-semibold"><i class="bi bi-truck me-1 text-muted"></i>{{ $tr->vehicle_no }}</div>
                                    @endif
                                    @if ($tr->driver_name)
                                        <div class="small text-muted" style="font-size:0.75rem;"><i class="bi bi-person me-1"></i>{{ $tr->driver_name }}</div>
                                    @endif
                                    @if ($tr->challan_no)
                                        <div class="small text-muted" style="font-size:0.72rem;">Challan: <span class="fw-semibold">{{ $tr->challan_no }}</span></div>
                                    @endif
                                    @if (!$tr->vehicle_no && !$tr->driver_name && !$tr->challan_no)
                                        <span class="text-muted small">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">{{ $tr->creator?->name ?? 'System' }}</div>
                                    <div class="text-muted" style="font-size:0.7rem;">{{ $tr->created_at?->diffForHumans() }}</div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item view-transfer-btn" type="button"><i class="bi bi-eye text-info me-2"></i>View Details</button></li>
                                            @if ($tr->transfer_type === 'TRANSFER' && $tr->return_expected && $tr->pending_return_quantity > 0)
                                                <li>
                                                    <button class="dropdown-item text-success return-stock-btn" type="button"
                                                        data-id="{{ $tr->id }}"
                                                        data-tr-no="{{ $tr->transfer_no }}"
                                                        data-from-site-id="{{ $tr->from_project_site_id }}"
                                                        data-from-site-name="{{ $tr->fromSite?->site_name }}"
                                                        data-to-site-id="{{ $tr->to_project_site_id }}"
                                                        data-to-site-name="{{ $tr->toSite?->site_name }}"
                                                        data-material-id="{{ $tr->material_id }}"
                                                        data-material-name="{{ $tr->material?->name_en }}"
                                                        data-unit="{{ $tr->unit }}"
                                                        data-pending="{{ $tr->pending_return_quantity }}">
                                                        <i class="bi bi-arrow-return-left text-success me-2"></i>Record Return
</button>
                                                </li>
                                            @endif
                                            <li><button class="dropdown-item edit-transfer-btn" type="button" data-id="{{ $tr->id }}"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li><button class="dropdown-item text-danger delete-transfer-btn" type="button" data-id="{{ $tr->id }}" data-tr-no="{{ $tr->transfer_no }}"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyTransfersRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    <h6>No stock transfers recorded yet</h6>
                                    <p class="small mb-3">Click "+ New Stock Transfer" to transfer material between project sites.</p>
                                    <button type="button" class="btn btn-primary btn-sm" id="emptyStateNewBtn">
                                        <i class="bi bi-plus-circle me-1"></i> New Stock Transfer
</button>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching stock transfers found for your search or filter criteria.
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
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, $transfers->count()) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $transfers->count() }}</strong> stock transfers
</span>
</div>
                <nav aria-label="Stock transfers table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- NEW STOCK TRANSFER MODAL -->
    <div class="modal fade" id="newTransferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2 px-3">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="bi bi-arrow-left-right me-1"></i> Record New Stock Transfer 
</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="newTransferForm" novalidate>
                    @csrf
                    <div class="modal-body p-3 p-md-4">
                        <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <div>
                                Transfer materials from one project site to another. Only materials recorded in Material Usage for the selected Source Site can be transferred.
</div>
</div>

                        <div class="row g-3">
                            <!-- Transfer No & Date -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Transfer No  *</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-bold font-monospace"
                                    id="transferNo" name="transfer_no" value="{{ $nextTransferNo }}" readonly>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Transfer Date  *</label>
                                <input type="date" class="form-control form-control-sm" id="transferDate" name="transfer_date"
                                    value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
</div>

                            <!-- Source Site -> Destination Site -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="fromSiteSelect">Source Site  <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="fromSiteSelect" name="from_project_site_id" required>
                                    <option value="" disabled selected>-- Select Source Site --</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}@if(!empty($site->location)) ({{ $site->location }})@endif</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errFromSite">Please select the source site .</div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="toSiteSelect">Destination Site  <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="toSiteSelect" name="to_project_site_id" required>
                                    <option value="" disabled selected>-- Select Destination Site --</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}@if(!empty($site->location)) ({{ $site->location }})@endif</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errToSite">Please select the destination site .</div>
</div>

                            <!-- Material Selection Box (Filtered by Source Site) -->
                            <div class="col-12">
                                <div class="p-3 border rounded bg-light-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div>
                                            <label class="form-label small fw-bold mb-0 text-dark">
                                                <i class="bi bi-box-seam text-primary me-1"></i>Select Materials from Available On-Site Stock  <span class="text-danger">*</span>
</label>
                                            <div class="text-muted" style="font-size: 0.74rem;">
                                                Only materials recorded in Material Usage for the source site are available to transfer.
</div>
</div>
                                        <span class="badge bg-primary-subtle text-primary border" id="siteAvailableCountBadge" style="display:none;">0 Materials In Stock</span>
</div>

                                    <!-- Prompt before site is picked -->
                                    <div id="noSiteSelectedMsg" class="text-muted small py-2 px-3 text-center border rounded bg-white">
                                        <i class="bi bi-arrow-up-circle me-1 text-primary"></i> Please select a <strong>Source Site</strong> above to view and add materials in stock.
</div>

                                    <!-- Loading Spinner -->
                                    <div id="materialsLoadingSpinner" class="text-center py-2 d-none">
                                        <div class="spinner-border spinner-border-sm text-primary me-1"></div>
                                        <span class="small text-muted">Checking on-site materials &amp; stock balances...</span>
</div>

                                    <!-- No Materials In Stock Alert -->
                                    <div id="noMaterialsAlert" class="alert alert-warning py-2 px-3 small mb-0 d-none">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <strong>No materials recorded in Material Usage for this site yet.</strong> First record Material Usage for this site from Purchases &amp; Inward, then transfer available stock.
</div>

                                    <!-- Available Materials Picker -->
                                    <div id="materialSelectContainer" class="d-none">
                                        <div class="row g-2 align-items-end">
                                            <div class="col-12 col-md-9">
                                                <label class="form-label small fw-semibold text-muted mb-1">Available Material &amp; Stock Source</label>
                                                <select class="form-select form-select-sm" id="siteMaterialPicker">
                                                    <option value="" selected>-- Select Material from On-Site Stock --</option>
                                                </select>
</div>
                                            <div class="col-12 col-md-3">
                                                <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold" id="addMaterialToTransferBtn">
                                                    <i class="bi bi-plus-circle me-1"></i> Add to List
</button>
</div>
</div>
</div>
</div>
</div>

                            <!-- Selected Materials Table View (Material, Quantity, Price, Total) -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-bold mb-0 text-dark">
                                        <i class="bi bi-table text-primary me-1"></i>Materials to Transfer  <span class="text-danger">*</span>
</label>
                                    <span class="small text-muted" id="selectedItemsCountLabel">0 materials added</span>
</div>
                                <div class="table-responsive border rounded bg-white shadow-sm">
                                    <table class="table table-sm table-bordered align-middle mb-0" id="transferItemsTable">
                                        <thead class="table-light small">
                                            <tr class="text-muted text-uppercase" style="font-size: 0.74rem;">
                                                <th class="ps-2" style="min-width: 220px;">Material &amp; Stock Source</th>
                                                <th class="text-center" style="width: 100px;">Available</th>
                                                <th class="text-center" style="min-width: 130px;">Transfer Qty *</th>
                                                <th class="text-end" style="min-width: 110px;">Price (₹)</th>
                                                <th class="text-end pe-2" style="min-width: 110px;">Total (₹)</th>
                                                <th class="text-center" style="width: 40px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="transferItemsTableBody">
                                            <tr id="emptyItemsTableRow">
                                                <td colspan="6" class="text-center py-4 text-muted small">
                                                    <i class="bi bi-inbox d-block fs-4 text-secondary mb-1"></i>
                                                    No materials added yet. Choose a material from the source site above and click <strong>Add to List</strong>.
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="table-light small fw-bold border-top" id="transferItemsTableFoot" style="display: none;">
                                            <tr>
                                                <td colspan="4" class="text-end pe-2">Estimated Total Transfer Value :</td>
                                                <td class="text-end pe-2 text-primary fs-6 fw-bold" id="transferGrandTotal">₹0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
</div>
                                <div class="invalid-feedback d-block" id="errItemsTable" style="display:none !important;"></div>
</div>

                            <!-- Transport / Logistics Details -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Vehicle / Transport No</label>
                                <input type="text" class="form-control form-control-sm" name="vehicle_no"
                                    placeholder="e.g. TN 45 AB 1234 / Auto / Tractor">
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Driver / Carrier Name</label>
                                <input type="text" class="form-control form-control-sm" name="driver_name"
                                    placeholder="e.g. Murugan / Driver">
</div>

                            <!-- Remarks -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Remarks / Reason</label>
                                <textarea class="form-control form-control-sm" name="remarks" rows="2"
                                    placeholder="e.g. Urgent slab casting requirement at Site B. To be returned on next cement truck arrival."></textarea>
</div>
</div>
</div>
                    <div class="modal-footer py-2 px-3 bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-semibold" id="saveTransferBtn">
                            <i class="bi bi-check2-circle me-1"></i> Record Transfer
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- RECORD RETURN MODAL -->
    <div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2 px-3">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="bi bi-arrow-return-left me-1"></i> Return Stock to Source Site 
</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="returnTransferForm">
                    @csrf
                    <input type="hidden" id="returnParentTransferId" value="">
                    <div class="modal-body p-3">
                        <div class="p-2 border rounded bg-light mb-3">
                            <div class="small text-muted">Returning against Transfer:</div>
                            <div class="fw-bold text-dark font-monospace" id="returnParentNo">ST-2026-0001</div>
                            <div class="d-flex align-items-center gap-2 mt-1 small">
                                <span class="badge bg-danger bg-opacity-10 text-danger" id="returnFromSiteBadge">Site B</span>
                                <i class="bi bi-arrow-right"></i>
                                <span class="badge bg-success bg-opacity-10 text-success" id="returnToSiteBadge">Site A</span>
</div>
                            <div class="mt-2 small">
                                Material: <strong class="text-dark" id="returnMaterialName">Cement</strong>
                                (<span class="text-muted" id="returnUnitName">Bags</span>)
</div>
                            <div class="mt-1 small text-warning fw-semibold">
                                Pending to Return: <span id="returnPendingQty">10</span> <span id="returnPendingUnit">Bags</span>
</div>
</div>

                        <div class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Return Date  *</label>
                                <input type="date" class="form-control form-control-sm" id="returnDate" name="transfer_date"
                                    value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Quantity Returned  *</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="0.01" step="any" class="form-control fw-bold"
                                        id="returnQuantityInput" name="quantity" required>
                                    <span class="input-group-text bg-light text-muted" id="returnUnitSpan">Units</span>
</div>
                                <div class="form-text small" style="font-size:0.72rem;">Max returnable: <span id="returnMaxHint">10</span></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Vehicle No</label>
                                <input type="text" class="form-control form-control-sm" name="vehicle_no"
                                    placeholder="e.g. TN 45 AB 1234">
</div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Remarks</label>
                                <input type="text" class="form-control form-control-sm" name="remarks"
                                    placeholder="e.g. Returned from new cement truck load received today">
</div>
</div>
</div>
                    <div class="modal-footer py-2 px-3 bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-semibold" id="saveReturnBtn">
                            <i class="bi bi-arrow-return-left me-1"></i> Confirm Stock Return
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- VIEW DETAILS MODAL -->
    <div class="modal fade" id="viewTransferModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h5 class="modal-title fs-6 fw-bold">
                        <i class="bi bi-info-circle me-1"></i> Stock Transfer Details
</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <div class="modal-body p-3" id="viewModalBody">
                    <!-- Populated via JS -->
</div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>

    <!-- Edit Transfer Modal -->
    <div class="modal fade" id="editTransferModal" tabindex="-1" aria-labelledby="editTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h5 class="modal-title fs-6 fw-bold" id="editTransferModalLabel">
                        <i class="bi bi-pencil-square me-2 text-warning"></i>Edit Stock Transfer: <span id="editTransferNoTitle" class="text-warning"></span>
</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <div class="modal-body p-3">
                    <form id="editTransferForm">
                        <input type="hidden" id="editTransferId">

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Transfer Date  <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="editTransferDate" max="{{ date('Y-m-d') }}" required>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Source Site</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" id="editTransferFromSite" readonly>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Destination Site</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold" id="editTransferToSite" readonly>
</div>
</div>

                        <div class="row g-2 mb-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Vehicle No</label>
                                <input type="text" class="form-control form-control-sm" id="editTransferVehicleNo" placeholder="e.g. TN-58-AB-1234">
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Driver Name</label>
                                <input type="text" class="form-control form-control-sm" id="editTransferDriverName" placeholder="Driver name">
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold mb-1">Delivery Challan No</label>
                                <input type="text" class="form-control form-control-sm" id="editTransferChallanNo" placeholder="Challan / Slip No">
</div>
</div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="fw-semibold mb-0 small text-uppercase text-muted">
                                        <i class="bi bi-boxes text-primary me-1"></i>Transferred Materials 
</h6>
                                    <span class="text-muted small">Update quantity or price below. Live stocks and linked PO will update automatically.</span>
</div>
                                <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" id="toggleEditTransferAddMaterialBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Add Material
</button>
</div>

                            {{-- Add Material Inline Box in Edit Modal --}}
                            <div id="editTransferAddMaterialBox" class="p-2 mb-3 border rounded bg-light-subtle shadow-sm" style="display: none;">
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-7">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            <i class="bi bi-boxes text-primary me-1"></i>Select Material from Source Site 
</label>
                                        <select class="form-select form-select-sm" id="editTransferMaterialPicker">
                                            <option value="">-- Choose Material to Transfer --</option>
                                        </select>
</div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small fw-semibold text-dark mb-1">Transfer Qty  <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-center fw-semibold" id="editTransferNewItemQty" placeholder="Qty">
</div>
                                    <div class="col-6 col-md-2 d-flex gap-1">
                                        <button type="button" class="btn btn-primary btn-sm flex-grow-1 fw-semibold" id="confirmAddTransferMaterialBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Add
</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelAddTransferMaterialBtn" title="Close">
                                            <i class="bi bi-x-lg"></i>
</button>
</div>
</div>
</div>

                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-bordered align-middle mb-0" id="editTransferItemsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:34px" class="text-center">#</th>
                                            <th>Material</th>
                                            <th>Source / Reference</th>
                                            <th class="text-center" style="width:130px">Quantity  <span class="text-danger">*</span></th>
                                            <th class="text-center" style="width:80px">Unit</th>
                                            <th class="text-end" style="width:140px">Unit Price (₹) <span class="text-danger">*</span></th>
                                            <th class="text-end" style="width:140px">Total (₹)</th>
                                            <th class="text-center" style="width:40px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="editTransferItemsBody">
                                        {{-- Populated by JS --}}
                                    </tbody>
                                    <tfoot class="table-secondary fw-semibold">
                                        <tr>
                                            <td colspan="3" class="text-end">Total:</td>
                                            <td class="text-center text-primary fw-bold" id="editTransferTotalQty">0.00</td>
                                            <td></td>
                                            <td></td>
                                            <td class="text-end text-success fw-bold" id="editTransferTotalAmount">₹0.00</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
</div>
</div>

                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">Remarks</label>
                                <textarea class="form-control form-control-sm" id="editTransferRemarks" rows="2" placeholder="Optional notes..."></textarea>
</div>
</div>
                    </form>
</div>
                <div class="modal-footer py-2 px-3 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm px-3" id="saveEditTransferBtn">
                        <i class="bi bi-check-circle me-1"></i> Update Stock Transfer
</button>
</div>
</div>
</div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const ROUTE_STORE_TRANSFER = '{{ route("stock-transfers.store") }}';
            const ROUTE_AVAILABLE_MATERIALS = '{{ url("/stock-transfers/available-materials") }}';
            const ROUTE_NEXT_NUMBER = '{{ route("stock-transfers.next-number") }}';

            let availableMaterialsList = [];

            // Open new transfer modal
            $('#newTransferBtn, #emptyStateNewBtn').on('click', function () {
                $('#newTransferForm')[0].reset();
                $('#newTransferForm .is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
                $('#transferDate').val(new Date().toISOString().split('T')[0]);

                $('#fromSiteSelect').val('');
                $('#toSiteSelect').val('');
                $('#siteMaterialPicker').empty().append('<option value="" selected>-- Select Material from On-Site Stock --</option>');

                $('#noSiteSelectedMsg').removeClass('d-none');
                $('#materialsLoadingSpinner').addClass('d-none');
                $('#noMaterialsAlert').addClass('d-none');
                $('#materialSelectContainer').addClass('d-none');
                $('#siteAvailableCountBadge').hide();

                // Clear table
                clearTransferItemsTable();

                $.get(ROUTE_NEXT_NUMBER, function (res) {
                    if (res && res.transfer_no) {
                        $('#transferNo').val(res.transfer_no);
                    }
                });

                const modal = new bootstrap.Modal(document.getElementById('newTransferModal'));
                modal.show();
            });

            // Source site changed -> Load only available materials at that site
            $('#fromSiteSelect').on('change', function () {
                const siteId = $(this).val();
                $(this).removeClass('is-invalid');
                $('#errFromSite').hide();

                const toId = $('#toSiteSelect').val();
                if (siteId && toId && siteId === toId) {
                    showToast('Source site and destination site cannot be the same .', 'warning');
                    $('#toSiteSelect').val('').addClass('is-invalid');
                }

                clearTransferItemsTable();

                if (!siteId) {
                    $('#noSiteSelectedMsg').removeClass('d-none');
                    $('#materialSelectContainer').addClass('d-none');
                    $('#noMaterialsAlert').addClass('d-none');
                    $('#siteAvailableCountBadge').hide();
                    return;
                }

                $('#noSiteSelectedMsg').addClass('d-none');
                $('#noMaterialsAlert').addClass('d-none');
                $('#materialSelectContainer').addClass('d-none');
                $('#materialsLoadingSpinner').removeClass('d-none');

                $.get(`${ROUTE_AVAILABLE_MATERIALS}/${siteId}`, function (res) {
                    $('#materialsLoadingSpinner').addClass('d-none');
                    availableMaterialsList = res.materials || [];

                    const picker = $('#siteMaterialPicker');
                    picker.empty();

                    if (!availableMaterialsList.length) {
                        $('#noMaterialsAlert').removeClass('d-none');
                        $('#siteAvailableCountBadge').text('0 Available').show().removeClass('bg-primary-subtle text-primary').addClass('bg-warning-subtle text-warning');
                        return;
                    }

                    picker.append('<option value="" selected disabled>-- Select Material from On-Site Stock --</option>');
                    availableMaterialsList.forEach((it, idx) => {
                        const isWh = it.source_type === 'Own Warehouse';
                        const sourceTag = isWh ? 'Own Warehouse' : `Supplier PO: ${it.reference_no}`;
                        const sourceDetail = isWh ? '' : (it.supplier_name ? ` · Supplier: ${it.supplier_name}` : '');
                        const label = `[${sourceTag}] ${it.material_name} · Avail: ${it.available_qty} ${it.unit} @ ₹${Number(it.unit_price).toLocaleString('en-IN')}${sourceDetail}`;
                        const opt = $('<option></option>')
                            .val(it.key)
                            .text(label)
                            .data('item', it);
                        picker.append(opt);
                    });

                    $('#siteAvailableCountBadge').text(`${availableMaterialsList.length} Materials In Stock`).show().removeClass('bg-warning-subtle text-warning').addClass('bg-primary-subtle text-primary');
                    $('#materialSelectContainer').removeClass('d-none');
                }).fail(function () {
                    $('#materialsLoadingSpinner').addClass('d-none');
                    $('#noMaterialsAlert').removeClass('d-none');
                    showToast('Could not load site materials. Please check connection.', 'danger');
                });
            });

            // Destination site changed
            $('#toSiteSelect').on('change', function () {
                const toId = $(this).val();
                const fromId = $('#fromSiteSelect').val();
                $(this).removeClass('is-invalid');
                $('#errToSite').hide();

                if (fromId && toId && fromId === toId) {
                    showToast('Source site and destination site cannot be the same .', 'warning');
                    $(this).val('').addClass('is-invalid');
                    $('#errToSite').text('Destination site must be different from source site.').show();
                }
            });

            // Add Material to transfer table
            $('#addMaterialToTransferBtn').on('click', function () {
                const picker = $('#siteMaterialPicker');
                const selectedKey = picker.val();
                if (!selectedKey) {
                    showToast('Please select a material from the dropdown above.', 'warning');
                    return;
                }

                const selectedOption = picker.find('option:selected');
                const item = selectedOption.data('item');
                if (!item) return;

                // Check if already in table (by key, material_id, or name)
                let existingRow = $(`#transferItemsTableBody tr[data-key="${item.key}"]`);
                if (!existingRow.length && item.material_id) {
                    existingRow = $(`#transferItemsTableBody tr[data-mat-id="${item.material_id}"]`);
                }
                if (!existingRow.length) {
                    existingRow = $(`#transferItemsTableBody tr`).filter(function() {
                        return $(this).attr('data-name')?.trim().toLowerCase() === item.material_name.trim().toLowerCase();
                    });
                }
                if (existingRow.length) {
                    existingRow.addClass('table-warning');
                    setTimeout(() => existingRow.removeClass('table-warning'), 1500);
                    existingRow.find('.tr-item-qty').focus();
                    showToast(`"${item.material_name}" is already in the transfer list. Adjust its quantity if needed.`, 'warning');
                    return;
                }

                // Remove empty row if present
                $('#emptyItemsTableRow').remove();

                const isWarehouse = item.source_type === 'Own Warehouse';
                const sourceBadge = isWarehouse
                    ? `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-boxes me-1"></i>Own Warehouse</span>`
                    : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" title="${item.supplier_name || ''}"><i class="bi bi-file-earmark-text me-1"></i>Supplier PO: ${item.reference_no}${item.supplier_name ? ' (' + item.supplier_name + ')' : ''}</span>`;

                const rowHtml = `
                    <tr data-key="${item.key}" data-mat-id="${item.material_id}" data-name="${item.material_name}"
                        data-source-type="${item.source_type}" data-ref="${item.reference_no}" data-supplier="${item.supplier_name || ''}"
                        data-unit="${item.unit}" data-unit-id="${item.unit_id || ''}" data-avail="${item.available_qty}">
                        <td class="ps-2">
                            <div class="fw-bold text-dark">${item.material_name}</div>
                            ${item.material_name_ta ? `<div class="small text-muted" style="font-size:0.75rem;">${item.material_name_ta}</div>` : ''}
                            <div class="mt-1">${sourceBadge}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary font-monospace" style="font-size:0.78rem;">${item.available_qty} ${item.unit}</span>
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm" style="max-width: 150px; margin: 0 auto;">
                                <input type="number" min="0.01" max="${item.available_qty}" step="any" value="1"
                                    class="form-control text-end fw-bold tr-item-qty" required>
                                <span class="input-group-text bg-light text-muted px-1" style="font-size:0.72rem;">${item.unit}</span>
</div>
                            <div class="invalid-feedback small d-none item-qty-err" style="font-size:0.7rem;">Exceeds stock (${item.available_qty})</div>
                        </td>
                        <td class="text-end">
                            <div class="input-group input-group-sm" style="max-width: 120px; margin-left: auto;">
                                <span class="input-group-text bg-light px-1" style="font-size:0.75rem;">₹</span>
                                <input type="number" min="0" step="0.01" value="${item.unit_price}"
                                    class="form-control text-end fw-semibold tr-item-price">
</div>
                        </td>
                        <td class="text-end pe-2">
                            <div class="fw-bold text-dark tr-item-total">₹${Number(item.unit_price).toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 tr-item-remove" title="Remove Item">
                                <i class="bi bi-trash"></i>
</button>
                        </td>
                    </tr>
                `;

                $('#transferItemsTableBody').append(rowHtml);
                $('#errItemsTable').hide();
                recalcTotals();
                showToast(`Added ${item.material_name} to transfer list.`, 'success');
            });

            // Quantity or Price change -> recalculate row total and grand total
            $(document).on('input', '.tr-item-qty, .tr-item-price', function () {
                const row = $(this).closest('tr');
                const qtyInput = row.find('.tr-item-qty');
                const qty = parseFloat(qtyInput.val()) || 0;
                const avail = parseFloat(row.data('avail')) || 0;
                const errDiv = row.find('.item-qty-err');

                if (qty > avail) {
                    qtyInput.addClass('is-invalid');
                    errDiv.removeClass('d-none').show();
                } else {
                    qtyInput.removeClass('is-invalid');
                    errDiv.addClass('d-none').hide();
                }

                recalcTotals();
            });

            // Remove item from transfer table
            $(document).on('click', '.tr-item-remove', function () {
                const row = $(this).closest('tr');
                row.remove();
                if ($('#transferItemsTableBody tr[data-key]').length === 0) {
                    clearTransferItemsTable();
                } else {
                    recalcTotals();
                }
            });

            function clearTransferItemsTable() {
                $('#transferItemsTableBody').html(`
                    <tr id="emptyItemsTableRow">
                        <td colspan="6" class="text-center py-4 text-muted small">
                            <i class="bi bi-inbox d-block fs-4 text-secondary mb-1"></i>
                            No materials added yet. Choose a material from the source site above and click <strong>Add to List</strong>.
                        </td>
                    </tr>
                `);
                $('#transferItemsTableFoot').hide();
                $('#selectedItemsCountLabel').text('0 materials added');
                $('#transferGrandTotal').text('₹0.00');
            }

            function recalcTotals() {
                const rows = $('#transferItemsTableBody tr[data-key]');
                let grandTotal = 0;
                let count = 0;

                rows.each(function () {
                    const row = $(this);
                    const qty = parseFloat(row.find('.tr-item-qty').val()) || 0;
                    const price = parseFloat(row.find('.tr-item-price').val()) || 0;
                    const lineTotal = qty * price;

                    grandTotal += lineTotal;
                    count++;

                    row.find('.tr-item-total').text('₹' + lineTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                });

                if (count > 0) {
                    $('#transferItemsTableFoot').show();
                    $('#selectedItemsCountLabel').text(`${count} material${count > 1 ? 's' : ''} added`);
                    $('#transferGrandTotal').text('₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                } else {
                    $('#transferItemsTableFoot').hide();
                    $('#selectedItemsCountLabel').text('0 materials added');
                }
            }

            // Save new transfer
            $('#newTransferForm').on('submit', function (e) {
                e.preventDefault();
                $('#newTransferForm .is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();

                const fromSiteId = $('#fromSiteSelect').val();
                const toSiteId = $('#toSiteSelect').val();

                // Validation 1: Source Site
                if (!fromSiteId) {
                    $('#fromSiteSelect').addClass('is-invalid');
                    $('#errFromSite').show();
                    $('#fromSiteSelect').focus();
                    showToast('Please select the source site .', 'warning');
                    return;
                }

                // Validation 2: Destination Site
                if (!toSiteId) {
                    $('#toSiteSelect').addClass('is-invalid');
                    $('#errToSite').show();
                    $('#toSiteSelect').focus();
                    showToast('Please select the destination site .', 'warning');
                    return;
                }

                // Validation 3: Different sites
                if (fromSiteId === toSiteId) {
                    $('#toSiteSelect').addClass('is-invalid');
                    $('#errToSite').text('Destination site must be different from source site.').show();
                    $('#toSiteSelect').focus();
                    showToast('Source site and destination site cannot be the same .', 'warning');
                    return;
                }

                // Validation 4: Must have at least 1 material in table
                const itemRows = $('#transferItemsTableBody tr[data-key]');
                if (!itemRows.length) {
                    $('#errItemsTable').show().text('Please add at least one material to transfer .');
                    showToast('Please add at least one material to transfer .', 'warning');
                    return;
                }

                // Validation 5: Check quantities & stocks for each row
                let hasItemError = false;
                const itemsPayload = [];

                itemRows.each(function () {
                    const row = $(this);
                    const qtyInput = row.find('.tr-item-qty');
                    const qty = parseFloat(qtyInput.val()) || 0;
                    const avail = parseFloat(row.data('avail')) || 0;
                    const price = parseFloat(row.find('.tr-item-price').val()) || 0;

                    if (qty <= 0) {
                        qtyInput.addClass('is-invalid');
                        showToast(`Quantity must be greater than zero for ${row.data('name')}.`, 'warning');
                        hasItemError = true;
                        return false;
                    }

                    if (qty > avail) {
                        qtyInput.addClass('is-invalid');
                        showToast(`Transfer quantity (${qty}) exceeds available stock (${avail}) for ${row.data('name')}.`, 'warning');
                        hasItemError = true;
                        return false;
                    }

                    itemsPayload.push({
                        material_id: row.data('mat-id'),
                        material_name: row.data('name'),
                        source_type: row.data('source-type'),
                        source_reference: row.data('ref'),
                        supplier_name: row.data('supplier'),
                        quantity: qty,
                        unit: row.data('unit'),
                        unit_id: row.data('unit-id'),
                        unit_price: price,
                        total_price: qty * price
                    });
                });

                if (hasItemError) {
                    return;
                }

                const btn = $('#saveTransferBtn');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Recording...');

                const payload = {
                    _token: '{{ csrf_token() }}',
                    transfer_no: $('#transferNo').val(),
                    transfer_date: $('#transferDate').val(),
                    from_project_site_id: fromSiteId,
                    to_project_site_id: toSiteId,
                    return_expected: 0,
                    vehicle_no: $('input[name="vehicle_no"]').val(),
                    driver_name: $('input[name="driver_name"]').val(),
                    remarks: $('textarea[name="remarks"]').val(),
                    items: itemsPayload
                };

                $.ajax({
                    url: ROUTE_STORE_TRANSFER,
                    type: 'POST',
                    data: payload,
                    success: function (res) {
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Record Transfer');
                        $('#newTransferModal').modal('hide');
                        showToast(res.message || 'Stock transfer recorded successfully!', 'success');
                        setTimeout(() => window.location.reload(), 700);
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Record Transfer');
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errs = xhr.responseJSON.errors;
                            if (errs.from_project_site_id) {
                                $('#fromSiteSelect').addClass('is-invalid');
                                $('#errFromSite').text(errs.from_project_site_id[0]).show();
                            }
                            if (errs.to_project_site_id) {
                                $('#toSiteSelect').addClass('is-invalid');
                                $('#errToSite').text(errs.to_project_site_id[0]).show();
                            }
                            if (errs.items) {
                                $('#errItemsTable').text(errs.items[0]).show();
                            }
                            showToast('Please fix the highlighted errors.', 'warning');
                        } else {
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to record stock transfer.';
                            showToast(msg, 'danger');
                        }
                    }
                });
            });

            // Open return modal
            $(document).on('click', '.return-stock-btn', function () {
                const id = $(this).data('id');
                const trNo = $(this).data('tr-no');
                const fromSiteName = $(this).data('from-site-name');
                const toSiteName = $(this).data('to-site-name');
                const matName = $(this).data('material-name');
                const unit = $(this).data('unit') || 'Units';
                const pending = parseFloat($(this).data('pending')) || 0;

                $('#returnParentTransferId').val(id);
                $('#returnParentNo').text(trNo);
                $('#returnFromSiteBadge').text(toSiteName + ' (Returning)');
                $('#returnToSiteBadge').text(fromSiteName + ' (Receiving)');
                $('#returnMaterialName').text(matName);
                $('#returnUnitName').text(unit);
                $('#returnPendingQty').text(pending);
                $('#returnPendingUnit').text(unit);
                $('#returnUnitSpan').text(unit);
                $('#returnMaxHint').text(`${pending} ${unit}`);
                $('#returnQuantityInput').attr('max', pending).val(pending);
                $('#returnDate').val(new Date().toISOString().split('T')[0]);

                const modal = new bootstrap.Modal(document.getElementById('returnModal'));
                modal.show();
            });

            // Submit Return Form
            $('#returnTransferForm').on('submit', function (e) {
                e.preventDefault();
                const parentId = $('#returnParentTransferId').val();
                if (!parentId) return;

                const btn = $('#saveReturnBtn');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Returning...');

                const url = `{{ url('/stock-transfers') }}/${parentId}/return`;
                const formData = $(this).serialize();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function (res) {
                        btn.prop('disabled', false).html('<i class="bi bi-arrow-return-left me-1"></i> Confirm Stock Return');
                        $('#returnModal').modal('hide');
                        showToast(res.message || 'Stock returned successfully!', 'success');
                        setTimeout(() => window.location.reload(), 700);
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-arrow-return-left me-1"></i> Confirm Stock Return');
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to record return.';
                        showToast(msg, 'danger');
                    }
                });
            });

            // Delete Transfer
            $(document).on('click', '.delete-transfer-btn', function () {
                const id = $(this).data('id');
                const trNo = $(this).data('tr-no');
                if (!confirm(`Are you sure you want to delete transfer "${trNo}"? Live site stock will be reverted.`)) {
                    return;
                }

                $.ajax({
                    url: `{{ url('/stock-transfers') }}/${id}/delete`,
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        showToast(res.message || 'Transfer deleted.', 'info');
                        setTimeout(() => window.location.reload(), 600);
                    },
                    error: function (xhr) {
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Cannot delete transfer.';
                        showToast(msg, 'danger');
                    }
                });
            });

            // View Details Modal
            $(document).on('click', '.view-transfer-btn', function () {
                const row = $(this).closest('.transfer-row');
                const tr = row.data('json');
                if (!tr) return;

                let itemsTableHtml = '';
                if (tr.items && tr.items.length > 0) {
                    let rowsHtml = '';
                    tr.items.forEach((it, idx) => {
                        const isWh = it.source_type === 'Own Warehouse';
                        const badge = isWh
                            ? `<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-boxes me-1"></i>Own Warehouse</span>`
                            : `<span class="badge bg-primary-subtle text-primary border border-primary-subtle">${it.source_reference || 'PO'}</span>`;

                        rowsHtml += `
                            <tr>
                                <td class="ps-2">
                                    <div class="fw-semibold text-dark">${it.material_name}</div>
                                    <div class="mt-1">${badge} ${it.supplier_name ? `<small class="text-muted">(${it.supplier_name})</small>` : ''}</div>
                                </td>
                                <td class="text-center fw-bold">${it.quantity} <small class="text-muted">${it.unit || ''}</small></td>
                                <td class="text-end">₹${Number(it.unit_price).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                <td class="text-end pe-2 fw-bold text-dark">₹${Number(it.total_price).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });

                    itemsTableHtml = `
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-1"><i class="bi bi-boxes text-primary me-1"></i>Transferred Materials (${tr.items.length})</label>
                            <div class="table-responsive border rounded bg-white">
                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.8rem;">
                                    <thead class="table-light">
                                        <tr class="text-muted text-uppercase" style="font-size:0.72rem;">
                                            <th class="ps-2">Material / Source</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end pe-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rowsHtml}
                                    </tbody>
                                    <tfoot class="table-light fw-bold">
                                        <tr>
                                            <td colspan="3" class="text-end pe-2">Total Value:</td>
                                            <td class="text-end pe-2 text-primary">₹${Number(tr.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                                        </tr>
                                    </tfoot>
                                </table>
</div>
</div>
                    `;
                }

                const html = `
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-primary fs-6 font-monospace">${tr.transfer_no}</span>
                        <span class="text-muted small">${tr.transfer_date ? new Date(tr.transfer_date).toLocaleDateString('en-GB') : ''}</span>
</div>
                    <div class="p-3 border rounded bg-light mb-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Source Site</small>
                                <strong class="text-danger">${tr.from_site ? (tr.from_site.name || tr.from_site.site_name) : 'Site #' + tr.from_project_site_id}</strong>
</div>
                            <div class="col-6">
                                <small class="text-muted d-block">Destination Site</small>
                                <strong class="text-success">${tr.to_site ? (tr.to_site.name || tr.to_site.site_name) : 'Site #' + tr.to_project_site_id}</strong>
</div>
                            <div class="col-6 mt-2">
                                <small class="text-muted d-block">Total Items</small>
                                <strong class="text-dark">${tr.total_items_count || (tr.items ? tr.items.length : 1)} Materials</strong>
</div>
                            <div class="col-6 mt-2">
                                <small class="text-muted d-block">Estimated Value</small>
                                <strong class="text-primary fs-6">₹${Number(tr.total_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
</div>
</div>
</div>
                    ${itemsTableHtml}
                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Transfer Type</span>
                            <span class="fw-semibold">${tr.transfer_type}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Vehicle / Transport</span>
                            <span>${tr.vehicle_no || '&mdash;'}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Driver</span>
                            <span>${tr.driver_name || '&mdash;'}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">Recorded By</span>
                            <span>${tr.creator ? tr.creator.name : 'System'}</span>
                        </li>
                    </ul>
                    ${tr.remarks ? `<div class="p-2 border rounded bg-white small"><strong class="d-block text-muted">Remarks:</strong>${tr.remarks}</div>` : ''}
                `;

                $('#viewModalBody').html(html);
                const modal = new bootstrap.Modal(document.getElementById('viewTransferModal'));
                modal.show();
            });

            // ── Edit Stock Transfer ──────────────────────────────────────────────
            function recalcEditTransferTotals() {
                let grandQty = 0;
                let grandAmount = 0;
                $('#editTransferItemsBody tr').each(function () {
                    const qty = parseFloat($(this).find('.edit-transfer-item-qty').val()) || 0;
                    const price = parseFloat($(this).find('.edit-transfer-item-price').val()) || 0;
                    const lineTotal = qty * price;
                    grandQty += qty;
                    grandAmount += lineTotal;
                    $(this).find('.edit-transfer-item-total').text('₹' + lineTotal.toFixed(2));
                });
                $('#editTransferTotalQty').text(grandQty.toFixed(2));
                $('#editTransferTotalAmount').text('₹' + grandAmount.toFixed(2));
            }

            function updateEditTransferRowNumbers() {
                $('#editTransferItemsBody tr').each(function (idx) {
                    $(this).find('.edit-transfer-row-num').text(idx + 1);
                });
            }

            // Load available materials into Edit Transfer Picker
            let editTransferAvailableMaterials = [];
            function loadEditTransferMaterialsPicker(fromSiteId) {
                const picker = $('#editTransferMaterialPicker');
                picker.html('<option value="">-- Loading available materials... --</option>');
                $('#editTransferNewItemQty').val('');

                if (!fromSiteId) {
                    picker.html('<option value="">-- Source site not specified --</option>');
                    return;
                }

                $.ajax({
                    url: `${ROUTE_AVAILABLE_MATERIALS}/${fromSiteId}`,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    success: function (res) {
                        editTransferAvailableMaterials = res.materials || [];
                        picker.html('<option value="">-- Choose Material to Transfer --</option>');
                        if (editTransferAvailableMaterials.length === 0) {
                            picker.html('<option value="">No additional materials in stock at source site</option>');
                            return;
                        }

                        editTransferAvailableMaterials.forEach(it => {
                            const sourceTag = it.source_type === 'Own Warehouse' ? 'Warehouse' : 'PO';
                            const sourceDetail = it.source_type === 'Own Warehouse' ? '' : (it.reference_no ? ` (${it.reference_no})` : '');
                            const label = `[${sourceTag}] ${it.material_name} · Avail: ${it.available_qty} ${it.unit} @ ₹${Number(it.unit_price).toLocaleString('en-IN')}${sourceDetail}`;
                            const opt = $('<option></option>')
                                .val(it.key)
                                .text(label)
                                .data('item', it);
                            picker.append(opt);
                        });
                    },
                    error: function () {
                        picker.html('<option value="">Failed to load site materials</option>');
                    }
                });
            }

            // Toggle / Cancel Add Material Box in Edit Modal
            $('#toggleEditTransferAddMaterialBtn').on('click', function () {
                $('#editTransferAddMaterialBox').slideToggle(150);
            });

            $('#cancelAddTransferMaterialBtn').on('click', function () {
                $('#editTransferAddMaterialBox').slideUp(150);
            });

            // Confirm Add Material to Edit Transfer Table
            $('#confirmAddTransferMaterialBtn').on('click', function () {
                const picker = $('#editTransferMaterialPicker');
                const selectedOpt = picker.find('option:selected');
                const item = selectedOpt.data('item');

                if (!picker.val() || !item) {
                    showToast('Please select a material from the dropdown first.', 'warning');
                    return;
                }

                const qtyInput = $('#editTransferNewItemQty');
                const qty = parseFloat(qtyInput.val());
                if (isNaN(qty) || qty <= 0) {
                    showToast('Please enter a transfer quantity greater than zero.', 'warning');
                    qtyInput.focus();
                    return;
                }

                // ── DUPLICATE CHECK: Verify same material is not added multiple times ──
                let isDuplicate = false;
                let dupRow = null;
                $('#editTransferItemsBody tr').each(function () {
                    const rowKey = $(this).attr('data-key');
                    const rowMatId = $(this).attr('data-mat-id');
                    const rowName = $(this).attr('data-name');

                    if (item.key && rowKey === item.key) {
                        isDuplicate = true;
                        dupRow = $(this);
                        return false;
                    }
                    if (item.material_id && rowMatId && String(rowMatId) === String(item.material_id)) {
                        isDuplicate = true;
                        dupRow = $(this);
                        return false;
                    }
                    if (item.material_name && rowName && rowName.trim().toLowerCase() === item.material_name.trim().toLowerCase()) {
                        isDuplicate = true;
                        dupRow = $(this);
                        return false;
                    }
                });

                if (isDuplicate && dupRow) {
                    dupRow.addClass('table-warning');
                    setTimeout(() => dupRow.removeClass('table-warning'), 1500);
                    dupRow.find('.edit-transfer-item-qty').focus();
                    showToast(`"${item.material_name}" is already in the transfer list. Adjust its quantity instead.`, 'warning');
                    return;
                }

                // Append new row
                const tbody = $('#editTransferItemsBody');
                const rowCount = tbody.find('tr').length;
                const sourceBadge = item.source_type === 'Own Warehouse'
                    ? '<span class="badge bg-info-subtle text-info border">Own Warehouse</span>'
                    : `<span class="badge bg-light text-secondary border">${item.reference_no || 'PO'}</span>`;

                const newRowHtml = `
                    <tr data-item-id="" data-key="${item.key || ''}" data-mat-id="${item.material_id || ''}"
                        data-name="${item.material_name}" data-source-type="${item.source_type}"
                        data-ref="${item.reference_no || ''}" data-supplier="${item.supplier_name || ''}"
                        data-unit="${item.unit || ''}" data-unit-id="${item.unit_id || ''}">
                        <td class="text-center text-muted small edit-transfer-row-num">${rowCount + 1}</td>
                        <td>
                            <div class="fw-semibold text-dark">${item.material_name}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Max avail: ${item.available_qty} ${item.unit}</div>
                        </td>
                        <td>${sourceBadge}</td>
                        <td>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-center fw-semibold edit-transfer-item-qty" value="${qty}" required>
                        </td>
                        <td class="text-center text-muted small">${item.unit || ''}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end edit-transfer-item-price" value="${parseFloat(item.unit_price || 0).toFixed(2)}" required>
</div>
                        </td>
                        <td class="text-end fw-semibold text-success edit-transfer-item-total">
                            ₹${(qty * parseFloat(item.unit_price || 0)).toFixed(2)}
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm p-0 remove-edit-transfer-item" style="width:26px; height:26px;" title="Remove Material">
                                <i class="bi bi-trash"></i>
</button>
                        </td>
                    </tr>
                `;

                tbody.append(newRowHtml);
                tbody.find('tr:last .edit-transfer-item-qty, tr:last .edit-transfer-item-price').on('input', recalcEditTransferTotals);
                recalcEditTransferTotals();
                updateEditTransferRowNumbers();
                qtyInput.val('');
                picker.val('');
                showToast(`Added ${item.material_name} to transfer list.`, 'success');
            });

            // Remove Material row in Edit Modal
            $(document).on('click', '.remove-edit-transfer-item', function () {
                const rows = $('#editTransferItemsBody tr');
                if (rows.length <= 1) {
                    showToast('A stock transfer must contain at least one material.', 'warning');
                    return;
                }
                $(this).closest('tr').remove();
                updateEditTransferRowNumbers();
                recalcEditTransferTotals();
                // Reload picker so removed material becomes available again
                if (window._editTransferFromSiteId) {
                    loadEditTransferMaterialsPicker(window._editTransferFromSiteId);
                }
            });

            $(document).on('click', '.edit-transfer-btn', function () {
                const id = $(this).data('id');
                const tbody = $('#editTransferItemsBody');
                $('#editTransferAddMaterialBox').hide();
                tbody.html('<tr><td colspan="8" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading transfer details...</td></tr>');

                const modalEl = document.getElementById('editTransferModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();

                $.ajax({
                    url: `{{ url('/stock-transfers') }}/${id}`,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    success: function (res) {
                        if (!res.success || !res.transfer) {
                            tbody.html('<tr><td colspan="8" class="text-center py-3 text-danger">Failed to load transfer details.</td></tr>');
                            return;
                        }
                        const tr = res.transfer;
                        $('#editTransferId').val(tr.id);
                        $('#editTransferNoTitle').text(tr.transfer_no);
                        $('#editTransferDate').val(tr.transfer_date ? tr.transfer_date.split('T')[0] : '');
                        $('#editTransferFromSite').val(tr.from_site ? (tr.from_site.site_name || tr.from_site.name) : 'Site #' + tr.from_project_site_id);
                        $('#editTransferToSite').val(tr.to_site ? (tr.to_site.site_name || tr.to_site.name) : 'Site #' + tr.to_project_site_id);
                        $('#editTransferVehicleNo').val(tr.vehicle_no || '');
                        $('#editTransferDriverName').val(tr.driver_name || '');
                        $('#editTransferChallanNo').val(tr.challan_no || '');
                        $('#editTransferRemarks').val(tr.remarks || '');
                        $('#editTransferReturnExpected').prop('checked', !!tr.return_expected);

                        // Load available materials from source site into the Add Material picker
                        if (tr.from_project_site_id) {
                            window._editTransferFromSiteId = tr.from_project_site_id;
                            loadEditTransferMaterialsPicker(tr.from_project_site_id);
                        }

                        let rowsHtml = '';
                        (tr.items || []).forEach((it, idx) => {
                            const sourceBadge = it.source_type === 'Own Warehouse'
                                ? '<span class="badge bg-info-subtle text-info border">Own Warehouse</span>'
                                : `<span class="badge bg-light text-secondary border">${it.source_reference || 'PO'}</span>`;

                            rowsHtml += `
                                <tr data-item-id="${it.id}" data-mat-id="${it.material_id || ''}" data-name="${it.material_name}"
                                    data-source-type="${it.source_type || 'Site Stock'}" data-ref="${it.source_reference || ''}"
                                    data-supplier="${it.supplier_name || ''}" data-unit="${it.unit || ''}" data-unit-id="${it.unit_id || ''}">
                                    <td class="text-center text-muted small edit-transfer-row-num">${idx + 1}</td>
                                    <td>
                                        <div class="fw-semibold text-dark">${it.material_name}</div>
                                    </td>
                                    <td>${sourceBadge}</td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-center fw-semibold edit-transfer-item-qty" value="${parseFloat(it.quantity || 0)}" required>
                                    </td>
                                    <td class="text-center text-muted small">${it.unit || ''}</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end edit-transfer-item-price" value="${parseFloat(it.unit_price || 0).toFixed(2)}" required>
</div>
                                    </td>
                                    <td class="text-end fw-semibold text-success edit-transfer-item-total">
                                        ₹${parseFloat(it.total_price || 0).toFixed(2)}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-0 remove-edit-transfer-item" style="width:26px; height:26px;" title="Remove Material">
                                            <i class="bi bi-trash"></i>
</button>
                                    </td>
                                </tr>
                            `;
                        });

                        tbody.html(rowsHtml);

                        tbody.find('.edit-transfer-item-qty, .edit-transfer-item-price').on('input', recalcEditTransferTotals);
                        recalcEditTransferTotals();
                    },
                    error: function () {
                        tbody.html('<tr><td colspan="8" class="text-center py-3 text-danger">Network error loading details.</td></tr>');
                    }
                });
            });

            $('#saveEditTransferBtn').on('click', function () {
                const transferId = $('#editTransferId').val();
                const transferDate = $('#editTransferDate').val();
                const vehicleNo = $('#editTransferVehicleNo').val();
                const driverName = $('#editTransferDriverName').val();
                const challanNo = $('#editTransferChallanNo').val();
                const remarks = $('#editTransferRemarks').val();
                const returnExpected = 0;

                if (!transferDate) {
                    showToast('Please select a valid transfer date.', 'warning');
                    return;
                }

                const items = [];
                let valid = true;
                $('#editTransferItemsBody tr').each(function () {
                    const row = $(this);
                    const itemId = row.attr('data-item-id');
                    const qtyInput = row.find('.edit-transfer-item-qty');
                    const priceInput = row.find('.edit-transfer-item-price');
                    const qty = parseFloat(qtyInput.val());
                    const price = parseFloat(priceInput.val());

                    if (isNaN(qty) || qty <= 0) {
                        valid = false;
                        qtyInput.addClass('is-invalid');
                    } else {
                        qtyInput.removeClass('is-invalid');
                    }

                    if (isNaN(price) || price < 0) {
                        valid = false;
                        priceInput.addClass('is-invalid');
                    } else {
                        priceInput.removeClass('is-invalid');
                    }

                    const itemPayload = {
                        quantity: qty,
                        unit_price: price,
                    };

                    if (itemId && parseInt(itemId, 10) > 0) {
                        itemPayload.id = parseInt(itemId, 10);
                    } else {
                        // New item added in Edit modal
                        itemPayload.material_id = row.attr('data-mat-id') ? parseInt(row.attr('data-mat-id'), 10) : null;
                        itemPayload.material_name = row.attr('data-name');
                        itemPayload.source_type = row.attr('data-source-type') || 'Site Stock';
                        itemPayload.source_reference = row.attr('data-ref') || null;
                        itemPayload.supplier_name = row.attr('data-supplier') || null;
                        itemPayload.unit = row.attr('data-unit') || '';
                        itemPayload.unit_id = row.attr('data-unit-id') ? parseInt(row.attr('data-unit-id'), 10) : null;
                    }

                    items.push(itemPayload);
                });

                if (!valid || items.length === 0) {
                    showToast('Please provide valid quantities (> 0) and prices (>= 0) for all materials.', 'danger');
                    return;
                }

                const btn = $(this);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');

                $.ajax({
                    url: `{{ url('/stock-transfers') }}/${transferId}/update`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({
                        transfer_date: transferDate,
                        vehicle_no: vehicleNo,
                        driver_name: driverName,
                        challan_no: challanNo,
                        remarks: remarks,
                        return_expected: returnExpected,
                        items: items,
                    }),
                    success: function (res) {
                        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Stock Transfer');
                        if (res.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editTransferModal')).hide();
                            showToast(res.message || 'Stock transfer updated successfully!', 'success');
                            setTimeout(() => location.reload(), 700);
                        } else {
                            showToast(res.message || 'Failed to update stock transfer.', 'danger');
                        }
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-check-circle me-1"></i> Update Stock Transfer');
                        const msg = xhr.responseJSON?.message || 'Error occurred while updating stock transfer.';
                        showToast(msg, 'danger');
                    }
                });
            });

            // ── Table Pagination & Client-Side Filtering ───────────────
            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function getMatchingRows() {
                const q = ($('#filterSearch').val() || '').toLowerCase().trim();
                const site = $('#filterSite').val() || '';
                const matId = $('#filterMaterial').val() || '';

                const matching = [];
                $('#transfersTableBody tr.transfer-row').each(function () {
                    const row = $(this);
                    const searchVal = String(row.data('search') || '');
                    const fromSite = String(row.data('from-site') || '');
                    const toSite = String(row.data('to-site') || '');
                    const matIds = String(row.data('material-ids') || '').split(',');

                    const matchesQuery = !q || searchVal.includes(q);
                    const matchesSite = !site || fromSite === site || toSite === site;
                    const matchesMat = !matId || matIds.includes(matId);

                    if (matchesQuery && matchesSite && matchesMat) {
                        matching.push(this);
                    }
                });
                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#transfersTableBody tr.transfer-row').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all transfer data rows
                $('#transfersTableBody tr.transfer-row').hide();

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
                summaryHtml += ' stock transfers';
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

            $('#filterSearch, #filterSite, #filterMaterial').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            $('#filterForm').on('submit', function (e) {
                e.preventDefault();
                currentPage = 1;
                updatePagination();
            });

            $('#filterResetBtn').on('click', function (e) {
                if (!window.location.search) {
                    e.preventDefault();
                    $('#filterSearch').val('');
                    $('#filterSite').val('');
                    $('#filterMaterial').val('');
                    currentPage = 1;
                    updatePagination();
                }
            });

            // Run initial pagination
            updatePagination();
        });
    </script>
@endpush
