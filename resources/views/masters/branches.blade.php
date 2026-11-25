@extends('layouts.app')

@section('title', 'Branch Master')
@section('page_title', 'Branch Master ')

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
                    <h5 class="fw-bold mb-0 text-dark">Empanelled Bank Branches & Valuation Directory</h5>
</div>
                <div class="d-flex gap-2">
                    <a href="{{ url('/banks') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-bank me-1"></i> Bank Master
</a>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#branchModal"
                        id="addBranchBtn">
                        <i class="bi bi-diagram-3 me-1"></i> Add Branch
</button>
</div>
</div>
</div>

        <!-- Stat Highlights Row -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-diagram-3 fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Total Branches</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalBranches">
                            {{ $stats['total_branches'] ?? $branches->count() }}
</h4>
</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Active Branches</div>
                        <h4 class="fw-bold mb-0 text-success" id="statActiveBranches">
                            {{ $stats['active_branches'] ?? $branches->where('status', 'Active')->count() }}
</h4>
</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-secondary bg-opacity-10 text-secondary">
                        <i class="bi bi-x-circle fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Inactive Branches</div>
                        <h4 class="fw-bold mb-0 text-secondary" id="statInactiveBranches">
                            {{ $stats['inactive_branches'] ?? $branches->where('status', 'Inactive')->count() }}
</h4>
</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-cash-coin fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Avg Valuation Fee</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statAvgFee">₹{{ number_format($stats['avg_fee'] ?? 0) }}</h4>
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
                        <input type="text" class="form-control border-start-0 ps-0" id="branchSearchInput"
                            placeholder="Search Bank Name, Branch, IFSC, Manager...">
</div>
</div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="branchBankFilter">
                        <option value="">All Banks</option>
                        @php
                            $allBanks = $allBanks ?? \App\Models\Bank::orderBy('bank_name')->get();
                        @endphp
                        @foreach ($allBanks as $bank)
                            @php
                                $bName = is_string($bank) ? $bank : $bank->bank_name;
                            @endphp
                            <option value="{{ $bName }}">{{ $bName }}</option>
                        @endforeach
                    </select>
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="branchStatusFilter">
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
                <div class="col-6 col-md-2 text-md-end">
                    <span class="small text-muted" id="branchCountBadge">Showing <strong class="text-dark"
                            id="visibleBranchCount">{{ $branches->count() }}</strong> records</span>
</div>
</div>
</div>

        <!-- Branches Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="branchesTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Bank & Branch Details</th>
                            <th>IFSC Code</th>
                            <th>Branch Contact & Phone</th>
                            <th>Std Valuation Fee</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="branchesTableBody">
                        @forelse ($branches as $index => $branch)
                            @php
                                $bankLower = strtolower($branch->bank_name);
                                $iconWrapperClass = 'bg-primary-subtle text-primary';
                                $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';

                                if (str_contains($bankLower, 'hdfc')) {
                                    $iconWrapperClass = 'bg-danger-subtle text-danger';
                                    $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                } elseif (str_contains($bankLower, 'canara')) {
                                    $iconWrapperClass = 'bg-info-subtle text-info';
                                    $badgeClass = 'bg-info-subtle text-info border border-info-subtle';
                                } elseif (str_contains($bankLower, 'icici')) {
                                    $iconWrapperClass = 'bg-warning-subtle text-dark';
                                    $badgeClass = 'bg-warning-subtle text-dark border border-warning-subtle';
                                } elseif (str_contains($bankLower, 'axis') || $branch->status === 'Inactive') {
                                    $iconWrapperClass = 'bg-secondary-subtle text-secondary';
                                    $badgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                                }

                                $feeClass = $branch->status === 'Active' ? 'text-success' : 'text-secondary';
                            @endphp
                            <tr data-id="{{ $branch->id }}" data-bank-id="{{ $branch->bank_id }}" data-bank="{{ $branch->bank_name }}"
                                data-branch="{{ $branch->branch_name }}" data-tamil="{{ $branch->branch_name_ta ?? '' }}"
                                data-ifsc="{{ $branch->ifsc_code }}" data-contact="{{ $branch->contact_person ?? '' }}"
                                data-phone="{{ $branch->phone ?? '' }}" data-fee="{{ (int) $branch->valuation_fee }}"
                                data-status="{{ $branch->status }}">
                                <td class="text-muted fw-semibold serial-cell">{{ sprintf('%02d', $index + 1) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-wrapper {{ $iconWrapperClass }} bank-icon-badge"
                                            style="width:36px; height:36px; font-size:1rem;">
                                            <i class="bi bi-bank"></i>
</div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 row-bank-title">{{ $branch->bank_name }}</div>
                                            <small class="text-muted row-branch-subtitle">
                                                <i class="bi bi-geo-alt me-1"></i>{{ $branch->branch_name }} Branch
                                                @if ($branch->branch_name_ta)
                                                    &bull; <span class="ta-badge">{{ $branch->branch_name_ta }}</span>
                                                @endif
</small>
</div>
</div>
                                </td>
                                <td>
                                    <div><span class="font-monospace badge {{ $badgeClass }} row-ifsc">{{ $branch->ifsc_code }}</span></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark row-contact">{{ $branch->contact_person ?: '—' }}</div>
                                    <small class="text-muted row-phone">
                                        <i class="bi bi-telephone text-muted me-1"></i>{{ $branch->phone ?: '—' }}
</small>
                                </td>
                                <td>
                                    <div class="fw-bold {{ $feeClass }} fs-6 row-fee">
                                        {{ $branch->valuation_fee > 0 ? '₹' . number_format($branch->valuation_fee) : '—' }}
</div>
                                </td>
                                <td>
                                    @if ($branch->status === 'Active')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-branch-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            <li><button class="dropdown-item text-danger delete-branch-btn" type="button"><i
                                                        class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyBranchesRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-diagram-3 fs-2 d-block mb-2 text-secondary"></i>
                                    No branch records found. Click <strong>Add Branch</strong> to add a new record.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching branch records found for your search or filter criteria.
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
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(5, $branches->count()) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $branches->count() }}</strong> branches
</span>
</div>
                <nav aria-label="Branch table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- Branch Add / Edit Modal -->
    <div class="modal fade" id="branchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="branchModalTitle">Add Branch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="branchForm" novalidate autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" id="branchEditId">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalBankId">Bank Name 
                                    <span class="text-danger">*</span></label>
                                <select class="form-select" id="modalBankId" name="bank_id" required>
                                    <option value="">Select Bank</option>
                                    @foreach ($bankList as $bankItem)
                                        <option value="{{ $bankItem->id }}">{{ $bankItem->bank_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errBankId"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalBranchName">Branch Name 
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modalBranchName" name="branch_name"
                                    placeholder="e.g. Valasaravakkam / Guindy / Porur" required>
                                <div class="invalid-feedback" id="errBranchName"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalBranchNameTa">Branch Name in
                                    Tamil  <span class="text-muted small fw-normal"></span></label>
                                <input type="text" class="form-control" id="modalBranchNameTa" name="branch_name_ta"
                                    placeholder="e.g. வளசரவாக்கம் கிளை">
                                <div class="invalid-feedback" id="errBranchNameTa"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalIfsc">IFSC Code <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace text-uppercase" id="modalIfsc"
                                    name="ifsc_code" placeholder="e.g. SBIN0001234" maxlength="11" required>
                                <div class="invalid-feedback" id="errIfsc"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalContact">Branch Manager / Loan
                                    Officer</label>
                                <input type="text" class="form-control" id="modalContact" name="contact_person"
                                    placeholder="e.g. Mr. S. Ramanathan">
                                <div class="invalid-feedback" id="errContact"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalPhone">Contact Phone Number</label>
                                <input type="text" class="form-control" id="modalPhone" name="phone"
                                    placeholder="e.g. +91 98401 23456">
                                <div class="invalid-feedback" id="errPhone"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalFee">Standard Valuation Fee (₹)
                                    <span class="text-muted small fw-normal"></span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="modalFee" name="valuation_fee"
                                        placeholder="7500" min="0" step="100">
                                    <div class="invalid-feedback" id="errFee"></div>
</div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalStatus">Status</label>
                                <select class="form-select" id="modalStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveBranchBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveBranchSpinner" role="status"
                                aria-hidden="true"></span>
                            <span id="saveBranchBtnText">Save Branch Details</span>
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

            const branchModal = new bootstrap.Modal(document.getElementById('branchModal'));

            function applyStats(stats) {
                if (!stats) return;
                if (stats.total_branches !== undefined) $('#statTotalBranches').text(stats.total_branches);
                if (stats.active_branches !== undefined) $('#statActiveBranches').text(stats.active_branches);
                if (stats.inactive_branches !== undefined) $('#statInactiveBranches').text(stats.inactive_branches);
                if (stats.avg_fee !== undefined) $('#statAvgFee').text('₹' + Number(stats.avg_fee).toLocaleString('en-IN'));
            }

            function recalculateStatsLocal() {
                let total = 0;
                let active = 0;
                let inactive = 0;
                let sumFee = 0;

                $('#branchesTableBody tr[data-bank]').each(function () {
                    total++;
                    const status = $(this).data('status');
                    const fee = parseFloat($(this).data('fee')) || 0;

                    if (status === 'Active') {
                        active++;
                        sumFee += fee;
                    } else if (status === 'Inactive') {
                        inactive++;
                    }
                });

                $('#statTotalBranches').text(total);
                $('#statActiveBranches').text(active);
                $('#statInactiveBranches').text(inactive);
                const avg = active > 0 ? Math.round(sumFee / active) : 0;
                $('#statAvgFee').text('₹' + avg.toLocaleString('en-IN'));
            }

            function renumberTableRows() {
                $('#branchesTableBody tr[data-bank]').each(function (index) {
                    $(this).find('.serial-cell').text(String(index + 1).padStart(2, '0'));
                });
            }

            const allBankFilterOptions = [];
            $('#branchBankFilter option').each(function () {
                const val = $(this).val();
                if (val) allBankFilterOptions.push(val);
            });

            function refreshBankFilterOptions() {
                const currentSelected = $('#branchBankFilter').val();
                const bankNames = new Set(allBankFilterOptions);
                $('#branchesTableBody tr[data-bank]').each(function () {
                    const b = $(this).data('bank');
                    if (b) bankNames.add(b);
                });

                const $filter = $('#branchBankFilter');
                $filter.empty().append('<option value="">All Banks</option>');
                Array.from(bankNames).sort().forEach(function (bName) {
                    const isSelected = bName === currentSelected ? 'selected' : '';
                    $filter.append(`<option value="${bName}" ${isSelected}>${bName}</option>`);
                });
            }

            function getBankTheme(bankName, status) {
                const lower = (bankName || '').toLowerCase();
                if (status === 'Inactive' || lower.includes('axis')) {
                    return {
                        iconWrapper: 'bg-secondary-subtle text-secondary',
                        badge: 'bg-secondary-subtle text-secondary border border-secondary-subtle'
                    };
                }
                if (lower.includes('hdfc')) {
                    return {
                        iconWrapper: 'bg-danger-subtle text-danger',
                        badge: 'bg-danger-subtle text-danger border border-danger-subtle'
                    };
                }
                if (lower.includes('canara')) {
                    return {
                        iconWrapper: 'bg-info-subtle text-info',
                        badge: 'bg-info-subtle text-info border border-info-subtle'
                    };
                }
                if (lower.includes('icici')) {
                    return {
                        iconWrapper: 'bg-warning-subtle text-dark',
                        badge: 'bg-warning-subtle text-dark border border-warning-subtle'
                    };
                }
                return {
                    iconWrapper: 'bg-primary-subtle text-primary',
                    badge: 'bg-primary-subtle text-primary border border-primary-subtle'
                };
            }

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#branchSearchInput').val().toLowerCase().trim();
                const selectedBank = $('#branchBankFilter').val();
                const selectedStatus = $('#branchStatusFilter').val();
                const matching = [];

                $('#branchesTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyBranchesRow' || rowId === 'noResultsRow') return;

                    const bank = String($(this).data('bank') || '');
                    const branch = String($(this).data('branch') || '');
                    const ifsc = String($(this).data('ifsc') || '');
                    const contact = String($(this).data('contact') || '');
                    const phone = String($(this).data('phone') || '');
                    const status = String($(this).data('status') || '');

                    const haystack = `${bank} ${branch} ${ifsc} ${contact} ${phone}`.toLowerCase();

                    const matchQuery = !query || haystack.includes(query);
                    const matchBank = !selectedBank || bank === selectedBank;
                    const matchStatus = !selectedStatus || status === selectedStatus;

                    if (matchQuery && matchBank && matchStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#branchesTableBody tr').not('#emptyBranchesRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#branchesTableBody tr').not('#emptyBranchesRow, #noResultsRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyBranchesRow').length === 0) {
                        $('#branchesTableBody').html(`
                            <tr id="emptyBranchesRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-diagram-3 fs-2 d-block mb-2 text-secondary"></i>
                                    No branch records found. Click <strong>Add Branch</strong> to add a new record.
                                </td>
                            </tr>
                        `);
                    }
                    $('#emptyBranchesRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#branchCountBadge').html('Showing <strong class="text-dark">0</strong> records');
                    return;
                } else {
                    $('#emptyBranchesRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#branchCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> records');
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
                summaryHtml += ' branches';
                $('#paginationSummary').html(summaryHtml);

                $('#branchCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> records`);

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

            // Filter on search, bank or status change
            $('#branchSearchInput, #branchBankFilter, #branchStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#branchSearchInput').val('');
                $('#branchBankFilter').val('');
                $('#branchStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            function clearFormErrors() {
                $('#branchForm .is-invalid').removeClass('is-invalid');
                $('#branchForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.bank_id) {
                    $('#modalBankId').addClass('is-invalid');
                    $('#errBankId').text(errors.bank_id[0]).show();
                }
                if (errors.branch_name) {
                    $('#modalBranchName').addClass('is-invalid');
                    $('#errBranchName').text(errors.branch_name[0]).show();
                }
                if (errors.branch_name_ta) {
                    $('#modalBranchNameTa').addClass('is-invalid');
                    $('#errBranchNameTa').text(errors.branch_name_ta[0]).show();
                }
                if (errors.ifsc_code) {
                    $('#modalIfsc').addClass('is-invalid');
                    $('#errIfsc').text(errors.ifsc_code[0]).show();
                }
                if (errors.contact_person) {
                    $('#modalContact').addClass('is-invalid');
                    $('#errContact').text(errors.contact_person[0]).show();
                }
                if (errors.phone) {
                    $('#modalPhone').addClass('is-invalid');
                    $('#errPhone').text(errors.phone[0]).show();
                }
                if (errors.valuation_fee) {
                    $('#modalFee').addClass('is-invalid');
                    $('#errFee').text(errors.valuation_fee[0]).show();
                }
                if (errors.status) {
                    $('#modalStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
            }

            // Open Modal for Add
            $('#addBranchBtn').on('click', function () {
                $('#branchModalTitle').text('Add Branch ');
                $('#saveBranchBtnText').text('Save Branch Details');
                $('#branchEditId').val('');
                $('#branchForm')[0].reset();
                $('#modalStatus').val('Active');
                clearFormErrors();
            });

            // Open Modal for Edit
            $(document).on('click', '.edit-branch-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const bankId = tr.data('bank-id');
                const bank = tr.data('bank');
                const branch = tr.data('branch');
                const branchTa = tr.data('tamil');
                const ifsc = tr.data('ifsc');
                const contact = tr.data('contact');
                const phone = tr.data('phone');
                const fee = tr.data('fee');
                const status = tr.data('status');

                $('#branchModalTitle').text('Edit Branch ');
                $('#saveBranchBtnText').text('Update Branch Details');
                $('#branchEditId').val(id);
                $('#modalBankId').val(bankId);
                $('#modalBranchName').val(branch);
                $('#modalBranchNameTa').val(branchTa || '');
                $('#modalIfsc').val(ifsc);
                $('#modalContact').val(contact || '');
                $('#modalPhone').val(phone || '');
                $('#modalFee').val(fee > 0 ? fee : '');
                $('#modalStatus').val(status || 'Active');

                clearFormErrors();
                branchModal.show();
            });

            $('#branchForm input, #branchForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
            });

            // Submit Form
            $('#branchForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#branchEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("branches.update", ":id") }}'.replace(':id', editId) : '{{ route("branches.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    bank_id: $('#modalBankId').val(),
                    branch_name: $('#modalBranchName').val().trim(),
                    branch_name_ta: $('#modalBranchNameTa').val().trim() || null,
                    ifsc_code: $('#modalIfsc').val().trim().toUpperCase(),
                    contact_person: $('#modalContact').val().trim() || null,
                    phone: $('#modalPhone').val().trim() || null,
                    valuation_fee: $('#modalFee').val() || null,
                    status: $('#modalStatus').val()
                };

                const $btn = $('#saveBranchBtn');
                const $spinner = $('#saveBranchSpinner');
                const $btnText = $('#saveBranchBtnText');

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
                        $btnText.text(isEdit ? 'Update Branch Details' : 'Save Branch Details');

                        if (res.success && res.data) {
                            const branch = res.data;
                            const feeVal = Number(branch.valuation_fee) || 0;
                            const formattedFee = feeVal > 0 ? '₹' + feeVal.toLocaleString('en-IN') : '—';
                            const theme = getBankTheme(branch.bank_name, branch.status);
                            const feeClass = branch.status === 'Active' ? 'text-success' : 'text-secondary';
                            const statusBadge = branch.status === 'Active'
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>'
                                : '<span class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>';

                            const branchSubtitle = branch.branch_name_ta
                                ? `<i class="bi bi-geo-alt me-1"></i>${branch.branch_name} Branch &bull; <span class="ta-badge">${branch.branch_name_ta}</span>`
                                : `<i class="bi bi-geo-alt me-1"></i>${branch.branch_name} Branch`;

                            if (isEdit) {
                                const tr = $(`#branchesTableBody tr[data-id="${branch.id}"]`);
                                tr.data('bank-id', branch.bank_id)
                                    .data('bank', branch.bank_name)
                                    .data('branch', branch.branch_name)
                                    .data('tamil', branch.branch_name_ta || '')
                                    .data('ifsc', branch.ifsc_code)
                                    .data('contact', branch.contact_person || '')
                                    .data('phone', branch.phone || '')
                                    .data('fee', branch.valuation_fee)
                                    .data('status', branch.status);

                                tr.find('.row-bank-title').text(branch.bank_name);
                                tr.find('.row-branch-subtitle').html(branchSubtitle);
                                tr.find('.bank-icon-badge').attr('class', `stat-icon-wrapper ${theme.iconWrapper} bank-icon-badge`);
                                tr.find('.row-ifsc').attr('class', `font-monospace badge ${theme.badge} row-ifsc`).text(branch.ifsc_code);
                                tr.find('.row-contact').text(branch.contact_person || '—');
                                tr.find('.row-phone').html(`<i class="bi bi-telephone text-muted me-1"></i>${branch.phone || '—'}`);
                                tr.find('.row-fee').removeClass('text-success text-secondary').addClass(feeClass).text(formattedFee);
                                tr.find('.row-status').replaceWith(statusBadge);

                                showToast(res.message || `Branch "${branch.bank_name} - ${branch.branch_name}" updated successfully!`, 'success');
                            } else {
                                $('#emptyBranchesRow').remove();

                                const newRowHtml = `
                                    <tr data-id="${branch.id}"
                                        data-bank-id="${branch.bank_id}"
                                        data-bank="${branch.bank_name}"
                                        data-branch="${branch.branch_name}"
                                        data-tamil="${branch.branch_name_ta || ''}"
                                        data-ifsc="${branch.ifsc_code}"
                                        data-contact="${branch.contact_person || ''}"
                                        data-phone="${branch.phone || ''}"
                                        data-fee="${branch.valuation_fee}"
                                        data-status="${branch.status}">
                                        <td class="text-muted fw-semibold serial-cell">01</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="stat-icon-wrapper ${theme.iconWrapper} bank-icon-badge" style="width:36px; height:36px; font-size:1rem;">
                                                    <i class="bi bi-bank"></i>
</div>
                                                <div>
                                                    <div class="fw-bold text-dark fs-6 row-bank-title">${branch.bank_name}</div>
                                                    <small class="text-muted row-branch-subtitle">${branchSubtitle}</small>
</div>
</div>
                                        </td>
                                        <td>
                                            <div><span class="font-monospace badge ${theme.badge} row-ifsc">${branch.ifsc_code}</span></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark row-contact">${branch.contact_person || '—'}</div>
                                            <small class="text-muted row-phone"><i class="bi bi-telephone text-muted me-1"></i>${branch.phone || '—'}</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold ${feeClass} fs-6 row-fee">${formattedFee}</div>
                                        </td>
                                        <td>${statusBadge}</td>
                                        <td class="text-end">
                                            <div class="dropdown table-action-dropdown">
                                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><button class="dropdown-item edit-branch-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li><button class="dropdown-item text-danger delete-branch-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                </ul>
</div>
                                        </td>
                                    </tr>
                                `;

                                $('#branchesTableBody').prepend(newRowHtml);
                                showToast(res.message || `Branch "${branch.bank_name} - ${branch.branch_name}" added successfully!`, 'success');
                            }

                            branchModal.hide();
                            $('#branchForm')[0].reset();
                            if (!isEdit) {
                                currentPage = 1;
                            }
                            refreshBankFilterOptions();
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
                        $btnText.text(isEdit ? 'Update Branch Details' : 'Save Branch Details');

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

            // Delete Branch
            $(document).on('click', '.delete-branch-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const bankName = tr.data('bank');
                const branch = tr.data('branch');

                confirmDelete(`Are you sure you want to remove branch "${bankName} - ${branch}"?`, function () {
                    $.ajax({
                        url: '{{ route("branches.destroy", ":id") }}'.replace(':id', id),
                        method: 'POST',
                        success: function (res) {
                            tr.fadeOut(300, function () {
                                $(this).remove();
                                refreshBankFilterOptions();
                                if (res.stats) {
                                    applyStats(res.stats);
                                } else {
                                    recalculateStatsLocal();
                                }
                                updatePagination();
                            });

                            showToast(res.message || `Branch removed successfully.`, 'success');
                        },
                        error: function (xhr) {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error deleting branch.';
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
