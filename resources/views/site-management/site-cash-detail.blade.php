@extends('layouts.app')

@section('title', 'Site Cash Transaction Details')
@section('page_title', 'Site Cash Transaction Details (தள ரொக்க பரிவர்த்தனை விவரம்)')

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
            </div>
        </div>

        <div id="detailContentBox">
            <div class="row g-3 mb-4">
                <!-- 1. Transaction Flow Type -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Flow Type (வகை)</span>
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
                                <span class="text-muted small fw-semibold d-block text-uppercase">Amount (தொகை)</span>
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
                <div class="col-12 col-s  m-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Project Site (தளம்)</span>
                                <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" style="max-width: 170px;">
                                    {{ $siteName }}
                                </h6>
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
                                <i class="bi bi-card-text text-primary me-2"></i>Transaction Specifications (முழு விவரம்)
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
                                            <td class="text-muted small py-2">Paid By (வழங்கியவர்):</td>
                                            <td class="fw-semibold text-dark py-2">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                    <i class="bi bi-person-badge me-1"></i>{{ $transaction->paid_by }}
                                                </span>
                                                @if ($transaction->wage_cash_source)
                                                    <span class="badge {{ $transaction->wage_cash_source === 'project_cash' ? 'bg-warning-subtle text-dark border border-warning-subtle' : 'bg-info-subtle text-info border border-info-subtle' }} ms-1">
                                                        {{ $transaction->wage_cash_source === 'project_cash' ? 'Project Cash (திட்ட ரொக்கம்)' : 'Office Cash (அலுவலக ரொக்கம்)' }}
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
                                                    <i class="bi bi-card-text me-1"></i>Cheque (காசோலை)
                                                </span>
                                            @elseif ($transaction->payment_mode === 'UPI')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">
                                                    <i class="bi bi-qr-code me-1"></i>UPI (யுபிஐ)
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="bi bi-cash-stack me-1"></i>Cash (ரொக்கம்)
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($transaction->payment_reference)
                                        <tr>
                                            <td class="text-muted small py-2">
                                                {{ $transaction->payment_mode === 'Cheque' ? 'Cheque Number (காசோலை எண்):' : 'UPI Reference / UTR No:' }}
                                            </td>
                                            <td class="font-monospace text-primary fw-bold py-2">
                                                {{ $transaction->payment_reference }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($transaction->bank_name)
                                        <tr>
                                            <td class="text-muted small py-2">Bank Name (வங்கி பெயர்):</td>
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
                                                Connected Cash Transfer Details (பணப் பரிமாற்ற இணைப்பு விவரம்)
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
                                                <i class="bi bi-chat-left-quote text-primary me-1"></i>காரணம் / Purpose of Transfer:
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
                                                Connected Cash Transfer Details (பணப் பரிமாற்ற இணைப்பு விவரம்)
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
                                                <i class="bi bi-chat-left-quote text-primary me-1"></i>காரணம் / Purpose of Transfer:
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
                                        <i class="bi bi-people-fill text-primary me-2"></i>Worker Details Breakdown (தொழிலாளர்கள் விவரம்)
                                    @else
                                        <i class="bi bi-boxes text-primary me-2"></i>Material Items Breakdown (பொருட்கள் பட்டியல்)
                                    @endif
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle small mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th>{{ $isWageTxn ? 'Worker Category (தொழிலாளர் பிரிவு)' : 'Material (பொருள்)' }}</th>
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
                                <i class="bi bi-image text-primary me-2"></i>Attached Receipt / Bill Image (ரசீது
                                புகைப்படம்)
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
@endsection