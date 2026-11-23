@extends('layouts.app')

@section('title', 'Daily Ledger Entry Details')
@section('page_title', 'Daily Ledger Entry Details (தினசரி பேரேடு பதிவு விவரம்)')

@section('content')
    <div class="container-fluid px-0" id="ledgerDetailApp">

        @if(!isset($ledger) || !$ledger)
            <!-- Record Not Found State -->
            <div class="card-custom p-5 text-center bg-white mb-4" id="notFoundBox">
                <i class="bi bi-journal-x text-warning fs-1 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Ledger Entry Not Found</h5>
                <p class="text-muted small mb-3">The requested ledger entry (#{{ $id }}) could not be located in your database
                    records.</p>
                <a href="{{ route('ledger.index') }}" class="btn btn-deiveni btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Return to Daily Ledger
                </a>
            </div>
        @else
            @php
                $isIncome = ($ledger->flow === 'income');
                $flowBadgeClass = $isIncome
                    ? 'bg-success-subtle text-success border border-success-subtle'
                    : 'bg-danger-subtle text-danger border border-danger-subtle';
                $flowLabel = $isIncome ? 'Income (Dr - வரவு)' : 'Expense (Cr - செலவு)';
                $amountVal = (float) $ledger->amount;
            @endphp

            <!-- Top Navigation / Action Bar -->
            <div class="card-custom p-3 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <a href="{{ route('ledger.index') }}"
                            class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                            title="Back to Daily Ledger">
                            <i class="bi bi-arrow-left me-1"></i> Back to Daily Ledger
                        </a>
                        <div class="vr d-none d-md-block"></div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="fw-bold mb-0 text-dark">{{ $ledger->description ?: $ledger->source }}</h5>
                                <span class="badge {{ $flowBadgeClass }}">{{ $flowLabel }}</span>
                                <span class="badge bg-light text-dark border">{{ $ledger->source }}</span>
                            </div>
                            <small class="text-muted">Entry ID: <span class="font-monospace text-primary fw-semibold">#{{ $ledger->id }}</span> &bull; Date: <span class="fw-semibold text-dark">{{ $ledger->entry_date->format('d M Y') }}</span></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                            onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Print Entry
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Detail Content -->
            <div id="detailContentBox">
                <!-- 4 Quick Stat Summary Cards -->
                <div class="row g-3 mb-4">
                    <!-- 1. Entry Flow Type -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card-custom p-3 bg-white h-100 border-start border-4 {{ $isIncome ? 'border-success' : 'border-danger' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small fw-semibold d-block text-uppercase">Flow Type (பரிவர்த்தனை)</span>
                                    <h6 class="fw-bold mb-0 text-dark mt-1">{{ $flowLabel }}</h6>
                                </div>
                                <div class="p-2 rounded-3 {{ $isIncome ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    <i class="bi bi-arrow-left-right fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Entry Amount -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card-custom p-3 bg-white h-100 border-start border-4 {{ $isIncome ? 'border-success' : 'border-danger' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small fw-semibold d-block text-uppercase">Amount (தொகை)</span>
                                    <h5 class="fw-bold mb-0 mt-1 {{ $isIncome ? 'text-success' : 'text-danger' }}">
                                        ₹{{ number_format($amountVal, 2) }}
                                    </h5>
                                </div>
                                <div class="p-2 rounded-3 {{ $isIncome ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    <i class="bi bi-cash-stack fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Source / Category -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small fw-semibold d-block text-uppercase">Source (வகை)</span>
                                    <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" style="max-width: 170px;">
                                        {{ $ledger->source }}
                                    </h6>
                                </div>
                                <div class="bg-info-subtle text-info p-2 rounded-3">
                                    <i class="bi bi-tag fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Associated Site -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card-custom p-3 bg-white h-100 border-start border-4 border-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small fw-semibold d-block text-uppercase">Associated Site (தளம்)</span>
                                    <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" style="max-width: 170px;">
                                        {{ $ledger->site_name ?: 'General / None' }}
                                    </h6>
                                </div>
                                <div class="bg-light text-secondary p-2 rounded-3 border">
                                    <i class="bi bi-building fs-5"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Left Column: Full Specifications -->
                    <div class="col-12 col-lg-7">
                        <div class="card-custom p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-card-checklist text-primary me-2"></i>Ledger Entry Details (முழு விவரம்)
                                </h6>
                                <span class="badge bg-light text-secondary border font-monospace small">
                                    ID: #{{ $ledger->id }}
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-muted small py-2" style="width: 35%;">Entry Date:</td>
                                            <td class="fw-semibold text-dark py-2">{{ $ledger->entry_date->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small py-2">Transaction Flow:</td>
                                            <td class="py-2"><span class="badge {{ $flowBadgeClass }}">{{ $flowLabel }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small py-2">Source / Category:</td>
                                            <td class="py-2"><span class="badge bg-light text-dark border">{{ $ledger->source }}</span></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small py-2">Associated Site:</td>
                                            <td class="fw-semibold text-dark py-2">{{ $ledger->site_name ?: '—' }}</td>
                                        </tr>
                                        @if($ledger->cash_received_from)
                                            <tr>
                                                <td class="text-muted small py-2">Cash Received From:</td>
                                                <td class="fw-semibold text-primary py-2">{{ ucfirst($ledger->cash_received_from) }}</td>
                                            </tr>
                                        @endif
                                        @if($ledger->client_name)
                                            <tr>
                                                <td class="text-muted small py-2">Client Name:</td>
                                                <td class="fw-semibold text-primary py-2">{{ $ledger->client_name }}</td>
                                            </tr>
                                        @endif
                                        @if($ledger->supplier_name)
                                            <tr>
                                                <td class="text-muted small py-2">Supplier:</td>
                                                <td class="fw-semibold text-primary py-2">{{ $ledger->supplier_name }}</td>
                                            </tr>
                                        @endif
                                        @if($ledger->bank_name)
                                            <tr>
                                                <td class="text-muted small py-2">Bank & Branch:</td>
                                                <td class="fw-semibold text-dark py-2">
                                                    {{ $ledger->bank_name }}{{ $ledger->branch_name ? ' (' . $ledger->branch_name . ')' : '' }}
                                                    @if($ledger->bank_customer_ref)
                                                        <span class="text-muted small">[Ref: {{ $ledger->bank_customer_ref }}]</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        @if($ledger->property_name)
                                            <tr>
                                                <td class="text-muted small py-2">Property & Unit:</td>
                                                <td class="fw-semibold text-dark py-2">
                                                    {{ $ledger->property_name }}{{ $ledger->unit_name ? ' [' . $ledger->unit_name . ']' : '' }}
                                                </td>
                                            </tr>
                                        @endif
                                        @if($ledger->office_expense_details)
                                            <tr>
                                                <td class="text-muted small py-2">Office Expense Details:</td>
                                                <td class="fw-semibold text-dark py-2">{{ $ledger->office_expense_details }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td class="text-muted small py-2">Income (Dr - வரவு):</td>
                                            <td class="fw-bold text-success py-2">
                                                {{ $isIncome ? '+₹' . number_format($amountVal, 2) : '—' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small py-2">Expense (Cr - செலவு):</td>
                                            <td class="fw-bold text-danger py-2">
                                                {{ !$isIncome ? '−₹' . number_format($amountVal, 2) : '—' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted small py-2 align-top">Description / Narration:</td>
                                            <td class="text-dark py-2 fw-semibold">{{ $ledger->description ?: '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            @php
                                $linkedCt = $ledger->cashTransfer ?? ($ledger->cash_transfer_id ? \App\Models\CashTransfer::find($ledger->cash_transfer_id) : null);
                            @endphp

                            @if($linkedCt)
                                <div class="mt-4 pt-3 border-top">
                                    <div class="card border border-primary-subtle shadow-sm rounded-3">
                                        <div class="card-header bg-primary-subtle py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-arrow-left-right text-primary fs-5"></i>
                                                <strong class="text-primary small text-uppercase">
                                                    Linked Cash Transfer Details (பணப் பரிமாற்ற விவரம்)
                                                </strong>
                                            </div>
                                            <span class="badge {{ $linkedCt->directionBadgeClass() }} border" style="font-size: 0.75rem;">
                                                <i class="bi {{ $linkedCt->directionIcon() }} me-1"></i>{{ $linkedCt->directionLabel() }}
                                            </span>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="p-3 mb-3 rounded-3 bg-light border-start border-4 border-primary">
                                                <div class="text-muted small fw-semibold text-uppercase mb-1">
                                                    <i class="bi bi-chat-left-quote text-primary me-1"></i>பரிமாற்றத்திற்கான காரணம் / Purpose:
                                                </div>
                                                <h6 class="fw-bold text-dark mb-0">
                                                    {{ $linkedCt->purpose ?: 'General site expenses / Office requirements' }}
                                                </h6>
                                            </div>
                                            <div class="row g-2 small">
                                                <div class="col-6 col-md-4">
                                                    <span class="text-muted d-block">Reference:</span>
                                                    <strong class="font-monospace text-primary">#CTR-{{ $linkedCt->id }}</strong>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <span class="text-muted d-block">Transfer Amount:</span>
                                                    <strong class="text-dark">₹{{ number_format((float) $linkedCt->amount, 2) }}</strong>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <span class="text-muted d-block">Settlement Status:</span>
                                                    <span class="badge {{ $linkedCt->settlementBadgeClass() }}">{{ $linkedCt->settlementLabel() }}</span>
                                                </div>
                                                <div class="col-6 col-md-6">
                                                    <span class="text-muted d-block">Handed Over By:</span>
                                                    <strong class="text-dark">{{ $linkedCt->handed_over_by ?: '—' }}</strong>
                                                </div>
                                                <div class="col-6 col-md-6">
                                                    <span class="text-muted d-block">Received By:</span>
                                                    <strong class="text-dark">{{ $linkedCt->received_by ?: '—' }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-info-circle me-1"></i>Connected to Daily Ledger & Site Cash.
                                            </small>
                                            <a href="{{ route('cash-transfer.index') }}?search={{ $linkedCt->id }}" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.78rem;">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Open Cash Transfer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Task 5: Transaction Type Master Linkage Section -->
                            @if($ledger->transaction_type_name || !empty($ledger->transaction_type_data))
                                <div class="mt-4 pt-3 border-top">
                                    <h6 class="fw-bold text-dark mb-2">
                                        <i class="bi bi-tag-fill text-primary me-2"></i>Linked Transaction Type Master (பரிவர்த்தனை வகை)
                                    </h6>
                                    <div class="p-3 bg-light rounded-3 border small">
                                        @if($ledger->transaction_type_name)
                                            <div class="mb-1">
                                                <span class="text-muted">Transaction Type:</span>
                                                <strong class="text-primary">{{ $ledger->transaction_type_name }}</strong>
                                            </div>
                                            @if($ledger->transaction_type_value)
                                                <div class="mb-1">
                                                    <span class="text-muted">Entered Value / Details:</span>
                                                    <span class="badge bg-white text-dark border px-2 py-1">{{ $ledger->transaction_type_value }}</span>
                                                </div>
                                            @endif
                                        @endif

                                        @if(is_array($ledger->transaction_type_data) && count($ledger->transaction_type_data) > 0)
                                            <div class="mt-2 pt-2 border-top">
                                                <span class="text-muted d-block mb-1">Additional Transaction Type Values:</span>
                                                <ul class="list-unstyled mb-0">
                                                    @foreach($ledger->transaction_type_data as $item)
                                                        @if(!empty($item['value']))
                                                            <li class="py-1">
                                                                <i class="bi bi-chevron-right text-muted me-1"></i>
                                                                <strong>{{ $item['name'] }}:</strong>
                                                                <span class="ms-1">{{ $item['value'] }}</span>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Audit info -->
                            <div class="mt-3 text-muted small">
                                <i class="bi bi-clock me-1"></i>Created: {{ $ledger->created_at->format('d M Y, h:i A') }}
                                @if($ledger->creator)
                                    &bull; Recorded by: {{ $ledger->creator->name }}
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Receipt / Voucher Image Section -->
                    <div class="col-12 col-lg-5">
                        <div class="card-custom p-4 bg-white h-100">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-image text-primary me-2"></i>Attached Receipt / Voucher Image (ரசீது படம்)
                                </h6>
                                @if($ledger->receipt_image)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Receipt Attached</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted">Not Attached</span>
                                @endif
                            </div>

                            @if($ledger->receipt_image)
                                <!-- When image is available -->
                                <div id="hasImageBox">
                                    <div class="p-2 border rounded-3 bg-light text-center mb-3">
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#ledgerReceiptZoomModal" title="Click to zoom image">
                                            <img id="receiptImageEl" src="{{ asset($ledger->receipt_image) }}" alt="Voucher Receipt Image"
                                                class="img-fluid rounded border shadow-sm"
                                                style="max-height: 280px; object-fit: contain; cursor: zoom-in;">
                                        </a>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#ledgerReceiptZoomModal">
                                            <i class="bi bi-arrows-fullscreen me-1"></i> Full Size View
                                        </button>
                                        <a href="{{ asset($ledger->receipt_image) }}" download="ledger_voucher_{{ $ledger->id }}.png"
                                            class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-download me-1"></i> Download Image
                                        </a>
                                    </div>
                                </div>
                            @else
                                <!-- When no image was attached -->
                                <div id="noImageBox" class="p-5 text-center text-muted border border-dashed rounded-3 bg-light">
                                    <i class="bi bi-image text-secondary fs-1 d-block mb-2"></i>
                                    <h6 class="fw-semibold text-dark mb-1">No Receipt Image Uploaded</h6>
                                    <small class="text-muted">No bill photo, receipt or payment voucher was attached when saving
                                        this ledger entry.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($ledger->receipt_image)
                <!-- Full Size Receipt Image Modal -->
                <div class="modal fade" id="ledgerReceiptZoomModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title fw-bold">
                                    <i class="bi bi-receipt text-primary me-2"></i>Attached Voucher / Receipt Document
                                </h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center p-3 bg-light">
                                <img src="{{ asset($ledger->receipt_image) }}" alt="Full Voucher Receipt" class="img-fluid rounded shadow border"
                                    style="max-height: 80vh;">
                            </div>
                            <div class="modal-footer">
                                <a href="{{ asset($ledger->receipt_image) }}" download="ledger_voucher_{{ $ledger->id }}.png" class="btn btn-primary btn-sm">
                                    <i class="bi bi-download me-1"></i> Download
                                </a>
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

    </div>
@endsection
