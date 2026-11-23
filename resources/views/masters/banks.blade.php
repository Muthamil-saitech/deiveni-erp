@extends('layouts.app')

@section('title', 'Bank Master')
@section('page_title', 'Bank Master (வங்கி முதன்மை பட்டியல்)')

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
                    <h5 class="fw-bold mb-0 text-dark">Empanelled Banks Directory (வங்கி விவரங்கள்)</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ url('/branches') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-diagram-3 me-1"></i> Branch Master
                    </a>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#bankModal"
                        id="addBankBtn">
                        <i class="bi bi-bank me-1"></i> Add Bank
                    </button>
                </div>
            </div>
        </div>

        <!-- Stat Highlights Row -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-bank fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Banks</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalBanks">
                            {{ $stats['total_banks'] ?? $banks->count() }}
                        </h4>
                        <small class="ta-text text-muted" style="font-size:0.72rem;">மொத்த வங்கிகள்</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Active Banks</div>
                        <h4 class="fw-bold mb-0 text-success" id="statActiveBanks">
                            {{ $stats['active_banks'] ?? $banks->where('status', 'Active')->count() }}
                        </h4>
                        <small class="ta-text text-muted" style="font-size:0.72rem;">செயலில் உள்ள வங்கிகள்</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-x-circle fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Inactive Banks</div>
                        <h4 class="fw-bold mb-0 text-secondary" id="statInactiveBanks">
                            {{ $stats['inactive_banks'] ?? $banks->where('status', 'Inactive')->count() }}
                        </h4>
                        <small class="ta-text text-muted" style="font-size:0.72rem;">செயலற்ற வங்கிகள்</small>
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
                        <input type="text" class="form-control border-start-0 ps-0" id="bankSearchInput"
                            placeholder="Search Bank Name (English / Tamil)...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="bankStatusFilter">
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
                    <span class="small text-muted" id="bankCountBadge">Showing <strong class="text-dark"
                            id="visibleBankCount">{{ $banks->count() }}</strong> records</span>
                </div>
            </div>
        </div>

        <!-- Banks Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="banksTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Bank Name (வங்கி பெயர்)</th>
                            <th>Status (நிலை)</th>
                            <th class="text-end" style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="banksTableBody">
                        @forelse ($banks as $index => $bank)
                            @php
                                $bankLower = strtolower($bank->bank_name);
                                $iconWrapperClass = 'bg-primary-subtle text-primary';

                                if (str_contains($bankLower, 'hdfc')) {
                                    $iconWrapperClass = 'bg-danger-subtle text-danger';
                                } elseif (str_contains($bankLower, 'canara')) {
                                    $iconWrapperClass = 'bg-info-subtle text-info';
                                } elseif (str_contains($bankLower, 'icici')) {
                                    $iconWrapperClass = 'bg-warning-subtle text-dark';
                                } elseif (str_contains($bankLower, 'axis') || $bank->status === 'Inactive') {
                                    $iconWrapperClass = 'bg-secondary-subtle text-secondary';
                                }

                                $branchesCount = (int) ($bank->branches_count ?? ($bank->branches ? $bank->branches->count() : 0));
                                $inUse = $branchesCount > 0;
                            @endphp
                            <tr data-id="{{ $bank->id }}" data-bank="{{ $bank->bank_name }}"
                                data-tamil="{{ $bank->bank_name_ta ?? '' }}" data-status="{{ $bank->status }}"
                                data-branches-count="{{ $branchesCount }}" data-in-use="{{ $inUse ? '1' : '0' }}">
                                <td class="text-muted fw-semibold serial-cell">{{ sprintf('%02d', $index + 1) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-wrapper {{ $iconWrapperClass }} bank-icon-badge"
                                            style="width:36px; height:36px; font-size:1rem;">
                                            <i class="bi bi-bank"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 row-bank-title">{{ $bank->bank_name }}</div>
                                            <div class="d-flex align-items-center gap-1 mt-0.5">
                                                @if ($bank->bank_name_ta)
                                                    <span class="ta-badge row-bank-subtitle">{{ $bank->bank_name_ta }}</span>
                                                @else
                                                    <span class="ta-badge row-bank-subtitle d-none"></span>
                                                @endif
                                                @if ($inUse)
                                                    <span class="badge bg-light text-secondary border border-secondary-subtle"
                                                        style="font-size: 0.68rem;"
                                                        title="Used by {{ $branchesCount }} branch(es) in Branch Master">
                                                        <i class="bi bi-link-45deg me-0.5"></i>{{ $branchesCount }}
                                                        {{ $branchesCount === 1 ? 'branch' : 'branches' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if ($bank->status === 'Active')
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>
                                    @else
                                        <span
                                            class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-bank-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            @if ($inUse)
                                                <li>
                                                    <button class="dropdown-item text-muted disabled" type="button" disabled
                                                        title="Cannot delete: bank is currently used in Branch Master"
                                                        style="cursor: not-allowed; opacity: 0.6;">
                                                        <i class="bi bi-lock-fill text-muted me-2"></i>Delete <span
                                                            class="badge bg-secondary-subtle text-secondary ms-1"
                                                            style="font-size: 0.65rem;">In Use</span>
                                                    </button>
                                                </li>
                                            @else
                                                <li><button class="dropdown-item text-danger delete-bank-btn" type="button"><i
                                                            class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyBanksRow">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-bank fs-2 d-block mb-2 text-secondary"></i>
                                    No bank records found. Click <strong>Add Bank</strong> to add a new record.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching bank records found for your search or filter criteria.
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
                            id="pageEnd">{{ min(5, $banks->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $banks->count() }}</strong> banks
                    </span>
                </div>
                <nav aria-label="Bank table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Bank Add / Edit Modal -->
    <div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="bankModalTitle">Add Bank (புதிய வங்கி தகவல்)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bankForm" novalidate autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" id="bankEditId">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalBankName">Bank Name (வங்கி பெயர்)
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modalBankName" name="bank_name"
                                    placeholder="e.g. State Bank of India (SBI)" required>
                                <div class="invalid-feedback" id="errBankName"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalBankNameTa">Bank Name in Tamil (தமிழ்
                                    பெயர்) <span class="text-muted small fw-normal">(விருப்பத்தேர்வு)</span></label>
                                <input type="text" class="form-control" id="modalBankNameTa" name="bank_name_ta"
                                    placeholder="e.g. பாரத ஸ்டேட் வங்கி">
                                <div class="invalid-feedback" id="errBankNameTa"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalStatus">Status (நிலை) <span
                                        class="text-danger">*</span></label>
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
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveBankBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveBankSpinner" role="status"
                                aria-hidden="true"></span>
                            <span id="saveBankBtnText">Save Bank Details</span>
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const bankModal = new bootstrap.Modal(document.getElementById('bankModal'));

            function applyStats(stats) {
                if (!stats) return;
                if (stats.total_banks !== undefined) $('#statTotalBanks').text(stats.total_banks);
                if (stats.active_banks !== undefined) $('#statActiveBanks').text(stats.active_banks);
                if (stats.inactive_banks !== undefined) $('#statInactiveBanks').text(stats.inactive_banks);
            }

            function recalculateStatsLocal() {
                let total = 0;
                let active = 0;
                let inactive = 0;

                $('#banksTableBody tr[data-bank]').each(function () {
                    total++;
                    const status = $(this).data('status');
                    if (status === 'Active') {
                        active++;
                    } else {
                        inactive++;
                    }
                });

                $('#statTotalBanks').text(total);
                $('#statActiveBanks').text(active);
                $('#statInactiveBanks').text(inactive);
            }

            function renumberTableRows() {
                $('#banksTableBody tr[data-bank]').each(function (index) {
                    $(this).find('.serial-cell').text(String(index + 1).padStart(2, '0'));
                });
            }

            function getBankTheme(bankName, status) {
                const lower = (bankName || '').toLowerCase();
                if (status === 'Inactive' || lower.includes('axis')) {
                    return {
                        iconWrapper: 'bg-secondary-subtle text-secondary',
                    };
                }
                if (lower.includes('hdfc')) {
                    return {
                        iconWrapper: 'bg-danger-subtle text-danger',
                    };
                }
                if (lower.includes('canara')) {
                    return {
                        iconWrapper: 'bg-info-subtle text-info',
                    };
                }
                if (lower.includes('icici')) {
                    return {
                        iconWrapper: 'bg-warning-subtle text-dark',
                    };
                }
                return {
                    iconWrapper: 'bg-primary-subtle text-primary',
                };
            }

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#bankSearchInput').val().toLowerCase().trim();
                const selectedStatus = $('#bankStatusFilter').val();
                const matching = [];

                $('#banksTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyBanksRow' || rowId === 'noResultsRow') return;

                    const bank = String($(this).data('bank') || '').toLowerCase();
                    const tamil = String($(this).data('tamil') || '').toLowerCase();
                    const status = String($(this).data('status') || '');

                    const haystack = `${bank} ${tamil}`;

                    const matchQuery = !query || haystack.includes(query);
                    const matchStatus = !selectedStatus || status === selectedStatus;

                    if (matchQuery && matchStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#banksTableBody tr').not('#emptyBanksRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#banksTableBody tr').not('#emptyBanksRow, #noResultsRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyBanksRow').length === 0) {
                        $('#banksTableBody').html(`
                                <tr id="emptyBanksRow">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-bank fs-2 d-block mb-2 text-secondary"></i>
                                        No bank records found. Click <strong>Add Bank</strong> to add a new record.
                                    </td>
                                </tr>
                            `);
                    }
                    $('#emptyBanksRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#bankCountBadge').html('Showing <strong class="text-dark">0</strong> records');
                    return;
                } else {
                    $('#emptyBanksRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#bankCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> records');
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
                summaryHtml += ' records';
                $('#paginationSummary').html(summaryHtml);

                $('#bankCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> records`);

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
            $('#bankSearchInput, #bankStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#bankSearchInput').val('');
                $('#bankStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            function clearFormErrors() {
                $('#bankForm .is-invalid').removeClass('is-invalid');
                $('#bankForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.bank_name) {
                    $('#modalBankName').addClass('is-invalid');
                    $('#errBankName').text(errors.bank_name[0]).show();
                }
                if (errors.bank_name_ta) {
                    $('#modalBankNameTa').addClass('is-invalid');
                    $('#errBankNameTa').text(errors.bank_name_ta[0]).show();
                }
                if (errors.status) {
                    $('#modalStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
            }

            // Open Modal for Add
            $('#addBankBtn').on('click', function () {
                $('#bankModalTitle').text('Add Bank (புதிய வங்கி தகவல்)');
                $('#saveBankBtnText').text('Save Bank Details');
                $('#bankEditId').val('');
                $('#bankForm')[0].reset();
                $('#modalStatus').val('Active');
                clearFormErrors();
            });

            // Open Modal for Edit
            $(document).on('click', '.edit-bank-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const bank = tr.data('bank');
                const bankTa = tr.data('tamil');
                const status = tr.data('status');

                $('#bankModalTitle').text('Edit Bank (வங்கி விவரங்களை திருத்து)');
                $('#saveBankBtnText').text('Update Bank Details');
                $('#bankEditId').val(id);
                $('#modalBankName').val(bank);
                $('#modalBankNameTa').val(bankTa || '');
                $('#modalStatus').val(status || 'Active');

                clearFormErrors();
                bankModal.show();
            });

            $('#bankForm input, #bankForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
            });

            // Submit Form
            $('#bankForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#bankEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("banks.update", ":id") }}'.replace(':id', editId) : '{{ route("banks.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    bank_name: $('#modalBankName').val().trim(),
                    bank_name_ta: $('#modalBankNameTa').val().trim() || null,
                    status: $('#modalStatus').val()
                };

                const $btn = $('#saveBankBtn');
                const $spinner = $('#saveBankSpinner');
                const $btnText = $('#saveBankBtnText');

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
                        $btnText.text(isEdit ? 'Update Bank Details' : 'Save Bank Details');

                        if (res.success && res.data) {
                            const bank = res.data;
                            const theme = getBankTheme(bank.bank_name, bank.status);
                            const statusBadge = bank.status === 'Active'
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>'
                                : '<span class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>';

                            const tamilBadge = bank.bank_name_ta
                                ? `<span class="ta-badge">${bank.bank_name_ta}</span>`
                                : '';

                            if (isEdit) {
                                const tr = $(`#banksTableBody tr[data-id="${bank.id}"]`);
                                tr.data('bank', bank.bank_name)
                                    .data('tamil', bank.bank_name_ta || '')
                                    .data('status', bank.status);

                                tr.find('.row-bank-title').text(bank.bank_name);
                                const $sub = tr.find('.row-bank-subtitle');
                                if (bank.bank_name_ta) {
                                    $sub.html(tamilBadge).removeClass('d-none');
                                } else {
                                    $sub.empty().addClass('d-none');
                                }
                                tr.find('.bank-icon-badge').attr('class', `stat-icon-wrapper ${theme.iconWrapper} bank-icon-badge`);
                                tr.find('.row-status').replaceWith(statusBadge);

                                showToast(res.message || `Bank "${bank.bank_name}" updated successfully!`, 'success');
                            } else {
                                $('#emptyBanksRow').remove();

                                const newRowHtml = `
                                        <tr data-id="${bank.id}"
                                            data-bank="${bank.bank_name}"
                                            data-tamil="${bank.bank_name_ta || ''}"
                                            data-status="${bank.status}"
                                            data-branches-count="0"
                                            data-in-use="0">
                                            <td class="text-muted fw-semibold serial-cell">01</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="stat-icon-wrapper ${theme.iconWrapper} bank-icon-badge" style="width:36px; height:36px; font-size:1rem;">
                                                        <i class="bi bi-bank"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark fs-6 row-bank-title">${bank.bank_name}</div>
                                                        <div class="d-flex align-items-center gap-1 mt-0.5">
                                                            <small class="text-muted row-bank-subtitle ${bank.bank_name_ta ? '' : 'd-none'}">${tamilBadge}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>${statusBadge}</td>
                                            <td class="text-end">
                                                <div class="dropdown table-action-dropdown">
                                                    <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li><button class="dropdown-item edit-bank-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li><button class="dropdown-item text-danger delete-bank-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    `;

                                $('#banksTableBody').prepend(newRowHtml);
                                showToast(res.message || `Bank "${bank.bank_name}" added successfully!`, 'success');
                            }

                            bankModal.hide();
                            $('#bankForm')[0].reset();
                            if (!isEdit) {
                                currentPage = 1;
                            }
                            if (res.stats) {
                                applyStats(res.stats);
                            } else {
                                recalculateStatsLocal();
                            }
                            updatePagination();
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Bank Details' : 'Save Bank Details');

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

            // Delete Bank
            $(document).on('click', '.delete-bank-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const bankName = tr.data('bank') || 'this bank';
                const branchesCount = parseInt(tr.data('branches-count') || 0, 10);
                const inUse = tr.data('in-use') == '1' || branchesCount > 0;

                if (inUse) {
                    showToast(`Bank "${bankName}" is currently used in Branch Master and cannot be deleted.`, 'danger');
                    return;
                }

                confirmDelete(`Are you sure you want to remove bank "${bankName}"?`, function () {
                    $.ajax({
                        url: '{{ route("banks.destroy", ":id") }}'.replace(':id', id),
                        method: 'POST',
                        success: function (res) {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                if (res.stats) {
                                    applyStats(res.stats);
                                } else {
                                    recalculateStatsLocal();
                                }
                                updatePagination();
                            });

                            showToast(res.message || `Bank removed successfully.`, 'success');
                        },
                        error: function (xhr) {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error deleting bank.';
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