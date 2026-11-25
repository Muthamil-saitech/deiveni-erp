@extends('layouts.app')

@section('title', 'Worker Categories')
@section('page_title', 'Worker Categories ')

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
                    <h5 class="fw-bold mb-0 text-dark">Worker Trade Categories</h5>
</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#categoryModal"
                        id="addCategoryBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Category
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
                        <input type="text" class="form-control border-start-0 ps-0" id="categorySearchInput"
                            placeholder="Search தமிழ் / English category name ...">
</div>
</div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="catStatusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
</div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
                <div class="col-12 col-md-3 text-md-end">
                    <span class="small text-muted" id="categoryCountBadge">Showing <strong
                            class="text-dark">{{ $categories->count() }}</strong>
                        categories</span>
</div>
</div>
</div>

        <!-- Worker Categories Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="categoriesTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Trade Category</th>
                            <th>Category Code</th>
                            <th>Default Daily Wage (₹)</th>
                            <th>Standard Shift</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody">
                        @forelse ($categories as $index => $category)
                            @php
                                $badgeColor = 'bg-primary bg-opacity-10 text-primary';
                                $iconClass = 'bi bi-hammer';

                                $lowerNameEn = strtolower($category->name_en);
                                if (str_contains($lowerNameEn, 'mason') || str_contains($lowerNameEn, 'bricklayer')) {
                                    $iconClass = 'bi bi-bricks';
                                    $badgeColor = 'bg-info bg-opacity-10 text-info';
                                } elseif (str_contains($lowerNameEn, 'female')) {
                                    $iconClass = 'bi bi-person';
                                    $badgeColor = 'bg-danger bg-opacity-10 text-danger';
                                } elseif (str_contains($lowerNameEn, 'helper') || str_contains($lowerNameEn, 'assistant')) {
                                    $iconClass = 'bi bi-person-walking';
                                    $badgeColor = 'bg-warning bg-opacity-10 text-warning';
                                } elseif (str_contains($lowerNameEn, 'bender') || str_contains($lowerNameEn, 'steel')) {
                                    $iconClass = 'bi bi-grid-3x3';
                                    $badgeColor = 'bg-secondary bg-opacity-10 text-secondary';
                                } elseif (str_contains($lowerNameEn, 'carpenter') || str_contains($lowerNameEn, 'centering')) {
                                    $iconClass = 'bi bi-tools';
                                    $badgeColor = 'bg-success bg-opacity-10 text-success';
                                } elseif (str_contains($lowerNameEn, 'painter') || str_contains($lowerNameEn, 'finisher')) {
                                    $iconClass = 'bi bi-paint-bucket';
                                    $badgeColor = 'bg-warning bg-opacity-10 text-dark';
                                } elseif (str_contains($lowerNameEn, 'electrician') || str_contains($lowerNameEn, 'plumber')) {
                                    $iconClass = 'bi bi-lightning-charge';
                                    $badgeColor = 'bg-primary bg-opacity-10 text-primary';
                                }
                            @endphp
                            <tr data-id="{{ $category->id }}" data-name-ta="{{ $category->name_ta }}"
                                data-name-en="{{ $category->name_en }}" data-code="{{ $category->code }}"
                                data-wage="{{ $category->daily_wage }}" data-shift="{{ $category->shift }}"
                                data-status="{{ $category->status }}" data-icon="{{ $iconClass }}"
                                data-badge="{{ $badgeColor }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle {{ $badgeColor }} d-flex align-items-center justify-content-center fw-bold me-2 icon-wrapper"
                                            style="width: 36px; height: 36px;">
                                            <i class="{{ $iconClass }}"></i>
</div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 ta-text row-name-ta">{{ $category->name_en }}
</div>
                                            <small class="text-muted row-name-en">{{ $category->name_ta }}</small>
</div>
</div>
                                </td>
                                <td><span
                                        class="font-monospace badge bg-light text-dark border row-code">{{ $category->code }}</span>
                                </td>
                                <td>
                                    <span
                                        class="fw-bold text-success fs-6 row-wage">₹{{ number_format((float) $category->daily_wage, 0) }}</span>
                                    <small class="text-muted">/ day</small>
                                </td>
                                <td><span class="small text-muted row-shift"><i
                                            class="bi bi-clock me-1"></i>{{ $category->shift }}</span></td>
                                <td>
                                    @if ($category->status === 'Active')
                                        <span class="badge bg-success-subtle text-success row-status-badge">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted row-status-badge">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-cat-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            <li><button class="dropdown-item text-danger delete-cat-btn" type="button"><i
                                                        class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyCategoriesRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No worker categories found. Click <strong>Add Category</strong> to create one.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching worker categories found for your search or filter criteria.
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
                            id="pageEnd">{{ min(5, $categories->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $categories->count() }}</strong> categories
</span>
</div>
                <nav aria-label="Worker category table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- Add / Edit Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="categoryModalTitle">Add Worker Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="categoryForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="categoryEditId">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="catNameEn">Category Name in English <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="catNameEn" name="name_en"
                                    placeholder="e.g. Brick Mason / Helper" required>
                                <div class="invalid-feedback" id="errNameEn"></div>
</div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="catNameTa">Category Name in Tamil (Tamil)
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control ta-text" id="catNameTa" name="name_ta"
                                    placeholder="எ.கா. கொத்தனார் / மேஸ்திரி / சித்தாள்" required>
                                <div class="invalid-feedback" id="errNameTa"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label
                                    class="form-label small fw-semibold d-flex justify-content-between align-items-center"
                                    for="catCode">
                                    <span>Category Code</span>
                                    <span class="badge bg-light text-muted border"
                                        style="font-size: 0.68rem;">Auto-generated</span>
</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i
                                            class="bi bi-lock-fill"></i></span>
                                    <input type="text" class="form-control font-monospace bg-light" id="catCode"
                                        placeholder="Auto-generated" readonly tabindex="-1">
</div>
                                <small class="text-muted" style="font-size: 0.72rem;">
                                    Auto-generated (User cannot fill)
                                </small>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="catWage">Default Daily Wage (₹) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="catWage" name="daily_wage"
                                        placeholder="1100" required min="1" step="0.01">
                                    <div class="invalid-feedback" id="errWage"></div>
</div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="catShift">Standard Shift</label>
                                <input type="text" class="form-control" id="catShift" name="shift"
                                    value="8 Hours (8:30 AM - 5:30 PM)">
                                <div class="invalid-feedback" id="errShift"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="catStatus">Status</label>
                                <select class="form-select" id="catStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveCategoryBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner" role="status"
                                aria-hidden="true"></span>
                            <span id="saveBtnText">Save Category</span>
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

            const categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));

            function getCategoryIcon(nameEn) {
                const lower = (nameEn || '').toLowerCase();
                if (lower.includes('mason') || lower.includes('bricklayer')) {
                    return { icon: 'bi bi-bricks', badge: 'bg-info bg-opacity-10 text-info' };
                } else if (lower.includes('female')) {
                    return { icon: 'bi bi-person', badge: 'bg-danger bg-opacity-10 text-danger' };
                } else if (lower.includes('helper') || lower.includes('assistant')) {
                    return { icon: 'bi bi-person-walking', badge: 'bg-warning bg-opacity-10 text-warning' };
                } else if (lower.includes('bender') || lower.includes('steel')) {
                    return { icon: 'bi bi-grid-3x3', badge: 'bg-secondary bg-opacity-10 text-secondary' };
                } else if (lower.includes('carpenter') || lower.includes('centering')) {
                    return { icon: 'bi bi-tools', badge: 'bg-success bg-opacity-10 text-success' };
                } else if (lower.includes('painter') || lower.includes('finisher')) {
                    return { icon: 'bi bi-paint-bucket', badge: 'bg-warning bg-opacity-10 text-dark' };
                } else if (lower.includes('electrician') || lower.includes('plumber')) {
                    return { icon: 'bi bi-lightning-charge', badge: 'bg-primary bg-opacity-10 text-primary' };
                }
                return { icon: 'bi bi-hammer', badge: 'bg-primary bg-opacity-10 text-primary' };
            }

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#categorySearchInput').val().toLowerCase().trim();
                const status = $('#catStatusFilter').val();
                const matching = [];

                $('#categoriesTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyCategoriesRow' || rowId === 'noResultsRow') return;

                    const nameTa = String($(this).data('name-ta') || '').toLowerCase();
                    const nameEn = String($(this).data('name-en') || '').toLowerCase();
                    const code = String($(this).data('code') || '').toLowerCase();
                    const rowStatus = String($(this).data('status') || '');

                    const matchQuery = !query || nameTa.includes(query) || nameEn.includes(query) || code.includes(query);
                    const matchStatus = !status || rowStatus === status;

                    if (matchQuery && matchStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#categoriesTableBody tr').not('#emptyCategoriesRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#categoriesTableBody tr').not('#emptyCategoriesRow, #noResultsRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyCategoriesRow').length === 0) {
                        $('#categoriesTableBody').html(`
                                                                <tr id="emptyCategoriesRow">
                                                                    <td colspan="7" class="text-center py-5 text-muted">
                                                                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                                                        No worker categories found. Click <strong>Add Category</strong> to create one.
                                                                    </td>
                                                                </tr>
                                                            `);
                    }
                    $('#emptyCategoriesRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#categoryCountBadge').html('Showing <strong class="text-dark">0</strong> categories');
                    return;
                } else {
                    $('#emptyCategoriesRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#categoryCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> categories');
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
                summaryHtml += ' categories';
                $('#paginationSummary').html(summaryHtml);

                $('#categoryCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> categories`);

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
            $('#categorySearchInput, #catStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#categorySearchInput').val('');
                $('#catStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            function clearFormErrors() {
                $('#categoryForm .is-invalid').removeClass('is-invalid');
                $('#categoryForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (errors.name_ta) {
                    $('#catNameTa').addClass('is-invalid');
                    $('#errNameTa').text(errors.name_ta[0]).show();
                }
                if (errors.name_en) {
                    $('#catNameEn').addClass('is-invalid');
                    $('#errNameEn').text(errors.name_en[0]).show();
                }
                if (errors.daily_wage) {
                    $('#catWage').addClass('is-invalid');
                    $('#errWage').text(errors.daily_wage[0]).show();
                }
                if (errors.shift) {
                    $('#catShift').addClass('is-invalid');
                    $('#errShift').text(errors.shift[0]).show();
                }
                if (errors.status) {
                    $('#catStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
            }

            function fetchNextCategoryCode() {
                $('#catCode').val('Generating...');
                $.ajax({
                    url: '{{ route("worker-categories.next-code") }}',
                    method: 'GET',
                    success: function (res) {
                        if (res && res.code) {
                            $('#catCode').val(res.code);
                        }
                    },
                    error: function () {
                        $('#catCode').val('Auto-generated');
                    }
                });
            }

            // Open Add Modal
            $('#addCategoryBtn').on('click', function () {
                $('#categoryModalTitle').text('Add Worker Category ');
                $('#categoryEditId').val('');
                $('#categoryForm')[0].reset();
                $('#catShift').val('8 Hours (8:30 AM - 5:30 PM)');
                $('#catStatus').val('Active');
                clearFormErrors();
                fetchNextCategoryCode();
            });

            // Open Edit Modal
            $(document).on('click', '.edit-cat-btn', function () {
                const tr = $(this).closest('tr');
                $('#categoryModalTitle').text('Edit Worker Category ');
                $('#categoryEditId').val(tr.data('id'));
                $('#catNameTa').val(tr.data('name-ta'));
                $('#catNameEn').val(tr.data('name-en'));
                $('#catCode').val(tr.data('code'));
                $('#catWage').val(tr.data('wage'));
                $('#catShift').val(tr.data('shift') || '8 Hours (8:30 AM - 5:30 PM)');
                $('#catStatus').val(tr.data('status') || 'Active');
                clearFormErrors();
                categoryModal.show();
            });

            // Submit Add or Edit Form
            $('#categoryForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#categoryEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("worker-categories.update", ":id") }}'.replace(':id', editId) : '{{ route("worker-categories.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    name_ta: $('#catNameTa').val().trim(),
                    name_en: $('#catNameEn').val().trim(),
                    daily_wage: $('#catWage').val(),
                    shift: $('#catShift').val().trim() || '8 Hours (8:30 AM - 5:30 PM)',
                    status: $('#catStatus').val()
                };

                const $btn = $('#saveCategoryBtn');
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
                        $btnText.text('Save Category');

                        if (res.success && res.data) {
                            const cat = res.data;
                            const formattedWage = Number(cat.daily_wage).toLocaleString('en-IN');
                            const iconConfig = getCategoryIcon(cat.name_en);

                            if (isEdit) {
                                const tr = $(`#categoriesTableBody tr[data-id="${cat.id}"]`);
                                tr.data('name-ta', cat.name_ta)
                                    .data('name-en', cat.name_en)
                                    .data('code', cat.code)
                                    .data('wage', cat.daily_wage)
                                    .data('shift', cat.shift)
                                    .data('status', cat.status);

                                tr.find('.row-name-ta').text(cat.name_ta);
                                tr.find('.row-name-en').text(cat.name_en);
                                tr.find('.row-code').text(cat.code);
                                tr.find('.row-wage').text(`₹${formattedWage}`);
                                tr.find('.row-shift').html(`<i class="bi bi-clock me-1"></i>${cat.shift}`);
                                tr.find('.icon-wrapper').attr('class', `rounded-circle ${iconConfig.badge} d-flex align-items-center justify-content-center fw-bold me-2 icon-wrapper`).html(`<i class="${iconConfig.icon}"></i>`);

                                if (cat.status === 'Active') {
                                    tr.find('.row-status-badge').attr('class', 'badge bg-success-subtle text-success row-status-badge').text('Active');
                                } else {
                                    tr.find('.row-status-badge').attr('class', 'badge bg-secondary-subtle text-muted row-status-badge').text('Inactive');
                                }

                                showToast(res.message || `Category "${cat.name_ta}" updated successfully!`, 'success');
                            } else {
                                $('#emptyCategoriesRow').hide();

                                const newRowHtml = `
                                                                        <tr data-id="${cat.id}"
                                                                            data-name-ta="${cat.name_ta}"
                                                                            data-name-en="${cat.name_en}"
                                                                            data-code="${cat.code}"
                                                                            data-wage="${cat.daily_wage}"
                                                                            data-shift="${cat.shift}"
                                                                            data-status="${cat.status}"
                                                                            data-icon="${iconConfig.icon}"
                                                                            data-badge="${iconConfig.badge}">
                                                                            <td class="text-muted fw-semibold serial-cell">01</td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <div class="rounded-circle ${iconConfig.badge} d-flex align-items-center justify-content-center fw-bold me-2 icon-wrapper"
                                                                                        style="width: 36px; height: 36px;">
                                                                                        <i class="${iconConfig.icon}"></i>
</div>
                                                                                    <div>
                                                                                        <div class="fw-bold text-dark fs-6 ta-text row-name-ta">${cat.name_en}</div>
                                                                                        <small class="text-muted row-name-en">${cat.name_ta}</small>
</div>
</div>
                                                                            </td>
                                                                            <td><span class="font-monospace badge bg-light text-dark border row-code">${cat.code}</span></td>
                                                                            <td>
                                                                                <span class="fw-bold text-success fs-6 row-wage">₹${formattedWage}</span>
                                                                                <small class="text-muted">/ day</small>
                                                                            </td>
                                                                            <td><span class="small text-muted row-shift"><i class="bi bi-clock me-1"></i>${cat.shift}</span></td>
                                                                            <td>
                                                                                <span class="badge ${cat.status === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted'} row-status-badge">
                                                                                    ${cat.status}
</span>
                                                                            </td>
                                                                            <td class="text-end">
                                                                                <div class="dropdown table-action-dropdown">
                                                                                    <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                                                        <i class="bi bi-three-dots-vertical"></i>
</button>
                                                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                                                        <li><button class="dropdown-item edit-cat-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                                                        <li><hr class="dropdown-divider my-1"></li>
                                                                                        <li><button class="dropdown-item text-danger delete-cat-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                                                    </ul>
</div>
                                                                            </td>
                                                                        </tr>
                                                                    `;

                                $('#categoriesTableBody').prepend(newRowHtml);
                                currentPage = 1;
                                showToast(res.message || `Category "${cat.name_ta}" added successfully!`, 'success');
                            }

                            categoryModal.hide();
                            updatePagination();
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Save Category');

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

            // Delete Category
            $(document).on('click', '.delete-cat-btn', function () {
                const tr = $(this).closest('tr');
                const catId = tr.data('id');
                const nameTa = tr.data('name-ta') || tr.data('name-en') || 'this category';

                confirmDelete(`Are you sure you want to remove category "${nameTa}"?`, function () {
                    $.ajax({
                        url: '{{ route("worker-categories.destroy", ":id") }}'.replace(':id', catId),
                        method: 'POST',
                        success: function (res) {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                updatePagination();
                                showToast(`Category "${nameTa}" removed successfully.`, 'danger');
                            });
                        },
                        error: function (xhr) {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete category.';
                            showToast(errorMsg, 'danger');
                        }
                    });
                });
            });

            // Initial pagination setup on page load
            updatePagination();
        });
    </script>
@endpush