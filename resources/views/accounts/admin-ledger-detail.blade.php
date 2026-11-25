@extends('layouts.app')

@section('title', 'Admin Ledger Entry Details')
@section('page_title', 'Admin Ledger Entry Details ')

@php
    $isIncome = ($entry->flow === 'income');
    $categoryLabel = ($entry->category === 'client_payment') ? 'Client Payment ' : 'Supplier Payment ';
    $flowBadgeClass = $isIncome
        ? 'bg-success-subtle text-success border border-success-subtle'
        : 'bg-danger-subtle text-danger border border-danger-subtle';
    $flowLabel = $isIncome ? 'Income Dr ' : 'Expense Cr ';
    $formattedDate = $entry->entry_date ? \Carbon\Carbon::parse($entry->entry_date)->format('d M Y') : '—';
    $partyName = ($entry->category === 'client_payment')
        ? ($entry->client_name ?: ($entry->client?->name ?? 'N/A'))
        : ($entry->supplier_name ?: ($entry->supplier?->name ?? 'N/A'));
    $siteName = ($entry->category === 'client_payment')
        ? ($entry->site_name ?: ($entry->projectSite?->name ?? 'N/A'))
        : ($entry->is_site_based ? ($entry->site_name ?: ($entry->projectSite?->name ?? 'Site Based')) : 'All Sites (Total)');
@endphp

@section('content')
    <div class="container-fluid px-0" id="adminLedgerDetailApp">

        <!-- Top Navigation / Action Bar -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('admin-ledger.index') }}"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        title="Back to Admin Ledger">
                        <i class="bi bi-arrow-left me-1"></i> Back to Admin Ledger
</a>
                    <div class="vr d-none d-md-block"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="fw-bold mb-0 text-dark">{{ $categoryLabel }}: {{ $partyName }}</h5>
                            <span class="badge {{ $flowBadgeClass }}">{{ $flowLabel }}</span>
</div>
                        <small class="text-muted">
                            Transaction Ref ID: <span class="font-monospace text-primary fw-semibold">#AL-{{ str_pad($entry->id, 5, '0', STR_PAD_LEFT) }}</span>
                            &bull; Date: <span class="fw-semibold text-dark">{{ $formattedDate }}</span>
</small>
</div>
</div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-warning btn-sm d-inline-flex align-items-center shadow-sm"
                        data-bs-toggle="modal" data-bs-target="#editAdminLedgerModal">
                        <i class="bi bi-pencil-square me-1"></i> Edit Entry 
</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Voucher
</button>
                    <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center"
                        id="btnDeleteEntry" data-id="{{ $entry->id }}">
                        <i class="bi bi-trash me-1"></i> Delete
</button>
</div>
</div>
</div>

        <!-- 4 Quick Stat Summary Cards -->
        <div class="row g-3 mb-4">
            <!-- 1. Transaction Flow Type -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 {{ $isIncome ? 'border-success' : 'border-danger' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Flow Type</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1">{{ $flowLabel }}</h6>
</div>
                        <div class="p-2 rounded-3 {{ $isIncome ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                            <i class="bi {{ $isIncome ? 'bi-arrow-down-left-circle' : 'bi-arrow-up-right-circle' }} fs-5"></i>
</div>
</div>
</div>
</div>

            <!-- 2. Transaction Amount -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 {{ $isIncome ? 'border-success' : 'border-danger' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Amount</span>
                            <h4 class="fw-bold mb-0 mt-1 {{ $isIncome ? 'text-success' : 'text-danger' }}">
                                {{ $isIncome ? '+' : '-' }}₹{{ number_format((float) $entry->amount, 2) }}
</h4>
</div>
                        <div class="p-2 rounded-3 {{ $isIncome ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                            <i class="bi bi-cash-stack fs-5"></i>
</div>
</div>
</div>
</div>

            <!-- 3. Category -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Category</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" style="max-width: 170px;">
                                {{ $entry->category === 'client_payment' ? 'Client Payment' : ($entry->category === 'cash_transfer' ? 'Cash Transfer' : 'Supplier Payment') }}
</h6>
                            <small class="text-muted">{{ $partyName }}</small>
</div>
                        <div class="bg-primary-subtle text-primary p-2 rounded-3">
                            <i class="bi {{ $entry->category === 'client_payment' ? 'bi-person-check' : ($entry->category === 'cash_transfer' ? 'bi-arrow-left-right' : 'bi-truck') }} fs-5"></i>
</div>
</div>
</div>
</div>

            <!-- 4. Project Site / Scope -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Project Site</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" style="max-width: 170px;" title="{{ $siteName }}">
                                {{ $siteName }}
</h6>
                            <small class="text-muted">{{ $entry->is_site_based ? 'Site Based' : 'Total Scope' }}</small>
</div>
                        <div class="bg-info-subtle text-info p-2 rounded-3">
                            <i class="bi bi-building fs-5"></i>
</div>
</div>
</div>
</div>
</div>

        <!-- 2-Column Detailed Information Layout -->
        <div class="row g-4">
            <!-- Left Column: Primary Details -->
            <div class="col-12 col-lg-8">
                <!-- Transaction & Party Details Card -->
                <div class="card-custom p-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-info-circle text-primary me-2"></i>Transaction & Party Details 
</h6>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small d-block">Transaction Type / Category</label>
                            <span class="fw-bold text-dark fs-6">{{ $categoryLabel }}</span>
</div>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small d-block">Entry Date</label>
                            <span class="fw-semibold text-dark">{{ $formattedDate }}</span>
</div>

                        @if ($entry->category === 'client_payment')
                            <!-- Client Details -->
                            <div class="col-12 col-md-6">
                                <label class="text-muted small d-block">Client Name</label>
                                <span class="fw-bold text-dark fs-6 text-primary">
                                    <i class="bi bi-person-fill me-1"></i>{{ $partyName }}
</span>
                                @if ($entry->client?->phone)
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $entry->client->phone }}</div>
                                @endif
                                @if ($entry->client?->address)
                                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $entry->client->address }}</div>
                                @endif
</div>
                            <div class="col-12 col-md-6">
                                <label class="text-muted small d-block">Project Site</label>
                                <span class="fw-bold text-dark fs-6">
                                    <i class="bi bi-building me-1 text-primary"></i>{{ $siteName }}
</span>
                                @if ($entry->projectSite?->location)
                                    <div class="small text-muted">{{ $entry->projectSite->location }}</div>
                                @endif
</div>
                        @else
                            <!-- Supplier Details -->
                            <div class="col-12 col-md-6">
                                <label class="text-muted small d-block">Supplier</label>
                                <span class="fw-bold text-dark fs-6 text-primary">
                                    <i class="bi bi-truck me-1"></i>{{ $partyName }}
</span>
                                @if ($entry->supplier?->contact_person)
                                    <div class="small text-muted">Contact: {{ $entry->supplier->contact_person }}</div>
                                @endif
                                @if ($entry->supplier?->phone)
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i>{{ $entry->supplier->phone }}</div>
                                @endif
</div>
                            <div class="col-12 col-md-6">
                                <label class="text-muted small d-block">Payment Scope & Site</label>
                                @if ($entry->is_site_based)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-building me-1"></i>{{ $siteName }}</span>
                                    @if ($entry->purchase_order_no)
                                        <div class="mt-1">
                                            <small class="text-muted">PO Number:</small>
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace">{{ $entry->purchase_order_no }}</span>
</div>
                                    @endif
                                @else
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-collection me-1"></i>Total Scope (All Sites Settle)
</span>
                                @endif
</div>
                        @endif

                        <div class="col-12"><hr class="my-1"></div>

                        <!-- Payment Mode & Reference -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small d-block">Payment Mode</label>
                            <span class="badge bg-light text-dark border px-2 py-1 fs-6">
                                <i class="bi bi-credit-card me-1 text-primary"></i>{{ $entry->payment_mode }}
</span>
</div>
                        <div class="col-12 col-md-4">
                            <label class="text-muted small d-block">Cheque / Reference No</label>
                            <span class="font-monospace fw-semibold text-dark">
                                {{ $entry->cheque_no ?: ($entry->payment_reference ?: '—') }}
</span>
</div>
                        <div class="col-12 col-md-4">
                            <label class="text-muted small d-block">Bank Name</label>
                            <span class="fw-semibold text-dark">{{ $entry->bank_name ?: '—' }}</span>
</div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="text-muted small d-block">Description / Notes</label>
                            <div class="p-3 bg-light rounded-2 border text-dark">
                                {{ $entry->description ?: 'No additional notes provided for this transaction.' }}
</div>
</div>
</div>
</div>

                <!-- Audit Information Card -->
                <div class="card-custom p-3 bg-white">
                    <h6 class="fw-bold text-dark mb-2 small text-uppercase">
                        <i class="bi bi-clock-history text-secondary me-1"></i>Audit Information 
</h6>
                    <div class="row g-2 small text-muted">
                        <div class="col-12 col-md-6">
                            <span>Recorded By:</span>
                            <strong class="text-dark">{{ $entry->creator?->name ?? 'System Admin' }}</strong>
                            @if ($entry->creator?->roleName())
                                <span class="badge bg-light text-secondary border ms-1">{{ $entry->creator->roleName() }}</span>
                            @endif
</div>
                        <div class="col-12 col-md-6">
                            <span>Created At:</span>
                            <span class="text-dark">{{ $entry->created_at ? $entry->created_at->format('d M Y, h:i A') : '—' }}</span>
</div>
                        @if ($entry->updated_at && $entry->updated_at->ne($entry->created_at))
                            <div class="col-12">
                                <span>Last Updated:</span>
                                <span class="text-dark">{{ $entry->updated_at->format('d M Y, h:i A') }}</span>
</div>
                        @endif
</div>
</div>
</div>

            <!-- Right Column: Document / Receipt Attachment -->
            <div class="col-12 col-lg-4">
                <div class="card-custom p-4 bg-white h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                        <i class="bi bi-paperclip text-primary me-1"></i>Receipt / Voucher Attachment
</h6>

                    @if ($entry->receipt_image)
                        <div class="text-center p-2 border rounded-3 bg-light mb-3">
                            <img src="{{ asset('storage/' . $entry->receipt_image) }}" alt="Receipt Image"
                                class="img-fluid rounded shadow-xs" style="max-height: 350px; object-fit: contain;">
</div>
                        <div class="d-grid gap-2">
                            <a href="{{ asset('storage/' . $entry->receipt_image) }}" target="_blank"
                                class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Open Full Document
</a>
</div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary"></i>
                            <h6 class="text-dark">No Document Attached</h6>
                            <p class="small mb-3">No receipt or voucher was uploaded for this entry.</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                data-bs-toggle="modal" data-bs-target="#editAdminLedgerModal">
                                <i class="bi bi-upload me-1"></i> Upload Receipt
</button>
</div>
                    @endif
</div>
</div>
</div>

</div>

    <!-- Edit Admin Ledger Entry Modal -->
    <div class="modal fade" id="editAdminLedgerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-pencil-square me-2"></i>Edit Admin Ledger Entry 
</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="editAdminLedgerForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Entry Date -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Entry Date  <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="editEntryDate" name="entry_date"
                                    value="{{ $entry->entry_date?->format('Y-m-d') }}" required>
                                <div class="invalid-feedback" id="errEditEntryDate"></div>
</div>

                            <!-- Category Selection -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Payment Category  <span class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="editCategorySelect" name="category" required>
                                    <option value="supplier_payment" {{ $entry->category === 'supplier_payment' ? 'selected' : '' }}>
                                        Supplier Payment (-) [Expense]
</option>
                                    <option value="client_payment" {{ $entry->category === 'client_payment' ? 'selected' : '' }}>
                                        Client Payment (+) [Income]
</option>
                                </select>
                                <div class="invalid-feedback" id="errEditCategory"></div>
</div>

                            <!-- CLIENT PAYMENT SECTION -->
                            <div class="col-12" id="editClientPaymentSection" style="{{ $entry->category === 'client_payment' ? '' : 'display: none;' }}">
                                <div class="p-3 bg-success-subtle bg-opacity-25 rounded-3 border border-success-subtle">
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold text-dark">Select Client <span class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="editClientSelect" name="client_id">
                                                <option value="" disabled>-- Select Client --</option>
                                                @foreach ($clients as $c)
                                                    <option value="{{ $c->id }}" data-name="{{ $c->name }}" {{ (int) $entry->client_id === (int) $c->id ? 'selected' : '' }}>
                                                        {{ $c->name }}
</option>
                                                @endforeach
                                            </select>
</div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold text-dark">Select Project Site <span class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="editClientProjectSelect" name="project_site_id">
                                                @foreach ($sites as $s)
                                                    <option value="{{ $s->id }}" {{ (int) $entry->project_site_id === (int) $s->id ? 'selected' : '' }}>
                                                        {{ $s->name }}
</option>
                                                @endforeach
                                            </select>
</div>
</div>
</div>
</div>

                            <!-- SUPPLIER PAYMENT SECTION -->
                            <div class="col-12" id="editSupplierPaymentSection" style="{{ $entry->category === 'supplier_payment' ? '' : 'display: none;' }}">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-dark mb-1">Payment Basis</label>
                                        <div class="d-flex gap-4 pt-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="supplier_payment_scope"
                                                    id="editScopeSiteBased" value="site_based" {{ $entry->is_site_based ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-semibold" for="editScopeSiteBased">
                                                    Site Based 
</label>
</div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="supplier_payment_scope"
                                                    id="editScopeTotal" value="total" {{ ! $entry->is_site_based ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-semibold text-primary" for="editScopeTotal">
                                                    Total 
</label>
</div>
</div>
</div>

                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold text-dark">Supplier <span class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="editSupplierSelect" name="supplier_id">
                                                <option value="" disabled>-- Select Supplier --</option>
                                                @foreach ($suppliers as $sup)
                                                    <option value="{{ $sup->id }}" {{ (int) $entry->supplier_id === (int) $sup->id ? 'selected' : '' }}>
                                                        {{ $sup->name }}
</option>
                                                @endforeach
                                            </select>
</div>
                                        <div class="col-12 col-md-6" id="editSupplierSiteGroup" style="{{ $entry->is_site_based ? '' : 'display: none;' }}">
                                            <label class="form-label small fw-semibold text-dark">Project Site <span class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="editSupplierSiteSelect" name="project_site_id">
                                                <option value="" disabled>-- Select Project Site --</option>
                                                @foreach ($sites as $s)
                                                    <option value="{{ $s->id }}" {{ (int) $entry->project_site_id === (int) $s->id ? 'selected' : '' }}>
                                                        {{ $s->name }}
</option>
                                                @endforeach
                                            </select>
</div>
</div>
</div>
</div>

                            <!-- Amount -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control fw-bold" id="editAmount"
                                    name="amount" value="{{ (float) $entry->amount }}" required>
                                <div class="invalid-feedback" id="errEditAmount"></div>
</div>

                            <!-- Payment Mode -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="editPaymentModeSelect" name="payment_mode" required>
                                    @foreach (['Cash', 'Cheque', 'UPI', 'Bank Transfer'] as $mode)
                                        <option value="{{ $mode }}" {{ $entry->payment_mode === $mode ? 'selected' : '' }}>
                                            {{ $mode }}
</option>
                                    @endforeach
                                </select>
</div>

                            <!-- Cheque / Bank Details -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Cheque / Ref No</label>
                                <input type="text" class="form-control font-monospace" id="editChequeNo"
                                    name="cheque_no" value="{{ $entry->cheque_no ?: $entry->payment_reference }}">
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Bank Name</label>
                                <input type="text" class="form-control" id="editBankName"
                                    name="bank_name" value="{{ $entry->bank_name }}">
</div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Description / Notes</label>
                                <textarea class="form-control" id="editDescription" name="description" rows="2">{{ $entry->description }}</textarea>
</div>

                            <!-- Receipt Document Upload -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Update Receipt Document (Optional)</label>
                                <input type="file" class="form-control form-control-sm" id="editReceiptImage"
                                    name="receipt_image" accept="image/*,application/pdf">
</div>
</div>
</div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm px-3 fw-bold" id="updateAdminLedgerBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="updateSpinner"></span>
                            <i class="bi bi-check-circle me-1"></i> Update Entry
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
            // Edit Category switch
            $('#editCategorySelect').on('change', function () {
                const cat = $(this).val();
                if (cat === 'client_payment') {
                    $('#editClientPaymentSection').slideDown(150);
                    $('#editSupplierPaymentSection').slideUp(150);
                } else {
                    $('#editClientPaymentSection').slideUp(150);
                    $('#editSupplierPaymentSection').slideDown(150);
                }
            });

            // Edit Scope switch
            $('input[name="supplier_payment_scope"]').on('change', function () {
                const isSite = $(this).val() === 'site_based';
                if (isSite) {
                    $('#editSupplierSiteGroup').slideDown(150);
                } else {
                    $('#editSupplierSiteGroup').slideUp(150);
                }
            });

            // Submit Edit Form
            $('#editAdminLedgerForm').on('submit', function (e) {
                e.preventDefault();
                $('.is-invalid').removeClass('is-invalid');

                const formData = new FormData(this);
                const $btn = $('#updateAdminLedgerBtn');
                const $spinner = $('#updateSpinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("admin-ledger.update", $entry->id) }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');

                        if (res.success) {
                            $('#editAdminLedgerModal').modal('hide');
                            showToast(res.message || 'Updated successfully!', 'success');
                            setTimeout(() => window.location.reload(), 700);
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        showToast('Failed to update entry. Check inputs.', 'danger');
                    }
                });
            });

            // Delete Entry
            $('#btnDeleteEntry').on('click', function () {
                const id = $(this).data('id');
                if (!confirm('Are you sure you want to delete this admin ledger entry? This action cannot be undone.')) {
                    return;
                }

                $.ajax({
                    url: `/admin-ledger/${id}/delete`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function (res) {
                        if (res.success) {
                            showToast(res.message || 'Deleted successfully', 'success');
                            setTimeout(() => {
                                window.location.href = '{{ route("admin-ledger.index") }}';
                            }, 600);
                        }
                    },
                    error: function () {
                        showToast('Failed to delete entry.', 'danger');
                    }
                });
            });
        });
    </script>
@endpush
