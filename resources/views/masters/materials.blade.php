@extends('layouts.app')

@section('title', 'Materials Master')
@section('page_title', 'Materials Master ')

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
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Materials Catalog</h5>
</div>
                <div class="d-flex gap-2">
                    <a href="{{ url('/units') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-rulers me-1"></i> Manage Units
</a>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#materialModal"
                        id="addMaterialBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Material
</button>
</div>
</div>
</div>

        <!-- Filter & Search Card -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="materialSearchInput"
                            placeholder="Search by English / தமிழ் Name, Unit...">
</div>
</div>
                <!-- Unit Select Box Filter -->
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="unitFilter">
                        <option value="">All Units</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name_en }} ({{ $unit->name_ta }})</option>
                        @endforeach
                    </select>
</div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
                <div class="col-12 col-md-3 text-md-end">
                    <span class="small text-muted" id="materialCountBadge">Showing <strong
                            class="text-dark">{{ $materials->count() }}</strong> items</span>
</div>
</div>
</div>

        <!-- Materials Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="materialsTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Material / Item Name</th>
                            <th>Unit of Measurement</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTableBody">
                        @forelse ($materials as $index => $material)
                            @php
                                $suppliersCount = (int) ($material->suppliers_count ?? ($material->suppliers ? $material->suppliers->count() : 0));
                                $inUse = $suppliersCount > 0;
                                $unitModel = $material->unit;
                                $unitName = $unitModel?->name_en ?? '';
                                $unitId = $material->unit_id ?? $unitModel?->id ?? '';
                                $unitLower = strtolower($unitName);
                                $unitIcon = 'bi bi-box';
                                if (str_contains($unitLower, 'bag')) {
                                    $unitIcon = 'bi bi-box';
                                } elseif (str_contains($unitLower, 'kg') || str_contains($unitLower, 'kilo')) {
                                    $unitIcon = 'bi bi-speedometer2';
                                } elseif (str_contains($unitLower, 'load')) {
                                    $unitIcon = 'bi bi-truck-flatbed';
                                } elseif (str_contains($unitLower, 'nos') || str_contains($unitLower, 'piece')) {
                                    $unitIcon = 'bi bi-bounding-box';
                                } elseif (str_contains($unitLower, 'litre') || str_contains($unitLower, 'ltr')) {
                                    $unitIcon = 'bi bi-droplet-half';
                                } elseif (str_contains($unitLower, 'hour')) {
                                    $unitIcon = 'bi bi-clock';
                                } elseif (str_contains($unitLower, 'ton')) {
                                    $unitIcon = 'bi bi-speedometer';
                                } elseif (str_contains($unitLower, 'sq') || str_contains($unitLower, 'feet')) {
                                    $unitIcon = 'bi bi-aspect-ratio';
                                } elseif (str_contains($unitLower, 'brass')) {
                                    $unitIcon = 'bi bi-layers';
                                }
                            @endphp
                            <tr data-id="{{ $material->id }}" data-name-en="{{ $material->name_en }}"
                                data-name-ta="{{ $material->name_ta }}" data-unit-id="{{ $unitId }}" data-unit="{{ $unitName }}"
                                data-status="{{ $material->status }}" data-suppliers-count="{{ $suppliersCount }}"
                                data-in-use="{{ $inUse ? '1' : '0' }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6 row-name-en">{{ $material->name_en }}</div>
                                    <div class="d-flex align-items-center gap-1 mt-0.5">
                                        <span class="ta-badge row-name-ta">{{ $material->name_ta }}</span>
                                        @if ($inUse)
                                            <span class="badge bg-light text-secondary border border-secondary-subtle"
                                                style="font-size: 0.68rem;"
                                                title="Supplied by {{ $suppliersCount }} {{ $suppliersCount === 1 ? 'supplier' : 'suppliers' }} in Supplier Master">
                                                <i class="bi bi-truck me-0.5"></i>{{ $suppliersCount }}
                                                {{ $suppliersCount === 1 ? 'supplier' : 'suppliers' }}
</span>
                                        @endif
</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border fw-semibold px-2 py-1 row-unit-badge">
                                        <i class="{{ $unitIcon }} me-1"></i><span class="row-unit-text">{{ $unitName }}</span>
</span>
                                </td>
                                <td>
                                    @if ($material->status === 'Active')
                                        <span class="badge bg-success-subtle text-success row-status-badge">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted row-status-badge">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-mat-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            @if ($inUse)
                                                <li>
                                                    <button class="dropdown-item text-muted disabled" type="button" disabled
                                                        title="Cannot delete: material is currently in use in Supplier Master"
                                                        style="cursor: not-allowed; opacity: 0.6;">
                                                        <i class="bi bi-lock-fill text-muted me-2"></i>Delete <span
                                                            class="badge bg-secondary-subtle text-secondary ms-1"
                                                            style="font-size: 0.65rem;">In Use</span>
</button>
                                                </li>
                                            @else
                                                <li><button class="dropdown-item text-danger delete-mat-btn" type="button"><i
                                                            class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                            @endif
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyMaterialsRow">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-2 d-block mb-2 text-secondary"></i>
                                    No materials found. Click <strong>Add Material</strong> to create one.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching materials found for your search or filter criteria.
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
                            id="pageEnd">{{ min(5, $materials->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $materials->count() }}</strong> items
</span>
</div>
                <nav aria-label="Materials table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- Material Add / Edit Modal with UNIT SELECT BOX -->
    <div class="modal fade" id="materialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="materialModalTitle">Add Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="materialForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="materialEditId">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="matNameEn">Material Name (English) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="matNameEn" name="name_en"
                                    placeholder="e.g. M-Sand / Cement" required>
                                <div class="invalid-feedback" id="errNameEn"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="matNameTa">Name in Tamil (Tamil) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control ta-text" id="matNameTa" name="name_ta"
                                    placeholder="எ.கா. மணல் / சிமெண்ட்" required>
                                <div class="invalid-feedback" id="errNameTa"></div>
</div>

                            <!-- UNIT SELECT BOX linked to Units Master -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="matUnitSelect">
                                    Unit of Measurement  <span class="text-danger">*</span>
</label>
                                <select class="form-select fw-semibold" id="matUnitSelect" name="unit_id" required>
                                    <option value="" disabled selected>-- Select Measurement Unit --</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" data-code="{{ $unit->code }}"
                                            data-name="{{ $unit->name_en }}">
                                            {{ $unit->name_en }} ({{ $unit->name_ta }})
</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errUnitId"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="matStatus">Status</label>
                                <select class="form-select" id="matStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveMaterialBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner" role="status"
                                aria-hidden="true"></span>
                            <span id="saveBtnText">Save Material</span>
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
            // Setup CSRF token for all jQuery AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const materialModalEl = document.getElementById('materialModal');
            const materialModal = new bootstrap.Modal(materialModalEl);

            function getUnitIcon(unit) {
                const u = (unit || '').toLowerCase();
                if (u.includes('bag')) return 'bi bi-box';
                if (u.includes('kg') || u.includes('kilo')) return 'bi bi-speedometer2';
                if (u.includes('load')) return 'bi bi-truck-flatbed';
                if (u.includes('nos') || u.includes('piece')) return 'bi bi-bounding-box';
                if (u.includes('litre') || u.includes('ltr')) return 'bi bi-droplet-half';
                if (u.includes('day') || u.includes('shift')) return 'bi bi-calendar-day';
                if (u.includes('ton')) return 'bi bi-speedometer';
                if (u.includes('sq') || u.includes('feet')) return 'bi bi-aspect-ratio';
                if (u.includes('brass')) return 'bi bi-layers';
                return 'bi bi-box';
            }

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#materialSearchInput').val().toLowerCase().trim();
                const selectedUnitId = $('#unitFilter').val();
                const matching = [];

                $('#materialsTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyMaterialsRow' || rowId === 'noResultsRow') return;

                    const nameEn = String($(this).data('name-en') || '').toLowerCase();
                    const nameTa = String($(this).data('name-ta') || '').toLowerCase();
                    const rowUnit = String($(this).data('unit') || '').toLowerCase();
                    const rowUnitId = String($(this).data('unit-id') || '');

                    const matchQuery = !query || nameEn.includes(query) || nameTa.includes(query) || rowUnit.includes(query);
                    const matchUnit = !selectedUnitId || rowUnitId === String(selectedUnitId);

                    if (matchQuery && matchUnit) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#materialsTableBody tr').not('#emptyMaterialsRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#materialsTableBody tr').not('#emptyMaterialsRow, #noResultsRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyMaterialsRow').length === 0) {
                        $('#materialsTableBody').html(`
                                <tr id="emptyMaterialsRow">
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-box-seam fs-2 d-block mb-2 text-secondary"></i>
                                        No materials found. Click <strong>Add Material</strong> to create one.
                                    </td>
                                </tr>
                            `);
                    }
                    $('#emptyMaterialsRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#materialCountBadge').html('Showing <strong class="text-dark">0</strong> items');
                    return;
                } else {
                    $('#emptyMaterialsRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#materialCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> items');
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
                summaryHtml += ' items';
                $('#paginationSummary').html(summaryHtml);

                $('#materialCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> items`);

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

            // Filter on search or unit change
            $('#materialSearchInput, #unitFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#materialSearchInput').val('');
                $('#unitFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            function clearFormErrors() {
                $('#materialForm .is-invalid').removeClass('is-invalid');
                $('#materialForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (errors.name_en) {
                    $('#matNameEn').addClass('is-invalid');
                    $('#errNameEn').text(errors.name_en[0]).show();
                }
                if (errors.name_ta) {
                    $('#matNameTa').addClass('is-invalid');
                    $('#errNameTa').text(errors.name_ta[0]).show();
                }
                if (errors.unit_id || errors.unit) {
                    const err = errors.unit_id ? errors.unit_id[0] : errors.unit[0];
                    $('#matUnitSelect').addClass('is-invalid');
                    $('#errUnitId').text(err).show();
                }
                if (errors.status) {
                    $('#matStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
            }

            function setSelectUnit(unitVal) {
                if (!unitVal) {
                    $('#matUnitSelect').val('');
                    return;
                }

                // Try exact value (ID) match
                $('#matUnitSelect').val(String(unitVal));
                if ($('#matUnitSelect').val()) {
                    return;
                }

                // Try case-insensitive name or code match for fallback
                const target = String(unitVal).toLowerCase();
                let matched = false;
                $('#matUnitSelect option').each(function () {
                    const val = String($(this).val() || '').toLowerCase();
                    const name = String($(this).data('name') || '').toLowerCase();
                    const code = String($(this).data('code') || '').toLowerCase();
                    if (val === target || (name && name === target) || (code && code === target)) {
                        $('#matUnitSelect').val($(this).val());
                        matched = true;
                        return false;
                    }
                });

                // If the unit was deactivated in unit master, keep it selectable for this existing record
                if (!matched && unitVal) {
                    if ($(`#matUnitSelect option[value="${unitVal}"]`).length === 0) {
                        $('#matUnitSelect').append(`<option value="${unitVal}" data-inactive="true">${unitVal} (Inactive)</option>`);
                    }
                    $('#matUnitSelect').val(String(unitVal));
                }
            }



            // Open Add Modal
            $('#addMaterialBtn').on('click', function () {
                $('#materialModalTitle').text('Add Material ');
                $('#materialEditId').val('');
                $('#materialForm')[0].reset();
                $('#matUnitSelect option[data-inactive="true"]').remove();
                $('#matUnitSelect').val('');
                $('#matStatus').val('Active');
                clearFormErrors();
            });

            // Open Edit Modal
            $(document).on('click', '.edit-mat-btn', function () {
                const tr = $(this).closest('tr');
                $('#materialModalTitle').text('Edit Material ');
                $('#materialEditId').val(tr.data('id'));
                $('#matNameEn').val(tr.data('name-en'));
                $('#matNameTa').val(tr.data('name-ta'));
                setSelectUnit(tr.data('unit-id') || tr.data('unit'));
                $('#matStatus').val(tr.data('status') || 'Active');
                clearFormErrors();

                materialModal.show();
            });

            // Form Submit (AJAX Add or Edit)
            $('#materialForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#materialEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("materials.update", ":id") }}'.replace(':id', editId) : '{{ route("materials.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    name_en: $('#matNameEn').val().trim(),
                    name_ta: $('#matNameTa').val().trim(),
                    unit_id: $('#matUnitSelect').val(),
                    status: $('#matStatus').val()
                };

                const $btn = $('#saveMaterialBtn');
                const $spinner = $('#saveSpinner');
                const $btnText = $('#saveBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    method: method,
                    data: postData,
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Save Material');

                        if (res.success && res.data) {
                            const mat = res.data;
                            const unitName = (mat.unit && mat.unit.name_en)
                                ? mat.unit.name_en
                                : ($(`#matUnitSelect option[value="${mat.unit_id}"]`).data('name') || $('#matUnitSelect option:selected').data('name') || '');
                            const unitIcon = getUnitIcon(unitName);

                            if (isEdit) {
                                const tr = $(`#materialsTableBody tr[data-id="${mat.id}"]`);
                                tr.data('name-en', mat.name_en)
                                    .data('name-ta', mat.name_ta)
                                    .data('unit-id', mat.unit_id)
                                    .data('unit', unitName)
                                    .data('status', mat.status);

                                tr.find('.row-name-en').text(mat.name_en);
                                tr.find('.row-name-ta').text(mat.name_ta);
                                tr.find('.row-unit-badge').html(`<i class="${unitIcon} me-1"></i><span class="row-unit-text">${unitName}</span>`);

                                if (mat.status === 'Active') {
                                    tr.find('.row-status-badge').attr('class', 'badge bg-success-subtle text-success row-status-badge').text('Active');
                                } else {
                                    tr.find('.row-status-badge').attr('class', 'badge bg-secondary-subtle text-muted row-status-badge').text('Inactive');
                                }

                                showToast(res.message || `Material "${mat.name_en}" updated successfully!`, 'success');
                            } else {
                                $('#emptyMaterialsRow').hide();

                                const statusBadge = mat.status === 'Active'
                                    ? '<span class="badge bg-success-subtle text-success row-status-badge">Active</span>'
                                    : '<span class="badge bg-secondary-subtle text-muted row-status-badge">Inactive</span>';

                                const newRowHtml = `
                                        <tr data-id="${mat.id}"
                                            data-name-en="${mat.name_en}"
                                            data-name-ta="${mat.name_ta}"
                                            data-unit-id="${mat.unit_id}"
                                            data-unit="${unitName}"
                                            data-status="${mat.status}"
                                            data-suppliers-count="0"
                                            data-in-use="0">
                                            <td class="text-muted fw-semibold serial-cell">01</td>
                                            <td>
                                                <div class="fw-bold text-dark fs-6 row-name-en">${mat.name_en}</div>
                                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                                    <span class="ta-badge row-name-ta">${mat.name_ta}</span>
</div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-primary border fw-semibold px-2 py-1 row-unit-badge">
                                                    <i class="${unitIcon} me-1"></i><span class="row-unit-text">${unitName}</span>
</span>
                                            </td>
                                            <td>${statusBadge}</td>
                                            <td class="text-end">
                                                <div class="dropdown table-action-dropdown">
                                                    <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                        <i class="bi bi-three-dots-vertical"></i>
</button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li><button class="dropdown-item edit-mat-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li><button class="dropdown-item text-danger delete-mat-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                    </ul>
</div>
                                            </td>
                                        </tr>
                                    `;

                                $('#materialsTableBody').prepend(newRowHtml);
                                currentPage = 1;
                                showToast(res.message || `Material "${mat.name_en}" added successfully!`, 'success');
                            }

                            materialModal.hide();
                            updatePagination();
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Save Material');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving material.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Delete Material
            $(document).on('click', '.delete-mat-btn', function () {
                const tr = $(this).closest('tr');
                const matId = tr.data('id');
                const nameEn = tr.data('name-en') || 'this material';
                const suppliersCount = parseInt(tr.data('suppliers-count') || 0, 10);
                const inUse = tr.data('in-use') == '1' || suppliersCount > 0;

                if (inUse) {
                    showToast(`Material "${nameEn}" is currently used in Supplier Master and cannot be deleted.`, 'danger');
                    return;
                }

                confirmDelete(`Remove "${nameEn}" from materials master?`, function () {
                    $.ajax({
                        url: '{{ route("materials.destroy", ":id") }}'.replace(':id', matId),
                        method: 'POST',
                        success: function (res) {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                updatePagination();
                                showToast(res.message || `Material "${nameEn}" removed.`, 'danger');
                            });
                        },
                        error: function (xhr) {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete material.';
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