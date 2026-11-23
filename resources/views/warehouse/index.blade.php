@extends('layouts.app')

@section('title', 'Warehouse Store')
@section('page_title', 'Warehouse Store & Tools (கிடங்கு & உபகரணங்கள்)')

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

        #warehouseItemModal .form-control[readonly],
        #warehouseItemModal .form-select:disabled {
            background-color: #f1f5f9 !important;
            cursor: not-allowed;
            color: #475569;
            border-color: #cbd5e1;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Owner Central Store & Tool Inventory (மைய கிடங்கு இருப்பு)</h5>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="exportWarehouseBtn">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Inventory
                    </button>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#warehouseItemModal"
                        id="addNewItemBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Inventory Item
                    </button>
                </div>
            </div>
        </div>

        <!-- Inventory KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">Total Catalog Items</span>
                        <i class="bi bi-boxes text-primary fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-primary mb-0" id="statTotalItems">{{ $stats['total_items'] ?? count($items) }}
                        Items</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Materials & Tools</small>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">Total Stock Valuation</span>
                        <i class="bi bi-currency-rupee text-success fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-success mb-0">₹{{ number_format($stats['total_valuation'] ?? 0, 2) }}</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Current Store Value</small>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card-stat h-100 bg-white border-danger border-opacity-50">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">Low Stock Items</span>
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-danger mb-0" id="statLowStockCount">{{ $stats['low_stock_count'] ?? 0 }} Items
                    </h4>
                    <small class="text-danger fw-semibold" style="font-size:0.75rem;">Below Minimum Threshold</small>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">Items Issued Today</span>
                        <i class="bi bi-truck text-warning fs-5"></i>
                    </div>
                    <h4 class="fw-bold text-warning mb-0">{{ $stats['issued_sites_today'] ?? 0 }} Sites</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Dispatched from store</small>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="warehouseSearchInput"
                            placeholder="Search by SKU, Item Name (EN / தமிழ்), Category...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="stockStatusFilter">
                        <option value="">All Stock Levels (அனைத்து இருப்பு நிலை)</option>
                        <option value="low">Low Stock Only (குறைந்த இருப்பு - எச்சரிக்கை)</option>
                        <option value="normal">Normal In-Stock (போதுமான இருப்பு)</option>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
                <div class="col-6 col-md-4 text-md-end d-flex align-items-center justify-content-end gap-2 flex-wrap">
                    <button class="btn btn-outline-danger btn-sm" id="quickFilterLowStockBtn">
                        <i class="bi bi-funnel me-1"></i> Show Low Stock ({{ $stats['low_stock_count'] ?? 0 }})
                    </button>
                    <span class="small text-muted" id="warehouseCountBadge">Showing <strong class="text-dark"
                            id="visibleWarehouseCount">{{ count($items) }}</strong> items</span>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card-custom overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" id="warehouseTable">
                    <thead>
                        <tr>
                            <th>SKU / Code</th>
                            <th>Item Name (English & தமிழ்)</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Unit</th>
                            <th>Cash Amount (விலை ₹)</th>
                            <th>Min. Threshold</th>
                            <th>Stock Status</th>
                            <th class="text-end" style="width: 70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="warehouseTableBody">
                        @forelse ($items as $it)
                            @php
                                $isLow = $it->isLowStock();
                                $statusBadge = $isLow
                                    ? '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-exclamation-circle me-1"></i>Low Stock</span>'
                                    : '<span class="badge bg-success-subtle text-success">Adequate</span>';
                                $stockColor = $isLow ? 'text-danger' : 'text-success';
                                $itemCash = (float) ($it->cash_amount ?? $it->unit_rate ?? 0);
                                $unitName = $it->unit?->name_en ?? (string) ($it->unit ?? '');
                                $unitId = $it->unit_id ?? $it->unit?->id ?? '';
                            @endphp
                            <tr data-id="{{ $it->id }}" data-sku="{{ $it->sku }}" data-name-en="{{ $it->name_en }}"
                                data-name-ta="{{ $it->name_ta }}" data-cat="{{ $it->category }}"
                                data-stock="{{ (float) $it->current_stock }}" data-unit="{{ $unitName }}"
                                data-unit-id="{{ $unitId }}" data-cash-amount="{{ $itemCash }}" data-rate="{{ $itemCash }}"
                                data-threshold="{{ (float) $it->min_threshold }}" data-is-low="{{ $isLow ? 'true' : 'false' }}">
                                <td class="font-monospace text-primary fw-semibold">{{ $it->sku }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $it->name_en }}</div>
                                    <span class="ta-badge">{{ $it->name_ta }}</span>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-dark">{{ $it->category }}</span></td>
                                <td class="fw-bold {{ $stockColor }} fs-6">{{ (float) $it->current_stock }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $unitName }}</span></td>
                                <td class="fw-bold text-dark font-monospace">₹{{ number_format($itemCash, 2) }}</td>
                                <td><span class="text-muted">{{ (float) $it->min_threshold }} {{ $unitName }}</span></td>
                                <td>{!! $statusBadge !!}</td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-warehouse-item-btn" type="button"
                                                    data-id="{{ $it->id }}" data-sku="{{ $it->sku }}"
                                                    data-name-en="{{ $it->name_en }}" data-name-ta="{{ $it->name_ta }}"
                                                    data-cat="{{ $it->category }}" data-stock="{{ (float) $it->current_stock }}"
                                                    data-unit="{{ $unitName }}" data-unit-id="{{ $unitId }}"
                                                    data-threshold="{{ (float) $it->min_threshold }}"
                                                    data-cash-amount="{{ $itemCash }}"><i
                                                        class="bi bi-pencil-square text-primary me-2"></i>Edit Item</button>
                                            </li>
                                            <li><button class="dropdown-item restock-row-btn" type="button"
                                                    data-id="{{ $it->id }}" data-name="{{ $it->name_en }}"
                                                    data-unit="{{ $unitName }}" data-cash-amount="{{ $itemCash }}"><i
                                                        class="bi bi-plus-lg text-success me-2"></i>Restock</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            <li><button class="dropdown-item delete-warehouse-item-btn text-danger"
                                                    type="button" data-id="{{ $it->id }}" data-name="{{ $it->name_en }}"><i
                                                        class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyWarehouseRow">
                                <td colspan="9" class="text-center text-muted py-4">No inventory items in warehouse.</td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching warehouse items found for your search or filter criteria.
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
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark"
                            id="pageEnd">{{ min(10, count($items)) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ count($items) }}</strong> items
                    </span>
                </div>
                <nav aria-label="Warehouse table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Recent Stock Movements Log (Stock In / Stock Out / Issue to Site) -->
        <div class="card-custom p-3 p-md-4 bg-white">
            <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-arrow-left-right text-primary me-2"></i>Recent Stock
                Movements & Site Dispatches</h6>
            <div class="table-responsive">
                <table class="table table-custom table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action Type</th>
                            <th>Item Details</th>
                            <th>Quantity</th>
                            <th>Issued To / Source</th>
                            <th>Issued By</th>
                        </tr>
                    </thead>
                    <tbody id="movementLogBody">
                        @forelse ($movements as $m)
                            @php
                                $badgeClass = match ($m->movement_type) {
                                    'Issue to Site' => 'bg-warning-subtle text-dark border',
                                    'Stock Inward' => 'bg-success-subtle text-success border',
                                    default => 'bg-info-subtle text-info border',
                                };
                                $qtyPrefix = $m->movement_type === 'Issue to Site' ? '-' : '+';
                                $qtyClass = $m->movement_type === 'Issue to Site' ? 'text-danger' : 'text-success';
                            @endphp
                            <tr>
                                <td class="small">{{ $m->movement_date?->format('d M Y') }}</td>
                                <td><span class="badge {{ $badgeClass }}">{{ $m->movement_type }}</span></td>
                                <td class="fw-semibold">{{ $m->item?->name_en ?? 'Item' }}</td>
                                <td class="fw-bold {{ $qtyClass }}">{{ $qtyPrefix }} {{ (float) $m->quantity }}
                                    {{ $m->item?->unit?->name_en ?? (string) ($m->item?->unit ?? '') }}
                                </td>
                                <td>{{ $m->site_name ?? ($m->projectSite?->name ?? 'Store') }}</td>
                                <td>{{ $m->creator?->name ?? 'Current User' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No stock movements recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Restock Modal -->
    <div class="modal fade" id="stockMovementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-seam text-success me-2"></i>Restock Item (சரக்கு
                        வரவு)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="stockMovementForm" novalidate>
                    <input type="hidden" id="restockItemId" value="">
                    <div class="modal-body">
                        <div class="alert alert-light border py-2 px-3 small mb-3 d-flex align-items-center">
                            <i class="bi bi-info-circle-fill text-primary me-2 fs-6"></i>
                            <div>Restocking Item: <strong id="restockItemName" class="text-dark">-</strong> <span
                                    id="restockItemUnit" class="badge bg-secondary-subtle text-dark ms-1"></span></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="moveQty">Quantity (அளவு) <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control fw-bold" id="moveQty"
                                    placeholder="e.g. 10">
                                <div class="invalid-feedback" id="errMoveQty"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="moveCashAmount">Cash Amount / Unit Price
                                    (விலை / அலகுத் தொகை ₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted">₹</span>
                                    <input type="number" step="any" min="0" class="form-control fw-bold" id="moveCashAmount"
                                        placeholder="e.g. 450.00">
                                </div>
                                <div class="form-text small text-muted">
                                    Current rate: <strong class="text-primary font-monospace"
                                        id="restockCurrentRate">₹0.00</strong>. Updates warehouse valuation if restocked
                                    with new rate.
                                </div>
                                <div class="invalid-feedback" id="errMoveCashAmount"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="moveRemarks">Remark (குறிப்பு)</label>
                                <input type="text" class="form-control" id="moveRemarks"
                                    placeholder="e.g. Restocked from supplier or factory delivery">
                                <div class="invalid-feedback" id="errMoveRemarks"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm" id="btnConfirmRestock"><i
                                class="bi bi-check-lg me-1"></i>Confirm Restock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add / Edit Inventory Item Modal -->
    <div class="modal fade" id="warehouseItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="warehouseItemModalTitle">Add Inventory Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="warehouseItemForm" novalidate>
                    <input type="hidden" id="editItemId" value="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="newItemSku">SKU / Item Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace text-uppercase" id="newItemSku"
                                    placeholder="e.g. WH-EQP-05">
                                <div class="invalid-feedback" id="errItemSku"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="newItemCat">Category <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newItemCat">
                                    <option value="" disabled selected>-- Select Category --</option>
                                    <option value="Raw Material">Raw Material</option>
                                    <option value="Hand Tools">Hand Tools</option>
                                    <option value="Power Tools">Power Tools</option>
                                    <option value="Safety Gear">Safety Gear</option>
                                    <option value="Plumbing">Plumbing</option>
                                    <option value="Chemicals">Chemicals</option>
                                </select>
                                <div class="invalid-feedback" id="errItemCat"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="newItemNameEn">Item Name (English) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="newItemNameEn"
                                    placeholder="e.g. Measuring Tape 50m">
                                <div class="invalid-feedback" id="errItemNameEn"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="newItemNameTa">Name in தமிழ் <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control ta-text" id="newItemNameTa"
                                    placeholder="எ.கா. அளக்கும் டேப்">
                                <div class="invalid-feedback" id="errItemNameTa"></div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold" for="newItemStock">Initial Stock <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control" id="newItemStock" placeholder="20">
                                <div class="invalid-feedback" id="errItemStock"></div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold" for="newItemUnit">Unit of Measure (அளவீடு) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newItemUnit" name="unit" required>
                                    <option value="" disabled selected>-- Select Unit --</option>
                                    @isset($units)
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}" data-code="{{ $unit->code }}"
                                                data-name="{{ $unit->name_en }}">
                                                {{ $unit->name_en }} ({{ $unit->name_ta }})
                                            </option>
                                        @endforeach
                                    @endisset
                                </select>
                                <div class="invalid-feedback" id="errItemUnit"></div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold" for="newItemThreshold">Min. Low Threshold <span
                                        class="text-danger">*</span></label>
                                <input type="number" step="any" class="form-control" id="newItemThreshold" placeholder="5">
                                <div class="invalid-feedback" id="errItemThreshold"></div>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold" for="newItemCashAmount">Cash Amount (விலை ₹)
                                    <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted px-2">₹</span>
                                    <input type="number" step="any" min="0" class="form-control fw-bold"
                                        id="newItemCashAmount" placeholder="450.00">
                                </div>
                                <div class="invalid-feedback" id="errItemCashAmount"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="btnSaveWarehouseItem">Save Item to
                            Store</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Display toast message if set before page reload (e.g. after create/update/restock/delete)
            const savedToast = sessionStorage.getItem('warehouseToast');
            const savedToastType = sessionStorage.getItem('warehouseToastType') || 'success';
            if (savedToast) {
                showToast(savedToast, savedToastType);
                sessionStorage.removeItem('warehouseToast');
                sessionStorage.removeItem('warehouseToastType');
            }

            let nextWhId = 7;
            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function getMatchingRows() {
                const query = ($('#warehouseSearchInput').val() || '').toLowerCase().trim();
                const statusFilter = $('#stockStatusFilter').val();
                const matching = [];

                $('#warehouseTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyWarehouseRow' || rowId === 'noResultsRow') return;

                    const sku = String($(this).data('sku') || '').toLowerCase();
                    const nameEn = String($(this).data('name-en') || '').toLowerCase();
                    const nameTa = String($(this).data('name-ta') || '').toLowerCase();
                    const cat = String($(this).data('cat') || '').toLowerCase();
                    const isLow = $(this).data('is-low');

                    const matchQuery = !query || sku.includes(query) || nameEn.includes(query) || nameTa.includes(query) || cat.includes(query);
                    let matchStatus = true;
                    if (statusFilter === 'low') matchStatus = isLow === true || isLow === 'true';
                    else if (statusFilter === 'normal') matchStatus = isLow === false || isLow === 'false';

                    if (matchQuery && matchStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#warehouseTableBody tr').not('#emptyWarehouseRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#warehouseTableBody tr').not('#emptyWarehouseRow, #noResultsRow').hide();

                if (totalCount === 0) {
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#warehouseCountBadge').html('Showing <strong class="text-dark">0</strong> items');
                    return;
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#warehouseCountBadge').html(`Showing <strong class="text-dark">0</strong> of <strong class="text-dark">${totalCount}</strong> items`);
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

                // Update summary footer text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' items';
                $('#paginationSummary').html(summaryHtml);

                $('#warehouseCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> items`);

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

            $('#warehouseSearchInput, #stockStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            $('#quickFilterLowStockBtn').on('click', function () {
                $('#stockStatusFilter').val('low').trigger('change');
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#warehouseSearchInput').val('');
                $('#stockStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Initial pagination run
            updatePagination();

            function clearRestockFormErrors() {
                $('#stockMovementForm .is-invalid').removeClass('is-invalid');
                $('#stockMovementForm .invalid-feedback').text('').hide();
            }

            function clearWarehouseItemFormErrors() {
                $('#warehouseItemForm .is-invalid').removeClass('is-invalid');
                $('#warehouseItemForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearWarehouseItemFormErrors();
                if (!errors) return;

                if (errors.sku) {
                    $('#newItemSku').addClass('is-invalid');
                    $('#errItemSku').text(errors.sku[0]).show();
                }
                if (errors.category) {
                    $('#newItemCat').addClass('is-invalid');
                    $('#errItemCat').text(errors.category[0]).show();
                }
                if (errors.name_en) {
                    $('#newItemNameEn').addClass('is-invalid');
                    $('#errItemNameEn').text(errors.name_en[0]).show();
                }
                if (errors.name_ta) {
                    $('#newItemNameTa').addClass('is-invalid');
                    $('#errItemNameTa').text(errors.name_ta[0]).show();
                }
                if (errors.current_stock) {
                    $('#newItemStock').addClass('is-invalid');
                    $('#errItemStock').text(errors.current_stock[0]).show();
                }
                if (errors.unit_id || errors.unit) {
                    const err = errors.unit_id ? errors.unit_id[0] : errors.unit[0];
                    $('#newItemUnit').addClass('is-invalid');
                    $('#errItemUnit').text(err).show();
                }
                if (errors.min_threshold) {
                    $('#newItemThreshold').addClass('is-invalid');
                    $('#errItemThreshold').text(errors.min_threshold[0]).show();
                }
                if (errors.cash_amount || errors.unit_rate) {
                    const err = errors.cash_amount ? errors.cash_amount[0] : errors.unit_rate[0];
                    $('#newItemCashAmount').addClass('is-invalid');
                    $('#errItemCashAmount').text(err).show();
                }
            }

            function displayRestockFormErrors(errors) {
                clearRestockFormErrors();
                if (!errors) return;

                if (errors.quantity) {
                    $('#moveQty').addClass('is-invalid');
                    $('#errMoveQty').text(errors.quantity[0]).show();
                }
                if (errors.cash_amount) {
                    $('#moveCashAmount').addClass('is-invalid');
                    $('#errMoveCashAmount').text(errors.cash_amount[0]).show();
                }
                if (errors.notes) {
                    $('#moveRemarks').addClass('is-invalid');
                    $('#errMoveRemarks').text(errors.notes[0]).show();
                }
                if (errors.warehouse_item_id) {
                    showToast(errors.warehouse_item_id[0], 'danger');
                }
            }

            function setItemModalEditMode(isEdit) {
                // In Warehouse Store Edit, readonly the marked fields:
                // SKU, Category, Item Name (English), Name in Tamil, Initial Stock, Unit of Measure.
                // Only Min. Low Threshold and Cash Amount remain editable.
                const readOnlyInputs = $('#newItemSku, #newItemNameEn, #newItemNameTa, #newItemStock');
                const disabledSelects = $('#newItemCat, #newItemUnit');
                const editableInputs = $('#newItemThreshold, #newItemCashAmount');

                if (isEdit) {
                    readOnlyInputs.prop('readonly', true).attr('tabindex', '-1');
                    disabledSelects.prop('disabled', true);
                    editableInputs.prop('readonly', false).removeAttr('tabindex');
                } else {
                    readOnlyInputs.prop('readonly', false).removeAttr('tabindex');
                    disabledSelects.prop('disabled', false);
                    editableInputs.prop('readonly', false).removeAttr('tabindex');
                }
            }

            $('#stockMovementModal').on('hidden.bs.modal', function () {
                clearRestockFormErrors();
            });

            $('#warehouseItemModal').on('hidden.bs.modal', function () {
                clearWarehouseItemFormErrors();
                setItemModalEditMode(false);
                $('#warehouseItemForm').removeData('edit-values');
            });

            $('#warehouseItemModal').on('shown.bs.modal', function () {
                if ($('#editItemId').val()) {
                    $('#newItemThreshold').focus().select();
                } else {
                    $('#newItemSku').focus();
                }
            });

            $('#stockMovementForm input').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).closest('.col-12').find('.invalid-feedback').text('').hide();
            });

            $('#warehouseItemForm input, #warehouseItemForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).closest('.col-12, .col-md-6, .col-md-3').find('.invalid-feedback').text('').hide();
            });

            $('#addNewItemBtn').on('click', function () {
                $('#warehouseItemForm')[0].reset();
                $('#editItemId').val('');
                $('#newItemUnit').val('');
                $('#warehouseItemModalTitle').text('Add Inventory Item');
                $('#btnSaveWarehouseItem').text('Save Item to Store');
                clearWarehouseItemFormErrors();
                setItemModalEditMode(false);
                $('#warehouseItemForm').removeData('edit-values');
            });

            // Open Restock Modal for specific row
            $(document).on('click', '.restock-row-btn', function () {
                const tr = $(this).closest('tr');
                const id = $(this).data('id');
                const name = $(this).data('name') || tr.data('name-en');
                const unit = $(this).data('unit') || tr.data('unit') || '';
                const currentCash = parseFloat($(this).data('cash-amount') || tr.data('cash-amount') || tr.data('rate') || 0);

                clearRestockFormErrors();
                $('#restockItemId').val(id);
                $('#restockItemName').text(name);
                $('#restockItemUnit').text(unit);
                $('#restockCurrentRate').text(`₹${currentCash.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`);
                $('#moveCashAmount').val(currentCash > 0 ? currentCash : '');
                $('#moveQty').val('');
                $('#moveRemarks').val('');

                const modal = new bootstrap.Modal(document.getElementById('stockMovementModal'));
                modal.show();
            });

            // Open Edit Item Modal for specific row
            $(document).on('click', '.edit-warehouse-item-btn', function () {
                const tr = $(this).closest('tr');
                const id = $(this).data('id') || tr.data('id');
                const sku = $(this).data('sku') || tr.data('sku') || '';
                const nameEn = $(this).data('name-en') || tr.data('name-en') || '';
                const nameTa = $(this).data('name-ta') || tr.data('name-ta') || '';
                const cat = $(this).data('cat') || tr.data('cat') || '';
                const stock = $(this).data('stock') !== undefined ? $(this).data('stock') : tr.data('stock');
                const unitId = $(this).data('unit-id') || tr.data('unit-id') || '';
                const unit = $(this).data('unit') || tr.data('unit') || '';
                const threshold = $(this).data('threshold') !== undefined ? $(this).data('threshold') : tr.data('threshold');
                const cashAmount = $(this).data('cash-amount') !== undefined ? $(this).data('cash-amount') : (tr.data('cash-amount') || tr.data('rate') || 0);

                clearWarehouseItemFormErrors();
                $('#editItemId').val(id);
                $('#warehouseItemModalTitle').text(`Edit Inventory Item: ${nameEn}`);
                $('#btnSaveWarehouseItem').text('Update Item');
                $('#newItemSku').val(sku);
                $('#newItemCat').val(cat);
                $('#newItemNameEn').val(nameEn);
                $('#newItemNameTa').val(nameTa);
                $('#newItemStock').val(stock);

                let resolvedUnitId = '';
                if (unitId && $(`#newItemUnit option[value="${unitId}"]`).length > 0) {
                    resolvedUnitId = unitId;
                    $('#newItemUnit').val(unitId);
                } else if (unit) {
                    $('#newItemUnit option').each(function () {
                        const optVal = $(this).val();
                        const optName = $(this).data('name') || '';
                        const optCode = $(this).data('code') || '';
                        if (optVal == unit || optName === unit || optCode === unit || optName.toLowerCase() === unit.toLowerCase() || optCode.toLowerCase() === unit.toLowerCase()) {
                            resolvedUnitId = optVal;
                            return false;
                        }
                    });
                    $('#newItemUnit').val(resolvedUnitId || '');
                } else {
                    $('#newItemUnit').val('');
                }

                $('#newItemThreshold').val(threshold);
                $('#newItemCashAmount').val(cashAmount);

                // Set marked fields readonly/disabled for edit mode
                setItemModalEditMode(true);
                $('#warehouseItemForm').data('edit-values', {
                    sku: sku,
                    category: cat,
                    name_en: nameEn,
                    name_ta: nameTa,
                    current_stock: stock,
                    unit_id: resolvedUnitId || unitId
                });

                const modal = new bootstrap.Modal(document.getElementById('warehouseItemModal'));
                modal.show();
            });

            // Delete Warehouse Item
            $(document).on('click', '.delete-warehouse-item-btn', function () {
                const id = $(this).data('id');
                const name = $(this).data('name') || 'Item';

                if (confirm(`Are you sure you want to delete "${name}" from the warehouse? (இந்த பொருளை நீக்க விரும்புகிறீர்களா?)`)) {
                    $.ajax({
                        url: '{{ route("warehouse.items.destroy", ":id") }}'.replace(':id', id),
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        success: function (res) {
                            sessionStorage.setItem('warehouseToast', res.message || `"${name}" deleted successfully.`);
                            sessionStorage.setItem('warehouseToastType', 'success');
                            window.location.reload();
                        },
                        error: function (xhr) {
                            let err = 'Failed to delete item.';
                            if (xhr.responseJSON && xhr.responseJSON.message) err = xhr.responseJSON.message;
                            showToast(err, 'danger');
                        }
                    });
                }
            });

            // Submit Restock Form
            $('#stockMovementForm').on('submit', function (e) {
                e.preventDefault();
                clearRestockFormErrors();
                const itemId = $('#restockItemId').val();
                const qtyVal = $('#moveQty').val().trim();
                const cashVal = $('#moveCashAmount').val().trim();
                const remarks = $('#moveRemarks').val().trim();

                const payload = {
                    warehouse_item_id: itemId || '',
                    movement_type: 'Stock Inward',
                    quantity: qtyVal !== '' ? parseFloat(qtyVal) : '',
                    cash_amount: cashVal !== '' ? parseFloat(cashVal) : null,
                    notes: remarks || null
                };

                const $btn = $('#btnConfirmRestock');
                const origBtnText = $btn.html();
                $btn.prop('disabled', true).text('Restocking...');

                $.ajax({
                    url: '{{ route("warehouse.movements.store") }}',
                    type: 'POST',
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        $('#stockMovementModal').modal('hide');
                        $('#stockMovementForm')[0].reset();
                        sessionStorage.setItem('warehouseToast', res.message || 'Restocked successfully!');
                        sessionStorage.setItem('warehouseToastType', 'success');
                        window.location.reload();
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).html(origBtnText);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayRestockFormErrors(xhr.responseJSON.errors);
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstKey][0], 'danger');
                        } else {
                            let err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to record restock.';
                            showToast(err, 'danger');
                        }
                    }
                });
            });

            // Add or Update Inventory Item Form
            $('#warehouseItemForm').on('submit', function (e) {
                e.preventDefault();
                clearWarehouseItemFormErrors();

                const editId = $('#editItemId').val();
                const isEdit = Boolean(editId);
                const editValues = isEdit ? ($('#warehouseItemForm').data('edit-values') || {}) : {};

                const sku = isEdit && editValues.sku !== undefined ? editValues.sku : $('#newItemSku').val().trim();
                const cat = isEdit && editValues.category !== undefined ? editValues.category : ($('#newItemCat').val() || '');
                const nameEn = isEdit && editValues.name_en !== undefined ? editValues.name_en : $('#newItemNameEn').val().trim();
                const nameTa = isEdit && editValues.name_ta !== undefined ? editValues.name_ta : $('#newItemNameTa').val().trim();
                const stockStr = isEdit && editValues.current_stock !== undefined ? String(editValues.current_stock) : $('#newItemStock').val().trim();
                const unitVal = isEdit && editValues.unit_id !== undefined ? String(editValues.unit_id) : ($('#newItemUnit').val() || '').trim();

                const thresholdStr = $('#newItemThreshold').val().trim();
                const cashStr = $('#newItemCashAmount').val().trim();

                const payload = {
                    sku: sku,
                    category: cat || '',
                    name_en: nameEn,
                    name_ta: nameTa,
                    current_stock: stockStr !== '' ? parseFloat(stockStr) : '',
                    unit_id: unitVal !== '' ? parseInt(unitVal) : '',
                    unit: unitVal !== '' ? parseInt(unitVal) : '',
                    min_threshold: thresholdStr !== '' ? parseFloat(thresholdStr) : '',
                    cash_amount: cashStr !== '' ? parseFloat(cashStr) : '',
                    unit_rate: cashStr !== '' ? parseFloat(cashStr) : ''
                };

                const $btn = $('#btnSaveWarehouseItem');
                const origBtnText = isEdit ? 'Update Item' : 'Save Item to Store';
                $btn.prop('disabled', true).text('Saving...');

                const ajaxUrl = isEdit ? '{{ route("warehouse.items.update", ":id") }}'.replace(':id', editId) : '{{ route("warehouse.items.store") }}';
                const ajaxMethod = isEdit ? 'POST' : 'POST';

                $.ajax({
                    url: ajaxUrl,
                    type: ajaxMethod,
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        $btn.prop('disabled', false).text(origBtnText);
                        $('#warehouseItemModal').modal('hide');
                        $('#warehouseItemForm')[0].reset();
                        $('#editItemId').val('');
                        const actionMsg = isEdit ? 'updated successfully' : 'added to warehouse catalog';
                        sessionStorage.setItem('warehouseToast', res.message || `Item "${payload.name_en}" ${actionMsg}!`);
                        sessionStorage.setItem('warehouseToastType', 'success');
                        window.location.reload();
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).text(origBtnText);
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstKey][0], 'danger');
                        } else {
                            let err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (isEdit ? 'Failed to update item.' : 'Failed to create item.');
                            showToast(err, 'danger');
                        }
                    }
                });
            });

            $('#exportWarehouseBtn').on('click', function () {
                window.location.href = '{{ route("reports.site.warehouse-inventory.export-excel") }}';
            });
        });
    </script>
@endpush