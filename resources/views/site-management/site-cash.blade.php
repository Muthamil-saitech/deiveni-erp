@extends('layouts.app')

@section('title', 'Site Cash Handling')
@section('page_title', 'Site Cash Handling ')

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
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Site Manager Cash-in-Hand Book</h5>
</div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-secondary btn-sm" id="exportCashBookBtn">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Cash Book
</button>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#cashTransactionModal"
                        id="recordCashTxnBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Cash Transaction
</button>
</div>
</div>
</div>

        <!-- Live Cash Formula Banner -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white border-start border-4 border-success">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold mb-1">
                        <i class="bi bi-wallet-fill me-1"></i> Formula
</span>
                    <h6 class="fw-bold text-dark mb-1">Cash-in-Hand = Advance + Collections &minus; Purchases &minus; Wages
                        &minus; Returned</h6>
                    <p class="text-muted small mb-0 font-monospace" id="cashFormulaDisplay">
                        ₹{{ number_format($stats['advance'], 0) }} (Adv) + ₹{{ number_format($stats['collections'], 0) }}
                        (Col) &minus; ₹{{ number_format($stats['purchases'], 0) }} (Pur) &minus;
                        ₹{{ number_format($stats['wages'], 0) }} (Wage) &minus; ₹{{ number_format($stats['returned'], 0) }}
                        (Ret) = <strong class="text-success">₹{{ number_format($stats['cash_in_hand'], 0) }}</strong>
                    </p>
</div>
                <div class="bg-success bg-opacity-10 p-3 rounded-3 text-center text-lg-end border border-success border-opacity-25"
                    style="min-width: 200px;">
                    <small class="text-muted d-block fw-semibold">CURRENT CASH-IN-HAND</small>
                    <h3 class="fw-bold text-success mb-0" id="liveCashInHandTotal">
                        @amount($stats['cash_in_hand'] ?? 0)</h3>
</div>
</div>
</div>

        <!-- Pending Cash Transfers Alert Banner -->
        @if (isset($pendingIncomingTransfers) && $pendingIncomingTransfers->isNotEmpty())
            <div class="alert alert-warning border border-warning shadow-sm mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <strong class="text-dark">
                            <i class="bi bi-bell-fill text-warning me-2 fs-5"></i>Pending Cash Transfer for Acceptance :
</strong>
                        <div class="small text-dark mt-1">
                            @foreach ($pendingIncomingTransfers as $pt)
                                <div>• <strong>{{ $pt->directionLabel() }}</strong>: <strong class="text-primary">₹{{ number_format((float) $pt->amount, 2) }}</strong> sent by {{ $pt->handed_over_by }} ({{ $pt->transfer_date ? \Carbon\Carbon::parse($pt->transfer_date)->format('d M Y') : '' }}). Site: <strong>{{ $pt->projectSite?->name ?? 'N/A' }}</strong>. Please accept to reflect in Site Cash Handling.</div>
                            @endforeach
</div>
</div>
                    <div>
                        <a href="{{ route('cash-transfer.index') }}" class="btn btn-warning btn-sm fw-bold shadow-sm">
                            <i class="bi bi-check-circle me-1"></i>Accept Transfer 
</a>
</div>
</div>
</div>
        @endif

        <!-- 6 Formula Breakdown KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- 1. Advance Received -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">1. Advance Received</span>
                        <i class="bi bi-plus-circle text-primary fs-5"></i>
</div>
                    <h4 class="fw-bold text-primary mb-0" id="kpiAdvance">@amount($stats['advance'])</h4>
                    <small class="text-muted" style="font-size:0.75rem;">From Account Manager / Owner</small>
</div>
</div>

            <!-- 2. Client Collections -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">2. Collections</span>
                        <i class="bi bi-cash-stack text-success fs-5"></i>
</div>
                    <h4 class="fw-bold text-success mb-0" id="kpiCollections">@amount($stats['collections'])</h4>
                    <small class="text-muted" style="font-size:0.75rem;">From Site Clients</small>
</div>
</div>

            <!-- 3. Local Purchases -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">3. Purchases (-)</span>
                        <i class="bi bi-cart-dash text-danger fs-5"></i>
</div>
                    <h4 class="fw-bold text-danger mb-0" id="kpiPurchases">@amount($stats['purchases'] ?? 0)</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Materials & Rentals</small>
</div>
</div>

            <!-- 4. Wages Paid -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">4. Wages Paid (-)</span>
                        <i class="bi bi-people text-danger fs-5"></i>
</div>
                    <h4 class="fw-bold text-danger mb-0" id="kpiWages">@amount($stats['wages'] ?? 0)</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Labour Daily Wages</small>
</div>
</div>

            <!-- 5. Returned to AM -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">5. Returned (-)</span>
                        <i class="bi bi-arrow-left-right text-warning fs-5"></i>
</div>
                    <h4 class="fw-bold text-warning mb-0" id="kpiReturned">@amount($stats['returned'] ?? 0)</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Balance to Office AM</small>
</div>
</div>

            <!-- 6. Total Balance in Hand -->
            <div class="col-6 col-md-4 col-xl">
                <div class="card-stat h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="text-muted small fw-semibold">6. Total Balance in Hand</span>
                        <i class="bi bi-wallet2 text-success fs-5"></i>
</div>
                    <h4 class="fw-bold text-success mb-0" id="kpiBalanceInHand">@amount($stats['cash_in_hand'] ?? 0)</h4>
                    <small class="text-muted" style="font-size:0.75rem;">Net Cash Available (=)</small>
</div>
</div>
</div>

        <!-- Filter & Transaction Log -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="cashTxnSearch"
                            placeholder="Search description, bill no, person, site...">
</div>
</div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="txnTypeFilter">
                        <option value="">All Cash Flow Types</option>
                        <option value="Project Cash Received">Project Cash Received from AM (+)</option>
                        <option value="Owner Advance">Advance Received from Owner (+)</option>
                        <option value="Client Advance">Advance Received from Client (+)</option>
                        <option value="Tool Rental">Tool Rental Purchase (-)</option>
                        <option value="Supplier Payment">Supplier Payment (-)</option>
                        <option value="Daily Wage Payment">Daily Worker Wage Payment (-)</option>
                        <option value="Balance Returned">Balance Returned to Account Manager (-)</option>
                        <option value="Other Expense">Other Expense (-)</option>
                    </select>
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="siteFilter">
                        <option value="">All Project Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
</div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
                <div class="col-12 col-md-2 text-md-end">
                    <span class="small text-muted" id="txnCounter">Showing <strong
                            class="text-dark">{{ $transactions->count() }}</strong>
                        records</span>
</div>
</div>
</div>

        <!-- Transactions Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover" id="cashTxnTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Paid To / Recipient</th>
                            <th>Site Project</th>
                            <th>Supplier</th>
                            <th>Description / Notes</th>
                            <th>Mode & Ref / Bill</th>
                            <th class="text-end">Inflow (+)</th>
                            <th class="text-end">Outflow (-)</th>
                            <th class="text-center" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cashTxnTableBody">
                        @forelse ($transactions as $txn)
                            @php
                                $badgeClass = match ($txn->transaction_type) {
                                    'Client Collection', 'Client Advance' => 'bg-success-subtle text-success border border-success-subtle',
                                    'Material Purchase', 'Supplier Payment', 'Tool Rental', 'Daily Wage Payment', 'Other Expense' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    'Balance Returned' => 'bg-warning-subtle text-dark border border-warning-subtle',
                                    default => 'bg-primary-subtle text-primary border border-primary-subtle',
                                };

                                $formattedDate = $txn->payment_date ? \Carbon\Carbon::parse($txn->payment_date)->format('d M Y') : 'N/A';
                                $siteName = $txn->projectSite?->name ?? 'N/A';
                                $supplierName = $txn->supplier_name ?: ($txn->supplier?->name ?? '');
                            @endphp
                            <tr data-id="{{ $txn->id }}" data-site-id="{{ $txn->project_site_id }}" data-site="{{ $siteName }}"
                                data-type="{{ $txn->transaction_type }}" data-supplier="{{ $supplierName }}"
                                data-desc="{{ $txn->description }}" data-bill="{{ $txn->bill_no }}"
                                data-payment-mode="{{ $txn->payment_mode ?? 'Cash' }}"
                                data-payment-ref="{{ $txn->payment_reference ?? '' }}"
                                data-paid-to="{{ $txn->paid_to ?? 'Site Manager' }}"
                                data-receiver="{{ $txn->receiver_name ?? '' }}"
                                data-bank="{{ $txn->bank_name ?? '' }}"
                                data-upi-id="{{ $txn->upi_id ?? '' }}"
                                data-account-holder="{{ $txn->account_holder_name ?? '' }}"
                                data-in="{{ (float) $txn->in_amount }}" data-out="{{ (float) $txn->out_amount }}">
                                <td class="small">{{ $formattedDate }}<br><span
                                        class="text-muted">{{ $txn->payment_time }}</span></td>
                                <td><span class="badge {{ $badgeClass }}">{{ $txn->transaction_type }}</span></td>
                                <td>
                                    <span class="badge bg-light text-dark border small fw-semibold">
                                        <i class="bi bi-person me-1 text-primary"></i>{{ $txn->paid_to ?? 'Site Manager' }}
</span>
                                    @if ($txn->transaction_type === 'Project Cash Received')
                                        <div class="mt-1">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle small fw-semibold" title="Project Cash received from Account Manager">
                                                <i class="bi bi-arrow-down-left-circle me-1"></i>From: {{ $txn->paid_by ?? 'Account Manager' }}
</span>
</div>
                                    @elseif ($txn->paid_by === 'Account Manager')
                                        <div class="mt-1">
                                            @if ($txn->wage_cash_source === 'project_cash')
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle small fw-semibold" title="Paid from Project Cash by Account Manager (Deducted from Site Cash)">
                                                    <i class="bi bi-cash-stack me-1"></i>Paid by AM (Project Cash)
</span>
                                            @else
                                                <span class="badge bg-info-subtle text-info border border-info-subtle small fw-semibold" title="Paid directly from Office Cash by Account Manager (Not deducted from Site Cash)">
                                                    <i class="bi bi-building me-1"></i>Paid by AM (Office Cash)
</span>
                                            @endif
</div>
                                    @elseif ($txn->receiver_name)
                                        <div class="small text-muted mt-1" title="Handover / Receiver Person">
                                            <i class="bi bi-person-check text-secondary me-1"></i>{{ $txn->receiver_name }}
</div>
                                    @endif
                                </td>
                                <td class="fw-semibold">
                                    <a href="{{ route('sites.show', $txn->projectSite?->encrypted_id ?? \App\Services\IdCipherService::encrypt($txn->project_site_id)) }}"
                                        class="text-dark text-decoration-none">
                                        {{ $siteName }}
</a>
                                </td>
                                <td>
                                    @if ($supplierName)
                                        <div>
                                            <span class="fw-semibold text-primary">
                                                <i class="bi bi-truck-front-fill me-1"></i>{{ $supplierName }}
</span>
                                            @if ($txn->transaction_type === 'Supplier Payment')
                                                <div class="mt-1 d-flex align-items-center gap-1 flex-wrap">
                                                    @if ($txn->is_site_based)
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.68rem;">
                                                            <i class="bi bi-building me-1"></i>Site Based
</span>
                                                    @else
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">
                                                            <i class="bi bi-cash-stack me-1"></i>Total Payment
</span>
                                                    @endif
                                                    @if ($txn->purchase_order_no)
                                                        <span class="badge bg-light text-dark border font-monospace" style="font-size:0.68rem;">
                                                            PO: {{ $txn->purchase_order_no }}
</span>
                                                    @endif
</div>
                                            @endif
                                            @if (!empty($txn->items) && is_array($txn->items))
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @foreach ($txn->items as $it)
                                                        <span class="badge bg-light text-dark border py-1 px-2" style="font-size:0.72rem;">
                                                            <i class="bi bi-box-seam text-secondary me-1"></i>{{ $it['qty'] ?? 1 }}
                                                            {{ $it['unit'] ?? 'Unit' }} {{ $it['materialName'] ?? ($it['material'] ?? ($it['name'] ?? '')) }}
</span>
                                                    @endforeach
</div>
                                            @endif
</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($txn->description)
                                        @php
                                            $cleanDesc = $txn->description;
                                            if (str_contains($cleanDesc, 'Painter & Electrician daily wages')) {
                                                $cleanDesc = 'Painter & Electrician daily wages';
                                            }
                                        @endphp
                                        <div class="text-dark small text-truncate" style="max-width: 250px;" title="{{ $txn->description }}">
                                            {{ \Illuminate\Support\Str::limit($cleanDesc, 45) }}
</div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                        @if ($txn->payment_mode === 'Cheque')
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-0" style="font-size: 0.72rem;">
                                                <i class="bi bi-card-text me-1"></i>Cheque
</span>
                                        @elseif ($txn->payment_mode === 'UPI')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-0" style="font-size: 0.72rem;">
                                                <i class="bi bi-qr-code me-1"></i>UPI
</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size: 0.72rem;">
                                                <i class="bi bi-cash-stack me-1"></i>Cash
</span>
                                        @endif
                                        @if ($txn->receipt_image)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                                                title="Receipt image attached">
                                                <i class="bi bi-image"></i>
</span>
                                        @endif
</div>
                                    @if ($txn->payment_reference)
                                        <div class="font-monospace text-dark small fw-semibold" title="Payment Reference / Cheque No / UPI ID">
                                            <i class="bi bi-hash text-muted"></i>{{ $txn->payment_reference }}
</div>
                                    @endif
                                    @if ($txn->bank_name)
                                        <div class="small text-muted" title="Bank Name">
                                            <i class="bi bi-bank me-1"></i>{{ $txn->bank_name }}
</div>
                                    @endif
                                    @if ($txn->upi_id)
                                        <div class="small font-monospace text-muted" title="Supplier UPI ID / Mobile">
                                            <i class="bi bi-phone me-1"></i>{{ $txn->upi_id }}
</div>
                                    @endif
                                    @if ($txn->account_holder_name)
                                        <div class="small text-dark fw-semibold" title="UPI Account Holder Name">
                                            <i class="bi bi-person-check me-1 text-success"></i>A/C: {{ $txn->account_holder_name }}
</div>
                                    @endif
                                    @if ($txn->bill_no)
                                        <div class="font-monospace text-secondary small" title="Bill / Voucher No">
                                            <small class="text-muted">Bill:</small> {{ $txn->bill_no }}
</div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold text-success">
                                    {{ (float) $txn->in_amount > 0 ? '+₹' . number_format((float) $txn->in_amount, 2) : '-' }}
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    {{ (float) $txn->out_amount > 0 ? '−₹' . number_format((float) $txn->out_amount, 2) : '-' }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('site-cash.show', $txn->encrypted_id) }}"
                                        class="btn btn-outline-primary btn-sm px-2 py-1"
                                        title="View Details ">
                                        <i class="bi bi-eye me-1"></i>View
</a>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyCashRow">
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-wallet2 fs-2 d-block mb-2 text-secondary"></i>
                                    <div class="fw-semibold">No Cash Transactions Recorded</div>
                                    <small class="text-muted">Click "Record Cash Transaction" to add your first entry.</small>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching cash transactions found for your search or filter criteria.
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
                            id="pageEnd">{{ min(10, $transactions->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $transactions->count() }}</strong> transactions
</span>
</div>
                <nav aria-label="Cash transactions table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- Record Cash Transaction Modal -->
    <div class="modal fade" id="cashTransactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Site Cash Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="cashTransactionForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6" id="siteSelectGroup">
                                <label class="form-label small fw-semibold">Project Site  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newTxnSite" name="project_site_id">
                                    <option value="" disabled selected>-- Select Project Site --
</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}" data-name="{{ $site->name }}">{{ $site->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errNewTxnSite"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Transaction Flow Type  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newTxnType" name="transaction_type">
                                    <option value="" disabled selected>-- Select Flow Type --</option>
                                    <option value="Owner Advance">Advance Received from Owner (+)</option>
                                    <option value="Client Advance">Advance Received from Client (+)</option>
                                    <option value="Client Direct to Supplier">Client Direct to Supplier  (-)</option>
                                    <option value="Tool Rental">Tool Rental Purchase (-)</option>
                                    <option value="Supplier Payment">Supplier Payment (-)</option>
                                    <option value="Daily Wage Payment">Daily Worker Wage Payment (-)</option>
                                    <option value="Balance Returned">Balance Returned to Account Manager (-)</option>
                                    <option value="Other Expense">Other Expense (-)</option>
                                </select>
                                <div class="invalid-feedback" id="errNewTxnType"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Paid To / Handled By  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="newTxnPaidTo" name="paid_to">
                                    <option value="Site Manager" selected>Site Manager</option>
                                    <option value="Account Manager">Account Manager</option>
                                    <option value="Direct to Supplier">Direct to Supplier</option>
                                    <option value="Supplier Delivery Person">Supplier Delivery Person / Rep</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div class="invalid-feedback" id="errNewTxnPaidTo"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Payment Mode  <span
                                        class="text-danger">*</span></label>
                                <div class="d-flex gap-3 align-items-center flex-wrap pt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_mode" id="payModeCash"
                                            value="Cash" checked>
                                        <label class="form-check-label small fw-semibold text-success" for="payModeCash">
                                            <i class="bi bi-cash-stack me-1"></i>Cash 
</label>
</div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_mode" id="payModeCheque"
                                            value="Cheque">
                                        <label class="form-check-label small fw-semibold text-warning-emphasis" for="payModeCheque">
                                            <i class="bi bi-card-text me-1"></i>Cheque 
</label>
</div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_mode" id="payModeUPI"
                                            value="UPI">
                                        <label class="form-check-label small fw-semibold text-info-emphasis" for="payModeUPI">
                                            <i class="bi bi-qr-code me-1"></i>UPI 
</label>
</div>
</div>
                                <div class="invalid-feedback" id="errNewTxnPaymentMode"></div>
</div>

                            <!-- Cheque Extended Details Group -->
                            <div class="col-12" id="chequeDetailsGroup" style="display: none;">
                                <div class="p-3 rounded-3 border bg-warning-subtle bg-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="small fw-bold text-dark">
                                            <i class="bi bi-card-text text-warning me-1"></i>Cheque Payment Details 
</span>
                                        <span class="badge bg-warning text-dark small">Cheque Mode</span>
</div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">Cheque Number  <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-hash"></i></span>
                                                <input type="text" class="form-control font-monospace fw-bold" id="newTxnChequeNo" placeholder="e.g. CHQ-981245">
</div>
                                            <div class="invalid-feedback" id="errNewTxnChequeNo"></div>
</div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">Bank Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-bank"></i></span>
                                                <input type="text" class="form-control" id="newTxnBankName" name="bank_name" placeholder="e.g. SBI, Indian Bank, HDFC">
</div>
</div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">Cheque Handed Over To / Received By</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                                <input type="text" class="form-control" id="newTxnChequeReceiver" placeholder="e.g. Site Manager, Account Manager, Supplier Contact">
</div>
</div>
</div>
</div>
</div>

                            <!-- UPI Extended Details Group -->
                            <div class="col-12" id="upiDetailsGroup" style="display: none;">
                                <div class="p-3 rounded-3 border bg-info-subtle bg-opacity-25">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="small fw-bold text-dark">
                                            <i class="bi bi-qr-code text-info me-1"></i>UPI Transaction Details 
</span>
                                        <span class="badge bg-info text-white small">UPI Mode</span>
</div>
                                    <div class="row g-2">
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">UPI Ref / UTR No  <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-hash"></i></span>
                                                <input type="text" class="form-control font-monospace fw-bold" id="newTxnUpiRef" placeholder="e.g. 438920194821">
</div>
                                            <div class="invalid-feedback" id="errNewTxnUpiRef"></div>
</div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">Supplier UPI ID / Mobile</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-phone"></i></span>
                                                <input type="text" class="form-control font-monospace" id="newTxnUpiId" name="upi_id" placeholder="e.g. 9444111222@upi / GPay No">
</div>
</div>
                                        <div class="col-12 col-md-4">
                                            <label class="form-label small fw-semibold">UPI Account Holder Name</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-person-check"></i></span>
                                                <input type="text" class="form-control fw-semibold" id="newTxnAccountHolder" name="account_holder_name" placeholder="e.g. Bala Murugan / Balaji Metals">
</div>
</div>
</div>
</div>
</div>

                            <!-- Hidden inputs for synchronized submission -->
                            <input type="hidden" name="payment_reference" id="newTxnPaymentReference">
                            <input type="hidden" name="receiver_name" id="newTxnReceiverName">

                            <!-- Supplier selection group (When Flow Type is Supplier Payment) -->
                            <div class="col-12" id="purchaseOrderSelectGroup" style="display: none;">
                                <div class="p-3 rounded-3 bg-light border">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            <i class="bi bi-box-arrow-in-down text-primary me-1"></i>Material / Supplier
                                            Source 
</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="material_source"
                                                    id="sourceThirdParty" value="third_party" checked>
                                                <label class="form-check-label small fw-semibold" for="sourceThirdParty">
                                                    Third-Party Supplier 
</label>
</div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="material_source"
                                                    id="sourceWarehouse" value="own_warehouse">
                                                <label class="form-check-label small fw-semibold text-primary"
                                                    for="sourceWarehouse">
                                                    Own Warehouse 
</label>
</div>
</div>
</div>

                                    <!-- Third Party Supplier Selection -->
                                    <div id="thirdPartySupplierBox">
                                        <!-- Payment Basis (Site Based vs Total) - Only shown if supplier in master is NOT site-based -->
                                        <div class="mb-3" id="supplierPaymentScopeBox" style="display: none;">
                                            <label class="form-label small fw-semibold text-dark mb-1">
                                                <i class="bi bi-diagram-3 text-primary me-1"></i>Payment Basis 
</label>
                                            <div class="d-flex gap-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="supplier_payment_scope"
                                                        id="scopeSiteBased" value="site_based" checked>
                                                    <label class="form-check-label small fw-semibold" for="scopeSiteBased">
                                                        <i class="bi bi-building me-1 text-primary"></i>Site Based 
</label>
</div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="supplier_payment_scope"
                                                        id="scopeTotal" value="total">
                                                    <label class="form-check-label small fw-semibold text-primary" for="scopeTotal">
                                                        <i class="bi bi-cash-stack me-1 text-success"></i>Total 
</label>
</div>
</div>
</div>

                                        <label class="form-label small fw-semibold text-dark">
                                            <i class="bi bi-truck text-primary me-1"></i>Select Supplier 
                                            <span class="text-danger">*</span>
</label>
                                        <select class="form-select fw-semibold mb-2" id="newTxnSupplier" name="supplier_id">
                                            <option value="" disabled selected>-- Select Supplier --
</option>
                                            @foreach ($suppliers as $supplier)
                                                <option value="{{ $supplier->id }}"
                                                    data-name="{{ $supplier->name }}"
                                                    data-contact="{{ $supplier->contact_person }}"
                                                    data-phone="{{ $supplier->phone }}"
                                                    data-is-site-based="{{ $supplier->is_site_based ? '1' : '0' }}">
                                                    {{ $supplier->name }}
                                                    @if ($supplier->contact_person)
                                                        ({{ $supplier->contact_person }})
                                                    @endif
</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" id="errNewTxnSupplier"></div>

                                        <!-- Cash Handover Person (ONLY for Supplier Option in Cash Mode) -->
                                        <div class="mt-2" id="cashReceiverGroup" style="display: none;">
                                            <label class="form-label small fw-semibold text-dark mb-1">
                                                <i class="bi bi-person-check text-primary me-1"></i>Received By 
</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                                                <input type="text" class="form-control" id="newTxnCashReceiver" placeholder="Person Name">
</div>
</div>

                                        <!-- Purchase Orders Select Section (Shown ONLY when Site Based) -->
                                        <div id="poListSection" class="mt-3">
                                            <label class="form-label small fw-semibold text-dark mb-1"
                                                for="newTxnPurchaseOrder">
                                                <i class="bi bi-file-earmark-ruled text-primary me-1"></i>Select
                                                Purchase Order 
</label>
                                            <select class="form-select fw-semibold" id="newTxnPurchaseOrder"
                                                name="purchase_order_no">
                                                <option value="" selected>-- Select Purchase Order --
</option>
                                            </select>
                                            <div class="invalid-feedback" id="errNewTxnPurchaseOrder"></div>
</div>
</div>

                                    <!-- Warehouse In-Stock Material Selection -->
                                    <div id="warehouseMaterialBox" style="display: none;">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            <i class="bi bi-boxes text-primary me-1"></i>Select In-Stock Materials from
                                            Central Warehouse 
</label>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered bg-white mb-2" id="siteCashWhTable">
                                                <thead class="table-light small">
                                                    <tr>
                                                        <th>Material</th>
                                                        <th style="width: 90px;">In Stock</th>
                                                        <th style="width: 100px;">Rate (₹)</th>
                                                        <th style="width: 90px;">Qty</th>
                                                        <th style="width: 110px;">Total (₹)</th>
                                                        <th style="width: 35px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="siteCashWhTableBody">
                                                    <!-- Dynamic rows -->
                                                </tbody>
                                            </table>
</div>
                                        <button type="button" class="btn btn-outline-primary btn-sm py-1"
                                            id="addSiteCashWhRowBtn">
                                            <i class="bi bi-plus-circle me-1"></i> Add Material
</button>
                                        <input type="hidden" name="items" id="newTxnItemsJson">
</div>
</div>
</div>

                            <!-- Daily Worker Allocation Section (When Flow Type is Daily Worker Wage Payment) -->
                            <div class="col-12 col-md-6" id="dailyAllocationDateGroup" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-dark mb-0" for="newTxnDailyAllocation">
                                        <i class="bi bi-calendar-event text-primary me-1"></i>Allocation Date  <span class="text-danger">*</span>
</label>
                                    <span class="badge bg-primary-subtle text-primary border small" id="allocationCountBadge" style="display:none;">0 Days</span>
</div>
                                <select class="form-select fw-semibold" id="newTxnDailyAllocation">
                                    <option value="" selected>-- Select Project Site above --</option>
                                </select>
</div>

                            <div class="col-12" id="dailyAllocationWorkersGroup" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-dark mb-0">
                                        <i class="bi bi-people-fill text-primary me-1"></i>Work Details 
</label>
                                    <span class="badge bg-primary-subtle text-primary border small" id="workersTotalCountBadge" style="display:none;">0 Workers</span>
</div>
                                <div id="newTxnWorkersContainer" class="p-2 rounded border bg-light d-flex flex-wrap gap-2 align-items-center" style="min-height: 44px;">
                                    <span class="text-muted small fst-italic" id="newTxnWorkersPlaceholder">
                                        <i class="bi bi-info-circle me-1"></i>Worker allocation details will appear here once an allocation date is selected.
</span>
</div>
                                <input type="hidden" id="newTxnWorkersDisplay" name="workers_summary">
</div>

                            <!-- Balance Returned to Account Manager Section (Office Cash Wage & Cash Transfer Details) -->
                            <div class="col-12" id="balanceReturnedOfficeCashGroup" style="display: none;">
                                <div class="card border border-primary-subtle shadow-sm bg-white rounded-3 mb-2">
                                    <div class="card-header bg-primary-subtle py-2 px-2 px-md-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small fw-bold text-primary">
                                                <i class="bi bi-wallet2 me-1"></i>Balance to Return 
</span>
</div>
                                        <div class="btn-group btn-group-sm" role="group" id="returnSourceToggleGroup">
                                            <button type="button" class="btn btn-primary active py-0 px-2 small" id="returnTabWagesBtn" style="font-size: 0.76rem;">
                                                <i class="bi bi-people me-1"></i>Office Cash Wages 
</button>
                                            <button type="button" class="btn btn-outline-primary py-0 px-2 small" id="returnTabTransfersBtn" style="font-size: 0.76rem;">
                                                <i class="bi bi-arrow-left-right me-1"></i>Cash Transfer 
</button>
</div>
</div>
                                    <div class="card-body p-2 p-md-3" id="balanceReturnedOfficeCashBody">
                                        <div class="text-muted small py-1">
                                            <i class="bi bi-arrow-up-circle text-primary me-1"></i>
                                            Select a Project Site above to display returnable Office Cash wages or cash transfer details.
</div>
</div>
                                    <div class="card-body p-2 p-md-3 d-none" id="balanceReturnedCashTransferBody">
                                        <div class="text-muted small py-1">
                                            <i class="bi bi-arrow-up-circle text-primary me-1"></i>
                                            Select a Project Site above to display returnable Cash Transfer details.
</div>
</div>
</div>
                                <input type="hidden" name="wage_cash_source" id="newTxnWageCashSource" value="">
                                <input type="hidden" name="returned_wage_ids" id="newTxnReturnedWageIds" value="[]">
                                <input type="hidden" name="cash_transfer_id" id="newTxnCashTransferId" value="">
                                <input type="hidden" name="ledger_id" id="newTxnLedgerId" value="">
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" id="txnAmountLabel">Cash Amount (₹) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="any" class="form-control fw-bold" id="newTxnAmount"
                                        name="amount" placeholder="e.g. 5000">
                                    <div class="invalid-feedback" id="errNewTxnAmount"></div>
</div>
                                <div class="form-text small text-success fw-semibold" id="labourWageHint" style="display: none;">
                                    <i class="bi bi-cash me-1"></i>Labour Wage: <span id="labourWageHintText">₹0.00</span>
</div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Bill / Voucher / Ref No</label>
                                <input type="text" class="form-control font-monospace" id="newTxnBill" name="bill_no"
                                    placeholder="e.g. BILL-1234">
                                <div class="invalid-feedback" id="errNewTxnBill"></div>
</div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Bill / Receipt Image <span
                                        class="text-muted fw-normal">(Optional)</span></label>
                                <input type="file" class="form-control" id="newTxnImage" name="receipt_image"
                                    accept="image/png, image/jpeg, image/jpg, image/webp">
                                <div class="invalid-feedback" id="errNewTxnImage"></div>
                                <div id="newTxnImagePreviewBox"
                                    class="mt-2 p-2 border rounded bg-light align-items-center justify-content-between"
                                    style="display: none !important;">
                                    <div class="d-flex align-items-center gap-2">
                                        <img id="newTxnImagePreviewImg" src="" alt="Preview"
                                            style="width: 44px; height: 44px; object-fit: cover;" class="rounded border">
                                        <div>
                                            <div class="small fw-semibold text-truncate" id="newTxnImageName"
                                                style="max-width: 240px;"></div>
                                            <small class="text-muted" id="newTxnImageSize"></small>
</div>
</div>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
                                        id="newTxnImageClearBtn" title="Remove attached image">
                                        <i class="bi bi-x-circle me-1"></i>Remove
</button>
</div>
</div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Description / Notes <span
                                        class="text-muted fw-normal">(Optional)</span></label>
                                <textarea class="form-control" id="newTxnDesc" name="description" rows="2"
                                    placeholder="e.g. Urgent plumbing pipe purchase & transport (optional)"></textarea>
                                <div class="invalid-feedback" id="errNewTxnDesc"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveCashTxnBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner"></span>
                            Record Transaction
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
            const warehouseInventory = @json($warehouseItems ?? []);
            const allPurchaseOrders = @json($purchaseOrders ?? []);
            let allDailyAllocations = @json($dailyAllocations ?? []);

            function syncPaymentModeFields() {
                const mode = $('input[name="payment_mode"]:checked').val() || 'Cash';
                const type = $('#newTxnType').val();
                const paidTo = $('#newTxnPaidTo').val();
                const isSupplier = (type === 'Supplier Payment' || type === 'Client Direct to Supplier' || paidTo === 'Direct to Supplier' || paidTo === 'Supplier Delivery Person');
                const isOwnWh = $('input[name="material_source"]:checked').val() === 'own_warehouse';

                if (mode === 'Cheque') {
                    $('#chequeDetailsGroup').slideDown(200);
                    $('#upiDetailsGroup').slideUp(200);
                    $('#cashReceiverGroup').slideUp(200);

                    // Auto-suggest receiver if supplier selected
                    const suppContact = $('#newTxnSupplier option:selected').data('contact');
                    if (suppContact && !$('#newTxnChequeReceiver').val()) {
                        $('#newTxnChequeReceiver').val(suppContact);
                    }
                } else if (mode === 'UPI') {
                    $('#upiDetailsGroup').slideDown(200);
                    $('#chequeDetailsGroup').slideUp(200);
                    $('#cashReceiverGroup').slideUp(200);

                    // Auto-suggest account holder and UPI ID if supplier selected
                    const suppName = $('#newTxnSupplier option:selected').data('name');
                    const suppContact = $('#newTxnSupplier option:selected').data('contact');
                    const suppPhone = $('#newTxnSupplier option:selected').data('phone');
                    if ((suppContact || suppName) && !$('#newTxnAccountHolder').val()) {
                        $('#newTxnAccountHolder').val(suppContact || suppName);
                    }
                    if (suppPhone && !$('#newTxnUpiId').val()) {
                        $('#newTxnUpiId').val(String(suppPhone).replace(/\s+/g, '') + '@upi');
                    }
                } else {
                    $('#chequeDetailsGroup').slideUp(200);
                    $('#upiDetailsGroup').slideUp(200);

                    // Cash Handover person ONLY for supplier transactions
                    if (isSupplier && !isOwnWh) {
                        $('#cashReceiverGroup').slideDown(200);
                    } else {
                        $('#cashReceiverGroup').slideUp(200);
                        $('#newTxnCashReceiver').val('');
                    }
                }
            }

            // Payment mode change (Cash / Cheque / UPI)
            $('input[name="payment_mode"]').on('change', function () {
                syncPaymentModeFields();
            });

            // Flow type change: toggle supplier group or daily allocation group
            $('#newTxnType').on('change', function () {
                const type = $(this).val();

                // 1. Daily Worker Wage Payment handling
                if (type === 'Daily Wage Payment' || type === 'Daily Worker Wage Payment') {
                    $('#dailyAllocationDateGroup').slideDown(200);
                    $('#dailyAllocationWorkersGroup').slideDown(200);
                    $('#txnAmountLabel').html('Labour Wages / Cash Amount (₹) <span class="text-danger">*</span>');
                    $('#purchaseOrderSelectGroup').slideUp(200);
                    $('#newTxnPaidTo').val('Site Manager');
                    updateDailyAllocationList();
                    syncPaymentModeFields();
                    return;
                } else {
                    $('#dailyAllocationDateGroup').slideUp(200);
                    $('#dailyAllocationWorkersGroup').slideUp(200);
                    $('#newTxnWorkersDisplay').val('');
                    $('#workersTotalCountBadge').hide();
                    $('#newTxnWorkersContainer').html('<span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Worker allocation details will appear here once an allocation date is selected.</span>');
                    $('#labourWageHint').hide();
                    $('#txnAmountLabel').html('Cash Amount (₹) <span class="text-danger">*</span>');
                }

                // 2. Client Direct to Supplier handling
                if (type === 'Client Direct to Supplier') {
                    $('#newTxnPaidTo').val('Direct to Supplier');
                    $('#purchaseOrderSelectGroup').slideDown(200);
                    syncSupplierPaymentScope();
                } else if (type === 'Supplier Payment' || $('#newTxnPaidTo').val() === 'Direct to Supplier') {
                    $('#purchaseOrderSelectGroup').slideDown(200);
                    syncSupplierPaymentScope();
                } else {
                    $('#purchaseOrderSelectGroup').slideUp(200);
                    $('#siteSelectGroup').slideDown(200);
                    $('#newTxnSupplier').val('');
                }

                // 3. Balance Returned to Account Manager handling
                if (type === 'Balance Returned') {
                    $('#newTxnPaidTo').val('Account Manager');
                    $('#balanceReturnedOfficeCashGroup').slideDown(200);
                    updateOfficeCashWages();
                    updatePendingCashTransfers();
                } else {
                    $('#balanceReturnedOfficeCashGroup').slideUp(200);
                }

                syncPaymentModeFields();
            });

            // Recipient change: toggle supplier group if Direct to Supplier
            $('#newTxnPaidTo').on('change', function () {
                const recipient = $(this).val();
                if (recipient === 'Direct to Supplier') {
                    $('#purchaseOrderSelectGroup').slideDown(200);
                    syncSupplierPaymentScope();
                } else if ($('#newTxnType').val() !== 'Supplier Payment' && $('#newTxnType').val() !== 'Client Direct to Supplier') {
                    $('#purchaseOrderSelectGroup').slideUp(200);
                    $('#siteSelectGroup').slideDown(200);
                }
                syncPaymentModeFields();
            });

            // Project Site change: update PO or Daily Allocation list or Office Cash Wages & Cash Transfers
            $('#newTxnSite').on('change', function () {
                const type = $('#newTxnType').val();
                if (type === 'Supplier Payment' || $('#newTxnPaidTo').val() === 'Direct to Supplier') {
                    updatePurchaseOrderList();
                } else if (type === 'Daily Wage Payment' || type === 'Daily Worker Wage Payment') {
                    updateDailyAllocationList();
                } else if (type === 'Balance Returned') {
                    updateOfficeCashWages();
                    updatePendingCashTransfers();
                }
            });

            // Supplier change: update PO list and auto-suggest supplier details
            $('#newTxnSupplier').on('change', function () {
                const selected = $(this).find('option:selected');
                const contact = selected.data('contact') || '';
                const phone = selected.data('phone') || '';
                const name = selected.data('name') || '';

                const mode = $('input[name="payment_mode"]:checked').val() || 'Cash';
                if (mode === 'UPI') {
                    if (contact || name) {
                        $('#newTxnAccountHolder').val(contact || name);
                    }
                    if (phone) {
                        $('#newTxnUpiId').val(String(phone).replace(/\s+/g, '') + '@upi');
                    }
                } else if (mode === 'Cheque') {
                    if (contact) {
                        $('#newTxnChequeReceiver').val(contact);
                    }
                } else if (mode === 'Cash' && $('#newTxnPaidTo').val() === 'Direct to Supplier') {
                    if (contact) {
                        $('#newTxnCashReceiver').val(contact);
                    }
                }

                // Auto-switch payment mode and toggle scope selector visibility based on supplier's is_site_based configuration
                const supplierIsSiteBased = selected.data('is-site-based');
                if (supplierIsSiteBased !== undefined && supplierIsSiteBased !== null && supplierIsSiteBased !== '') {
                    const isSb = (String(supplierIsSiteBased) === '1' || supplierIsSiteBased === true);
                    if (isSb) {
                        // Site based toggle la iruntha intha option kaata thevai ila
                        $('#supplierPaymentScopeBox').hide();
                        $('#scopeSiteBased').prop('checked', true);
                    } else {
                        // Site based toggle aagama iruntha athuku mattum site based and total kaatanum
                        $('#supplierPaymentScopeBox').slideDown(150);
                        $('#scopeTotal').prop('checked', true);
                    }
                    syncSupplierPaymentScope();
                } else {
                    $('#supplierPaymentScopeBox').hide();
                    const isSiteBased = $('input[name="supplier_payment_scope"]:checked').val() === 'site_based';
                    if (!isSiteBased) {
                        calculateTotalSupplierPending($(this).val(), name);
                    } else {
                        updatePurchaseOrderList();
                    }
                }
            });

            function updateDailyAllocationList() {
                const siteId = $('#newTxnSite').val();
                const allocSelect = $('#newTxnDailyAllocation');
                const badge = $('#allocationCountBadge');
                const promptMsg = $('#allocationPromptMsg');

                if (!siteId) {
                    badge.hide();
                    allocSelect.empty().append('<option value="" selected>-- Select Project Site above --</option>');
                    $('#newTxnWorkersDisplay').val('');
                    $('#workersTotalCountBadge').hide();
                    $('#newTxnWorkersContainer').html('<span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Worker allocation details will appear here once an allocation date is selected.</span>');
                    $('#labourWageHint').hide();
                    return;
                }

                // Filter matching allocations for this site from preloaded array
                const matching = allDailyAllocations.filter(function (a) {
                    return String(a.project_site_id) === String(siteId);
                });

                // Render immediately with cached matching
                renderDailyAllocations(matching);

                // Fetch fresh from backend endpoint
                $.ajax({
                    url: '{{ route("site-cash.daily-allocations") }}',
                    type: 'GET',
                    data: { project_site_id: siteId },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        if (res.success && Array.isArray(res.allocations)) {
                            allDailyAllocations = res.allocations;
                            const freshMatching = res.allocations.filter(function (a) {
                                return String(a.project_site_id) === String(siteId);
                            });
                            renderDailyAllocations(freshMatching);
                        }
                    }
                });
            }

            function renderDailyAllocations(list) {
                const allocSelect = $('#newTxnDailyAllocation');
                const badge = $('#allocationCountBadge');
                const workersContainer = $('#newTxnWorkersContainer');
                const totalCountBadge = $('#workersTotalCountBadge');

                if (!list || list.length === 0) {
                    badge.hide();
                    allocSelect.empty().append('<option value="" selected>-- All daily wages recorded --</option>');
                    $('#newTxnWorkersDisplay').val('');
                    totalCountBadge.hide();
                    workersContainer.html('<span class="text-success small fw-semibold"><i class="bi bi-check-circle me-1"></i>All daily wages have been recorded in Site Cash.</span>');
                    $('#labourWageHint').hide();
                    return;
                }

                badge.text(`${list.length} Pending Day${list.length > 1 ? 's' : ''}`).show();
                allocSelect.empty();
                allocSelect.append('<option value="">-- Select Allocation Date --</option>');

                list.forEach(function (a) {
                    const rawWage = (a.site_payable_wage !== undefined && a.site_payable_wage !== null) ? parseFloat(a.site_payable_wage) : (parseFloat(a.total_labour_wage) || 0);
                    const amPaid = parseFloat(a.am_paid_wage) || 0;
                    const dateStr = a.date ? new Date(a.date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
                    const workers = a.total_labour_count || (a.items ? a.items.reduce((s, it) => s + (parseInt(it.worker_count) || 0), 0) : 0);

                    // Build workers summary: ex: Mason : 2, Electrician : 1
                    let workerParts = [];
                    if (a.items && a.items.length > 0) {
                        a.items.forEach(function (it) {
                            const name = it.name_ta || it.name_en || 'Worker';
                            const count = parseInt(it.worker_count) || 1;
                            const amTag = (it.paid_by === 'Account Manager') ? ' [AM Paid]' : '';
                            workerParts.push(`${name} : ${count}${amTag}`);
                        });
                    }
                    const summaryText = workerParts.length > 0 ? workerParts.join(', ') : `${workers} Workers`;

                    const isUpdated = Boolean(a.is_wage_updated);
                    const updateNotice = isUpdated ? ' ⚠️ [UPDATED]' : '';
                    const amNotice = amPaid > 0 ? ` [AM Paid: ₹${Math.round(amPaid).toLocaleString('en-IN')}]` : '';
                    const optionText = `${dateStr}${updateNotice} (${workers} Workers - Site Cash: ₹${rawWage.toLocaleString('en-IN')}${amNotice})`;

                    const opt = $('<option></option>')
                        .val(a.id)
                        .text(optionText)
                        .attr('data-id', a.id)
                        .attr('data-date', a.date ? (typeof a.date === 'string' ? a.date.split('T')[0] : a.date) : '')
                        .attr('data-date-formatted', dateStr)
                        .attr('data-wage', rawWage)
                        .attr('data-workers', workers)
                        .attr('data-is-updated', isUpdated ? '1' : '0')
                        .attr('data-workers-summary', summaryText)
                        .attr('data-items', JSON.stringify(a.items || []));

                    allocSelect.append(opt);
                });

                // Auto-select the first pending daily allocation and trigger automatic fetch
                if (list.length > 0) {
                    allocSelect.val(list[0].id).trigger('change');
                }
            }

            $('#newTxnDailyAllocation').on('change', function () {
                const selected = $(this).find('option:selected');
                const allocId = selected.val();
                const workersContainer = $('#newTxnWorkersContainer');
                const totalCountBadge = $('#workersTotalCountBadge');

                if (!allocId) {
                    $('#newTxnWorkersDisplay').val('');
                    totalCountBadge.hide();
                    workersContainer.html('<span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Worker allocation details will appear here once an allocation date is selected.</span>');
                    $('#labourWageHint').hide();
                    return;
                }

                const wage = parseFloat(selected.attr('data-wage')) || 0;
                const rawDate = selected.attr('data-date');
                const dateFormatted = selected.attr('data-date-formatted') || rawDate;
                const workers = parseInt(selected.attr('data-workers')) || 0;
                const isUpdated = selected.attr('data-is-updated') === '1';
                const summaryText = selected.attr('data-workers-summary') || `${workers} Workers`;
                const itemsJson = selected.attr('data-items') || '[]';

                // 1. AUTO-FILL AMOUNT (Labour wages)
                $('#newTxnAmount').val(wage.toFixed(2)).addClass('is-valid');
                setTimeout(() => $('#newTxnAmount').removeClass('is-valid'), 1200);

                if (isUpdated) {
                    $('#labourWageHintText').html('₹' + wage.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' <span class="badge bg-warning text-dark ms-1">Updated Allocation</span>');
                } else {
                    $('#labourWageHintText').text('₹' + wage.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }
                $('#labourWageHint').show();

                // 2. DISPLAY WORKERS SUMMARY
                $('#newTxnWorkersDisplay').val(`${summaryText} (Total: ${workers})`);
                totalCountBadge.text(`Total: ${workers} Workers`).show();

                // 3. SHOW EACH CATEGORY SEPARATELY AND FULLY IN DETAIL
                workersContainer.empty();
                try {
                    const itemsArr = JSON.parse(itemsJson);
                    if (Array.isArray(itemsArr) && itemsArr.length > 0) {
                        itemsArr.forEach(function (it) {
                            const name = it.name_en || it.name_ta || 'Worker';
                            const count = parseInt(it.worker_count) || 1;
                            const dailyWage = parseFloat(it.daily_wage) || 0;
                            const isAmPaid = (it.paid_by === 'Account Manager');

                            workersContainer.append(`
                                <span class="badge ${isAmPaid ? 'bg-info-subtle text-info border-info-subtle' : 'bg-white text-dark'} border px-3 py-2 shadow-xs d-inline-flex align-items-center gap-2" style="font-size: 0.875rem; font-weight: normal;">
                                    <i class="bi ${isAmPaid ? 'bi-person-check text-info' : 'bi-person text-primary'} fs-6"></i>
                                    <span class="fw-semibold text-dark">${name}:</span>
                                    <strong class="${isAmPaid ? 'text-info' : 'text-primary'} fs-6">${count}</strong>
                                    ${dailyWage > 0 ? `<span class="text-muted small ps-1 border-start">₹${dailyWage.toLocaleString('en-IN')}/day</span>` : ''}
                                    ${isAmPaid ? `<span class="badge bg-info text-white ms-1" style="font-size:0.7rem;">Paid by AM</span>` : ''}
</span>
                            `);
                        });

                        const formattedItems = itemsArr.map(function (it) {
                            return {
                                name: it.name_ta || it.name_en || 'Daily Labour',
                                materialName: it.name_ta || it.name_en || 'Daily Labour',
                                qty: it.worker_count || 1,
                                rate: it.daily_wage || 0,
                                total: it.subtotal || 0,
                                unit: 'Worker(s)'
                            };
                        });
                        $('#newTxnItemsJson').val(JSON.stringify(formattedItems));
                    } else {
                        workersContainer.html(`<span class="badge bg-white text-dark border px-3 py-2"><i class="bi bi-people me-1 text-primary"></i>${summaryText}</span>`);
                    }
                } catch (e) {
                    workersContainer.html(`<span class="badge bg-white text-dark border px-3 py-2"><i class="bi bi-people me-1 text-primary"></i>${summaryText}</span>`);
                }

                // 4. AUTO-FILL BILL / VOUCHER NO
                if (rawDate) {
                    $('#newTxnBill').val(`WAGE-${rawDate}`);
                }

                // 5. AUTO-FILL DESCRIPTION
                $('#newTxnDesc').val(`Daily labour wage payment: ${summaryText} on ${dateFormatted}`);
            });

            function calculateTotalSupplierPending(supplierId, supplierName) {
                if (!supplierId) {
                    $('#newTxnAmount').val('');
                    return;
                }

                $.ajax({
                    url: '{{ route("site-cash.purchase-orders") }}',
                    type: 'GET',
                    data: {
                        supplier_id: supplierId,
                        supplier_name: supplierName,
                        scope: 'total',
                        is_site_based: 0
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        if (res.success) {
                            const totalAmount = parseFloat(res.total_pending_amount) || 0;
                            const count = parseInt(res.pending_count) || 0;
                            const formatted = totalAmount.toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });

                            if (totalAmount > 0) {
                                $('#newTxnAmount').val(totalAmount.toFixed(2));
                                $('#newTxnDesc').val(`Total Supplier Payment to ${supplierName || 'Supplier'}: ₹${formatted} (${count} pending POs across your assigned sites)`);
                                showToast(`Total pending ₹${formatted} across ${count} POs loaded.`, 'info');
                            } else {
                                $('#newTxnAmount').val('');
                                $('#newTxnDesc').val(supplierName ? `Total Supplier Payment to ${supplierName}` : 'Total Supplier Payment');
                                showToast(`No pending POs found for ${supplierName || 'this supplier'} on your assigned sites.`, 'info');
                            }
                        }
                    }
                });
            }

            // Sync site and PO visibility based on Supplier Payment basis (Site Based vs Total)
            function syncSupplierPaymentScope() {
                const type = $('#newTxnType').val();
                const isSupplierPayment = (type === 'Supplier Payment' || $('#newTxnPaidTo').val() === 'Direct to Supplier');

                if (isSupplierPayment) {
                    const isSiteBased = $('input[name="supplier_payment_scope"]:checked').val() === 'site_based';
                    if (isSiteBased) {
                        $('#siteSelectGroup').slideDown(150);
                        $('#poListSection').slideDown(150);
                        $('#newTxnAmount').val('');
                        $('#newTxnDesc').val('');
                        updatePurchaseOrderList();
                    } else {
                        // Total chosen: hide PO selection and site selection
                        $('#siteSelectGroup').slideUp(150);
                        $('#poListSection').slideUp(150);
                        $('#newTxnPurchaseOrder').val('');
                        $('#newTxnSite').val('');
                        const suppId = $('#newTxnSupplier').val();
                        const suppName = $('#newTxnSupplier option:selected').data('name') || '';
                        $('#newTxnDesc').val(suppName ? `Total Supplier Payment to ${suppName}` : 'Total Supplier Payment');
                        if (suppId) {
                            calculateTotalSupplierPending(suppId, suppName);
                        }
                    }
                } else {
                    $('#siteSelectGroup').slideDown(150);
                    $('#poListSection').hide();
                }
            }

            // Payment Scope radio change (Site Based vs Total)
            $('input[name="supplier_payment_scope"]').on('change', function () {
                syncSupplierPaymentScope();
            });

            function updatePurchaseOrderList() {
                const isSiteBased = $('input[name="supplier_payment_scope"]:checked').val() === 'site_based';
                if (!isSiteBased) {
                    $('#poListSection').hide();
                    return;
                }

                const siteId = $('#newTxnSite').val();
                const supplierId = $('#newTxnSupplier').val();
                const supplierName = $('#newTxnSupplier option:selected').data('name') || '';
                const poSelect = $('#newTxnPurchaseOrder');

                if (!siteId || !supplierId) {
                    poSelect.empty().append('<option value="" selected>-- Select Purchase Order --</option>');
                    return;
                }

                // Filter matching purchase orders for this site and supplier from cache
                const cachedMatching = allPurchaseOrders.filter(function (po) {
                    const matchSite = (String(po.project_site_id) === String(siteId));
                    const matchSupplier = (supplierId && String(po.supplier_id) === String(supplierId)) ||
                        (supplierName && po.supplier_name && po.supplier_name.trim().toLowerCase() === supplierName.trim().toLowerCase());
                    return matchSite && matchSupplier;
                });
                renderPurchaseOrders(cachedMatching);

                // Fetch fresh filtered list from backend
                $.ajax({
                    url: '{{ route("site-cash.purchase-orders") }}',
                    type: 'GET',
                    data: {
                        project_site_id: siteId,
                        supplier_id: supplierId,
                        supplier_name: supplierName,
                        scope: 'site_based',
                        is_site_based: 1
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (res) {
                        if (res.success && Array.isArray(res.orders)) {
                            allPurchaseOrders = res.orders;
                            renderPurchaseOrders(res.orders);
                        }
                    }
                });
            }

            function renderPurchaseOrders(matchingPOs) {
                const poSelect = $('#newTxnPurchaseOrder');

                if (!matchingPOs || matchingPOs.length === 0) {
                    poSelect.empty().append('<option value="" selected>-- No Pending POs --</option>');
                    return;
                }

                poSelect.empty().append('<option value="" selected>-- Select Purchase Order --</option>');

                matchingPOs.forEach(function (po) {
                    const rawAmount = parseFloat(po.total_amount) || 0;
                    const formattedAmount = rawAmount.toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    let itemsSummary = '';
                    if (po.items && po.items.length > 0) {
                        itemsSummary = po.items.map(function (it) {
                            return `${it.material_name} (${(parseFloat(it.quantity) || 0)} ${it.unit || ''})`;
                        }).join(', ');
                    } else {
                        itemsSummary = 'General Material Purchase';
                    }

                    const dateStr = po.order_date ? new Date(po.order_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
                    const datePart = dateStr ? ` | ${dateStr}` : '';
                    const isUpdated = Boolean(po.is_po_updated);
                    const updateNotice = isUpdated ? ' ⚠️ [UPDATED]' : '';

                    const optionText = `${po.po_number}${updateNotice} - ₹${formattedAmount} (${itemsSummary}${datePart})`;

                    const opt = $('<option></option>')
                        .val(po.po_number)
                        .text(optionText)
                        .attr('data-id', po.id)
                        .attr('data-po-no', po.po_number)
                        .attr('data-amount', rawAmount)
                        .attr('data-is-updated', isUpdated ? '1' : '0')
                        .attr('data-desc', itemsSummary);

                    poSelect.append(opt);
                });
            }

            // Change purchase order: fill amount, bill number, description
            $('#newTxnPurchaseOrder').on('change', function () {
                const opt = $(this).find('option:selected');
                const poNo = opt.data('po-no') || $(this).val();
                const amount = opt.data('amount');
                const desc = opt.data('desc');

                if (poNo && amount !== undefined) {
                    $('#newTxnAmount').val(amount);
                    $('#newTxnBill').val(poNo);
                    if (desc) {
                        $('#newTxnDesc').val(`PO ${poNo}: ${desc}`);
                    }

                    showToast(`PO ${poNo} selected. Amount ₹${amount} filled.`, 'info');
                }
            });

            // Fetch and render Office Cash wage payments for the selected site
            function updateOfficeCashWages() {
                const siteId = $('#newTxnSite').val();
                const $container = $('#balanceReturnedOfficeCashBody');

                if (!siteId) {
                    $container.html(`
                        <div class="text-muted small py-1">
                            <i class="bi bi-arrow-up-circle text-primary me-1"></i>
                            Select a Project Site above to display pending Office Cash wage details.
</div>
                    `);
                    return;
                }

                $container.html(`
                    <div class="text-center py-2 text-muted small">
                        <span class="spinner-border spinner-border-sm me-1 text-primary"></span>
                        Checking office cash wage payments for this site...
</div>
                `);

                $.ajax({
                    url: '{{ route("site-cash.office-cash-wages") }}',
                    type: 'GET',
                    data: { project_site_id: siteId },
                    dataType: 'json',
                    success: function (res) {
                        if (!res.success) {
                            $container.html('<div class="text-danger small">Failed to load office cash wages.</div>');
                            return;
                        }

                        const payments = res.payments || [];
                        const returnedPayments = res.returned_payments || [];
                        const netPending = Number(res.net_pending_return || 0);
                        const totalOfficeWages = Number(res.total_office_cash_wages || 0);
                        const totalReturned = Number(res.total_already_returned || 0);

                        if (payments.length === 0 && returnedPayments.length === 0) {
                            $container.html(`
                                <div class="text-muted small py-1 d-flex align-items-center">
                                    <i class="bi bi-info-circle text-secondary fs-5 me-2"></i>
                                    <div>
                                        <strong class="text-dark">No pending Office Cash wages to return for this site.</strong>
                                        <div class="text-secondary" style="font-size: 0.78rem;">Painter/electrician wages paid via Project Cash do not need to be returned. You can enter any regular advance balance.</div>
</div>
</div>
                            `);
                            return;
                        }

                        let html = '';

                        // Header Summary Bar - fully responsive for 320px mobile screens
                        html += `
                            <div class="p-2 mb-2 rounded bg-light border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" style="font-size: 0.78rem;">Total Office Cash:</span>
                                    <strong class="text-dark" style="font-size: 0.85rem;">₹${totalOfficeWages.toLocaleString('en-IN')}</strong>
</div>
                                ${totalReturned > 0 ? `
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" style="font-size: 0.78rem;">Already Returned:</span>
                                    <span class="text-success fw-semibold" style="font-size: 0.82rem;">₹${totalReturned.toLocaleString('en-IN')}</span>
</div>
                                ` : ''}
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                                    <span class="text-muted" style="font-size: 0.78rem;">Pending to Return:</span>
                                    <span class="badge ${netPending > 0 ? 'bg-danger' : 'bg-success'}" style="font-size: 0.78rem;">
                                        ₹${netPending.toLocaleString('en-IN')}
</span>
</div>
                                ${payments.length > 0 ? `
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Selection Controls">
                                        <button type="button" class="btn btn-outline-primary py-0 px-2 btn-select-all-wages" style="font-size: 0.72rem;">
                                            <i class="bi bi-check-all"></i> All
</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-clear-all-wages" style="font-size: 0.72rem;">
                                            <i class="bi bi-x"></i> Clear
</button>
</div>
                                    <span class="badge bg-primary py-1 px-2" id="wageReturnSelectedBadge" style="font-size: 0.75rem;">
                                        Selected: ₹0 (0 items)
</span>
</div>
                                ` : ''}
</div>
                        `;

                        if (payments.length === 0) {
                            html += `
                                <div class="alert alert-success py-2 px-3 small d-flex align-items-center mb-2">
                                    <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                                    <div>
                                        <strong>All Office Cash wages have been returned to the account manager!</strong>
                                        <div class="text-muted" style="font-size: 0.76rem;">All office cash wage payments for this site have been fully returned.</div>
</div>
</div>
                            `;
                        } else {
                            // List of pending selectable payments
                            payments.forEach(function (p, idx) {
                                const dateStr = p.date_formatted;
                                const amt = Number(p.amount);
                                const items = p.items || [];

                                let itemsRowsHtml = '';
                                if (items.length > 0) {
                                    items.forEach(function (it, iIdx) {
                                        const icon = (it.trade.includes('பெயிண்டர்') || it.trade.toLowerCase().includes('painter'))
                                            ? '<i class="bi bi-brush text-primary me-1"></i>'
                                            : ((it.trade.includes('எலக்ட்ரீஷியன்') || it.trade.toLowerCase().includes('electrician') || it.trade.toLowerCase().includes('wireman'))
                                                ? '<i class="bi bi-lightning-charge text-warning me-1"></i>'
                                                : '<i class="bi bi-person-plus text-info me-1"></i>');

                                        const isLast = iIdx === items.length - 1;
                                        const borderClass = isLast ? '' : 'border-bottom pb-1 mb-1';
                                        const calcText = it.is_helper
                                            ? ''
                                            : `<span class="text-muted me-1" style="font-size: 0.72rem;">(${it.workers} × ₹${Number(it.wage).toLocaleString('en-IN')})</span>`;

                                        itemsRowsHtml += `
                                            <div class="d-flex justify-content-between align-items-center ${borderClass}" style="font-size: 0.78rem;">
                                                <div class="d-flex align-items-center text-truncate me-1" style="max-width: 60%;">
                                                    ${icon}
                                                    <span class="text-dark fw-medium text-truncate" title="${it.trade}">${it.trade}</span>
</div>
                                                <div class="text-end text-nowrap" style="font-size: 0.75rem;">
                                                    ${calcText}
                                                    <strong class="text-dark">₹${Number(it.subtotal).toLocaleString('en-IN')}</strong>
</div>
</div>
                                        `;
                                    });
                                } else {
                                    itemsRowsHtml = `<div class="text-muted fst-italic" style="font-size: 0.75rem;">Painter & Electrician daily wages</div>`;
                                }

                                html += `
                                    <div class="border rounded-2 p-2 mb-2 office-wage-card position-relative transition-all cursor-pointer"
                                        id="wageCard_${p.id}"
                                        data-id="${p.id}"
                                        style="border-left: 4px solid #2563eb !important; background-color: #f0f7ff;">
                                        <!-- Row 1: Checkbox, Date & Amount -->
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input wage-return-check fs-6 m-0 me-2 cursor-pointer"
                                                    type="checkbox"
                                                    id="chk_wage_${p.id}"
                                                    value="${p.id}"
                                                    data-amount="${amt}"
                                                    data-date="${dateStr}"
                                                    data-ledger-id="${p.ledger_id || ''}"
                                                    checked>
                                                <label class="form-check-label cursor-pointer mb-0 fw-semibold text-dark" for="chk_wage_${p.id}" style="font-size: 0.82rem;">
                                                    <i class="bi bi-calendar3 text-muted me-1"></i>${dateStr}
</label>
</div>
                                            <div class="text-end">
                                                <span class="fw-bold text-success" style="font-size: 0.95rem;">₹${amt.toLocaleString('en-IN')}</span>
</div>
</div>

                                        <!-- Row 2: Status & Source Badges -->
                                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                            <span class="badge bg-primary text-white wage-card-status" id="badge_status_${p.id}" style="font-size: 0.72rem;">✓ Selected to Pay</span>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">AM Paid</span>
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.68rem;">🏢 Office Cash</span>
</div>
</div>

                                        <!-- Row 3: Structured Worker Breakdown (Mobile-optimized) -->
                                        <div class="bg-white rounded-2 p-2 border mb-2">
                                            ${itemsRowsHtml}
</div>

                                        <!-- Row 4: Description note & Click instruction -->
                                        <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.72rem;">
                                            <span class="text-muted text-truncate me-2 fst-italic" style="max-width: 65%;" title="${p.description}">
                                                ${p.description || ''}
</span>
                                            <span class="text-primary text-nowrap" style="font-size: 0.7rem;"><i class="bi bi-hand-index-thumb me-1"></i>Tap to toggle</span>
</div>
</div>
                                `;
                            });
                        }

                        // Previously returned collapsible section
                        if (returnedPayments.length > 0) {
                            html += `
                                <div class="mt-2 pt-2 border-top">
                                    <button class="btn btn-sm btn-link text-decoration-none text-muted p-0 d-flex align-items-center"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#returnedWagesHistory">
                                        <i class="bi bi-clock-history text-success me-1"></i>
                                        <span style="font-size: 0.78rem;">View Previously Returned (${returnedPayments.length} records - ₹${totalReturned.toLocaleString('en-IN')})</span>
                                        <i class="bi bi-chevron-down ms-1" style="font-size: 0.75rem;"></i>
</button>
                                    <div class="collapse mt-2" id="returnedWagesHistory">
                            `;

                            returnedPayments.forEach(function (rp) {
                                const dateStr = rp.date_formatted;
                                const amt = Number(rp.amount);
                                html += `
                                    <div class="border rounded p-2 mb-1 bg-white opacity-75">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small fw-semibold text-dark"><i class="bi bi-calendar3 text-muted me-1"></i>${dateStr}</span>
                                            <span class="fw-bold text-success small">₹${amt.toLocaleString('en-IN')}</span>
</div>
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">✓ Returned to AM</span>
                                            <span class="text-muted fst-italic text-truncate" style="max-width: 180px; font-size: 0.72rem;">${rp.description || ''}</span>
</div>
</div>
                                `;
                            });

                            html += `
</div>
</div>
                            `;
                        }

                        $container.html(html);

                        // Recalculate selected sum immediately
                        recalculateSelectedWages();
                    },
                    error: function () {
                        $container.html('<div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Error connecting to office cash wages service.</div>');
                    }
                });
            }

            // Recalculate total for selected wage cards
            function recalculateSelectedWages() {
                let total = 0;
                let count = 0;
                const dates = [];
                const ids = [];

                $('.wage-return-check').each(function () {
                    const $chk = $(this);
                    const id = $chk.val();
                    const amt = Number($chk.data('amount') || 0);
                    const dateStr = $chk.data('date') || '';
                    const $card = $(`#wageCard_${id}`);
                    const $badge = $(`#badge_status_${id}`);

                    if ($chk.is(':checked')) {
                        total += amt;
                        count++;
                        ids.push(Number(id));
                        if (!dates.includes(dateStr)) {
                            dates.push(dateStr);
                        }
                        $card.css({
                            'border-left': '4px solid #2563eb',
                            'background-color': '#f0f7ff',
                            'opacity': '1'
                        });
                        $badge.removeClass('bg-secondary').addClass('bg-primary').text('✓ Selected to Pay');
                    } else {
                        $card.css({
                            'border-left': '4px solid #cbd5e1',
                            'background-color': '#ffffff',
                            'opacity': '0.7'
                        });
                        $badge.removeClass('bg-primary').addClass('bg-secondary').text('○ Pay Later');
                    }
                });

                // Update badge indicator
                $('#wageReturnSelectedBadge').text(`Selected: ₹${total.toLocaleString('en-IN')} (${count} items)`);

                // Auto-fill amount, description, and hidden inputs
                if (count > 0) {
                    $('#newTxnAmount').val(total);
                    const datesText = dates.join(', ');
                    $('#newTxnDesc').val(`Balance returned to Account Manager for Painter & Electrician wages on ${datesText} (Office Cash)`);
                    $('#newTxnWageCashSource').val('office_cash');
                    $('#newTxnReturnedWageIds').val(JSON.stringify(ids));
                } else {
                    $('#newTxnAmount').val('');
                    $('#newTxnDesc').val('');
                    $('#newTxnWageCashSource').val('');
                    $('#newTxnReturnedWageIds').val('[]');
                }
            }

            // Checkbox change listener
            $(document).on('change', '.wage-return-check', function () {
                recalculateSelectedWages();
            });

            // Card click listener (toggles checkbox if not clicking directly on checkbox)
            $(document).on('click', '.office-wage-card', function (e) {
                if ($(e.target).is('input, label, button, a')) {
                    return;
                }
                const $chk = $(this).find('.wage-return-check');
                $chk.prop('checked', !$chk.is(':checked')).trigger('change');
            });

            // Select All listener
            $(document).on('click', '.btn-select-all-wages', function () {
                $('.wage-return-check').prop('checked', true);
                recalculateSelectedWages();
                showToast('All pending wages selected.', 'info');
            });

            // Clear Selection listener
            $(document).on('click', '.btn-clear-all-wages', function () {
                $('.wage-return-check').prop('checked', false);
                recalculateSelectedWages();
                showToast('All selections cleared.', 'info');
            });

            // Tab switching between Office Cash Wages & Cash Transfers
            $('#returnTabWagesBtn').on('click', function () {
                $(this).addClass('active btn-primary').removeClass('btn-outline-primary');
                $('#returnTabTransfersBtn').removeClass('active btn-primary').addClass('btn-outline-primary');
                $('#balanceReturnedOfficeCashBody').removeClass('d-none');
                $('#balanceReturnedCashTransferBody').addClass('d-none');
                $('#newTxnCashTransferId').val('');
                recalculateSelectedWages();
            });

            $('#returnTabTransfersBtn').on('click', function () {
                $(this).addClass('active btn-primary').removeClass('btn-outline-primary');
                $('#returnTabWagesBtn').removeClass('active btn-primary').addClass('btn-outline-primary');
                $('#balanceReturnedCashTransferBody').removeClass('d-none');
                $('#balanceReturnedOfficeCashBody').addClass('d-none');
                $('.wage-return-check').prop('checked', false);
                recalculateSelectedWages();
                updatePendingCashTransfers();
            });

            // Fetch and render pending Cash Transfers for the selected site
            function updatePendingCashTransfers() {
                const siteId = $('#newTxnSite').val();
                const $container = $('#balanceReturnedCashTransferBody');

                if (!siteId) {
                    $container.html(`
                        <div class="text-muted small py-1">
                            <i class="bi bi-arrow-up-circle text-primary me-1"></i>
                            Select a Project Site above to view Cash Transfer details.
</div>
                    `);
                    return;
                }

                $container.html(`
                    <div class="text-center py-2 text-muted small">
                        <span class="spinner-border spinner-border-sm me-1 text-primary"></span>
                        Checking pending cash transfers for this site...
</div>
                `);

                $.ajax({
                    url: '{{ route("site-cash.cash-transfers") }}',
                    type: 'GET',
                    data: { project_site_id: siteId },
                    dataType: 'json',
                    success: function (res) {
                        if (!res.success) {
                            $container.html('<div class="text-danger small">Failed to load cash transfers.</div>');
                            return;
                        }

                        const transfers = res.transfers || [];
                        const totalPending = Number(res.total_pending || 0);

                        if (transfers.length === 0) {
                            $container.html(`
                                <div class="text-muted small py-1 d-flex align-items-center">
                                    <i class="bi bi-info-circle text-secondary fs-5 me-2"></i>
                                    <div>
                                        <strong class="text-dark">No pending Cash Transfer balance to return for this site.</strong>
                                        <div class="text-secondary" style="font-size: 0.78rem;">All cash transfers have already been settled.</div>
</div>
</div>
                            `);
                            return;
                        }

                        let html = '';
                        html += `
                            <div class="p-2 mb-2 rounded bg-light border d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <span class="text-muted small">Total Pending Transfers: <strong class="text-danger">₹${totalPending.toLocaleString('en-IN')}</strong> (${transfers.length} transfers)</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Return Pending</span>
</div>
                            <div class="d-flex flex-column gap-2" id="pendingCashTransfersList">
                        `;

                        transfers.forEach(function (ct) {
                            const isOfficeToSite = ct.direction === 'office_to_site';
                            const dirBadgeClass = isOfficeToSite ? 'bg-primary-subtle text-primary border-primary-subtle' : 'bg-warning-subtle text-dark border-warning-subtle';
                            const dirIcon = isOfficeToSite ? 'bi-arrow-right-circle' : 'bi-arrow-left-circle';

                            html += `
                                <div class="card border rounded-3 p-2 bg-white shadow-sm cash-transfer-return-card" id="ctCard_${ct.id}" data-id="${ct.id}" data-amount="${ct.amount}" data-ref="${ct.ref}" data-purpose="${escapeHtml(ct.purpose)}" style="cursor: pointer; transition: all 0.2s ease;">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-1">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="badge ${dirBadgeClass} border px-2 py-0" style="font-size: 0.73rem;">
                                                <i class="bi ${dirIcon} me-1"></i>${ct.direction_label}
</span>
                                            <span class="font-monospace fw-bold text-dark" style="font-size: 0.75rem;">${ct.ref}</span>
                                            <span class="text-muted" style="font-size: 0.72rem;">&bull; ${ct.date}</span>
</div>
                                        <span class="fw-bold text-danger fs-6">${ct.amount_formatted}</span>
</div>
                                    <div class="p-2 rounded bg-light border-start border-3 border-primary small mb-1">
                                        <div class="text-muted" style="font-size: 0.72rem;">Purpose:</div>
                                        <strong class="text-dark" style="font-size: 0.82rem;">
                                            <i class="bi bi-chat-left-quote text-primary me-1"></i>${escapeHtml(ct.purpose)}
</strong>
</div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 text-muted" style="font-size: 0.72rem;">
                                        <div>
                                            <span>Handed over by: <strong class="text-dark">${escapeHtml(ct.handed_over_by || '-')}</strong></span>
                                            <span class="mx-1">&bull;</span>
                                            <span>Received by: <strong class="text-dark">${escapeHtml(ct.received_by || '-')}</strong></span>
</div>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 select-ct-return-btn" data-id="${ct.id}" style="font-size: 0.75rem;">
                                            <i class="bi bi-check-circle me-1"></i>Select to Return
</button>
</div>
</div>
                            `;
                        });

                        html += `</div>`;
                        $container.html(html);
                    },
                    error: function () {
                        $container.html('<div class="text-danger small">Network error while fetching cash transfers.</div>');
                    }
                });
            }

            // Click listener for selecting a cash transfer to return
            $(document).on('click', '.cash-transfer-return-card, .select-ct-return-btn', function (e) {
                const $card = $(this).closest('.cash-transfer-return-card');
                const id = $card.data('id');
                const amount = $card.data('amount');
                const ref = $card.data('ref');
                const purpose = $card.data('purpose');

                // Highlight card
                $('.cash-transfer-return-card').removeClass('border-primary bg-primary-subtle').addClass('bg-white');
                $('.select-ct-return-btn').removeClass('btn-primary').addClass('btn-outline-primary').html('<i class="bi bi-check-circle me-1"></i>Select to Return');

                $card.addClass('border-primary bg-primary-subtle').removeClass('bg-white');
                $card.find('.select-ct-return-btn').removeClass('btn-outline-primary').addClass('btn-primary').html('✓ Selected to Return');

                // Fill form fields
                $('#newTxnAmount').val(amount);
                $('#newTxnBill').val(ref);
                $('#newTxnDesc').val(`Balance returned to Account Manager for Cash Transfer #${ref} (${purpose}) [Office Cash]`);
                $('#newTxnCashTransferId').val(id);
                $('#newTxnWageCashSource').val('cash_transfer');
                $('#newTxnReturnedWageIds').val('[]');

                showToast(`Cash Transfer #${ref} selected: ₹${Number(amount).toLocaleString('en-IN')} for "${purpose}"`, 'info');
            });

            // Source switch: Third party vs Warehouse
            $('input[name="material_source"]').on('change', function () {
                if ($(this).val() === 'own_warehouse') {
                    $('#thirdPartySupplierBox').hide();
                    $('#warehouseMaterialBox').slideDown(200);
                    if ($('#siteCashWhTableBody tr').length === 0) {
                        addWarehouseItemRow();
                    }
                } else {
                    $('#warehouseMaterialBox').hide();
                    $('#thirdPartySupplierBox').slideDown(200);
                }
                syncPaymentModeFields();
            });

            function addWarehouseItemRow() {
                let optionsHtml = '<option value="" disabled selected>-- Select Material from Warehouse Store --</option>';
                warehouseInventory.forEach(function (item) {
                    const price = item.cash_amount !== undefined && item.cash_amount !== null ? item.cash_amount : (item.unit_rate || 0);
                    const unitName = (item.unit && typeof item.unit === 'object')
                        ? (item.unit.name_en || item.unit.code || '')
                        : (item.unit || item.unit_name || '');
                    const unitDisplay = unitName ? ` (${unitName})` : '';
                    const tamil = item.name_ta || '';
                    optionsHtml += `<option value="${item.id}" data-rate="${price}" data-stock="${item.current_stock}" data-unit="${unitName}" data-name="${item.name_en}" data-tamil="${tamil}">${item.name_en}${unitDisplay}</option>`;
                });

                const row = `
                                        <tr class="wh-item-row">
                                            <td style="min-width: 250px;">
                                                <select class="form-select form-select-sm wh-select-item">
                                                    ${optionsHtml}
                                                </select>
                                            </td>
                                            <td class="text-center align-middle"><span class="badge bg-light text-dark border wh-stock-badge">0</span></td>
                                            <td class="text-end align-middle"><span class="small fw-semibold wh-rate-display">₹0</span></td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm text-center wh-qty-input" min="1" step="any" value="1">
                                            </td>
                                            <td class="text-end align-middle fw-bold text-danger wh-line-total">₹0</td>
                                            <td class="text-center align-middle">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 remove-wh-row-btn" title="Remove">&times;</button>
                                            </td>
                                        </tr>
                                    `;
                const $row = $(row);
                $('#siteCashWhTableBody').append($row);
                if (typeof window.makeSearchableSelect === 'function') {
                    window.makeSearchableSelect($row.find('.wh-select-item'), {
                        placeholder: '-- Select Material (Warehouse Store) --',
                        type: 'warehouse'
                    });
                }
                recalcWarehouseItems();
            }

            $('#addSiteCashWhRowBtn').on('click', function () {
                addWarehouseItemRow();
            });

            $(document).on('click', '.remove-wh-row-btn', function () {
                $(this).closest('tr').remove();
                recalcWarehouseItems();
            });

            $(document).on('change', '.wh-select-item', function () {
                const tr = $(this).closest('tr');
                const opt = $(this).find('option:selected');
                const rate = parseFloat(opt.data('rate')) || 0;
                const stock = parseFloat(opt.data('stock')) || 0;
                const unit = opt.data('unit') || 'Nos';

                tr.find('.wh-stock-badge').text(`${stock} ${unit}`);
                tr.find('.wh-rate-display').text(`₹${rate.toLocaleString('en-IN')}`);
                tr.find('.wh-qty-input').attr('max', stock);

                recalcWarehouseItems();
            });

            $(document).on('input', '.wh-qty-input', function () {
                recalcWarehouseItems();
            });

            function recalcWarehouseItems() {
                let grandTotal = 0;
                const items = [];

                $('#siteCashWhTableBody tr').each(function () {
                    const opt = $(this).find('.wh-select-item option:selected');
                    const itemId = opt.val();
                    if (!itemId) return;

                    const name = opt.data('name');
                    const unit = opt.data('unit');
                    const rate = parseFloat(opt.data('rate')) || 0;
                    const qty = parseFloat($(this).find('.wh-qty-input').val()) || 0;
                    const lineTotal = qty * rate;

                    $(this).find('.wh-line-total').text(`₹${lineTotal.toLocaleString('en-IN')}`);
                    grandTotal += lineTotal;

                    items.push({
                        warehouse_item_id: itemId,
                        name: name,
                        unit: unit,
                        qty: qty,
                        rate: rate,
                        total: lineTotal
                    });
                });

                if ($('input[name="material_source"]:checked').val() === 'own_warehouse') {
                    $('#newTxnAmount').val(grandTotal > 0 ? grandTotal.toFixed(2) : '');
                    $('#newTxnItemsJson').val(JSON.stringify(items));
                }
            }

            // Image preview
            $('#newTxnImage').on('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        showToast('Image file size must be less than 5MB.', 'warning');
                        $(this).val('');
                        $('#newTxnImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function (evt) {
                        $('#newTxnImagePreviewImg').attr('src', evt.target.result);
                        $('#newTxnImageName').text(file.name);
                        $('#newTxnImageSize').text((file.size / 1024).toFixed(1) + ' KB');
                        $('#newTxnImagePreviewBox').removeAttr('style').addClass('d-flex');
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#newTxnImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                }
            });

            $('#newTxnImageClearBtn').on('click', function () {
                $('#newTxnImage').val('');
                $('#newTxnImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
            });

            function clearCashFormErrors() {
                $('#cashTransactionForm .is-invalid').removeClass('is-invalid');
                $('#cashTransactionForm .invalid-feedback').text('').hide();
            }

            function displayCashFormErrors(errors) {
                clearCashFormErrors();
                if (!errors) return;

                const mode = $('input[name="payment_mode"]:checked').val() || 'Cash';
                const fieldMap = {
                    'project_site_id': { input: '#newTxnSite', err: '#errNewTxnSite' },
                    'transaction_type': { input: '#newTxnType', err: '#errNewTxnType' },
                    'paid_to': { input: '#newTxnPaidTo', err: '#errNewTxnPaidTo' },
                    'payment_mode': { input: 'input[name="payment_mode"]', err: '#errNewTxnPaymentMode' },
                    'payment_reference': {
                        input: mode === 'Cheque' ? '#newTxnChequeNo' : '#newTxnUpiRef',
                        err: mode === 'Cheque' ? '#errNewTxnChequeNo' : '#errNewTxnUpiRef'
                    },
                    'receiver_name': {
                        input: mode === 'Cheque' ? '#newTxnChequeReceiver' : '#newTxnCashReceiver',
                        err: '#errNewTxnPaidTo'
                    },
                    'bank_name': { input: '#newTxnBankName', err: '#newTxnBankName' },
                    'upi_id': { input: '#newTxnUpiId', err: '#newTxnUpiId' },
                    'account_holder_name': { input: '#newTxnAccountHolder', err: '#newTxnAccountHolder' },
                    'supplier_id': { input: '#newTxnSupplier', err: '#errNewTxnSupplier' },
                    'purchase_order_no': { input: '#newTxnPurchaseOrder', err: '#errNewTxnPurchaseOrder' },
                    'amount': { input: '#newTxnAmount', err: '#errNewTxnAmount' },
                    'bill_no': { input: '#newTxnBill', err: '#errNewTxnBill' },
                    'receipt_image': { input: '#newTxnImage', err: '#errNewTxnImage' },
                    'description': { input: '#newTxnDesc', err: '#errNewTxnDesc' },
                };

                let firstInvalidInput = null;

                Object.keys(errors).forEach(function (field) {
                    const errorText = Array.isArray(errors[field]) ? errors[field][0] : errors[field];
                    if (fieldMap[field]) {
                        const $input = $(fieldMap[field].input);
                        const $err = $(fieldMap[field].err);
                        $input.addClass('is-invalid');
                        $err.text(errorText).show();
                        if (!firstInvalidInput) {
                            firstInvalidInput = $input;
                        }
                    }
                });

                if (firstInvalidInput) {
                    firstInvalidInput.focus();
                }
            }

            $('#cashTransactionForm input, #cashTransactionForm select, #cashTransactionForm textarea').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
                $(this).closest('.input-group').find('.invalid-feedback').text('').hide();
            });

            // Reset modal on close
            $('#cashTransactionModal').on('hidden.bs.modal', function () {
                clearCashFormErrors();
                $('#cashTransactionForm')[0].reset();
                $('#dailyAllocationDateGroup').hide();
                $('#dailyAllocationWorkersGroup').hide();
                $('#balanceReturnedOfficeCashGroup').hide();
                $('#newTxnWageCashSource').val('');
                $('#newTxnLedgerId').val('');
                $('#newTxnDailyAllocation').empty().append('<option value="" selected>-- Select Project Site above --</option>');
                $('#newTxnWorkersDisplay').val('');
                $('#workersTotalCountBadge').hide();
                $('#newTxnWorkersContainer').html('<span class="text-muted small fst-italic"><i class="bi bi-info-circle me-1"></i>Worker allocation details will appear here once an allocation date is selected.</span>');
                $('#labourWageHint').hide();
                $('#txnAmountLabel').html('Cash Amount (₹) <span class="text-danger">*</span>');
                $('#allocationCountBadge').hide();
                $('#payModeCash').prop('checked', true);
                $('#chequeDetailsGroup').hide();
                $('#upiDetailsGroup').hide();
                $('#cashReceiverGroup').hide();
                $('#newTxnChequeNo').val('').removeClass('is-invalid');
                $('#newTxnBankName').val('');
                $('#newTxnChequeReceiver').val('');
                $('#newTxnUpiRef').val('').removeClass('is-invalid');
                $('#newTxnUpiId').val('');
                $('#newTxnAccountHolder').val('');
                $('#newTxnCashReceiver').val('');
                $('#newTxnPaymentReference').val('').removeClass('is-invalid');
                $('#newTxnReceiverName').val('');
                $('#siteSelectGroup').show();
                $('#newTxnPaidTo').val('Site Manager');
                $('#purchaseOrderSelectGroup').hide();
                $('#sourceThirdParty').prop('checked', true);
                $('#scopeSiteBased').prop('checked', true);
                $('#supplierPaymentScopeBox').hide();
                $('#thirdPartySupplierBox').show();
                $('#warehouseMaterialBox').hide();
                $('#siteCashWhTableBody').empty();
                $('#newTxnSupplier').val('');
                $('#poListSection').show();
                $('#newTxnPurchaseOrder').empty().append('<option value="" selected>-- Select Purchase Order --</option>');
                $('#newTxnImagePreviewBox').attr('style', 'display: none !important;').removeClass('d-flex');
                $('#saveSpinner').addClass('d-none');
                $('#saveCashTxnBtn').prop('disabled', false);
            });

            // Submit Cash Transaction via AJAX
            $('#cashTransactionForm').on('submit', function (e) {
                e.preventDefault();
                clearCashFormErrors();

                const form = this;
                const formData = new FormData(form);
                const type = $('#newTxnType').val() || '';
                const paidTo = $('#newTxnPaidTo').val() || '';

                // Synchronize payment mode details
                const mode = $('input[name="payment_mode"]:checked').val() || 'Cash';
                if (mode === 'Cheque') {
                    const chq = $('#newTxnChequeNo').val().trim();
                    const receiver = $('#newTxnChequeReceiver').val().trim();
                    const bank = $('#newTxnBankName').val().trim();

                    if (!chq) {
                        $('#newTxnChequeNo').addClass('is-invalid');
                        $('#errNewTxnChequeNo').text('Please enter the Cheque Number .').show();
                        showToast('Please enter the Cheque Number.', 'warning');
                        return;
                    }
                    formData.set('payment_reference', chq);
                    formData.set('receiver_name', receiver);
                    formData.set('bank_name', bank);
                    formData.delete('upi_id');
                    formData.delete('account_holder_name');
                } else if (mode === 'UPI') {
                    const upiRef = $('#newTxnUpiRef').val().trim();
                    const upiId = $('#newTxnUpiId').val().trim();
                    const acHolder = $('#newTxnAccountHolder').val().trim();

                    if (!upiRef) {
                        $('#newTxnUpiRef').addClass('is-invalid');
                        $('#errNewTxnUpiRef').text('Please enter the UPI Reference ID / UTR Number.').show();
                        showToast('Please enter the UPI Reference / UTR Number.', 'warning');
                        return;
                    }
                    formData.set('payment_reference', upiRef);
                    formData.set('upi_id', upiId);
                    formData.set('account_holder_name', acHolder);
                    formData.set('receiver_name', acHolder || paidTo);
                    formData.delete('bank_name');
                } else {
                    formData.set('payment_reference', '');
                    const isSupplier = (type === 'Supplier Payment' || type === 'Client Direct to Supplier' || paidTo === 'Direct to Supplier' || paidTo === 'Supplier Delivery Person');
                    const isOwnWhCheck = $('input[name="material_source"]:checked').val() === 'own_warehouse';
                    formData.set('receiver_name', (isSupplier && !isOwnWhCheck) ? $('#newTxnCashReceiver').val().trim() : '');
                    formData.delete('bank_name');
                    formData.delete('upi_id');
                    formData.delete('account_holder_name');
                }

                const isOwnWh = $('input[name="material_source"]:checked').val() === 'own_warehouse';
                if (isOwnWh) {
                    formData.set('supplier_name', 'Central Warehouse');
                    recalcWarehouseItems();
                    formData.set('items', $('#newTxnItemsJson').val());
                    if (!$('#newTxnBill').val()) {
                        formData.set('bill_no', 'WH-ISSUE-' + Date.now().toString().slice(-6));
                    }
                } else {
                    // Add supplier name if supplier selected
                    const supplierOpt = $('#newTxnSupplier').find('option:selected');
                    if (supplierOpt.val()) {
                        formData.append('supplier_name', supplierOpt.data('name') || supplierOpt.text());
                    }
                }

                $('#saveSpinner').removeClass('d-none');
                $('#saveCashTxnBtn').prop('disabled', true);

                $.ajax({
                    url: '{{ route("site-cash.store") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        $('#cashTransactionModal').modal('hide');
                        showToast(res.message || 'Transaction recorded successfully!', 'success');

                        // Update KPI stats if returned
                        if (res.stats) {
                            updateKpiDisplays(res.stats);
                        }

                        // Reload page smoothly to refresh table & full server state
                        setTimeout(function () {
                            window.location.reload();
                        }, 600);
                    },
                    error: function (xhr) {
                        $('#saveSpinner').addClass('d-none');
                        $('#saveCashTxnBtn').prop('disabled', false);

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            displayCashFormErrors(errors);
                            const firstErrKey = Object.keys(errors)[0];
                            showToast(errors[firstErrKey][0], 'danger');
                        } else {
                            const errMsg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : 'Failed to record transaction. Please check your inputs.';
                            showToast(errMsg, 'danger');
                        }
                    }
                });
            });

            function updateKpiDisplays(stats) {
                const adv = parseFloat(stats.advance || 0);
                const col = parseFloat(stats.collections || 0);
                const pur = parseFloat(stats.purchases || 0);
                const wage = parseFloat(stats.wages || 0);
                const ret = parseFloat(stats.returned || 0);
                const cashInHand = parseFloat(stats.cash_in_hand || 0);

                $('#kpiAdvance').text('₹' + adv.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiCollections').text('₹' + col.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiPurchases').text('₹' + pur.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiWages').text('₹' + wage.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiReturned').text('₹' + ret.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#kpiBalanceInHand').text('₹' + cashInHand.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
                $('#liveCashInHandTotal').text('₹' + cashInHand.toLocaleString('en-IN', { minimumFractionDigits: 2 }));

                $('#cashFormulaDisplay').html(`
                                        ₹${adv.toLocaleString('en-IN')} (Adv) + ₹${col.toLocaleString('en-IN')} (Col) &minus; ₹${pur.toLocaleString('en-IN')} (Pur) &minus; ₹${wage.toLocaleString('en-IN')} (Wage) &minus; ₹${ret.toLocaleString('en-IN')} (Ret) = <strong class="text-success">₹${cashInHand.toLocaleString('en-IN')}</strong>
                                    `);
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function getMatchingRows() {
                const query = $('#cashTxnSearch').val().toLowerCase().trim();
                const type = $('#txnTypeFilter').val();
                const siteId = $('#siteFilter').val();
                const matching = [];

                $('#cashTxnTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyCashRow' || rowId === 'noResultsRow') return;

                    const desc = ($(this).data('desc') || '').toString().toLowerCase();
                    const bill = ($(this).data('bill') || '').toString().toLowerCase();
                    const supplier = ($(this).data('supplier') || '').toString().toLowerCase();
                    const site = ($(this).data('site') || '').toString().toLowerCase();
                    const rowType = ($(this).data('type') || '').toString();
                    const rowSiteId = ($(this).data('site-id') || '').toString();

                    const matchQuery = !query || desc.includes(query) || bill.includes(query) || supplier.includes(query) || site.includes(query);
                    const matchType = !type || rowType === type;
                    const matchSite = !siteId || rowSiteId === siteId;

                    if (matchQuery && matchType && matchSite) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#cashTxnTableBody tr').not('#emptyCashRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#cashTxnTableBody tr').not('#emptyCashRow, #noResultsRow').hide();

                if (totalCount === 0) {
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#txnCounter').html('Showing <strong class="text-dark">0</strong> records');
                    return;
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#txnCounter').html(`Showing <strong class="text-dark">0</strong> of <strong class="text-dark">${totalCount}</strong> records`);
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

                // Update summary footer text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' transactions';
                $('#paginationSummary').html(summaryHtml);

                $('#txnCounter').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> records`);

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

            $('#cashTxnSearch, #txnTypeFilter, #siteFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#cashTxnSearch').val('');
                $('#txnTypeFilter').val('');
                $('#siteFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Initial pagination run
            updatePagination();

            // Export to CSV
            $('#exportCashBookBtn').on('click', function () {
                const rows = [];
                const matching = getMatchingRows();
                matching.forEach(function (tr) {
                    const date = $(tr).find('td').eq(0).text().trim().replace(/\s+/g, ' ');
                    const type = $(tr).data('type') || '';
                    const paidTo = $(tr).data('paid-to') || 'Site Manager';
                    const receiver = $(tr).data('receiver') || '';
                    const bank = $(tr).data('bank') || '';
                    const upiId = $(tr).data('upi-id') || '';
                    const acHolder = $(tr).data('account-holder') || '';
                    const site = $(tr).data('site') || '';
                    const supp = $(tr).data('supplier') || '';
                    const desc = $(tr).data('desc') || '';
                    const mode = $(tr).data('payment-mode') || 'Cash';
                    const ref = $(tr).data('payment-ref') || '';
                    const bill = $(tr).data('bill') || '';
                    const inVal = $(tr).data('in') || 0;
                    const outVal = $(tr).data('out') || 0;

                    rows.push({ date, type, paidTo, receiver, site, supp, desc, mode, ref, bank, upiId, acHolder, bill, inVal, outVal });
                });

                if (rows.length === 0) {
                    showToast('No cash transactions available to export.', 'warning');
                    return;
                }

                let csv = 'Date,Type,Paid To,Handover / Receiver,Site,Supplier,Description,Payment Mode,Reference / Cheque / UTR,Bank Name,UPI ID,Account Holder,Bill No,Inflow,Outflow\n';
                rows.forEach(function (t) {
                    csv += `"${t.date}","${t.type}","${t.paidTo}","${t.receiver}","${t.site}","${t.supp.replace(/"/g, '""')}","${t.desc.replace(/"/g, '""')}","${t.mode}","${t.ref}","${t.bank}","${t.upiId}","${t.acHolder}","${t.bill}",${t.inVal},${t.outVal}\n`;
                });

                const blob = new Blob([csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'site_cash_book.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                showToast('Exported Cash Book to CSV file.', 'success');
            });
        });
    </script>
@endpush