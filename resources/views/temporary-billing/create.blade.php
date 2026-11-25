@extends('layouts.app')

@section('title', $bill ? 'Edit Temporary Bill' : 'Create Temporary Bill')
@section('page_title', $bill ? "Edit Temporary Bill ({$bill->bill_number})" : 'Create Temporary Bill')

@section('content')
    <div class="container-fluid px-0">
        <!-- Header Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt-cutoff text-primary me-2"></i>
                        {{ $bill ? "Edit Temporary Bill — {$bill->bill_number}" : 'Temporary Billing — Painters & Electricians' }}
                    </h5>
                    <small class="text-muted">Select a Project Site to load date-wise Painter &amp; Electrician payments, edit amounts, save, and print</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('temporary-billing.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Saved Bills Table
                    </a>
                </div>
            </div>
        </div>

        <form id="temporaryBillForm" novalidate>
            @csrf
            <!-- Bill Meta Card -->
            <div class="card-custom p-3 p-md-4 mb-4 bg-white">
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold" for="tbSiteSelect">Project Site <span class="text-danger">*</span></label>
                        <select class="form-select fw-semibold" id="tbSiteSelect" name="project_site_id" required>
                            <option value="" disabled {{ !$selectedSiteId ? 'selected' : '' }}>-- Select Project Site --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}"
                                    data-client="{{ $site->client?->name ?? '' }}"
                                    {{ (string) $selectedSiteId === (string) $site->id ? 'selected' : '' }}>
                                    {{ $site->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="errTbSite"></div>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold" for="tbDate">Bill Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tbDate" name="bill_date"
                            value="{{ $bill ? $bill->bill_date->format('Y-m-d') : date('Y-m-d') }}" required>
                        <div class="invalid-feedback" id="errTbDate"></div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold" for="tbTradeFilter">Trade Filter</label>
                        <select class="form-select" id="tbTradeFilter" name="trade_filter">
                            <option value="all" {{ ($bill->trade_filter ?? 'all') === 'all' ? 'selected' : '' }}>All (Painters &amp; Electricians)</option>
                            <option value="painter" {{ ($bill->trade_filter ?? '') === 'painter' ? 'selected' : '' }}>Painter Only</option>
                            <option value="electrician" {{ ($bill->trade_filter ?? '') === 'electrician' ? 'selected' : '' }}>Electrician Only</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold" for="tbTitle">Bill Title</label>
                        <input type="text" class="form-control" id="tbTitle" name="title"
                            value="{{ $bill->title ?? 'Painter & Electrician Temporary Bill' }}"
                            placeholder="e.g. Painter & Electrician Settlement">
                    </div>
                </div>
            </div>

            <!-- Date-Wise Payments Editable Table -->
            <div class="card-custom p-3 p-md-4 mb-4 bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-calendar3 text-primary me-1"></i> Date-Wise Painter &amp; Electrician Payments
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2 d-none" id="tbSelectedSiteBadge"></span>
                        </h6>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="reloadSitePaymentsBtn">
                            <i class="bi bi-arrow-clockwise me-1"></i> Load / Refresh from Site
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" id="addCustomRowBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Row
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="tbItemsTable">
                        <thead class="table-light small">
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="selectAllRowsChk" checked>
                                </th>
                                <th style="width: 145px;">Date</th>
                                <th style="width: 130px;">Trade</th>
                                <th>Worker Category</th>
                                <th style="width: 90px;" class="text-center">Workers</th>
                                <th style="width: 110px;" class="text-end">Rate (₹)</th>
                                <th style="width: 120px;" class="text-end">Actual (₹)</th>
                                <th style="width: 145px;" class="text-end">Edit Amount (₹)</th>
                                <th style="width: 45px;" class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody id="tbItemsBody">
                            <tr id="tbEmptyPlaceholder">
                                <td colspan="9" class="text-center py-4 text-muted">
                                    Select a Project Site above to load date-wise Painter &amp; Electrician payments.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="7" class="text-end fw-bold fs-6">Total Temporary Bill Amount:</td>
                                <td class="text-end fw-bold text-primary fs-5" id="tbGrandTotal">₹0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <label class="form-label small fw-semibold" for="tbNotes">Remarks / Notes (Optional)</label>
                    <textarea class="form-control" id="tbNotes" name="notes" rows="2"
                        placeholder="Optional notes to print on the temporary bill...">{{ $bill->notes ?? '' }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="{{ route('temporary-billing.index') }}" class="btn btn-light btn-sm px-3">Cancel</a>
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 fw-semibold save-tb-btn" data-print="0">
                        <i class="bi bi-floppy me-1"></i> Save Only
                    </button>
                    <button type="button" class="btn btn-deiveni btn-sm px-4 fw-bold save-tb-btn" data-print="1">
                        <i class="bi bi-printer-fill me-1"></i> Save &amp; Print
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });

            const IS_EDIT = {{ $bill ? 'true' : 'false' }};
            const SAVED_ITEMS = @json($bill ? ($bill->items ?? []) : []);
            const WORKER_CATEGORIES = @json($workerCategories ?? []);
            const STORE_URL = "{{ $bill ? route('temporary-billing.update', $bill->encrypted_id) : route('temporary-billing.store') }}";
            const SITE_PAYMENTS_URL = "{{ route('temporary-billing.site-payments') }}";

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function addRowHtml(item) {
                const date = item.date || $('#tbDate').val() || '{{ date('Y-m-d') }}';
                const trade = item.trade || 'Painter';
                const desc = (item.description || '').trim();
                const workers = parseInt(item.worker_count ?? 1) || 0;
                const wage = parseFloat(item.daily_wage ?? 0).toFixed(2);
                const orig = parseFloat(item.original_amount ?? item.billed_amount ?? 0).toFixed(2);
                const billed = parseFloat(item.billed_amount ?? item.original_amount ?? 0).toFixed(2);
                const paidBy = item.paid_by || 'Unpaid';

                let matchedCatId = '';
                WORKER_CATEGORIES.forEach(function (cat) {
                    const ta = (cat.name_ta || '').trim();
                    const en = (cat.name_en || '').trim();
                    if ((ta && desc.startsWith(ta)) || (en && desc.toLowerCase().startsWith(en.toLowerCase()))) {
                        matchedCatId = String(cat.id);
                    }
                });

                const isOthers = (desc !== '' && matchedCatId === '');
                let catOptionsHtml = `<option value="">-- Select Worker Category --</option>`;
                WORKER_CATEGORIES.forEach(function (cat) {
                    const label = cat.name_ta ? `${cat.name_ta} (${cat.name_en})` : cat.name_en;
                    const shortLabel = cat.name_ta || cat.name_en;
                    const selected = String(cat.id) === matchedCatId ? 'selected' : '';
                    catOptionsHtml += `<option value="${cat.id}" data-rate="${parseFloat(cat.daily_wage || 0).toFixed(2)}" data-label="${escapeHtml(shortLabel)}" ${selected}>${escapeHtml(label)}</option>`;
                });
                catOptionsHtml += `<option value="__others__" ${isOthers ? 'selected' : ''}>+ Others (மற்றவை)</option>`;

                return `
                    <tr class="tb-item-row" data-paid-by="${escapeHtml(paidBy)}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input row-include-chk" checked>
                        </td>
                        <td>
                            <input type="date" class="form-control form-control-sm row-date" value="${escapeHtml(date)}" required>
                        </td>
                        <td>
                            <select class="form-select form-select-sm row-trade">
                                <option value="Painter" ${trade === 'Painter' ? 'selected' : ''}>Painter</option>
                                <option value="Electrician" ${trade === 'Electrician' ? 'selected' : ''}>Electrician</option>
                            </select>
                        </td>
                        <td>
                            <select class="form-select form-select-sm row-category-select">
                                ${catOptionsHtml}
                            </select>
                            <input type="text" class="form-control form-control-sm mt-1 row-desc-custom ${isOthers ? '' : 'd-none'}" value="${isOthers ? escapeHtml(desc) : ''}" placeholder="Enter custom work / category...">
                        </td>
                        <td>
                            <input type="number" min="0" class="form-control form-control-sm text-center row-workers" value="${workers}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end row-wage" value="${wage}">
                        </td>
                        <td class="text-end text-muted small fw-semibold row-orig" data-orig="${orig}">
                            ₹${parseFloat(orig).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end fw-bold text-primary row-billed" value="${billed}" required>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 remove-tb-row" title="Remove">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            function recalculateTotal() {
                let total = 0;
                $('#tbItemsBody .tb-item-row').each(function () {
                    const row = $(this);
                    if (row.find('.row-include-chk').is(':checked')) {
                        total += parseFloat(row.find('.row-billed').val()) || 0;
                    }
                });
                $('#tbGrandTotal').text('₹' + total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }

            function renderItems(items) {
                const tbody = $('#tbItemsBody');
                tbody.empty();
                if (!items || items.length === 0) {
                    tbody.html('<tr id="tbEmptyPlaceholder"><td colspan="9" class="text-center py-4 text-muted">No Painter or Electrician payments found for this site. You can add custom rows manually using "Add Row".</td></tr>');
                    recalculateTotal();
                    return;
                }
                items.forEach(item => tbody.append(addRowHtml(item)));
                recalculateTotal();
            }

            let activePaymentsRequest = null;

            function loadSitePayments() {
                const siteId = $('#tbSiteSelect').val();
                const siteName = $('#tbSiteSelect option:selected').text().trim();

                if (siteId) {
                    $('#tbSelectedSiteBadge').text(siteName).removeClass('d-none');
                } else {
                    $('#tbSelectedSiteBadge').addClass('d-none').text('');
                }

                if (activePaymentsRequest) {
                    activePaymentsRequest.abort();
                    activePaymentsRequest = null;
                }

                // Always clear previous site rows & total immediately when switching sites
                $('#tbItemsBody').empty();
                recalculateTotal();

                if (!siteId) {
                    $('#tbItemsBody').html('<tr id="tbEmptyPlaceholder"><td colspan="9" class="text-center py-4 text-muted">Select a Project Site above to load date-wise Painter &amp; Electrician payments.</td></tr>');
                    return;
                }

                const tradeFilter = $('#tbTradeFilter').val() || 'all';
                $('#tbItemsBody').html('<tr><td colspan="9" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Loading date-wise Painter &amp; Electrician payments...</td></tr>');

                activePaymentsRequest = $.get(SITE_PAYMENTS_URL, {
                    project_site_id: siteId,
                    trade_filter: tradeFilter
                }, function (res) {
                    activePaymentsRequest = null;
                    if (res.success) {
                        renderItems(res.items || []);
                    } else {
                        renderItems([]);
                    }
                }).fail(function (xhr, status) {
                    activePaymentsRequest = null;
                    if (status === 'abort') return;
                    $('#tbItemsBody').html('<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load payments for this site.</td></tr>');
                    recalculateTotal();
                });
            }

            if (IS_EDIT) {
                renderItems(SAVED_ITEMS);
            } else if ($('#tbSiteSelect').val()) {
                loadSitePayments();
            }

            $('#tbSiteSelect, #tbTradeFilter').on('change', function () {
                loadSitePayments();
            });

            $('#reloadSitePaymentsBtn').on('click', function () {
                if (!$('#tbSiteSelect').val()) {
                    showToast('Please select a Project Site first.', 'warning');
                    return;
                }
                loadSitePayments();
            });

            $('#addCustomRowBtn').on('click', function () {
                $('#tbEmptyPlaceholder').remove();
                $('#tbItemsBody').append(addRowHtml({
                    date: $('#tbDate').val(),
                    trade: $('#tbTradeFilter').val() === 'electrician' ? 'Electrician' : 'Painter',
                    description: '',
                    worker_count: 1,
                    daily_wage: 0,
                    original_amount: 0,
                    billed_amount: 0,
                }));
                recalculateTotal();
            });

            $(document).on('click', '.remove-tb-row', function () {
                $(this).closest('tr').remove();
                if ($('#tbItemsBody .tb-item-row').length === 0) {
                    $('#tbItemsBody').html('<tr id="tbEmptyPlaceholder"><td colspan="9" class="text-center py-4 text-muted">No rows added.</td></tr>');
                }
                recalculateTotal();
            });

            $(document).on('change', '.row-category-select', function () {
                const row = $(this).closest('tr');
                const val = $(this).val();
                const customInput = row.find('.row-desc-custom');

                if (val === '__others__') {
                    customInput.removeClass('d-none').focus();
                } else {
                    customInput.addClass('d-none');
                    if (val) {
                        const rate = parseFloat($(this).find('option:selected').data('rate')) || 0;
                        row.find('.row-wage').val(rate.toFixed(2));
                        const workers = parseFloat(row.find('.row-workers').val()) || 0;
                        row.find('.row-billed').val((workers * rate).toFixed(2));
                        recalculateTotal();
                    }
                }
            });

            $(document).on('input change', '.row-workers, .row-wage', function () {
                const row = $(this).closest('tr');
                const workers = parseFloat(row.find('.row-workers').val()) || 0;
                const rate = parseFloat(row.find('.row-wage').val()) || 0;
                row.find('.row-billed').val((workers * rate).toFixed(2));
                recalculateTotal();
            });

            $(document).on('input change', '.row-billed, .row-include-chk', recalculateTotal);

            $('#selectAllRowsChk').on('change', function () {
                $('.row-include-chk').prop('checked', $(this).is(':checked'));
                recalculateTotal();
            });

            $('.save-tb-btn').on('click', function () {
                const shouldPrint = String($(this).data('print')) === '1';
                const siteId = $('#tbSiteSelect').val();
                const billDate = $('#tbDate').val();

                if (!siteId) {
                    showToast('Please select a Project Site.', 'warning');
                    return;
                }
                if (!billDate) {
                    showToast('Please select a Bill Date.', 'warning');
                    return;
                }

                const items = [];
                $('#tbItemsBody .tb-item-row').each(function () {
                    const row = $(this);
                    if (!row.find('.row-include-chk').is(':checked')) return;

                    const catSelect = row.find('.row-category-select');
                    const catVal = catSelect.val();
                    const workersCount = parseInt(row.find('.row-workers').val()) || 0;
                    let desc = '';

                    if (catVal === '__others__') {
                        desc = (row.find('.row-desc-custom').val() || '').trim();
                    } else if (catVal) {
                        const label = catSelect.find('option:selected').data('label') || catSelect.find('option:selected').text();
                        desc = `${label} (${workersCount} workers)`;
                    }

                    if (!desc) return;
                    items.push({
                        date: row.find('.row-date').val(),
                        trade: row.find('.row-trade').val(),
                        description: desc,
                        worker_count: workersCount,
                        daily_wage: parseFloat(row.find('.row-wage').val()) || 0,
                        paid_by: row.data('paid-by') || 'Site Manager',
                        original_amount: parseFloat(row.find('.row-orig').data('orig')) || 0,
                        billed_amount: parseFloat(row.find('.row-billed').val()) || 0,
                    });
                });

                if (items.length === 0) {
                    showToast('Please include at least one payment row in the temporary bill.', 'warning');
                    return;
                }

                const btns = $('.save-tb-btn');
                btns.prop('disabled', true);

                $.ajax({
                    url: STORE_URL,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        project_site_id: siteId,
                        bill_date: billDate,
                        title: $('#tbTitle').val(),
                        trade_filter: $('#tbTradeFilter').val(),
                        notes: $('#tbNotes').val(),
                        items: items,
                    }),
                    success: function (res) {
                        btns.prop('disabled', false);
                        showToast(res.message || 'Temporary bill saved!', 'success');
                        if (shouldPrint && res.print_url) {
                            window.location.href = res.print_url;
                        } else {
                            window.location.href = "{{ route('temporary-billing.index') }}";
                        }
                    },
                    error: function (xhr) {
                        btns.prop('disabled', false);
                        const msg = xhr.responseJSON?.message || 'Failed to save temporary bill.';
                        showToast(msg, 'danger');
                    }
                });
            });
        });
    </script>
@endpush
