@extends('layouts.app')

@section('title', 'Transaction Type Master')
@section('page_title', 'Transaction Types ')

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
                    <h5 class="fw-bold mb-0 text-dark">Transaction Type Directory</h5>
</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#txnTypeModal" id="addTxnTypeBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Transaction Type
</button>
</div>
</div>
</div>

        <!-- Stat Highlights Row -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-tags-fill fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Total Types</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalTypes">
                            {{ $stats['total'] ?? $transactionTypes->count() }}
</h4>
</div>
</div>
</div>
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Active Types</div>
                        <h4 class="fw-bold mb-0 text-success" id="statActiveTypes">
                            {{ $stats['active'] ?? $transactionTypes->where('status', 'Active')->count() }}
</h4>
</div>
</div>
</div>
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-x-circle fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Inactive Types</div>
                        <h4 class="fw-bold mb-0 text-secondary" id="statInactiveTypes">
                            {{ $stats['inactive'] ?? $transactionTypes->where('status', 'Inactive')->count() }}
</h4>
</div>
</div>
</div>
</div>

        <!-- Filter & Search Card -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="txnTypeSearchInput"
                            placeholder="Search Transaction Name ...">
</div>
</div>
                <div class="col-6 col-md-4">
                    <select class="form-select form-select-sm" id="categoryFilter">
                        <option value="">All Categories</option>
                        <option value="Construction">Construction</option>
                        <option value="Bank Valuation">Bank (Valuation / Inspection fee)</option>
                        <option value="Rental">Rental</option>
                        <option value="Supplier">Supplier</option>
                        <option value="Office and other expenses">Office and other expenses</option>
                    </select>
</div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="txnStatusFilter">
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
</div>
</div>

        <!-- Table Card -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="txnTypesTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Transaction Name</th>
                            <th>Transaction Category</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 70px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="txnTypesTableBody">
                        @forelse ($transactionTypes as $index => $type)
                            <tr data-id="{{ $type->id }}"
                                data-name-en="{{ $type->name_en }}"
                                data-category="{{ $type->category }}"
                                data-status="{{ $type->status }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="fw-bold text-dark fs-6 row-name-en">
                                        {{ $type->name_en }}
</div>
                                </td>
                                <td>
                                    @if ($type->category === 'Construction')
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle row-category-badge">
                                            <i class="bi bi-hammer me-1"></i>Construction
</span>
                                    @elseif ($type->category === 'Bank Valuation')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle row-category-badge">
                                            <i class="bi bi-bank me-1"></i>Bank Valuation
</span>
                                    @elseif ($type->category === 'Rental')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle row-category-badge">
                                            <i class="bi bi-building me-1"></i>Rental
</span>
                                    @elseif ($type->category === 'Supplier')
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle row-category-badge">
                                            <i class="bi bi-truck me-1"></i>Supplier
</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle row-category-badge">
                                            <i class="bi bi-receipt me-1"></i>Office and other expenses
</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($type->status === 'Active')
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
                                            <li>
                                                <button class="dropdown-item edit-txn-type-btn" type="button">
                                                    <i class="bi bi-pencil text-primary me-2"></i>Edit
</button>
                                            </li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li>
                                                <button class="dropdown-item text-danger delete-txn-type-btn" type="button">
                                                    <i class="bi bi-trash3 text-danger me-2"></i>Delete
</button>
                                            </li>
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyTxnTypesRow">
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No transaction types recorded yet. Click <strong>Add Transaction Type</strong> to get started.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>

            <!-- Client-side Pagination Footer -->
            <div class="p-3 border-top d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2" id="paginationWrapper" style="display: none !important;">
                <span class="small text-muted" id="paginationInfo">Showing 1 to 10 entries</span>
                <nav aria-label="Transaction Types pagination">
                    <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                </nav>
</div>
</div>

</div>

    <!-- Create / Edit Modal -->
    <div class="modal fade" id="txnTypeModal" tabindex="-1" aria-labelledby="txnTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="txnTypeModalLabel">Add Transaction Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="txnTypeForm" novalidate>
                    <input type="hidden" id="txnTypeId" name="id">
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" id="modalAlertBox" role="alert"></div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="inputNameEn" class="form-label small fw-semibold">
                                    Transaction Name <span class="text-danger">*</span>
</label>
                                <input type="text" class="form-control" id="inputNameEn" name="name_en"
                                    placeholder="e.g. Client Stage Advance" required maxlength="100">
                                <div class="invalid-feedback" id="errNameEn"></div>
</div>

                            <div class="col-12">
                                <label for="inputCategory" class="form-label small fw-semibold">
                                    Transaction Category <span class="text-danger">*</span>
</label>
                                <select class="form-select" id="inputCategory" name="category" required>
                                    <option value="" disabled selected>-- Select Category --</option>
                                    <option value="Construction">Construction</option>
                                    <option value="Bank Valuation">Bank (Valuation / Inspection fee)</option>
                                    <option value="Rental">Rental</option>
                                    <option value="Supplier">Supplier</option>
                                    <option value="Office and other expenses">Office and other expenses</option>
                                </select>
                                <div class="invalid-feedback" id="errCategory"></div>
</div>

                            <div class="col-12">
                                <label for="inputStatus" class="form-label small fw-semibold">
                                    Status  <span class="text-danger">*</span>
</label>
                                <select class="form-select" id="inputStatus" name="status" required>
                                    <option value="Active" selected>Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveTxnTypeBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner"></span>
                            <span id="saveBtnText">Save Transaction Type</span>
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteTxnTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-1"></i>
</div>
                    <h6 class="fw-bold mb-2">Delete Transaction Type?</h6>
                    <p class="text-muted small mb-3">
                        Are you sure you want to delete <strong class="text-dark" id="deleteTxnTypeName"></strong>? This action cannot be undone.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteTxnTypeBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="deleteSpinner"></span>
                            Delete
</button>
</div>
</div>
</div>
</div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
            let deleteTargetId = null;

            // Pagination state
            const PAGE_SIZE = 10;
            let currentPage = 1;
            let filteredRows = [];

            // Open Add Modal
            $('#addTxnTypeBtn').on('click', function () {
                $('#txnTypeId').val('');
                $('#txnTypeModalLabel').text('Add New Transaction Type');
                $('#saveBtnText').text('Save Transaction Type');
                $('#txnTypeForm')[0].reset();
                $('#inputCategory').val('');
                $('#inputStatus').val('Active');
                clearFormErrors();
                $('#modalAlertBox').addClass('d-none').text('');
            });

            // Open Edit Modal
            $(document).on('click', '.edit-txn-type-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const nameEn = tr.data('name-en');
                const category = tr.data('category');
                const status = tr.data('status');

                $('#txnTypeId').val(id);
                $('#txnTypeModalLabel').text('Edit Transaction Type');
                $('#saveBtnText').text('Update Transaction Type');
                clearFormErrors();
                $('#modalAlertBox').addClass('d-none').text('');

                $('#inputNameEn').val(nameEn);
                $('#inputCategory').val(category);
                $('#inputStatus').val(status);

                const modal = new bootstrap.Modal(document.getElementById('txnTypeModal'));
                modal.show();
            });

            // Input / Select change clears invalid feedback
            $('#txnTypeForm input, #txnTypeForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
            });

            // Form Submit (relies on Laravel FormRequest backend validation)
            $('#txnTypeForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();
                $('#modalAlertBox').addClass('d-none').text('');

                const id = $('#txnTypeId').val();
                const isEdit = Boolean(id);
                const url = isEdit ? '{{ route("transaction-types.update", ":id") }}'.replace(':id', id) : '{{ route("transaction-types.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    _token: csrfToken,
                    _method: method,
                    name_en: $('#inputNameEn').val(),
                    category: $('#inputCategory').val(),
                    status: $('#inputStatus').val()
                };

                const $btn = $('#saveTxnTypeBtn');
                const $spinner = $('#saveSpinner');
                const $btnText = $('#saveBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: postData,
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Transaction Type' : 'Save Transaction Type');

                        if (res.success) {
                            const modal = bootstrap.Modal.getInstance(document.getElementById('txnTypeModal'));
                            if (modal) modal.hide();

                            showToast(res.message, 'success');
                            setTimeout(() => { window.location.reload(); }, 600);
                        } else {
                            $('#modalAlertBox').removeClass('d-none').text(res.message || 'Operation failed.');
                            showToast(res.message || 'Operation failed.', 'danger');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Transaction Type' : 'Save Transaction Type');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving.';
                            $('#modalAlertBox').removeClass('d-none').text(errorMsg);
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Delete click
            $(document).on('click', '.delete-txn-type-btn', function () {
                const tr = $(this).closest('tr');
                deleteTargetId = tr.data('id');
                const name = tr.data('name-en');

                $('#deleteTxnTypeName').text(name);
                const modal = new bootstrap.Modal(document.getElementById('deleteTxnTypeModal'));
                modal.show();
            });

            // Confirm Delete
            $('#confirmDeleteTxnTypeBtn').on('click', function () {
                if (!deleteTargetId) return;

                $('#deleteSpinner').removeClass('d-none');
                $(this).prop('disabled', true);

                $.ajax({
                    url: '{{ route("transaction-types.destroy", ":id") }}'.replace(':id', deleteTargetId),
                    type: 'POST',
                    data: {
                        _token: csrfToken,
                        _method: 'POST'
                    },
                    dataType: 'json',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (res) {
                        $('#deleteSpinner').addClass('d-none');
                        $('#confirmDeleteTxnTypeBtn').prop('disabled', false);

                        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteTxnTypeModal'));
                        if (modal) modal.hide();

                        if (res.success) {
                            showToast(res.message, 'success');
                            setTimeout(() => { window.location.reload(); }, 600);
                        } else {
                            showToast(res.message || 'Could not delete transaction type.', 'danger');
                        }
                    },
                    error: function (xhr) {
                        $('#deleteSpinner').addClass('d-none');
                        $('#confirmDeleteTxnTypeBtn').prop('disabled', false);
                        const msg = xhr.responseJSON?.message || 'Could not delete transaction type.';
                        showToast(msg, 'danger');
                    }
                });
            });

            // Filtering & Search
            function applyFilters() {
                const q = ($('#txnTypeSearchInput').val() || '').toLowerCase().trim();
                const category = $('#categoryFilter').val();
                const status = $('#txnStatusFilter').val();

                filteredRows = [];

                $('#txnTypesTableBody tr:not(#emptyTxnTypesRow)').each(function () {
                    const tr = $(this);
                    const nameEn = (tr.data('name-en') || '').toString().toLowerCase();
                    const rowCategory = (tr.data('category') || '').toString();
                    const rowStatus = (tr.data('status') || '').toString();

                    const matchSearch = !q || nameEn.includes(q);
                    const matchCategory = !category || rowCategory === category;
                    const matchStatus = !status || rowStatus === status;

                    if (matchSearch && matchCategory && matchStatus) {
                        filteredRows.push(tr);
                    } else {
                        tr.hide();
                    }
                });

                currentPage = 1;
                renderPagination();
            }

            function renderPagination() {
                const total = filteredRows.length;

                if (total === 0) {
                    $('#txnTypesTableBody tr:not(#emptyTxnTypesRow)').hide();
                    if ($('#emptyTxnTypesRow').length === 0) {
                        $('#txnTypesTableBody').append(
                            `<tr id="emptyTxnTypesRow"><td colspan="5" class="text-center py-4 text-muted">No matching transaction types found.</td></tr>`
                        );
                    } else {
                        $('#emptyTxnTypesRow').show();
                    }
                    $('#paginationWrapper').attr('style', 'display: none !important;');
                    return;
                }

                $('#emptyTxnTypesRow').hide();

                if (total <= PAGE_SIZE) {
                    filteredRows.forEach((tr, idx) => {
                        tr.find('.serial-cell').text(String(idx + 1).padStart(2, '0'));
                        tr.show();
                    });
                    $('#paginationWrapper').attr('style', 'display: none !important;');
                    return;
                }

                $('#paginationWrapper').removeAttr('style').addClass('d-flex');
                const totalPages = Math.ceil(total / PAGE_SIZE);
                if (currentPage > totalPages) currentPage = totalPages;

                const startIdx = (currentPage - 1) * PAGE_SIZE;
                const endIdx = Math.min(startIdx + PAGE_SIZE, total);

                $('#txnTypesTableBody tr:not(#emptyTxnTypesRow)').hide();
                filteredRows.forEach((tr, idx) => {
                    if (idx >= startIdx && idx < endIdx) {
                        tr.find('.serial-cell').text(String(idx + 1).padStart(2, '0'));
                        tr.show();
                    }
                });

                $('#paginationInfo').text(`Showing ${startIdx + 1} to ${endIdx} of ${total} entries`);

                let paginationHtml = '';
                paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                </li>`;

                for (let p = 1; p <= totalPages; p++) {
                    paginationHtml += `<li class="page-item ${p === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${p}">${p}</a>
                    </li>`;
                }

                paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                </li>`;

                $('#paginationControls').html(paginationHtml);
            }

            $(document).on('click', '#paginationControls .page-link', function (e) {
                e.preventDefault();
                const page = parseInt($(this).data('page'));
                if (!page || page === currentPage) return;
                currentPage = page;
                renderPagination();
            });

            $('#txnTypeSearchInput').on('input', applyFilters);
            $('#categoryFilter, #txnStatusFilter').on('change', applyFilters);

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#txnTypeSearchInput').val('');
                $('#categoryFilter').val('');
                $('#txnStatusFilter').val('');
                currentPage = 1;
                applyFilters();
            });

            // Helpers
            function clearFormErrors() {
                $('#txnTypeForm .is-invalid').removeClass('is-invalid');
                $('#txnTypeForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.name_en && errors.name_en.length > 0) {
                    $('#inputNameEn').addClass('is-invalid');
                    $('#errNameEn').text(errors.name_en[0]).css('display', 'block').show();
                }

                if (errors.category && errors.category.length > 0) {
                    $('#inputCategory').addClass('is-invalid');
                    $('#errCategory').text(errors.category[0]).css('display', 'block').show();
                }

                if (errors.status && errors.status.length > 0) {
                    $('#inputStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).css('display', 'block').show();
                }
            }

            function showToast(message, type = 'success') {
                if (typeof window.showToast === 'function') {
                    window.showToast(message, type);
                    return;
                }

                const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill';
                const toastHtml = `
                    <div class="toast align-items-center text-bg-${type} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body d-flex align-items-center gap-2">
                                <i class="bi ${icon} fs-5"></i>
                                <span>${message}</span>
</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
</div>
                `;
                let toastContainer = document.getElementById('toastContainer');
                if (!toastContainer) {
                    toastContainer = document.createElement('div');
                    toastContainer.id = 'toastContainer';
                    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                    toastContainer.style.zIndex = '9999';
                    document.body.appendChild(toastContainer);
                }
                const $toast = $(toastHtml).appendTo(toastContainer);
                const bsToast = new bootstrap.Toast($toast[0], { delay: 3500 });
                bsToast.show();
                $toast.on('hidden.bs.toast', function () { $(this).remove(); });
            }

            // Initial filter run
            applyFilters();
        });
    </script>
@endpush
