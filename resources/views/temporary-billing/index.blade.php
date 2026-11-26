@extends('layouts.app')

@section('title', 'Temporary Billing')
@section('page_title', 'Temporary Billing')

@push('styles')
    <style>
        .pagination .page-link {
            color: #1e3a8a;
            border-color: #e2e8f0;
            border-radius: 6px;
            padding: 0.32rem 0.65rem;
            font-size: 0.825rem;
            font-weight: 500;
        }
        .pagination .page-link:hover {
            background-color: #eff6ff;
            color: #1e40af;
        }
        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-color: #1e3a8a;
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0">
        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt text-primary me-2"></i>Temporary Billing (Painters &amp; Electricians)
                    </h5>
                </div>
                <div>
                    <a href="{{ route('temporary-billing.create') }}" class="btn btn-deiveni btn-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Create Temporary Bill
                    </a>
                </div>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="tbSearchInput"
                            placeholder="Search Bill No, Project Site, Title...">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="tbSiteFilter">
                        <option value="">All Project Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="tbResetFilterBtn" title="Reset filters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
                <div class="col-12 col-md-2 text-md-end">
                    <span class="small text-muted">Showing <strong class="text-dark" id="tbTotalCount">{{ $bills->count() }}</strong> bills</span>
                </div>
            </div>
        </div>

        <!-- Saved Temporary Bills Table -->
        <div class="card-custom overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" id="tempBillsTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Bill No</th>
                            <th>Bill Date</th>
                            <th>Project Site</th>
                            <th>Title &amp; Scope</th>
                            <th class="text-center">Entries</th>
                            <th class="text-end">Total Amount (₹)</th>
                            <th class="text-center" style="width: 210px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tempBillsTableBody">
                        @forelse ($bills as $index => $bill)
                            <tr class="tb-row"
                                data-id="{{ $bill->id }}"
                                data-site-id="{{ $bill->project_site_id }}"
                                data-search="{{ strtolower($bill->bill_number . ' ' . ($bill->projectSite?->name ?? '') . ' ' . ($bill->title ?? '')) }}">
                                <td class="text-muted fw-semibold serial-cell">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold">
                                        {{ $bill->bill_number }}
                                    </span>
                                </td>
                                <td class="text-nowrap fw-semibold">
                                    {{ $bill->bill_date ? \Carbon\Carbon::parse($bill->bill_date)->format('d M Y') : '-' }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $bill->projectSite?->name ?? 'N/A' }}</div>
                                    @if ($bill->projectSite?->client)
                                        <small class="text-muted">{{ $bill->projectSite->client->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $bill->title ?: 'Painter & Electrician Temporary Bill' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary-subtle text-dark">{{ is_array($bill->items) ? count($bill->items) : 0 }}</span>
                                </td>
                                <td class="text-end fw-bold text-success fs-6">
                                    ₹{{ number_format((float) $bill->total_amount, 2) }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('temporary-billing.print', $bill->encrypted_id) }}"
                                            target="_blank"
                                            class="btn btn-primary btn-sm px-2 py-1"
                                            title="Print / Reprint Temporary Bill">
                                            <i class="bi bi-printer me-1"></i>Reprint
                                        </a>
                                        <a href="{{ route('temporary-billing.edit', $bill->encrypted_id) }}"
                                            class="btn btn-outline-secondary btn-sm px-2 py-1"
                                            title="Edit Bill">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                            class="btn btn-outline-danger btn-sm px-2 py-1 delete-tb-btn"
                                            data-url="{{ route('temporary-billing.delete', $bill->encrypted_id) }}"
                                            data-bill-no="{{ $bill->bill_number }}"
                                            title="Delete Bill">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyTbRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                                    <div class="fw-semibold">No Temporary Bills Saved Yet</div>
                                    <small>Click "Create Temporary Bill" to generate and save a date-wise Painter &amp; Electrician bill.</small>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsTbRow" class="d-none">
                            <td colspan="8" class="text-center py-4 text-muted">
                                No matching temporary bills found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
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
                            id="pageEnd">{{ min(10, $bills->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $bills->count() }}</strong> bills
                    </span>
                </div>
                <nav aria-label="Temporary bills pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav"></ul>
                </nav>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            let currentPage = 1;
            let perPage = 10;

            function getFilteredRows() {
                const search = ($('#tbSearchInput').val() || '').toLowerCase().trim();
                const siteId = $('#tbSiteFilter').val();

                return $('.tb-row').filter(function () {
                    const row = $(this);
                    const matchSearch = !search || (row.data('search') || '').includes(search);
                    const matchSite = !siteId || String(row.data('site-id')) === String(siteId);
                    return matchSearch && matchSite;
                });
            }

            function updatePagination() {
                const rows = getFilteredRows();
                const total = rows.length;
                const totalPages = Math.max(1, Math.ceil(total / perPage));
                if (currentPage > totalPages) currentPage = totalPages;

                $('.tb-row').hide();

                if (total === 0) {
                    if ($('.tb-row').length > 0) {
                        $('#noResultsTbRow').removeClass('d-none');
                    }
                    $('#pageStart').text(0);
                    $('#pageEnd').text(0);
                    $('#pageTotal').text(0);
                    $('#tbTotalCount').text(0);
                    $('#paginationNav').empty();
                    return;
                }

                $('#noResultsTbRow').addClass('d-none');
                const start = (currentPage - 1) * perPage;
                const end = Math.min(start + perPage, total);

                rows.slice(start, end).each(function (idx) {
                    $(this).show();
                    $(this).find('.serial-cell').text(String(start + idx + 1).padStart(2, '0'));
                });

                $('#pageStart').text(start + 1);
                $('#pageEnd').text(end);
                $('#pageTotal').text(total);
                $('#tbTotalCount').text(total);

                const nav = $('#paginationNav');
                nav.empty();
                nav.append(`<li class="page-item ${currentPage === 1 ? 'disabled' : ''}"><button class="page-link" type="button" data-page="${currentPage - 1}">&laquo;</button></li>`);
                for (let p = 1; p <= totalPages; p++) {
                    nav.append(`<li class="page-item ${p === currentPage ? 'active' : ''}"><button class="page-link" type="button" data-page="${p}">${p}</button></li>`);
                }
                nav.append(`<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}"><button class="page-link" type="button" data-page="${currentPage + 1}">&raquo;</button></li>`);
            }

            $('#perPageSelect').on('change', function () {
                perPage = parseInt($(this).val()) || 10;
                currentPage = 1;
                updatePagination();
            });

            $(document).on('click', '#paginationNav .page-link', function () {
                const p = $(this).data('page');
                if (p && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    currentPage = parseInt(p);
                    updatePagination();
                }
            });

            $('#tbSearchInput').on('input', function () { currentPage = 1; updatePagination(); });
            $('#tbSiteFilter').on('change', function () { currentPage = 1; updatePagination(); });
            $('#tbResetFilterBtn').on('click', function () {
                $('#tbSearchInput').val('');
                $('#tbSiteFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            $(document).on('click', '.delete-tb-btn', function () {
                const btn = $(this);
                const url = btn.data('url');
                const billNo = btn.data('bill-no');
                if (!confirm(`Are you sure you want to delete Temporary Bill ${billNo}?`)) return;

                $.post(url, {}, function (res) {
                    if (res.success) {
                        btn.closest('tr').remove();
                        updatePagination();
                        showToast(res.message || 'Temporary bill deleted.', 'success');
                    }
                }).fail(function () {
                    showToast('Failed to delete temporary bill.', 'danger');
                });
            });

            updatePagination();
        });
    </script>
@endpush
