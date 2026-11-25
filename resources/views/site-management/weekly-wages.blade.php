@extends('layouts.app')

@section('title', 'Weekly Wages')
@section('page_title', 'Weekly Wages Sheet ')

@push('styles')
    <style>
        #saveWeeklySettlementBtn:disabled,
        #saveWeeklySettlementBtn.disabled,
        #btnFinalApprove:disabled,
        #btnFinalApprove.disabled {
            background: #e2e8f0 !important;
            background-image: none !important;
            color: #475569 !important;
            border: 1px solid #cbd5e1 !important;
            opacity: 1 !important;
            box-shadow: none !important;
            cursor: not-allowed !important;
            pointer-events: auto !important;
        }

        #saveWeeklySettlementBtn:disabled i,
        #saveWeeklySettlementBtn.disabled i,
        #btnFinalApprove:disabled i,
        #btnFinalApprove.disabled i {
            color: #475569 !important;
        }

        #saveWeeklySettlementBtn:disabled:hover,
        #saveWeeklySettlementBtn.disabled:hover,
        #btnFinalApprove:disabled:hover,
        #btnFinalApprove.disabled:hover {
            background: #e2e8f0 !important;
            background-image: none !important;
            color: #475569 !important;
            border-color: #cbd5e1 !important;
            box-shadow: none !important;
        }
    </style>
@endpush

@section('content')
    @php
        $isWeekend = now()->isSaturday() || now()->isSunday();
    @endphp
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="fw-bold mb-0 text-dark">Weekly Wages Sheet & Settlement</h5>
                        <span class="badge bg-secondary"><i class="bi bi-lock-fill me-1"></i>Labour Data: Read-Only</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Synced from Daily
                            Allocation</span>
</div>
</div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ url('/daily-allocation') }}"
                        class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                        <i class="bi bi-calendar-plus"></i>
                        <span>Go to Daily Allocation</span>
</a>
                    <button class="btn btn-deiveni btn-sm d-flex align-items-center gap-1" id="saveWeeklySettlementBtn"
                        title="Approve & Settle Weekly Wages">
                        <i class="bi bi-check2-all"></i>
                        <span>Approve & Settle Weekly Wages</span>
                    </button>
</div>
</div>
</div>

        <!-- 1. Site Selector & Week Cycle Filter -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white border-start border-4 border-primary">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="bi bi-building-gear text-primary me-1"></i> 1. Select Project Site  <span
                            class="text-danger">*</span>
</label>
                    <select class="form-select form-select-md fw-bold text-primary border-primary border-opacity-50"
                        id="weeklySiteSelect">
                        @forelse($sites as $site)
                            <option value="{{ $site->id }}" data-name="{{ $site->name }}" {{ $loop->first ? 'selected' : '' }}>
                                {{ $site->name }} &bull; {{ $site->work_type ?? 'Construction' }} ({{ $site->location }})
</option>
                        @empty
                            <option value="" disabled selected>No project sites found</option>
                        @endforelse
                    </select>
</div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark mb-1 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-calendar3-range text-primary me-1"></i> 2. Wage Week Cycle</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold"
                            id="activeWeekBadge">Current Week</span>
</label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary btn-sm px-2" type="button" id="prevWeekBtn"
                            title="Previous Week ">
                            <i class="bi bi-chevron-left"></i>
</button>
                        <select class="form-select form-select-md fw-bold" id="weeklyCycleSelect">
                            <!-- Populated dynamically: Current week default, recent weeks, custom date selection -->
                        </select>
                        <button class="btn btn-outline-secondary btn-sm px-2" type="button" id="nextWeekBtn"
                            title="Next Week ">
                            <i class="bi bi-chevron-right"></i>
</button>
                        <button class="btn btn-outline-primary btn-sm fw-semibold" type="button" id="currentWeekQuickBtn"
                            title="Go to Current Active Week">
                            Current
</button>
                        <button class="btn btn-outline-secondary btn-sm fw-semibold" type="button" id="customDateToggleBtn"
                            title="Select any date or previous year ">
                            <i class="bi bi-calendar-date"></i> Custom
</button>
</div>

                    <!-- Custom Date Selection Drawer -->
                    <div id="customDateSection" class="p-2 mt-2 border rounded bg-light" style="display: none;">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <label for="customWeekDatePicker" class="form-label small fw-bold mb-0 text-muted">
                                <i class="bi bi-calendar-event text-primary me-1"></i> Select Any Date :
</label>
                            <input type="date" class="form-control form-control-sm" id="customWeekDatePicker" style="max-width: 170px;" value="{{ date('Y-m-d') }}">
                            <button type="button" class="btn btn-sm btn-primary" id="loadCustomWeekBtn">
                                <i class="bi bi-arrow-right-circle me-1"></i> Go to Week 
</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="closeCustomWeekBtn" title="Close">
                                <i class="bi bi-x-lg"></i>
</button>
                            <span class="small text-muted ms-auto" id="customWeekPreviewText"></span>
</div>
</div>
</div>
</div>
</div>

        <!-- 3. Day-by-Day Aggregated Table (READ-ONLY DISPLAY) -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white">
            <div
                class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-table text-primary me-2"></i>Day-by-Day Allocation & Daily Wage Records for <span
                            id="siteTitleDisplay" class="text-primary">Project Site</span>
                        <span class="badge bg-primary ms-2" id="tableWeekCycleBadge">Current Week</span>
</h6>
                    <small class="text-muted" id="tableWeekRangeText">Updated from Daily Allocation entries for the selected
                        week period.</small>
</div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-2 py-1" id="weekRangeDisplayBadge">Loading
                        Week...</span>
</div>
</div>

            <!-- Read Only Attendance Sheet (Client Understanding Level) -->
            <div class="table-responsive mb-3">
                <table class="table table-custom table-bordered align-middle text-center mb-0" id="weeklyWageDisplayTable">
                    <thead class="table-light">
                        <tr class="text-nowrap">
                            <th class="text-start" style="min-width: 140px;">DATE & DAY</th>
                            <th class="text-start" style="min-width: 320px;">WORKER CATEGORY BREAKDOWN</th>
                            <th class="text-center" style="min-width: 130px;">TOTAL LABOUR</th>
                            <th class="text-end" style="min-width: 130px;">DAILY LABOUR WAGE</th>
                            <th class="text-end" style="min-width: 130px;">DAILY SITE EXPENSES</th>
                            <th class="text-end" style="min-width: 130px;">DAY SUBTOTAL</th>
                            <th class="text-center" style="min-width: 90px;">STATUS</th>
                            <th class="text-center" style="min-width: 80px;">DETAILS</th>
                        </tr>
                    </thead>
                    <tbody id="weeklyDaysTbody">
                        <!-- Populated dynamically from saved daily allocations in database -->
                    </tbody>

                    <!-- Table Footer: Cumulative Subtotal of Daily Records -->
                    <tfoot class="table-dark">
                        <tr class="fw-bold align-middle">
                            <td class="text-start align-middle">Daily Sync Subtotal:</td>
                            <td class="text-start align-middle">
                                <span class="text-white fw-bold" id="footRecordedDaysCountText">0 of 7 Days Recorded</span>
                            </td>
                            <td class="text-center align-middle" style="min-width: 140px;">
                                <span class="badge bg-light text-dark fs-6 fw-bold px-3 py-1 shadow-sm" id="footTotalLabourManDays">0 Man-Days</span>
                            </td>
                            <td class="text-end fs-6 fw-bold text-white align-middle" id="footTotalLabourWages">₹0</td>
                            <td class="text-end fs-6 fw-bold text-warning align-middle" id="footTotalDailyExpenses">₹0</td>
                            <td class="text-end fs-6 fw-bold text-success align-middle" id="footTotalDailyCumulative">₹0</td>
                            <td colspan="2" class="text-center text-white-50 align-middle small"><i class="bi bi-lock-fill me-1"></i> Read Only</td>
                        </tr>
                    </tfoot>
                </table>
</div>

            <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3 small">
                <span class="text-muted"><i class="bi bi-info-circle text-primary me-1"></i> Working days strictly Monday to Saturday. Entries are saved in <a href="{{ url('/daily-allocation') }}" class="fw-bold text-primary">Daily Allocation</a>.</span>
                <span class="fw-semibold text-dark">Active Work Days: <strong id="footRecordedDaysCount">0 of 6 Days Recorded</strong></span>
</div>
</div>

        <!-- 3.1 Weekly Category-Wise Salary Calculation -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white border-start border-4 border-success">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-calculator-fill text-success me-2"></i>Weekly Labour Wage Calculation by Trade 
</h6>
</div>
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 fw-bold px-3 py-2" id="badgeTotalLabourWages">₹0</span>
</div>
</div>

            <div class="table-responsive mb-0">
                <table class="table table-custom table-bordered align-middle mb-0" id="weeklyCategorySalaryTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start" style="min-width: 220px;">Worker Category</th>
                            <th class="text-center" style="min-width: 140px;">Total Workers</th>
                            <th class="text-end" style="min-width: 160px;">Daily Wage Rate</th>
                            <th class="text-end" style="min-width: 180px;">Total Salary</th>
                        </tr>
                    </thead>
                    <tbody id="weeklyCategorySalaryTbody">
                        <!-- Populated dynamically: e.g. கொத்தனார்: 11 Workers x ₹1200 = ₹13,200 -->
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td class="text-start">Total Labour Salary :</td>
                            <td class="text-center text-primary" id="catSalaryTotalWorkers">0 Workers</td>
                            <td></td>
                            <td class="text-end fs-6 text-success fw-bold" id="catSalaryTotalAmount">₹0</td>
                        </tr>
                    </tfoot>
                </table>
</div>
</div>

        <!-- 4. ADDITIONAL & DAILY WEEKLY EXPENSES SECTION -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white border-start border-4 border-warning">
            <div
                class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-cup-hot-fill text-warning me-2"></i>Weekly Site Expenses & Settlement
                            Adjustments 
</h6>
                        <span class="badge bg-warning text-dark">Weekly Settlement Expenses</span>
</div>
</div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-semibold" id="addWeeklyExpenseBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Expense
</button>
</div>
</div>

            <!-- Expenses Table -->
            <div class="table-responsive mb-2" style="overflow: visible !important;">
                <table class="table table-custom table-bordered align-middle mb-0" id="weeklyExpensesTable">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="text-start" style="min-width: 250px;">Expense Item / Extra Purchase Description
</th>
                            <th style="width: 140px;" class="text-center">Quantity / Hours</th>
                            <th style="width: 160px;" class="text-end">Unit Price</th>
                            <th class="text-end" style="width: 160px;">Final Price (₹)</th>
                            <th style="width: 120px;" class="text-center">Type / Action</th>
                        </tr>
                    </thead>
                    <tbody id="weeklyExpensesBody">
                        <!-- Populated dynamically: Synced daily expenses + Additional weekly expenses -->
                    </tbody>
                    <tfoot class="bg-light">
                        <tr class="text-muted small">
                            <td colspan="3" class="fw-semibold text-end">Daily Allocation Expenses :</td>
                            <td class="text-end fw-bold text-dark" id="footDailyExpensesSubtotal">₹0</td>
                            <td class="text-center"><span class="badge bg-success-subtle text-success">Daily Synced</span></td>
                        </tr>
                        <tr class="text-muted small">
                            <td colspan="3" class="fw-semibold text-end">Additional Weekly Expenses :</td>
                            <td class="text-end fw-bold text-warning" id="totalWeeklyExtraBadge">₹0</td>
                            <td class="text-center"><span class="badge bg-warning-subtle text-warning">Manual</span></td>
                        </tr>
                        <tr class="table-warning fw-bold">
                            <td colspan="3" class="fw-bold text-end">Total Combined Site Expenses :</td>
                            <td class="text-end fw-bold text-dark fs-6" id="totalCombinedExpensesBadge">₹0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
</div>
</div>

        <!-- 5. Weekly Net Financial Settlement Summary -->
        <div class="row g-3 g-md-4 mb-3 mb-md-4">
            <div class="col-12 col-md-3">
                <div class="card-stat bg-white h-100 border">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small fw-semibold">1. Cumulative Labour Wages</span>
                        <i class="bi bi-lock-fill text-muted" title="Read Only from Daily Allocation"></i>
</div>
                    <h3 class="fw-bold text-dark mb-1 mt-2" id="sumLabourWages">₹0</h3>
                    <small class="text-muted">From Daily Allocation</small>
</div>
</div>

            <div class="col-12 col-md-3">
                <div class="card-stat bg-white h-100 border">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small fw-semibold">2. Daily Site Expenses</span>
                        <i class="bi bi-lock-fill text-muted" title="Read Only from Daily Allocation"></i>
</div>
                    <h3 class="fw-bold text-secondary mb-1 mt-2" id="sumDailyExpenses">₹0</h3>
                    <small class="text-muted">Daily water cans, tea & petty</small>
</div>
</div>

            <div class="col-12 col-md-3">
                <div class="card-stat bg-white h-100 border border-warning">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small fw-semibold">3. Additional Weekly Expenses</span>
                        <span class="badge bg-warning text-dark"><i class="bi bi-pencil-fill"></i> Added</span>
</div>
                    <h3 class="fw-bold text-warning mb-1 mt-2" id="sumWeeklyExpenses">₹0</h3>
                    <small class="text-muted">Added directly on this page</small>
</div>
</div>

            <div class="col-12 col-md-3">
                <div class="card-stat bg-primary text-white h-100"
                    style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%) !important;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-white-50 small fw-semibold">Net Weekly Settlement Total</span>
                        <i class="bi bi-wallet2 text-white-50"></i>
</div>
                    <h3 class="fw-bold text-white mb-1 mt-2" id="sumNetWeeklyPayout">₹0</h3>
                    <small class="text-white-50">Total wage & site payout</small>
</div>
</div>
</div>

        <!-- Action Settlement Bar -->
        <div
            class="card-custom p-3 p-md-4 bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <strong class="text-dark d-block">Ready to settle weekly wages for <span id="settleSiteName">Project
                        Site</span>?</strong>
                <small class="text-muted">Approving will confirm the ₹<span id="settleTotalText">0</span> payout for this
                    weekly period.</small>
</div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ url('/daily-allocation') }}"
                    class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Daily Allocation</span>
</a>
                <button type="button" class="btn btn-deiveni btn-sm d-flex align-items-center gap-1" id="btnFinalApprove"
                    title="Approve & Settle Week">
                    <i class="bi bi-check2-circle fs-6"></i>
                    <span>Approve & Settle Week</span>
                </button>
</div>
</div>

</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            const ROUTE_GET_WEEKLY_DATA = "{{ route('weekly-wages.data') }}";
            const ROUTE_SAVE_WEEKLY_EXPENSES = "{{ route('weekly-wages.expenses') }}";
            const ROUTE_SETTLE_WEEKLY = "{{ route('weekly-wages.settle') }}";

            // Local Date String (YYYY-MM-DD) avoiding timezone shifts
            function getLocalDateString(d = new Date()) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Calculate Monday to Sunday date range for any date or offset
            function getWeekRangeForDate(targetDate) {
                let d;
                if (typeof targetDate === 'string') {
                    const parts = targetDate.split('-');
                    if (parts.length === 3) {
                        d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    } else {
                        d = new Date(targetDate);
                    }
                } else if (targetDate instanceof Date) {
                    d = new Date(targetDate.getFullYear(), targetDate.getMonth(), targetDate.getDate());
                } else {
                    d = new Date();
                }

                const day = d.getDay(); // 0 is Sunday, 1 is Monday, ..., 6 is Saturday
                const diffToMonday = (day === 0 ? -6 : 1 - day);

                const monday = new Date(d);
                monday.setDate(d.getDate() + diffToMonday);
                monday.setHours(0, 0, 0, 0);

                const saturday = new Date(monday);
                saturday.setDate(monday.getDate() + 5);
                saturday.setHours(23, 59, 59, 999);

                const sunday = new Date(monday);
                sunday.setDate(monday.getDate() + 6);
                sunday.setHours(23, 59, 59, 999);

                return {
                    monday,
                    saturday,
                    sunday,
                    mondayStr: getLocalDateString(monday),
                    saturdayStr: getLocalDateString(saturday),
                    sundayStr: getLocalDateString(sunday)
                };
            }

            // Calculate Monday to Sunday date range for any week offset relative to reference date
            function getWeekRangeByOffset(offsetWeeks = 0, refDate = new Date()) {
                const ref = new Date(refDate);
                ref.setDate(ref.getDate() + (offsetWeeks * 7));
                return getWeekRangeForDate(ref);
            }

            const ROUTE_QUICK_ADD_MATERIAL = "{{ route('daily-allocation.quick-add-material') }}";
            let availableMaterials = @json($materials ?? []);

            function escapeHtml(str) {
                return String(str ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function createWeeklyExpenseRowHtml(e = null) {
                const titleVal = e ? (e.title || '') : '';
                const qty = e ? (parseFloat(e.qty) || 1) : 1;
                const unitPrice = e ? (parseFloat(e.unitPrice) || 0) : '';
                const cost = e ? Math.round(e.cost || (qty * (parseFloat(e.unitPrice) || 0))) : 0;

                return `
                    <tr class="weekly-exp-row align-middle">
                        <td>
                            <div class="position-relative expense-autocomplete-wrapper">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control form-control-sm weekly-exp-title"
                                        value="${escapeHtml(titleVal)}"
                                        placeholder="Search Material Master or type new..."
                                        autocomplete="off">
                                </div>
                                <div class="expense-suggestions-dropdown shadow rounded border d-none"
                                    style="background: #fff; max-height: 220px; overflow-y: auto;"></div>
                            </div>
                        </td>
                        <td>
                            <input type="number" min="0" step="any" class="form-control form-control-sm text-center weekly-exp-qty" value="${qty}" placeholder="Qty">
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" min="0" step="any" class="form-control text-end fw-semibold weekly-exp-unit-price" value="${unitPrice}" placeholder="Price">
                            </div>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-dark fs-6 weekly-exp-subtotal-text">₹${cost.toLocaleString('en-IN')}</span>
                            <input type="hidden" class="weekly-exp-cost" value="${cost}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-weekly-exp-btn" title="Remove"><i class="bi bi-trash3 fs-5"></i></button>
                        </td>
                    </tr>
                `;
            }

            function setupWeeklyExpenseAutocomplete(wrapper) {
                const $wrap = $(wrapper);
                const $input = $wrap.find('.weekly-exp-title');
                const $dropdown = $wrap.find('.expense-suggestions-dropdown');

                function positionDropdown() {
                    if (!$input.length) return;
                    const rect = $input[0].getBoundingClientRect();
                    $dropdown.css({
                        position: 'fixed',
                        top: (rect.bottom + 2) + 'px',
                        left: rect.left + 'px',
                        width: Math.max(rect.width, 320) + 'px',
                        zIndex: 99999
                    });
                }

                function renderMatches(q) {
                    const query = (q || '').toLowerCase().trim();
                    let matches = availableMaterials;
                    if (query) {
                        matches = availableMaterials.filter(function (m) {
                            const en = (m.name_en || '').toLowerCase();
                            const ta = (m.name_ta || '').toLowerCase();
                            return en.includes(query) || ta.includes(query);
                        });
                    }

                    const trimmedQ = (q || '').trim();

                    if (!matches || matches.length === 0) {
                        let emptyHtml = `
                            <div class="p-2 small text-muted text-center">
                                <i class="bi bi-info-circle me-1"></i>No matching materials found in Master.
                            </div>
                        `;
                        if (trimmedQ) {
                            emptyHtml += `
                                <div class="border-top p-1 bg-light">
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 text-start quick-add-material-btn d-flex align-items-center justify-content-between py-1 px-2"
                                        data-name="${escapeHtml(trimmedQ)}">
                                        <span class="text-truncate small"><i class="bi bi-plus-circle-fill text-success me-1"></i>Add "<strong>${escapeHtml(trimmedQ)}</strong>" to Master</span>
                                        <span class="badge bg-primary-subtle text-primary ms-1" style="font-size:0.65rem;">+ Add Master</span>
                                    </button>
                                </div>
                            `;
                        }
                        $dropdown.html(emptyHtml).removeClass('d-none');
                        positionDropdown();
                        return;
                    }

                    let html = '<div class="py-1">';
                    html += '<div class="px-2 py-1 small fw-bold text-uppercase text-muted" style="font-size:0.7rem; background:#f8fafc;"><i class="bi bi-box-seam me-1"></i>Materials Master</div>';
                    matches.slice(0, 20).forEach(function (m) {
                        const unitName = (m.unit && typeof m.unit === 'object') ? (m.unit.name_en || m.unit.code || '') : (m.unit || '');
                        const unitBadge = unitName ? `<span class="badge bg-light text-secondary border ms-1" style="font-size:0.7rem;">${escapeHtml(unitName)}</span>` : '';
                        const taText = m.name_ta ? `<span class="text-muted ms-1">(${escapeHtml(m.name_ta)})</span>` : '';
                        html += `
                            <a href="javascript:void(0)" class="dropdown-item py-1 px-2 d-flex justify-content-between align-items-center expense-sugg-item"
                                data-en="${escapeHtml(m.name_en)}" data-ta="${escapeHtml(m.name_ta || '')}">
                                <div class="text-truncate me-2">
                                    <span class="fw-semibold text-dark">${escapeHtml(m.name_en)}</span>
                                    ${taText}
                                </div>
                                ${unitBadge}
                            </a>
                        `;
                    });

                    const hasExactMatch = matches.some(function (m) {
                        return (m.name_en || '').toLowerCase() === query || (m.name_ta || '').toLowerCase() === query;
                    });

                    if (trimmedQ && !hasExactMatch) {
                        html += `
                            <div class="border-top p-1 bg-light">
                                <button type="button" class="btn btn-sm btn-link text-primary w-100 text-start text-decoration-none py-1 px-2 quick-add-material-btn d-flex align-items-center justify-content-between"
                                    data-name="${escapeHtml(trimmedQ)}">
                                    <span class="text-truncate small"><i class="bi bi-plus-circle text-success me-1"></i>Add "<strong>${escapeHtml(trimmedQ)}</strong>" to Materials Master</span>
                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.65rem;">New</span>
                                </button>
                            </div>
                        `;
                    }

                    html += '</div>';
                    $dropdown.html(html).removeClass('d-none');
                    positionDropdown();
                }

                $input.on('focus input', function () {
                    renderMatches($(this).val());
                    $(window).on('scroll.weeklyExpAutoc resize.weeklyExpAutoc', positionDropdown);
                });

                $(document).on('click', function (e) {
                    if (!$(e.target).closest($wrap).length && !$(e.target).closest($dropdown).length) {
                        $dropdown.addClass('d-none');
                        $(window).off('scroll.weeklyExpAutoc resize.weeklyExpAutoc');
                    }
                });

                $dropdown.on('click', '.expense-sugg-item', function () {
                    const en = $(this).data('en');
                    const ta = $(this).data('ta');
                    const display = ta ? `${en} - ${ta}` : en;
                    $input.val(display);
                    $dropdown.addClass('d-none');
                    $(window).off('scroll.weeklyExpAutoc resize.weeklyExpAutoc');
                    recalculateWeeklySheet();
                    autoSaveWeeklyExpenses();
                });

                $dropdown.on('click', '.quick-add-material-btn', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const name = $(this).data('name');
                    if (!name) return;

                    const $btn = $(this);
                    $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Adding to Master...');

                    $.ajax({
                        url: ROUTE_QUICK_ADD_MATERIAL,
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ name: name }),
                        success: function (res) {
                            if (res.success && res.material) {
                                const exists = availableMaterials.some(m => m.id === res.material.id);
                                if (!exists) {
                                    availableMaterials.unshift(res.material);
                                }
                                const display = res.material.name_ta && res.material.name_ta !== res.material.name_en
                                    ? `${res.material.name_en} - ${res.material.name_ta}`
                                    : res.material.name_en;
                                $input.val(display);
                                $dropdown.addClass('d-none');
                                $(window).off('scroll.weeklyExpAutoc resize.weeklyExpAutoc');
                                recalculateWeeklySheet();
                                autoSaveWeeklyExpenses();
                                showToast(`Material "${res.material.name_en}" added into Materials Master!`, 'success');
                            }
                        },
                        error: function (xhr) {
                            $btn.prop('disabled', false).html('<i class="bi bi-plus-circle text-danger me-1"></i> Try Again');
                            const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add material.';
                            showToast(msg, 'danger');
                        }
                    });
                });
            }

            const now = new Date();
            const todayStr = getLocalDateString(now);
            const currentWeekBounds = getWeekRangeByOffset(0, now);
            const currentWeekKey = `${currentWeekBounds.mondayStr}_${currentWeekBounds.sundayStr}`;

            // Helper to format week option label
            function formatWeekOptionLabel(range, offset = null) {
                const isCurrent = (range.mondayStr === currentWeekBounds.mondayStr);
                const startFormatted = range.monday.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                const endFormatted = range.sunday.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

                if (isCurrent) {
                    return `★ Current Week: ${startFormatted} – ${endFormatted}`;
                } else if (offset === -1) {
                    return `Last Week (1 Wk Ago): ${startFormatted} – ${endFormatted}`;
                } else if (offset === -2) {
                    return `2 Weeks Ago: ${startFormatted} – ${endFormatted}`;
                } else if (offset === -3) {
                    return `3 Weeks Ago: ${startFormatted} – ${endFormatted}`;
                } else if (offset === -4) {
                    return `4 Weeks Ago: ${startFormatted} – ${endFormatted}`;
                } else {
                    const yearSuffix = (range.monday.getFullYear() !== now.getFullYear()) ? ` (${range.monday.getFullYear()})` : '';
                    return `🗓 Week${yearSuffix}: ${startFormatted} – ${endFormatted}`;
                }
            }

            // Dynamically populate Week Cycles (Current week, last 4 weeks, and custom date option)
            function populateWeekCycles() {
                const select = $('#weeklyCycleSelect');
                const previousVal = select.val();
                select.empty();

                // Standard predefined offsets
                const predefinedOffsets = [0, -1, -2, -3, -4];

                predefinedOffsets.forEach(off => {
                    const range = getWeekRangeByOffset(off, now);
                    const key = `${range.mondayStr}_${range.sundayStr}`;
                    const isCurrent = (off === 0);
                    const label = formatWeekOptionLabel(range, off);

                    const isSelected = (previousVal ? previousVal === key : isCurrent);
                    select.append(`<option value="${key}" data-start="${range.mondayStr}" data-end="${range.sundayStr}" data-is-current="${isCurrent ? '1' : '0'}" ${isSelected ? 'selected' : ''}>${label}</option>`);
                });

                // Add Custom Date Selection Trigger Item
                select.append(`<option value="custom_picker_action" class="text-primary fw-bold">📅 Pick Any Date / Year ...</option>`);

                // If previous value was a custom range not in predefined offsets, preserve it
                if (previousVal && previousVal !== 'custom_picker_action' && !select.find(`option[value="${previousVal}"]`).length) {
                    const parts = previousVal.split('_');
                    if (parts.length === 2) {
                        const range = getWeekRangeForDate(parts[0]);
                        const label = formatWeekOptionLabel(range);
                        const optHtml = `<option value="${previousVal}" data-start="${range.mondayStr}" data-end="${range.sundayStr}" data-is-current="0" selected>${label}</option>`;
                        $(optHtml).insertBefore(select.find('option[value="custom_picker_action"]'));
                    }
                }

                // Guarantee default on page load is Current Active Week
                if (!select.val() || select.val() === 'custom_picker_action') {
                    select.val(currentWeekKey);
                }
            }

            // Add or select a week range from any target date (supports past years, custom dates)
            function addOrSelectWeekRange(targetDateInput) {
                const range = getWeekRangeForDate(targetDateInput);
                const key = `${range.mondayStr}_${range.sundayStr}`;
                const select = $('#weeklyCycleSelect');

                let opt = select.find(`option[value="${key}"]`);
                if (opt.length === 0) {
                    const label = formatWeekOptionLabel(range);
                    const isCurrent = (key === currentWeekKey);
                    const optHtml = `<option value="${key}" data-start="${range.mondayStr}" data-end="${range.sundayStr}" data-is-current="${isCurrent ? '1' : '0'}">${label}</option>`;
                    const customActionOpt = select.find('option[value="custom_picker_action"]');
                    if (customActionOpt.length > 0) {
                        $(optHtml).insertBefore(customActionOpt);
                    } else {
                        select.append(optHtml);
                    }
                }

                select.val(key).trigger('change');
                return range;
            }

            // Quick Week Navigation Buttons
            $('#currentWeekQuickBtn').on('click', function () {
                $('#weeklyCycleSelect').val(currentWeekKey).trigger('change');
                showToast('Switched to Current Active Week', 'info');
            });

            // Previous Week: Navigates back 1 week from currently viewed week (no 4-week limit)
            $('#prevWeekBtn').on('click', function () {
                const selectedOption = $('#weeklyCycleSelect option:selected');
                const startStr = selectedOption.data('start');
                if (!startStr) {
                    addOrSelectWeekRange(new Date());
                    return;
                }

                const parts = startStr.split('-');
                const prevMonday = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10) - 7);
                addOrSelectWeekRange(getLocalDateString(prevMonday));
            });

            // Next Week: Navigates forward 1 week from currently viewed week
            $('#nextWeekBtn').on('click', function () {
                const selectedOption = $('#weeklyCycleSelect option:selected');
                const startStr = selectedOption.data('start');
                if (!startStr) {
                    addOrSelectWeekRange(new Date());
                    return;
                }

                const parts = startStr.split('-');
                const nextMonday = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10) + 7);
                addOrSelectWeekRange(getLocalDateString(nextMonday));
            });

            // Toggle Custom Date Section
            $('#customDateToggleBtn').on('click', function () {
                $('#customDateSection').slideToggle(150, function () {
                    if ($(this).is(':visible')) {
                        $('#customWeekDatePicker').focus();
                        updateCustomWeekPreview();
                    }
                });
            });

            $('#closeCustomWeekBtn').on('click', function () {
                $('#customDateSection').slideUp(150);
                if ($('#weeklyCycleSelect').val() === 'custom_picker_action') {
                    $('#weeklyCycleSelect').val(currentWeekKey).trigger('change');
                }
            });

            function updateCustomWeekPreview() {
                const val = $('#customWeekDatePicker').val();
                if (!val) {
                    $('#customWeekPreviewText').text('');
                    return;
                }
                const range = getWeekRangeForDate(val);
                const startFormatted = range.monday.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
                const endFormatted = range.sunday.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                $('#customWeekPreviewText').html(`Week Cycle: <strong class="text-primary">${startFormatted} – ${endFormatted}</strong>`);
            }

            $('#customWeekDatePicker').on('input change', updateCustomWeekPreview);

            $('#loadCustomWeekBtn').on('click', function () {
                const dateVal = $('#customWeekDatePicker').val();
                if (!dateVal) {
                    showToast('Please select a valid date', 'warning');
                    return;
                }
                const range = addOrSelectWeekRange(dateVal);
                $('#customDateSection').slideUp(150);
                showToast(`Loaded week cycle for ${dateVal}`, 'success');
            });

            // Render Day-by-Day sync rows from Daily Allocation (from DB via AJAX)
            function renderWeeklySyncTable() {
                const siteId = $('#weeklySiteSelect').val();
                const siteName = $('#weeklySiteSelect option:selected').data('name') || $('#weeklySiteSelect option:selected').text();
                $('#siteTitleDisplay, #settleSiteName').text(siteName);

                if (!siteId) return;

                const selectedOption = $('#weeklyCycleSelect option:selected');
                const isCurrent = (selectedOption.data('is-current') === 1 || selectedOption.data('is-current') === '1');

                const startDate = selectedOption.data('start');
                const endDate = selectedOption.data('end');

                if (!startDate || !endDate) return;

                updateSettleButtonState(startDate);

                // Update Badges and labels according to active cycle
                const startObj = new Date(startDate + 'T00:00:00');
                const endObj = new Date(endDate + 'T00:00:00');
                const rangeStr = `${startObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' })} – ${endObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}`;

                if (isCurrent) {
                    $('#activeWeekBadge')
                        .removeClass('bg-secondary-subtle text-secondary bg-dark-subtle text-dark')
                        .addClass('bg-primary-subtle text-primary')
                        .text('Current Week Active');
                    $('#tableWeekCycleBadge')
                        .removeClass('bg-secondary bg-dark')
                        .addClass('bg-primary')
                        .text('Current Week');
                    $('#tableWeekRangeText').text(`Showing daily allocations strictly for Current Week (${rangeStr})`);
                    $('#weekRangeDisplayBadge').text(rangeStr);
                } else {
                    const isDiffYear = (startObj.getFullYear() !== now.getFullYear());
                    const badgeTitle = isDiffYear ? `Week (${startObj.getFullYear()})` : 'Past Week';
                    $('#activeWeekBadge')
                        .removeClass('bg-primary-subtle text-primary bg-dark-subtle text-dark')
                        .addClass('bg-secondary-subtle text-secondary')
                        .text(badgeTitle);
                    $('#tableWeekCycleBadge')
                        .removeClass('bg-primary bg-dark')
                        .addClass('bg-secondary')
                        .text(badgeTitle);
                    $('#tableWeekRangeText').text(`Showing daily allocations strictly for Week Cycle: ${rangeStr}`);
                    $('#weekRangeDisplayBadge').text(rangeStr);
                }

                $.get(ROUTE_GET_WEEKLY_DATA, {
                    project_site_id: siteId,
                    start_date: startDate,
                    end_date: endDate
                }, function (response) {
                    if (!response.success) return;

                    const data = response.data;
                    const days = data.days || [];
                    const categories = data.categories || [];
                    const summary = data.summary || {};

                    const tbody = $('#weeklyDaysTbody');
                    tbody.empty();

                    let recordedDaysCount = 0;
                    let totalLabourCount = 0;
                    let totalLabourWages = 0;
                    let totalDailyExpenses = 0;

                    const categoryCounts = {};
                    const categoryNames = {};
                    categories.forEach(cat => {
                        categoryCounts[cat.code] = 0;
                        categoryNames[cat.code] = cat.name_ta || cat.name_en || 'Trade';
                    });

                    const todayStr = '{{ date('Y-m-d') }}';

                    days.forEach(function (day) {
                        const dayOfWeek = day.day_of_week;
                        const dateFormatted = day.date_formatted;
                        const dayDateStr = day.date;
                        const isSunday = day.is_sunday;

                        if (day.has_data) {
                            recordedDaysCount++;
                            const dayLabourCount = parseInt(day.total_labour_count) || 0;
                            const dayLabourWage = parseFloat(day.total_labour_wage) || 0;
                            const dayExpenses = parseFloat(day.total_expenses) || 0;
                            const dayGrandTotal = parseFloat(day.grand_total) || (dayLabourWage + dayExpenses);

                            totalLabourCount += dayLabourCount;
                            totalLabourWages += dayLabourWage;
                            totalDailyExpenses += dayExpenses;

                            // Build worker pills
                            let workerPills = '';
                            if (Array.isArray(day.workers) && day.workers.length > 0) {
                                day.workers.forEach(function (w) {
                                    const cnt = parseInt(w.count) || 0;
                                    if (cnt > 0) {
                                        const cCode = w.code || w.cat || ('CAT-' + (w.categoryId || w.nameTa));
                                        categoryCounts[cCode] = (categoryCounts[cCode] || 0) + cnt;
                                        if (!categoryNames[cCode]) {
                                            categoryNames[cCode] = w.nameTa || w.nameEn || 'Trade';
                                        }
                                        const wName = w.nameTa || w.nameEn || 'Worker';
                                        workerPills += `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 mb-1 fw-semibold">${cnt} ${wName}</span>`;
                                    }
                                });
                            }

                            // Build expense pills
                            let expensePills = '';
                            if (Array.isArray(day.expenses) && day.expenses.length > 0) {
                                day.expenses.forEach(function (e) {
                                    const eAmount = Math.round(parseFloat(e.amount) || 0);
                                    expensePills += `<span class="badge bg-warning-subtle text-dark border border-warning-subtle me-1 mb-1 fw-semibold" title="${e.qty} @ ₹${e.unitPrice}"><i class="bi bi-tag-fill text-warning me-1"></i>${e.title}: ₹${eAmount.toLocaleString('en-IN')}</span>`;
                                });
                            }

                            if (!workerPills) {
                                workerPills = '<span class="text-muted small">No labour allocated</span>';
                            }

                            const rowHtml = `
                                <tr class="day-record-row align-middle">
                                    <td class="text-start">
                                        <strong class="text-dark d-block">${dayOfWeek}, ${dateFormatted}</strong>
                                        <small class="text-muted">${dayDateStr}</small>
                                    </td>
                                    <td class="text-start">
                                        <div class="d-flex flex-wrap align-items-center mb-1">
                                            ${workerPills}
</div>
                                        ${expensePills ? `<div class="d-flex flex-wrap align-items-center mt-1 pt-1 border-top border-light">${expensePills}</div>` : ''}
                                        <div class="small text-muted" style="font-size: 0.75rem;">Recorded via Daily Allocation</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border fw-bold px-2 py-1">${dayLabourCount} Workers</span>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">₹${Math.round(dayLabourWage).toLocaleString('en-IN')}</td>
                                    <td class="text-end text-muted">₹${Math.round(dayExpenses).toLocaleString('en-IN')}</td>
                                    <td class="text-end fw-bold text-dark">₹${Math.round(dayGrandTotal).toLocaleString('en-IN')}</td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Synced</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ url('/daily-allocation') }}?site_id=${siteId}&date=${dayDateStr}" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 0.75rem;" title="View in Daily Allocation">
                                            <i class="bi bi-eye me-1"></i>View
</a>
                                    </td>
                                </tr>
                            `;
                            tbody.append(rowHtml);
                        } else {
                            let categoryBreakdownHtml = '';
                            let statusBadge = '';
                            let actionHtml = '—';

                            if (isSunday) {
                                categoryBreakdownHtml = `
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Sunday Holiday</span>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">Site holiday / No labour</div>
                                `;
                                statusBadge = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Holiday</span>`;
                                actionHtml = `<span class="text-muted">—</span>`;
                            } else {
                                categoryBreakdownHtml = `
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-person-x me-1"></i>No Labour</span>
                                    <div class="small text-muted mt-1" style="font-size: 0.75rem;">Not allocated</div>
                                `;
                                statusBadge = `<span class="badge bg-light text-secondary border">No Labour</span>`;
                                if (dayDateStr <= todayStr) {
                                    actionHtml = `<a href="{{ url('/daily-allocation') }}?site_id=${siteId}&date=${dayDateStr}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;"><i class="bi bi-plus me-1"></i>Add</a>`;
                                } else {
                                    actionHtml = `<span class="text-muted">—</span>`;
                                }
                            }

                            const rowHtml = `
                                <tr class="day-record-row bg-light bg-opacity-50 align-middle">
                                    <td class="text-start">
                                        <strong class="text-dark d-block">${dayOfWeek}, ${dateFormatted}</strong>
                                        <small class="text-muted">${dayDateStr}</small>
                                    </td>
                                    <td class="text-start">
                                        ${categoryBreakdownHtml}
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted small">0 Workers</span>
                                    </td>
                                    <td class="text-end text-muted">₹0</td>
                                    <td class="text-end text-muted">₹0</td>
                                    <td class="text-end text-muted">₹0</td>
                                    <td class="text-center">${statusBadge}</td>
                                    <td class="text-center">${actionHtml}</td>
                                </tr>
                            `;
                            tbody.append(rowHtml);
                        }
                    });

                    const dailyCumulative = totalLabourWages + totalDailyExpenses;

                    $('#footRecordedDaysCountText').text(`${recordedDaysCount} of ${days.length} Days Recorded`);
                    $('#footTotalLabourManDays').text(`${totalLabourCount} Man-Days`);
                    $('#footTotalLabourWages').text('₹' + Math.round(totalLabourWages).toLocaleString('en-IN'));
                    $('#footTotalDailyExpenses').text('₹' + Math.round(totalDailyExpenses).toLocaleString('en-IN'));
                    $('#footTotalDailyCumulative').text('₹' + Math.round(dailyCumulative).toLocaleString('en-IN'));
                    $('#footRecordedDaysCount').text(`${recordedDaysCount} of ${days.length} Days Recorded`);

                    // Populate Table 2: Category Salary Calculation Table 
                    const catSalaryTbody = $('#weeklyCategorySalaryTbody');
                    catSalaryTbody.empty();

                    let calcTotalWorkers = 0;
                    let calcTotalSalary = 0;

                    categories.forEach(function (cat) {
                        const catWorkers = categoryCounts[cat.code] !== undefined ? categoryCounts[cat.code] : (cat.total_workers || 0);
                        const rate = parseFloat(cat.rate) || 0;
                        const catSalary = Math.round(catWorkers * rate);

                        calcTotalWorkers += catWorkers;
                        calcTotalSalary += catSalary;

                        let amPaidBadge = '';
                        if (cat.is_am_paid) {
                            let sourceTag = '';
                            if (cat.am_project_amount > 0 && cat.am_office_amount > 0) {
                                sourceTag = ` (Office: ₹${Math.round(cat.am_office_amount).toLocaleString('en-IN')} | Project: ₹${Math.round(cat.am_project_amount).toLocaleString('en-IN')})`;
                            } else if (cat.am_project_amount > 0) {
                                sourceTag = ' [Project Cash]';
                            } else {
                                sourceTag = ' [Office Cash]';
                            }
                            amPaidBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle small ms-2" title="Paid by Account Manager"><i class="bi bi-person-check me-1"></i>Paid by AM: ₹${Math.round(cat.am_paid_amount || 0).toLocaleString('en-IN')}${sourceTag}</span>`;
                        }

                        const row = `
                            <tr>
                                <td class="text-start">
                                    <strong class="text-dark fs-6">${cat.name_ta}</strong>
                                    ${cat.name_en ? `<span class="text-muted small ms-1">(${cat.name_en})</span>` : ''}
                                    ${amPaidBadge}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1 fw-bold">
                                        ${catWorkers} Workers
</span>
                                </td>
                                <td class="text-end fw-semibold text-dark fs-6">
                                    ₹${Math.round(rate).toLocaleString('en-IN')} <small class="text-muted">/ Day</small>
                                </td>
                                <td class="text-end fw-bold text-success fs-6">
                                    ₹${catSalary.toLocaleString('en-IN')}
                                </td>
                            </tr>
                        `;
                        catSalaryTbody.append(row);
                    });

                    if (categories.length === 0) {
                        catSalaryTbody.append(`
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    No labour categories recorded for this week.
                                </td>
                            </tr>
                        `);
                    }

                    $('#catSalaryTotalWorkers').text(`${calcTotalWorkers} Workers`);
                    $('#catSalaryTotalAmount').text(`₹${calcTotalSalary.toLocaleString('en-IN')}`);
                    $('#badgeTotalLabourWages').text(`₹${calcTotalSalary.toLocaleString('en-IN')}`);

                    // Cache daily expenses and labour salary on table element for recalculation
                    $('#weeklyWageDisplayTable').data('daily-expenses', totalDailyExpenses);
                    $('#weeklyWageDisplayTable').data('labour-salary', calcTotalSalary);
                    $('#weeklyWageDisplayTable').data('am-wages', (data.summary && data.summary.total_am_wages) ? parseFloat(data.summary.total_am_wages) : 0);

                    // Populate Expenses Table: Both Synced Daily Expenses and Additional Weekly Expenses
                    const weeklyExpensesTbody = $('#weeklyExpensesBody');
                    weeklyExpensesTbody.empty();

                    const dailyExpenses = data.daily_expenses || [];
                    const weeklyExpenses = data.additional_expenses || [];
                    let hasAnyExpenses = false;

                    // 1. Render Synced Daily Allocation Expenses
                    if (dailyExpenses.length > 0) {
                        hasAnyExpenses = true;
                        dailyExpenses.forEach(function (de) {
                            const qty = parseFloat(de.qty) || 1;
                            const unitPrice = parseFloat(de.unitPrice) || 0;
                            const cost = Math.round(de.cost || (qty * unitPrice));
                            const row = `
                                <tr class="daily-synced-exp-row table-light align-middle">
                                    <td class="text-start">
                                        <div class="fw-semibold text-dark">${de.title || 'Daily Expense'}</div>
                                        <small class="text-muted"><i class="bi bi-calendar-check text-success me-1"></i>Daily Allocation: ${de.date_formatted || de.date}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2 py-1">${qty}</span>
                                    </td>
                                    <td class="text-end text-muted">
                                        ₹${unitPrice.toLocaleString('en-IN')}
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-dark fs-6">₹${cost.toLocaleString('en-IN')}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2" title="Automatically synced from Daily Allocation">
                                            <i class="bi bi-check2-circle me-1"></i>Daily Synced
</span>
                                    </td>
                                </tr>
                            `;
                            weeklyExpensesTbody.append(row);
                        });
                    }

                    // 2. Render Editable Additional Weekly Expenses
                    if (weeklyExpenses.length > 0) {
                        hasAnyExpenses = true;
                        weeklyExpenses.forEach(function (e) {
                            const $row = $(createWeeklyExpenseRowHtml(e));
                            weeklyExpensesTbody.append($row);
                            setupWeeklyExpenseAutocomplete($row.find('.expense-autocomplete-wrapper'));
                        });
                    }

                    if (!hasAnyExpenses) {
                        weeklyExpensesTbody.append(`
                            <tr class="empty-exp-placeholder">
                                <td colspan="5" class="text-center text-muted py-3 small">
                                    <i class="bi bi-inbox fs-4 d-block mb-1 text-muted"></i>
                                    No site expenses recorded for this week yet. Click <strong>+ Add Expense</strong> above if you have any additional weekly expenses.
                                </td>
                            </tr>
                        `);
                    }

                    recalculateWeeklySheet();
                }).fail(function () {
                    showToast('Failed to load weekly wage data from server.', 'danger');
                });
            }

            // Collect current weekly expense items
            function getWeeklyExpenseRows() {
                let items = [];
                $('.weekly-exp-row').each(function () {
                    const title = $(this).find('.weekly-exp-title').val().trim();
                    const qty = parseFloat($(this).find('.weekly-exp-qty').val()) || 0;
                    const unitPrice = parseFloat($(this).find('.weekly-exp-unit-price').val()) || 0;
                    const amount = Math.round(qty * unitPrice);

                    if (title || amount > 0) {
                        items.push({
                            item_name: title,
                            quantity: qty,
                            unit_price: unitPrice,
                            amount: amount
                        });
                    }
                });
                return items;
            }

            // Save weekly expenses to DB via AJAX debounce
            let saveExpensesTimeout = null;
            function autoSaveWeeklyExpenses() {
                clearTimeout(saveExpensesTimeout);
                saveExpensesTimeout = setTimeout(function () {
                    const siteId = $('#weeklySiteSelect').val();
                    const selectedOption = $('#weeklyCycleSelect option:selected');
                    const startDate = selectedOption.data('start');
                    const endDate = selectedOption.data('end');

                    if (!siteId || !startDate || !endDate) return;

                    const items = getWeeklyExpenseRows();

                    $.ajax({
                        url: ROUTE_SAVE_WEEKLY_EXPENSES,
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({
                            project_site_id: siteId,
                            start_date: startDate,
                            end_date: endDate,
                            expenses: items
                        })
                    });
                }, 800);
            }

            // Recalculate Weekly Totals
            function recalculateWeeklySheet() {
                const totalLabourWages = parseFloat($('#weeklyWageDisplayTable').data('labour-salary')) || 0;
                const totalAmWages = parseFloat($('#weeklyWageDisplayTable').data('am-wages')) || 0;
                const totalDailyExpenses = parseFloat($('#weeklyWageDisplayTable').data('daily-expenses')) || 0;

                let totalWeeklyExtra = 0;
                $('.weekly-exp-row').each(function () {
                    const qty = parseFloat($(this).find('.weekly-exp-qty').val()) || 0;
                    const unitPrice = parseFloat($(this).find('.weekly-exp-unit-price').val()) || 0;
                    const cost = Math.round(qty * unitPrice);

                    $(this).find('.weekly-exp-cost').val(cost);
                    $(this).find('.weekly-exp-subtotal-text').text('₹' + cost.toLocaleString('en-IN'));
                    totalWeeklyExtra += cost;
                });

                const netLabourWages = Math.max(0, totalLabourWages - totalAmWages);
                const grandWeeklyPayout = netLabourWages + totalDailyExpenses + totalWeeklyExtra;
                const totalCombinedExp = totalDailyExpenses + totalWeeklyExtra;

                // Update Table Footers
                $('#footDailyExpensesSubtotal').text('₹' + Math.round(totalDailyExpenses).toLocaleString('en-IN'));
                $('#totalWeeklyExtraBadge').text('₹' + Math.round(totalWeeklyExtra).toLocaleString('en-IN'));
                $('#totalCombinedExpensesBadge').text('₹' + Math.round(totalCombinedExp).toLocaleString('en-IN'));

                // Update Settlement KPI Cards
                if (totalAmWages > 0) {
                    $('#sumLabourWages').html(`₹${Math.round(netLabourWages).toLocaleString('en-IN')} <div class="text-muted" style="font-size:0.75rem;">(Gross: ₹${Math.round(totalLabourWages).toLocaleString('en-IN')} - AM: ₹${Math.round(totalAmWages).toLocaleString('en-IN')})</div>`);
                } else {
                    $('#sumLabourWages').text('₹' + Math.round(totalLabourWages).toLocaleString('en-IN'));
                }
                $('#sumDailyExpenses').text('₹' + Math.round(totalDailyExpenses).toLocaleString('en-IN'));
                $('#sumWeeklyExpenses').text('₹' + Math.round(totalWeeklyExtra).toLocaleString('en-IN'));
                $('#sumNetWeeklyPayout').text('₹' + Math.round(grandWeeklyPayout).toLocaleString('en-IN'));
                $('#settleTotalText').text(Math.round(grandWeeklyPayout).toLocaleString('en-IN'));
            }

            // Input listener on editable weekly expense rows
            $(document).on('input', '.weekly-exp-qty, .weekly-exp-unit-price, .weekly-exp-title', function () {
                recalculateWeeklySheet();
                autoSaveWeeklyExpenses();
            });

            // Add Custom Weekly Expense Row
            $('#addWeeklyExpenseBtn').on('click', function () {
                $('.empty-exp-placeholder').remove();
                const $newRow = $(createWeeklyExpenseRowHtml());
                $('#weeklyExpensesBody').append($newRow);
                setupWeeklyExpenseAutocomplete($newRow.find('.expense-autocomplete-wrapper'));
                $newRow.find('.weekly-exp-title').focus();
                recalculateWeeklySheet();
                autoSaveWeeklyExpenses();
                showToast('Added weekly expense row', 'info');
            });

            // Remove Weekly Expense Row
            $(document).on('click', '.remove-weekly-exp-btn', function () {
                $(this).closest('.weekly-exp-row').remove();
                if ($('#weeklyExpensesBody tr').length === 0) {
                    $('#weeklyExpensesBody').append(`
                        <tr class="empty-exp-placeholder">
                            <td colspan="5" class="text-center text-muted py-3 small">
                                <i class="bi bi-inbox fs-4 d-block mb-1 text-muted"></i>
                                No site expenses recorded for this week yet. Click <strong>+ Add Expense</strong> above if you have any additional weekly expenses.
                            </td>
                        </tr>
                    `);
                }
                recalculateWeeklySheet();
                autoSaveWeeklyExpenses();
                showToast('Weekly expense item removed', 'info');
            });

            // Site or cycle Change Handler
            $('#weeklySiteSelect').on('change', function () {
                renderWeeklySyncTable();
            });

            $('#weeklyCycleSelect').on('change', function () {
                const val = $(this).val();
                if (val === 'custom_picker_action') {
                    $('#customDateSection').slideDown(150, function () {
                        $('#customWeekDatePicker').focus();
                        updateCustomWeekPreview();
                    });
                    return;
                }
                renderWeeklySyncTable();
            });

            // Helper to determine if a selected week cycle is completed (Saturday reached or passed)
            function canSettleWeek(startDateStr) {
                if (!startDateStr) return false;
                const startObj = new Date(startDateStr + 'T00:00:00');
                const saturdayObj = new Date(startObj);
                saturdayObj.setDate(startObj.getDate() + 5);
                const saturdayStr = getLocalDateString(saturdayObj);
                return todayStr >= saturdayStr;
            }

            function updateSettleButtonState(startDateStr) {
                const canSettle = canSettleWeek(startDateStr);
                const btns = $('#saveWeeklySettlementBtn, #btnFinalApprove');
                if (canSettle) {
                    btns.prop('disabled', false).attr('title', 'Approve & Settle Weekly Wages');
                } else {
                    const startObj = new Date(startDateStr + 'T00:00:00');
                    const satObj = new Date(startObj);
                    satObj.setDate(startObj.getDate() + 5);
                    const satStr = getLocalDateString(satObj);
                    btns.prop('disabled', true).attr('title', `Available to settle from Saturday (${satStr}) onwards`);
                }
            }

            // Approve & Settle Handler (Persists to database)
            $('#saveWeeklySettlementBtn, #btnFinalApprove').on('click', function () {
                const selectedOption = $('#weeklyCycleSelect option:selected');
                const startDate = selectedOption.data('start');
                const endDate = selectedOption.data('end');

                if (!canSettleWeek(startDate)) {
                    showToast('This week is not completed yet. You can settle it from Saturday onwards.', 'warning');
                    return;
                }

                const siteId = $('#weeklySiteSelect').val();
                const siteName = $('#weeklySiteSelect option:selected').data('name') || $('#weeklySiteSelect option:selected').text();

                if (!siteId || !startDate || !endDate) {
                    showToast('Please select site and week cycle to settle.', 'warning');
                    return;
                }

                const expenses = getWeeklyExpenseRows();
                const totalPayout = $('#sumNetWeeklyPayout').text();

                const btn = $(this);
                btn.prop('disabled', true);

                $.ajax({
                    url: ROUTE_SETTLE_WEEKLY,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        project_site_id: siteId,
                        start_date: startDate,
                        end_date: endDate,
                        expenses: expenses
                    }),
                    success: function (res) {
                        updateSettleButtonState(startDate);
                        showToast(`Weekly Wages settlement of ${totalPayout} for "${siteName}" saved and settled!`, 'success');
                        renderWeeklySyncTable();
                    },
                    error: function (xhr) {
                        updateSettleButtonState(startDate);
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to settle weekly wages.';
                        showToast(msg, 'danger');
                    }
                });
            });

            // Initial render: populate week cycles (Current week default) and render filtered table
            populateWeekCycles();
            renderWeeklySyncTable();
        });
    </script>
@endpush