@extends('layouts.app')

@section('title', 'Purchases & Material Inward')
@section('page_title', 'Purchases & Inward (பொருட்கள் கொள்முதல் & வரவு)')

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
    </style>
@endpush

@php
    $suppliersMap = [];
    foreach ($suppliers as $s) {
        $mats = $s->materials->map(function ($m) {
            $unitName = 'Nos';
            if ($m->unit instanceof \App\Models\Unit) {
                $unitName = $m->unit->name_en ?: ($m->unit->code ?: 'Nos');
            } elseif (is_string($m->unit) && trim($m->unit) !== '') {
                $unitName = trim($m->unit);
            }
            return [
                'id' => $m->id,
                'name' => $m->name_en,
                'tamil' => $m->name_ta,
                'unit' => $unitName,
                'price' => (float) ($m->pivot->price ?? 0),
            ];
        })->values()->all();

        $suppliersMap[$s->id] = [
            'id' => $s->id,
            'name' => $s->name,
            'materials' => $mats,
        ];
    }

    $allMaterialsList = ($allMaterials ?? \App\Models\Material::where('status', 'Active')->with('unit')->orderBy('name_en')->get())->map(function ($m) {
        $unitName = 'Nos';
        if ($m->unit instanceof \App\Models\Unit) {
            $unitName = $m->unit->name_en ?: ($m->unit->code ?: 'Nos');
        } elseif (is_string($m->unit) && trim($m->unit) !== '') {
            $unitName = trim($m->unit);
        }
        return [
            'id' => $m->id,
            'name' => $m->name_en,
            'tamil' => $m->name_ta,
            'unit' => $unitName,
            'unit_id' => $m->unit_id,
            'price' => 0,
        ];
    })->values()->all();
@endphp

@section('content')
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Material Purchases & Inwards (பொருட்கள் கொள்முதல் மேலாண்மை)</h5>
                    <p class="text-muted small mb-0">Manage purchase orders, material dispatches, inward deliveries, and suppliers.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#purchaseModal"
                        id="addPurchaseBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add New Purchase
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stat Cards -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Total Purchases</span>
                        <div class="stat-icon-wrapper bg-income-subtle">
                            <i class="bi bi-cart-check-fill"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark" id="statTotalPurchases">₹{{ number_format((float) ($stats['total_value'] ?? 0), 2) }}</h4>
                    <div class="small text-muted mt-1" id="statOrderSubtitle">Across {{ (int) ($stats['total_count'] ?? 0) }} purchase orders</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Purchase Orders</span>
                        <div class="stat-icon-wrapper bg-cash-subtle">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-success" id="statTotalCount">{{ (int) ($stats['total_count'] ?? 0) }} Orders</h4>
                    <div class="small text-success mt-1"><i class="bi bi-check2-circle me-1"></i>Recorded & Inwarded</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Destination Sites</span>
                        <div class="stat-icon-wrapper bg-expense-subtle">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-primary" id="statTotalSites">{{ (int) ($stats['distinct_sites_count'] ?? 0) }} Sites</h4>
                    <div class="small text-muted mt-1"><i class="bi bi-buildings me-1"></i>Active project sites</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card-stat h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-semibold">Suppliers Involved</span>
                        <div class="stat-icon-wrapper bg-workers-subtle">
                            <i class="bi bi-truck-flatbed"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-0 text-dark" id="statTotalSuppliers">{{ (int) ($stats['distinct_suppliers_count'] ?? 0) }} Suppliers</h4>
                    <div class="small text-muted mt-1">Active supplier partners</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="purchaseSearchInput"
                            placeholder="Search Bill No, Material, Supplier...">
                    </div>
                </div>
                <!-- Site Filter -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="siteFilter">
                        <option value="">All Project Sites (அனைத்து தளங்கள்)</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->name }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <!-- Supplier Filter -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="supplierFilter">
                        <option value="">All Suppliers (அனைத்து சப்ளையர்கள்)</option>
                        @foreach ($suppliers as $supplierOption)
                            <option value="{{ $supplierOption->name }}">{{ $supplierOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
                <!-- Counter Badge -->
                <div class="col-6 col-md-2">
                    <span class="small text-muted" id="purchaseCountBadge">Showing <strong class="text-dark" id="visiblePurchaseCount">{{ $purchases->count() }}</strong> orders</span>
                </div>
            </div>
        </div>

        <!-- Purchases Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" id="purchasesTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Bill No & Date</th>
                            <th>Project Site (தளம்)</th>
                            <th>Supplier (சப்ளையர்)</th>
                            <th>Material & Quantity (பொருள் & அளவு)</th>
                            <th>Total Bill (ரூ.)</th>
                            <th>Status (நிலை)</th>
                            <th class="text-end" style="width: 70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="purchasesTableBody">
                        @forelse ($purchases as $idx => $po)
                            @php
                                $firstItem = $po->items->first();
                                $materialNames = $po->items->pluck('material_name')->filter()->implode(', ');
                                $itemsJson = $po->items->map(function ($it) {
                                    return [
                                        'id' => $it->id,
                                        'material_id' => $it->material_id,
                                        'material' => $it->material_name,
                                        'material_name' => $it->material_name,
                                        'qty' => (float) $it->quantity,
                                        'quantity' => (float) $it->quantity,
                                        'unit' => $it->unit,
                                        'rate' => (float) $it->unit_price,
                                        'unit_price' => (float) $it->unit_price,
                                        'total' => (float) $it->total_price,
                                        'total_price' => (float) $it->total_price,
                                    ];
                                })->values()->toJson();

                                $badgeClass = match ($po->status) {
                                    'Order Completed' => 'bg-success-subtle text-success border border-success-subtle',
                                    'Partial Received' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                    'Ordered' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                    'Order Return' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                    'Order Canceled' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    default => 'bg-light text-dark border',
                                };
                            @endphp
                            <tr class="purchase-row" data-id="{{ $po->id }}" data-bill-no="{{ $po->po_number }}" data-date="{{ $po->order_date?->format('Y-m-d') }}"
                                data-site="{{ $po->projectSite?->name }}" data-site-id="{{ $po->project_site_id }}"
                                data-supplier="{{ $po->supplier_name }}" data-supplier-id="{{ $po->supplier_id }}"
                                data-material="{{ $materialNames }}" data-unit="{{ $firstItem?->unit ?? 'Nos' }}"
                                data-qty="{{ $firstItem?->quantity ?? 1 }}" data-rate="{{ $firstItem?->unit_price ?? 0 }}"
                                data-total="{{ (float) $po->total_amount }}" data-items='{{ $itemsJson }}'
                                data-status="{{ $po->status }}" data-status-reason="{{ $po->status_reason }}"
                                data-notes="{{ $po->notes }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $po->po_number }}</div>
                                    <small class="text-muted"><i class="bi bi-calendar3 me-1"></i>{{ $po->order_date?->format('d M Y') }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $po->projectSite?->name ?? 'N/A' }}</div>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $po->projectSite?->location ?? 'Site Inward' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-primary">{{ $po->supplier_name }}</div>
                                    @if($po->source_type === 'Own Warehouse')
                                        <span class="badge bg-secondary-subtle text-dark border"><i class="bi bi-boxes me-1"></i>Own Warehouse</span>
                                    @endif
                                </td>
                                <td>
                                    @if($po->items->count() === 1)
                                        <div class="fw-bold text-dark">{{ $firstItem?->material_name }}</div>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-box me-1"></i>{{ (float) $firstItem?->quantity }} {{ $firstItem?->unit }} @ ₹{{ number_format((float) $firstItem?->unit_price, 2) }}</span>
                                    @else
                                        <div class="fw-bold text-dark">{{ $materialNames }}</div>
                                        <div class="mt-1 d-flex flex-wrap gap-1">
                                            @foreach($po->items as $it)
                                                <span class="badge bg-light text-dark border"><i class="bi bi-box me-1"></i>{{ (float) $it->quantity }} {{ $it->unit }} {{ $it->material_name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold text-dark fs-6">₹{{ number_format((float) $po->total_amount, 2) }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">{{ $po->status }}</span>
                                    @if($po->status_reason)
                                        <div class="small text-muted mt-1 text-truncate" style="max-width: 140px;" title="{{ $po->status_reason }}"><i class="bi bi-info-circle me-1 text-danger"></i>{{ $po->status_reason }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="{{ route('purchases.show', $po->encrypted_id) }}"><i class="bi bi-eye text-info me-2"></i>View Details</a></li>
                                            <li><button class="dropdown-item edit-purchase-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li><button class="dropdown-item text-danger delete-purchase-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyPurchasesRow">
                                <td colspan="8" class="text-center text-muted py-4">No purchases recorded yet.</td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching purchase orders found for your search or filter criteria.
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
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, $purchases->count()) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $purchases->count() }}</strong> purchase orders
                    </span>
                </div>
                <nav aria-label="Purchases table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Purchase Add / Edit Modal -->
    <div class="modal fade" id="purchaseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="purchaseModalTitle">Add New Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="purchaseForm" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="purchaseEditId">
                        <div class="row g-3">
                            <!-- Date & Bill No -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="purDate">Purchase Date (தேதி) <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="purDate" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="errDate"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="purBillNo">PO / Invoice No. (கொள்முதல் ஆணை எண்) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted font-monospace"><i class="bi bi-hash"></i></span>
                                    <input type="text" class="form-control font-monospace fw-bold bg-light" id="purBillNo"
                                        placeholder="PO-{{ date('Y') }}-001" readonly>
                                </div>
                                <div class="invalid-feedback" id="errBillNo"></div>
                                <small class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-lock me-1"></i>Auto-generated unique invoice / PO number (Read-only)</small>
                            </div>

                            <!-- Site & Supplier -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="purSite">Destination Project Site (தளம்) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="purSite">
                                    <option value="" disabled selected>-- Select Project Site --</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->name }}" data-id="{{ $site->id }}">{{ $site->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errSite"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="purSupplier">Supplier (சப்ளையர்) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="purSupplier" name="supplier_id">
                                    <option value="" disabled selected>-- Select Supplier (சப்ளையரை தேர்வு செய்க) --</option>
                                    @foreach ($suppliers as $supplierOption)
                                        <option value="{{ $supplierOption->id }}" data-id="{{ $supplierOption->id }}" data-name="{{ $supplierOption->name }}">{{ $supplierOption->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errSupplier"></div>
                            </div>

                            <!-- Purchase Order Status & Reason Input -->
                            <!-- <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="purStatus">Purchase Order Status (ஆர்டர் நிலை) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="purStatus">
                                    <option value="Ordered">Ordered (ஆர்டர் செய்யப்பட்டது)</option>
                                    <option value="Partial Received">Partial Received (பகுதி பெறப்பட்டது)</option>
                                    <option value="Order Completed" selected>Order Completed (முழுவதும் பெறப்பட்டது)</option>
                                    <option value="Order Return">Order Return (பொருட்கள் திருப்பப்பட்டது)</option>
                                    <option value="Order Canceled">Order Canceled (ஆர்டர் ரத்து செய்யப்பட்டது)</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
                            </div> -->
                            <div class="col-12 col-md-6" id="purReasonContainer" style="display: none;">
                                <label class="form-label small fw-semibold text-danger" id="purReasonLabel" for="purReason">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Reason (காரணம்) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control border-danger" id="purReason"
                                    placeholder="Enter reason for return / cancellation">
                                <div class="invalid-feedback" id="errReason"></div>
                            </div>

                            <!-- Material, Quantity & Unit Section (Multiple sets with Add and Delete buttons) -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                    <div>
                                        <label class="form-label small fw-bold mb-0 text-dark">
                                            <i class="bi bi-box-seam text-primary me-1"></i>Materials, Quantity & Units (பொருட்கள், அளவு & அளவீட்டு அலகு) <span class="text-danger">*</span>
                                        </label>
                            <div class="text-muted" style="font-size: 0.75rem;">All Material Master items are available for any selected supplier.</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-success btn-sm px-2 py-1" id="quickAddMaterialBtn" title="Create new material in Material Master without leaving this page">
                                            <i class="bi bi-plus-circle-fill me-1"></i> + New Material (+ புதிய பொருள்)
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1" id="addMaterialUnitRowBtn">
                                            <i class="bi bi-plus-circle me-1"></i> Add Material Item
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-2 text-muted small fw-semibold px-2 mb-1 d-none d-md-flex" style="font-size: 0.78rem;">
                                    <div class="col-md-4">Material / Item (பொருள்) <span class="text-danger">*</span></div>
                                    <div class="col-md-2">Quantity (அளவு) <span class="text-danger">*</span></div>
                                    <div class="col-md-2">Unit of Measurement (அளவீட்டு அலகு) <span class="text-danger">*</span></div>
                                    <div class="col-md-3">Rate / Price (விலை ₹) <span class="text-danger">*</span></div>
                                    <div class="col-md-1 text-end">Action</div>
                                </div>

                                <div id="materialUnitRowsContainer" class="d-flex flex-column gap-2 mb-2">
                                    <!-- Dynamic material, quantity and unit sets appended here -->
                                </div>
                                <div class="invalid-feedback d-block" id="errMaterials" style="display:none !important;"></div>
                            </div>

                            <!-- Total Amount (editable; auto-filled from material rows) -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0" for="purTotal">
                                        Total Amount (மொத்த தொகை) <span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary" id="recalcPurTotalBtn" style="font-size: 0.75rem;" title="Auto-calculate from Quantity × Master Price">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Auto-calculate
                                        </button>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.72rem;">
                                            <i class="bi bi-pencil me-1"></i>Editable
                                        </span>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold text-dark">₹</span>
                                    <input type="number" min="0" step="any"
                                           class="form-control fw-bold text-dark fs-5 bg-white" id="purTotal"
                                           placeholder="0.00" value="0.00">
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    <i class="bi bi-info-circle me-1"></i>Total amount is editable. You can type or change the final bill amount manually (மொத்த தொகையை நேரடியாக மாற்றலாம்).
                                </small>
                                <div class="invalid-feedback d-block" id="errPurTotal" style="display:none !important;"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">Delivery Notes / Remarks (குறிப்புகள்)</label>
                                <textarea class="form-control" id="purNotes" rows="2"
                                    placeholder="e.g. Checked weight slip, received materials at site."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="savePurBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="savePurSpinner" role="status" aria-hidden="true"></span>
                            <span id="savePurBtnText">Save Purchase Entry</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Quick Add Material Modal (Overlay on top of Purchases Modal) -->
    <div class="modal fade" id="quickAddMaterialModal" tabindex="-1" aria-labelledby="quickAddMaterialModalLabel" aria-hidden="true" style="z-index: 10600;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header py-2 px-3 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);">
                    <h6 class="modal-title fw-bold text-white d-flex align-items-center mb-0" id="quickAddMaterialModalLabel">
                        <i class="bi bi-plus-circle-fill me-2"></i>Quick Add Material to Master (புதிய பொருள் சேர்த்தல்)
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickAddMaterialForm">
                    <div class="modal-body p-3">
                        <div class="alert alert-info py-2 px-3 small mb-3 border-0 bg-primary-subtle text-primary-emphasis">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            This material will automatically be saved to <strong>Material Master</strong> and available immediately here and for future purchases.
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="qmNameEn">Material Name (English) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="qmNameEn" placeholder="e.g. PVC Pipe 2 inch, Asian Paint Apex" required autocomplete="off">
                            <div class="invalid-feedback" id="qmErrNameEn">Please enter material name.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="qmNameTa">Material Name (Tamil / தமிழ்) <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="text" class="form-control form-control-sm" id="qmNameTa" placeholder="e.g. பிவிசி பைப் 2 இன்ச் (Optional)" autocomplete="off">
                            <div class="invalid-feedback" id="qmErrNameTa"></div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="qmUnit">Unit of Measurement <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="qmUnit" required>
                                    @foreach ($units as $u)
                                        <option value="{{ $u->name_en }}" data-id="{{ $u->id }}">{{ $u->name_en }}{{ $u->name_ta ? ' (' . $u->name_ta . ')' : '' }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="qmErrUnit">Please select unit.</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold" for="qmPrice">Unit Rate / Price (₹) <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm" id="qmPrice" placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-0 alert alert-light border py-1 px-2 small text-muted">
                            <i class="bi bi-globe2 text-success me-1"></i>
                            <span>Saved globally to Material Master and available for every supplier.</span>
                        </div>
                    </div>
                    <div class="modal-footer py-2 px-3 bg-light">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary" id="btnSaveQuickMaterial">
                            <i class="bi bi-check-circle me-1"></i> Save to Master & Select
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Display toast message if set before page reload (e.g. after create/update)
            const savedToast = sessionStorage.getItem('purchaseSuccessToast');
            if (savedToast) {
                showToast(savedToast, 'success');
                sessionStorage.removeItem('purchaseSuccessToast');
            }

            // Suppliers and their allocated materials from database
            let allMaterialsMaster = @json($allMaterialsList ?? []);
            const dbUnits = @json($units ?? []);

            let activeRowForQuickMaterial = null;

            let defaultNextPo = @json($nextPoNumber ?? 'PO-' . date('Y') . '-001');

            function getUnitOptionsHtml(selectedUnit = '') {
                let unitOptionsHtml = '';
                const normSelected = (selectedUnit || '').toLowerCase().trim();
                let hasSelected = false;

                dbUnits.forEach(function (u) {
                    const uVal = u.name_en || u.code;
                    const uLabel = u.name_en + (u.name_ta ? ` (${u.name_ta})` : '');
                    const isSel = Boolean(normSelected && (normSelected === uVal.toLowerCase() || normSelected === (u.code || '').toLowerCase()));
                    if (isSel) hasSelected = true;
                    unitOptionsHtml += `<option value="${uVal}" ${isSel ? 'selected' : ''}>${uLabel}</option>`;
                });

                if (selectedUnit && !hasSelected) {
                    unitOptionsHtml = `<option value="${selectedUnit}" selected>${selectedUnit}</option>` + unitOptionsHtml;
                }

                return unitOptionsHtml;
            }

            function getMaterialsForSupplier(supplierId) {
                return allMaterialsMaster;
            }

            function buildMaterialOptionsHtml(materials, selectedId = '') {
                if (!materials || materials.length === 0) {
                    return '<option value="" disabled selected>-- No materials available --</option>';
                }

                let html = '<option value="" disabled ' + (!selectedId ? 'selected' : '') + '>-- Select Material (பொருளை தேர்வு செய்க) --</option>';
                materials.forEach(function (m) {
                    const isSel = Boolean(selectedId && String(selectedId) === String(m.id));
                    const label = m.name + (m.tamil ? ' - ' + m.tamil : '');
                    const rateVal = parseFloat(m.price || 0);
                    const rateDisplay = rateVal > 0 ? ` (₹${rateVal.toLocaleString('en-IN')})` : '';
                    html += `<option value="${m.id}" data-id="${m.id}" data-name="${m.name}" data-tamil="${m.tamil || ''}" data-unit="${m.unit || 'Nos'}" data-rate="${rateVal}" ${isSel ? 'selected' : ''}>${label}${rateDisplay}</option>`;
                });
                return html;
            }

            function appendMaterialUnitRow(materials, selectedMaterialId = '', selectedQty = '', selectedUnit = '', selectedRate = '') {
                const rowUid = 'mat_row_' + Date.now() + '_' + Math.floor(Math.random() * 10000);
                const optionsHtml = buildMaterialOptionsHtml(materials, selectedMaterialId);
                const unitOptionsHtml = getUnitOptionsHtml(selectedUnit);

                let initRate = selectedRate;
                if (initRate === '' || initRate === undefined || initRate === null) {
                    if (selectedMaterialId) {
                        const foundMat = materials.find(m => String(m.id) === String(selectedMaterialId));
                        if (foundMat && parseFloat(foundMat.price) > 0) {
                            initRate = parseFloat(foundMat.price);
                        }
                    }
                }

                const rowHtml = `
                    <div class="material-unit-row card p-2 bg-light-subtle border shadow-none" id="${rowUid}">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-4">
                                <label class="form-label small text-muted mb-1 d-md-none">Material / Item (பொருள்) <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm pur-material-select">
                                    ${optionsHtml}
                                </select>
                                <div class="invalid-feedback">Please select a material.</div>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small text-muted mb-1 d-md-none">Quantity (அளவு) <span class="text-danger">*</span></label>
                                <input type="number" step="any" min="0" class="form-control form-control-sm pur-qty-input" placeholder="Quantity" value="${selectedQty || ''}">
                                <div class="invalid-feedback">Enter valid quantity (> 0).</div>
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label small text-muted mb-1 d-md-none">Unit of Measurement (அளவீட்டு அலகு) <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm pur-unit-select">
                                    ${unitOptionsHtml}
                                </select>
                                <div class="invalid-feedback">Select unit.</div>
                            </div>
                            <div class="col-8 col-md-3">
                                <label class="form-label small text-muted mb-1 d-md-none">Rate / Price (விலை ₹) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text py-0 px-2 bg-light text-muted" style="font-size:0.75rem;">₹</span>
                                    <input type="number" step="any" min="0" class="form-control form-control-sm pur-rate-input" placeholder="Rate (விலை)" value="${initRate !== '' && initRate !== null && initRate !== undefined ? initRate : ''}">
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted" style="font-size:0.68rem;">Unit Price</small>
                                    <small class="pur-line-total-display fw-semibold text-primary" style="font-size: 0.72rem; line-height: 1;">
                                        Total: ₹0.00
                                    </small>
                                </div>
                            </div>
                            <div class="col-4 col-md-1 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm p-1 remove-material-unit-row-btn" title="Delete set of input" style="line-height: 1;">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                $('#materialUnitRowsContainer').append(rowHtml);
                updateRemoveButtonsState();

                const newRow = $(`#${rowUid}`);
                if (selectedMaterialId) {
                    newRow.find('.pur-material-select').val(selectedMaterialId);
                }
                if (selectedUnit) {
                    newRow.find('.pur-unit-select').val(selectedUnit);
                }
                if (selectedQty) {
                    newRow.find('.pur-qty-input').val(selectedQty);
                }
                if (initRate !== '' && initRate !== undefined && initRate !== null) {
                    newRow.find('.pur-rate-input').val(initRate);
                }

                if (typeof window.makeSearchableSelect === 'function') {
                    window.makeSearchableSelect(newRow.find('.pur-material-select'), {
                        placeholder: '-- Select Material (பொருளை தேர்வு செய்க) --',
                        type: 'material',
                        allowNew: true,
                        onAddNew: function (query, select, container, closeMenu) {
                            closeMenu();
                            openQuickAddMaterialModal(query, newRow);
                        }
                    });
                }

                updateRowLineTotal(newRow);

                return newRow;
            }

            function openQuickAddMaterialModal(initialName = '', targetRow = null) {
                activeRowForQuickMaterial = targetRow || $('#materialUnitRowsContainer .material-unit-row:last');
                $('#quickAddMaterialForm')[0].reset();
                $('#qmNameEn').val(initialName).removeClass('is-invalid');
                $('#qmNameTa').val('').removeClass('is-invalid');
                $('#qmPrice').val('');

                const quickModal = new bootstrap.Modal(document.getElementById('quickAddMaterialModal'));
                quickModal.show();
                setTimeout(() => {
                    if (initialName) {
                        $('#qmUnit').focus();
                    } else {
                        $('#qmNameEn').focus();
                    }
                }, 300);
            }

            $('#quickAddMaterialBtn').on('click', function () {
                openQuickAddMaterialModal('', null);
            });

            $('#quickAddMaterialForm').on('submit', function (e) {
                e.preventDefault();
                const nameEn = $('#qmNameEn').val().trim();
                const nameTa = $('#qmNameTa').val().trim();
                const unitVal = $('#qmUnit').val();
                const unitId = $('#qmUnit option:selected').data('id');
                const price = $('#qmPrice').val();

                if (!nameEn) {
                    $('#qmNameEn').addClass('is-invalid');
                    $('#qmErrNameEn').text('Material name is required.');
                    return;
                }

                const btn = $('#btnSaveQuickMaterial');
                const origBtnHtml = btn.html();
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

                $.ajax({
                    url: '{{ route('purchases.quick-create-material') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        name_en: nameEn,
                        name_ta: nameTa,
                        unit: unitVal,
                        unit_id: unitId,
                        price: price
                    },
                    success: function (res) {
                        btn.prop('disabled', false).html(origBtnHtml);
                        if (res.success && res.material) {
                            const newMat = res.material;

                            // 1. Add to allMaterialsMaster
                            const existingIdx = allMaterialsMaster.findIndex(m => String(m.id) === String(newMat.id));
                            if (existingIdx >= 0) {
                                allMaterialsMaster[existingIdx] = newMat;
                            } else {
                                allMaterialsMaster.unshift(newMat);
                            }

                            // 2. Hide modal
                            bootstrap.Modal.getInstance(document.getElementById('quickAddMaterialModal')).hide();

                            // 3. Ensure target row exists
                            let row = activeRowForQuickMaterial;
                            if (!row || !row.length || !document.body.contains(row[0])) {
                                const currentSupplier = $('#purSupplier').val();
                                const currentMats = getMaterialsForSupplier(currentSupplier);
                                row = appendMaterialUnitRow(currentMats);
                            }

                            // 4. Rebuild all material selects in the modal to include new material
                            const currentSupplier = $('#purSupplier').val();
                            const updatedMats = getMaterialsForSupplier(currentSupplier);
                            $('#materialUnitRowsContainer .material-unit-row').each(function () {
                                const curSelect = $(this).find('.pur-material-select');
                                const curVal = curSelect.val();
                                curSelect.html(buildMaterialOptionsHtml(updatedMats, curVal));
                                if (curVal) {
                                    curSelect.val(curVal);
                                }
                                curSelect.trigger('rebuild');
                            });

                            // 5. Select this newly created material in the target row
                            const targetSelect = row.find('.pur-material-select');
                            targetSelect.val(newMat.id).trigger('change');

                            // 6. Auto set unit and rate
                            if (newMat.unit) {
                                const unitSelect = row.find('.pur-unit-select');
                                let unitMatched = false;
                                unitSelect.find('option').each(function () {
                                    if ($(this).val().toLowerCase() === newMat.unit.toLowerCase()) {
                                        unitSelect.val($(this).val());
                                        unitMatched = true;
                                        return false;
                                    }
                                });
                                if (!unitMatched) {
                                    unitSelect.append(`<option value="${newMat.unit}" selected>${newMat.unit}</option>`);
                                    unitSelect.val(newMat.unit);
                                }
                            }

                            if (newMat.price && parseFloat(newMat.price) > 0) {
                                row.find('.pur-rate-input').val(parseFloat(newMat.price));
                            }
                            updateRowLineTotal(row);
                            calculateModalTotals();

                            // 7. Focus quantity input
                            setTimeout(() => {
                                row.find('.pur-qty-input').focus();
                            }, 200);

                            // 8. Show toast
                            showToast(`Material "${newMat.name_en}" saved to Material Master!`, 'success');
                        }
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html(origBtnHtml);
                        const err = xhr.responseJSON;
                        let msg = 'Failed to save material.';
                        if (err && err.errors) {
                            msg = Object.values(err.errors).flat().join('<br>');
                        } else if (err && err.message) {
                            msg = err.message;
                        }
                        showToast(msg, 'error');
                    }
                });
            });

            function updateRemoveButtonsState() {
                const rows = $('#materialUnitRowsContainer .material-unit-row');
                if (rows.length <= 1) {
                    rows.find('.remove-material-unit-row-btn').prop('disabled', true).addClass('opacity-50').attr('title', 'At least one material set is required');
                } else {
                    rows.find('.remove-material-unit-row-btn').prop('disabled', false).removeClass('opacity-50').attr('title', 'Delete this set of input');
                }
            }

            function resetMaterialUnitRows() {
                $('#materialUnitRowsContainer').empty();
                const supplierId = $('#purSupplier').val();
                const materials = getMaterialsForSupplier(supplierId);
                appendMaterialUnitRow(materials);
            }

            // Initialize 1 row in modal container
            resetMaterialUnitRows();

            // When supplier is selected, ONLY load materials allocated to that supplier
            $('#purSupplier').on('change', function () {
                const supplierId = $(this).val();
                const materials = getMaterialsForSupplier(supplierId);

                const rows = $('#materialUnitRowsContainer .material-unit-row');
                if (rows.length === 0) {
                    appendMaterialUnitRow(materials);
                } else {
                    rows.each(function () {
                        const matSelect = $(this).find('.pur-material-select');
                        const prevVal = matSelect.val();
                        matSelect.html(buildMaterialOptionsHtml(materials));

                        if (prevVal && materials.some(m => String(m.id) === String(prevVal))) {
                            matSelect.val(prevVal);
                        } else if (materials.length === 1) {
                            matSelect.val(materials[0].id).trigger('change');
                        } else {
                            matSelect.val('');
                        }
                        matSelect.trigger('rebuild');
                    });
                }
                calculateModalTotals();
            });

            function updateRowLineTotal(row) {
                const qty = parseFloat(row.find('.pur-qty-input').val()) || 0;
                const rate = parseFloat(row.find('.pur-rate-input').val()) || 0;
                const lineTotal = qty * rate;
                row.find('.pur-line-total-display').text('Total: ₹' + lineTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                return lineTotal;
            }

            // Live calculation: fills Total Amount from material rows
            function calculateModalTotals(force = false) {
                let totalAmount = 0;

                $('#materialUnitRowsContainer .material-unit-row').each(function () {
                    const lineTotal = updateRowLineTotal($(this));
                    totalAmount += lineTotal;
                });

                if (force || !isUserEditedTotal) {
                    $('#purTotal').val(Math.round(totalAmount));
                }
            }

            // Automatically select the unit based on selected material & auto-fill rate
            $(document).on('change', '.pur-material-select', function () {
                const row = $(this).closest('.material-unit-row');
                const selectedOpt = $(this).find('option:selected');
                const unit = selectedOpt.data('unit');
                const rate = parseFloat(selectedOpt.data('rate')) || 0;

                if (unit) {
                    const unitSelect = row.find('.pur-unit-select');
                    let matched = false;
                    unitSelect.find('option').each(function () {
                        if ($(this).val().toLowerCase() === unit.toLowerCase() || $(this).text().toLowerCase().includes(unit.toLowerCase())) {
                            unitSelect.val($(this).val());
                            matched = true;
                            return false;
                        }
                    });
                    if (!matched) {
                        unitSelect.append(`<option value="${unit}" selected>${unit}</option>`);
                        unitSelect.val(unit);
                    }
                }

                if (rate > 0) {
                    row.find('.pur-rate-input').val(rate);
                } else if (!row.find('.pur-rate-input').val()) {
                    row.find('.pur-rate-input').val('');
                }

                isUserEditedTotal = false;
                updateRowLineTotal(row);
                calculateModalTotals();
            });

            // Quantity or Rate changed: recalculate total live
            $(document).on('input', '.pur-qty-input, .pur-rate-input', function () {
                const row = $(this).closest('.material-unit-row');
                updateRowLineTotal(row);
                isUserEditedTotal = false;
                calculateModalTotals();
            });

            // Add new material row
            $('#addMaterialUnitRowBtn').on('click', function () {
                const supplierId = $('#purSupplier').val();
                if (!supplierId) {
                    showToast('Please select a supplier first to load their material list.', 'warning');
                    $('#purSupplier').focus();
                    return;
                }

                const materials = getMaterialsForSupplier(supplierId);
                appendMaterialUnitRow(materials);
            });

            // Remove material row
            $(document).on('click', '.remove-material-unit-row-btn', function () {
                const rows = $('#materialUnitRowsContainer .material-unit-row');
                if (rows.length > 1) {
                    $(this).closest('.material-unit-row').fadeOut(150, function () {
                        $(this).remove();
                        updateRemoveButtonsState();
                        calculateModalTotals();
                    });
                } else {
                    const singleRow = rows.first();
                    singleRow.find('.pur-material-select').val('');
                    singleRow.find('.pur-qty-input').val('');
                    singleRow.find('.pur-rate-input').val('');
                    singleRow.find('.pur-line-total-display').text('Total: ₹0.00');
                    calculateModalTotals();
                    showToast('Set of input cleared.', 'info');
                }
            });

            let isUserEditedTotal = false;

            $('#purTotal').on('input', function () {
                isUserEditedTotal = true;
            });

            $(document).on('click', '#recalcPurTotalBtn', function () {
                isUserEditedTotal = false;
                calculateModalTotals(true);
                showToast('Total amount recalculated from items.', 'info');
            });

            // Reason visibility during return / cancel
            function updateStatusReasonVisibility() {
                const status = 'Ordered';
                const reasonContainer = $('#purReasonContainer');
                const reasonInput = $('#purReason');
                const reasonLabel = $('#purReasonLabel');

                if (status === 'Order Return' || status === 'Order Canceled') {
                    if (status === 'Order Return') {
                        reasonLabel.html('<i class="bi bi-arrow-return-left text-danger me-1"></i>Return Reason (திரும்பியதற்கான காரணம்) <span class="text-danger">*</span>');
                        reasonInput.attr('placeholder', 'e.g. Quality defect, damaged items, rejected at site');
                    } else {
                        reasonLabel.html('<i class="bi bi-x-circle text-danger me-1"></i>Cancellation Reason (ரத்து செய்ததற்கான காரணம்) <span class="text-danger">*</span>');
                        reasonInput.attr('placeholder', 'e.g. Order canceled by site engineer, supplier unavailable');
                    }
                    reasonContainer.stop(true, true).slideDown(200);
                } else {
                    reasonContainer.stop(true, true).slideUp(200);
                    reasonInput.val('');
                }
            }

            $('#purStatus').on('change', updateStatusReasonVisibility);

            // Recalculate summary stats: ONLY count actual purchase rows with class .purchase-row
            function recalculateSummaryStats(rowsToCount) {
                let totalPur = 0;
                let count = 0;
                const sites = new Set();
                const suppliers = new Set();

                const rows = rowsToCount !== undefined ? rowsToCount : $('#purchasesTableBody tr.purchase-row').toArray();

                rows.forEach(function (row) {
                    totalPur += parseFloat($(row).data('total')) || 0;
                    const s = $(row).data('site');
                    const sup = $(row).data('supplier');
                    if (s) sites.add(s);
                    if (sup) suppliers.add(sup);
                    count++;
                });

                $('#statTotalPurchases').text('₹' + totalPur.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                $('#statTotalCount').text(count + ' Orders');
                $('#statOrderSubtitle').text(`Across ${count} purchase order${count === 1 ? '' : 's'}`);
                $('#statTotalSites').text(`${sites.size} Site${sites.size === 1 ? '' : 's'}`);
                $('#statTotalSuppliers').text(`${suppliers.size} Supplier${suppliers.size === 1 ? '' : 's'}`);
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function getMatchingRows() {
                const query = ($('#purchaseSearchInput').val() || '').toLowerCase().trim();
                const site = $('#siteFilter').val();
                const supplier = $('#supplierFilter').val();
                const matching = [];

                $('#purchasesTableBody tr.purchase-row').each(function () {
                    const billNo = String($(this).data('bill-no') || '').toLowerCase();
                    const rowSite = String($(this).data('site') || '');
                    const rowSupplier = String($(this).data('supplier') || '');
                    const material = String($(this).data('material') || '').toLowerCase();
                    const rowStatus = String($(this).data('status') || '');
                    const rowReason = String($(this).data('status-reason') || '').toLowerCase();

                    const matchQuery = !query || billNo.includes(query) || material.includes(query) || rowSupplier.toLowerCase().includes(query) || rowSite.toLowerCase().includes(query) || rowStatus.toLowerCase().includes(query) || rowReason.includes(query);
                    const matchSite = !site || rowSite === site;
                    const matchSupplier = !supplier || rowSupplier === supplier;

                    if (matchQuery && matchSite && matchSupplier) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#purchasesTableBody tr.purchase-row').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#purchasesTableBody tr.purchase-row').hide();

                if (totalCount === 0) {
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#purchaseCountBadge').html('Showing <strong class="text-dark">0</strong> orders');
                    recalculateSummaryStats([]);
                    return;
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#purchaseCountBadge').html(`Showing <strong class="text-dark">0</strong> of <strong class="text-dark">${totalCount}</strong> orders`);
                    recalculateSummaryStats([]);
                    return;
                } else {
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').removeClass('d-none');
                }

                // Slice visible rows for current page
                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, filteredCount);

                for (let i = startIndex; i < endIndex; i++) {
                    $(matchingRows[i]).show();
                }

                // Re-index serial cell across all matching rows
                matchingRows.forEach(function (row, idx) {
                    $(row).find('.serial-cell').text(String(idx + 1).padStart(2, '0'));
                });

                // Update summary footer text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' purchase orders';
                $('#paginationSummary').html(summaryHtml);

                $('#purchaseCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> orders`);

                renderPaginationButtons(totalPages, currentPage);
                recalculateSummaryStats(matchingRows);
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

            // Per page dropdown change
            $('#perPageSelect').on('change', function () {
                perPage = parseInt($(this).val()) || 10;
                currentPage = 1;
                updatePagination();
            });

            // Pagination button click delegation
            $(document).on('click', '#paginationNav .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    currentPage = parseInt(page);
                    updatePagination();
                }
            });

            $('#purchaseSearchInput, #siteFilter, #supplierFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#purchaseSearchInput').val('');
                $('#siteFilter').val('');
                $('#supplierFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Initial pagination and KPI calculation run
            updatePagination();

            function clearPurchaseFormErrors() {
                $('#purchaseForm .is-invalid').removeClass('is-invalid');
                $('#purchaseForm .invalid-feedback').text('').hide();
                $('#errMaterials').text('').attr('style', 'display:none !important;');
                $('#errPurTotal').text('').attr('style', 'display:none !important;');
            }

            function displayPurchaseFormErrors(errors) {
                clearPurchaseFormErrors();
                if (!errors) return;

                if (errors.order_date) {
                    $('#purDate').addClass('is-invalid');
                    $('#errDate').text(errors.order_date[0]).show();
                }
                if (errors.po_number) {
                    $('#purBillNo').addClass('is-invalid');
                    $('#errBillNo').text(errors.po_number[0]).show();
                }
                if (errors.project_site_id || errors.site_name) {
                    const err = (errors.project_site_id || errors.site_name)[0];
                    $('#purSite').addClass('is-invalid');
                    $('#errSite').text(err).show();
                }
                if (errors.supplier_id || errors.supplier_name) {
                    const err = (errors.supplier_id || errors.supplier_name)[0];
                    $('#purSupplier').addClass('is-invalid');
                    $('#errSupplier').text(err).show();
                }
                if (errors.status) {
                    $('#purStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
                if (errors.status_reason) {
                    $('#purReason').addClass('is-invalid');
                    $('#errReason').text(errors.status_reason[0]).show();
                }
                if (errors.total_amount) {
                    $('#purTotal').addClass('is-invalid');
                    $('#errPurTotal').text(errors.total_amount[0]).removeAttr('style').show();
                }
                if (errors.notes) {
                    $('#purNotes').addClass('is-invalid');
                }

                if (errors.items) {
                    $('#errMaterials').text(errors.items[0]).removeAttr('style').show();
                }

                // Check row-level item errors: items.0.material_id, items.0.quantity, items.0.unit, etc.
                const rows = $('#materialUnitRowsContainer .material-unit-row');
                Object.keys(errors).forEach(function (key) {
                    const match = key.match(/^items\.(\d+)\.(.+)$/);
                    if (match) {
                        const index = parseInt(match[1], 10);
                        const field = match[2];
                        const row = rows.eq(index);
                        if (row.length) {
                            if (field === 'material_id') {
                                const sel = row.find('.pur-material-select');
                                sel.addClass('is-invalid');
                                sel.siblings('.invalid-feedback').text(errors[key][0]).show();
                            } else if (field === 'quantity') {
                                const inp = row.find('.pur-qty-input');
                                inp.addClass('is-invalid');
                                inp.siblings('.invalid-feedback').text(errors[key][0]).show();
                            } else if (field === 'unit') {
                                const sel = row.find('.pur-unit-select');
                                sel.addClass('is-invalid');
                                sel.siblings('.invalid-feedback').text(errors[key][0]).show();
                            }
                        }
                    }
                });
            }

            $('#purchaseModal').on('hidden.bs.modal', function () {
                isUserEditedTotal = false;
                clearPurchaseFormErrors();
            });

            $('#purchaseForm input, #purchaseForm select, #purchaseForm textarea').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
                if ($(this).attr('id') === 'purReason') {
                    $('#errReason').text('').hide();
                }
            });

            $(document).on('input change', '.pur-material-select, .pur-qty-input, .pur-unit-select', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
                $('#errMaterials').text('').attr('style', 'display:none !important;');
            });

            $('#addPurchaseBtn').on('click', function () {
                isUserEditedTotal = false;
                $('#purchaseModalTitle').text('Add New Purchase');
                $('#savePurBtnText').text('Save Purchase Entry');
                $('#purchaseEditId').val('');
                $('#purchaseForm')[0].reset();
                clearPurchaseFormErrors();
                $('#purDate').val(new Date().toISOString().split('T')[0]);
                $('#purBillNo').val(defaultNextPo);

                // Fetch fresh next PO sequence via API
                $.get('{{ route("purchases.next-po-number") }}', function (res) {
                    if (res && res.po_number) {
                        defaultNextPo = res.po_number;
                        $('#purBillNo').val(res.po_number);
                    }
                });

                $('#purStatus').val('Ordered');
                $('#purReason').val('');
                $('#purReasonContainer').hide();
                $('#purTotal').val('0');
                resetMaterialUnitRows();
            });

            // EDIT purchase order: correctly appends all materials and sets values
            $(document).on('click', '.edit-purchase-btn', function () {
                const tr = $(this).closest('tr');
                $('#purchaseModalTitle').text('Edit Purchase Entry');
                $('#savePurBtnText').text('Update Purchase Entry');
                $('#purchaseEditId').val(tr.data('id'));
                clearPurchaseFormErrors();
                $('#purDate').val(tr.data('date'));
                $('#purBillNo').val(tr.data('bill-no'));
                $('#purSite').val(tr.data('site'));

                const supplierId = tr.data('supplier-id');
                const supplierName = tr.data('supplier');

                if (supplierId && $(`#purSupplier option[value="${supplierId}"]`).length) {
                    $('#purSupplier').val(supplierId);
                } else {
                    $('#purSupplier option').each(function () {
                        if ($(this).data('name') === supplierName || $(this).text().trim() === supplierName) {
                            $('#purSupplier').val($(this).val());
                            return false;
                        }
                    });
                }

                const currentStatus = tr.data('status') || 'Order Completed';
                const currentReason = tr.data('status-reason') || '';
                $('#purStatus').val(currentStatus);
                if (currentStatus === 'Order Return' || currentStatus === 'Order Canceled') {
                    $('#purReasonContainer').show();
                    $('#purReason').val(currentReason);
                    if (currentStatus === 'Order Return') {
                        $('#purReasonLabel').html('<i class="bi bi-arrow-return-left text-danger me-1"></i>Return Reason (திரும்பியதற்கான காரணம்) <span class="text-danger">*</span>');
                        $('#purReason').attr('placeholder', 'e.g. Quality defect, damaged items, rejected at site');
                    } else {
                        $('#purReasonLabel').html('<i class="bi bi-x-circle text-danger me-1"></i>Cancellation Reason (ரத்து செய்ததற்கான காரணம்) <span class="text-danger">*</span>');
                        $('#purReason').attr('placeholder', 'e.g. Order canceled by site engineer, supplier unavailable');
                    }
                } else {
                    $('#purReasonContainer').hide();
                    $('#purReason').val('');
                }

                const currentSupplierId = $('#purSupplier').val();
                let materials = getMaterialsForSupplier(currentSupplierId).slice(); // copy

                let items = tr.data('items');
                if (typeof items === 'string') {
                    try { items = JSON.parse(items); } catch(e) { items = []; }
                }

                isUserEditedTotal = false;

                // Ensure all materials from this purchase order exist in the options list and keep their prices
                if (Array.isArray(items) && items.length > 0) {
                    items.forEach(function (it) {
                        const itMatId = it.material_id;
                        const itMatName = it.material_name || it.material || '';
                        const itRate = parseFloat(it.unit_price !== undefined ? it.unit_price : (it.rate || 0));
                        const foundMat = materials.find(m => String(m.id) === String(itMatId));
                        if (foundMat) {
                            if (itRate > 0 && (!foundMat.price || parseFloat(foundMat.price) === 0)) {
                                foundMat.price = itRate;
                            }
                        } else if (itMatId) {
                            materials.push({
                                id: itMatId,
                                name: itMatName,
                                tamil: '',
                                unit: it.unit || 'Nos',
                                price: itRate
                            });
                        }
                    });
                }

                $('#materialUnitRowsContainer').empty();
                if (Array.isArray(items) && items.length > 0) {
                    items.forEach(function (it) {
                        const matId = it.material_id || it.material;
                        const qty = it.quantity || it.qty || 1;
                        const unit = it.unit || 'Nos';
                        const rate = it.unit_price !== undefined ? it.unit_price : (it.rate || 0);
                        appendMaterialUnitRow(materials, matId, qty, unit, rate);
                    });
                } else {
                    appendMaterialUnitRow(materials);
                }

                $('#purNotes').val(tr.data('notes'));

                // Calculate modal totals from items
                calculateModalTotals();

                // If DB had a specific total that differs from line items sum, show it
                const savedTotal = parseFloat(tr.data('total'));
                if (!isNaN(savedTotal) && savedTotal > 0 && Math.round(savedTotal) !== Math.round(parseFloat($('#purTotal').val()) || 0)) {
                    $('#purTotal').val(Math.round(savedTotal));
                    isUserEditedTotal = true;
                }

                const modal = new bootstrap.Modal(document.getElementById('purchaseModal'));
                modal.show();
            });

            // Form Submit (Create or Update)
            $('#purchaseForm').on('submit', function (e) {
                e.preventDefault();
                clearPurchaseFormErrors();

                const editId = $('#purchaseEditId').val();
                const isEdit = Boolean(editId);
                const date = $('#purDate').val();
                const billNo = $('#purBillNo').val().trim();
                const site = $('#purSite').val();
                const siteOpt = $('#purSite option:selected');
                const siteId = siteOpt.data('id') || null;
                const supplierId = $('#purSupplier').val();
                const supplierName = $('#purSupplier option:selected').data('name') || $('#purSupplier option:selected').text();
                const status = $('#purStatus').val() || 'Ordered';
                const reason = (status === 'Order Return' || status === 'Order Canceled') ? $('#purReason').val().trim() : '';

                const items = [];
                const rows = $('#materialUnitRowsContainer .material-unit-row');
                rows.each(function () {
                    const matSelect = $(this).find('.pur-material-select');
                    const qtyInput = $(this).find('.pur-qty-input');
                    const unitSelect = $(this).find('.pur-unit-select');

                    const matId = matSelect.val();
                    const selectedMatOpt = matSelect.find('option:selected');
                    const matName = selectedMatOpt.data('name') || selectedMatOpt.text();
                    const qtyVal = qtyInput.val();
                    const qty = parseFloat(qtyVal);
                    const unt = unitSelect.val();
                    const rateInput = $(this).find('.pur-rate-input');
                    const enteredRate = parseFloat(rateInput.val());
                    const rate = (!isNaN(enteredRate) && enteredRate >= 0) ? enteredRate : (parseFloat(selectedMatOpt.data('rate')) || 0);

                    items.push({
                        material_id: matId ? parseInt(matId, 10) : null,
                        material_name: (matId && matName) ? matName : null,
                        quantity: qtyVal !== '' && !isNaN(qty) ? qty : null,
                        unit: unt || null,
                        unit_price: rate,
                        total_price: Math.round(((qtyVal !== '' && !isNaN(qty) ? qty : 0) * rate))
                    });
                });

                let calculatedTotal = 0;
                items.forEach(function (i) {
                    if (i.total_price) {
                        calculatedTotal += i.total_price;
                    }
                });

                // Get Total Amount from editable input field (if empty or invalid, fallback to calculated total)
                const rawPurTotal = String($('#purTotal').val() || '').replace(/,/g, '').trim();
                const enteredTotal = parseFloat(rawPurTotal);
                const finalTotal = (!isNaN(enteredTotal) && enteredTotal >= 0)
                    ? enteredTotal
                    : calculatedTotal;

                const notes = $('#purNotes').val().trim();

                const payload = {
                    po_number: billNo || null,
                    order_date: date || null,
                    project_site_id: siteId ? parseInt(siteId, 10) : null,
                    site_name: site || null,
                    supplier_id: supplierId ? parseInt(supplierId, 10) : null,
                    supplier_name: (supplierId && supplierName) ? supplierName : (supplierName && !supplierName.includes('--') ? supplierName : null),
                    source_type: 'Third-Party Supplier',
                    status: status,
                    status_reason: reason || null,
                    total_amount: finalTotal,
                    notes: notes || null,
                    items: items
                };

                const url = isEdit ? '{{ route("purchases.update", ":id") }}'.replace(':id', editId) : `{{ route('purchases.store') }}`;
                const method = isEdit ? 'POST' : 'POST';

                const $btn = $('#savePurBtn');
                const $spinner = $('#savePurSpinner');
                const $btnText = $('#savePurBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    type: method,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Purchase Entry' : 'Save Purchase Entry');

                        $('#purchaseModal').modal('hide');
                        sessionStorage.setItem('purchaseSuccessToast', res.message || `Purchase order "${billNo}" saved successfully!`);
                        window.location.reload();
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Purchase Entry' : 'Save Purchase Entry');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayPurchaseFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            let errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to save purchase.';
                            showToast(errMsg, 'danger');
                        }
                    }
                });
            });

            $(document).on('click', '.delete-purchase-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const billNo = tr.data('bill-no');
                confirmDelete(`Delete purchase bill "${billNo}"? (கொள்முதல் ரசீதை நீக்க விரும்புகிறீர்களா?)`, function () {
                    $.ajax({
                        url: '{{ route("purchases.destroy", ":id") }}'.replace(':id', id),
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        success: function () {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                recalculateSummaryStats();
                                showToast(`Purchase bill "${billNo}" deleted.`, 'danger');
                            });
                        },
                        error: function () {
                            showToast('Failed to delete purchase.', 'danger');
                        }
                    });
                });
            });
        });
    </script>
@endpush
