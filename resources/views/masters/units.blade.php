@extends('layouts.app')

@section('title', 'Units Master')
@section('page_title', 'Units of Measurement (அளவீட்டு அலகுகள்)')

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
                    <h5 class="fw-bold mb-0 text-dark">Units of Measurement Directory (அளவீடுகள் முதன்மை பட்டியல்)</h5>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#unitModal"
                        id="addUnitBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Measurement Unit
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
                        <input type="text" class="form-control border-start-0 ps-0" id="unitSearchInput"
                            placeholder="Search Unit Name (English / தமிழ்), Short Code...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="unitStatusFilter">
                        <option value="">All Status (அனைத்து நிலை)</option>
                        <option value="Active">Active (செயலில் உள்ளது)</option>
                        <option value="Inactive">Inactive (நிறுத்தப்பட்டது)</option>
                    </select>
                </div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
                <div class="col-12 col-md-3 text-md-end">
                    <span class="small text-muted" id="unitCountBadge">Showing <strong class="text-dark">{{ $units->count() }}</strong>
                        units</span>
                </div>
            </div>
        </div>

        <!-- Units Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="unitsTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Unit Name (English & தமிழ்)</th>
                            <th>Short Symbol / Code</th>
                            <th>Standard Usage Example</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="unitsTableBody">
                        @forelse ($units as $index => $unit)
                            @php
                                $materialsCount = (int) ($unit->materials_count ?? ($unit->materials ? $unit->materials->count() : 0));
                                $inUse = $materialsCount > 0;
                            @endphp
                            <tr data-id="{{ $unit->id }}"
                                data-name-en="{{ $unit->name_en }}"
                                data-name-ta="{{ $unit->name_ta }}"
                                data-code="{{ $unit->code }}"
                                data-usage="{{ $unit->usage ?? '' }}"
                                data-status="{{ $unit->status }}"
                                data-materials-count="{{ $materialsCount }}"
                                data-in-use="{{ $inUse ? '1' : '0' }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="fw-bold text-dark fs-6 row-name-en">{{ $unit->name_en }}</div>
                                    <div class="d-flex align-items-center gap-1 mt-0.5">
                                        <span class="ta-badge row-name-ta">{{ $unit->name_ta }}</span>
                                        @if ($inUse)
                                            <span class="badge bg-light text-secondary border border-secondary-subtle" style="font-size: 0.68rem;" title="Used by {{ $materialsCount }} material(s) in Material Master">
                                                <i class="bi bi-link-45deg me-0.5"></i>{{ $materialsCount }} {{ $materialsCount === 1 ? 'material' : 'materials' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="font-monospace badge bg-primary-subtle text-primary border border-primary-subtle fs-6 row-code">{{ $unit->code }}</span>
                                </td>
                                <td>
                                    <small class="text-muted row-usage">{{ $unit->usage ?: '—' }}</small>
                                </td>
                                <td>
                                    @if ($unit->status === 'Active')
                                        <span class="badge bg-success-subtle text-success row-status-badge">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted row-status-badge">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-unit-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            @if ($inUse)
                                                <li>
                                                    <button class="dropdown-item text-muted disabled" type="button" disabled title="Cannot delete: unit is currently in use in Material Master" style="cursor: not-allowed; opacity: 0.6;">
                                                        <i class="bi bi-lock-fill text-muted me-2"></i>Delete <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.65rem;">In Use</span>
                                                    </button>
                                                </li>
                                            @else
                                                <li><button class="dropdown-item text-danger delete-unit-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyUnitsRow">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No measurement units found. Click <strong>Add Measurement Unit</strong> to create one.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching measurement units found for your search or filter criteria.
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
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(5, $units->count()) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $units->count() }}</strong> units
                    </span>
                </div>
                <nav aria-label="Units table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Unit Add / Edit Modal -->
    <div class="modal fade" id="unitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="unitModalTitle">Add Measurement Unit (அளவீட்டு அலகு)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="unitForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="unitEditId">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="unitNameEn">Unit Name in English <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="unitNameEn" name="name_en"
                                    placeholder="e.g. Kilograms / Litres / Bags" required>
                                <div class="invalid-feedback" id="errNameEn"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="unitNameTa">Unit Name in தமிழ் (Tamil) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control ta-text" id="unitNameTa" name="name_ta"
                                    placeholder="எ.கா. கிலோகிராம் / லிட்டர் / மூட்டை" required>
                                <div class="invalid-feedback" id="errNameTa"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="unitCode">Short Symbol / Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace text-uppercase" id="unitCode" name="code"
                                    placeholder="e.g. KG / LTR / BAG" required>
                                <div class="invalid-feedback" id="errCode"></div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="unitStatus">Status</label>
                                <select class="form-select" id="unitStatus" name="status">
                                    <option value="Active">Active (செயலில் உள்ளது)</option>
                                    <option value="Inactive">Inactive (நிறுத்தப்பட்டது)</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="unitUsage">Standard Usage Examples</label>
                                <input type="text" class="form-control" id="unitUsage" name="usage"
                                    placeholder="e.g. Cement, Steel, Paints, Sand">
                                <div class="invalid-feedback" id="errUsage"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveUnitBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner" role="status" aria-hidden="true"></span>
                            <span id="saveBtnText">Save Measurement Unit</span>
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

            const unitModal = new bootstrap.Modal(document.getElementById('unitModal'));

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#unitSearchInput').val().toLowerCase().trim();
                const status = $('#unitStatusFilter').val();
                const matching = [];

                $('#unitsTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyUnitsRow' || rowId === 'noResultsRow') return;

                    const nameEn = String($(this).data('name-en') || '').toLowerCase();
                    const nameTa = String($(this).data('name-ta') || '').toLowerCase();
                    const code = String($(this).data('code') || '').toLowerCase();
                    const usage = String($(this).data('usage') || '').toLowerCase();
                    const rowStatus = String($(this).data('status') || '');

                    const matchQuery = !query || nameEn.includes(query) || nameTa.includes(query) || code.includes(query) || usage.includes(query);
                    const matchStatus = !status || rowStatus === status;

                    if (matchQuery && matchStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#unitsTableBody tr').not('#emptyUnitsRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#unitsTableBody tr').not('#emptyUnitsRow, #noResultsRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyUnitsRow').length === 0) {
                        $('#unitsTableBody').html(`
                            <tr id="emptyUnitsRow">
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No measurement units found. Click <strong>Add Measurement Unit</strong> to create one.
                                </td>
                            </tr>
                        `);
                    }
                    $('#emptyUnitsRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#unitCountBadge').html('Showing <strong class="text-dark">0</strong> units');
                    return;
                } else {
                    $('#emptyUnitsRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#unitCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> units');
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
                summaryHtml += ' units';
                $('#paginationSummary').html(summaryHtml);

                $('#unitCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> units`);

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

            // Filter on search or status change
            $('#unitSearchInput, #unitStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#unitSearchInput').val('');
                $('#unitStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            function clearFormErrors() {
                $('#unitForm .is-invalid').removeClass('is-invalid');
                $('#unitForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.name_en) {
                    $('#unitNameEn').addClass('is-invalid');
                    $('#errNameEn').text(errors.name_en[0]).show();
                }
                if (errors.name_ta) {
                    $('#unitNameTa').addClass('is-invalid');
                    $('#errNameTa').text(errors.name_ta[0]).show();
                }
                if (errors.code) {
                    $('#unitCode').addClass('is-invalid');
                    $('#errCode').text(errors.code[0]).show();
                }
                if (errors.status) {
                    $('#unitStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
                if (errors.usage) {
                    $('#unitUsage').addClass('is-invalid');
                    $('#errUsage').text(errors.usage[0]).show();
                }
            }

            $('#unitForm input, #unitForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
            });

            $('#unitModal').on('hidden.bs.modal', function () {
                clearFormErrors();
            });

            // Open Add Modal
            $('#addUnitBtn').on('click', function () {
                $('#unitModalTitle').text('Add Measurement Unit (அளவீட்டு அலகு)');
                $('#saveBtnText').text('Save Measurement Unit');
                $('#unitEditId').val('');
                $('#unitForm')[0].reset();
                $('#unitStatus').val('Active');
                clearFormErrors();
            });

            // Open Edit Modal
            $(document).on('click', '.edit-unit-btn', function () {
                const tr = $(this).closest('tr');
                $('#unitModalTitle').text('Edit Measurement Unit (அளவீட்டு அலகு திருத்து)');
                $('#saveBtnText').text('Update Measurement Unit');
                $('#unitEditId').val(tr.data('id'));
                $('#unitNameEn').val(tr.data('name-en'));
                $('#unitNameTa').val(tr.data('name-ta'));
                $('#unitCode').val(tr.data('code'));
                $('#unitStatus').val(tr.data('status') || 'Active');
                $('#unitUsage').val(tr.data('usage') || '');
                clearFormErrors();
                unitModal.show();
            });

            // Submit Add or Edit Form
            $('#unitForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#unitEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("units.update", ":id") }}'.replace(':id', editId) : '{{ route("units.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    name_en: $('#unitNameEn').val().trim(),
                    name_ta: $('#unitNameTa').val().trim(),
                    code: $('#unitCode').val().trim().toUpperCase(),
                    status: $('#unitStatus').val(),
                    usage: $('#unitUsage').val().trim()
                };

                const $btn = $('#saveUnitBtn');
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
                        $btnText.text(isEdit ? 'Update Measurement Unit' : 'Save Measurement Unit');

                        if (res.success && res.data) {
                            const unit = res.data;
                            const displayUsage = unit.usage ? unit.usage : '—';

                            if (isEdit) {
                                const tr = $(`#unitsTableBody tr[data-id="${unit.id}"]`);
                                tr.data('name-en', unit.name_en)
                                  .data('name-ta', unit.name_ta)
                                  .data('code', unit.code)
                                  .data('usage', unit.usage || '')
                                  .data('status', unit.status);

                                tr.find('.row-name-en').text(unit.name_en);
                                tr.find('.row-name-ta').text(unit.name_ta);
                                tr.find('.row-code').text(unit.code);
                                tr.find('.row-usage').text(displayUsage);

                                if (unit.status === 'Active') {
                                    tr.find('.row-status-badge').attr('class', 'badge bg-success-subtle text-success row-status-badge').text('Active');
                                } else {
                                    tr.find('.row-status-badge').attr('class', 'badge bg-secondary-subtle text-muted row-status-badge').text('Inactive');
                                }

                                showToast(res.message || `Unit "${unit.name_en} (${unit.code})" updated successfully!`, 'success');
                            } else {
                                $('#emptyUnitsRow').hide();

                                const newRowHtml = `
                                    <tr data-id="${unit.id}"
                                        data-name-en="${unit.name_en}"
                                        data-name-ta="${unit.name_ta}"
                                        data-code="${unit.code}"
                                        data-usage="${unit.usage || ''}"
                                        data-status="${unit.status}"
                                        data-materials-count="0"
                                        data-in-use="0">
                                        <td class="text-muted fw-semibold serial-cell">01</td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6 row-name-en">${unit.name_en}</div>
                                            <div class="d-flex align-items-center gap-1 mt-0.5">
                                                <span class="ta-badge row-name-ta">${unit.name_ta}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="font-monospace badge bg-primary-subtle text-primary border border-primary-subtle fs-6 row-code">${unit.code}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted row-usage">${displayUsage}</small>
                                        </td>
                                        <td>
                                            <span class="badge ${unit.status === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted'} row-status-badge">
                                                ${unit.status}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown table-action-dropdown">
                                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><button class="dropdown-item edit-unit-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li><button class="dropdown-item text-danger delete-unit-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                `;

                                $('#unitsTableBody').prepend(newRowHtml);
                                currentPage = 1;
                                showToast(res.message || `Unit "${unit.name_en} (${unit.code})" added successfully!`, 'success');
                            }

                            unitModal.hide();
                            updatePagination();
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Measurement Unit' : 'Save Measurement Unit');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Delete Unit
            $(document).on('click', '.delete-unit-btn', function () {
                const tr = $(this).closest('tr');
                const unitId = tr.data('id');
                const nameEn = tr.data('name-en') || 'this unit';
                const code = tr.data('code') || '';
                const materialsCount = parseInt(tr.data('materials-count') || 0, 10);
                const inUse = tr.data('in-use') == '1' || materialsCount > 0;

                if (inUse) {
                    showToast(`Unit "${nameEn} (${code})" is currently used in Material Master and cannot be deleted.`, 'danger');
                    return;
                }

                confirmDelete(`Are you sure you want to remove unit "${nameEn} (${code})"? (அளவீட்டு அலகு நீக்க வேண்டுமா?)`, function () {
                    $.ajax({
                        url: '{{ route("units.destroy", ":id") }}'.replace(':id', unitId),
                        method: 'POST',
                        success: function (res) {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                updatePagination();
                                showToast(res.message || `Unit "${nameEn}" removed.`, 'danger');
                            });
                        },
                        error: function (xhr) {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete unit.';
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