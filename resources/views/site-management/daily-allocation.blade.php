@extends('layouts.app')

@section('title', 'Daily Allocation')
@section('page_title', 'Daily Allocation ')

@section('content')
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="fw-bold mb-0 text-dark">Daily Labour & Expense Allocation</h5>
                        <span class="badge bg-primary">Daily Entry Flow</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Rolls into Weekly
                            Wages</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-deiveni btn-sm d-flex align-items-center gap-1" id="saveDailyAllocBtn">
                        <i class="bi bi-floppy2-fill"></i>
                        <span>Save Daily Allocation</span>
</button>
</div>
</div>
</div>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <style>
            .week-day-pill {
                border: 1px solid #e2e8f0;
                background: #ffffff;
                border-radius: 8px;
                padding: 6px 12px;
                text-align: center;
                cursor: pointer;
                transition: all 0.2s ease;
                font-size: 0.825rem;
                min-width: 95px;
                user-select: none;
            }

            .week-day-pill:hover:not(.disabled):not(.active) {
                background: #f1f5f9;
                border-color: #cbd5e1;
            }

            .week-day-pill.active {
                background: var(--deiveni-primary, #1e3a8a);
                color: #ffffff !important;
                border-color: var(--deiveni-primary, #1e3a8a);
                font-weight: 600;
                box-shadow: 0 2px 6px rgba(30, 58, 138, 0.25);
            }

            .week-day-pill.active .pill-sub,
            .week-day-pill.active .pill-day {
                color: #ffffff !important;
            }

            .week-day-pill.disabled {
                background: #f8fafc;
                color: #94a3b8 !important;
                cursor: not-allowed;
                opacity: 0.65;
                border-style: dashed;
            }

            .week-day-pill.has-data:not(.active) {
                border-color: #10b981;
                background: #f0fdf4;
            }

            .flatpickr-day.flatpickr-disabled,
            .flatpickr-day.flatpickr-disabled:hover {
                color: #cbd5e1 !important;
                background: #f8fafc !important;
                cursor: not-allowed !important;
            }

            .rate-edited-badge {
                font-size: 0.65rem;
                padding: 0.15rem 0.35rem;
            }
        </style>

        <!-- 1. Date & Project Site Selection Card -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white border-start border-4 border-primary">
            <div class="row g-3 align-items-center">
                <!-- Date Selector -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark mb-1" for="dailyAllocDate">
                        <i class="bi bi-calendar-event text-primary me-1"></i> 1. Entry Date  <span
                            class="text-danger">*</span>
</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-primary"><i class="bi bi-calendar3"></i></span>
                        <input type="text" class="form-control form-control-md fw-bold bg-white" id="dailyAllocDate"
                            placeholder="YYYY-MM-DD" readonly style="cursor: pointer;">
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="dailyAllocDateTodayBtn"
                            title="Select Today">
                            Today
</button>
</div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <small class="text-muted fw-semibold" id="dayNameDisplay">Loading...</small>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                            id="dateStatusBadge">Current Week</span>
</div>
</div>

                <!-- Site Selector -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-bold text-dark mb-1">
                        <i class="bi bi-building-gear text-primary me-1"></i> 2. Project Site  <span
                            class="text-danger">*</span>
</label>
                    <select class="form-select form-select-md fw-bold text-primary border-primary border-opacity-50"
                        id="dailyAllocSiteSelect">
                        @forelse($sites as $site)
                            <option value="{{ $site->id }}" data-name="{{ $site->name }}" {{ $loop->first ? 'selected' : '' }}>
                                {{ $site->name }} &bull; {{ $site->work_type ?? 'Construction' }} ({{ $site->location }})
</option>
                        @empty
                            <option value="" disabled selected>No project sites found (Add sites in Project Sites module)
</option>
                        @endforelse
                    </select>
                </div>
</div>

            <!-- Current Week Day Selector Strip -->
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-bold text-secondary text-uppercase"
                            style="letter-spacing: 0.5px; font-size: 0.75rem;">
                            <i class="bi bi-calendar-week me-1 text-primary"></i> Current Week Working Days 
</span>
                        <span class="badge bg-light text-muted border" id="weekRangeLabel"></span>
</div>
                    <small class="text-muted" style="font-size: 0.78rem;">
                        <i class="bi bi-info-circle me-1 text-primary"></i>Data entry allowed for <strong>Today & Previous
                            Dates</strong>. Future dates and Sundays are disabled.
</small>
</div>
                <div class="d-flex gap-2 flex-wrap" id="currentWeekDaysStrip">
                    <!-- Populated dynamically: Mon to Sun (Sun disabled) -->
</div>
</div>
</div>

        <!-- Read-Only Notice Banner (Shown when selected date is a future date or Sunday) -->
        <div id="readOnlyNoticeBanner"
            class="alert alert-warning border-warning d-none align-items-center justify-content-between py-2 px-3 mb-3 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="bi bi-lock-fill fs-4 text-warning-emphasis me-2"></i>
                <div>
                    <strong class="text-dark">View-Only Mode:</strong>
                    <span class="text-dark small ms-1" id="readOnlyNoticeText">Future date entry is not allowed. Only today
                        and previous dates can be added or edited.</span>
</div>
</div>
            <button type="button" class="btn btn-warning btn-sm text-dark fw-bold ms-2 shadow-sm" id="jumpToTodayBtn">
                <i class="bi bi-arrow-return-left me-1"></i> Go to Today 
</button>
</div>

        <!-- 2. Core Section: Multiple Worker Categories & Headcount Allocation -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white">
            <div
                class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-people-fill text-primary me-2"></i>Worker Categories & Headcount Entry
                    </h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle py-1" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false" id="workersCategoryDropdownBtn">
                            <i class="bi bi-plus-circle me-1"></i> Workers Category
</button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" id="tradeAddDropdown"
                            style="min-width: 320px; max-height: 380px; overflow-y: auto;">
                            <li>
                                <h6 class="dropdown-header text-uppercase small fw-bold text-muted">Select Worker Category
</h6>
                            </li>
                            @forelse($workerCategories as $category)
                                <li>
                                    <a class="dropdown-item add-trade-btn py-2" href="javascript:void(0)"
                                        data-id="{{ $category->id }}" data-cat="{{ $category->code }}"
                                        data-ta="{{ $category->name_ta }}" data-en="{{ $category->name_en }}"
                                        data-rate="{{ (int) $category->daily_wage }}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-dark ta-text">+ {{ $category->name_ta }}</div>
                                                <small class="text-muted">{{ $category->name_en }}</small>
</div>
                                            <span
                                                class="badge bg-light text-dark border ms-2">₹{{ number_format((float) $category->daily_wage) }}</span>
</div>
</a>
                                </li>
                            @empty
                                <li class="px-3 py-2 text-muted small">No active worker categories found</li>
                            @endforelse
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item py-2 text-primary fw-semibold add-other-trade-btn" href="javascript:void(0)" id="addOtherTradeBtn">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary bg-opacity-10 text-primary fw-bold me-2"
                                            style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                            <i class="bi bi-plus-circle-dotted fs-5"></i>
</div>
                                        <div>
                                            <div class="fw-bold text-primary">+ Others</div>
                                            <small class="text-muted fw-normal">Add custom / new worker category</small>
</div>
</div>
</a>
                            </li>
                        </ul>
</div>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-1" id="resetLabourCountsBtn"
                        title="Reset counts to 0">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
</button>
</div>
</div>

            <!-- Worker Category Allocation Table -->
            <div class="table-responsive mb-3">
                <table class="table table-custom table-hover align-middle text-center mb-0" id="workerAllocationTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start" style="min-width: 200px;">Worker Category</th>
                            <th style="min-width: 140px;">Daily Wage Rate</th>
                            <th style="min-width: 180px;">Worker Count</th>
                            <th class="text-end" style="min-width: 140px;">Est. Wage (₹)</th>
                            <th style="width: 60px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="workerAllocationBody">
                        <tr id="emptyWorkerRow">
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-people text-secondary fs-3 d-block mb-1"></i>
                                No worker categories added yet. Click <strong>"Workers Category"</strong> above to add
                                categories.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="text-start">Total Labour Allocated:</td>
                            <td></td>
                            <td>
                                <span class="badge bg-primary fs-6 px-3 py-1" id="totalLabourCountBadge">0 Workers
                                    Present</span>
                            </td>
                            <td class="text-end text-primary fs-6" id="totalLabourWageBadge">₹0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
</div>

            <div class="alert alert-light border py-2 px-3 small d-flex align-items-center justify-content-between mb-0">
                <div class="text-muted">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    Click <strong>"Workers Category"</strong> to add labour categories (Mestri, Mason, Helper, etc.) for
                    today's shift.
</div>
                <span class="fw-semibold text-dark">Active Categories: <span id="activeCategoriesCount"
                        class="text-primary">0 Categories</span></span>
</div>
</div>

        <!-- 3. Core Section: Same-Day Site Expenses & Purchases -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 bg-white border-start border-4 border-warning">
            <div
                class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 pb-2 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-cup-hot-fill text-warning me-2"></i>Same-Day Site Expenses & Purchases
                        </h6>
                        <span class="badge bg-warning text-dark">Same-Day Purchase</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-semibold" id="addCustomExpenseBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Expense
</button>
</div>
</div>

            <!-- Expenses Table -->
            <div class="table-responsive mb-2" id="dailyExpensesTableWrap" style="overflow: visible !important;">
                <table class="table table-custom table-bordered align-middle mb-0" id="dailyExpensesTable">
                    <thead class="bg-light text-muted small">
                        <tr>
                            <th class="text-start" style="min-width: 250px;">Expense Item / Extra Purchase Description
</th>
                            <th style="width: 140px;">Quantity / Hours</th>
                            <th style="width: 160px;">Unit Price</th>
                            <th class="text-end" style="width: 160px;">Final Price (₹)</th>
                            <th style="width: 60px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="dailyExpensesBody">
                        <!-- Empty by default, filled manually by user -->
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="3" class="fw-bold text-end">Total Same-Day Site Expenses:</td>
                            <td class="text-end fw-bold text-warning fs-6" id="totalExpensesBadge">₹0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
</div>
</div>

        <!-- 4. Financial Summary KPI Cards -->
        <div class="row g-3 g-md-4 mb-3 mb-md-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stat bg-white h-100 border">
                    <span class="text-muted small fw-semibold">Total Workers Today</span>
                    <h3 class="fw-bold text-primary mb-1 mt-2" id="statTodayWorkers">0</h3>
                    <small class="text-muted text-truncate d-block" id="statTodayTradesDesc" title="No workers allocated">No workers allocated</small>
</div>
</div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stat bg-white h-100 border">
                    <span class="text-muted small fw-semibold">Labour Wages Total</span>
                    <h3 class="fw-bold text-success mb-1 mt-2" id="statLabourTotal">₹0</h3>
                    <small class="text-muted text-truncate d-block" id="statLabourSubtext" title="Calculated daily wage total">Calculated daily wage total</small>
</div>
</div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stat bg-white h-100 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small fw-semibold">Painter & Electrician (AM Paid)</span>
                        <span id="statAmWageStatusBadge"><span class="badge bg-light text-muted border px-2 py-1">₹0 Paid by AM</span></span>
</div>
                    <h3 class="fw-bold mb-1 mt-2" id="statAmWageTotal">₹0</h3>
                    <small class="text-muted text-truncate d-block" id="statAmWageDesc" title="No Painter & Electrician payment made by Account Manager for this date">No Painter & Electrician payment made by Account Manager for this date</small>
</div>
</div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-stat bg-white h-100 border">
                    <span class="text-muted small fw-semibold">Same-Day Expenses</span>
                    <h3 class="fw-bold text-warning mb-1 mt-2" id="statExpensesTotal">₹0</h3>
                    <small class="text-muted">Water, tea & local purchases</small>
</div>
</div>
</div>

        <!-- 5. Day-by-Day Historical Log / Continuity View -->
        <div class="card-custom p-3 p-md-4 bg-white">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-clock-history text-primary me-2"></i>Recent Daily Allocations for <span
                            id="historySiteTitle">Project Site</span>
                    </h6>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-light text-dark border px-2 py-1 align-self-center" id="historyDaysBadge">0 Days
                        Recorded</span>
</div>
</div>

            <!-- Date Range Filter Toolbar (Task 4) -->
            <div class="bg-light p-3 rounded-3 border mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-lg-auto d-flex align-items-center gap-1">
                        <i class="bi bi-funnel-fill text-primary"></i>
                        <span class="fw-bold text-dark small">Filter Range:</span>
</div>
                    <div class="col-6 col-sm-auto">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">From</span>
                            <input type="date" class="form-control form-control-sm" id="historyFromDate"
                                title="Filter From Date">
</div>
</div>
                    <div class="col-6 col-sm-auto">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted">To</span>
                            <input type="date" class="form-control form-control-sm" id="historyToDate"
                                title="Filter To Date">
</div>
</div>
                    <div class="col-12 col-sm-auto d-flex gap-2 flex-wrap ms-lg-auto">
                        <button type="button" class="btn btn-primary btn-sm px-2 py-1" id="historyFilterThisWeekBtn">
                            <i class="bi bi-calendar-week me-1"></i> Current Week
</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterAllBtn">
                            <i class="bi bi-calendar3 me-1"></i> All Dates
</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                            title="Reset filter to current week">
                            <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
</div>
</div>

            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Date & Day</th>
                            <th>Worker Categories & Count</th>
                            <th class="text-center">Total Labour</th>
                            <th class="text-end">Labour Wages</th>
                            <th class="text-end">Same-Day Expenses</th>
                            <th class="text-end">Day Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="dailyAllocationHistoryBody">
                        <!-- Populated dynamically from saved records in database -->
                    </tbody>
                </table>
</div>
</div>

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function () {
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                }
            });

            const ROUTE_GET_BY_DATE = "{{ route('daily-allocation.by-date') }}";
            const ROUTE_GET_HISTORY = "{{ route('daily-allocation.history') }}";
            const ROUTE_STORE_ALLOCATION = "{{ route('daily-allocation.store') }}";
            const ROUTE_QUICK_ADD_MATERIAL = "{{ route('daily-allocation.quick-add-material') }}";
            let availableMaterials = @json($materials ?? []);

            function escapeHtml(text) {
                if (!text) return '';
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Local Date String (YYYY-MM-DD) avoiding timezone shift
            function getLocalDateString(d = new Date()) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Calculate Monday to Sunday of the current week (Indian/Construction work cycle)
            function getWeekBounds(refDate = new Date()) {
                const d = new Date(refDate);
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

            const now = new Date();
            const todayStr = getLocalDateString(now);
            const weekBounds = getWeekBounds(now);

            // Set today's date initially (or Monday if today happens to be Sunday)
            const initialDate = (now.getDay() === 0) ? weekBounds.mondayStr : todayStr;
            $('#dailyAllocDate').val(initialDate);

            // Flatpickr instance placeholder
            let fpInstance = null;
            let currentWeekSavedMap = {};
            let currentAmWagePayment = null;

            // Day name updater
            function updateDayName() {
                const dateVal = $('#dailyAllocDate').val();
                if (!dateVal) return;
                const d = new Date(dateVal + 'T00:00:00');
                const daysEn = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                const daysTa = ['ஞாயிறு', 'திங்கள்', 'செவ்வாய்', 'புதன்', 'வியாழன்', 'வெள்ளி', 'சனி'];
                const dayEn = daysEn[d.getDay()];
                const dayTa = daysTa[d.getDay()];
                $('#dayNameDisplay').html(`${dayEn} &bull; ${dayTa}`);
            }

            // Render Current Week Strip (Mon to Sun, Sun disabled, future days disabled)
            function renderCurrentWeekStrip() {
                const strip = $('#currentWeekDaysStrip');
                strip.empty();

                const selectedDate = $('#dailyAllocDate').val();

                $('#weekRangeLabel').text(`${weekBounds.mondayStr} to ${weekBounds.saturdayStr}`);

                const daysShort = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                const daysTaShort = ['திங்கள்', 'செவ்வாய்', 'புதன்', 'வியாழன்', 'வெள்ளி', 'சனி', 'ஞாயிறு'];

                for (let i = 0; i < 7; i++) {
                    const curDate = new Date(weekBounds.monday);
                    curDate.setDate(weekBounds.monday.getDate() + i);
                    const curDateStr = getLocalDateString(curDate);
                    const isSunday = (i === 6);
                    const isToday = (curDateStr === todayStr);
                    const isSelected = (curDateStr === selectedDate);
                    const isFuture = (curDateStr > todayStr);

                    const hasData = !!currentWeekSavedMap[curDateStr];

                    let pillClass = 'week-day-pill';
                    if (isSunday || isFuture) {
                        pillClass += ' disabled';
                    } else if (isSelected) {
                        pillClass += ' active';
                    } else if (hasData) {
                        pillClass += ' has-data';
                    }

                    let badgeHtml = '';
                    if (isSunday) {
                        badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-0 px-1" style="font-size:0.65rem;">Holiday</span>';
                    } else if (isFuture) {
                        badgeHtml = '<span class="badge bg-secondary-subtle text-muted border border-secondary-subtle py-0 px-1" style="font-size:0.65rem;">Future</span>';
                    } else if (isToday) {
                        badgeHtml = `<span class="badge ${isSelected ? 'bg-white text-primary' : 'bg-success text-white'} py-0 px-1" style="font-size:0.65rem;">Today</span>`;
                    } else if (hasData) {
                        badgeHtml = `<span class="badge ${isSelected ? 'bg-white text-primary' : 'bg-success-subtle text-success border border-success-subtle'} py-0 px-1" style="font-size:0.65rem;">Saved</span>`;
                    }

                    const pill = $(`
                                <div class="${pillClass}" data-date="${curDateStr}" data-sunday="${isSunday ? '1' : '0'}" data-future="${isFuture ? '1' : '0'}" title="${isSunday ? 'Sundays are disabled (site holiday)' : (isFuture ? 'Future dates are disabled' : curDateStr)}">
                                    <div class="d-flex justify-content-between align-items-center gap-1 mb-1">
                                        <span class="fw-bold pill-day">${daysShort[i]}</span>
                                        ${badgeHtml}
</div>
                                    <div class="small fw-semibold pill-sub">${curDate.getDate()} ${curDate.toLocaleString('en-US', { month: 'short' })}</div>
                                    <div class="small text-muted ta-text pill-sub" style="font-size: 0.7rem;">${daysTaShort[i]}</div>
</div>
                            `);

                    if (!isSunday && !isFuture) {
                        pill.on('click', function () {
                            const dateVal = $(this).data('date');
                            $('#dailyAllocDate').val(dateVal).trigger('change');
                            if (fpInstance) fpInstance.setDate(dateVal, false);
                        });
                    }

                    strip.append(pill);
                }
            }

            // Initialize Flatpickr (Allows past dates & today, future dates disabled, all Sundays disabled)
            function initFlatpickr() {
                if (typeof flatpickr !== 'undefined') {
                    if (fpInstance) fpInstance.destroy();
                    fpInstance = flatpickr("#dailyAllocDate", {
                        dateFormat: "Y-m-d",
                        maxDate: todayStr, // Future dates not allowed
                        disable: [
                            function (date) {
                                return (date.getDay() === 0); // Disable all Sundays
                            }
                        ],
                        disableMobile: true,
                        defaultDate: $('#dailyAllocDate').val() || todayStr,
                        onChange: function (selectedDates, dateStr) {
                            $('#dailyAllocDate').val(dateStr).trigger('change');
                        }
                    });
                }
            }

            // Data Entry Mode Controller (Allows Today and Previous Dates, blocks future dates & Sundays)
            function applyDataEntryMode() {
                const selectedDate = $('#dailyAllocDate').val();
                const isToday = (selectedDate === todayStr);
                const chosen = new Date(selectedDate + 'T00:00:00');
                const isSunday = (chosen.getDay() === 0);
                const isFuture = (selectedDate > todayStr);

                if (!isSunday && !isFuture) {
                    // Editable mode for Today and Previous working dates
                    $('#readOnlyNoticeBanner').addClass('d-none').removeClass('d-flex');
                    $('#saveDailyAllocBtn').prop('disabled', false).removeClass('disabled').removeAttr('title');
                    $('#workersCategoryDropdownBtn').prop('disabled', false).css({ 'pointer-events': '', 'opacity': '' });
                    $('#resetLabourCountsBtn').prop('disabled', false);
                    $('#addCustomExpenseBtn').prop('disabled', false);

                    $('.labour-count-input').prop('disabled', false).prop('readonly', false);
                    $('.labour-rate-input').prop('disabled', false).prop('readonly', false);
                    $('.step-btn').prop('disabled', false).removeClass('disabled').css({ 'pointer-events': '', 'opacity': '' });
                    $('.remove-trade-btn').removeClass('d-none');

                    $('.expense-title').prop('disabled', false).prop('readonly', false);
                    $('.expense-qty').prop('disabled', false).prop('readonly', false);
                    $('.expense-unit-price').prop('disabled', false).prop('readonly', false);
                    $('.remove-expense-btn').removeClass('d-none');

                    if (isToday) {
                        $('#dateStatusBadge')
                            .removeClass('bg-warning-subtle text-warning-emphasis bg-secondary-subtle text-secondary bg-primary-subtle text-primary')
                            .addClass('bg-success-subtle text-success border-success-subtle')
                            .text('Today (Data Entry Active)');
                    } else {
                        $('#dateStatusBadge')
                            .removeClass('bg-warning-subtle text-warning-emphasis bg-secondary-subtle text-secondary bg-success-subtle text-success')
                            .addClass('bg-primary-subtle text-primary border-primary-subtle')
                            .text('Previous Date (Data Entry Active)');
                    }
                } else {
                    // View-Only mode for Sunday or Future date
                    let noticeMessage = '';
                    if (isSunday) {
                        noticeMessage = 'Sunday is a non-working site holiday. Data entry and recording are disabled.';
                    } else {
                        noticeMessage = `Viewing future date (${selectedDate}). Future data entry is not allowed. Only today (${todayStr}) and previous dates can be added or edited.`;
                    }

                    $('#readOnlyNoticeText').text(noticeMessage);
                    $('#readOnlyNoticeBanner').removeClass('d-none').addClass('d-flex');

                    $('#saveDailyAllocBtn').prop('disabled', true).addClass('disabled').attr('title', isSunday ? 'Site holiday (Sunday)' : 'Future date entry is not allowed');
                    $('#workersCategoryDropdownBtn').prop('disabled', true).css({ 'pointer-events': 'none', 'opacity': '0.6' });
                    $('#resetLabourCountsBtn').prop('disabled', true);
                    $('#addCustomExpenseBtn').prop('disabled', true);

                    $('.labour-count-input').prop('disabled', true).prop('readonly', true);
                    $('.labour-rate-input').prop('disabled', true).prop('readonly', true);
                    $('.step-btn').prop('disabled', true).addClass('disabled').css({ 'pointer-events': 'none', 'opacity': '0.4' });
                    $('.remove-trade-btn').addClass('d-none');

                    $('.expense-title').prop('disabled', true).prop('readonly', true);
                    $('.expense-qty').prop('disabled', true).prop('readonly', true);
                    $('.expense-unit-price').prop('disabled', true).prop('readonly', true);
                    $('.remove-expense-btn').addClass('d-none');

                    $('#dateStatusBadge')
                        .removeClass('bg-success-subtle text-success bg-primary-subtle text-primary')
                        .addClass('bg-warning-subtle text-warning-emphasis border-warning-subtle')
                        .text(isSunday ? 'Holiday (Locked)' : 'Future Date (Locked)');
                }
            }

            // Quick button to jump to today
            $('#jumpToTodayBtn, #dailyAllocDateTodayBtn').on('click', function () {
                $('#dailyAllocDate').val(todayStr);
                if (fpInstance) fpInstance.setDate(todayStr, false);
                updateDayName();
                loadAllocationForCurrentSelection();
                showToast(`Switched to today's date (${todayStr}) - Data entry enabled.`, 'info');
            });

            // Date change listener with strict validation (allows previous dates, blocks future dates & Sundays)
            $('#dailyAllocDate').on('change', function () {
                const val = $(this).val();
                if (!val) return;

                const chosen = new Date(val + 'T00:00:00');
                // Check Sunday
                if (chosen.getDay() === 0) {
                    showToast('Sundays are disabled . Please select a working day.', 'warning');
                    $(this).val(todayStr);
                    if (fpInstance) fpInstance.setDate(todayStr, false);
                } else if (val > todayStr) {
                    showToast('Future dates are not allowed for daily allocation. Please select today or a previous working date.', 'warning');
                    $(this).val(todayStr);
                    if (fpInstance) fpInstance.setDate(todayStr, false);
                }

                updateDayName();
                loadAllocationForCurrentSelection();
            });

            updateDayName();
            initFlatpickr();

            // Set project site title
            const initialSiteName = $('#dailyAllocSiteSelect option:selected').data('name') || $('#dailyAllocSiteSelect option:selected').text();
            $('#historySiteTitle').text(initialSiteName);

            // Helper to identify Painter & Electrician ONLY (strictly CAT-007 Painter & CAT-008 Electrician)
            function isPainterElectricianOrAmTrade(catCode, nameEn, nameTa, paidBy) {
                const code = (catCode || '').toString().toUpperCase().trim();
                const en = (nameEn || '').toString().toLowerCase();
                const ta = (nameTa || '').toString();

                // Explicitly exclude Wireman (CAT-009)
                if (code === 'CAT-009' || en.includes('wireman') || ta.includes('மின்கம்பி') || ta.includes('மின் கம்பி')) {
                    return false;
                }

                if (code === 'CAT-007' || code === 'CAT-008') {
                    return true;
                }
                if (en.includes('painter') || en.includes('electrician')) {
                    return true;
                }
                if (ta.includes('பெயிண்டர்') || ta.includes('எலக்ட்ரீஷியன்')) {
                    return true;
                }
                return false;
            }

            // Master Calculation Function
            function recalculateDaily() {
                let totalLabourCount = 0;
                let totalLabourWage = 0;
                let tradeSummary = [];
                let activeTradesCount = 0;

                // Painter, Electrician & Accountant Managed Wages
                let amWageTotal = 0;
                let amWorkersCount = 0;
                let amPaidTotal = 0;
                let amUnpaidCount = 0;
                let amPaidCount = 0;
                let amTradeSummary = [];

                // Labour Rows
                $('.labour-row').each(function () {
                    const isOther = $(this).hasClass('is-other-row') || $(this).data('is-other');
                    const ta = isOther ? ($(this).find('.other-worker-cat-ta').val().trim() || $(this).find('.other-worker-cat-en').val().trim() || 'Others') : $(this).data('name-ta');
                    const en = isOther ? ($(this).find('.other-worker-cat-en').val().trim() || '') : ($(this).data('name-en') || '');
                    const catCode = ($(this).data('cat') || '').toString();
                    const masterRate = parseFloat($(this).data('master-rate')) || 0;
                    const count = parseInt($(this).find('.labour-count-input').val()) || 0;
                    const rate = parseFloat($(this).find('.labour-rate-input').val()) || 0;
                    const subtotal = Math.round(count * rate);
                    const paidBy = ($(this).attr('data-paid-by') || $(this).data('paid-by') || '').toString().trim();

                    $(this).find('.labour-subtotal').text('₹' + subtotal.toLocaleString('en-IN'));

                    // Show custom badge if user changed the master rate
                    const isCustom = (!isOther && masterRate > 0 && Math.round(rate) !== Math.round(masterRate));
                    if (isCustom) {
                        $(this).find('.rate-edited-badge').removeClass('d-none');
                        $(this).find('.labour-rate-input').addClass('border-primary text-primary');
                    } else if (!isOther) {
                        $(this).find('.rate-edited-badge').addClass('d-none');
                        $(this).find('.labour-rate-input').removeClass('border-primary text-primary');
                    }

                    if (count > 0) {
                        activeTradesCount++;
                        totalLabourCount += count;
                        totalLabourWage += subtotal;
                        tradeSummary.push(`${count} ${ta}`);

                        if (isPainterElectricianOrAmTrade(catCode, en, ta, paidBy)) {
                            amWageTotal += subtotal;
                            amWorkersCount += count;
                            amTradeSummary.push(`${count} ${ta}`);
                            const isPaid = (paidBy.toLowerCase() === 'account manager' || paidBy.toLowerCase() === 'accountant');
                            if (isPaid) {
                                amPaidTotal += subtotal;
                                amPaidCount++;
                            } else {
                                amUnpaidCount++;
                            }
                        }
                    }
                });

                // Expenses Rows
                let totalExpenses = 0;
                $('.expense-row').each(function () {
                    const qty = parseFloat($(this).find('.expense-qty').val()) || 0;
                    const unitPrice = parseFloat($(this).find('.expense-unit-price').val()) || 0;
                    const amount = Math.round(qty * unitPrice);

                    $(this).find('.expense-amount').val(amount);
                    $(this).find('.expense-subtotal-text').text('₹' + amount.toLocaleString('en-IN'));
                    totalExpenses += amount;
                });

                // Update UI Badges & KPIs
                $('#totalLabourCountBadge').text(`${totalLabourCount} Workers Present`);
                $('#totalLabourWageBadge').text('₹' + totalLabourWage.toLocaleString('en-IN'));
                $('#totalExpensesBadge').text('₹' + totalExpenses.toLocaleString('en-IN'));
                $('#activeCategoriesCount').text(`${activeTradesCount} Trades Active`);

                $('#statTodayWorkers').text(totalLabourCount);
                $('#statTodayTradesDesc').text(tradeSummary.join(', ') || 'No workers allocated').attr('title', tradeSummary.join(', ') || 'No workers allocated');
                $('#statLabourTotal').text('₹' + totalLabourWage.toLocaleString('en-IN'));
                $('#statExpensesTotal').text('₹' + totalExpenses.toLocaleString('en-IN'));

                // Update Painter & Electrician KPI Card
                const amBadgeEl = $('#statAmWageStatusBadge');
                const amDescEl = $('#statAmWageDesc');

                // Check actual payment from backend records (SiteCash / Ledger) or row flags strictly for Painter & Electrician
                const serverPaidAmount = (currentAmWagePayment && currentAmWagePayment.has_payment) ? parseFloat(currentAmWagePayment.paid_amount) || 0 : 0;
                const effectivePaidAmount = amPaidTotal > 0 ? amPaidTotal : (serverPaidAmount > 0 ? serverPaidAmount : 0);
                const hasEffectivePayment = effectivePaidAmount > 0;

                if (hasEffectivePayment) {
                    $('#statAmWageTotal').html(`<span class="text-success fw-bold">₹${effectivePaidAmount.toLocaleString('en-IN')}</span>`);
                    amBadgeEl.html('<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Paid by Account Manager</span>');

                    let detailsStr = '';
                    if (currentAmWagePayment && currentAmWagePayment.details && currentAmWagePayment.details.length > 0) {
                        const firstDet = currentAmWagePayment.details[0];
                        const sourceLabel = firstDet.cash_source === 'project_cash' ? 'Project Cash' : 'Office Cash';
                        detailsStr = ` (${sourceLabel})`;
                    }
                    const tradesList = amTradeSummary.length > 0 ? ` • ${amTradeSummary.join(', ')}` : '';
                    amDescEl.text(`Paid by Account Manager: ₹${effectivePaidAmount.toLocaleString('en-IN')}${detailsStr}${tradesList}`)
                            .attr('title', `Paid by Account Manager: ₹${effectivePaidAmount.toLocaleString('en-IN')}${detailsStr}${tradesList}`);
                } else if (amWageTotal > 0) {
                    // Allocated in daily table, but Account Manager hasn't paid on this date
                    $('#statAmWageTotal').html(`<span class="text-secondary fw-bold">₹0</span> <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-6 align-middle ms-1">Pending: ₹${amWageTotal.toLocaleString('en-IN')}</span>`);
                    amBadgeEl.html('<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Payment Pending</span>');
                    amDescEl.text(`AM Paid: ₹0 • Allocated: ₹${amWageTotal.toLocaleString('en-IN')} (${amWorkersCount} Workers: ${amTradeSummary.join(', ')}) • Awaiting AM Payment`)
                            .attr('title', `AM Paid: ₹0 • Allocated: ₹${amWageTotal.toLocaleString('en-IN')} • Awaiting AM Payment`);
                } else {
                    // No painter / electrician payment or allocation for this date
                    $('#statAmWageTotal').html('<span class="text-muted fw-bold">₹0</span>');
                    amBadgeEl.html('<span class="badge bg-light text-muted border px-2 py-1">₹0 Paid by AM</span>');
                    amDescEl.text('No Painter & Electrician payment made by Account Manager for this date').attr('title', 'No Painter & Electrician payment made by Account Manager for this date');
                }

                // Update Labour Wages Card subtext to clearly separate Site Labour vs AM Paid
                if (hasEffectivePayment && totalLabourWage > 0) {
                    const siteLabourWage = Math.max(0, totalLabourWage - effectivePaidAmount);
                    $('#statLabourSubtext').text(`Site Labour: ₹${siteLabourWage.toLocaleString('en-IN')} • AM Paid: ₹${effectivePaidAmount.toLocaleString('en-IN')}`);
                } else {
                    $('#statLabourSubtext').text('Calculated daily wage total');
                }
            }

            // Stepper buttons (+/-)
            $(document).on('click', '.step-btn', function () {
                const d = $('#dailyAllocDate').val();
                if (!d || d > todayStr) return;
                const step = parseInt($(this).data('step'));
                const input = $(this).closest('td').find('.labour-count-input');
                let val = (parseInt(input.val()) || 0) + step;
                if (val < 0) val = 0;
                input.val(val).trigger('input');
            });

            // Input listener
            $(document).on('input', '.labour-count-input, .labour-rate-input, .expense-qty, .expense-unit-price, .expense-title, .other-worker-cat-en, .other-worker-cat-ta', recalculateDaily);

            // Trade Visual Helper for icons and colors matching master
            function getTradeVisuals(cat, nameEn) {
                const lower = ((nameEn || '') + ' ' + (cat || '')).toLowerCase();
                if (lower.includes('mestri') || lower.includes('head mason') || lower.includes('cat-001')) {
                    return { icon: 'bi-person-badge', badge: 'bg-primary bg-opacity-10 text-primary' };
                } else if (lower.includes('brick') || lower.includes('kothanar') || lower.includes('mason') || lower.includes('cat-002')) {
                    return { icon: 'bi-bricks', badge: 'bg-info bg-opacity-10 text-info' };
                } else if (lower.includes('female') || lower.includes('cat-004')) {
                    return { icon: 'bi-person', badge: 'bg-danger bg-opacity-10 text-danger' };
                } else if (lower.includes('helper') || lower.includes('chithal') || lower.includes('sithal') || lower.includes('cat-003')) {
                    return { icon: 'bi-person-walking', badge: 'bg-warning bg-opacity-10 text-warning' };
                } else if (lower.includes('bar') || lower.includes('steel') || lower.includes('bender') || lower.includes('cat-005')) {
                    return { icon: 'bi-grid-3x3', badge: 'bg-secondary bg-opacity-10 text-secondary' };
                } else if (lower.includes('carpenter') || lower.includes('centering') || lower.includes('cat-006')) {
                    return { icon: 'bi-tools', badge: 'bg-success bg-opacity-10 text-success' };
                } else if (lower.includes('painter') || lower.includes('finisher') || lower.includes('cat-007')) {
                    return { icon: 'bi-paint-bucket', badge: 'bg-warning bg-opacity-10 text-dark' };
                } else if (lower.includes('electrician') || lower.includes('plumber') || lower.includes('cat-008')) {
                    return { icon: 'bi-lightning-charge', badge: 'bg-primary bg-opacity-10 text-primary' };
                }
                return { icon: 'bi-person', badge: 'bg-primary bg-opacity-10 text-primary' };
            }

            // State for retained worker categories across date & site changes
            const RETAINED_WORKERS_STORAGE_KEY = 'deiveni_retained_worker_categories';
            let retainedWorkerCategories = [];

            try {
                const storedCategories = sessionStorage.getItem(RETAINED_WORKERS_STORAGE_KEY);
                if (storedCategories) {
                    const parsed = JSON.parse(storedCategories);
                    if (Array.isArray(parsed)) {
                        retainedWorkerCategories = parsed;
                    }
                }
            } catch (e) {
                retainedWorkerCategories = [];
            }

            function persistRetainedWorkerCategories() {
                try {
                    sessionStorage.setItem(RETAINED_WORKERS_STORAGE_KEY, JSON.stringify(retainedWorkerCategories));
                } catch (e) {}
            }

            function snapshotCurrentWorkerCategories() {
                const list = [];
                $('.labour-row').each(function () {
                    const isOther = $(this).hasClass('is-other-row') || $(this).data('is-other');
                    const paidBy = $(this).attr('data-paid-by') || $(this).data('paid-by') || null;
                    const wageSource = $(this).attr('data-wage-source') || $(this).data('wage-source') || null;
                    const ledgerId = $(this).attr('data-ledger-id') || $(this).data('ledger-id') || null;

                    if (isOther) {
                        const en = ($(this).find('.other-worker-cat-en').val() || '').trim();
                        const ta = ($(this).find('.other-worker-cat-ta').val() || '').trim();
                        const rate = parseFloat($(this).find('.labour-rate-input').val()) || 0;
                        if (en || ta) {
                            list.push({
                                isOther: true,
                                name_en: en,
                                name_ta: ta,
                                daily_wage: rate,
                                master_wage: 0,
                                paid_by: paidBy,
                                wage_cash_source: wageSource,
                                ledger_id: ledgerId
                            });
                        }
                    } else {
                        const cat = ($(this).data('cat') || '').toString();
                        const ta = ($(this).data('name-ta') || '').toString();
                        const en = ($(this).data('name-en') || '').toString();
                        const masterRate = parseFloat($(this).data('master-rate')) || 0;
                        const rate = parseFloat($(this).find('.labour-rate-input').val()) || masterRate;
                        const catId = $(this).data('worker-category-id') || null;

                        if (ta || cat) {
                            list.push({
                                isOther: false,
                                category_code: cat,
                                name_ta: ta,
                                name_en: en,
                                master_wage: masterRate,
                                daily_wage: rate,
                                worker_category_id: catId,
                                paid_by: paidBy,
                                wage_cash_source: wageSource,
                                ledger_id: ledgerId
                            });
                        }
                    }
                });

                if (list.length > 0) {
                    retainedWorkerCategories = list;
                    persistRetainedWorkerCategories();
                }
            }

            // Create Worker Row HTML with editable Daily Wage Rate
            function createWorkerRowHtml(cat, ta, en, rate, count = 0, masterRate = null, workerCategoryId = null, paidBy = null, wageCashSource = null, ledgerId = null) {
                const visuals = getTradeVisuals(cat, en);
                const countVal = parseInt(count) || 0;
                const defaultRate = masterRate !== null && masterRate !== undefined ? Math.round(masterRate) : Math.round(rate) || 0;
                const rateVal = (rate !== null && rate !== undefined) ? Math.round(rate) : defaultRate;
                const subtotal = countVal * rateVal;
                const isRateCustomized = (rateVal !== defaultRate && defaultRate > 0);
                const catIdAttr = workerCategoryId ? `data-worker-category-id="${workerCategoryId}"` : '';
                const paidByAttr = paidBy ? `data-paid-by="${paidBy}"` : '';
                const wageSourceAttr = wageCashSource ? `data-wage-source="${wageCashSource}"` : '';
                const ledgerIdAttr = ledgerId ? `data-ledger-id="${ledgerId}"` : '';

                const isPaidByAm = (paidBy && (paidBy.toLowerCase().includes('account') || paidBy.toLowerCase().includes('am')));
                const paidBadgeHtml = isPaidByAm ? `<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.68rem;" title="Wage paid by Account Manager"><i class="bi bi-check-circle-fill me-1"></i>Paid by AM</span>` : '';

                return `
                            <tr class="labour-row" data-cat="${cat || ''}" data-name-ta="${ta}" data-name-en="${en || ''}" data-master-rate="${defaultRate}" ${catIdAttr} ${paidByAttr} ${wageSourceAttr} ${ledgerIdAttr}>
                                <td class="text-start">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle ${visuals.badge} fw-bold"
                                            style="width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                            <i class="bi ${visuals.icon}"></i>
</div>
                                        <div>
                                            <div class="fw-bold text-dark ta-text d-flex align-items-center flex-wrap gap-1">
                                                <span>${ta}</span>
                                                ${paidBadgeHtml}
</div>
                                            <small class="text-muted">${en || ''}</small>
</div>
</div>
                                </td>
                                <td class="text-center">
                                    <div class="input-group input-group-sm justify-content-center mx-auto" style="max-width: 140px;">
                                        <span class="input-group-text bg-light text-muted px-2">₹</span>
                                        <input type="number" min="0" step="10"
                                            class="form-control form-control-sm text-end fw-bold labour-rate-input ${isRateCustomized ? 'border-primary text-primary' : 'text-dark'}"
                                            value="${rateVal}"
                                            data-master-rate="${defaultRate}"
                                            placeholder="Rate"
                                            title="Daily Wage Rate (Master: ₹${defaultRate.toLocaleString('en-IN')}). You can edit this rate optionally.">
</div>
                                    <div class="d-flex justify-content-center align-items-center gap-1 mt-1">
                                        <small class="text-muted rate-master-label" style="font-size: 0.72rem;">Master: ₹${defaultRate.toLocaleString('en-IN')}</small>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rate-edited-badge ${isRateCustomized ? '' : 'd-none'}">Custom</span>
</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-1"
                                        style="max-width: 160px; margin: auto;">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 step-btn"
                                            data-step="-1"><i class="bi bi-dash"></i></button>
                                        <input type="number" min="0" max="99"
                                            class="form-control form-control-sm text-center fw-bold fs-6 labour-count-input"
                                            value="${countVal}" style="width: 70px;">
                                        <button type="button" class="btn btn-outline-secondary btn-sm px-2 step-btn"
                                            data-step="1"><i class="bi bi-plus"></i></button>
</div>
                                </td>
                                <td class="text-end fw-bold text-dark labour-subtotal">₹${subtotal.toLocaleString('en-IN')}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-link text-muted p-0 remove-trade-btn"
                                        title="Remove Category"><i class="bi bi-trash text-danger"></i></button>
                                </td>
                            </tr>
                        `;
            }

            // Remove Trade Category Row
            $(document).on('click', '.remove-trade-btn', function () {
                const d = $('#dailyAllocDate').val();
                if (!d || d > todayStr) return;
                const row = $(this).closest('.labour-row');
                const catName = row.data('name-ta');
                const catCode = (row.data('cat') || '').toString();
                const isOther = row.hasClass('is-other-row') || row.data('is-other');
                const otherEn = isOther ? (row.find('.other-worker-cat-en').val() || '').trim() : '';
                const otherTa = isOther ? (row.find('.other-worker-cat-ta').val() || '').trim() : '';

                row.remove();

                if (isOther) {
                    retainedWorkerCategories = retainedWorkerCategories.filter(function (c) {
                        if (!c.isOther) return true;
                        return !(c.name_en === otherEn && c.name_ta === otherTa);
                    });
                } else {
                    retainedWorkerCategories = retainedWorkerCategories.filter(function (c) {
                        if (c.isOther) return true;
                        if (catCode && c.category_code) return c.category_code !== catCode;
                        return c.name_ta !== catName;
                    });
                }

                if ($('.labour-row').length === 0) {
                    retainedWorkerCategories = [];
                    $('#workerAllocationBody').html(`
                                <tr id="emptyWorkerRow">
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-people text-secondary fs-3 d-block mb-1"></i>
                                        No worker categories added yet. Click <strong>"Workers Category"</strong> above to add categories.
                                    </td>
                                </tr>
                            `);
                }

                persistRetainedWorkerCategories();
                recalculateDaily();
                showToast(`Removed ${catName || otherEn || 'category'} from allocation`, 'info');
            });

            // Add Trade from Dropdown
            $(document).on('click', '.add-trade-btn', function () {
                const curDate = $('#dailyAllocDate').val();
                if (!curDate || curDate > todayStr) {
                    showToast('Data entry is restricted: Future dates cannot be modified. Only today and previous dates are allowed.', 'warning');
                    return;
                }

                const workerCategoryId = $(this).data('id');
                const cat = $(this).data('cat');
                const ta = $(this).data('ta');
                const en = $(this).data('en');
                const rate = $(this).data('rate');

                let exists = false;
                $(`.labour-row`).each(function () {
                    if ($(this).data('name-ta') === ta || ($(this).data('cat') && $(this).data('cat') === cat)) {
                        exists = true;
                    }
                });

                if (exists) {
                    showToast(`${ta} is already present in allocation list.`, 'warning');
                    return;
                }

                $('#emptyWorkerRow').remove();

                const newRow = createWorkerRowHtml(cat, ta, en, rate, 0, rate, workerCategoryId);
                $('#workerAllocationBody').append(newRow);
                snapshotCurrentWorkerCategories();
                recalculateDaily();
                applyDataEntryMode();
                showToast(`Added ${ta} (${en}) with Master Rate ₹${rate}`, 'success');
            });

            // Reset Labour Counts
            $('#resetLabourCountsBtn').on('click', function () {
                const d = $('#dailyAllocDate').val();
                if (!d || d > todayStr) return;
                $('.labour-count-input').val(0);
                recalculateDaily();
                showToast('Reset labour counts to 0', 'info');
            });

            // Create "Others" Custom Worker Row HTML with category input in Work Category column
            function createOtherWorkerRowHtml(paidBy = null, wageCashSource = null, ledgerId = null) {
                const paidByAttr = paidBy ? `data-paid-by="${paidBy}"` : '';
                const wageSourceAttr = wageCashSource ? `data-wage-source="${wageCashSource}"` : '';
                const ledgerIdAttr = ledgerId ? `data-ledger-id="${ledgerId}"` : '';
                const isPaidByAm = (paidBy && (paidBy.toLowerCase().includes('account') || paidBy.toLowerCase().includes('am')));
                const paidBadgeHtml = isPaidByAm ? `<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.68rem;" title="Wage paid by Account Manager"><i class="bi bi-check-circle-fill me-1"></i>Paid by AM</span>` : '';

                return `
                    <tr class="labour-row is-other-row" data-cat="" data-name-ta="" data-name-en="" data-master-rate="0" data-is-other="true" ${paidByAttr} ${wageSourceAttr} ${ledgerIdAttr}>
                        <td class="text-start">
                            <div class="d-flex align-items-start gap-2">
                                <div class="avatar-circle bg-warning bg-opacity-10 text-dark fw-bold mt-1"
                                    style="width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <i class="bi bi-person-plus-fill text-warning"></i>
</div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <input type="text" class="form-control form-control-sm fw-semibold other-worker-cat-en"
                                            placeholder="Category Name in English * (e.g. Brick Mason / Helper)" required>
                                        ${paidBadgeHtml}
</div>
                                    <div class="mt-1">
                                        <input type="text" class="form-control form-control-sm ta-text other-worker-cat-ta"
                                               placeholder="Category Name in Tamil *" required style="font-size: 0.82rem;">
</div>
</div>
</div>
                        </td>
                        <td class="text-center">
                            <div class="input-group input-group-sm justify-content-center mx-auto" style="max-width: 140px;">
                                <span class="input-group-text bg-light text-muted px-2">₹</span>
                                <input type="number" min="0" step="10"
                                    class="form-control form-control-sm text-end fw-bold labour-rate-input text-dark"
                                    value="0"
                                    data-master-rate="0"
                                    placeholder="Rate"
                                    title="Daily Wage Rate for this new category.">
</div>
                            <div class="d-flex justify-content-center align-items-center gap-1 mt-1">
                                <small class="text-muted" style="font-size: 0.72rem;">Master Wage</small>
</div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center gap-1"
                                style="max-width: 160px; margin: auto;">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-2 step-btn"
                                    data-step="-1"><i class="bi bi-dash"></i></button>
                                <input type="number" min="0" max="99"
                                    class="form-control form-control-sm text-center fw-bold fs-6 labour-count-input"
                                    value="1" style="width: 70px;">
                                <button type="button" class="btn btn-outline-secondary btn-sm px-2 step-btn"
                                    data-step="1"><i class="bi bi-plus"></i></button>
</div>
                        </td>
                        <td class="text-end fw-bold text-dark labour-subtotal">₹0</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-link text-muted p-0 remove-trade-btn"
                                title="Remove Category"><i class="bi bi-trash text-danger"></i></button>
                        </td>
                    </tr>
                `;
            }

            // Create Expense Row with Auto-Search from Materials
            function createExpenseRowHtml(item = null) {
                const titleVal = item ? (item.item_name || '') : '';
                const qtyVal = item && item.quantity !== undefined ? item.quantity : '';
                const priceVal = item && item.unit_price !== undefined ? item.unit_price : '';
                const amountVal = item && item.amount !== undefined ? item.amount : '';
                const formattedAmount = amountVal ? '₹' + Math.round(amountVal).toLocaleString('en-IN') : '₹0';

                return `
                    <tr class="expense-row">
                        <td>
                            <div class="position-relative expense-autocomplete-wrapper">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control form-control-sm expense-title"
                                           value="${titleVal}"
                                           placeholder="e.g. Drinking Water Cans / Tea / Binding Wire"
                                           autocomplete="off">
</div>
                                <div class="expense-suggestions-dropdown shadow rounded border d-none"
                                     style="background: #fff; max-height: 220px; overflow-y: auto;">
</div>
</div>
                        </td>
                        <td>
                            <input type="number" min="0" step="any" class="form-control form-control-sm text-center expense-qty"
                                   value="${qtyVal}" placeholder="Qty">
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₹</span>
                                <input type="number" min="0" step="any" class="form-control text-end fw-semibold expense-unit-price"
                                       value="${priceVal}" placeholder="Price">
</div>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-dark fs-6 expense-subtotal-text">${formattedAmount}</span>
                            <input type="hidden" class="expense-amount" value="${amountVal}">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-expense-btn"><i class="bi bi-x-circle-fill fs-5"></i></button>
                        </td>
                    </tr>
                `;
            }

            // Setup Auto-Search for Expense title fetching from Materials
            function setupExpenseAutocomplete(wrapper) {
                const $wrap = $(wrapper);
                const $input = $wrap.find('.expense-title');
                const $dropdown = $wrap.find('.expense-suggestions-dropdown');

                // Position the dropdown using fixed coordinates so it is never
                // clipped by any overflow:hidden/auto ancestor (e.g. table-responsive).
                function positionDropdown() {
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
                               data-en="${escapeHtml(m.name_en)}" data-ta="${escapeHtml(m.name_ta || '')}" data-unit="${escapeHtml(unitName)}">
                                <div class="text-truncate me-2">
                                    <span class="fw-semibold text-dark">${escapeHtml(m.name_en)}</span>
                                    ${taText}
</div>
                                ${unitBadge}
</a>
                        `;
                    });

                    // If query doesn't exactly match any existing material, give option to quick-add
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
                    $(window).on('scroll.expenseAutoc resize.expenseAutoc', positionDropdown);
                });

                $(document).on('click', function (e) {
                    if (!$(e.target).closest($wrap).length && !$(e.target).closest($dropdown).length) {
                        $dropdown.addClass('d-none');
                        $(window).off('scroll.expenseAutoc resize.expenseAutoc');
                    }
                });

                $dropdown.on('click', '.expense-sugg-item', function () {
                    const en = $(this).data('en');
                    const ta = $(this).data('ta');
                    const display = ta ? `${en} - ${ta}` : en;
                    $input.val(display);
                    $dropdown.addClass('d-none');
                    $(window).off('scroll.expenseAutoc resize.expenseAutoc');

                    const row = $input.closest('.expense-row');
                    const qtyInput = row.find('.expense-qty');
                    if (!qtyInput.val()) {
                        qtyInput.val(1);
                    }
                    recalculateDaily();
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
                                $(window).off('scroll.expenseAutoc resize.expenseAutoc');

                                const row = $input.closest('.expense-row');
                                const qtyInput = row.find('.expense-qty');
                                if (!qtyInput.val()) {
                                    qtyInput.val(1);
                                }
                                recalculateDaily();
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

            // Re-render categories in dropdown when new category is created
            function updateDropdownCategories(categories) {
                if (!categories || !categories.length) return;
                const list = $('#tradeAddDropdown');
                list.empty();
                list.append(`
                    <li>
                        <h6 class="dropdown-header text-uppercase small fw-bold text-muted">Select Worker Category</h6>
                    </li>
                `);
                categories.forEach(function (category) {
                    list.append(`
                        <li>
                            <a class="dropdown-item add-trade-btn py-2" href="javascript:void(0)"
                                data-id="${category.id}" data-cat="${category.code}"
                                data-ta="${category.name_ta}" data-en="${category.name_en}"
                                data-rate="${Math.round(category.daily_wage)}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark ta-text">+ ${category.name_ta}</div>
                                        <small class="text-muted">${category.name_en}</small>
</div>
                                    <span class="badge bg-light text-dark border ms-2">₹${Math.round(category.daily_wage).toLocaleString('en-IN')}</span>
</div>
</a>
                        </li>
                    `);
                });
                list.append(`
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2 text-primary fw-semibold add-other-trade-btn" href="javascript:void(0)" id="addOtherTradeBtn">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-primary bg-opacity-10 text-primary fw-bold me-2"
                                    style="width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                                    <i class="bi bi-plus-circle-dotted fs-5"></i>
</div>
                                <div>
                                    <div class="fw-bold text-primary ta-text">+ மற்றவை (Others)</div>
                                    <small class="text-muted fw-normal">Add custom / new worker category</small>
</div>
</div>
</a>
                    </li>
                `);
            }

            // Add "Others" Trade from Dropdown
            $(document).on('click', '.add-other-trade-btn', function () {
                const curDate = $('#dailyAllocDate').val();
                if (!curDate || curDate > todayStr) {
                    showToast('Data entry is restricted: Future dates cannot be modified. Only today and previous dates are allowed.', 'warning');
                    return;
                }

                $('#emptyWorkerRow').remove();

                const newRow = $(createOtherWorkerRowHtml());
                $('#workerAllocationBody').append(newRow);
                newRow.find('.other-worker-cat-en').focus();
                snapshotCurrentWorkerCategories();
                recalculateDaily();
                applyDataEntryMode();
                showToast('Added custom worker category row. Enter category name in the Worker Category column.', 'info');
            });

            // Add Custom Expense Row
            $('#addCustomExpenseBtn').on('click', function () {
                const curDate = $('#dailyAllocDate').val();
                if (!curDate || curDate > todayStr) {
                    showToast('Data entry is restricted: Future dates cannot be modified. Only today and previous dates are allowed.', 'warning');
                    return;
                }

                const newRow = $(createExpenseRowHtml());
                $('#dailyExpensesBody').append(newRow);
                setupExpenseAutocomplete(newRow.find('.expense-autocomplete-wrapper'));
                recalculateDaily();
                applyDataEntryMode();
            });

            // Remove Expense Row
            $(document).on('click', '.remove-expense-btn', function () {
                const d = $('#dailyAllocDate').val();
                if (!d || d > todayStr) return;
                $(this).closest('.expense-row').remove();
                recalculateDaily();
                showToast('Expense item removed', 'info');
            });

            // Initialize Default History Filter (Current Week Data)
            $('#historyFromDate').val(weekBounds.mondayStr);
            $('#historyToDate').val(weekBounds.sundayStr);

            // Render Day-by-day Historical log from Database with Date Filter
            function renderHistoryTable() {
                const siteId = $('#dailyAllocSiteSelect').val();
                const siteName = $('#dailyAllocSiteSelect option:selected').data('name') || $('#dailyAllocSiteSelect option:selected').text();
                $('#historySiteTitle').text(siteName);

                if (!siteId) return;

                let fromDate = $('#historyFromDate').val();
                let toDate = $('#historyToDate').val();

                $.get(ROUTE_GET_HISTORY, {
                    project_site_id: siteId,
                    from_date: fromDate || null,
                    to_date: toDate || null
                }, function (response) {
                    if (!response.success) return;

                    const siteAllocations = response.data || [];

                    // Generate date range bounds
                    let startBound = fromDate;
                    let endBound = toDate;

                    if (!startBound && !endBound) {
                        if (siteAllocations.length > 0) {
                            const sortedDates = siteAllocations.map(a => a.date.substring(0, 10)).sort();
                            startBound = sortedDates[0];
                            endBound = sortedDates[sortedDates.length - 1];
                        } else {
                            startBound = weekBounds.mondayStr;
                            endBound = weekBounds.sundayStr;
                        }
                    } else if (!startBound) {
                        startBound = weekBounds.mondayStr;
                    } else if (!endBound) {
                        endBound = weekBounds.sundayStr;
                    }

                    if (startBound > endBound) {
                        const temp = startBound;
                        startBound = endBound;
                        endBound = temp;
                    }

                    const dateList = [];
                    let cur = new Date(startBound + 'T00:00:00');
                    const end = new Date(endBound + 'T00:00:00');
                    while (cur <= end && dateList.length < 90) {
                        dateList.push(getLocalDateString(cur));
                        cur.setDate(cur.getDate() + 1);
                    }

                    const tbody = $('#dailyAllocationHistoryBody');
                    tbody.empty();

                    const savedCount = siteAllocations.filter(a => {
                        const d = a.date.substring(0, 10);
                        return d >= startBound && d <= endBound;
                    }).length;

                    $('#historyDaysBadge').text(`${savedCount} of ${dateList.length} Day(s) Allocated`);

                    dateList.forEach(function (dateStr) {
                        const rec = siteAllocations.find(a => a.date.substring(0, 10) === dateStr);
                        const d = new Date(dateStr + 'T00:00:00');
                        const dateFormatted = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                        const dayOfWeek = d.toLocaleDateString('en-US', { weekday: 'short' });
                        const isSunday = (d.getDay() === 0);

                        if (rec) {
                            const tradesText = rec.trade_summary || (rec.items || []).filter(w => w.worker_count > 0).map(w => `${w.worker_count} ${w.name_ta}`).join(' + ') || 'No labour';
                            const expText = (rec.expenses || []).map(e => `${e.item_name} (₹${Math.round(e.amount)})`).join(', ') || 'No extra expenses';

                            const rowHtml = `
                                        <tr data-date="${dateStr}" class="history-date-row" style="cursor: pointer;" title="Click to view/edit allocation for ${dateStr}">
                                            <td>
                                                <strong class="text-dark d-block">${dayOfWeek}, ${dateFormatted}</strong>
                                                <small class="text-muted">${dateStr}</small>
                                            </td>
                                            <td>
                                                <span class="ta-text fw-semibold text-dark">${tradesText}</span>
                                                <div class="small text-muted" style="font-size: 0.75rem;">${expText}</div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-primary">${rec.total_labour_count || 0}</span></td>
                                            <td class="text-end fw-bold text-dark">₹${Math.round(rec.total_labour_wage || 0).toLocaleString('en-IN')}</td>
                                            <td class="text-end fw-bold text-warning">₹${Math.round(rec.total_expenses || 0).toLocaleString('en-IN')}</td>
                                            <td class="text-end fw-bold text-success fs-6">₹${Math.round(rec.grand_total || 0).toLocaleString('en-IN')}</td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-block">Saved</span>
                                                <div><button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 mt-1 edit-history-date-btn" data-date="${dateStr}" style="font-size: 0.72rem;"><i class="bi bi-pencil-square me-1"></i>Edit</button></div>
                                            </td>
                                        </tr>
                                    `;
                            tbody.append(rowHtml);
                        } else {
                            // Unallocated Day (No Labour)
                            let badgeHtml = '';
                            let categoryText = '';
                            let statusBadge = '';

                            if (isSunday) {
                                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle me-1">Sunday Holiday</span>';
                                categoryText = 'Site holiday / No labour';
                                statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Holiday</span>';
                            } else {
                                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1"><i class="bi bi-person-x me-1"></i>No Labour</span>';
                                categoryText = 'Not allocated';
                                statusBadge = '<span class="badge bg-light text-secondary border">No Labour</span>';
                            }

                            const canAllocate = (!isSunday && dateStr <= todayStr);
                            const rowHtml = `
                                        <tr data-date="${dateStr}" class="${canAllocate ? 'history-date-row' : ''} ${isSunday ? 'opacity-75 bg-light' : ''}" ${canAllocate ? 'style="cursor: pointer;" title="Click to add allocation for ' + dateStr + '"' : ''}>
                                            <td>
                                                <strong class="text-dark d-block">${dayOfWeek}, ${dateFormatted}</strong>
                                                <small class="text-muted">${dateStr}</small>
                                            </td>
                                            <td>
                                                <div>${badgeHtml}</div>
                                                <small class="text-muted">${categoryText}</small>
                                            </td>
                                            <td class="text-center"><span class="badge bg-light text-muted border">0</span></td>
                                            <td class="text-end text-muted">₹0</td>
                                            <td class="text-end text-muted">₹0</td>
                                            <td class="text-end text-muted fw-semibold">₹0</td>
                                            <td class="text-center">
                                                ${statusBadge}
                                                ${canAllocate ? `<div><button type="button" class="btn btn-outline-success btn-sm py-0 px-2 mt-1 edit-history-date-btn" data-date="${dateStr}" style="font-size: 0.72rem;"><i class="bi bi-plus-circle me-1"></i>Add</button></div>` : ''}
                                            </td>
                                        </tr>
                                    `;
                            tbody.append(rowHtml);
                        }
                    });
                });
            }

            // Task 4: Filter Button Handlers
            $('#historyFromDate, #historyToDate').on('change', function () {
                $('#historyFilterThisWeekBtn').removeClass('btn-primary').addClass('btn-outline-secondary');
                $('#historyFilterAllBtn').removeClass('btn-primary').addClass('btn-outline-secondary');
                renderHistoryTable();
            });

            $('#historyFilterThisWeekBtn').on('click', function () {
                $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
                $('#historyFilterAllBtn').removeClass('btn-primary').addClass('btn-outline-secondary');
                $('#historyFromDate').val(weekBounds.mondayStr);
                $('#historyToDate').val(weekBounds.sundayStr);
                renderHistoryTable();
            });

            $('#historyFilterAllBtn').on('click', function () {
                $(this).removeClass('btn-outline-secondary').addClass('btn-primary');
                $('#historyFilterThisWeekBtn').removeClass('btn-primary').addClass('btn-outline-secondary');
                $('#historyFromDate').val('');
                $('#historyToDate').val('');
                renderHistoryTable();
            });

            $('#historyFilterResetBtn').on('click', function () {
                $('#historyFilterThisWeekBtn').removeClass('btn-outline-secondary').addClass('btn-primary');
                $('#historyFilterAllBtn').removeClass('btn-primary').addClass('btn-outline-secondary');
                $('#historyFromDate').val(weekBounds.mondayStr);
                $('#historyToDate').val(weekBounds.sundayStr);
                renderHistoryTable();
            });

            $(document).on('click', '#resetHistoryFilterLink', function () {
                $('#historyFilterThisWeekBtn').trigger('click');
            });

            $(document).on('click', '.history-jump-today-btn', function () {
                $('#jumpToTodayBtn').trigger('click');
                $('html, body').animate({ scrollTop: $('#dailyAllocDate').offset().top - 100 }, 300);
            });

            // Click history row or edit button to load previous/selected date into editor
            $(document).on('click', '.history-date-row, .edit-history-date-btn', function (e) {
                e.stopPropagation();
                const dateVal = $(this).data('date');
                if (!dateVal) return;
                const chosen = new Date(dateVal + 'T00:00:00');
                if (chosen.getDay() === 0) {
                    showToast('Sundays are non-working site holidays.', 'warning');
                    return;
                }
                if (dateVal > todayStr) {
                    showToast('Future dates cannot be selected for allocation.', 'warning');
                    return;
                }
                $('#dailyAllocDate').val(dateVal).trigger('change');
                if (fpInstance) fpInstance.setDate(dateVal, false);
                $('html, body').animate({ scrollTop: $('#dailyAllocDate').offset().top - 100 }, 300);
            });

            // Load allocation dynamically from Database
            function loadAllocationForCurrentSelection() {
                const date = $('#dailyAllocDate').val();
                const siteId = $('#dailyAllocSiteSelect').val();

                if (!siteId || !date) return;

                // Snapshot any currently present categories before switching/loading
                snapshotCurrentWorkerCategories();

                $.get(ROUTE_GET_BY_DATE, {
                    project_site_id: siteId,
                    date: date
                }, function (response) {
                    currentWeekSavedMap = response.week_saved || {};
                    currentAmWagePayment = response.am_wage_payment || null;
                    renderCurrentWeekStrip();

                    $('#workerAllocationBody').empty();

                    const existing = response.allocation;
                    const hasServerAmPayment = !!(currentAmWagePayment && currentAmWagePayment.has_payment);

                    if (existing && existing.items && existing.items.length > 0) {
                        // Existing records in table: show that (no change)
                        existing.items.forEach(function (w) {
                            if (hasServerAmPayment && !w.paid_by && isPainterElectricianOrAmTrade(w.category_code, w.name_en, w.name_ta)) {
                                w.paid_by = 'Account Manager';
                            }
                            if (!w.category_code && !w.worker_category_id) {
                                const otherRow = $(createOtherWorkerRowHtml(w.paid_by, w.wage_cash_source, w.ledger_id));
                                otherRow.find('.other-worker-cat-en').val(w.name_en || '');
                                otherRow.find('.other-worker-cat-ta').val(w.name_ta || '');
                                otherRow.find('.labour-rate-input').val(w.daily_wage || 0);
                                otherRow.find('.labour-count-input').val(w.worker_count || 0);
                                $('#workerAllocationBody').append(otherRow);
                            } else {
                                $('#workerAllocationBody').append(
                                    createWorkerRowHtml(
                                        w.category_code,
                                        w.name_ta,
                                        w.name_en,
                                        w.daily_wage,
                                        w.worker_count,
                                        w.master_wage || w.daily_wage,
                                        w.worker_category_id,
                                        w.paid_by,
                                        w.wage_cash_source,
                                        w.ledger_id
                                    )
                                );
                            }
                        });
                        // Sync retained categories with the loaded existing items
                        snapshotCurrentWorkerCategories();
                    } else {
                        // Days with new / unsaved record: don't reset selected worker categories
                        if (retainedWorkerCategories && retainedWorkerCategories.length > 0) {
                            retainedWorkerCategories.forEach(function (w) {
                                if (w.isOther) {
                                    const otherRow = $(createOtherWorkerRowHtml(w.paid_by, w.wage_cash_source, w.ledger_id));
                                    otherRow.find('.other-worker-cat-en').val(w.name_en || '');
                                    otherRow.find('.other-worker-cat-ta').val(w.name_ta || '');
                                    otherRow.find('.labour-rate-input').val(w.daily_wage || 0);
                                    otherRow.find('.labour-count-input').val(0);
                                    $('#workerAllocationBody').append(otherRow);
                                } else {
                                    $('#workerAllocationBody').append(
                                        createWorkerRowHtml(
                                            w.category_code,
                                            w.name_ta,
                                            w.name_en,
                                            w.daily_wage || w.master_wage,
                                            0,
                                            w.master_wage || w.daily_wage,
                                            w.worker_category_id,
                                            w.paid_by,
                                            w.wage_cash_source,
                                            w.ledger_id
                                        )
                                    );
                                }
                            });
                        } else {
                            $('#workerAllocationBody').html(`
                                        <tr id="emptyWorkerRow">
                                            <td colspan="5" class="text-center py-4 text-muted">
                                                <i class="bi bi-people text-secondary fs-3 d-block mb-1"></i>
                                                No worker categories added yet. Click <strong>"Workers Category"</strong> above to add categories.
                                            </td>
                                        </tr>
                                    `);
                        }
                    }

                    // Load expenses
                    $('#dailyExpensesBody').empty();
                    if (existing && existing.expenses && existing.expenses.length > 0) {
                        existing.expenses.forEach(function (e) {
                            const row = $(createExpenseRowHtml(e));
                            $('#dailyExpensesBody').append(row);
                            setupExpenseAutocomplete(row.find('.expense-autocomplete-wrapper'));
                        });
                    }

                    recalculateDaily();
                    applyDataEntryMode();
                    renderHistoryTable();
                }).fail(function () {
                    showToast('Failed to load allocation data from server.', 'danger');
                });
            }

            // Site Change Handler
            $('#dailyAllocSiteSelect').on('change', function () {
                const currentSiteName = $('#dailyAllocSiteSelect option:selected').data('name') || $('#dailyAllocSiteSelect option:selected').text();
                $('#historySiteTitle').text(currentSiteName);
                loadAllocationForCurrentSelection();
            });

            // Save Daily Allocation (Saves to DB and rolls into Weekly Wages automatically)
            $('#saveDailyAllocBtn').on('click', function () {
                const date = $('#dailyAllocDate').val();
                const siteId = $('#dailyAllocSiteSelect').val();
                const siteName = $('#dailyAllocSiteSelect option:selected').data('name') || $('#dailyAllocSiteSelect option:selected').text();

                if (!date) {
                    showToast('Please select an entry date.', 'danger');
                    return;
                }

                if (!siteId) {
                    showToast('Please select a project site.', 'danger');
                    return;
                }

                // Block putting data for future dates and Sundays
                if (date > todayStr) {
                    showToast(`Data entry is restricted: Future dates cannot be saved. Only today (${todayStr}) and previous dates can be added or edited.`, 'warning');
                    return;
                }
                const chosen = new Date(date + 'T00:00:00');
                if (chosen.getDay() === 0) {
                    showToast('Sundays are non-working site holidays. Cannot save allocation for Sunday.', 'warning');
                    return;
                }

                let totalLabourCount = 0;
                let totalLabourWage = 0;
                let workers = [];
                let tradeSummary = [];

                let hasValidationErr = false;
                $('.labour-row').each(function () {
                    const isOther = $(this).hasClass('is-other-row') || $(this).data('is-other');
                    let cat = $(this).data('cat');
                    let workerCategoryId = $(this).data('worker-category-id') || null;
                    let nameTa, nameEn;

                    if (isOther) {
                        nameEn = $(this).find('.other-worker-cat-en').val().trim();
                        nameTa = $(this).find('.other-worker-cat-ta').val().trim();
                        if (!nameEn) {
                            showToast('Category Name in English is required .', 'warning');
                            $(this).find('.other-worker-cat-en').focus();
                            hasValidationErr = true;
                            return false;
                        }
                        if (!nameTa) {
                            showToast('Category Name in Tamil is required .', 'warning');
                            $(this).find('.other-worker-cat-ta').focus();
                            hasValidationErr = true;
                            return false;
                        }
                    } else {
                        nameTa = $(this).data('name-ta');
                        nameEn = $(this).data('name-en');
                    }

                    const masterRate = parseFloat($(this).data('master-rate')) || 0;
                    const count = parseInt($(this).find('.labour-count-input').val()) || 0;
                    const rate = parseFloat($(this).find('.labour-rate-input').val()) || 0;
                    const subtotal = Math.round(count * rate);

                    if (count > 0) {
                        totalLabourCount += count;
                        totalLabourWage += subtotal;
                        tradeSummary.push(`${count} ${nameTa}`);
                    }

                    workers.push({
                        worker_category_id: workerCategoryId,
                        category_code: cat,
                        name_ta: nameTa,
                        name_en: nameEn,
                        worker_count: count,
                        daily_wage: rate,
                        master_wage: masterRate || rate,
                        subtotal: subtotal,
                        paid_by: $(this).attr('data-paid-by') || $(this).data('paid-by') || null,
                        wage_cash_source: $(this).attr('data-wage-source') || $(this).data('wage-source') || null,
                        ledger_id: $(this).attr('data-ledger-id') || $(this).data('ledger-id') || null
                    });
                });

                if (hasValidationErr) return;

                let expenses = [];
                let totalExpenses = 0;
                $('.expense-row').each(function () {
                    const title = $(this).find('.expense-title').val().trim();
                    const qty = parseFloat($(this).find('.expense-qty').val()) || 0;
                    const unitPrice = parseFloat($(this).find('.expense-unit-price').val()) || 0;
                    const amount = Math.round(qty * unitPrice);

                    if (title && amount > 0) {
                        expenses.push({
                            item_name: title,
                            quantity: qty,
                            unit_price: unitPrice,
                            amount: amount
                        });
                        totalExpenses += amount;
                    }
                });

                const grandTotal = totalLabourWage + totalExpenses;

                const payload = {
                    project_site_id: siteId,
                    date: date,
                    total_labour_count: totalLabourCount,
                    total_labour_wage: totalLabourWage,
                    total_expenses: totalExpenses,
                    grand_total: grandTotal,
                    trade_summary: tradeSummary.join(' + '),
                    items: workers,
                    expenses: expenses
                };

                const saveBtn = $('#saveDailyAllocBtn');
                saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');

                $.ajax({
                    url: ROUTE_STORE_ALLOCATION,
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify(payload),
                    success: function (res) {
                        saveBtn.prop('disabled', false).html('<i class="bi bi-floppy2-fill"></i> <span>Save Daily Allocation</span>');
                        currentWeekSavedMap[date] = true;
                        renderCurrentWeekStrip();
                        renderHistoryTable();
                        if (res.worker_categories && res.worker_categories.length) {
                            updateDropdownCategories(res.worker_categories);
                        }
                        if (res.materials && res.materials.length) {
                            availableMaterials = res.materials;
                        }
                        showToast(`Daily Allocation for "${siteName}" on ${date} saved successfully! Rolls into Weekly Wages.`, 'success');
                    },
                    error: function (xhr) {
                        saveBtn.prop('disabled', false).html('<i class="bi bi-floppy2-fill"></i> <span>Save Daily Allocation</span>');
                        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to save daily allocation.';
                        showToast(msg, 'danger');
                    }
                });
            });

            // Handle URL params if coming from Site details or other links (?site_id=...&date=...)
            const urlParams = new URLSearchParams(window.location.search);
            const paramSiteId = urlParams.get('site_id');
            const paramDate = urlParams.get('date');
            if (paramSiteId && $(`#dailyAllocSiteSelect option[value="${paramSiteId}"]`).length > 0) {
                $('#dailyAllocSiteSelect').val(paramSiteId);
                const sName = $('#dailyAllocSiteSelect option:selected').data('name') || $('#dailyAllocSiteSelect option:selected').text();
                $('#historySiteTitle').text(sName);
            }
            if (paramDate) {
                $('#dailyAllocDate').val(paramDate);
                if (typeof flatpickrInstance !== 'undefined' && flatpickrInstance) {
                    flatpickrInstance.setDate(paramDate, false);
                }
                updateDayName();
                applyDataEntryMode();
            }

            // Initial load
            loadAllocationForCurrentSelection();
        });
    </script>
@endpush