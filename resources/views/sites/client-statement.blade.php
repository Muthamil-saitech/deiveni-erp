@extends('layouts.app')

@section('title', 'Client Statement - ' . $site->name)
@section('page_title', 'Client Statement & Print (கிளையண்ட் அறிக்கை & அச்சு விவரம்)')

@push('styles')
    <style>
        .statement-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }
        .editable-amt-input {
            font-weight: 700;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .editable-amt-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background-color: #f8fafc;
        }
        .table-statement th {
            font-size: 0.75rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .table-statement td {
            font-size: 0.825rem;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="clientStatementApp">

        <!-- Top Navigation / Action Bar -->
        <div class="statement-card p-3 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('sites.show', $site->encrypted_id) }}"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        title="Back to Project Site Details">
                        <i class="bi bi-arrow-left me-1"></i> Back to Site (தளத்திற்கு திரும்பு)
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="fw-bold mb-0 text-dark">
                                Client Project Statement (கிளையண்ட் திட்ட அறிக்கை)
                            </h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                {{ $site->name }}
                            </span>
                        </div>
                        <small class="text-muted">
                            Client: <strong class="text-dark">{{ $client?->name ?? 'Direct Client' }}</strong>
                            &bull; Location: <span>{{ $site->location ?? 'Site' }}</span>
                        </small>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-3" id="btnSaveOnly">
                        <i class="bi bi-floppy"></i> Save Draft (சேமிக்க)
                    </button>
                    <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm px-3" id="btnSaveAndPrint">
                        <i class="bi bi-printer-fill"></i> Print & Save A4 (அச்சிடு & சேமி)
                    </button>
                </div>
            </div>
        </div>

        <form id="clientStatementForm">
            @csrf

            <!-- Site & Client Meta Box -->
            <div class="statement-card p-4 mb-4">
                <div class="row g-3">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-dark">Statement No (அறிக்கை எண்) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm font-monospace fw-bold" name="statement_number" id="statement_number" value="{{ $nextStatementNumber }}" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-dark">Statement Date (தேதி) <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" name="statement_date" id="statement_date" value="{{ $todayDate }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-dark">Statement Title / Subject (தலைப்பு)</label>
                        <input type="text" class="form-control form-control-sm" name="title" id="statement_title" value="Project Expense & Construction Work Statement — {{ $site->name }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-bold text-dark">Client Name (கிளையண்ட் பெயர்)</label>
                        <input type="text" class="form-control form-control-sm" name="client_name" id="client_name" value="{{ $client?->name ?? 'Client' }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-bold text-dark">Client Phone (தொலைபேசி)</label>
                        <input type="text" class="form-control form-control-sm" name="client_phone" id="client_phone" value="{{ $client?->phone ?? '' }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-bold text-dark">Client Address / Site Location (முகவரி)</label>
                        <input type="text" class="form-control form-control-sm" name="client_address" id="client_address" value="{{ $client?->address ?: $site->location }}">
                    </div>
                </div>
            </div>

            <!-- 1. Date-Wise Expense Items Table Card -->
            <div class="statement-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 border-bottom pb-3">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-date text-primary"></i>
                            Date-Wise Material & Work Expenses (தேதி வாரியான பொருட்கள் & பணிகள்)
                        </h6>
                        <small class="text-muted">
                            தேதி வாரியாக வரிசைப்படுத்தப்பட்டுள்ளது. கிளையண்ட்டிற்கு ஏற்ப தொகையை நேரடியாக <strong>திருத்தம் (Edit)</strong> செய்து கொள்ளலாம்.
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border px-2 py-1" id="itemsCountBadge">
                            {{ count($expenseItems) }} Items
                        </span>
                        <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" id="btnAddCustomItem">
                            <i class="bi bi-plus-circle"></i> + Add Date Item (கூடுதல் பொருள்)
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle table-statement mb-0" id="statementItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th style="width: 135px;">Date</th>
                                <th style="width: 150px;">Category</th>
                                <th>Item / Description</th>
                                <th class="text-center" style="width: 130px;">Qty & Unit</th>
                                <th class="text-end" style="width: 120px;">Actual Cost (₹)</th>
                                <th class="text-end" style="width: 160px;">Client Amount (₹) <span class="text-danger">*</span></th>
                                <th class="text-center" style="width: 45px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="statementItemsBody">
                            @forelse ($expenseItems as $index => $item)
                                <tr class="statement-row" data-index="{{ $index }}">
                                    <td class="text-center text-muted font-monospace row-num">
                                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td>
                                        <input type="date" class="form-control form-control-sm item-date font-monospace"
                                            name="items[{{ $index }}][date]" value="{{ $item['date'] }}">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm item-category"
                                            name="items[{{ $index }}][category]" value="{{ $item['category'] }}">
                                    </td>
                                    <td>
                                        @if (!empty($item['name_ta']))
                                            <div class="fw-bold text-dark fs-6">{{ $item['name_ta'] }}</div>
                                            <small class="text-muted d-block">{{ $item['name_en'] }} @if(!empty($item['supplier_name'])) &bull; {{ $item['supplier_name'] }} @endif</small>
                                        @else
                                            <div class="fw-medium text-dark">{{ $item['name_en'] ?: ($item['description'] ?? 'Work Item') }} @if(!empty($item['supplier_name'])) <small class="text-muted">&bull; {{ $item['supplier_name'] }}</small> @endif</div>
                                        @endif
                                        <input type="hidden" class="item-desc" name="items[{{ $index }}][description]" value="{{ $item['description'] }}">
                                        <input type="hidden" class="item-name-ta" name="items[{{ $index }}][name_ta]" value="{{ $item['name_ta'] ?? '' }}">
                                        <input type="hidden" class="item-name-en" name="items[{{ $index }}][name_en]" value="{{ $item['name_en'] ?? '' }}">
                                        <input type="hidden" class="item-supplier" name="items[{{ $index }}][supplier_name]" value="{{ $item['supplier_name'] ?? '' }}">
                                        <input type="hidden" class="item-ref-no" name="items[{{ $index }}][ref_no]" value="{{ $item['ref_no'] }}">
                                        <input type="hidden" class="item-source-type" name="items[{{ $index }}][source_type]" value="{{ $item['source_type'] }}">
                                        <input type="hidden" class="item-is-weekly" name="items[{{ $index }}][is_weekly]" value="0">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" class="form-control text-center item-qty"
                                                name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                                            <input type="text" class="form-control text-center item-unit" style="max-width: 60px;"
                                                name="items[{{ $index }}][unit]" value="{{ $item['unit'] }}">
                                        </div>
                                    </td>
                                    <td class="text-end text-muted small">
                                        ₹{{ number_format($item['original_amount'], 2) }}
                                        <input type="hidden" class="item-orig-amt" name="items[{{ $index }}][original_amount]" value="{{ $item['original_amount'] }}">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-end editable-amt-input item-amount"
                                                name="items[{{ $index }}][billed_amount]" value="{{ (float) $item['billed_amount'] }}" required>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-row" title="Remove line item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noItemsPlaceholder">
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No date-wise expenses recorded yet. Click "+ Add Date Item" to add an item.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Weekly Site & Operational Expenses Table Card (Placed Separately Below) -->
            <div class="statement-card p-4 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3 border-bottom pb-3">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-week text-success"></i>
                            Weekly Site & Operational Expenses (வாரம் வாரியாக செலவுகள்)
                        </h6>
                        <small class="text-muted">
                            அன்றாட தளச் செலவுகள் (Daily site cash / expenses) வாராந்திர அடிப்படையில் இங்கு கணக்கிடப்பட்டுள்ளது. தொகையை நேரடியாக <strong>திருத்தம் (Edit)</strong> செய்து கொள்ளலாம்.
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" id="weeklyCountBadge">
                            {{ count($weeklyExpenses) }} Weeks
                        </span>
                        <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1" id="btnAddWeeklyItem">
                            <i class="bi bi-plus-circle"></i> + Add Weekly Expense (கூடுதல் வாரம் சேர்)
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle table-statement mb-0" id="weeklyExpensesTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th style="width: 260px;">Week Period (From ~ To)</th>
                                <th>Description</th>
                                <th class="text-end" style="width: 140px;">Actual Cost (₹)</th>
                                <th class="text-end" style="width: 170px;">Client Amount (₹) <span class="text-danger">*</span></th>
                                <th class="text-center" style="width: 45px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="weeklyExpensesBody">
                            @forelse ($weeklyExpenses as $wIdx => $week)
                                <tr class="weekly-row" data-index="{{ $wIdx }}">
                                    <td class="text-center text-muted font-monospace weekly-row-num">
                                        {{ str_pad($wIdx + 1, 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1">
                                            <input type="date" class="form-control form-control-sm weekly-start-date font-monospace"
                                                name="weekly[{{ $wIdx }}][start_date]" value="{{ $week['start_date'] }}">
                                            <span class="text-muted fw-bold">~</span>
                                            <input type="date" class="form-control form-control-sm weekly-end-date font-monospace"
                                                name="weekly[{{ $wIdx }}][end_date]" value="{{ $week['end_date'] }}">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm weekly-desc"
                                            name="weekly[{{ $wIdx }}][description]" value="{{ $week['description'] }}">
                                        <input type="hidden" class="weekly-category" name="weekly[{{ $wIdx }}][category]" value="Weekly Expenses">
                                        <input type="hidden" class="weekly-is-weekly" name="weekly[{{ $wIdx }}][is_weekly]" value="1">
                                        <input type="hidden" class="weekly-name-ta" name="weekly[{{ $wIdx }}][name_ta]" value="{{ $week['name_ta'] ?? '' }}">
                                        <input type="hidden" class="weekly-name-en" name="weekly[{{ $wIdx }}][name_en]" value="{{ $week['name_en'] ?? '' }}">
                                        <input type="hidden" class="weekly-ref-no" name="weekly[{{ $wIdx }}][ref_no]" value="{{ $week['ref_no'] ?? 'WEEK' }}">
                                        <input type="hidden" class="weekly-source-type" name="weekly[{{ $wIdx }}][source_type]" value="WeeklyExpense">
                                    </td>
                                    <td class="text-end text-muted small">
                                        ₹{{ number_format($week['original_amount'], 2) }}
                                        <input type="hidden" class="weekly-orig-amt" name="weekly[{{ $wIdx }}][original_amount]" value="{{ $week['original_amount'] }}">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text fw-bold">₹</span>
                                            <input type="number" step="0.01" min="0" class="form-control text-end editable-amt-input weekly-amount"
                                                name="weekly[{{ $wIdx }}][billed_amount]" value="{{ (float) $week['billed_amount'] }}" required>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-weekly-row" title="Remove weekly expense">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noWeeklyPlaceholder">
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        No weekly expenses recorded yet. Click "+ Add Weekly Expense" to add a week block.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Statement Summary & Notes Box -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-7">
                    <div class="statement-card p-4 h-100">
                        <h6 class="fw-bold mb-2 text-dark">
                            <i class="bi bi-card-text text-primary me-1"></i> Notes & Terms / Payment Instructions (குறிப்புகள்)
                        </h6>
                        <textarea class="form-control form-control-sm" name="notes" id="statement_notes" rows="6"
                            placeholder="Enter payment instructions, bank details, or remarks for client...">1. All work carried out as per project specifications and approved standards.
2. Please make payment via Cheque / Bank Transfer to the authorized company account.
3. Thank you for your business!</textarea>
                        <small class="text-muted mt-2 d-block">
                            These notes will appear at the bottom of the client's printed statement.
                        </small>
                    </div>
                </div>

                <div class="col-12 col-lg-5">
                    <div class="statement-card p-4 h-100 bg-light border-2">
                        <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">
                            <i class="bi bi-calculator text-primary me-1"></i> Statement Summary (தொகை சுருக்கம்)
                        </h6>

                        <!-- Items Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Date Items Subtotal (பொருட்கள் மொத்தம்):</span>
                            <span class="fw-bold text-dark" id="dispItemsSubtotal">₹0.00</span>
                        </div>

                        <!-- Weekly Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Weekly Expenses Subtotal (வாராந்திர செலவு):</span>
                            <span class="fw-bold text-dark" id="dispWeeklySubtotal">₹0.00</span>
                        </div>

                        <hr class="my-2">

                        <!-- Combined Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark small">Combined Subtotal (துணை மொத்தம்):</span>
                            <span class="fw-bold text-primary fs-6" id="dispSubtotal">₹0.00</span>
                            <input type="hidden" name="subtotal" id="inpSubtotal" value="0">
                        </div>

                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-6">
                                <span class="text-muted small">Tax / GST (%):</span>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control text-end"
                                        name="tax_percent" id="inpTaxPercent" value="0">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Tax Amount (வரித் தொகை):</span>
                            <span class="fw-semibold text-dark" id="dispTaxAmount">₹0.00</span>
                            <input type="hidden" name="tax_amount" id="inpTaxAmount" value="0">
                        </div>

                        <div class="row g-2 align-items-center mb-3">
                            <div class="col-6">
                                <span class="text-muted small">Discount / Adjustment (தள்ளுபடி ₹):</span>
                            </div>
                            <div class="col-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" min="0" class="form-control text-end"
                                        name="discount_amount" id="inpDiscountAmount" value="0">
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="p-3 bg-white rounded border border-success-subtle mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Grand Total (மொத்தத் தொகை):</span>
                                <span class="fw-bold text-success fs-4" id="dispGrandTotal">₹0.00</span>
                                <input type="hidden" name="total_amount" id="inpGrandTotal" value="0">
                            </div>
                            <div class="mt-2 text-muted small font-monospace" id="dispWordsTotal" style="font-size:0.75rem;">
                                Rupees Zero Only
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-lg shadow-sm d-flex align-items-center justify-content-center gap-2" id="btnBigPrint">
                                <i class="bi bi-printer-fill fs-5"></i> Print & Save A4 (அச்சிடு & சேமி)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('statementItemsBody');
    const weeklyBody = document.getElementById('weeklyExpensesBody');
    const btnAdd = document.getElementById('btnAddCustomItem');
    const btnAddWeekly = document.getElementById('btnAddWeeklyItem');
    const inpTaxPercent = document.getElementById('inpTaxPercent');
    const inpDiscountAmount = document.getElementById('inpDiscountAmount');
    const dispItemsSubtotal = document.getElementById('dispItemsSubtotal');
    const dispWeeklySubtotal = document.getElementById('dispWeeklySubtotal');
    const dispSubtotal = document.getElementById('dispSubtotal');
    const inpSubtotal = document.getElementById('inpSubtotal');
    const dispTaxAmount = document.getElementById('dispTaxAmount');
    const inpTaxAmount = document.getElementById('inpTaxAmount');
    const dispGrandTotal = document.getElementById('dispGrandTotal');
    const inpGrandTotal = document.getElementById('inpGrandTotal');
    const dispWordsTotal = document.getElementById('dispWordsTotal');
    const itemsCountBadge = document.getElementById('itemsCountBadge');
    const weeklyCountBadge = document.getElementById('weeklyCountBadge');

    let rowIndexCounter = {{ count($expenseItems) }};
    let weeklyIndexCounter = {{ count($weeklyExpenses) }};

    function updateRowNumbersAndBadges() {
        const itemRows = tableBody.querySelectorAll('.statement-row');
        itemRows.forEach((row, i) => {
            const numTd = row.querySelector('.row-num');
            if (numTd) numTd.textContent = String(i + 1).padStart(2, '0');
        });
        if (itemsCountBadge) itemsCountBadge.textContent = `${itemRows.length} Items`;

        const wRows = weeklyBody.querySelectorAll('.weekly-row');
        wRows.forEach((row, i) => {
            const numTd = row.querySelector('.weekly-row-num');
            if (numTd) numTd.textContent = String(i + 1).padStart(2, '0');
        });
        if (weeklyCountBadge) weeklyCountBadge.textContent = `${wRows.length} Weeks`;
    }

    // Calculate Totals function
    function calculateTotals() {
        let itemsSubtotal = 0;
        tableBody.querySelectorAll('.item-amount').forEach(input => {
            const val = parseFloat(input.value) || 0;
            itemsSubtotal += val;
        });

        let weeklySubtotal = 0;
        weeklyBody.querySelectorAll('.weekly-amount').forEach(input => {
            const val = parseFloat(input.value) || 0;
            weeklySubtotal += val;
        });

        const combinedSubtotal = itemsSubtotal + weeklySubtotal;
        const taxPercent = parseFloat(inpTaxPercent.value) || 0;
        const taxAmount = (combinedSubtotal * taxPercent) / 100;
        const discountAmount = parseFloat(inpDiscountAmount.value) || 0;
        const grandTotal = Math.max(0, combinedSubtotal + taxAmount - discountAmount);

        dispItemsSubtotal.textContent = '₹' + itemsSubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        dispWeeklySubtotal.textContent = '₹' + weeklySubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        dispSubtotal.textContent = '₹' + combinedSubtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        inpSubtotal.value = combinedSubtotal.toFixed(2);

        dispTaxAmount.textContent = '₹' + taxAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        inpTaxAmount.value = taxAmount.toFixed(2);

        dispGrandTotal.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        inpGrandTotal.value = grandTotal.toFixed(2);

        dispWordsTotal.textContent = numberToWords(Math.round(grandTotal));
        updateRowNumbersAndBadges();
    }

    // Number to Indian Words
    function numberToWords(number) {
        if (number === 0) return 'Rupees Zero Only';

        const units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        function convertThree(num) {
            let str = '';
            if (num >= 100) {
                str += units[Math.floor(num / 100)] + ' Hundred ';
                num %= 100;
            }
            if (num >= 20) {
                str += tens[Math.floor(num / 10)] + ' ';
                num %= 10;
            }
            if (num > 0) {
                str += units[num] + ' ';
            }
            return str.trim();
        }

        let words = '';
        const crore = Math.floor(number / 10000000);
        number %= 10000000;
        const lakh = Math.floor(number / 100000);
        number %= 100000;
        const thousand = Math.floor(number / 1000);
        number %= 1000;
        const hundred = number;

        if (crore > 0) words += convertThree(crore) + ' Crore ';
        if (lakh > 0) words += convertThree(lakh) + ' Lakh ';
        if (thousand > 0) words += convertThree(thousand) + ' Thousand ';
        if (hundred > 0) words += convertThree(hundred);

        return 'Rupees ' + words.trim() + ' Only';
    }

    // Event delegation on Date-Items table body
    tableBody.addEventListener('input', function (e) {
        if (e.target.classList.contains('item-amount') || e.target.classList.contains('item-qty')) {
            calculateTotals();
        }
    });

    tableBody.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.btn-remove-row');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            if (row) {
                row.remove();
                calculateTotals();
            }
        }
    });

    // Event delegation on Weekly-Expenses table body
    weeklyBody.addEventListener('input', function (e) {
        if (e.target.classList.contains('weekly-amount')) {
            calculateTotals();
        }
    });

    weeklyBody.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.btn-remove-weekly-row');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            if (row) {
                row.remove();
                calculateTotals();
            }
        }
    });

    inpTaxPercent.addEventListener('input', calculateTotals);
    inpDiscountAmount.addEventListener('input', calculateTotals);

    // Add custom date item
    btnAdd.addEventListener('click', function () {
        const placeholder = document.getElementById('noItemsPlaceholder');
        if (placeholder) placeholder.remove();

        const idx = rowIndexCounter++;
        const newRow = document.createElement('tr');
        newRow.className = 'statement-row';
        newRow.dataset.index = idx;
        newRow.innerHTML = `
            <td class="text-center text-muted font-monospace row-num">00</td>
            <td>
                <input type="date" class="form-control form-control-sm item-date font-monospace"
                    name="items[${idx}][date]" value="{{ $todayDate }}">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm item-category"
                    name="items[${idx}][category]" value="Additional Work">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm item-desc"
                    name="items[${idx}][description]" placeholder="Enter item description...">
                <input type="hidden" class="item-name-ta" name="items[${idx}][name_ta]" value="">
                <input type="hidden" class="item-name-en" name="items[${idx}][name_en]" value="Additional Work">
                <input type="hidden" class="item-supplier" name="items[${idx}][supplier_name]" value="">
                <input type="hidden" class="item-ref-no" name="items[${idx}][ref_no]" value="CUSTOM">
                <input type="hidden" class="item-source-type" name="items[${idx}][source_type]" value="Custom">
                <input type="hidden" class="item-is-weekly" name="items[${idx}][is_weekly]" value="0">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" class="form-control text-center item-qty"
                        name="items[${idx}][quantity]" value="1">
                    <input type="text" class="form-control text-center item-unit" style="max-width: 60px;"
                        name="items[${idx}][unit]" value="Job">
                </div>
            </td>
            <td class="text-end text-muted small">
                ₹0.00
                <input type="hidden" class="item-orig-amt" name="items[${idx}][original_amount]" value="0">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold">₹</span>
                    <input type="number" step="0.01" min="0" class="form-control text-end editable-amt-input item-amount"
                        name="items[${idx}][billed_amount]" value="0.00" required>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-row" title="Remove line item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(newRow);
        calculateTotals();
    });

    // Add custom weekly item
    btnAddWeekly.addEventListener('click', function () {
        const placeholder = document.getElementById('noWeeklyPlaceholder');
        if (placeholder) placeholder.remove();

        const wIdx = weeklyIndexCounter++;
        const newRow = document.createElement('tr');
        newRow.className = 'weekly-row';
        newRow.dataset.index = wIdx;
        newRow.innerHTML = `
            <td class="text-center text-muted font-monospace weekly-row-num">00</td>
            <td>
                <div class="d-flex align-items-center gap-1">
                    <input type="date" class="form-control form-control-sm weekly-start-date font-monospace"
                        name="weekly[${wIdx}][start_date]" value="{{ $todayDate }}">
                    <span class="text-muted fw-bold">~</span>
                    <input type="date" class="form-control form-control-sm weekly-end-date font-monospace"
                        name="weekly[${wIdx}][end_date]" value="{{ $todayDate }}">
                </div>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm weekly-desc"
                    name="weekly[${wIdx}][description]" value="Weekly Site Expenses">
                <input type="hidden" class="weekly-category" name="weekly[${wIdx}][category]" value="Weekly Expenses">
                <input type="hidden" class="weekly-is-weekly" name="weekly[${wIdx}][is_weekly]" value="1">
                <input type="hidden" class="weekly-name-ta" name="weekly[${wIdx}][name_ta]" value="வாராந்திர தள செலவுகள்">
                <input type="hidden" class="weekly-name-en" name="weekly[${wIdx}][name_en]" value="Weekly Site Expenses">
                <input type="hidden" class="weekly-ref-no" name="weekly[${wIdx}][ref_no]" value="WEEK">
                <input type="hidden" class="weekly-source-type" name="weekly[${wIdx}][source_type]" value="WeeklyExpense">
            </td>
            <td class="text-end text-muted small">
                ₹0.00
                <input type="hidden" class="weekly-orig-amt" name="weekly[${wIdx}][original_amount]" value="0">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold">₹</span>
                    <input type="number" step="0.01" min="0" class="form-control text-end editable-amt-input weekly-amount"
                        name="weekly[${wIdx}][billed_amount]" value="0.00" required>
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-1 btn-remove-weekly-row" title="Remove weekly expense">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        weeklyBody.appendChild(newRow);
        calculateTotals();
    });

    // Save and optionally trigger print
    async function saveStatement(triggerPrint = false) {
        const rows = tableBody.querySelectorAll('.statement-row');
        const weeklyRows = weeklyBody.querySelectorAll('.weekly-row');

        if (rows.length === 0 && weeklyRows.length === 0) {
            alert('Please add at least one line item or weekly expense to the statement.');
            return;
        }

        const btnPrint = document.getElementById('btnSaveAndPrint');
        const btnBig = document.getElementById('btnBigPrint');
        const btnDraft = document.getElementById('btnSaveOnly');

        const oldPrintText = btnPrint.innerHTML;
        btnPrint.disabled = true;
        btnBig.disabled = true;
        btnDraft.disabled = true;
        btnPrint.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        // Build combined items payload
        const allItems = [];

        // 1. Collect date items
        rows.forEach((r) => {
            const date = r.querySelector('.item-date')?.value || '{{ $todayDate }}';
            const cat = r.querySelector('.item-category')?.value || 'Material';
            const desc = r.querySelector('.item-desc')?.value || '';
            const nameTa = r.querySelector('.item-name-ta')?.value || '';
            const nameEn = r.querySelector('.item-name-en')?.value || '';
            const supplier = r.querySelector('.item-supplier')?.value || '';
            const refNo = r.querySelector('.item-ref-no')?.value || '';
            const sourceType = r.querySelector('.item-source-type')?.value || 'Material';
            const qty = parseFloat(r.querySelector('.item-qty')?.value) || 1;
            const unit = r.querySelector('.item-unit')?.value || 'Nos';
            const origAmt = parseFloat(r.querySelector('.item-orig-amt')?.value) || 0;
            const billedAmt = parseFloat(r.querySelector('.item-amount')?.value) || 0;

            allItems.push({
                category: cat,
                description: desc,
                date: date,
                name_ta: nameTa,
                name_en: nameEn,
                supplier_name: supplier,
                ref_no: refNo,
                source_type: sourceType,
                quantity: qty,
                unit: unit,
                original_amount: origAmt,
                billed_amount: billedAmt,
                is_weekly: 0
            });
        });

        // 2. Collect weekly items
        weeklyRows.forEach((wr) => {
            const sDate = wr.querySelector('.weekly-start-date')?.value || '{{ $todayDate }}';
            const eDate = wr.querySelector('.weekly-end-date')?.value || '{{ $todayDate }}';
            const desc = wr.querySelector('.weekly-desc')?.value || 'Weekly Site Expenses';
            const nameTa = wr.querySelector('.weekly-name-ta')?.value || '';
            const nameEn = wr.querySelector('.weekly-name-en')?.value || '';
            const cat = wr.querySelector('.weekly-category')?.value || 'Weekly Expenses';
            const refNo = wr.querySelector('.weekly-ref-no')?.value || 'WEEK';
            const sourceType = wr.querySelector('.weekly-source-type')?.value || 'WeeklyExpense';
            const origAmt = parseFloat(wr.querySelector('.weekly-orig-amt')?.value) || 0;
            const billedAmt = parseFloat(wr.querySelector('.weekly-amount')?.value) || 0;

            allItems.push({
                category: cat,
                description: desc,
                date: sDate,
                start_date: sDate,
                end_date: eDate,
                name_ta: nameTa,
                name_en: nameEn,
                ref_no: refNo,
                source_type: sourceType,
                quantity: 1,
                unit: 'Week',
                original_amount: origAmt,
                billed_amount: billedAmt,
                is_weekly: 1
            });
        });

        const payload = {
            statement_number: document.getElementById('statement_number').value,
            statement_date: document.getElementById('statement_date').value,
            title: document.getElementById('statement_title').value,
            client_name: document.getElementById('client_name').value,
            client_phone: document.getElementById('client_phone').value,
            client_address: document.getElementById('client_address').value,
            notes: document.getElementById('statement_notes').value,
            subtotal: parseFloat(inpSubtotal.value) || 0,
            tax_percent: parseFloat(inpTaxPercent.value) || 0,
            tax_amount: parseFloat(inpTaxAmount.value) || 0,
            discount_amount: parseFloat(inpDiscountAmount.value) || 0,
            total_amount: parseFloat(inpGrandTotal.value) || 0,
            items: allItems
        };

        try {
            const response = await fetch("{{ route('sites.client-statement.store', $site->encrypted_id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                let err = data.message || 'Error saving statement.';
                if (data.errors) {
                    const first = Object.keys(data.errors)[0];
                    err = data.errors[first][0];
                }
                alert(err);
                btnPrint.disabled = false;
                btnBig.disabled = false;
                btnDraft.disabled = false;
                btnPrint.innerHTML = oldPrintText;
                return;
            }

            // Success
            if (triggerPrint) {
                const printWindow = window.open(data.print_url + '?autoprint=1', '_blank');
                setTimeout(() => {
                    window.location.href = data.site_url;
                }, 1000);
            } else {
                alert('Client Statement saved successfully! (அறிக்கை சேமிக்கப்பட்டது)');
                window.location.href = data.site_url;
            }

        } catch (error) {
            console.error('Error saving statement:', error);
            alert('A network or server error occurred. Please try again.');
            btnPrint.disabled = false;
            btnBig.disabled = false;
            btnDraft.disabled = false;
            btnPrint.innerHTML = oldPrintText;
        }
    }

    document.getElementById('btnSaveAndPrint').addEventListener('click', () => saveStatement(true));
    document.getElementById('btnBigPrint').addEventListener('click', () => saveStatement(true));
    document.getElementById('btnSaveOnly').addEventListener('click', () => saveStatement(false));

    // Initial calculation
    calculateTotals();
});
</script>
@endpush
