@extends('layouts.app')

@section('title', 'Daily Ledger')
@section('page_title', 'Daily Income & Expense Ledger ')

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

        <!-- Navigation Tabs: Daily Ledger vs Admin Ledger -->
        <ul class="nav nav-tabs mb-3 border-bottom">
            @if (auth()->user()?->canAccess('ledger'))
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('ledger.index') }}">
                        <i class="bi bi-book-half me-1"></i> Daily Ledger 
                    </a>
                </li>
            @endif
            @if (auth()->user()?->canAccess('admin_ledger'))
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('admin-ledger.index') }}">
                        <i class="bi bi-journal-bookmark-fill me-1 text-primary"></i> Admin Ledger 
                    </a>
                </li>
            @endif
        </ul>

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Notebook Style Daily Ledger</h5>
</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#ledgerModal"
                        id="addLedgerEntryBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Ledger Entry
</button>
</div>
</div>
</div>

        <!-- Notebook Balance Summary Bar -->
        <div class="card-custom p-3 p-md-4 mb-4 text-white"
            style="background: linear-gradient(135deg, #26448b 0%, #1e293b 100%);">
            <div class="row text-center g-3 align-items-center">
                <!-- Previous Income c/o -->
                <div class="col-6 col-md-3 border-end border-secondary border-opacity-25">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;">Previous Balance Carried Forward</div>
                    @php
                        $yesterdayIncome = (float) ($summary['yesterday_income'] ?? $summary['opening_balance'] ?? 0);
                        $priorBal = (float) ($summary['prior_balance'] ?? 0);
                        $displayOpening = $priorBal != 0 ? $priorBal : $yesterdayIncome;
                    @endphp
                    <h4 class="fw-bold text-warning mb-0" id="ledgerOpeningBal">{{ $displayOpening >= 0 ? '₹' . number_format($displayOpening, 2) : '-₹' . number_format(abs($displayOpening), 2) }}</h4>
</div>

                <!-- Total Income Dr -->
                <div class="col-6 col-md-3 border-end border-secondary border-opacity-25">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;" id="lblLedgerIncome">Today's Income (Dr)</div>
                    @php
                        $todayInc = (float) ($summary['today_income'] ?? $summary['total_income'] ?? 0);
                    @endphp
                    <h4 class="fw-bold text-success mb-0" id="ledgerTotalIncome">{{ $todayInc > 0 ? '+₹' . number_format($todayInc, 2) : '₹0.00' }}</h4>
                    <small class="ta-text text-white-50" style="font-size: 0.72rem;" id="lblLedgerIncomeSub">Income</small>
</div>

                <!-- Total Expense Cr -->
                <div class="col-6 col-md-3 border-end border-secondary border-opacity-25">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;" id="lblLedgerExpense">Today's Expense (Cr)</div>
                    @php
                        $todayExp = (float) ($summary['today_expense'] ?? $summary['total_expense'] ?? 0);
                    @endphp
                    <h4 class="fw-bold text-danger mb-0" id="ledgerTotalExpense">{{ $todayExp > 0 ? '−₹' . number_format($todayExp, 2) : '₹0.00' }}</h4>
                    <small class="ta-text text-white-50" style="font-size: 0.72rem;" id="lblLedgerExpenseSub">Expense</small>
</div>

                <!-- Closing Running Balance -->
                <div class="col-6 col-md-3">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;" id="lblLedgerClosing">Closing Running Balance</div>
                    @php
                        $todayClosing = (float) ($summary['today_closing'] ?? $summary['closing_balance'] ?? 0);
                    @endphp
                    <h3 class="fw-bold text-info mb-0" id="ledgerClosingBal">{{ $todayClosing != 0 ? ($todayClosing >= 0 ? '₹' . number_format($todayClosing, 2) : '-₹' . number_format(abs($todayClosing), 2)) : '₹0.00' }}</h3>
                    <small class="ta-text text-white-50" style="font-size: 0.72rem;" id="lblLedgerClosingSub">Balance</small>
</div>
</div>
</div>

        <!-- Filter & Search Bar -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="ledgerSearchInput"
                            placeholder="Search Description, Source, Customer, Bill No...">
</div>
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm fw-semibold" id="ledgerDateFilter" title="Filter by Date ">
                        <option value="today" selected>Today ({{ \Carbon\Carbon::today()->format('d M') }})</option>
                        <option value="yesterday">Yesterday ({{ \Carbon\Carbon::yesterday()->format('d M') }})</option>
                        <option value="all">All Dates</option>
                        <option value="custom">Pick Date ...</option>
                    </select>
</div>
                <div class="col-6 col-md-2 d-none" id="ledgerCustomDateCol">
                    <input type="date" class="form-control form-control-sm" id="ledgerCustomDateInput" value="{{ \Carbon\Carbon::today()->toDateString() }}">
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="siteFilter" title="Filter by Site ">
                        <option value="">All Sites</option>
                        @foreach($activeProjectSites as $site)
                            <option value="{{ $site->name }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="sourceFilter">
                        <option value="">All Sources / Categories</option>
                        <option value="Construction">Construction</option>
                        <option value="Bank Valuation">Bank (Valuation / Inspection)</option>
                        <option value="Rental">Rental</option>
                        <option value="Supplier">Supplier</option>
                        <option value="Office and other expenses">Office and other expenses</option>
                    </select>
</div>
                <div class="col-6 col-md-1">
                    <select class="form-select form-select-sm" id="flowFilter">
                        <option value="">All Flows</option>
                        <option value="income">Income (Dr)</option>
                        <option value="expense">Expense (Cr)</option>
                    </select>
</div>
                <div class="col-6 col-md-2 text-md-end d-flex align-items-center justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 flex-fill" id="ledgerFilterResetBtn"
                        title="Reset all filters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
</button>
                    <span class="small text-muted" id="ledgerCountBadge">Showing <strong
                            class="text-dark">{{ count($ledgers) }}</strong>
                        entries</span>
</div>
</div>
</div>

        <!-- Ledger Table with Running Balance Column -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" id="ledgerTable">
                    <thead>
                        <tr>
                            <th style="width: 110px;">Date</th>
                            <th>Description & Details</th>
                            <th style="width: 170px;">Source / Category</th>
                            <th style="width: 180px;">Site (Optional)</th>
                            <th class="text-end" style="width: 140px; background-color: #f0fdf4;">Income (Dr)</th>
                            <th class="text-end" style="width: 140px; background-color: #fef2f2;">Expense (Cr)</th>
                            <th class="text-end" style="width: 150px;">Running Bal (₹)</th>
                            <th class="text-center" style="width: 70px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerTableBody">
                        @php
                            $priorBal = (float) ($summary['prior_balance'] ?? 0);
                        @endphp
                        <!-- Opening Balance Row (Shown when viewing a specific date) -->
                        <tr class="opening-balance-row fw-bold" id="openingBalanceRow" style="background-color: #fffbeb; border-bottom: 2px solid #fde68a;">
                            <td colspan="4" class="py-2 text-start">
                                <span class="text-warning-emphasis fw-bold">
                                    <i class="bi bi-box-arrow-in-down-right me-1"></i>
                                    Opening Balance Carried Forward:
</span>
                            </td>
                            <td class="text-end text-muted py-2">-</td>
                            <td class="text-end text-muted py-2">-</td>
                            <td class="text-end fw-bold py-2 text-dark" id="tableOpeningBalCell">
                                {{ $priorBal >= 0 ? '₹' . number_format($priorBal, 2) : '-₹' . number_format(abs($priorBal), 2) }}
                            </td>
                            <td></td>
                        </tr>

                        @forelse($ledgers as $index => $ledger)
                            @php
                                $isIncome = ($ledger->flow === 'income');
                                $amount = (float) $ledger->amount;
                                $dr = $isIncome ? $amount : 0;
                                $cr = !$isIncome ? $amount : 0;
                                $rowRunningBal = $ledgerBalances[$ledger->id] ?? 0;

                                $badgeClass = $isIncome
                                    ? 'bg-success-subtle text-success border border-success-subtle'
                                    : 'bg-danger-subtle text-danger border border-danger-subtle';
                                $drText = $isIncome ? '+₹' . number_format($dr, 2) : '-';
                                $crText = !$isIncome ? '−₹' . number_format($cr, 2) : '-';

                                $siteDisplay = ($ledger->site_name)
                                    ? $ledger->site_name
                                    : '-';
                                $siteSubtitle = ($ledger->site_name) ? ' • ' . $ledger->site_name : '';
                                if ($ledger->client_name) {
                                    $siteSubtitle .= ' (Client: ' . $ledger->client_name . ')';
                                }

                                $sourceSubtitle = '';
                                if ($ledger->source === 'Supplier' && $ledger->supplier_name) {
                                    $sourceSubtitle = '<i class="bi bi-truck me-1"></i>Supplier: ' . e($ledger->supplier_name) . e($siteSubtitle);
                                } elseif ($ledger->source === 'Bank Valuation' && $ledger->bank_name) {
                                    $sourceSubtitle = '<i class="bi bi-bank me-1"></i>' . e($ledger->bank_name) . ($ledger->branch_name ? ' (' . e($ledger->branch_name) . ')' : '') . ($ledger->bank_customer_ref ? ' • Ref: ' . e($ledger->bank_customer_ref) : '');
                                } elseif ($ledger->source === 'Rental' && $ledger->property_name) {
                                    $sourceSubtitle = '<i class="bi bi-building me-1"></i>' . e($ledger->property_name) . ($ledger->unit_name ? ' [' . e($ledger->unit_name) . ']' : '');
                                } else {
                                    $sourceSubtitle = '<i class="bi bi-tag me-1"></i>' . e($ledger->source) . e($siteSubtitle);
                                }

                                if ($ledger->transaction_type_name && $ledger->transaction_type_value) {
                                    $sourceSubtitle .= ' <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"><i class="bi bi-tag me-1"></i>' . e($ledger->transaction_type_name) . ': ' . e($ledger->transaction_type_value) . '</span>';
                                } elseif ($ledger->transaction_type_name) {
                                    $sourceSubtitle .= ' <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"><i class="bi bi-tag me-1"></i>' . e($ledger->transaction_type_name) . '</span>';
                                }
                                if ($ledger->cash_transfer_id) {
                                    $sourceSubtitle .= ' <span class="badge bg-info-subtle text-info border border-info-subtle ms-1"><i class="bi bi-arrow-left-right me-1"></i>Cash Transfer #CTR-' . $ledger->cash_transfer_id . '</span>';
                                }
                            @endphp
                            <tr data-id="{{ $ledger->id }}" data-flow="{{ $ledger->flow }}" data-source="{{ $ledger->source }}"
                                data-site="{{ $ledger->site_name ?? '-' }}"
                                data-date="{{ $ledger->entry_date->format('Y-m-d') }}" data-dr="{{ $dr }}" data-cr="{{ $cr }}"
                                data-balance="{{ $rowRunningBal }}"
                                data-desc="{{ $ledger->description }}">
                                <td class="fw-semibold text-secondary small align-middle">
                                    {{ $ledger->entry_date->format('d M Y') }}
                                </td>
                                <td>
                                    @php
                                        $cleanDesc = $ledger->description;
                                        if (str_contains($cleanDesc, 'Painter & Electrician daily wages')) {
                                            $cleanDesc = 'Painter & Electrician daily wages';
                                        }
                                    @endphp
                                    <div class="fw-bold text-dark d-flex align-items-center flex-wrap gap-1" title="{{ $ledger->description }}">
                                        <span class="text-truncate d-inline-block" style="max-width: 320px;">
                                            {{ \Illuminate\Support\Str::limit($cleanDesc, 45) }}
</span>
                                        @if($ledger->receipt_image)
                                             <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"
                                                title="Receipt image attached">
                                                <i class="bi bi-image"></i>
</span>
                                        @endif
</div>
                                    <small class="text-muted">{!! $sourceSubtitle !!}</small>
                                </td>
                                <td><span class="badge {{ $badgeClass }}">{{ $ledger->source }}</span></td>
                                <td>
                                    @if($ledger->site_name)
                                        <span class="badge bg-light text-dark border">{{ $ledger->site_name }}</span>
                                        @if($ledger->client_name)
                                            <div class="small text-muted" style="font-size: 0.75rem;">Client: {{ $ledger->client_name }}
</div>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success" style="background-color: #f0fdf4;">{{ $drText }}</td>
                                <td class="text-end fw-bold text-danger" style="background-color: #fef2f2;">{!! $crText !!}</td>
                                <td class="text-end fw-semibold text-dark">₹{{ number_format($rowRunningBal, 2) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('ledger.show', $ledger->encrypted_id) }}"
                                        class="btn btn-outline-primary btn-sm px-2 py-1"
                                        title="View Details ">
                                        <i class="bi bi-eye"></i>
</a>
                                </td>
                            </tr>
                            @php
                                $nextLedger = $ledgers[$index + 1] ?? null;
                                $currentDateKey = $ledger->entry_date->format('Y-m-d');
                                $nextDateKey = $nextLedger ? $nextLedger->entry_date->format('Y-m-d') : null;
                                $isLastForDate = ($nextDateKey !== $currentDateKey);
                            @endphp
                            @if($isLastForDate)
                                @php
                                    $dayInc = $dayTotals[$currentDateKey]['income'] ?? 0;
                                    $dayExp = $dayTotals[$currentDateKey]['expense'] ?? 0;
                                    $dayNet = $dayInc - $dayExp;
                                @endphp
                                <tr class="date-summary-row fw-bold" data-summary-date="{{ $currentDateKey }}" style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1; border-bottom: 2px solid #cbd5e1;">
                                    <td colspan="4" class="py-2 text-start">
                                        <span class="text-primary"><i class="bi bi-calendar-check-fill me-1"></i>{{ $ledger->entry_date->format('d M Y') }}</span>
                                        <span class="text-secondary small fw-semibold ms-2">Day Total:</span>
                                    </td>
                                    <td class="text-end text-success py-2" style="background-color: #e8f5e9;">
                                        {{ $dayInc > 0 ? '+₹' . number_format($dayInc, 2) : '₹0.00' }}
                                    </td>
                                    <td class="text-end text-danger py-2" style="background-color: #ffebee;">
                                        {{ $dayExp > 0 ? '−₹' . number_format($dayExp, 2) : '₹0.00' }}
                                    </td>
                                    <td class="text-end py-2">
                                        <span class="{{ $dayNet >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $dayNet >= 0 ? '+₹' : '−₹' }}{{ number_format(abs($dayNet), 2) }}
</span>
                                    </td>
                                    <td></td>
                                </tr>
                            @endif
                        @empty
                            <tr id="ledgerNoDataRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                                    <span class="fw-semibold">No daily ledger entries recorded yet.</span>
                                    <div class="small text-muted mt-1">Use the "Add Ledger Entry" button above to record
                                        real-time transactions.</div>
                                    <div class="mt-3 d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-deiveni btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#ledgerModal">
                                            <i class="bi bi-plus-circle-fill me-1"></i> Add First Ledger Entry
</button>
</div>
                                </td>
                            </tr>
                        @endforelse

                        <!-- Empty State Row when filtered out -->
                        <tr id="ledgerFilterEmptyRow" class="d-none">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-funnel fs-2 d-block mb-2 text-secondary"></i>
                                <span class="fw-semibold">No ledger entries match the selected filters.</span>
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-secondary btn-sm"
                                        id="clearLedgerFiltersBtn">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
</button>
</div>
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
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark"
                            id="pageEnd">{{ min(10, count($ledgers)) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ count($ledgers) }}</strong> entries
</span>
</div>
                <nav aria-label="Ledger table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- Add Ledger Entry Modal -->
    <div class="modal fade" id="ledgerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Daily Ledger Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="ledgerForm" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">Entry Date  <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="newLedgerDate" name="entry_date"
                                    value="{{ date('Y-m-d') }}">
                                <div class="invalid-feedback" id="errEntryDate"></div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">Entry Flow Type <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newLedgerFlow" name="flow">
                                    <option value="income">Income (Dr)</option>
                                    <option value="expense">Expense (Cr)</option>
                                </select>
                                <div class="invalid-feedback" id="errFlow"></div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">Source / Category <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newLedgerSource" name="source">
                                    <option value="Construction">Construction</option>
                                    <option value="Bank Valuation">Bank (Valuation / Inspection fee)</option>
                                    <option value="Rental">Rental</option>
                                    <option value="Office and other expenses">Office and other expenses</option>
                                </select>
                                <div class="invalid-feedback" id="errSource"></div>
</div>

                            <!-- Dynamic Bank Specific Details -->
                            <div class="col-12 bank-fields-group d-none">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="small fw-bold text-primary mb-2 d-block"><i
                                            class="bi bi-bank me-1"></i>Bank Valuation Details</span>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6" id="bankSelectContainer">
                                            <label class="form-label small fw-semibold">Select Bank 
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="ledgerBankSelect" name="bank_id">
                                                <option value="" disabled selected>-- Select Bank --
</option>
                                                @foreach($activeBanks as $bank)
                                                    <option value="{{ $bank->id }}" data-bank-name="{{ $bank->bank_name }}">
                                                        {{ $bank->bank_name }}{{ $bank->bank_name_ta ? ' (' . $bank->bank_name_ta . ')' : '' }}
</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errBankId"></div>
</div>
                                        <div class="col-12 col-md-6 d-none" id="branchSelectContainer">
                                            <label class="form-label small fw-semibold">Select Branch 
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="ledgerBranchSelect"
                                                name="branch_id">
                                                <option value="" disabled selected>-- Select Branch --</option>
                                            </select>
                                            <div class="invalid-feedback" id="errBranchId"></div>
</div>
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Customer / Loan Ref</label>
                                            <input type="text" class="form-control form-control-sm" id="bankCustomer"
                                                name="bank_customer_ref" placeholder="e.g. Mr. S. Karthi (Ref #88)">
                                            <div class="invalid-feedback" id="errBankCustomerRef"></div>
</div>
</div>
</div>
</div>

                            <!-- Dynamic Rental Specific Details -->
                            <div class="col-12 rental-fields-group d-none">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="small fw-bold text-primary mb-2 d-block"><i
                                            class="bi bi-house me-1"></i>Rental Income Details</span>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6" id="propertySelectContainer">
                                            <label class="form-label small fw-semibold">Select Property 
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="ledgerPropertySelect"
                                                name="property_id">
                                                <option value="" disabled selected>-- Select Property 
                                                   --</option>
                                                @foreach($activeProperties as $prop)
                                                    <option value="{{ $prop->id }}"
                                                        data-property-name="{{ $prop->property_name }}">
                                                        {{ $prop->property_name }}{{ $prop->property_name_ta ? ' (' . $prop->property_name_ta . ')' : '' }}
</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errPropertyId"></div>
</div>
                                        <div class="col-12 col-md-6 d-none" id="propertyTypeSelectContainer">
                                            <label class="form-label small fw-semibold">Select Property Type / Unit 
                                                <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="ledgerPropertyTypeSelect"
                                                name="property_type_id">
                                                <option value="" disabled selected>-- Select Property Type / Unit --</option>
                                            </select>
                                            <div class="invalid-feedback" id="errPropertyTypeId"></div>
</div>
</div>
</div>
</div>

                            <!-- Dynamic Construction Specific Details (Task 3: Site & Client Auto-fill) -->
                            <div class="col-12 construction-fields-group d-none">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="small fw-bold text-primary mb-2 d-block">
                                        <i class="bi bi-building me-1"></i>Construction Site Details 
</span>
                                    <div class="row g-2">
                                        <div class="col-12" id="constructionSiteWrapper">
                                            <label class="form-label small fw-semibold">Associated Site  <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm" id="constructionSite"
                                                name="project_site_id">
                                                <option value="">-- Select Site --</option>
                                                @foreach($activeProjectSites as $site)
                                                    <option value="{{ $site->id }}" data-site-name="{{ $site->name }}"
                                                        data-client-name="{{ $site->client ? $site->client->name : '' }}">
                                                        {{ $site->name }}{{ $site->client ? ' (' . $site->client->name . ')' : '' }}
</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errProjectSiteId"></div>
</div>
                                        <!-- Task 3: Appended Client Name input with auto-filled value, editable by user -->
                                        <div class="col-12 d-none" id="constructionClientContainer">
                                            <label class="form-label small fw-semibold">
                                                Client Name 
                                                <span class="badge bg-secondary-subtle text-secondary fw-normal ms-1"
                                                    style="font-size: 0.7rem;">Auto-filled / Editable</span>
</label>
                                            <div class="input-group input-group-sm has-validation">
                                                <span class="input-group-text bg-white"><i
                                                        class="bi bi-person text-muted"></i></span>
                                                <input type="text" class="form-control form-control-sm"
                                                    id="constructionClientName" name="client_name"
                                                    placeholder="Client Name ">
                                                <div class="invalid-feedback" id="errClientName"></div>
</div>
                                            <small class="text-muted" style="font-size: 0.72rem;">Pre-filled from site
                                                master. You may modify the name if needed.</small>
</div>

                                        <div class="col-12 d-none" id="cashReceivedFromContainer">
                                            <label class="form-label small fw-semibold">Cash Received From <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="cash_received_from" id="cashReceivedFromClient" value="client">
                                                    <label class="form-check-label" for="cashReceivedFromClient">Client</label>
</div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="cash_received_from" id="cashReceivedFromOwner" value="owner">
                                                    <label class="form-check-label" for="cashReceivedFromOwner">Owner</label>
</div>
</div>
                                            <div class="invalid-feedback" id="errCashReceivedFrom"></div>
</div>

                                        <!-- Painter & Electrician Daily Wage Auto-Check Card -->
                                        <div class="col-12 mt-2 d-none" id="wageCheckCard">
                                            <div class="p-3 rounded-3 border border-primary-subtle bg-white shadow-sm" id="wageCheckCardInner">
                                                <!-- Clean Header -->
                                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                            <i class="bi bi-people-fill small"></i>
</div>
                                                        <div>
                                                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.88rem;">Painter & Electrician Daily Wages</h6>
                                                            <small class="text-muted" style="font-size: 0.7rem;">Allocated Daily Wages</small>
</div>
</div>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold" id="wageCheckBadge" style="font-size: 0.72rem;">
                                                        Pending Wages
</span>
</div>

                                                <!-- Dynamic Worker Badges (Clean Cards) -->
                                                <div id="wageCheckItemsContainer" class="d-flex flex-wrap gap-2 mb-3">
                                                    <!-- Dynamic Badges -->
</div>

                                                <!-- Cash Source Segmented Button Group (Clean, Modern, No bulky box) -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold text-secondary mb-1 d-block" style="font-size: 0.75rem;">
                                                        <i class="bi bi-wallet2 text-primary me-1"></i>Wage Payment Source 
</label>
                                                    <div class="btn-group btn-group-sm w-100" role="group">
                                                        <input type="radio" class="btn-check wage-source-radio" name="wage_cash_source" id="wageSourceOfficeCash" value="office_cash" checked>
                                                        <label class="btn btn-outline-primary py-1" for="wageSourceOfficeCash" style="font-size: 0.78rem;">
                                                            <i class="bi bi-building me-1"></i>Office Cash 
</label>

                                                        <input type="radio" class="btn-check wage-source-radio" name="wage_cash_source" id="wageSourceProjectCash" value="project_cash">
                                                        <label class="btn btn-outline-primary py-1" for="wageSourceProjectCash" style="font-size: 0.78rem;">
                                                            <i class="bi bi-cash-stack me-1"></i>Project Cash 
</label>
</div>
</div>

                                                <!-- Extra / Support Workers Section (Clean, Expandable) -->
                                                <div class="mb-3 pt-2 border-top" id="extraWorkersSection">
                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                        <span class="small fw-semibold text-secondary" style="font-size: 0.75rem;">
                                                            <i class="bi bi-person-plus text-primary me-1"></i>Extra Helper / Support Worker 
</span>
                                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill" id="btnAddExtraWorker" style="font-size: 0.72rem;">
                                                            <i class="bi bi-plus-lg me-1"></i>Add Helper 
</button>
</div>
                                                    <div id="extraWorkersContainer" class="d-flex flex-column gap-2">
                                                        <!-- Dynamic extra worker input rows -->
</div>
</div>

                                                <!-- Clean Summary Bar -->
                                                <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-light border" id="wageCheckActionRow">
                                                    <div class="d-flex align-items-baseline gap-2">
                                                        <span class="text-muted small" style="font-size: 0.78rem;">Total Wages:</span>
                                                        <strong class="text-success fs-5" id="wageCheckTotalText">₹0</strong>
</div>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.72rem;">
                                                        <i class="bi bi-check2-circle me-1"></i>Auto-filled in Ledger
</span>
</div>
                                                <input type="hidden" name="daily_allocation_id" id="ledgerDailyAllocationId">
                                                <input type="hidden" name="is_am_wage_payment" id="ledgerIsAmWagePayment" value="0">
                                                <div id="ledgerWageItemIdsInputs"></div>
</div>
</div>
</div>
</div>
</div>

                            <!-- Dynamic Supplier Specific Details (Task 4: Only Supplier Select Box) -->
                            <div class="col-12 supplier-fields-group d-none">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="small fw-bold text-primary mb-2 d-block">
                                        <i class="bi bi-truck me-1"></i>Supplier Details 
</span>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small fw-semibold">Select Supplier  <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm fw-semibold" id="ledgerSupplier"
                                                name="supplier_id">
                                                <option value="" disabled selected>-- Select Supplier --</option>
                                                @foreach($activeSuppliers as $supplier)
                                                    <option value="{{ $supplier->id }}"
                                                        data-supplier-name="{{ $supplier->name }}">{{ $supplier->name }}
</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errSupplierId"></div>
</div>
</div>
</div>
</div>

                            <!-- Dynamic Office and Other Expenses Specific Details -->
                            <div class="col-12 office-expense-fields-group d-none">
                                <label class="form-label small fw-semibold">Office & Other Expense Details</label>
                                <input type="text" class="form-control form-control-sm" id="officeExpenseDetails"
                                    name="office_expense_details"
                                    placeholder="Enter expense (e.g. Office stationery, EB bill, tea/snacks, travel)">
                                <div class="invalid-feedback" id="errOfficeExpenseDetails"></div>
</div>

                            <!-- Dynamic Transaction Types from Master (Task 5) -->
                            <div class="col-12 d-none" id="dynamicTransactionTypesContainer">
                                <div class="p-3 bg-light rounded-3 border">
                                    <span class="small fw-bold text-primary mb-2 d-block">
                                        <i class="bi bi-tags-fill me-1"></i>Transaction Type Fields (<span
                                            id="txnCategoryLabel">Category</span>)
</span>
                                    <p class="text-muted small mb-2" style="font-size: 0.75rem;">
                                        Linked with Transaction Type Master. Enter any details or values for the
                                        corresponding types below.
                                    </p>
                                    <div class="row g-2" id="txnTypeFieldsWrapper">
                                        <!-- Dynamically populated transaction type input fields -->
</div>
</div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Amount (₹) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="1" class="form-control" id="newLedgerAmount" name="amount"
                                        placeholder="e.g. 5000">
                                    <div class="invalid-feedback" id="errAmount"></div>
</div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Description / Narration <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="newLedgerDesc" name="description"
                                    placeholder="e.g. Client stage 3 advance received">
                                <div class="invalid-feedback" id="errDescription"></div>
</div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Receipt / Voucher Image <span
                                        class="text-muted fw-normal">(Optional)</span></label>
                                <input type="file" class="form-control" id="newLedgerImage" name="receipt_image"
                                    accept="image/png, image/jpeg, image/jpg, image/webp">
                                <div class="invalid-feedback" id="errReceiptImage"></div>
                                <div id="newLedgerImagePreviewBox"
                                    class="mt-2 p-2 border rounded bg-light align-items-center justify-content-between"
                                    style="display: none !important;">
                                    <div class="d-flex align-items-center gap-2">
                                        <img id="newLedgerImagePreviewImg" src="" alt="Preview"
                                            style="width: 44px; height: 44px; object-fit: cover;" class="rounded border">
                                        <div>
                                            <div class="small fw-semibold text-truncate" id="newLedgerImageName"
                                                style="max-width: 240px;"></div>
                                            <small class="text-muted" id="newLedgerImageSize"></small>
</div>
</div>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                        id="newLedgerImageClearBtn" title="Remove attached image">
                                        <i class="bi bi-x-circle me-1"></i>Remove
</button>
</div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="btnSaveLedgerEntry">
                            <span class="spinner-border spinner-border-sm d-none me-1" id="saveSpinner"></span>
                            Save Entry to Ledger
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
            const activeBanksData = @json($activeBanks);
            const activePropertiesData = @json($activeProperties);
            const activeProjectSitesData = @json($activeProjectSites);
            const activeTransactionTypesData = @json($activeTransactionTypes);

            function showToast(message, type = 'success') {
                if (typeof window.showToast === 'function') {
                    window.showToast(message, type);
                } else {
                    alert(message);
                }
            }

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }

            function getTodayString() {
                const d = new Date();
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // Task 5: Dynamic Transaction Type Inputs for the chosen category
            function populateTransactionTypeFields(category) {
                const container = $('#dynamicTransactionTypesContainer');
                const wrapper = $('#txnTypeFieldsWrapper');
                const label = $('#txnCategoryLabel');
                wrapper.empty();

                if (!category) {
                    container.addClass('d-none');
                    return;
                }

                // Match category flexibly (e.g. Bank vs Bank Valuation)
                const targetCat = (category === 'Bank Valuation' || category === 'Bank') ? 'bank' : category.toLowerCase().trim();

                const matchedTypes = activeTransactionTypesData.filter(function (t) {
                    const tCat = (t.category || '').toLowerCase().trim();
                    if (targetCat === 'bank') {
                        return tCat === 'bank' || tCat === 'bank valuation';
                    }
                    return tCat === targetCat;
                });

                if (matchedTypes.length > 0) {
                    label.text(category);
                    matchedTypes.forEach(function (t) {
                        const fieldHtml = `
                                                            <div class="col-12 col-md-6">
                                                                <label class="form-label small fw-semibold">
                                                                    ${escapeHtml(t.name_en)}
                                                                    <span class="badge bg-secondary-subtle text-secondary fw-normal ms-1" style="font-size: 0.68rem;">Input</span>
</label>
                                                                <input type="text" class="form-control form-control-sm transaction-type-input"
                                                                    name="transaction_types[${t.id}]"
                                                                    data-id="${t.id}"
                                                                    data-name="${escapeHtml(t.name_en)}"
                                                                    placeholder="Enter ${escapeHtml(t.name_en)} details...">
</div>
                                                        `;
                        wrapper.append(fieldHtml);
                    });
                    container.removeClass('d-none');
                } else {
                    container.addClass('d-none');
                }
            }
            // Source / Category options based on Entry Flow Type
            const sourceOptionsByFlow = {
                income: [
                    { value: 'Construction', text: 'Construction ' },
                    { value: 'Bank Valuation', text: 'Bank (Valuation / Inspection fee)' },
                    { value: 'Rental', text: 'Rental ' },
                    { value: 'Office and other expenses', text: 'Office and other expenses' }
                ],
                expense: [
                    { value: 'Construction', text: 'Construction ' },
                    { value: 'Supplier', text: 'Supplier ' },
                    { value: 'Office and other expenses', text: 'Office and other expenses' }
                ]
            };

            function syncSourceOptions(preferredValue) {
                const flow = $('#newLedgerFlow').val() || 'income';
                const options = sourceOptionsByFlow[flow] || sourceOptionsByFlow.income;
                const currentVal = preferredValue !== undefined ? preferredValue : $('#newLedgerSource').val();

                const select = $('#newLedgerSource');
                select.empty();

                let isCurrentValPresent = false;
                options.forEach(function (opt) {
                    const isSelected = (opt.value === currentVal);
                    if (isSelected) {
                        isCurrentValPresent = true;
                    }
                    select.append($('<option>', {
                        value: opt.value,
                        text: opt.text,
                        selected: isSelected
                    }));
                });

                if (!isCurrentValPresent && options.length > 0) {
                    select.val(options[0].value);
                }

                select.trigger('change');
            }

            // Entry Flow Type Change handler
            $('#newLedgerFlow').on('change', function () {
                syncSourceOptions();
            });

            // Category Change handler
            $('#newLedgerSource').on('change', function () {
                const val = $(this).val();
                $("#newLedgerAmount").val('');

                // Reset sub-groups
                $('.bank-fields-group').addClass('d-none');
                $('.rental-fields-group').addClass('d-none');
                $('.construction-fields-group').addClass('d-none');
                $('.supplier-fields-group').addClass('d-none');
                $('.office-expense-fields-group').addClass('d-none');

                if (val === 'Bank Valuation') {
                    $('.bank-fields-group').removeClass('d-none');
                    if (!$('#ledgerBankSelect').val()) {
                        $('#branchSelectContainer').addClass('d-none');
                    }
                } else if (val === 'Rental') {
                    $('.rental-fields-group').removeClass('d-none');
                    if (!$('#ledgerPropertySelect').val()) {
                        $('#propertyTypeSelectContainer').addClass('d-none');
                    }
                } else if (val === 'Construction') {
                    // Task 3: Show Construction site & client container
                    $('.construction-fields-group').removeClass('d-none');
                } else if (val === 'Supplier') {
                    // Task 4: Only append supplier select box, no site and no PO
                    $('.supplier-fields-group').removeClass('d-none');
                } else if (val === 'Office and other expenses') {
                    $('.office-expense-fields-group').removeClass('d-none');
                }

                // Task 5: Populate dynamic transaction type inputs for this category
                populateTransactionTypeFields(val);
                                syncCashReceivedFromVisibility();
            });

            function syncCashReceivedFromVisibility() {
                const shouldShow = $('#newLedgerFlow').val() === 'income'
                    && $('#newLedgerSource').val() === 'Construction'
                    && Boolean($('#constructionSite').val());

                $('#cashReceivedFromContainer').toggleClass('d-none', !shouldShow);
                if (!shouldShow) {
                    $('input[name="cash_received_from"]').prop('checked', false).removeClass('is-invalid');
                    $('#errCashReceivedFrom').text('').hide();
                }
            }

            // Task 3: Construction Site Change -> Append Client Name input with auto-filled value
            $('#constructionSite').on('change', function () {
                $("#newLedgerAmount").val('');
                const siteId = $(this).val();
                const selectedOpt = $(this).find('option:selected');
                const clientName = selectedOpt.data('client-name') || '';
                const siteName = selectedOpt.data('site-name') || '';

                if (siteId) {
                    $('#constructionClientName').val(clientName);
                    $('#constructionClientContainer').removeClass('d-none');
                    if (siteName && (!$('#newLedgerDesc').val() || $('#newLedgerDesc').data('autofilled') === 'site')) {
                        $('#newLedgerDesc').val(`Construction: ${siteName}${clientName ? ' (' + clientName + ')' : ''}`).data('autofilled', 'site');
                    }
                } else {
                    $('#constructionClientContainer').addClass('d-none');
                    $('#constructionClientName').val('');
                }
                syncCashReceivedFromVisibility();
                checkPainterElectricianWages();
            });

            let currentWageCheckData = null;

            function checkPainterElectricianWages() {
                const source = $('#newLedgerSource').val();
                const flow = $('#newLedgerFlow').val();
                const siteId = $('#constructionSite').val();
                const entryDate = $('#newLedgerDate').val();

                if (source !== 'Construction' || flow !== 'expense' || !siteId || !entryDate) {
                    $('#wageCheckCard').addClass('d-none');
                    currentWageCheckData = null;
                    $('#ledgerDailyAllocationId').val('');
                    $('#ledgerIsAmWagePayment').val('0');
                    return;
                }

                $.ajax({
                    url: "{{ route('ledger.check-wages') }}",
                    type: 'GET',
                    data: {
                        project_site_id: siteId,
                        entry_date: entryDate
                    },
                    success: function (res) {
                        if (res.success && res.found && res.has_matching_trades && res.items.length > 0 && !res.all_paid && res.unpaid_total > 0) {
                            currentWageCheckData = res;
                            currentWageCheckData.removedIds = new Set();
                            $('#ledgerDailyAllocationId').val(res.allocation_id);
                            renderLedgerWageItems();
                            $('#wageCheckCard').removeClass('d-none');
                        } else {
                            // Once completed/settled or no unpaid trades, do NOT auto-fill and clear wage payment flags!
                            $('#wageCheckCard').addClass('d-none');
                            currentWageCheckData = null;
                            $('#ledgerDailyAllocationId').val('');
                            $('#ledgerIsAmWagePayment').val('0');
                            $('#ledgerWageItemIdsInputs').empty();
                        }
                    },
                    error: function () {
                        $('#wageCheckCard').addClass('d-none');
                        currentWageCheckData = null;
                        $('#ledgerDailyAllocationId').val('');
                        $('#ledgerIsAmWagePayment').val('0');
                    }
                });
            }

            function reindexExtraWorkers() {
                $('#extraWorkersContainer .extra-worker-item').each(function (idx) {
                    $(this).find('.extra-worker-name').attr('name', `extra_workers[${idx}][name]`);
                    $(this).find('.extra-worker-work').attr('name', `extra_workers[${idx}][work]`);
                    $(this).find('.extra-worker-salary').attr('name', `extra_workers[${idx}][salary]`);
                });
            }

            function renderLedgerWageItems() {
                if (!currentWageCheckData) return;
                const removedIds = currentWageCheckData.removedIds || new Set();
                let itemsHtml = '';
                let activeUnpaidTotal = 0;
                let pendingCount = 0;
                const inputsContainer = $('#ledgerWageItemIdsInputs');
                inputsContainer.empty();

                currentWageCheckData.items.forEach(function (it) {
                    const idStr = String(it.id || '');
                    if (!it.is_paid && removedIds.has(idStr)) {
                        return;
                    }

                    if (!it.is_paid) {
                        activeUnpaidTotal += (parseFloat(it.subtotal) || 0);
                        pendingCount++;
                        inputsContainer.append(`<input type="hidden" name="wage_item_ids[]" value="${idStr}">`);
                    }

                    const isPaint = (it.name_en || '').toLowerCase().includes('paint');
                    const icon = isPaint ? 'bi-brush' : 'bi-lightning-charge';
                    const iconColor = isPaint ? 'text-info' : 'text-warning';
                    const statusHtml = it.is_paid
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;"><i class="bi bi-check2"></i> Paid</span>'
                        : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:0.68rem;">Pending</span>';
                    const removeBtn = !it.is_paid
                        ? `<button type="button" class="btn-close ms-1 remove-ledger-wage-item-btn" data-item-id="${idStr}" style="font-size: 0.62rem;" title="Remove worker (keep pending)"></button>`
                        : '';

                    itemsHtml += `
                        <div class="px-2 py-1 rounded-2 border bg-light d-inline-flex align-items-center gap-2" style="font-size: 0.8rem;">
                            <i class="bi ${icon} ${iconColor}"></i>
                            <span class="fw-semibold text-dark">${it.name_ta || it.name_en}</span>
                            <span class="text-muted" style="font-size: 0.72rem;">(${it.count} workers)</span>
                            <strong class="text-success ms-1">₹${Math.round(it.subtotal).toLocaleString('en-IN')}</strong>
                            ${statusHtml}
                            ${removeBtn}
                        </div>
                    `;
                });

                if (removedIds.size > 0) {
                    itemsHtml += `
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="restoreLedgerWageItemsBtn" style="font-size: 0.72rem;">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore (${removedIds.size})
                        </button>
                    `;
                }

                $('#wageCheckItemsContainer').html(itemsHtml);
                $('#wageCheckBadge').removeClass('bg-success-subtle text-success').addClass('bg-primary-subtle text-primary').text(`${pendingCount} Trade(s) Pending`);
                currentWageCheckData.active_unpaid_total = activeUnpaidTotal;
                syncWageTotalsAndAutoFill();
            }

            $(document).on('click', '.remove-ledger-wage-item-btn', function () {
                if (!currentWageCheckData) return;
                const itemId = String($(this).data('item-id'));
                currentWageCheckData.removedIds.add(itemId);
                renderLedgerWageItems();
            });

            $(document).on('click', '#restoreLedgerWageItemsBtn', function () {
                if (!currentWageCheckData) return;
                currentWageCheckData.removedIds.clear();
                renderLedgerWageItems();
            });

            function syncWageTotalsAndAutoFill() {
                if (!currentWageCheckData) return;

                const baseUnpaid = parseFloat(currentWageCheckData.active_unpaid_total ?? currentWageCheckData.unpaid_total) || 0;
                let extraTotal = 0;
                const extraDetails = [];

                $('#extraWorkersContainer .extra-worker-item').each(function () {
                    const name = $(this).find('.extra-worker-name').val().trim();
                    const work = $(this).find('.extra-worker-work').val().trim();
                    const salary = parseFloat($(this).find('.extra-worker-salary').val()) || 0;
                    if (salary > 0 || name) {
                        extraTotal += salary;
                        const roleStr = work ? ` (${work})` : '';
                        extraDetails.push(`${name || 'Helper'}${roleStr}: ₹${Math.round(salary).toLocaleString('en-IN')}`);
                    }
                });

                const grandTotal = Math.round(baseUnpaid + extraTotal);
                $('#wageCheckTotalText').text(`₹${grandTotal.toLocaleString('en-IN')}`);

                const siteName = $('#constructionSite option:selected').data('site-name') || 'Site';
                const dateVal = $('#newLedgerDate').val();
                const cashSourceVal = $('input[name="wage_cash_source"]:checked').val() || 'office_cash';
                const cashSourceText = cashSourceVal === 'project_cash' ? 'Project Cash ' : 'Office Cash ';

                let extraText = '';
                if (extraDetails.length > 0) {
                    extraText = ` + Extra: ${extraDetails.join(', ')}`;
                }

                $('#newLedgerAmount').val(grandTotal);
                $('#newLedgerDesc').val(`Painter & Electrician daily wages (${dateVal})`);
                $('#ledgerIsAmWagePayment').val('1');
                $('#ledgerDailyAllocationId').val(currentWageCheckData.allocation_id);
            }

            // Add Extra Worker Row (Clean, compact layout)
            $('#btnAddExtraWorker').on('click', function () {
                const rowHtml = `
                    <div class="extra-worker-item p-2 rounded-2 bg-light border">
                        <div class="row g-1 align-items-center">
                            <div class="col-12 col-sm-4">
                                <input type="text" class="form-control form-control-sm extra-worker-name bg-white" placeholder="Worker Name " required>
</div>
                            <div class="col-12 col-sm-4">
                                <input type="text" class="form-control form-control-sm extra-worker-work bg-white" placeholder="Role " required>
</div>
                            <div class="col-9 col-sm-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">₹</span>
                                    <input type="number" step="1" min="0" class="form-control form-control-sm extra-worker-salary bg-white" placeholder="Salary" required>
</div>
</div>
                            <div class="col-3 col-sm-1 text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-extra-worker" title="Remove">
                                    <i class="bi bi-x-lg"></i>
</button>
</div>
</div>
</div>
                `;
                $('#extraWorkersContainer').append(rowHtml);
                reindexExtraWorkers();
                syncWageTotalsAndAutoFill();
            });

            $(document).on('click', '.btn-remove-extra-worker', function () {
                $(this).closest('.extra-worker-item').remove();
                reindexExtraWorkers();
                syncWageTotalsAndAutoFill();
            });

            $(document).on('input change', '.extra-worker-name, .extra-worker-work, .extra-worker-salary', function () {
                syncWageTotalsAndAutoFill();
            });

            $(document).on('change', 'input[name="wage_cash_source"]', function () {
                syncWageTotalsAndAutoFill();
            });

            $('#newLedgerDate, #newLedgerFlow').on('change', function () {
                checkPainterElectricianWages();
            });

            // Task 4: Supplier Selection -> Suggest description
            $('#ledgerSupplier').on('change', function () {
                $("#newLedgerAmount").val('');
                const selectedOpt = $(this).find('option:selected');
                const supplierName = selectedOpt.data('supplier-name') || selectedOpt.text();
                if (supplierName && (!$('#newLedgerDesc').val() || $('#newLedgerDesc').data('autofilled') === 'supplier')) {
                    $('#newLedgerDesc').val(`Payment to ${supplierName}`).data('autofilled', 'supplier');
                }
            });

            // Bank Selection -> Show active branches
            $('#ledgerBankSelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const bankId = parseInt($(this).val(), 10);
                const branchSelect = $('#ledgerBranchSelect');
                branchSelect.empty();

                if (!bankId) {
                    $('#branchSelectContainer').addClass('d-none');
                    branchSelect.append('<option value="" disabled selected>-- Select Branch --</option>');
                    return;
                }

                const selectedBank = activeBanksData.find(b => b.id === bankId);
                const activeBranches = (selectedBank && selectedBank.branches)
                    ? selectedBank.branches.filter(br => br.status === 'Active')
                    : [];

                if (activeBranches.length > 0) {
                    branchSelect.append('<option value="" disabled selected>-- Select Branch --</option>');
                    activeBranches.forEach(br => {
                        const taText = br.branch_name_ta ? ` (${br.branch_name_ta})` : '';
                        const feeVal = parseFloat(br.valuation_fee) || 0;
                        const feeText = feeVal > 0 ? ` - ₹${feeVal.toLocaleString('en-IN')}` : '';
                        branchSelect.append(
                            $('<option></option>')
                                .val(br.id)
                                .attr('data-branch-name', br.branch_name)
                                .attr('data-bank-name', selectedBank.bank_name)
                                .attr('data-fee', feeVal)
                                .text(`${br.branch_name}${taText}${feeText}`)
                        );
                    });
                    $('#branchSelectContainer').removeClass('d-none');
                } else {
                    branchSelect.append('<option value="" disabled selected>-- No active branches for this bank --</option>');
                    $('#branchSelectContainer').removeClass('d-none');
                }
            });

            // Branch selection -> auto-suggest valuation fee
            $('#ledgerBranchSelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const selected = $(this).find(':selected');
                const fee = selected.data('fee');
                if (fee && (!$('#newLedgerAmount').val() || $('#newLedgerAmount').data('autofilled') === 'bank')) {
                    $('#newLedgerAmount').val(fee).data('autofilled', 'bank');
                }
            });

            // Property Selection -> Show property types/units
            $('#ledgerPropertySelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const propertyId = parseInt($(this).val(), 10);
                const propertyTypeSelect = $('#ledgerPropertyTypeSelect');
                propertyTypeSelect.empty();

                if (!propertyId) {
                    $('#propertyTypeSelectContainer').addClass('d-none');
                    propertyTypeSelect.append('<option value="" disabled selected>-- Select Property Type / Unit --</option>');
                    return;
                }

                const selectedProperty = activePropertiesData.find(p => p.id === propertyId);
                const activePropertyTypes = (selectedProperty && selectedProperty.property_types)
                    ? selectedProperty.property_types.filter(pt => pt.status === 'Active')
                    : [];

                if (activePropertyTypes.length > 0) {
                    propertyTypeSelect.append('<option value="" disabled selected>-- Select Property Type / Unit --</option>');
                    activePropertyTypes.forEach(pt => {
                        const rentVal = parseFloat(pt.monthly_rent) || 0;
                        const rentText = rentVal > 0 ? ` - ₹${rentVal.toLocaleString('en-IN')}/mo` : '';
                        const tenantText = pt.tenant_name ? ` (Tenant: ${pt.tenant_name})` : '';
                        const label = `${pt.shop_home_office_name} [${pt.property_type}]${tenantText}${rentText}`;
                        propertyTypeSelect.append(
                            $('<option></option>')
                                .val(pt.id)
                                .attr('data-unit-name', pt.shop_home_office_name)
                                .attr('data-prop-type', pt.property_type)
                                .attr('data-property-name', selectedProperty.property_name)
                                .attr('data-tenant', pt.tenant_name || '')
                                .attr('data-rent', rentVal)
                                .text(label)
                        );
                    });
                    $('#propertyTypeSelectContainer').removeClass('d-none');
                } else {
                    propertyTypeSelect.append('<option value="" disabled selected>-- No active units/types for this property --</option>');
                    $('#propertyTypeSelectContainer').removeClass('d-none');
                }
            });

            // Property type selection -> auto-suggest monthly rent
            $('#ledgerPropertyTypeSelect').on('change', function () {
                $("#newLedgerAmount").val('');
                const selected = $(this).find(':selected');
                const rent = selected.data('rent');
                if (rent && (!$('#newLedgerAmount').val() || $('#newLedgerAmount').data('autofilled') === 'rental')) {
                    $('#newLedgerAmount').val(rent).data('autofilled', 'rental');
                }
            });

            $('#newLedgerAmount').on('input', function () {
                $(this).removeData('autofilled');
            });

            // Image preview
            $('#newLedgerImage').on('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        showToast('Image file size must be less than 5MB.', 'warning');
                        $(this).val('');
                        $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        $('#newLedgerImagePreviewImg').attr('src', evt.target.result);
                        $('#newLedgerImageName').text(file.name);
                        $('#newLedgerImageSize').text((file.size / 1024).toFixed(1) + ' KB');
                        $('#newLedgerImagePreviewBox').removeAttr('style').addClass('d-flex');
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                }
            });

            $('#newLedgerImageClearBtn').on('click', function () {
                $('#newLedgerImage').val('');
                $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
            });

            function clearFormErrors() {
                $('#ledgerForm .is-invalid').removeClass('is-invalid');
                $('#ledgerForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (!errors) return;

                if (errors.entry_date) {
                    $('#newLedgerDate').addClass('is-invalid');
                    $('#errEntryDate').text(errors.entry_date[0]).show();
                }
                if (errors.flow) {
                    $('#newLedgerFlow').addClass('is-invalid');
                    $('#errFlow').text(errors.flow[0]).show();
                }
                if (errors.source) {
                    $('#newLedgerSource').addClass('is-invalid');
                    $('#errSource').text(errors.source[0]).show();
                }
                if (errors.amount) {
                    $('#newLedgerAmount').addClass('is-invalid');
                    $('#errAmount').text(errors.amount[0]).show();
                }
                if (errors.description || errors.desc) {
                    $('#newLedgerDesc').addClass('is-invalid');
                    $('#errDescription').text((errors.description || errors.desc)[0]).show();
                }
                if (errors.project_site_id) {
                    $('#constructionSite').addClass('is-invalid');
                    $('#errProjectSiteId').text(errors.project_site_id[0]).show();
                }
                if (errors.client_name) {
                    $('#constructionClientName').addClass('is-invalid');
                    $('#errClientName').text(errors.client_name[0]).show();
                }
                if (errors.cash_received_from) {
                    $('input[name="cash_received_from"]').addClass('is-invalid');
                    $('#errCashReceivedFrom').text(errors.cash_received_from[0]).show();
                }
                if (errors.supplier_id) {
                    $('#ledgerSupplier').addClass('is-invalid');
                    $('#errSupplierId').text(errors.supplier_id[0]).show();
                }
                if (errors.bank_id) {
                    $('#ledgerBankSelect').addClass('is-invalid');
                    $('#errBankId').text(errors.bank_id[0]).show();
                }
                if (errors.branch_id) {
                    $('#ledgerBranchSelect').addClass('is-invalid');
                    $('#errBranchId').text(errors.branch_id[0]).show();
                }
                if (errors.bank_customer_ref) {
                    $('#bankCustomer').addClass('is-invalid');
                    $('#errBankCustomerRef').text(errors.bank_customer_ref[0]).show();
                }
                if (errors.property_id) {
                    $('#ledgerPropertySelect').addClass('is-invalid');
                    $('#errPropertyId').text(errors.property_id[0]).show();
                }
                if (errors.property_type_id) {
                    $('#ledgerPropertyTypeSelect').addClass('is-invalid');
                    $('#errPropertyTypeId').text(errors.property_type_id[0]).show();
                }
                if (errors.office_expense_details) {
                    $('#officeExpenseDetails').addClass('is-invalid');
                    $('#errOfficeExpenseDetails').text(errors.office_expense_details[0]).show();
                }
                if (errors.receipt_image) {
                    $('#newLedgerImage').addClass('is-invalid');
                    $('#errReceiptImage').text(errors.receipt_image[0]).show();
                }
            }

            $('#ledgerForm input, #ledgerForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
            });

            function resetLedgerModalForm() {
                $('#ledgerForm')[0].reset();
                clearFormErrors();
                $('#newLedgerDate').val(getTodayString());
                $('#constructionSite').val('');
                $('#constructionClientContainer').addClass('d-none');
                $('#constructionClientName').val('');
                $('input[name="cash_received_from"]').prop('checked', false);
                $('#cashReceivedFromContainer').addClass('d-none');
                $('#wageCheckCard').addClass('d-none');
                $('#wageCheckItemsContainer').empty();
                $('#extraWorkersContainer').empty();
                $('#ledgerDailyAllocationId').val('');
                $('#ledgerIsAmWagePayment').val('0');
                $('input[name="wage_cash_source"][value="office_cash"]').prop('checked', true);
                currentWageCheckData = null;
                $('#ledgerSupplier').val('');
                $('#ledgerBankSelect').val('');
                $('#ledgerBranchSelect').empty().append('<option value="" disabled selected>-- Select Branch --</option>');
                $('#branchSelectContainer').addClass('d-none');
                $('#bankCustomer').val('');
                $('#ledgerPropertySelect').val('');
                $('#ledgerPropertyTypeSelect').empty().append('<option value="" disabled selected>-- Select Property Type / Unit --</option>');
                $('#propertyTypeSelectContainer').addClass('d-none');
                $('#newLedgerAmount').removeData('autofilled');
                $('#newLedgerDesc').removeData('autofilled');
                $('#newLedgerImage').val('');
                $('#newLedgerImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                $('#dynamicTransactionTypesContainer').addClass('d-none');
                $('#txnTypeFieldsWrapper').empty();
                syncSourceOptions('Construction');
            }

            // Submit Form via AJAX
            $('#ledgerForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();
                const form = this;
                const formData = new FormData(form);

                $('#btnSaveLedgerEntry').prop('disabled', true);
                $('#saveSpinner').removeClass('d-none');

                $.ajax({
                    url: "{{ route('ledger.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        $('#btnSaveLedgerEntry').prop('disabled', false);
                        $('#saveSpinner').addClass('d-none');

                        if (res.success) {
                            showToast(res.message || 'Ledger entry recorded successfully!', 'success');
                            $('#ledgerModal').modal('hide');
                            resetLedgerModalForm();
                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                        } else {
                            showToast(res.message || 'Failed to save entry', 'danger');
                        }
                    },
                    error: function (xhr) {
                        $('#btnSaveLedgerEntry').prop('disabled', false);
                        $('#saveSpinner').addClass('d-none');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving the ledger entry.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;
            const todayStr = '{{ \Carbon\Carbon::today()->toDateString() }}';
            const yesterdayStr = '{{ \Carbon\Carbon::yesterday()->toDateString() }}';

            function formatMoney(amount) {
                return parseFloat(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function calculateDateSummary(selectedDate) {
                let opening = 0;
                let dayInc = 0;
                let dayExp = 0;

                $('#ledgerTableBody tr[data-flow]').each(function () {
                    const rowDate = ($(this).data('date') || '').toString().trim();
                    const dr = parseFloat($(this).data('dr')) || 0;
                    const cr = parseFloat($(this).data('cr')) || 0;

                    if (rowDate < selectedDate) {
                        opening += (dr - cr);
                    } else if (rowDate === selectedDate) {
                        dayInc += dr;
                        dayExp += cr;
                    }
                });

                return {
                    opening: opening,
                    income: dayInc,
                    expense: dayExp,
                    closing: opening + dayInc - dayExp
                };
            }

            function updateSummaryCards() {
                const dateFilter = $('#ledgerDateFilter').val();
                const customDate = $('#ledgerCustomDateInput').val();

                if (dateFilter === 'all') {
                    $('#openingBalanceRow').hide();
                    let totalInc = 0;
                    let totalExp = 0;
                    $('#ledgerTableBody tr[data-flow]').each(function () {
                        totalInc += parseFloat($(this).data('dr')) || 0;
                        totalExp += parseFloat($(this).data('cr')) || 0;
                    });
                    const closing = totalInc - totalExp;

                    $('#lblLedgerIncome').text('Total Income (Dr)');
                    $('#lblLedgerIncomeSub').text('Total Income');
                    $('#lblLedgerExpense').text('Total Expense (Cr)');
                    $('#lblLedgerExpenseSub').text('Total Expense');
                    $('#lblLedgerClosing').text('Closing Running Balance');
                    $('#lblLedgerClosingSub').text('Closing Balance');

                    $('#ledgerOpeningBal').text('₹0.00');
                    $('#ledgerTotalIncome').text(totalInc > 0 ? '+₹' + formatMoney(totalInc) : '₹0.00');
                    $('#ledgerTotalExpense').text(totalExp > 0 ? '−₹' + formatMoney(totalExp) : '₹0.00');
                    $('#ledgerClosingBal').text((closing >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(closing)));
                } else {
                    const targetDate = (dateFilter === 'today') ? todayStr : ((dateFilter === 'yesterday') ? yesterdayStr : customDate);
                    const stats = calculateDateSummary(targetDate);

                    $('#openingBalanceRow').show();
                    $('#tableOpeningBalCell').text((stats.opening >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(stats.opening)));

                    const dateLabel = (dateFilter === 'today') ? "Today's" : ((dateFilter === 'yesterday') ? "Yesterday's" : targetDate);
                    $('#lblLedgerIncome').text(`${dateLabel} Income (Dr)`);
                    $('#lblLedgerIncomeSub').text('Today\'s Income');
                    $('#lblLedgerExpense').text(`${dateLabel} Expense (Cr)`);
                    $('#lblLedgerExpenseSub').text('Today\'s Expense');
                    $('#lblLedgerClosing').text(`${dateLabel} Closing Balance`);
                    $('#lblLedgerClosingSub').text('Closing Balance');

                    $('#ledgerOpeningBal').text((stats.opening >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(stats.opening)));
                    $('#ledgerTotalIncome').text(stats.income > 0 ? '+₹' + formatMoney(stats.income) : '₹0.00');
                    $('#ledgerTotalExpense').text(stats.expense > 0 ? '−₹' + formatMoney(stats.expense) : '₹0.00');
                    $('#ledgerClosingBal').text((stats.closing >= 0 ? '₹' : '−₹') + formatMoney(Math.abs(stats.closing)));
                }
            }

            function getMatchingRows() {
                const query = ($('#ledgerSearchInput').val() || '').toLowerCase().trim();
                const selectedSite = ($('#siteFilter').val() || '').trim();
                const source = $('#sourceFilter').val();
                const flow = $('#flowFilter').val();
                const dateFilter = $('#ledgerDateFilter').val();
                const customDate = $('#ledgerCustomDateInput').val();
                const matching = [];

                $('#ledgerTableBody tr[data-flow]').each(function () {
                    const desc = ($(this).data('desc') || '').toString().toLowerCase();
                    const rowSource = ($(this).data('source') || '').toString();
                    const rowFlow = ($(this).data('flow') || '').toString();
                    const rowSite = ($(this).data('site') || '').toString().trim();
                    const rowDate = ($(this).data('date') || '').toString().trim();

                    const matchQuery = !query || desc.includes(query) || rowSource.toLowerCase().includes(query) || rowSite.toLowerCase().includes(query);
                    const matchSite = !selectedSite || (rowSite !== '-' && rowSite.toLowerCase() === selectedSite.toLowerCase());
                    const matchSource = !source || rowSource === source;
                    const matchFlow = !flow || rowFlow === flow;

                    let matchDate = true;
                    if (dateFilter === 'today') {
                        matchDate = (rowDate === todayStr);
                    } else if (dateFilter === 'yesterday') {
                        matchDate = (rowDate === yesterdayStr);
                    } else if (dateFilter === 'custom' && customDate) {
                        matchDate = (rowDate === customDate);
                    }

                    if (matchQuery && matchSite && matchSource && matchFlow && matchDate) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                updateSummaryCards();
                const matchingRows = getMatchingRows();
                const totalCount = $('#ledgerTableBody tr[data-flow]').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;
                const selectedSite = ($('#siteFilter').val() || '').trim();

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows and date summary rows initially
                $('#ledgerTableBody tr[data-flow]').hide();
                $('.date-summary-row').hide();

                if (totalCount === 0) {
                    $('#ledgerFilterEmptyRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#ledgerCountBadge').html('Showing <strong class="text-dark">0</strong> entries');
                    return;
                }

                if (filteredCount === 0) {
                    $('#ledgerFilterEmptyRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    let badgeHtml = `Showing <strong class="text-dark">0</strong> of <strong class="text-dark">${totalCount}</strong> entries`;
                    if (selectedSite) {
                        badgeHtml += ` <span class="badge bg-info-subtle text-info border border-info-subtle ms-1"><i class="bi bi-building me-1"></i>Site: ${escapeHtml(selectedSite)}</span>`;
                    }
                    $('#ledgerCountBadge').html(badgeHtml);
                    return;
                } else {
                    $('#ledgerFilterEmptyRow').addClass('d-none');
                    $('#tablePaginationContainer').removeClass('d-none');
                }

                // Slice visible rows for current page
                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, filteredCount);

                const visibleDates = new Set();
                for (let i = startIndex; i < endIndex; i++) {
                    const row = $(matchingRows[i]);
                    row.show();
                    const d = row.data('date');
                    if (d) {
                        visibleDates.add(d);
                    }
                }

                visibleDates.forEach(function (d) {
                    $(`.date-summary-row[data-summary-date="${d}"]`).show();
                });

                // Update summary footer text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' entries';
                $('#paginationSummary').html(summaryHtml);

                let badgeHtml = `Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> entries`;
                if (selectedSite) {
                    badgeHtml += ` <span class="badge bg-info-subtle text-info border border-info-subtle ms-1"><i class="bi bi-building me-1"></i>Site: ${escapeHtml(selectedSite)}</span>`;
                }
                $('#ledgerCountBadge').html(badgeHtml);

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
                perPage = parseInt($(this).val()) || 10;
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

            $('#ledgerDateFilter').on('change', function () {
                const val = $(this).val();
                if (val === 'custom') {
                    $('#ledgerCustomDateCol').removeClass('d-none');
                } else {
                    $('#ledgerCustomDateCol').addClass('d-none');
                }
                currentPage = 1;
                updatePagination();
            });

            $('#ledgerCustomDateInput').on('change input', function () {
                currentPage = 1;
                updatePagination();
            });

            $('#ledgerSearchInput, #siteFilter, #sourceFilter, #flowFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            $('#ledgerFilterResetBtn, #clearLedgerFiltersBtn').on('click', function () {
                $('#ledgerSearchInput').val('');
                $('#ledgerDateFilter').val('today');
                $('#ledgerCustomDateCol').addClass('d-none');
                $('#siteFilter').val('');
                $('#sourceFilter').val('');
                $('#flowFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Initial pagination run
            updatePagination();

            $('#ledgerModal').on('show.bs.modal', function () {
                clearFormErrors();
            });

            $('#ledgerModal').on('hidden.bs.modal', function () {
                resetLedgerModalForm();
            });

            // Initialize Page
            syncSourceOptions('Construction');
        });
    </script>
@endpush
