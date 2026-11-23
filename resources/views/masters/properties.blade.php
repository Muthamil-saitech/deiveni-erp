@extends('layouts.app')

@section('title', 'Property Master')
@section('page_title', 'Property Master (சொத்து முதன்மை பட்டியல்)')

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
                    <h5 class="fw-bold mb-0 text-dark">Properties Directory (சொத்து விவரங்கள்)</h5>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#propertyModal"
                        id="addPropertyBtn">
                        <i class="bi bi-building-add me-1"></i> Add Property
                    </button>
                </div>
            </div>
        </div>

        <!-- Stat Highlights Row -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Properties</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalProperties">
                            {{ $stats['total_properties'] ?? $properties->count() }}
                        </h4>
                        <small class="ta-text text-muted" style="font-size:0.72rem;">மொத்த சொத்துக்கள்</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Active Properties</div>
                        <h4 class="fw-bold mb-0 text-success" id="statActiveProperties">
                            {{ $stats['active_properties'] ?? $properties->where('status', 'Active')->count() }}
                        </h4>
                        <small class="ta-text text-muted" style="font-size:0.72rem;">செயலில் உள்ளவை</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-x-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Inactive Properties</div>
                        <h4 class="fw-bold mb-0 text-secondary" id="statInactiveProperties">
                            {{ $stats['inactive_properties'] ?? $properties->where('status', 'Inactive')->count() }}
                        </h4>
                        <small class="ta-text text-muted" style="font-size:0.72rem;">செயலற்றவை</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Card -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="propertySearchInput"
                            placeholder="Search Property Name...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="propertyStatusFilter">
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
                    <span class="small text-muted" id="propertyCountBadge">Showing <strong class="text-dark"
                            id="visiblePropertyCount">{{ $properties->count() }}</strong> records</span>
                </div>
            </div>
        </div>

        <!-- Properties Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="propertiesTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Property Name (சொத்தின் பெயர்)</th>
                            <th>Status (நிலை)</th>
                            <th class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="propertiesTableBody">
                        @forelse ($properties as $index => $property)
                            @php
                                $unitsCount = (int) ($property->property_types_count ?? ($property->propertyTypes ? $property->propertyTypes->count() : 0));
                                $inUse = $unitsCount > 0;
                            @endphp
                            <tr data-id="{{ $property->id }}" data-property="{{ $property->property_name }}"
                                data-tamil="{{ $property->property_name_ta ?? '' }}"
                                data-status="{{ $property->status }}"
                                data-units-count="{{ $unitsCount }}" data-in-use="{{ $inUse ? '1' : '0' }}">
                                <td class="text-muted fw-semibold serial-cell">{{ sprintf('%02d', $index + 1) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-wrapper bg-primary-subtle text-primary prop-icon-badge"
                                            style="width:36px; height:36px; font-size:1rem;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 row-prop-title">{{ $property->property_name }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-0.5 row-prop-sub">
                                                @if ($property->property_name_ta)
                                                    <small class="text-muted row-prop-tamil" style="font-size:0.75rem;">
                                                        <span class="badge bg-light text-secondary border border-secondary-subtle font-normal">{{ $property->property_name_ta }}</span>
                                                    </small>
                                                @endif
                                                @if ($inUse)
                                                    <span class="badge bg-light text-secondary border border-secondary-subtle" style="font-size: 0.68rem;" title="Used by {{ $unitsCount }} units in Property Type Master">
                                                        <i class="bi bi-link-45deg me-0.5"></i>{{ $unitsCount }} {{ $unitsCount === 1 ? 'branch' : 'branches' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($property->status === 'Active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-property-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            @if ($inUse)
                                                <li>
                                                    <button class="dropdown-item text-muted disabled" type="button" disabled
                                                        title="Cannot delete: property is currently used in Property Type Master"
                                                        style="cursor: not-allowed; opacity: 0.6;">
                                                        <i class="bi bi-lock-fill text-muted me-2"></i>Delete <span
                                                            class="badge bg-secondary-subtle text-secondary ms-1"
                                                            style="font-size: 0.65rem;">In Use</span>
                                                    </button>
                                                </li>
                                            @else
                                                <li><button class="dropdown-item text-danger delete-property-btn" type="button"><i
                                                            class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyPropertiesRow">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-building fs-2 d-block mb-2 text-secondary"></i>
                                    No property records found. Click <strong>Add Property</strong> to add a new record.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching property records found for your search or filter criteria.
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
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(5, $properties->count()) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $properties->count() }}</strong> properties
                    </span>
                </div>
                <nav aria-label="Property table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Property Add / Edit Modal -->
    <div class="modal fade" id="propertyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="propertyModalTitle">Add Property (புதிய சொத்து)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="propertyForm" novalidate autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="propertyEditId">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalPropertyName">Property Name (சொத்தின் பெயர்)
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modalPropertyName" name="property_name"
                                    placeholder="e.g. Medavakkam Commercial Complex" required>
                                <div class="invalid-feedback" id="errPropertyName"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalPropertyNameTa">Property Name in Tamil (சொத்தின் பெயர் தமிழில்) <span class="text-muted small fw-normal">(விருப்பத்தேர்வு)</span></label>
                                <input type="text" class="form-control" id="modalPropertyNameTa" name="property_name_ta"
                                    placeholder="e.g. மேடவாக்கம் வணிக வளாகம்">
                                <div class="invalid-feedback" id="errPropertyNameTa"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalStatus">Status (நிலை) <span class="text-danger">*</span></label>
                                <select class="form-select" id="modalStatus" name="status" required>
                                    <option value="Active">Active (செயலில் உள்ளது)</option>
                                    <option value="Inactive">Inactive (நிறுத்தப்பட்டது)</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="savePropertyBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="savePropertySpinner" role="status"
                                aria-hidden="true"></span>
                            <span id="savePropertyBtnText">Save Property</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePropertyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content text-center p-3">
                <div class="modal-body p-2">
                    <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger mx-auto mb-3"
                        style="width:54px; height:54px; border-radius:50%;">
                        <i class="bi bi-trash3 fs-3"></i>
                    </div>
                    <h6 class="fw-bold mb-1 text-dark">Delete Property?</h6>
                    <p class="text-muted small mb-3">Are you sure you want to delete <strong class="text-dark"
                            id="deletePropertyName"></strong>? This action cannot be reversed.</p>
                    <input type="hidden" id="deletePropertyId">
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm px-3" id="confirmDeletePropertyBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="deletePropertySpinner" role="status"
                                aria-hidden="true"></span>
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
            const getCsrfToken = () => $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val() || '{{ csrf_token() }}';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            const propertyModal = new bootstrap.Modal(document.getElementById('propertyModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deletePropertyModal'));

            function showToast(message, type = 'success') {
                if (typeof window.showToast === 'function') {
                    window.showToast(message, type);
                    return;
                }
                const toastContainer = document.querySelector('.toast-container') || createToastContainer();
                const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill';
                const bgClass = type === 'success' ? 'text-bg-success' : 'text-bg-danger';

                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center ${bgClass} border-0 shadow`;
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i class="bi bi-${icon}"></i>
                            <span>${message}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;

                toastContainer.appendChild(toastEl);
                const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            }

            function createToastContainer() {
                const container = document.createElement('div');
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '1100';
                document.body.appendChild(container);
                return container;
            }

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#propertySearchInput').val().toLowerCase().trim();
                const status = $('#propertyStatusFilter').val();
                const matching = [];

                $('#propertiesTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyPropertiesRow' || rowId === 'noResultsRow' || rowId === 'emptyFilterRow') return;

                    const row = $(this);
                    const propName = (row.data('property') || '').toString().toLowerCase();
                    const propTa = (row.data('tamil') || '').toString().toLowerCase();
                    const rowStatus = (row.data('status') || '').toString();

                    const matchesQuery = !query || propName.includes(query) || propTa.includes(query);
                    const matchesStatus = !status || rowStatus === status;

                    if (matchesQuery && matchesStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#propertiesTableBody tr').not('#emptyPropertiesRow, #noResultsRow, #emptyFilterRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#propertiesTableBody tr').not('#emptyPropertiesRow, #noResultsRow, #emptyFilterRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyPropertiesRow').length === 0) {
                        $('#propertiesTableBody').html(`
                            <tr id="emptyPropertiesRow">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-building fs-2 d-block mb-2 text-secondary"></i>
                                    No property records found. Click <strong>Add Property</strong> to add a new record.
                                </td>
                            </tr>
                        `);
                    }
                    $('#emptyPropertiesRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#propertyCountBadge').html('Showing <strong class="text-dark">0</strong> records');
                    return;
                } else {
                    $('#emptyPropertiesRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#propertyCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> records');
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
                summaryHtml += ' properties';
                $('#paginationSummary').html(summaryHtml);

                $('#propertyCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> records`);

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
            $('#propertySearchInput, #propertyStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#propertySearchInput').val('');
                $('#propertyStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            function resetForm() {
                $('#propertyForm')[0].reset();
                $('#propertyEditId').val('');
                $('#modalPropertyNameTa').val('');
                $('#propertyModalTitle').text('Add Property (புதிய சொத்து)');
                $('#savePropertyBtnText').text('Save Property');
                $('#propertyForm .is-invalid').removeClass('is-invalid');
                $('#propertyForm .invalid-feedback').text('').hide();
            }

            $('#addPropertyBtn').on('click', function () {
                resetForm();
            });

            $('#propertyForm input, #propertyForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').text('').hide();
            });

            // Handle Create & Update
            $('#propertyForm').on('submit', function (e) {
                e.preventDefault();

                const editId = $('#propertyEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("properties.update", ":id") }}'.replace(':id', editId) : '{{ route("properties.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const token = getCsrfToken();
                const formData = {
                    _token: token,
                    property_name: $('#modalPropertyName').val().trim(),
                    property_name_ta: $('#modalPropertyNameTa').val().trim() || null,
                    status: $('#modalStatus').val()
                };

                $('#savePropertySpinner').removeClass('d-none');
                $('#savePropertyBtn').prop('disabled', true);
                $('#propertyForm .is-invalid').removeClass('is-invalid');
                $('#propertyForm .invalid-feedback').text('').hide();

                $.ajax({
                    url: url,
                    type: method,
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    success: function (response) {
                        propertyModal.hide();
                        showToast(response.message || (isEdit ? 'Property updated successfully!' : 'Property added successfully!'));

                        const property = response.data;
                        const unitsCount = 0;
                        const inUse = false;
                        const tamilBadge = property.property_name_ta
                            ? `<small class="text-muted row-prop-tamil" style="font-size:0.75rem;"><span class="badge bg-light text-secondary border border-secondary-subtle font-normal">${escapeHtml(property.property_name_ta)}</span></small>`
                            : '';

                        const rowHtml = `
                            <tr data-id="${property.id}" data-property="${escapeHtml(property.property_name)}"
                                data-tamil="${escapeHtml(property.property_name_ta || '')}"
                                data-status="${property.status}" data-units-count="${unitsCount}" data-in-use="${inUse ? '1' : '0'}">
                                <td class="text-muted fw-semibold serial-cell">01</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-wrapper bg-primary-subtle text-primary prop-icon-badge"
                                            style="width:36px; height:36px; font-size:1rem;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 row-prop-title">${escapeHtml(property.property_name)}</div>
                                            <div class="d-flex align-items-center gap-1 mt-0.5 row-prop-sub">
                                                ${tamilBadge}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge ${property.status === 'Active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-muted border border-secondary-subtle'} row-status">${property.status}</span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-property-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li><hr class="dropdown-divider my-1"></li>
                                            <li><button class="dropdown-item text-danger delete-property-btn" type="button"><i
                                                        class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        `;

                        if (isEdit) {
                            const existingRow = $(`#propertiesTableBody tr[data-id="${property.id}"]`);
                            const currentUnits = existingRow.data('units-count') || 0;
                            const currentInUse = existingRow.data('in-use') === '1' || existingRow.data('in-use') === 1;

                            existingRow.attr('data-property', property.property_name);
                            existingRow.data('property', property.property_name);
                            existingRow.attr('data-tamil', property.property_name_ta || '');
                            existingRow.data('tamil', property.property_name_ta || '');
                            existingRow.attr('data-status', property.status);
                            existingRow.data('status', property.status);
                            existingRow.find('.row-prop-title').text(property.property_name);

                            let subtitleHtml = '';
                            if (property.property_name_ta) {
                                subtitleHtml += `<small class="text-muted row-prop-tamil" style="font-size:0.75rem;"><span class="badge bg-light text-secondary border border-secondary-subtle font-normal">${escapeHtml(property.property_name_ta)}</span></small> `;
                            }
                            if (currentInUse) {
                                subtitleHtml += `<span class="badge bg-light text-secondary border border-secondary-subtle" style="font-size: 0.68rem;" title="Used by ${currentUnits} units in Property Type Master"><i class="bi bi-link-45deg me-0.5"></i>${currentUnits} ${currentUnits === 1 ? 'branch' : 'branches'}</span>`;
                            }
                            existingRow.find('.row-prop-sub').html(subtitleHtml);

                            const statusBadge = property.status === 'Active'
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>'
                                : '<span class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>';
                            existingRow.find('td:nth-child(3)').html(statusBadge);
                        } else {
                            $('#emptyPropertiesRow').remove();
                            $('#propertiesTableBody').prepend(rowHtml);
                            currentPage = 1;
                        }

                        if (response.stats) {
                            $('#statTotalProperties').text(response.stats.total_properties);
                            $('#statActiveProperties').text(response.stats.active_properties);
                            $('#statInactiveProperties').text(response.stats.inactive_properties);
                        }

                        updatePagination();
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            if (errors.property_name) {
                                $('#modalPropertyName').addClass('is-invalid');
                                $('#errPropertyName').text(errors.property_name[0]).show();
                            }
                            if (errors.property_name_ta) {
                                $('#modalPropertyNameTa').addClass('is-invalid');
                                $('#errPropertyNameTa').text(errors.property_name_ta[0]).show();
                            }
                            if (errors.status) {
                                $('#modalStatus').addClass('is-invalid');
                                $('#errStatus').text(errors.status[0]).show();
                            }

                            const firstErrKey = Object.keys(errors)[0];
                            showToast(errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error occurred while saving property.';
                            showToast(errorMsg, 'danger');
                        }
                    },
                    complete: function () {
                        $('#savePropertySpinner').addClass('d-none');
                        $('#savePropertyBtn').prop('disabled', false);
                    }
                });
            });

            // Edit Click
            $(document).on('click', '.edit-property-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');

                $.ajax({
                    url: '{{ route("properties.show", ":id") }}'.replace(':id', id),
                    type: 'GET',
                    success: function (response) {
                        if (response.success && response.data) {
                            const p = response.data;
                            $('#propertyEditId').val(p.id);
                            $('#modalPropertyName').val(p.property_name);
                            $('#modalPropertyNameTa').val(p.property_name_ta || '');
                            $('#modalStatus').val(p.status);

                            $('#propertyModalTitle').text('Edit Property (சொத்து திருத்துதல்)');
                            $('#savePropertyBtnText').text('Update Property');
                            $('#propertyForm .is-invalid').removeClass('is-invalid');
                            $('#propertyForm .invalid-feedback').text('').hide();

                            propertyModal.show();
                        }
                    },
                    error: function () {
                        showToast('Failed to load property details.', 'danger');
                    }
                });
            });

            // Delete Click
            $(document).on('click', '.delete-property-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const name = tr.data('property');

                $('#deletePropertyId').val(id);
                $('#deletePropertyName').text(name);
                deleteModal.show();
            });

            // Confirm Delete
            $('#confirmDeletePropertyBtn').on('click', function () {
                const id = $('#deletePropertyId').val();
                if (!id) return;

                $('#deletePropertySpinner').removeClass('d-none');
                $('#confirmDeletePropertyBtn').prop('disabled', true);

                const token = getCsrfToken();
                $.ajax({
                    url: '{{ route("properties.destroy", ":id") }}'.replace(':id', id),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    data: JSON.stringify({ _token: token }),
                    contentType: 'application/json',
                    success: function (response) {
                        deleteModal.hide();
                        showToast(response.message || 'Property removed successfully.');

                        $(`#propertiesTableBody tr[data-id="${id}"]`).remove();

                        if (response.stats) {
                            $('#statTotalProperties').text(response.stats.total_properties);
                            $('#statActiveProperties').text(response.stats.active_properties);
                            $('#statInactiveProperties').text(response.stats.inactive_properties);
                        }

                        updatePagination();
                    },
                    error: function (xhr) {
                        deleteModal.hide();
                        showToast(xhr.responseJSON?.message || 'Failed to delete property record.', 'danger');
                    },
                    complete: function () {
                        $('#deletePropertySpinner').addClass('d-none');
                        $('#confirmDeletePropertyBtn').prop('disabled', false);
                    }
                });
            });

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }

            // Initial pagination on page load
            updatePagination();
        });
    </script>
@endpush
