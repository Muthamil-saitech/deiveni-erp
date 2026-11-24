@extends('layouts.app')

@section('title', 'Suppliers Master')
@section('page_title', 'Suppliers Master')

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

@section('content')
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Material Suppliers Directory</h5>
                    <p class="text-muted small mb-0">Suppliers for Cement, Steel, Blue Metal, Sand, Bricks, Tiles & Hardware
                        with material grades & rates.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#supplierModal"
                        id="addSupplierBtn">
                        <i class="bi bi-truck-front-fill me-1"></i> Add New Supplier
                    </button>
                </div>
            </div>
        </div>

        <!-- Search & Status Filter Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="supplierSearchInput"
                            placeholder="Search by Supplier Name, GST No, Location, Material, Grade...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="supplierStatusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="supplierScopeFilter">
                        <option value="">All Payment Scope</option>
                        <option value="1">Site Based</option>
                        <option value="0">Total</option>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                        title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
                <div class="col-12 col-md-3 text-md-end">
                    <span class="small text-muted" id="supplierCountBadge">Showing <strong
                            class="text-dark">{{ $suppliers->count() }}</strong> suppliers</span>
                </div>
            </div>
        </div>

        <!-- Suppliers Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="suppliersTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Supplier / Company Name</th>
                            <th>Contact & Phone</th>
                            <th>Address / Location</th>
                            <th>GST Number</th>
                            <th>Materials</th>
                            <th class="text-center" style="width: 140px;">Site Based</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 125px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody">
                        @forelse ($suppliers as $index => $supplier)
                            @php
                                $purchaseOrdersCount = (int) ($supplier->purchase_orders_count ?? 0);
                                $inUse = $purchaseOrdersCount > 0;
                                $isSiteBased = (bool) ($supplier->is_site_based ?? true);
                                $materialsJson = json_encode($supplier->materials->map(function ($m) {
                                    return [
                                        'id' => $m->id,
                                        'name' => $m->name_en,
                                        'tamil' => $m->name_ta,
                                        'unit' => $m->unit?->name_en ?? (string) $m->unit,
                                        'grade' => $m->pivot->grade ?? '1st Quality',
                                        'price' => (float) ($m->pivot->price ?? 0),
                                    ];
                                })->values()->all());
                            @endphp
                            <tr data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                data-contact="{{ $supplier->contact_person }}" data-phone="{{ $supplier->phone }}"
                                data-address="{{ $supplier->address }}" data-gst="{{ $supplier->gst_number }}"
                                data-materials="{{ $materialsJson }}" data-status="{{ $supplier->status }}"
                                data-is-site-based="{{ $isSiteBased ? '1' : '0' }}"
                                data-purchase-orders-count="{{ $purchaseOrdersCount }}" data-in-use="{{ $inUse ? '1' : '0' }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark row-supplier-name">{{ $supplier->name }}</div>
                                    <div class="d-flex align-items-center gap-1 flex-wrap mt-0.5">
                                        <small class="text-muted row-supplier-contact">
                                            <i class="bi bi-person me-1"></i>{{ $supplier->contact_person ?: '—' }}
                                        </small>
                                        @if ($inUse)
                                            <span class="badge bg-light text-secondary border border-secondary-subtle"
                                                style="font-size: 0.68rem;"
                                                title="Used in {{ $purchaseOrdersCount }} {{ $purchaseOrdersCount === 1 ? 'purchase order' : 'purchase orders' }}">
                                                <i class="bi bi-receipt me-0.5"></i>{{ $purchaseOrdersCount }}
                                                {{ $purchaseOrdersCount === 1 ? 'purchase order' : 'purchase orders' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="row-supplier-phone">
                                    <i class="bi bi-telephone text-muted me-1"></i>{{ $supplier->phone }}
                                </td>
                                <td class="row-supplier-address">{{ $supplier->address ?: '—' }}</td>
                                <td class="row-supplier-gst">
                                    @if ($supplier->gst_number)
                                        <span class="font-monospace text-secondary small">{{ $supplier->gst_number }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="row-supplier-materials">
                                    @if ($supplier->materials->isNotEmpty())
                                        <span
                                            class="text-dark fw-medium">{{ $supplier->materials->pluck('name_en')->filter()->join(', ') }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="text-center row-supplier-site-based">
                                    <div class="form-check form-switch d-inline-flex align-items-center gap-1 justify-content-center mb-0">
                                        <input class="form-check-input supplier-site-based-toggle" type="checkbox" role="switch"
                                            data-id="{{ $supplier->id }}"
                                            data-name="{{ $supplier->name }}"
                                            id="toggleSiteBased_{{ $supplier->id }}"
                                            {{ $isSiteBased ? 'checked' : '' }}
                                            style="cursor: pointer; width: 2.2em; height: 1.15em;"
                                            title="Toggle Site Based vs Total">
                                        <label class="form-check-label small fw-semibold cursor-pointer text-nowrap toggle-site-label-{{ $supplier->id }} {{ $isSiteBased ? 'text-primary' : 'text-muted' }}"
                                            for="toggleSiteBased_{{ $supplier->id }}" style="font-size: 0.75rem;">
                                            {{ $isSiteBased ? 'Site Based' : 'Total' }}
                                        </label>
                                    </div>
                                </td>
                                <td class="row-supplier-status">
                                    @if ($supplier->status === 'Active')
                                        <span class="badge bg-success-subtle text-success row-status-badge">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted row-status-badge">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <div class="dropdown table-action-dropdown">
                                            <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                                aria-expanded="false" title="Actions">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item view-supplier-btn"
                                                        href="{{ route('suppliers.show', $supplier->encrypted_id) }}"><i
                                                            class="bi bi-eye text-info me-2"></i>View Details</a></li>
                                                <li><button class="dropdown-item edit-supplier-btn" type="button"><i
                                                            class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                <li>
                                                    <hr class="dropdown-divider my-1">
                                                </li>
                                                @if ($inUse)
                                                    <li>
                                                        <button class="dropdown-item text-muted disabled" type="button" disabled
                                                            title="Cannot delete: supplier is currently in use in Purchase Orders"
                                                            style="cursor: not-allowed; opacity: 0.6;">
                                                            <i class="bi bi-lock-fill text-muted me-2"></i>Delete <span
                                                                class="badge bg-secondary-subtle text-secondary ms-1"
                                                                style="font-size: 0.65rem;">In Use</span>
                                                        </button>
                                                    </li>
                                                @else
                                                    <li><button class="dropdown-item text-danger delete-supplier-btn"
                                                            type="button"><i
                                                                class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptySuppliersRow">
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-truck display-5 d-block mb-2 text-secondary opacity-50"></i>
                                    <h6>No Suppliers Found</h6>
                                    <p class="small mb-0">Click "Add New Supplier" to register your first supplier.</p>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching suppliers found for your search or filter criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3"
                id="tablePaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: 75px;">
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark"
                            id="pageEnd">{{ min(5, $suppliers->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $suppliers->count() }}</strong> suppliers
                    </span>
                </div>
                <nav aria-label="Suppliers table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Supplier Add / Edit Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="supplierModalTitle">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="supplierForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="supplierEditId">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="supplierName">Supplier / Shop Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="supplierName" name="name"
                                    placeholder="e.g. Sri Balaji Blue Metals" required>
                                <div class="invalid-feedback" id="errSupplierName"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="supplierContact">Contact Person</label>
                                <input type="text" class="form-control" id="supplierContact" name="contact_person"
                                    placeholder="e.g. Bala Murugan">
                                <div class="invalid-feedback" id="errSupplierContact"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="supplierPhone">Phone Number <span
                                        class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="supplierPhone" name="phone"
                                    placeholder="94440 00000" required>
                                <div class="invalid-feedback" id="errSupplierPhone"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="supplierGst">GSTIN / Tax No</label>
                                <input type="text" class="form-control font-monospace" id="supplierGst" name="gst_number"
                                    placeholder="33AAAAA0000A1Z5">
                                <div class="invalid-feedback" id="errSupplierGst"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="supplierAddress">Address / Location</label>
                                <input type="text" class="form-control" id="supplierAddress" name="address"
                                    placeholder="e.g. GST Road, Tambaram, Chennai">
                                <div class="invalid-feedback" id="errSupplierAddress"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="supplierStatus">Status</label>
                                <select class="form-select" id="supplierStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errSupplierStatus"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="supplierSiteBased">Payment Basis / Scope</label>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="supplierSiteBased" name="is_site_based" value="1" checked style="cursor: pointer; width: 2.2em; height: 1.2em;">
                                        <label class="form-check-label small fw-semibold" for="supplierSiteBased" id="supplierSiteBasedLabel">
                                            Site Based
                                        </label>
                                    </div>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">Enabled: Settle per Site PO. Disabled: Settle in Total across sites.</div>
                            </div>

                            <!-- Materials, Grade and Price Section (Dynamic Rows) -->
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                    <div>
                                        <label class="form-label small fw-bold mb-0 text-dark">
                                            <i class="bi bi-boxes text-primary me-1"></i>Materials Supplied, Grade & Price
                                             <span class="text-danger">*</span>
                                        </label>
                                        <div class="text-muted" style="font-size: 0.75rem;">Select at least one material and
                                            rate per unit (grade is optional)</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1"
                                        id="addMaterialRowBtn">
                                        <i class="bi bi-plus-circle me-1"></i>Add Material
                                    </button>
                                </div>

                                <div id="materialRowsContainer" class="d-flex flex-column gap-2">
                                    <div class="card p-2 border bg-light-subtle material-row shadow-none">
                                        <div class="row g-2 align-items-start">
                                            <div class="col-12 col-md-5">
                                                <label class="form-label small text-muted mb-1 d-md-none">Material <span class="text-danger">*</span></label>
                                                <select class="form-select form-select-sm material-select">
                                                    <option value="">-- Select Material --</option>
                                                    @foreach ($materials as $material)
                                                        <option value="{{ $material->id }}"
                                                            data-unit="{{ $material->unit?->name_en ?? (string) $material->unit }}"
                                                            data-tamil="{{ $material->name_ta }}"
                                                            data-name="{{ $material->name_en }}">
                                                            {{ $material->name_en }}
                                                            {{ $material->name_ta ? '- ' . $material->name_ta : '' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback material-select-feedback"
                                                    style="display: none;"></div>
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <label class="form-label small text-muted mb-1 d-md-none">Grade</label>
                                                <select class="form-select form-select-sm material-grade-select">
                                                    <option value="" selected>-- Select Grade --</option>
                                                    <option value="1st Quality">1st Quality</option>
                                                    <option value="2nd Quality">2nd Quality</option>
                                                    <option value="3rd Quality">3rd Quality</option>
                                                    <option value="Standard">Standard</option>
                                                    <option value="Premium">Premium</option>
                                                </select>
                                                <div class="invalid-feedback material-grade-feedback"
                                                    style="display: none;"></div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label small text-muted mb-1 d-md-none">Price / Rate <span class="text-danger">*</span></label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-white text-muted">₹</span>
                                                    <input type="number" step="0.01" min="0"
                                                        class="form-control material-price-input"
                                                        placeholder="Rate per Unit">
                                                    <button type="button"
                                                        class="btn btn-outline-danger remove-material-row-btn"
                                                        title="Remove">
                                                        <i class="bi bi-trash3"></i>
                                                    </button>
                                                </div>
                                                <div class="invalid-feedback material-price-feedback"
                                                    style="display: none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="invalid-feedback d-block" id="errSupplierMaterials"
                                    style="display: none !important;"></div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveSupplierBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSupplierSpinner"
                                role="status" aria-hidden="true"></span>
                            <span id="saveSupplierBtnText">Save Supplier</span>
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
            // Setup CSRF header for all AJAX calls
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const supplierModalElement = document.getElementById('supplierModal');
            const supplierModalInstance = new bootstrap.Modal(supplierModalElement);

            // Master materials lookup map
            const allMaterialsMaster = @json($materials);
            const materialMap = {};
            allMaterialsMaster.forEach(function (mat) {
                materialMap[String(mat.id)] = mat;
            });

            function escapeHtml(text) {
                if (text === null || text === undefined) return '';
                return $('<div>').text(text).html();
            }

            // HTML options for material select dropdown
            const materialOptionsHtml = `
                                                    <option value="">-- Select Material --</option>
                                                    @foreach ($materials as $material)
                                                        <option value="{{ $material->id }}" data-unit="{{ $material->unit?->name_en ?? (string) $material->unit }}" data-tamil="{{ $material->name_ta }}" data-name="{{ $material->name_en }}">
                                                            {{ $material->name_en }} {{ $material->name_ta ? '- ' . $material->name_ta : '' }}
                                                        </option>
                                                    @endforeach
                                                `;

            function getMaterialUnitLabel(mat, $selectEl = null) {
                if ($selectEl && $selectEl.length) {
                    const optUnit = $selectEl.find('option:selected').data('unit');
                    if (optUnit && typeof optUnit === 'string' && optUnit.trim() !== '') {
                        return optUnit.trim();
                    }
                }
                if (!mat) return 'Unit';
                if (typeof mat.unit === 'string' && mat.unit.trim() !== '') {
                    return mat.unit.trim();
                }
                if (mat.unit && typeof mat.unit === 'object') {
                    return mat.unit.name_en || mat.unit.code || 'Unit';
                }
                return 'Unit';
            }

            function createMaterialRow(matId = '', grade = '', price = '') {
                const strId = String(matId || '');
                const selectedMat = strId ? materialMap[strId] : null;
                const unitLabel = getMaterialUnitLabel(selectedMat);

                const $row = $(`
                                        <div class="card p-2 border bg-light-subtle material-row shadow-none">
                                            <div class="row g-2 align-items-start">
                                                <div class="col-12 col-md-5">
                                                    <label class="form-label small text-muted mb-1 d-md-none">Material <span class="text-danger">*</span></label>
                                                    <select class="form-select form-select-sm material-select">
                                                        ${materialOptionsHtml}
                                                    </select>
                                                    <div class="invalid-feedback material-select-feedback" style="display: none;"></div>
                                                </div>
                                                <div class="col-12 col-md-3">
                                                    <label class="form-label small text-muted mb-1 d-md-none">Grade</label>
                                                    <select class="form-select form-select-sm material-grade-select">
                                                        <option value="" ${!grade ? 'selected' : ''}>-- Select Grade --</option>
                                                        <option value="1st Quality" ${grade === '1st Quality' ? 'selected' : ''}>1st Quality</option>
                                                        <option value="2nd Quality" ${grade === '2nd Quality' ? 'selected' : ''}>2nd Quality</option>
                                                        <option value="3rd Quality" ${grade === '3rd Quality' ? 'selected' : ''}>3rd Quality</option>
                                                        <option value="Standard" ${grade === 'Standard' ? 'selected' : ''}>Standard</option>
                                                        <option value="Premium" ${grade === 'Premium' ? 'selected' : ''}>Premium</option>
                                                    </select>
                                                    <div class="invalid-feedback material-grade-feedback" style="display: none;"></div>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label small text-muted mb-1 d-md-none">Price / Rate <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-white text-muted">₹</span>
                                                        <input type="number" step="0.01" min="0" class="form-control material-price-input" 
                                                            value="${escapeHtml(price !== '' ? price : '')}" 
                                                            placeholder="Rate per ${escapeHtml(unitLabel)}">
                                                        <button type="button" class="btn btn-outline-danger remove-material-row-btn" title="Remove row">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </div>
                                                    <div class="invalid-feedback material-price-feedback" style="display: none;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    `);

                if (strId) {
                    $row.find('.material-select').val(strId);
                    const actualUnit = getMaterialUnitLabel(selectedMat, $row.find('.material-select'));
                    $row.find('.material-price-input').attr('placeholder', `Rate per ${actualUnit}`);
                }

                if (typeof window.makeSearchableSelect === 'function') {
                    window.makeSearchableSelect($row.find('.material-select'), {
                        placeholder: '-- Select Material --',
                        type: 'material'
                    });
                }

                return $row;
            }

            function resetMaterialRows() {
                $('#materialRowsContainer').empty();
                $('#materialRowsContainer').append(createMaterialRow());
            }

            // Append new set of inputs when Add button is clicked
            $('#addMaterialRowBtn').on('click', function () {
                const $newRow = createMaterialRow();
                $('#materialRowsContainer').append($newRow);
                $newRow.find('.material-select').focus();
            });

            // Update price placeholder when material changes
            $(document).on('change', '.material-select', function () {
                const $row = $(this).closest('.material-row');
                const matId = String($(this).val() || '');
                const mat = matId ? materialMap[matId] : null;
                const unitLabel = getMaterialUnitLabel(mat, $(this));
                $row.find('.material-price-input').attr('placeholder', `Rate per ${unitLabel}`);
            });

            if (typeof window.makeSearchableSelect === 'function') {
                window.makeSearchableSelect($('#materialRowsContainer .material-select'), {
                    placeholder: '-- Select Material --',
                    type: 'material'
                });
            }

            // Clear validation errors on user input or change
            $('#supplierModal').on('hidden.bs.modal', function () {
                clearFormErrors();
            });

            $('#supplierForm input, #supplierForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
            });

            $(document).on('input change', '.material-select, .material-grade-select, .material-price-input', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').attr('style', 'display:none !important;').hide();
                $(this).closest('.col-12, .col-md-5, .col-md-3, .col-md-4').find('.invalid-feedback').text('').attr('style', 'display:none !important;').hide();
                $('#errSupplierMaterials').text('').attr('style', 'display:none !important;').hide();
            });

            // Remove row button click
            $(document).on('click', '.remove-material-row-btn', function () {
                const $rows = $('.material-row');
                if ($rows.length > 1) {
                    $(this).closest('.material-row').remove();
                } else {
                    const $row = $(this).closest('.material-row');
                    $row.find('.material-select').val('');
                    $row.find('.material-grade-select').val('');
                    $row.find('.material-price-input').val('').attr('placeholder', 'Rate per Unit');
                    $row.find('.is-invalid').removeClass('is-invalid');
                    $row.find('.material-select-feedback, .material-grade-feedback, .material-price-feedback').text('').attr('style', 'display:none !important;').hide();
                }
            });

            function renderMaterialNames(materials) {
                if (!materials) return '<span class="text-muted small">—</span>';
                let list = [];
                if (typeof materials === 'string') {
                    try {
                        list = JSON.parse(materials);
                    } catch (e) {
                        list = [];
                    }
                } else if (Array.isArray(materials)) {
                    list = materials;
                }
                if (!list.length) {
                    return '<span class="text-muted small">—</span>';
                }
                const names = list.map(function (m) {
                    const mid = String(m.id || m.material_id || '');
                    return m.name_en || m.name || (materialMap[mid] ? materialMap[mid].name_en : '');
                }).filter(Boolean);

                if (!names.length) {
                    return '<span class="text-muted small">—</span>';
                }
                return `<span class="text-dark fw-medium">${escapeHtml(names.join(', '))}</span>`;
            }

            function renderStatusBadge(status) {
                if (status === 'Inactive') {
                    return '<span class="badge bg-secondary-subtle text-muted row-status-badge">Inactive</span>';
                }
                return '<span class="badge bg-success-subtle text-success row-status-badge">Active</span>';
            }

            function updateRowSerials() {
                let counter = 1;
                $('#suppliersTableBody tr:not(#emptySuppliersRow):not(#noFilteredSuppliersRow)').each(function () {
                    $(this).find('.serial-cell').text(String(counter).padStart(2, '0'));
                    counter++;
                });
            }

            function clearFormErrors() {
                $('#supplierForm .is-invalid').removeClass('is-invalid');
                $('#supplierForm .invalid-feedback').text('').hide();
                $('#errSupplierMaterials').text('').attr('style', 'display:none !important;');
                $('.material-select-feedback, .material-grade-feedback, .material-price-feedback').text('').attr('style', 'display:none !important;').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.name) {
                    $('#supplierName').addClass('is-invalid');
                    $('#errSupplierName').text(errors.name[0]).show();
                }
                if (errors.contact_person) {
                    $('#supplierContact').addClass('is-invalid');
                    $('#errSupplierContact').text(errors.contact_person[0]).show();
                }
                if (errors.phone) {
                    $('#supplierPhone').addClass('is-invalid');
                    $('#errSupplierPhone').text(errors.phone[0]).show();
                }
                if (errors.address) {
                    $('#supplierAddress').addClass('is-invalid');
                    $('#errSupplierAddress').text(errors.address[0]).show();
                }
                if (errors.gst_number) {
                    $('#supplierGst').addClass('is-invalid');
                    $('#errSupplierGst').text(errors.gst_number[0]).show();
                }
                if (errors.status) {
                    $('#supplierStatus').addClass('is-invalid');
                    $('#errSupplierStatus').text(errors.status[0]).show();
                }
                if (errors.materials) {
                    $('#errSupplierMaterials').text(errors.materials[0]).removeAttr('style').show();
                }

                // Check row-level item errors: materials.0.material_id, materials.0.price, materials.0.grade, etc.
                const rows = $('#materialRowsContainer .material-row');
                Object.keys(errors).forEach(function (key) {
                    const match = key.match(/^materials\.(\d+)\.(.+)$/);
                    if (match) {
                        const index = parseInt(match[1], 10);
                        const field = match[2];
                        const row = rows.eq(index);
                        if (row.length) {
                            if (field === 'material_id') {
                                const sel = row.find('.material-select');
                                sel.addClass('is-invalid');
                                row.find('.material-select-feedback').text(errors[key][0]).removeAttr('style').show();
                            } else if (field === 'price') {
                                const inp = row.find('.material-price-input');
                                inp.addClass('is-invalid');
                                row.find('.material-price-feedback').text(errors[key][0]).removeAttr('style').show();
                            } else if (field === 'grade') {
                                const sel = row.find('.material-grade-select');
                                sel.addClass('is-invalid');
                                row.find('.material-grade-feedback').text(errors[key][0]).removeAttr('style').show();
                            }
                        }
                    }
                });
            }

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#supplierSearchInput').val().toLowerCase().trim();
                const statusFilter = $('#supplierStatusFilter').val();
                const scopeFilter = $('#supplierScopeFilter').val();
                const matching = [];

                $('#suppliersTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptySuppliersRow' || rowId === 'noResultsRow' || rowId === 'noFilteredSuppliersRow') return;

                    const name = String($(this).data('name') || '').toLowerCase();
                    const contact = String($(this).data('contact') || '').toLowerCase();
                    const phone = String($(this).data('phone') || '').toLowerCase();
                    const address = String($(this).data('address') || '').toLowerCase();
                    const gst = String($(this).data('gst') || '').toLowerCase();
                    const status = String($(this).data('status') || 'Active');
                    const isSiteBased = String($(this).data('is-site-based') !== undefined ? $(this).data('is-site-based') : '1');

                    const matData = $(this).data('materials') || [];
                    let matNames = '';
                    if (Array.isArray(matData)) {
                        matNames = matData.map(function (m) {
                            return (m.name_en || m.name || '') + ' ' + (m.name_ta || m.tamil || '') + ' ' + (m.grade || '');
                        }).join(' ').toLowerCase();
                    } else if (typeof matData === 'string') {
                        matNames = matData.toLowerCase();
                    }

                    const matchesQuery = !query ||
                        name.includes(query) ||
                        contact.includes(query) ||
                        phone.includes(query) ||
                        address.includes(query) ||
                        gst.includes(query) ||
                        matNames.includes(query);

                    const matchesStatus = !statusFilter || status === statusFilter;
                    const matchesScope = !scopeFilter || isSiteBased === scopeFilter;

                    if (matchesQuery && matchesStatus && matchesScope) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#suppliersTableBody tr').not('#emptySuppliersRow, #noResultsRow, #noFilteredSuppliersRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#suppliersTableBody tr').not('#emptySuppliersRow, #noResultsRow, #noFilteredSuppliersRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptySuppliersRow').length === 0) {
                        $('#suppliersTableBody').html(`
                                        <tr id="emptySuppliersRow">
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="bi bi-truck display-5 d-block mb-2 text-secondary opacity-50"></i>
                                                <h6>No Suppliers Found</h6>
                                                <p class="small mb-0">Click "Add New Supplier" to register your first supplier.</p>
                                            </td>
                                        </tr>
                                    `);
                    }
                    $('#emptySuppliersRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#supplierCountBadge').html('Showing <strong class="text-dark">0</strong> suppliers');
                    return;
                } else {
                    $('#emptySuppliersRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#supplierCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> suppliers');
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
                summaryHtml += ' suppliers';
                $('#paginationSummary').html(summaryHtml);

                $('#supplierCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> suppliers`);

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

            // Per page dropdown change
            $('#perPageSelect').on('change', function () {
                perPage = parseInt($(this).val()) || 5;
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

            // Filter on search, status, or scope change
            $('#supplierSearchInput, #supplierStatusFilter, #supplierScopeFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#supplierSearchInput').val('');
                $('#supplierStatusFilter').val('');
                $('#supplierScopeFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Toggle Supplier Site Based Mode via Switch in Table
            $(document).on('change', '.supplier-site-based-toggle', function () {
                const $switch = $(this);
                const supplierId = $switch.data('id');
                const supplierName = $switch.data('name') || 'Supplier';
                const isChecked = $switch.is(':checked');
                const $row = $switch.closest('tr');
                const $label = $row.find(`.toggle-site-label-${supplierId}`);

                $switch.prop('disabled', true);

                $.ajax({
                    url: `/suppliers/${supplierId}/toggle-site-based`,
                    type: 'POST',
                    data: {
                        is_site_based: isChecked ? 1 : 0,
                        _token: '{{ csrf_token() }}'
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        $switch.prop('disabled', false);
                        if (res.success) {
                            $row.data('is-site-based', isChecked ? '1' : '0').attr('data-is-site-based', isChecked ? '1' : '0');
                            if (isChecked) {
                                $label.text('Site Based').removeClass('text-muted').addClass('text-primary');
                                showToast(`${supplierName} is now set to Site Based payment mode.`, 'success');
                            } else {
                                $label.text('Total').removeClass('text-primary').addClass('text-muted');
                                showToast(`${supplierName} is now set to Total payment mode.`, 'info');
                            }
                        } else {
                            $switch.prop('checked', !isChecked);
                            showToast(res.message || 'Failed to update payment mode.', 'danger');
                        }
                    },
                    error: function () {
                        $switch.prop('disabled', false);
                        $switch.prop('checked', !isChecked);
                        showToast('Failed to update payment mode. Please try again.', 'danger');
                    }
                });
            });

            // Open Add Modal
            $('#addSupplierBtn').on('click', function () {
                $('#supplierModalTitle').text('Add New Supplier');
                $('#saveSupplierBtnText').text('Save Supplier');
                $('#supplierEditId').val('');
                $('#supplierForm')[0].reset();
                $('#supplierStatus').val('Active');
                $('#supplierSiteBased').prop('checked', true);
                $('#supplierSiteBasedLabel').text('Site Based');
                resetMaterialRows();
                clearFormErrors();
            });

            // Open Edit Modal
            $(document).on('click', '.edit-supplier-btn', function () {
                const tr = $(this).closest('tr');
                $('#supplierModalTitle').text('Edit Supplier');
                $('#saveSupplierBtnText').text('Update Supplier');
                $('#supplierEditId').val(tr.data('id'));
                $('#supplierName').val(tr.data('name') || '');
                $('#supplierContact').val(tr.data('contact') || '');
                $('#supplierPhone').val(tr.data('phone') || '');
                $('#supplierAddress').val(tr.data('address') || '');
                $('#supplierGst').val(tr.data('gst') || '');
                $('#supplierStatus').val(tr.data('status') || 'Active');

                const isSiteBased = tr.data('is-site-based');
                const isSb = (isSiteBased === undefined || isSiteBased === null || String(isSiteBased) === '1' || isSiteBased === true);
                $('#supplierSiteBased').prop('checked', isSb);
                $('#supplierSiteBasedLabel').text(isSb ? 'Site Based' : 'Total');

                let mats = tr.data('materials') || [];
                if (typeof mats === 'string') {
                    try {
                        mats = JSON.parse(mats);
                    } catch (e) {
                        mats = [];
                    }
                }

                $('#materialRowsContainer').empty();
                if (mats && mats.length > 0) {
                    mats.forEach(function (m) {
                        const mid = String(m.id || m.material_id);
                        const p = m.pivot && m.pivot.price !== undefined ? m.pivot.price : (m.price !== undefined ? m.price : '');
                        const g = m.pivot && m.pivot.grade !== undefined ? m.pivot.grade : (m.grade || '1st Quality');
                        const $row = createMaterialRow(mid, g, p);
                        $('#materialRowsContainer').append($row);
                    });
                } else {
                    resetMaterialRows();
                }

                clearFormErrors();
                supplierModalInstance.show();
            });

            // Modal Site Based Switch Label Update
            $('#supplierSiteBased').on('change', function () {
                $('#supplierSiteBasedLabel').text($(this).is(':checked') ? 'Site Based' : 'Total');
            });

            // Form Submit (Create or Update)
            $('#supplierForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#supplierEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("suppliers.update", ":id") }}'.replace(':id', editId) : '{{ route("suppliers.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                // Collect materials rows directly without javascript validation
                const materialsPayload = [];
                const rows = $('#materialRowsContainer .material-row');
                rows.each(function () {
                    const $row = $(this);
                    const matSelect = $row.find('.material-select');
                    const gradeSelect = $row.find('.material-grade-select');
                    const priceInput = $row.find('.material-price-input');

                    const matId = matSelect.val();
                    const gradeVal = gradeSelect.val();
                    const priceVal = priceInput.val().trim();

                    materialsPayload.push({
                        material_id: matId ? parseInt(matId, 10) : null,
                        grade: gradeVal ? gradeVal.trim() : null,
                        price: (priceVal !== '' && !isNaN(priceVal)) ? parseFloat(priceVal) : (priceVal !== '' ? priceVal : null)
                    });
                });

                const postData = {
                    name: $('#supplierName').val().trim(),
                    contact_person: $('#supplierContact').val().trim(),
                    phone: $('#supplierPhone').val().trim(),
                    address: $('#supplierAddress').val().trim(),
                    gst_number: $('#supplierGst').val().trim(),
                    status: $('#supplierStatus').val(),
                    is_site_based: $('#supplierSiteBased').is(':checked') ? 1 : 0,
                    materials: materialsPayload
                };

                const $btn = $('#saveSupplierBtn');
                const $spinner = $('#saveSupplierSpinner');
                const $btnText = $('#saveSupplierBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    type: method,
                    data: JSON.stringify(postData),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Supplier' : 'Save Supplier');

                        if (res.success && res.data) {
                            const supplier = res.data;
                            const materialsList = supplier.materials || [];
                            const materialNamesHtml = renderMaterialNames(materialsList);
                            const materialsJsonStr = JSON.stringify(materialsList.map(function (m) {
                                return {
                                    id: m.id,
                                    name: m.name_en || m.name,
                                    tamil: m.name_ta || m.tamil,
                                    unit: getMaterialUnitLabel(m),
                                    grade: m.pivot ? m.pivot.grade : (m.grade || '1st Quality'),
                                    price: m.pivot ? parseFloat(m.pivot.price) : (parseFloat(m.price) || 0)
                                };
                            }));

                            const gstHtml = supplier.gst_number
                                ? `<span class="font-monospace text-secondary small">${escapeHtml(supplier.gst_number)}</span>`
                                : '<span class="text-muted small">—</span>';
                            const contactHtml = supplier.contact_person
                                ? `<small class="text-muted row-supplier-contact"><i class="bi bi-person me-1"></i>${escapeHtml(supplier.contact_person)}</small>`
                                : '<small class="text-muted row-supplier-contact"><i class="bi bi-person me-1"></i>—</small>';
                            const addressHtml = supplier.address ? escapeHtml(supplier.address) : '—';
                            const statusBadgeHtml = renderStatusBadge(supplier.status);
                            const isSb = (supplier.is_site_based !== undefined ? Boolean(supplier.is_site_based) : true);

                            const toggleCellHtml = `
                                <div class="form-check form-switch d-inline-flex align-items-center gap-1 justify-content-center mb-0">
                                    <input class="form-check-input supplier-site-based-toggle" type="checkbox" role="switch"
                                        data-id="${supplier.id}"
                                        data-name="${escapeHtml(supplier.name)}"
                                        id="toggleSiteBased_${supplier.id}"
                                        ${isSb ? 'checked' : ''}
                                        style="cursor: pointer; width: 2.2em; height: 1.15em;"
                                        title="Toggle Site Based vs Total">
                                    <label class="form-check-label small fw-semibold cursor-pointer text-nowrap toggle-site-label-${supplier.id} ${isSb ? 'text-primary' : 'text-muted'}"
                                        for="toggleSiteBased_${supplier.id}" style="font-size: 0.75rem;">
                                        ${isSb ? 'Site Based' : 'Total'}
                                    </label>
                                </div>
                            `;

                            if (isEdit) {
                                const tr = $(`#suppliersTableBody tr[data-id="${supplier.id}"]`);
                                tr.data('name', supplier.name)
                                    .data('contact', supplier.contact_person)
                                    .data('phone', supplier.phone)
                                    .data('address', supplier.address)
                                    .data('gst', supplier.gst_number)
                                    .data('materials', materialsList)
                                    .data('status', supplier.status)
                                    .data('is-site-based', isSb ? '1' : '0')
                                    .attr('data-is-site-based', isSb ? '1' : '0')
                                    .attr('data-materials', materialsJsonStr);

                                tr.find('.row-supplier-name').text(supplier.name);
                                tr.find('.row-supplier-contact').replaceWith(contactHtml);
                                tr.find('.row-supplier-phone').html(`<i class="bi bi-telephone text-muted me-1"></i>${escapeHtml(supplier.phone)}`);
                                tr.find('.row-supplier-address').text(addressHtml);
                                tr.find('.row-supplier-gst').html(gstHtml);
                                tr.find('.row-supplier-materials').html(materialNamesHtml);
                                tr.find('.row-supplier-site-based').html(toggleCellHtml);
                                tr.find('.row-supplier-status').html(statusBadgeHtml);

                                showToast(res.message || `Supplier "${supplier.name}" updated successfully!`, 'success');
                            } else {
                                $('#emptySuppliersRow').remove();

                                const newRowHtml = `
                                    <tr data-id="${supplier.id}"
                                        data-name="${escapeHtml(supplier.name)}"
                                        data-contact="${escapeHtml(supplier.contact_person || '')}"
                                        data-phone="${escapeHtml(supplier.phone)}"
                                        data-address="${escapeHtml(supplier.address || '')}"
                                        data-gst="${escapeHtml(supplier.gst_number || '')}"
                                        data-materials='${materialsJsonStr}'
                                        data-status="${escapeHtml(supplier.status || 'Active')}"
                                        data-is-site-based="${isSb ? '1' : '0'}">
                                        <td class="text-muted fw-semibold serial-cell">01</td>
                                        <td>
                                            <div class="fw-bold text-dark row-supplier-name">${escapeHtml(supplier.name)}</div>
                                            ${contactHtml}
                                        </td>
                                        <td class="row-supplier-phone"><i class="bi bi-telephone text-muted me-1"></i>${escapeHtml(supplier.phone)}</td>
                                        <td class="row-supplier-address">${addressHtml}</td>
                                        <td class="row-supplier-gst">${gstHtml}</td>
                                        <td class="row-supplier-materials">${materialNamesHtml}</td>
                                        <td class="text-center row-supplier-site-based">${toggleCellHtml}</td>
                                        <td class="row-supplier-status">${statusBadgeHtml}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex align-items-center gap-1">
                                                <div class="dropdown table-action-dropdown">
                                                    <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li><a class="dropdown-item" href="${'{{ route("suppliers.show", ":id") }}'.replace(':id', supplier.encrypted_id)}"><i class="bi bi-eye text-info me-2"></i>View Details</a></li>
                                                        <li><button class="dropdown-item edit-supplier-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li><button class="dropdown-item text-danger delete-supplier-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                `;

                                $('#suppliersTableBody').prepend(newRowHtml);
                                const newTr = $(`#suppliersTableBody tr[data-id="${supplier.id}"]`);
                                newTr.data('materials', materialsList);

                                currentPage = 1;
                                showToast(res.message || `Supplier "${supplier.name}" registered successfully!`, 'success');
                            }

                            updatePagination();
                            supplierModalInstance.hide();
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Supplier' : 'Save Supplier');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving the supplier.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Delete Supplier
            $(document).on('click', '.delete-supplier-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const name = tr.data('name') || 'this supplier';

                confirmDelete(`Are you sure you want to remove supplier "${name}"?`, function () {
                    $.ajax({
                        url: '{{ route("suppliers.destroy", ":id") }}'.replace(':id', id),
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (res) {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                updatePagination();
                            });
                            showToast(`Supplier "${name}" removed successfully.`, 'danger');
                        },
                        error: function (xhr) {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Could not delete supplier.';
                            showToast(errorMsg, 'danger');
                        }
                    });
                });
            });

            // Initial pagination on page load
            updatePagination();
        });
    </script>
@endpush