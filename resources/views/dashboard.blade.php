@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard Overview (முகப்பு பலகை)')

@section('content')
    @php
        $dashUser = auth()->user();
        $reportTargetUrl = match (true) {
            ($dashUser?->isSiteManager() && $dashUser?->canAccess('reports_labour_allocations')) => route('reports.site.labour-allocations'),
            ($dashUser?->isSiteManager() && $dashUser?->canAccess('reports_cash_handling')) => route('reports.site.cash-handling'),
            ($dashUser?->isSiteManager() && $dashUser?->canAccess('reports_purchases_inward')) => route('reports.site.purchases-inward'),
            ($dashUser?->isSiteManager() && $dashUser?->canAccess('reports_warehouse_inventory')) => route('reports.site.warehouse-inventory'),
            ($dashUser?->isAccountManager() && $dashUser?->canAccess('reports_ledger')) => route('reports.account.ledger'),
            $dashUser?->canAccess('reports_profit_loss') => route('reports.admin.profit-loss'),
            $dashUser?->canAccess('reports_labour_allocations') => route('reports.site.labour-allocations'),
            $dashUser?->canAccess('reports_cash_handling') => route('reports.site.cash-handling'),
            $dashUser?->canAccess('reports_purchases_inward') => route('reports.site.purchases-inward'),
            $dashUser?->canAccess('reports_warehouse_inventory') => route('reports.site.warehouse-inventory'),
            $dashUser?->canAccess('reports_ledger') => route('reports.account.ledger'),
            default => null,
        };
    @endphp
    <div class="container-fluid px-0">

        <!-- Top Welcome Banner -->
        <div class="card-custom p-3 p-md-4 mb-3 mb-md-4 border-start border-4 border-primary bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h4 class="fw-bold mb-0 text-dark fs-5 fs-md-4">Welcome, <span
                                id="dashGreetingUser">{{ Auth::user() ? Auth::user()->name . ' (' . (Auth::user()->role?->label() ?? 'User') . ')' : 'Murugan (Site Manager)' }}</span>!
                        </h4>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                            id="dashRolePill">{{ Auth::user()?->role?->label() ?? 'Site Manager' }}</span>
                    </div>
                    <p class="text-muted mb-0 small">
                        <span class="ta-text text-secondary">
                            @if (Auth::user()?->isAccountManager())
                                தினசரி பேரேடு, வரவு-செலவு, வங்கிகள், வாடகை மற்றும் சப்ளையர் கணக்குகள்.
                            @elseif (Auth::user()?->isAdmin())
                                நிறுவனத்தின் வரவு-செலவு, தளங்கள், தொழிலாளர் மற்றும் கொள்முதல் ஒட்டுமொத்த சுருக்கம்.
                            @else
                                மேஸ்திரி, கொத்தனார் தினக்கூலி மற்றும் வரவு-செலவு சுருக்கம்.
                            @endif
                        </span>
                        @if (!Auth::user()?->isAdmin())
                            <span class="d-none d-sm-inline">&bull; Today: <strong
                                    class="text-dark">{{ $todayFormatted ?? \Carbon\Carbon::now()->format('l, d M Y') }}</strong></span>
                        @endif
                    </p>
                </div>
                @if (!Auth::user()?->isAdmin())
                    <div class="d-flex gap-2 flex-wrap">
                        @if (auth()->user()?->canAccess('daily_allocation') ?? true)
                            <a href="{{ url('/daily-allocation') }}" class="btn btn-deiveni btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-calendar-check"></i>
                                <span>Daily Allocation</span>
                            </a>
                        @endif
                        @if (auth()->user()?->canAccess('weekly_wages') ?? true)
                            <a href="{{ url('/weekly-wages') }}"
                                class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-journal-text"></i>
                                <span>Weekly Wages</span>
                            </a>
                        @endif
                        @if (auth()->user()?->canAccess('site_cash') ?? true)
                            <a href="{{ url('/site-cash') }}"
                                class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-cash-stack"></i>
                                <span>Site Cash</span>
                            </a>
                        @endif
                        @if (auth()->user()?->canAccess('banks') ?? true)
                            <a href="{{ url('/banks') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-bank"></i>
                                <span>Bank Master</span>
                            </a>
                        @endif
                        @if (auth()->user()?->canAccess('ledger') ?? true)
                            <a href="{{ url('/ledger') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-book"></i>
                                <span>Daily Ledger</span>
                            </a>
                        @endif
                        @if ($reportTargetUrl)
                            <a href="{{ $reportTargetUrl }}" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
                                <i class="bi bi-file-earmark-bar-graph"></i>
                                <span>Reports & Export</span>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if (auth()->user()?->isAdmin())
            <!-- Admin Period Filter Bar -->
            <div class="card-custom p-2 p-md-3 mb-3 bg-white border">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @php
                            $periodOptions = [
                                'today' => ['en' => 'Today', 'ta' => 'இன்று', 'icon' => 'bi-calendar-day'],
                                'this_week' => ['en' => 'This Week', 'ta' => 'இந்த வாரம்', 'icon' => 'bi-calendar-week'],
                                'this_month' => ['en' => 'This Month', 'ta' => 'இந்த மாதம்', 'icon' => 'bi-calendar-month'],
                                'this_year' => ['en' => 'This Year', 'ta' => 'இந்த ஆண்டு', 'icon' => 'bi-calendar'],
                            ];
                        @endphp
                        <div class="btn-group btn-group-sm" role="group">
                            @foreach ($periodOptions as $pKey => $pMeta)
                                <a href="{{ route('dashboard', ['period' => $pKey]) }}"
                                    class="btn {{ ($selectedPeriod ?? 'today') === $pKey ? 'btn-primary active fw-bold' : 'btn-outline-secondary' }}">
                                    <i class="bi {{ $pMeta['icon'] }} me-1"></i>{{ $pMeta['en'] }} ({{ $pMeta['ta'] }})
                                </a>
                            @endforeach
                        </div>

                        <!-- Custom Datepicker Form -->
                        <form method="GET" action="{{ route('dashboard') }}"
                            class="d-flex align-items-center gap-1 ms-sm-2 flex-wrap" id="customDateRangeForm">
                            <input type="hidden" name="period" value="custom">
                            <div class="input-group input-group-sm" style="width: auto;">
                                <span class="input-group-text bg-white border-end-0 py-1">
                                    <i class="bi bi-calendar-event text-primary"></i>
                                </span>
                                <input type="date" name="start_date" id="dashCustomStart"
                                    class="form-control form-control-sm border-start-0"
                                    value="{{ $customStartDate ?? $periodStart ?? '' }}" title="Start Date">
                                <span class="input-group-text bg-light py-1">&sim;</span>
                                <input type="date" name="end_date" id="dashCustomEnd" class="form-control form-control-sm"
                                    value="{{ $customEndDate ?? $periodEnd ?? '' }}" title="End Date">
                                <button type="submit" class="btn btn-sm btn-outline-primary py-1">
                                    <i class="bi bi-filter"></i> Apply
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-2 px-3 small">
                            <i class="bi bi-clock-history me-1"></i> {{ $periodLabel ?? 'Today' }}
                            ({{ $periodLabelTa ?? 'இன்று' }}):
                            <strong>{{ \Carbon\Carbon::parse($periodStart ?? now())->format('d M Y') }}</strong>
                            @if (($periodStart ?? '') !== ($periodEnd ?? ''))
                                &sim; <strong>{{ \Carbon\Carbon::parse($periodEnd ?? now())->format('d M Y') }}</strong>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Summary KPI Cards -->
        <div class="row g-2 g-md-3 mb-3 mb-md-4">
            @if (auth()->user()?->isAccountManager())
                <!-- Account Manager Specific KPIs -->
                <div class="col-6 col-lg-2">
                    <a href="{{ route('ledger.index', ['flow' => 'income']) }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Today's Income</span>
                                <div class="stat-icon-wrapper bg-income-subtle">
                                    <i class="bi bi-arrow-down-left-circle-fill"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-success mb-1 fs-5 fs-md-4">₹{{ number_format($todayLedgerIncome ?? 0) }}
                            </h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">வரவு (Dr)</span>
                                <span class="badge bg-success-subtle text-success ms-auto">
                                    {{ ($todayIncomeCount ?? 0) > 0 ? $todayIncomeCount . ' ' . \Illuminate\Support\Str::plural('entry', $todayIncomeCount) : '0 entries' }}
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ route('ledger.index', ['flow' => 'expense']) }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Today's Expense</span>
                                <div class="stat-icon-wrapper bg-expense-subtle">
                                    <i class="bi bi-arrow-up-right-circle-fill"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-danger mb-1 fs-5 fs-md-4">₹{{ number_format($todayLedgerExpense ?? 0) }}
                            </h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">செலவு (Cr)</span>
                                <span class="badge bg-danger-subtle text-danger ms-auto">
                                    {{ ($todayExpenseCount ?? 0) > 0 ? $todayExpenseCount . ' ' . \Illuminate\Support\Str::plural('bill', $todayExpenseCount) : '0 bills' }}
                                </span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ route('ledger.index') }}" class="text-decoration-none">
                        <div class="card-stat h-100 border-info border-opacity-50">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Running Balance</span>
                                <div class="stat-icon-wrapper bg-income-subtle">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-info mb-1 fs-5 fs-md-4">
                                {{ ($ledgerClosingBalance ?? 0) < 0 ? '-' : '' }}₹{{ number_format(abs($ledgerClosingBalance ?? 0)) }}
                            </h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">கையிருப்பு</span>
                                <span class="text-info fw-bold ms-auto">Live</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ route('banks.index') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Bank Accounts</span>
                                <div class="stat-icon-wrapper bg-workers-subtle">
                                    <i class="bi bi-bank"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-primary mb-1 fs-5 fs-md-4">
                                {{ $activeBanksCount ?? 0 }} <small
                                    class="fs-6 text-muted fw-normal">{{ $activeBranchesCount ?? 0 }}
                                    {{ \Illuminate\Support\Str::plural('Branch', $activeBranchesCount ?? 0) }}</small>
                            </h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">வங்கிகள்</span>
                                <span class="badge bg-primary-subtle text-primary ms-auto">Active</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ route('property-types.index') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Rental Units</span>
                                <div class="stat-icon-wrapper bg-sites-subtle">
                                    <i class="bi bi-buildings"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-warning mb-1 fs-5 fs-md-4">
                                {{ $activeRentalUnitsCount ?? 0 }} <small
                                    class="fs-6 text-muted fw-normal">{{ $activePropertiesCount ?? 0 }}
                                    {{ \Illuminate\Support\Str::plural('Property', $activePropertiesCount ?? 0) }}</small>
                            </h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">வாடகை</span>
                                <span class="badge bg-warning-subtle text-dark ms-auto">Active</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ route('suppliers.index') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Active Suppliers</span>
                                <div class="stat-icon-wrapper bg-warehouse-subtle">
                                    <i class="bi bi-truck"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 fs-5 fs-md-4" style="color: #7c3aed;">
                                {{ $activeSuppliersCount ?? 0 }} <small class="fs-6 text-muted fw-normal">Suppliers</small>
                            </h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">சப்ளையர்கள்</span>
                                <span class="badge bg-secondary-subtle text-dark ms-auto">
                                    {{ ($pendingSupplierPayables ?? 0) > 0 ? '₹' . number_format($pendingSupplierPayables) . ' Due' : 'Active' }}
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @elseif (auth()->user()?->isSiteManager())
                <!-- Site Manager Specific KPIs -->
                <div class="col-6 col-lg-2">
                    <a href="{{ url('/sites') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Active Sites</span>
                                <div class="stat-icon-wrapper bg-sites-subtle">
                                    <i class="bi bi-building-fill-gear"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-warning mb-1 fs-5 fs-md-4">{{ $activeSitesCount ?? 0 }} <small
                                    class="fs-6 text-muted fw-normal">Sites</small></h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">தளங்கள்</span>
                                <span class="badge bg-warning-subtle text-dark ms-auto">{{ $completedSitesCount ?? 0 }}
                                    Done</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ url('/daily-allocation') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Labour on Site</span>
                                <div class="stat-icon-wrapper bg-workers-subtle">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-primary mb-1 fs-5 fs-md-4">{{ $labourCount ?? 0 }} <small
                                    class="fs-6 text-muted fw-normal">Present</small></h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">தொழிலாளர்கள்</span>
                                <span class="text-primary fw-semibold ms-auto">{{ $labourSitesBadge ?? '0 Sites' }}</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ url('/weekly-wages') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Weekly Wages</span>
                                <div class="stat-icon-wrapper bg-income-subtle">
                                    <i class="bi bi-journal-text"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-primary mb-1 fs-5 fs-md-4">₹{{ number_format($currentWeekWages ?? 0) }}</h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">வார கூலி</span>
                                <span class="badge bg-success-subtle text-success ms-auto">Due Sat</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ url('/site-cash') }}" class="text-decoration-none">
                        <div class="card-stat h-100 border-success border-opacity-50">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Site Cash</span>
                                <div class="stat-icon-wrapper bg-cash-subtle">
                                    <i class="bi bi-wallet2"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-success mb-1 fs-5 fs-md-4">₹{{ number_format($cashInHand ?? 0) }}</h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">ரொக்கம்</span>
                                <span class="text-success fw-bold ms-auto">Live</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ url('/worker-categories') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Worker Categories</span>
                                <div class="stat-icon-wrapper bg-warehouse-subtle">
                                    <i class="bi bi-person-badge-fill"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-1 fs-5 fs-md-4" style="color: #7c3aed;">{{ $workerCategoriesCount ?? 0 }}
                                <small class="fs-6 text-muted fw-normal">Trades</small></h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">பிரிவுகள்</span>
                                <span class="badge bg-primary-subtle text-primary ms-auto">Wages set</span>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-6 col-lg-2">
                    <a href="{{ url('/warehouse') }}" class="text-decoration-none">
                        <div class="card-stat h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="text-muted small fw-semibold">Warehouse Store</span>
                                <div class="stat-icon-wrapper bg-expense-subtle">
                                    <i class="bi bi-box-seam-fill"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold text-dark mb-1 fs-5 fs-md-4">{{ $warehouseTotalItems ?? 0 }} <small
                                    class="fs-6 text-muted fw-normal">Items</small></h4>
                            <div class="d-flex align-items-center small text-muted">
                                <span class="ta-text" style="font-size:0.75rem;">கிடங்கு</span>
                                <span
                                    class="badge bg-info-subtle text-info ms-auto">{{ ($warehouseLowStockCount ?? 0) > 0 ? $warehouseLowStockCount . ' Low' : 'In stock' }}</span>
                            </div>
                        </div>
                    </a>
                </div>
            @else
                <!-- Admin Standard Cards: Clean 4-card layout without hyperlinks -->
                <div class="col-6 col-lg-3">
                    <div class="card-stat h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span
                                class="text-muted small fw-semibold">{!! ($selectedPeriod ?? 'today') === 'today' ? "Today's Income" : ($periodLabel . " Income") !!}</span>
                            <div class="stat-icon-wrapper bg-income-subtle">
                                <i class="bi bi-arrow-down-left-circle-fill"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-success mb-1 fs-5 fs-md-4">
                            ₹{{ number_format(($selectedPeriod ?? 'today') === 'today' ? ($todayLedgerIncome ?? 0) : ($periodIncome ?? 0)) }}
                        </h4>
                        <div class="d-flex align-items-center small text-muted">
                            <span class="ta-text" style="font-size:0.75rem;">வரவு (Dr) &bull;
                                {{ $periodLabelTa ?? 'இன்று' }}</span>
                            <span class="badge bg-success-subtle text-success ms-auto">
                                {{ (($selectedPeriod ?? 'today') === 'today' ? ($todayIncomeCount ?? 0) : ($periodIncomeCount ?? 0)) }}
                                {{ \Illuminate\Support\Str::plural('entry', ($selectedPeriod ?? 'today') === 'today' ? ($todayIncomeCount ?? 0) : ($periodIncomeCount ?? 0)) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card-stat h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span
                                class="text-muted small fw-semibold">{!! ($selectedPeriod ?? 'today') === 'today' ? "Today's Expense" : ($periodLabel . " Expense") !!}</span>
                            <div class="stat-icon-wrapper bg-expense-subtle">
                                <i class="bi bi-arrow-up-right-circle-fill"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-danger mb-1 fs-5 fs-md-4">
                            ₹{{ number_format(($selectedPeriod ?? 'today') === 'today' ? ($todayLedgerExpense ?? 0) : ($periodExpense ?? 0)) }}
                        </h4>
                        <div class="d-flex align-items-center small text-muted">
                            <span class="ta-text" style="font-size:0.75rem;">செலவு (Cr) &bull;
                                {{ $periodLabelTa ?? 'இன்று' }}</span>
                            <span class="badge bg-danger-subtle text-danger ms-auto">
                                {{ (($selectedPeriod ?? 'today') === 'today' ? ($todayExpenseCount ?? 0) : ($periodExpenseCount ?? 0)) }}
                                {{ \Illuminate\Support\Str::plural('bill', ($selectedPeriod ?? 'today') === 'today' ? ($todayExpenseCount ?? 0) : ($periodExpenseCount ?? 0)) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card-stat h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small fw-semibold">Active Sites</span>
                            <div class="stat-icon-wrapper bg-sites-subtle">
                                <i class="bi bi-building-fill-gear"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-warning mb-1 fs-5 fs-md-4">{{ $activeSitesCount ?? 0 }} <small
                                class="fs-6 text-muted fw-normal">Sites</small></h4>
                        <div class="d-flex align-items-center small text-muted">
                            <span class="ta-text" style="font-size:0.75rem;">தளங்கள்</span>
                            <span class="badge bg-warning-subtle text-dark ms-auto">{{ $completedSitesCount ?? 0 }} Done</span>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-lg-3">
                    <div class="card-stat h-100 border-success border-opacity-50">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="text-muted small fw-semibold">Cash in Hand</span>
                            <div class="stat-icon-wrapper bg-cash-subtle">
                                <i class="bi bi-wallet2"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-success mb-1 fs-5 fs-md-4">₹{{ number_format($cashInHand ?? 0) }}</h4>
                        <div class="d-flex align-items-center small text-muted">
                            <span class="ta-text" style="font-size:0.75rem;">ரொக்கம்</span>
                            <span class="text-success fw-bold ms-auto">Live</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Charts Row -->
        <div class="row g-3 g-md-4 mb-3 mb-md-4">
            <!-- Cashflow / Income vs Expense Trend -->
            <div class="col-12 col-lg-8">
                <div class="card-custom p-3 p-md-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Income & Expense Overview (வரவு - செலவு போக்கு)</h6>
                            <small class="text-muted">
                                @if (($selectedPeriod ?? 'today') === 'today')
                                    7-Day Activity Trend ending Today (இன்றைய 7 நாள் போக்கு)
                                @elseif (($selectedPeriod ?? '') === 'this_week')
                                    Past 7 Days Activity (இந்த வார சுருக்கம்)
                                @elseif (($selectedPeriod ?? '') === 'this_month')
                                    This Month Daily Activity (இந்த மாத சுருக்கம்)
                                @else
                                    This Year Monthly Activity (இந்த ஆண்டு சுருக்கம்)
                                @endif
                            </small>
                        </div>
                        <span
                            class="badge bg-success-subtle text-success border border-success-subtle d-none d-sm-inline">{{ ($sevenDayNet ?? 0) >= 0 ? '+' : '' }}₹{{ number_format($sevenDayNet ?? 0) }}
                            Net</span>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="incomeExpenseChart"></canvas>
                    </div>
                    <div class="row text-center mt-3 pt-3 border-top g-2">
                        <div class="col-4">
                            <div class="text-muted small" style="font-size: 0.75rem;">{{ $periodLabel ?? 'Period' }} Income
                                (வரவு)</div>
                            <div class="fw-bold text-success fs-6">₹{{ number_format($sevenDayIncome ?? 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small" style="font-size: 0.75rem;">{{ $periodLabel ?? 'Period' }} Expense
                                (செலவு)</div>
                            <div class="fw-bold text-danger fs-6">₹{{ number_format($sevenDayExpense ?? 0) }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small" style="font-size: 0.75rem;">Net Balance (மீதி)</div>
                            <div class="fw-bold text-primary fs-6">
                                {{ ($sevenDayNet ?? 0) >= 0 ? '+' : '' }}₹{{ number_format($sevenDayNet ?? 0) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @if (auth()->user()?->isAccountManager())
                <!-- Bank Master Summary for Account Manager -->
                <div class="col-12 col-lg-4">
                    <div class="card-custom p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Bank Master (வங்கி விவரங்கள்)</h6>
                                    <small class="text-muted">Active Bank Accounts & Branches</small>
                                </div>
                                <span class="badge bg-primary-subtle text-primary">{{ $activeBanksCount ?? 0 }} Banks</span>
                            </div>
                            <div class="list-group list-group-flush small mb-3">
                                @forelse ($activeBanksList ?? [] as $bank)
                                    <div
                                        class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-0">
                                        <div class="me-2 text-truncate">
                                            <span class="fw-semibold d-block text-dark text-truncate">{{ $bank->bank_name }}</span>
                                            <small class="text-muted">
                                                {{ $bank->branches_count }}
                                                {{ \Illuminate\Support\Str::plural('Branch', $bank->branches_count) }}
                                                @if ($bank->branches && $bank->branches->isNotEmpty())
                                                    ({{ $bank->branches->pluck('branch_name')->take(2)->join(', ') }})
                                                @endif
                                                @if ($bank->bank_name_ta)
                                                    &bull; {{ $bank->bank_name_ta }}
                                                @endif
                                            </small>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary flex-shrink-0">
                                            {{ $bank->branches_count }}
                                            {{ \Illuminate\Support\Str::plural('Branch', $bank->branches_count) }}
                                        </span>
                                    </div>
                                @empty
                                    <div class="list-group-item px-0 py-3 text-muted border-0 small text-center">
                                        No active bank records found.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted">
                                <i class="bi bi-bank me-1"></i> {{ $activeBanksCount ?? 0 }} Banks
                                ({{ $activeBranchesCount ?? 0 }} Branches)
                            </span>
                            <a href="{{ route('banks.index') }}" class="btn btn-sm btn-outline-primary py-1">View Banks</a>
                        </div>
                    </div>
                </div>
            @elseif (auth()->user()?->isAdmin())
                <!-- Expense Distribution by Source for Admin -->
                <div class="col-12 col-lg-4">
                    <div class="card-custom p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Expense Distribution (செலவு வகைப்பாடு)</h6>
                                    <small class="text-muted">By Category & Source</small>
                                </div>
                                <span
                                    class="badge bg-danger-subtle text-danger">₹{{ number_format($totalExpenseBreakdown ?? 0) }}</span>
                            </div>
                            <div style="height: 180px; position: relative;">
                                <canvas id="expenseDonutChart"></canvas>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-dark"><i class="bi bi-pie-chart-fill text-primary me-1"></i> Top
                                    Expense Sectors</span>
                                <a href="{{ route('reports.admin.profit-loss') }}"
                                    class="small text-primary text-decoration-none">P&L Report &rarr;</a>
                            </div>
                            <div class="list-group list-group-flush small">
                                @forelse ($topExpenseItems ?? [] as $item)
                                    <div
                                        class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center border-0">
                                        <span class="fw-semibold text-truncate me-2" style="max-width: 190px;"
                                            title="{{ $item['label'] }}">
                                            {{ $item['label'] }}
                                        </span>
                                        <span class="badge bg-light text-dark border">
                                            ₹{{ number_format((float) $item['value']) }} ({{ $item['percentage'] }}%)
                                        </span>
                                    </div>
                                @empty
                                    <div class="list-group-item px-0 py-1 text-muted border-0 small">
                                        No expense records found.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @elseif (auth()->user()?->canAccess('worker_categories') ?? true)
                <!-- Worker Category Distribution Breakdown -->
                <div class="col-12 col-lg-4">
                    <div class="card-custom p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-dark">Trade Category Breakdown</h6>
                                <span class="badge bg-primary-subtle text-primary">{{ $totalTradeLabourers ?? 0 }}
                                    Labourers</span>
                            </div>
                            <div style="height: 180px; position: relative;">
                                <canvas id="workersPieChart"></canvas>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-dark"><i class="bi bi-tag-fill text-primary me-1"></i> Trade
                                    Wage Rates</span>
                                <a href="{{ url('/worker-categories') }}"
                                    class="small text-primary text-decoration-none">Manage</a>
                            </div>
                            <div class="list-group list-group-flush small">
                                @forelse ($tradeWageRates ?? [] as $trade)
                                    <div
                                        class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center border-0">
                                        <span class="fw-semibold">{{ $trade->name_ta }} ({{ $trade->name_en }})</span>
                                        <span
                                            class="badge bg-primary-subtle text-primary">₹{{ number_format((float) $trade->daily_wage) }}
                                            / day</span>
                                    </div>
                                @empty
                                    <div class="list-group-item px-0 py-1 text-muted border-0 small">
                                        No active trade categories found.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Active Projects & Live Notebook Daily Wages or Financial Overview -->
        @if (auth()->user()?->isAdmin())
            <!-- Admin Essential Details & Financial Summary -->
            <div class="row g-3 g-md-4 mb-3 mb-md-4">
                <div class="col-12 col-md-6">
                    <div class="card-custom p-3 p-md-4 h-100 bg-white border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted">Supplier Payables (சப்ளையர் பாக்கி)</span>
                            <span class="badge bg-danger-subtle text-danger">Payables</span>
                        </div>
                        <h3 class="fw-bold text-danger mb-1">
                            ₹{{ number_format($pendingSupplierPayables ?? 0) }}
                        </h3>
                        <div class="small text-muted d-flex justify-content-between pt-2 border-top mt-2">
                            <span>Suppliers: <strong>{{ $activeSuppliersCount ?? 0 }} Active</strong></span>
                            <span>Period Purchases: <strong>₹{{ number_format($periodPurchasesTotal ?? 0) }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="card-custom p-3 p-md-4 h-100 bg-white border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-muted">Purchase Orders Overview (கொள்முதல் விவரம்)</span>
                            <span class="badge bg-primary-subtle text-primary">{{ $totalPurchaseOrdersCount ?? 0 }}
                                Orders</span>
                        </div>
                        <h3 class="fw-bold text-primary mb-1">
                            ₹{{ number_format($totalSupplierPurchases ?? 0) }}
                        </h3>
                        <div class="small text-muted d-flex justify-content-between pt-2 border-top mt-2">
                            <span>Total Orders: <strong>{{ $totalPurchaseOrdersCount ?? 0 }} POs</strong></span>
                            <span>Materials Catalog: <strong>{{ $totalSupplierMaterialsCount ?? 0 }} Items</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if (!auth()->user()?->isAccountManager() && (auth()->user()?->canAccess('sites') ?? true))
            <div class="row g-3 g-md-4 mb-3 mb-md-4">
                <!-- Active Project Sites Summary -->
                <div class="col-12 col-lg-6">
                    <div class="card-custom p-3 p-md-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Active Project Sites (தளங்கள்)</h6>
                                <small class="text-muted">Ongoing site progress and cost tracking</small>
                            </div>
                            <a href="{{ url('/sites') }}" class="btn btn-outline-primary btn-sm">All Sites</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>Site & Client</th>
                                        <th>Type</th>
                                        <th>Estimate</th>
                                        <th>Spent</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($activeSites ?? [] as $site)
                                        <tr>
                                            <td>
                                                <a href="{{ route('sites.show', $site->encrypted_id) }}"
                                                    class="text-decoration-none text-dark fw-bold hover-primary">
                                                    {{ $site->name }}
                                                </a>
                                                <div class="text-muted small">{{ $site->location }} &bull;
                                                    {{ $site->client?->name ?? 'N/A' }}</div>
                                            </td>
                                            <td><span class="badge bg-info-subtle text-info border">{{ $site->work_type }}</span>
                                            </td>
                                            <td class="fw-semibold">₹{{ number_format((float) $site->estimated_budget) }}</td>
                                            <td class="text-danger fw-semibold">₹{{ number_format((float) $site->spent) }}</td>
                                            <td>
                                                @php
                                                    $statusBadgeClass = match ($site->status) {
                                                        'Completed' => 'bg-success-subtle text-success',
                                                        'Planning' => 'bg-info-subtle text-info',
                                                        'On Hold' => 'bg-secondary-subtle text-dark',
                                                        default => 'bg-warning-subtle text-dark',
                                                    };
                                                @endphp
                                                <span class="badge {{ $statusBadgeClass }}">{{ $site->status }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">No active project sites found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Today's Site Daily Entry or Recent Activity for Admin -->
                <div class="col-12 col-lg-6">
                    @if (auth()->user()?->isAdmin())
                        <!-- Recent Financial Activity Log for Admin -->
                        <div class="card-custom p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Recent Ledger Transactions (சமீபத்திய கணக்கு பதிவுகள்)
                                        </h6>
                                        <small class="text-muted">Latest financial credit and debit vouchers</small>
                                    </div>
                                    <a href="{{ route('ledger.index') }}" class="btn btn-outline-primary btn-sm">Daily Ledger</a>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-custom table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Party / Description</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($recentLedgers ?? [] as $entry)
                                                <tr>
                                                    <td>
                                                        <span class="text-muted small">
                                                            @if ($entry->entry_date?->isToday())
                                                                <span class="badge bg-primary-subtle text-primary">Today</span>
                                                            @else
                                                                {{ $entry->entry_date?->format('d M') }}
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark text-truncate" style="max-width: 180px;"
                                                            title="{{ $entry->description ?: ($entry->transaction_type_name ?: $entry->source) }}">
                                                            {{ $entry->description ?: ($entry->transaction_type_name ?: $entry->source) }}
                                                        </div>
                                                        <small class="text-muted d-block text-truncate" style="max-width: 180px;">
                                                            {{ $entry->source }}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        @if ($entry->flow === 'income')
                                                            <span class="badge bg-success-subtle text-success">வரவு</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">செலவு</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="{{ $entry->flow === 'income' ? 'text-success' : 'text-danger' }} fw-bold small">
                                                            {{ $entry->flow === 'income' ? '+' : '-' }}₹{{ number_format((float) $entry->amount) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">No transactions recorded yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mt-3">
                                <span class="small text-muted">
                                    <i class="bi bi-book-half me-1"></i> Running Ledger Records
                                </span>
                                <a href="{{ route('ledger.index') }}" class="btn btn-sm btn-outline-secondary py-1">View Full
                                    Ledger</a>
                            </div>
                        </div>
                    @else
                        <!-- Today's Notebook Daily Wage Calculation Snapshot for Site Manager -->
                        <div class="card-custom p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Today's Site Daily Entry (நோட்புக் கூலி)</h6>
                                        <small class="text-muted">
                                            @if (!empty($latestAllocation))
                                                {{ $latestAllocation->projectSite?->name ?? 'Site' }} (Formula:
                                                {{ $latestAllocation->trade_summary ?? 'Wage Entry' }})
                                            @else
                                                No daily allocation recorded yet
                                            @endif
                                        </small>
                                    </div>
                                    <a href="{{ url('/weekly-wages') }}" class="btn btn-outline-secondary btn-sm">Full Sheet</a>
                                </div>

                                @if (!empty($latestAllocation))
                                    <!-- Notebook Formula Card -->
                                    <div class="p-3 mb-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold small text-primary"><i class="bi bi-calculator me-1"></i> Wage
                                                Calculation Breakdown:</span>
                                            <span class="badge bg-success">
                                                {{ $latestAllocation->date?->isToday() ? 'Today' : $latestAllocation->date?->format('d M') }}:
                                                ₹{{ number_format((float) $latestAllocation->grand_total) }}
                                            </span>
                                        </div>
                                        <div class="font-monospace text-dark small bg-white p-2 rounded border mb-2">
                                            {!! $latestAllocationFormulaString !!}
                                        </div>
                                        <div class="row text-center g-2 small">
                                            @foreach ($latestAllocationPreviewItems ?? [] as $previewItem)
                                                <div class="col-4 {{ !$loop->last ? 'border-end' : '' }}">
                                                    <span class="text-muted d-block text-truncate"
                                                        style="font-size: 0.72rem;">{{ $previewItem['label'] }}</span>
                                                    <span class="fw-bold">{{ $previewItem['value'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="p-4 text-center my-auto rounded-3 border border-dashed mb-3">
                                        <i class="bi bi-journal-plus text-primary fs-2 mb-2 d-block"></i>
                                        <p class="text-muted small mb-0">No daily allocation logged for today yet. Record daily
                                            attendance to generate wages automatically.</p>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded mt-auto">
                                <span class="small text-muted">
                                    <i class="bi bi-clock-history me-1"></i>
                                    @if (!empty($latestAllocation))
                                        Recorded
                                        {{ $latestAllocation->created_at?->diffForHumans() ?? $latestAllocation->date?->format('d M Y') }}
                                    @else
                                        Ready for today's entry
                                    @endif
                                </span>
                                <a href="{{ url('/daily-allocation') }}" class="btn btn-sm btn-deiveni py-1">Record Daily
                                    Allocation</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @elseif (auth()->user()?->isAccountManager())
            <div class="row g-3 g-md-4 mb-3 mb-md-4">
                <!-- Recent Ledger Entries for Account Manager -->
                <div class="col-12 col-lg-7">
                    <div class="card-custom p-3 p-md-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Recent Ledger Entries (கணக்கு பதிவுகள்)</h6>
                                <small class="text-muted">Latest Credit and Debit notebook vouchers</small>
                            </div>
                            <a href="{{ route('ledger.index') }}" class="btn btn-outline-primary btn-sm">Daily Ledger</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Party / Description</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentLedgers ?? [] as $entry)
                                        <tr>
                                            <td>
                                                <span class="text-muted small">
                                                    @if ($entry->entry_date?->isToday())
                                                        <span class="badge bg-primary-subtle text-primary">Today</span>
                                                    @elseif ($entry->entry_date?->isYesterday())
                                                        <span class="badge bg-light text-secondary border">Yesterday</span>
                                                    @else
                                                        {{ $entry->entry_date?->format('d M Y') }}
                                                    @endif
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark text-truncate" style="max-width: 240px;"
                                                    title="{{ $entry->description ?: ($entry->transaction_type_name ?: $entry->source) }}">
                                                    {{ $entry->description ?: ($entry->transaction_type_name ?: $entry->source) }}
                                                </div>
                                                <small class="text-muted d-block text-truncate" style="max-width: 240px;">
                                                    @if ($entry->source === 'Construction')
                                                        {{ $entry->site_name ?? $entry->projectSite?->name ?? 'Site' }} &bull;
                                                        {{ $entry->client_name ?? $entry->projectSite?->client?->name ?? 'Client' }}
                                                    @elseif ($entry->source === 'Supplier')
                                                        {{ $entry->supplier_name ?? $entry->supplier?->name ?? 'Supplier' }}
                                                    @elseif ($entry->source === 'Bank Valuation' || $entry->source === 'Bank')
                                                        {{ $entry->bank_name ?? $entry->bank?->bank_name ?? 'Bank' }}{{ $entry->branch_name ? ' (' . $entry->branch_name . ')' : '' }}
                                                    @elseif ($entry->source === 'Rental')
                                                        {{ $entry->property_name ?? $entry->property?->property_name ?? 'Rental' }}{{ $entry->unit_name ? ' [' . $entry->unit_name . ']' : '' }}
                                                    @elseif ($entry->source === 'Office and other expenses')
                                                        {{ $entry->office_expense_details ?: 'Office Expense' }}
                                                    @else
                                                        {{ $entry->source }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                @if ($entry->flow === 'income')
                                                    <span class="badge bg-success-subtle text-success">வரவு (Dr)</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">செலவு (Cr)</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="{{ $entry->flow === 'income' ? 'text-success' : 'text-danger' }} fw-bold">
                                                    {{ $entry->flow === 'income' ? '+' : '-' }}₹{{ number_format((float) $entry->amount) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge bg-light text-dark border">{{ $entry->source }}</span>
                                                    @if (!empty($entry->receipt_image))
                                                        <a href="{{ asset($entry->receipt_image) }}" target="_blank"
                                                            class="badge bg-info-subtle text-info text-decoration-none"
                                                            title="View Receipt">
                                                            <i class="bi bi-paperclip"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-journal-x fs-3 d-block mb-1 text-secondary"></i>
                                                No ledger transactions recorded yet.
                                                <div class="mt-2">
                                                    <a href="{{ route('ledger.index') }}"
                                                        class="btn btn-sm btn-outline-primary">Record Ledger Entry</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Rental & Supplier Summary for Account Manager -->
                <div class="col-12 col-lg-5">
                    <div class="card-custom p-3 p-md-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Rental & Supplier Summary</h6>
                                    <small class="text-muted">Master Collections & Payables</small>
                                </div>
                                <span class="badge bg-warning-subtle text-dark">Active Overview</span>
                            </div>

                            <div class="p-3 mb-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-muted">Rental Master Summary</span>
                                    <span class="badge bg-warning-subtle text-dark">{{ $activeRentalUnitsCount ?? 0 }}
                                        Units</span>
                                </div>
                                <div class="fw-bold text-dark fs-5 mb-2">
                                    ₹{{ number_format($totalMonthlyRent ?? 0) }}
                                    <small class="text-muted fs-6 fw-normal">/ month</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted">
                                        {{ $activePropertiesCount ?? 0 }}
                                        {{ \Illuminate\Support\Str::plural('Property', $activePropertiesCount ?? 0) }} &bull;
                                        {{ $occupiedRentalUnitsCount ?? 0 }} Occupied
                                    </span>
                                    <a href="{{ route('property-types.index') }}"
                                        class="small text-primary text-decoration-none">
                                        Manage Rentals &rarr;
                                    </a>
                                </div>
                            </div>

                            <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px dashed #cbd5e1;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold text-muted">Supplier & Purchase Payables</span>
                                    <span class="badge bg-secondary-subtle text-dark">{{ $activeSuppliersCount ?? 0 }}
                                        Suppliers</span>
                                </div>
                                <div class="fw-bold text-danger fs-5 mb-2">
                                    ₹{{ number_format(($pendingSupplierPayables ?? 0) > 0 ? $pendingSupplierPayables : ($totalSupplierPurchases ?? 0)) }}
                                    <small
                                        class="text-muted fs-6 fw-normal">{{ ($pendingSupplierPayables ?? 0) > 0 ? 'Pending' : 'Total' }}</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted">
                                        {{ $totalPurchaseOrdersCount ?? 0 }}
                                        {{ \Illuminate\Support\Str::plural('Purchase Order', $totalPurchaseOrdersCount ?? 0) }}
                                        &bull; {{ $activeSuppliersCount ?? 0 }} Active
                                    </span>
                                    <a href="{{ route('suppliers.index') }}" class="small text-primary text-decoration-none">
                                        Manage Suppliers &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        @if ($reportTargetUrl)
                            <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                <span class="small text-muted"><i class="bi bi-file-earmark-bar-graph me-1"></i> Statements</span>
                                <a href="{{ $reportTargetUrl }}" class="btn btn-sm btn-outline-secondary py-1">View Reports</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function () {
            const chartElem = document.getElementById('incomeExpenseChart');
            if (chartElem) {
                const ctxCashflow = chartElem.getContext('2d');
                new Chart(ctxCashflow, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode(!empty($chartLabels) ? $chartLabels : []) !!},
                        datasets: [
                            {
                                label: 'Income (Dr) ₹',
                                data: {!! json_encode(!empty($incomeData) ? $incomeData : []) !!},
                                borderColor: '#059669',
                                backgroundColor: 'rgba(5, 150, 105, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35
                            },
                            {
                                label: 'Expense (Cr) ₹',
                                data: {!! json_encode(!empty($expenseData) ? $expenseData : []) !!},
                                borderColor: '#dc2626',
                                backgroundColor: 'rgba(220, 38, 38, 0.05)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.35
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } }
                    }
                });
            }

            const workersCanvas = document.getElementById('workersPieChart');
            if (workersCanvas) {
                const ctxWorkers = workersCanvas.getContext('2d');
                new Chart(ctxWorkers, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(!empty($tradeChartLabels) ? $tradeChartLabels : ['மேஸ்திரி (Lead)', 'கொத்தனார் (Mason)', 'சித்தாள் (Helper)', 'கம்பிகட்டுனர்', 'பெயிண்டர்']) !!},
                        datasets: [{
                            data: {!! json_encode(!empty($tradeChartData) ? $tradeChartData : [4, 6, 10, 2, 2]) !!},
                            backgroundColor: ['#2563eb', '#0284c7', '#f59e0b', '#8b5cf6', '#10b981', '#ec4899', '#f97316'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        cutout: '65%'
                    }
                });
            }

            const expenseDonutCanvas = document.getElementById('expenseDonutChart');
            if (expenseDonutCanvas) {
                const ctxExpenseDonut = expenseDonutCanvas.getContext('2d');
                new Chart(ctxExpenseDonut, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(!empty($expenseBreakdownLabels) ? $expenseBreakdownLabels : ['Construction Sites', 'Supplier Purchases', 'Office & Admin']) !!},
                        datasets: [{
                            data: {!! json_encode(!empty($expenseBreakdownData) ? $expenseBreakdownData : [100, 50, 25]) !!},
                            backgroundColor: ['#dc2626', '#f59e0b', '#2563eb', '#8b5cf6', '#10b981', '#06b6d4'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        cutout: '65%'
                    }
                });
            }
        });
    </script>
@endpush