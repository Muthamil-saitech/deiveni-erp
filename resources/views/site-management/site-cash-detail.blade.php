@extends('layouts.app')

@section('title', 'Site Cash Transaction Details')
@section('page_title', 'Site Cash Transaction Details ')

@php
    $isInflow = in_array($transaction->transaction_type, ['Owner Advance', 'Advance Received', 'Client Advance', 'Client Collection'], true);

    $badgeClass = match ($transaction->transaction_type) {
        'Client Collection', 'Client Advance' => 'bg-success-subtle text-success border border-success-subtle',
        'Material Purchase', 'Supplier Payment', 'Tool Rental', 'Daily Wage Payment', 'Other Expense' => 'bg-danger-subtle text-danger border border-danger-subtle',
        'Balance Returned' => 'bg-warning-subtle text-dark border border-warning-subtle',
        default => 'bg-primary-subtle text-primary border border-primary-subtle',
    };

    $formattedDateTime = \Carbon\Carbon::parse($transaction->payment_date)->format('d M Y') . ($transaction->payment_time ? ' ' . $transaction->payment_time : '');
    $siteName = $transaction->projectSite?->name ?? 'N/A';
    $supplierName = $transaction->supplier_name ?: ($transaction->supplier?->name ?? '');
    $items = is_array($transaction->items) ? $transaction->items : (json_decode($transaction->items ?? '[]', true) ?: []);
@endphp

@section('content')
    <div class="container-fluid px-0" id="siteCashDetailApp">

        <!-- Top Navigation / Action Bar -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('site-cash.index') }}"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        title="Back to Site Cash Book">
                        <i class="bi bi-arrow-left me-1"></i> Back to Site Cash
</a>
                    <div class="vr d-none d-md-block"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="fw-bold mb-0 text-dark">{{ $transaction->transaction_type }} ({{ $siteName }})</h5>
                            <span class="badge {{ $badgeClass }}">{{ $transaction->transaction_type }}</span>
</div>
                        <small class="text-muted">Transaction ID: <span
                                class="font-monospace text-primary fw-semibold">#{{ $transaction->id }}</span> &bull;
                            <span>{{ $formattedDateTime }}</span></small>
</div>
</div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if (! empty($weeklyWage))
                        <a href="{{ route('site-cash.weekly-settlement.show', $weeklyWage->encrypted_id) }}"
                            target="_blank"
                            class="btn btn-primary btn-sm d-inline-flex align-items-center shadow-sm">
                            <i class="bi bi-eye-fill me-1"></i> View Weekly Details
                        </a>
                    @endif
                    <button type="button" class="btn btn-warning btn-sm d-inline-flex align-items-center shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#editSiteCashModal">
                        <i class="bi bi-pencil-square me-1"></i> Edit Entry 
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
</button>
</div>
</div>
</div>

        <div id="detailContentBox">
            <div class="row g-3 mb-4">
                <!-- 1. Transaction Flow Type -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Flow Type</span>
                                <h6 class="fw-bold mb-0 text-dark mt-1">{{ $transaction->transaction_type }}</h6>
</div>
                            <div class="bg-primary-subtle text-primary p-2 rounded-3">
                                <i class="bi bi-arrow-left-right fs-5"></i>
</div>
</div>
</div>
</div>

                <!-- 2. Transaction Amount -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div
                        class="card-custom p-3 bg-white h-100 border-start border-4 {{ $isInflow ? 'border-success' : 'border-danger' }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Amount</span>
                                <h5 class="fw-bold mb-0 {{ $isInflow ? 'text-success' : 'text-danger' }} mt-1">
                                    ₹{{ number_format((float) $transaction->amount, 2) }}
</h5>
</div>
                            <div
                                class="{{ $isInflow ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} p-2 rounded-3">
                                <i class="bi bi-cash-stack fs-5"></i>
</div>
</div>
</div>
</div>

                <!-- 3. Project Site -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Project Site</span>
                                <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" style="max-width: 170px;">
                                    {{ $siteName }}
</h6>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none mt-1 small text-info fw-semibold"
                                    data-bs-toggle="modal" data-bs-target="#editSiteCashModal" title="Change Project Site">
                                    <i class="bi bi-pencil-square me-1"></i>Change Site 
</button>
</div>
                            <div class="bg-info-subtle text-info p-2 rounded-3">
                                <i class="bi bi-building fs-5"></i>
</div>
</div>
</div>
</div>

                <!-- 4. Bill / Voucher No -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-secondary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Bill / Voucher No</span>
                                <h6 class="fw-bold font-monospace mb-0 text-dark mt-1">{{ $transaction->bill_no ?: 'None' }}
</h6>
</div>
                            <div class="bg-light text-secondary p-2 rounded-3 border">
                                <i class="bi bi-receipt fs-5"></i>
</div>
</div>
</div>
</div>
</div>

            <div class="row g-4 mb-4">
                <!-- Left Column: Full Details -->
                <div class="col-12 col-lg-7">
                    <div class="card-custom p-4 bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-card-text text-primary me-2"></i>Transaction Specifications 
</h6>
                            <span class="badge bg-light text-secondary border font-monospace small">
                                {{ $transaction->bill_no ? 'REF: ' . $transaction->bill_no : 'ID: #' . $transaction->id }}
</span>
</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted small py-2" style="width: 35%;">Date & Time:</td>
                                        <td class="fw-semibold text-dark py-2">{{ $formattedDateTime }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2">Transaction Flow:</td>
                                        <td class="py-2"><span
                                                class="badge {{ $badgeClass }}">{{ $transaction->transaction_type }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2">Paid To / Recipient:</td>
                                        <td class="fw-semibold text-dark py-2">
                                            <span class="badge bg-light text-dark border">
                                                <i class="bi bi-person me-1 text-primary"></i>{{ $transaction->paid_to ?? 'Site Manager' }}
</span>
                                        </td>
                                    </tr>
                                    @if ($transaction->paid_by)
                                        <tr>
                                            <td class="text-muted small py-2">Paid By :</td>
                                            <td class="fw-semibold text-dark py-2">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="bi bi-person-badge me-1"></i>{{ $transaction->paid_by }}
</span>
                                                @if ($transaction->wage_cash_source)
                                                    <span class="badge {{ $transaction->wage_cash_source === 'project_cash' ? 'bg-warning-subtle text-dark border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle' }} ms-1">
                                                        {{ $transaction->wage_cash_source === 'project_cash' ? 'Project Cash ' : 'Office Cash ' }}
</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="text-muted small py-2">Payment Mode:</td>
                                        <td class="py-2">
                                            @if ($transaction->payment_mode === 'Cheque')
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                                    <i class="bi bi-card-text me-1"></i>Cheque 
</span>
                                            @elseif ($transaction->payment_mode === 'UPI')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                                    <i class="bi bi-qr-code me-1"></i>UPI 
</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-cash-stack me-1"></i>Cash 
</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($transaction->payment_reference)
                                        <tr>
                                            <td class="text-muted small py-2">
                                                {{ $transaction->payment_mode === 'Cheque' ? 'Cheque Number :' : 'UPI Reference / UTR No:' }}
                                            </td>
                                            <td class="font-monospace text-primary fw-bold py-2">
                                                {{ $transaction->payment_reference }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($transaction->bank_name)
                                        <tr>
                                            <td class="text-muted small py-2">Bank Name :</td>
                                            <td class="fw-semibold text-dark py-2">
                                                <i class="bi bi-bank me-1 text-primary"></i>{{ $transaction->bank_name }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($transaction->upi_id)
                                        <tr>
                                            <td class="text-muted small py-2">Supplier UPI ID / Mobile:</td>
                                            <td class="font-monospace text-primary fw-semibold py-2">
                                                <i class="bi bi-phone me-1 text-muted"></i>{{ $transaction->upi_id }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($transaction->account_holder_name)
                                        <tr>
                                            <td class="text-muted small py-2">UPI Account Holder Name:</td>
                                            <td class="fw-bold text-dark py-2">
                                                <i class="bi bi-person-check me-1 text-success"></i>{{ $transaction->account_holder_name }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($transaction->receiver_name)
                                        <tr>
                                            <td class="text-muted small py-2">Received / Handed Over To:</td>
                                            <td class="fw-bold text-primary py-2">
                                                <i class="bi bi-person me-1"></i>{{ $transaction->receiver_name }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="text-muted small py-2">Project Site:</td>
                                        <td class="fw-semibold text-dark py-2">
                                            @if ($transaction->project_site_id)
                                                <a href="{{ route('sites.show', $transaction->projectSite?->encrypted_id ?? \App\Services\IdCipherService::encrypt($transaction->project_site_id)) }}"
                                                    class="text-primary text-decoration-none">
                                                    {{ $siteName }}
</a>
                                            @else
                                                {{ $siteName }}
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2">Supplier:</td>
                                        <td class="fw-semibold text-primary py-2">
                                            @if ($supplierName)
                                                {{ $supplierName }}
                                                @if ($transaction->supplier?->contact_person)
                                                    <span class="text-muted small fw-normal ms-1">({{ $transaction->supplier->contact_person }}{{ $transaction->supplier->phone ? ' &bull; ' . $transaction->supplier->phone : '' }})</span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($transaction->transaction_type === 'Supplier Payment')
                                        <tr>
                                            <td class="text-muted small py-2">Payment Basis :</td>
                                            <td class="fw-semibold py-2">
                                                @if ($transaction->is_site_based)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        <i class="bi bi-building me-1"></i>Site Based 
</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-cash-stack me-1"></i>Total Supplier Payment 
</span>
                                                @endif
                                                @if ($transaction->purchase_order_no)
                                                    <span class="badge bg-light text-dark border ms-1 font-monospace">
                                                        PO: {{ $transaction->purchase_order_no }}
</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2">Bill / Voucher Ref:</td>
                                        <td class="font-monospace text-secondary fw-semibold py-2">
                                            {{ $transaction->bill_no ?: 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2">Cash Inflow (+):</td>
                                        <td class="fw-bold text-success py-2">
                                            {{ (float) $transaction->in_amount > 0 ? '+₹' . number_format((float) $transaction->in_amount, 2) : '—' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2">Cash Outflow (-):</td>
                                        <td class="fw-bold text-danger py-2">
                                            {{ (float) $transaction->out_amount > 0 ? '−₹' . number_format((float) $transaction->out_amount, 2) : '—' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted small py-2 align-top">Description / Notes:</td>
                                        <td class="text-dark py-2">
                                            {{ $transaction->description ?: 'No specific notes recorded.' }}</td>
                                    </tr>
                                </tbody>
                            </table>
</div>
                        @php
                            $linkedTransfer = $transaction->cashTransfer ?? ($transaction->cash_transfer_id ? \App\Models\CashTransfer::find($transaction->cash_transfer_id) : null);
                            if (! $linkedTransfer && $transaction->bill_no && (str_starts_with($transaction->bill_no, 'XFER-') || str_starts_with($transaction->bill_no, 'CTR-'))) {
                                $xferId = (int) str_replace(['XFER-', 'CTR-'], '', $transaction->bill_no);
                                if ($xferId > 0) {
                                    $linkedTransfer = \App\Models\CashTransfer::find($xferId);
                                }
                            }
                        @endphp
                        @if ($linkedTransfer)
                            <div class="mt-4 pt-3 border-top">
                                <div class="card border border-primary-subtle shadow-sm rounded-3">
                                    <div class="card-header bg-primary-subtle py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-arrow-left-right text-primary fs-5"></i>
                                            <strong class="text-primary small text-uppercase">
                                                Connected Cash Transfer Details 
</strong>
</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $linkedTransfer->directionBadgeClass() }} border" style="font-size: 0.75rem;">
                                                <i class="bi {{ $linkedTransfer->directionIcon() }} me-1"></i>{{ $linkedTransfer->directionLabel() }}
</span>
                                            <span class="badge {{ $linkedTransfer->settlementBadgeClass() }} border" style="font-size: 0.75rem;">
                                                {{ $linkedTransfer->settlementLabel() }}
</span>
</div>
</div>
                                    <div class="card-body p-3">
                                        <!-- Purpose Callout -->
                                        <div class="p-3 mb-3 rounded-3 bg-light border-start border-4 border-primary">
                                            <div class="text-muted small fw-semibold text-uppercase mb-1">
                                                <i class="bi bi-chat-left-quote text-primary me-1"></i>Purpose of Transfer:
</div>
                                            <h6 class="fw-bold text-dark mb-0">
                                                {{ $linkedTransfer->purpose ?: 'General site expenses / Office requirements' }}
</h6>
</div>

                                        <div class="row g-3 small">
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Transfer Ref:</span>
                                                <strong class="font-monospace text-primary">#CTR-{{ $linkedTransfer->id }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Transfer Amount:</span>
                                                <strong class="text-dark">₹{{ number_format((float) $linkedTransfer->amount, 2) }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Handed Over By:</span>
                                                <strong class="text-dark">{{ $linkedTransfer->handed_over_by ?: '—' }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Received By:</span>
                                                <strong class="text-dark">{{ $linkedTransfer->received_by ?: '—' }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Transfer Date:</span>
                                                <span class="text-dark">{{ $linkedTransfer->transfer_date ? $linkedTransfer->transfer_date->format('d M Y') : '—' }} ({{ $linkedTransfer->transfer_time ?: 'N/A' }})</span>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Payment Mode:</span>
                                                <span class="badge bg-light text-dark border">{{ $linkedTransfer->payment_mode ?: 'Cash' }}</span>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Expected Return Date:</span>
                                                <span class="text-dark">{{ $linkedTransfer->expected_return_date ? $linkedTransfer->expected_return_date->format('d M Y') : 'N/A' }}</span>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Linked Daily Ledger:</span>
                                                @if ($linkedTransfer->ledger_id)
                                                    <a href="{{ route('ledger.index') }}?search=CTR-{{ $linkedTransfer->id }}" class="text-decoration-none fw-semibold">
                                                        <i class="bi bi-book-half me-1"></i>Ledger #{{ $linkedTransfer->ledger_id }}
</a>
                                                @else
                                                    <span class="text-muted">Direct Sync</span>
                                                @endif
</div>
</div>

                                        @if ($linkedTransfer->settlement_notes)
                                            <div class="mt-2 pt-2 border-top small text-muted">
                                                <strong>Settlement Notes:</strong> {{ $linkedTransfer->settlement_notes }}
</div>
                                        @endif
</div>
                                    <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>Connected via Cash Transfer Module & Daily Ledger.
</small>
                                        <a href="{{ route('cash-transfer.index') }}?search={{ $linkedTransfer->id }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.78rem;">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Open in Cash Transfer Module
</a>
</div>
</div>
</div>
                        @endif

                        @php
                            $linkedTransfer = $transaction->cashTransfer ?? ($transaction->cash_transfer_id ? \App\Models\CashTransfer::find($transaction->cash_transfer_id) : null);
                            if (! $linkedTransfer && $transaction->bill_no && (str_starts_with($transaction->bill_no, 'XFER-') || str_starts_with($transaction->bill_no, 'CTR-'))) {
                                $xferId = (int) str_replace(['XFER-', 'CTR-'], '', $transaction->bill_no);
                                if ($xferId > 0) {
                                    $linkedTransfer = \App\Models\CashTransfer::find($xferId);
                                }
                            }
                        @endphp

                        @if ($linkedTransfer)
                            <div class="mt-4 pt-3 border-top">
                                <div class="card border border-primary-subtle shadow-sm rounded-3">
                                    <div class="card-header bg-primary-subtle py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-arrow-left-right text-primary fs-5"></i>
                                            <strong class="text-primary small text-uppercase">
                                                Connected Cash Transfer Details 
</strong>
</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge {{ $linkedTransfer->directionBadgeClass() }} border" style="font-size: 0.75rem;">
                                                <i class="bi {{ $linkedTransfer->directionIcon() }} me-1"></i>{{ $linkedTransfer->directionLabel() }}
</span>
                                            <span class="badge {{ $linkedTransfer->settlementBadgeClass() }} border" style="font-size: 0.75rem;">
                                                {{ $linkedTransfer->settlementLabel() }}
</span>
</div>
</div>
                                    <div class="card-body p-3">
                                        <!-- Purpose Callout -->
                                        <div class="p-3 mb-3 rounded-3 bg-light border-start border-4 border-primary">
                                            <div class="text-muted small fw-semibold text-uppercase mb-1">
                                                <i class="bi bi-chat-left-quote text-primary me-1"></i>Purpose of Transfer:
</div>
                                            <h6 class="fw-bold text-dark mb-0">
                                                {{ $linkedTransfer->purpose ?: 'General site expenses / Office requirements' }}
</h6>
</div>

                                        <div class="row g-3 small">
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Transfer Ref:</span>
                                                <strong class="font-monospace text-primary">#CTR-{{ $linkedTransfer->id }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Transfer Amount:</span>
                                                <strong class="text-dark">₹{{ number_format((float) $linkedTransfer->amount, 2) }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Handed Over By:</span>
                                                <strong class="text-dark">{{ $linkedTransfer->handed_over_by ?: '—' }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Received By:</span>
                                                <strong class="text-dark">{{ $linkedTransfer->received_by ?: '—' }}</strong>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Transfer Date:</span>
                                                <span class="text-dark">{{ $linkedTransfer->transfer_date ? $linkedTransfer->transfer_date->format('d M Y') : '—' }} ({{ $linkedTransfer->transfer_time ?: 'N/A' }})</span>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Payment Mode:</span>
                                                <span class="badge bg-light text-dark border">{{ $linkedTransfer->payment_mode ?: 'Cash' }}</span>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Expected Return Date:</span>
                                                <span class="text-dark">{{ $linkedTransfer->expected_return_date ? $linkedTransfer->expected_return_date->format('d M Y') : 'N/A' }}</span>
</div>
                                            <div class="col-12 col-sm-6 col-md-3">
                                                <span class="text-muted d-block">Linked Daily Ledger:</span>
                                                @if ($linkedTransfer->ledger_id)
                                                    <a href="{{ route('ledger.index') }}?search=CTR-{{ $linkedTransfer->id }}" class="text-decoration-none fw-semibold">
                                                        <i class="bi bi-book-half me-1"></i>Ledger #{{ $linkedTransfer->ledger_id }}
</a>
                                                @else
                                                    <span class="text-muted">Direct Sync</span>
                                                @endif
</div>
</div>

                                        @if ($linkedTransfer->settlement_notes)
                                            <div class="mt-2 pt-2 border-top small text-muted">
                                                <strong>Settlement Notes:</strong> {{ $linkedTransfer->settlement_notes }}
</div>
                                        @endif
</div>
                                    <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>Connected via Cash Transfer Module & Daily Ledger.
</small>
                                        <a href="{{ route('cash-transfer.index') }}?search={{ $linkedTransfer->id }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.78rem;">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Open in Cash Transfer Module
</a>
</div>
</div>
</div>
                        @endif

                        <!-- Material / Worker Items Breakdown -->
                        @if (!empty($items) && count($items) > 0)
                            @php
                                $isWageTxn = in_array($transaction->transaction_type, ['Daily Wage Payment', 'Daily Worker Wage Payment'], true);
                            @endphp
                            <div id="itemsSection" class="mt-4 pt-3 border-top">
                                <h6 class="fw-bold text-dark mb-2">
                                    @if ($isWageTxn)
                                        <i class="bi bi-people-fill text-primary me-2"></i>Worker Details Breakdown 
                                    @else
                                        <i class="bi bi-boxes text-primary me-2"></i>Material Items Breakdown 
                                    @endif
</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle small mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th>{{ $isWageTxn ? 'Worker Category ' : 'Material ' }}</th>
                                                <th class="text-center" style="width: 80px;">{{ $isWageTxn ? 'Workers' : 'Qty' }}</th>
                                                <th class="text-center" style="width: 90px;">Unit</th>
                                                <th class="text-end" style="width: 120px;">{{ $isWageTxn ? 'Daily Wage (₹)' : 'Rate (₹)' }}</th>
                                                <th class="text-end" style="width: 130px;">Total (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $idx => $item)
                                                <tr>
                                                    <td class="text-center">{{ $idx + 1 }}</td>
                                                    <td class="fw-semibold text-dark">
                                                        {{ $item['materialName'] ?? ($item['name'] ?? ($item['material'] ?? 'Item')) }}
                                                    </td>
                                                    <td class="text-center fw-bold text-primary">{{ $item['qty'] ?? 1 }}</td>
                                                    <td class="text-center text-muted small">{{ $item['unit'] ?? 'Unit' }}</td>
                                                    <td class="text-end">₹{{ number_format((float) ($item['rate'] ?? 0), 2) }}</td>
                                                    <td class="text-end fw-bold text-dark">
                                                        ₹{{ number_format((float) ($item['total'] ?? 0), 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
</div>
</div>
                        @endif
</div>
</div>

                <!-- Right Column: Receipt / Voucher Image Section -->
                <div class="col-12 col-lg-5">
                    <div class="card-custom p-4 bg-white h-100">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                            <h6 class="fw-bold mb-0 text-dark">
                                <i class="bi bi-image text-primary me-2"></i>Attached Receipt / Bill Image 
</h6>
                            @if ($transaction->receipt_image)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Receipt
                                    Attached</span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted">Not Attached</span>
                            @endif
</div>

                        @if ($transaction->receipt_image)
                            <div>
                                <div class="p-2 border rounded-3 bg-light text-center mb-3">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#receiptZoomModal"
                                        title="Click to zoom image">
                                        <img src="{{ asset($transaction->receipt_image) }}" alt="Receipt Image"
                                            class="img-fluid rounded border shadow-sm"
                                            style="max-height: 280px; object-fit: contain; cursor: zoom-in;">
</a>
</div>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#receiptZoomModal">
                                        <i class="bi bi-arrows-fullscreen me-1"></i> Full Size View
</button>
                                    <a href="{{ asset($transaction->receipt_image) }}"
                                        download="cash_receipt_{{ $transaction->id }}.png"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-download me-1"></i> Download Image
</a>
</div>
</div>
                        @else
                            <div class="p-5 text-center text-muted border border-dashed rounded-3 bg-light">
                                <i class="bi bi-image text-secondary fs-1 d-block mb-2"></i>
                                <h6 class="fw-semibold text-dark mb-1">No Receipt Image Uploaded</h6>
                                <small class="text-muted">No bill photo or cash voucher was attached when recording this
                                    transaction.</small>
</div>
                        @endif
</div>
</div>
</div>
</div>

</div>

    @if ($transaction->receipt_image)
        <!-- Full Size Receipt Image Modal -->
        <div class="modal fade" id="receiptZoomModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">
                            <i class="bi bi-receipt text-primary me-2"></i>Attached Receipt / Bill Document
</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                    <div class="modal-body text-center p-3 bg-light">
                        <img src="{{ asset($transaction->receipt_image) }}" alt="Full Receipt"
                            class="img-fluid rounded shadow border" style="max-height: 80vh;">
</div>
                    <div class="modal-footer">
                        <a href="{{ asset($transaction->receipt_image) }}" download="cash_receipt_{{ $transaction->id }}.png"
                            class="btn btn-primary btn-sm">
                            <i class="bi bi-download me-1"></i> Download
</a>
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
    @endif

    <!-- Edit Site Cash Transaction Modal -->
    <div class="modal fade" id="editSiteCashModal" tabindex="-1" aria-labelledby="editSiteCashModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-light">
                    <h6 class="modal-title fw-bold" id="editSiteCashModalLabel">
                        <i class="bi bi-pencil-square text-warning me-2"></i>Edit Site Cash Transaction 
</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="editSiteCashForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body p-4">
                        <div id="editAlertBox" class="alert alert-danger d-none mb-3 py-2 px-3 small"></div>

                        <div class="row g-3">
                            <!-- Project Site Dropdown -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-dark">
                                    Project Site  <span class="text-danger">*</span>
</label>
                                <select class="form-select form-select-sm" name="project_site_id" id="edit_project_site_id" required>
                                    <option value="">-- Select Project Site --</option>
                                    @if(isset($sites))
                                        @foreach($sites as $site)
                                            <option value="{{ $site->id }}" {{ $transaction->project_site_id == $site->id ? 'selected' : '' }}>
                                                {{ $site->name }} ({{ $site->location ?? 'Site' }})
</option>
                                        @endforeach
                                    @endif
                                </select>
                                <div class="form-text small text-muted">
                                    If recorded under the wrong site, you can change to the correct site here.
</div>
</div>

                            <!-- Transaction Type Dropdown -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-dark">
                                    Transaction Type  <span class="text-danger">*</span>
</label>
                                <select class="form-select form-select-sm" name="transaction_type" id="edit_transaction_type" required>
                                    <optgroup label="Expense ">
                                        <option value="Material Purchase" {{ $transaction->transaction_type === 'Material Purchase' ? 'selected' : '' }}>Material Purchase</option>
                                        <option value="Supplier Payment" {{ $transaction->transaction_type === 'Supplier Payment' ? 'selected' : '' }}>Supplier Payment</option>
                                        <option value="Daily Wage Payment" {{ $transaction->transaction_type === 'Daily Wage Payment' ? 'selected' : '' }}>Daily Wage Payment</option>
                                        <option value="Tool Rental" {{ $transaction->transaction_type === 'Tool Rental' ? 'selected' : '' }}>Tool Rental</option>
                                        <option value="Other Expense" {{ $transaction->transaction_type === 'Other Expense' ? 'selected' : '' }}>Other Expense</option>
                                        <option value="Balance Returned" {{ $transaction->transaction_type === 'Balance Returned' ? 'selected' : '' }}>Balance Returned</option>
                                    </optgroup>
                                    <optgroup label="Inflow ">
                                        <option value="Owner Advance" {{ $transaction->transaction_type === 'Owner Advance' ? 'selected' : '' }}>Owner Advance</option>
                                        <option value="Advance Received" {{ $transaction->transaction_type === 'Advance Received' ? 'selected' : '' }}>Advance Received</option>
                                        <option value="Client Advance" {{ $transaction->transaction_type === 'Client Advance' ? 'selected' : '' }}>Client Advance</option>
                                        <option value="Client Collection" {{ $transaction->transaction_type === 'Client Collection' ? 'selected' : '' }}>Client Collection</option>
                                    </optgroup>
                                </select>
</div>

                            <!-- Amount -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-dark d-flex justify-content-between align-items-center">
                                    <span>Amount  <span class="text-danger">*</span></span>
                                    @if ($transaction->isFromAccountManager())
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.72rem;">
                                            <i class="bi bi-lock-fill me-1"></i>From Account Manager (Read-Only)
</span>
                                    @endif
</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">₹</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control {{ $transaction->isFromAccountManager() ? 'bg-light text-muted' : '' }}" 
                                        name="amount" id="edit_amount" 
                                        value="{{ (float) $transaction->amount }}" 
                                        {{ $transaction->isFromAccountManager() ? 'readonly tabindex=-1' : '' }} 
                                        required>
                                    @if ($transaction->isFromAccountManager())
                                        <span class="input-group-text bg-light text-warning" title="Amount is locked because it came from Account Manager">
                                            <i class="bi bi-lock-fill"></i>
</span>
                                    @endif
</div>
                                @if ($transaction->isFromAccountManager())
                                    <div class="form-text small text-danger mt-1">
                                        <i class="bi bi-shield-lock me-1"></i>This amount was issued by the Account Manager / Accountant and cannot be modified (Read-Only). Only site, date, or notes can be edited.
</div>
                                @endif
</div>

                            <!-- Payment Date -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-dark">
                                    Payment Date  <span class="text-danger">*</span>
</label>
                                <input type="date" class="form-control form-control-sm" name="payment_date" id="edit_payment_date"
                                    value="{{ \Carbon\Carbon::parse($transaction->payment_date)->format('Y-m-d') }}" required>
</div>

                            <!-- Payment Mode -->
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small text-dark">
                                    Payment Mode 
</label>
                                <select class="form-select form-select-sm" name="payment_mode" id="edit_payment_mode">
                                    <option value="Cash" {{ ($transaction->payment_mode ?? 'Cash') === 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Cheque" {{ ($transaction->payment_mode ?? '') === 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="UPI" {{ ($transaction->payment_mode ?? '') === 'UPI' ? 'selected' : '' }}>UPI / Online Transfer</option>
                                </select>
</div>

                            <!-- Payment Reference (UTR / Cheque No) -->
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small text-dark">
                                    Ref / Cheque / UTR No
</label>
                                <input type="text" class="form-control form-control-sm" name="payment_reference" id="edit_payment_reference"
                                    value="{{ $transaction->payment_reference ?? '' }}" placeholder="Cheque / UTR / Txn No">
</div>

                            <!-- Bill / Voucher No -->
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small text-dark">
                                    Bill / Voucher No 
</label>
                                <input type="text" class="form-control form-control-sm" name="bill_no" id="edit_bill_no"
                                    value="{{ $transaction->bill_no ?? '' }}" placeholder="e.g. VCH-0012">
</div>

                            <!-- Paid To / Receiver Name -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-dark">
                                    Paid To / Receiver Name 
</label>
                                <input type="text" class="form-control form-control-sm" name="receiver_name" id="edit_receiver_name"
                                    value="{{ $transaction->receiver_name ?: $transaction->paid_to }}" placeholder="e.g. Electrician Kumar / Driver">
</div>

                            <!-- Paid By / Issuer -->
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-dark">
                                    Paid By 
</label>
                                <input type="text" class="form-control form-control-sm {{ $transaction->isFromAccountManager() ? 'bg-light' : '' }}" name="paid_by" id="edit_paid_by"
                                    value="{{ $transaction->paid_by ?? '' }}" placeholder="e.g. Site Manager" {{ $transaction->isFromAccountManager() ? 'readonly tabindex=-1' : '' }}>
</div>

                            <!-- Description / Remarks -->
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-dark">
                                    Description / Remarks 
</label>
                                <textarea class="form-control form-control-sm" name="description" id="edit_description" rows="3"
                                    placeholder="Enter details or reasons for adjustment...">{{ $transaction->description ?? '' }}</textarea>
</div>

                            <!-- Receipt Image Replacement -->
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-dark">
                                    Update Receipt Image 
</label>
                                <input type="file" class="form-control form-control-sm" name="receipt_image" id="edit_receipt_image" accept="image/*">
                                <div class="form-text small text-muted">
                                    Leave blank to keep existing receipt image. Allowed formats: PNG, JPG, JPEG, WEBP (Max 5MB).
</div>
</div>
</div>
</div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center" id="saveEditBtn">
                            <i class="bi bi-check-circle me-1"></i> Save Changes 
</button>
</div>
                </form>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editForm = document.getElementById('editSiteCashForm');
    const saveBtn = document.getElementById('saveEditBtn');
    const alertBox = document.getElementById('editAlertBox');

    if (editForm) {
        editForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';
            saveBtn.disabled = true;
            const originalBtnHtml = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...';

            const formData = new FormData(editForm);

            try {
                const response = await fetch("{{ route('site-cash.update', $transaction->id) }}", {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    let errorMsg = data.message || 'Error updating transaction. Please check input.';
                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey && data.errors[firstKey].length > 0) {
                            errorMsg = data.errors[firstKey][0];
                        }
                    }
                    alertBox.textContent = errorMsg;
                    alertBox.classList.remove('d-none');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalBtnHtml;
                    return;
                }

                // Success
                saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Saved!';
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Transaction updated successfully!', 'success');
                }

                setTimeout(() => {
                    if (data.encrypted_id) {
                        window.location.href = "{{ url('/site-cash') }}/" + data.encrypted_id;
                    } else {
                        window.location.reload();
                    }
                }, 700);

            } catch (err) {
                console.error('Update error:', err);
                alertBox.textContent = 'A network or server error occurred. Please try again.';
                alertBox.classList.remove('d-none');
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnHtml;
            }
        });
    }
});
</script>
@endpush