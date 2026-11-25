@extends('layouts.app')

@section('title', 'Cash Transfer')
@section('page_title', 'Cash Transfer Management ')

@push('styles')
    <style>
        .direction-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background-color: #f8fafc;
        }

        .direction-card:hover {
            border-color: #94a3b8;
            background-color: #ffffff;
        }

        .direction-card.selected-office-site {
            border-color: #2563eb !important;
            background-color: #eff6ff !important;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1);
        }

        .direction-card.selected-project-cash {
            border-color: #059669 !important;
            background-color: #ecfdf5 !important;
            box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.15);
        }

        .direction-card.selected-site-office {
            border-color: #f59e0b !important;
            background-color: #fffbeb !important;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.1);
        }

        .pagination .page-link {
            color: #1e3a8a;
            border-color: #e2e8f0;
            border-radius: 6px;
            padding: 0.32rem 0.65rem;
            font-size: 0.825rem;
            font-weight: 500;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-color: #1e3a8a;
            color: #ffffff;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0">

        <!-- Top Header Card -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-arrow-left-right text-primary me-2"></i>
                        Cash Transfer Management
</h5>
</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1" id="btnOpenNewTransfer" data-bs-toggle="modal" data-bs-target="#cashTransferModal">
                        <i class="bi bi-plus-circle"></i>
                        <span>New Cash Transfer</span>
</button>
</div>
</div>
</div>

        <!-- 5 Quick KPI Summary Cards -->
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-md-4 col-xl">
                <div class="card-custom p-3 bg-white border-start border-4 border-primary h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">1. Office &rarr; Site Transferred</div>
                            <h5 class="fw-bold mb-0 text-primary mt-1" id="kpiOfficeToSite">₹{{ number_format($kpis['total_office_to_site'], 2) }}</h5>
</div>
                        <div class="bg-primary-subtle text-primary p-2 rounded">
                            <i class="bi bi-arrow-up-right-circle-fill fs-5"></i>
</div>
</div>
</div>
</div>

            <div class="col-6 col-md-4 col-xl">
                <div class="card-custom p-3 bg-white border-start border-4 border-success h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">2. Project Cash to Site</div>
                            <h5 class="fw-bold mb-0 text-success mt-1" id="kpiProjectCash">₹{{ number_format($kpis['total_project_cash'] ?? 0, 2) }}</h5>
</div>
                        <div class="bg-success-subtle text-success p-2 rounded">
                            <i class="bi bi-wallet2 fs-5"></i>
</div>
</div>
</div>
</div>

            <div class="col-6 col-md-4 col-xl">
                <div class="card-custom p-3 bg-white border-start border-4 border-warning h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">3. Site &rarr; Office Borrowed</div>
                            <h5 class="fw-bold mb-0 text-warning-emphasis mt-1" id="kpiSiteToOffice">₹{{ number_format($kpis['total_site_to_office'], 2) }}</h5>
</div>
                        <div class="bg-warning-subtle text-dark p-2 rounded">
                            <i class="bi bi-arrow-down-left-circle-fill fs-5"></i>
</div>
</div>
</div>
</div>

            <div class="col-6 col-md-6 col-xl">
                <div class="card-custom p-3 bg-white border-start border-4 border-danger h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">4. Pending Return</div>
                            <h5 class="fw-bold mb-0 text-danger mt-1" id="kpiPendingReturn">₹{{ number_format($kpis['total_pending_return'], 2) }}</h5>
</div>
                        <div class="bg-danger-subtle text-danger p-2 rounded">
                            <i class="bi bi-hourglass-split fs-5"></i>
</div>
</div>
</div>
</div>

            <div class="col-12 col-md-6 col-xl">
                <div class="card-custom p-3 bg-white border-start border-4 border-info h-100 shadow-sm">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small fw-semibold">5. Settled / Returned</div>
                            <h5 class="fw-bold mb-0 text-info mt-1" id="kpiSettled">₹{{ number_format($kpis['total_settled'], 2) }}</h5>
</div>
                        <div class="bg-info-subtle text-info p-2 rounded">
                            <i class="bi bi-check2-all fs-5"></i>
</div>
</div>
</div>
</div>
</div>

        <!-- Filter & Search Toolbar Card -->
        <div class="card-custom p-3 mb-4 bg-white shadow-sm border-0">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Transfer Direction</label>
                    <select class="form-select form-select-sm" id="filterDirection">
                        <option value="all">All Directions</option>
                        <option value="office_to_site">🏢 &rarr; 🏗️ Office to Site</option>
                        <option value="project_cash">💰 &rarr; 🏗️ Project Cash to Site</option>
                        <option value="site_to_office">🏗️ &rarr; 🏢 Site to Office</option>
                    </select>
</div>

                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Project Site</label>
                    <select class="form-select form-select-sm" id="filterSite">
                        <option value="all">All Project Sites</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
</div>

                <div class="col-12 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="all">All Statuses</option>
                        <option value="pending_acceptance">⏳ Pending Acceptance</option>
                        <option value="accepted">✓ Accepted / Received</option>
                        <option value="pending_return">↩️ Pending Return</option>
                        <option value="settled">✓ Settled</option>
                    </select>
</div>

                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1">Search Particulars / Person</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="filterSearch" placeholder="Search person, purpose, ref...">
</div>
</div>

                <div class="col-12 col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnResetFilters" title="Reset Filters" style="margin-top: 1.5rem;">
                        <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
</div>
</div>

        <!-- Cash Transfers Data Table Card -->
        <div class="card-custom p-3 p-md-4 bg-white mb-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt text-primary me-1"></i>
                        Cash Transfer Register 
</h6>
</div>
                <span class="badge bg-light text-dark border" id="transfersCountBadge">{{ count($transfers) }} Entries</span>
</div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="cashTransfersTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="min-width: 110px;">Date &amp; Time</th>
                            <th style="min-width: 150px;">Direction</th>
                            <th style="min-width: 160px;">Project Site</th>
                            <th style="min-width: 170px;">From &rarr; To Person</th>
                            <th style="min-width: 220px;">Purpose / Reason</th>
                            <th class="text-end" style="min-width: 120px;">Amount (₹)</th>
                            <th style="min-width: 100px;">Payment Mode</th>
                            <th class="text-center" style="min-width: 120px;">Status</th>
                            <th class="text-center" style="min-width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="cashTransfersTableBody" class="small">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                    <tfoot class="table-light fw-bold small">
                        <tr>
                            <td colspan="5">Summary Total:</td>
                            <td class="text-end text-primary fs-6" id="footTotalAmount">₹0.00</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
</div>

            <!-- Table Pagination & Footer Summary -->
            <div class="p-3 bg-white border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-3" id="tablePaginationContainer">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="small text-muted">Rows per page:</span>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: 75px;">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">0</strong> of <strong class="text-dark" id="pageTotal">0</strong> entries
</span>
</div>
                <nav aria-label="Cash transfers pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- MODAL: Add / Edit Cash Transfer -->
    <div class="modal fade" id="cashTransferModal" tabindex="-1" aria-labelledby="cashTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <form id="cashTransferForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="transferEditId" name="id">

                    <div class="modal-header bg-light py-3 border-bottom">
                        <h6 class="modal-title fw-bold text-dark" id="cashTransferModalLabel">
                            <i class="bi bi-arrow-left-right text-primary me-2"></i>New Cash Transfer 
</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

                    <div class="modal-body p-3 p-md-4">
                        @php
                            $isSm = auth()->user()?->isSiteManager();
                            $currentUserName = auth()->user()?->name ?? 'Owner';
                        @endphp
                        <input type="hidden" id="transferDirection" name="transfer_direction" value="{{ $isSm ? 'site_to_office' : 'office_to_site' }}">
                        <input type="hidden" id="transferHandedOverBy" name="handed_over_by" value="{{ $currentUserName }}">

                        <!-- Step 1: Project Site, Date & Time -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">
                                    Project Site  <span class="text-danger">*</span>
                                </label>
                                <select class="form-select fw-semibold" id="transferSiteId" name="project_site_id" required>
                                    <option value="" disabled selected>-- Select Project Site --</option>
                                    @foreach ($sites as $site)
                                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errTransferSiteId"></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Transfer Date  <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transferDate" name="transfer_date" value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback" id="errTransferDate"></div>
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-dark">Time</label>
                                <input type="text" class="form-control" id="transferTime" name="transfer_time" value="{{ date('h:i A') }}">
                            </div>
                        </div>

                        <!-- Read-only Project Received & Available Amount Box -->
                        <div class="p-3 mb-3 rounded-3 bg-light border" id="siteReceivedAmountBox" style="display: none;">
                            <div class="row g-3 align-items-center">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold text-muted mb-1">
                                        <i class="bi bi-cash-coin text-success me-1"></i>Received Amount
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold text-success">₹</span>
                                        <input type="text" class="form-control fw-bold text-success bg-white" id="siteReceivedAmountReadonly" value="0.00" readonly>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold text-muted mb-1">
                                        <i class="bi bi-wallet2 text-primary me-1"></i>Available Amount
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white fw-bold text-primary">₹</span>
                                        <input type="text" class="form-control fw-bold text-primary bg-white" id="siteAvailableBalanceReadonly" value="0.00" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Transfer Amount & Payment Mode -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">
                                    Transfer Amount  <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">₹</span>
                                    <input type="number" step="any" class="form-control fw-bold fs-6" id="transferAmount" name="amount" placeholder="e.g. 10000" required>
                                </div>
                                <div class="invalid-feedback" id="errTransferAmount"></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Payment Mode  <span class="text-danger">*</span></label>
                                <select class="form-select" id="transferPaymentMode" name="payment_mode" required>
                                    <option value="Cash" selected>Cash</option>
                                    <option value="Cheque">Cheque</option>
                                    <option value="UPI">UPI / GPay / PhonePe</option>
                                    <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Mode Details (Ref, Bank) -->
                        <div class="row g-3 mb-3" id="paymentDetailsRow" style="display: none;">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark" id="paymentRefLabel">Transaction Ref / UTR / Cheque No</label>
                                <input type="text" class="form-control font-monospace" id="transferPaymentRef" name="payment_reference" placeholder="e.g. UTR-4910284910">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark">Bank Name</label>
                                <input type="text" class="form-control" id="transferBankName" name="bank_name" placeholder="e.g. SBI, Indian Bank, HDFC">
                            </div>
                        </div>

                        <!-- Step 3: Role -> User (Handed Over To) -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark" for="transferRoleSelect">
                                    Role  <span class="text-danger">*</span>
                                </label>
                                <select class="form-select fw-semibold" id="transferRoleSelect" required>
                                    <option value="" disabled selected>-- Select Role --</option>
                                    @foreach ($roles as $roleItem)
                                        <option value="{{ $roleItem->id }}" data-slug="{{ $roleItem->slug }}">{{ $roleItem->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errTransferHandedOverBy"></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold text-dark" for="transferUserSelect">
                                    Handed Over To (User)  <span class="text-danger">*</span>
                                </label>
                                <select class="form-select fw-semibold" id="transferUserSelect" name="received_by" required>
                                    <option value="" disabled selected>-- Select Role First --</option>
                                </select>
                                <div class="invalid-feedback" id="errTransferReceivedBy"></div>
                            </div>
                        </div>

                        <!-- Step 4: Temporary Borrowing Details (Shown when Site to Office) -->
                        <div class="p-3 mb-3 rounded-3 bg-warning-subtle bg-opacity-25 border border-warning-subtle" id="temporaryBorrowingBox" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark">
                                    <i class="bi bi-clock-history text-warning me-1"></i>Temporary Borrowing &amp; Return Terms
</span>
                                <span class="badge bg-warning text-dark small">Temporary Office Use</span>
</div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="transferIsTemporary" name="is_temporary" value="1" checked>
                                        <label class="form-check-label small fw-semibold text-dark" for="transferIsTemporary">
                                            This amount is temporary and will be returned to site budget 
</label>
</div>
</div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-semibold text-dark mb-1">Expected Return Date</label>
                                    <input type="date" class="form-control form-control-sm" id="transferExpectedReturnDate" name="expected_return_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}">
</div>
</div>
</div>

                        <!-- Purpose / Description -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Purpose / Reason</label>
                            <textarea class="form-control" id="transferPurpose" name="purpose" rows="2" placeholder="e.g. Urgent site materials & plumbing advance, or temporary office cash shortage..."></textarea>
</div>

                        <!-- Receipt Image -->
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-dark">Voucher / Receipt Image</label>
                            <input type="file" class="form-control form-control-sm" id="transferReceiptImage" name="receipt_image" accept="image/*">
</div>
</div>

                    <div class="modal-footer bg-light py-2 border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3 d-flex align-items-center gap-1" id="btnSaveTransfer">
                            <span class="spinner-border spinner-border-sm d-none" id="saveTransferSpinner"></span>
                            <span id="saveTransferBtnText">Record Cash Transfer</span>
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- MODAL: Settle Temporary Cash Transfer -->
    <div class="modal fade" id="settleTransferModal" tabindex="-1" aria-labelledby="settleTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <form id="settleTransferForm">
                    @csrf
                    <input type="hidden" id="settleTransferId" name="id">

                    <div class="modal-header bg-success-subtle py-3 border-bottom">
                        <h6 class="modal-title fw-bold text-success" id="settleTransferModalLabel">
                            <i class="bi bi-check2-circle me-1"></i>Settle Temporary Cash Transfer 
</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

                    <div class="modal-body p-3">
                        <p class="small text-muted mb-2">
                            You are marking this temporary borrowing as returned/settled. Confirm that the borrowed amount has been handed back:
                        </p>
                        <div class="alert alert-info py-2 px-3 small mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle-fill text-primary fs-5"></i>
                            <div>
                                <strong>Auto Sync:</strong>
                                <span id="settleSyncHint">Once settled, this will automatically reflect as an inflow (+ Inflow) in Site Cash and an outflow (- Outflow) in the Office Ledger.</span>
</div>
</div>
                        <div class="p-2 mb-3 rounded bg-light border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Site:</span>
                                <strong class="text-dark" id="settleSiteName">—</strong>
</div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Borrowed Amount:</span>
                                <strong class="text-success fs-6" id="settleAmount">—</strong>
</div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Borrowed Date:</span>
                                <span class="text-dark small" id="settleDate">—</span>
</div>
</div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Settlement Notes</label>
                            <textarea class="form-control form-control-sm" id="settleNotes" name="settlement_notes" rows="2" placeholder="e.g. Amount returned in full to Site Manager Bala on {{ date('d M Y') }}"></textarea>
</div>
</div>

                    <div class="modal-footer bg-light py-2 border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm px-3" id="btnConfirmSettle">
                            <span class="spinner-border spinner-border-sm d-none" id="settleSpinner"></span>
                            <span>Confirm &amp; Mark Settled</span>
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- MODAL: Accept Cash Transfer -->
    <div class="modal fade" id="acceptTransferModal" tabindex="-1" aria-labelledby="acceptTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <form id="acceptTransferForm">
                    @csrf
                    <input type="hidden" id="acceptTransferId">
                    <div class="modal-header bg-success text-white py-3">
                        <h6 class="modal-title fw-bold" id="acceptTransferModalLabel">
                            <i class="bi bi-check-circle-fill me-2"></i>Accept Cash Transfer 
</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                    <div class="modal-body p-4">
                        <div class="alert alert-success-subtle border border-success-subtle mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-cash-stack fs-3 text-success me-2"></i>
                                <div>
                                    <span class="small text-muted d-block">Transfer Amount :</span>
                                    <h4 class="fw-bold text-success mb-0" id="acceptModalAmount">₹0.00</h4>
</div>
</div>
                            <div class="small text-dark mt-2" id="acceptModalDetails">
                                <!-- Details populated via JS -->
</div>
</div>
                        <p class="small text-muted mb-3">
                            Are you sure you want to accept this amount? Once accepted, it will be credited to your account (Site Cash Handling / Office Ledger) immediately.
                        </p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Acceptance Remarks</label>
                            <input type="text" class="form-control" id="acceptModalNotes" placeholder="e.g. Cash received in hand by Site Manager">
</div>
</div>
                    <div class="modal-footer bg-light py-2 border-top">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3" id="btnConfirmAccept">
                            <i class="bi bi-check-lg me-1"></i>Confirm &amp; Accept 
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- MODAL: View Transfer Details -->
    <div class="modal fade" id="viewTransferModal" tabindex="-1" aria-labelledby="viewTransferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0">
                <div class="modal-header bg-light py-3 border-bottom">
                    <h6 class="modal-title fw-bold text-dark" id="viewTransferModalLabel">
                        <i class="bi bi-info-circle text-primary me-2"></i>Cash Transfer Details
</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <div class="modal-body p-3" id="viewTransferBody">
                    <!-- Populated dynamically -->
</div>
                <div class="modal-footer bg-light py-2 border-top">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
</div>
</div>
</div>
</div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            let rawTransfers = @json($transfers);
            let canSettle = @json($canSettle ?? false);

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html();
            }

            function formatDateDisplay(dateStr) {
                if (!dateStr) return '-';
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const parts = String(dateStr).split('-');
                if (parts.length !== 3) return dateStr;
                return `${parts[2]} ${months[parseInt(parts[1], 10) - 1]} ${parts[0]}`;
            }

            const isSiteManagerUser = {{ auth()->user()?->isSiteManager() ? 'true' : 'false' }};
            const siteFundsMap = @json($siteFundsMap);
            const transferUsers = @json($transferUsers);

            function formatInrAmount(val) {
                return Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function resetTransferFormDefaults() {
                $('#transferDirection').val(isSiteManagerUser ? 'site_to_office' : 'office_to_site');
                $('#siteReceivedAmountBox').hide();
                $('#transferRoleSelect').val('');
                $('#transferUserSelect').html('<option value="" disabled selected>-- Select Role First --</option>');
                $('#paymentDetailsRow').hide();
            }

            function populateUsersByRole() {
                const roleVal = $('#transferRoleSelect').val();
                const userSelect = $('#transferUserSelect');

                if (!roleVal) {
                    userSelect.html('<option value="" disabled selected>-- Select Role First --</option>');
                    return;
                }

                const selectedOpt = $('#transferRoleSelect').find('option:selected');
                const roleId = String(roleVal || '');
                const roleSlug = String(selectedOpt.data('slug') || '').toLowerCase().replace(/[-\s]+/g, '_');

                let matchedUsers = transferUsers.filter(function (u) {
                    const uSlug = String(u.role_slug || '').toLowerCase().replace(/[-\s]+/g, '_');
                    return (roleId && String(u.role_id || '') === roleId) || (roleSlug && uSlug === roleSlug);
                });

                if (roleSlug === 'site_manager') {
                    const siteId = $('#transferSiteId').val();
                    if (!siteId) {
                        userSelect.html('<option value="" disabled selected>-- Select Project Site First --</option>');
                        return;
                    }
                    const siteCreatedBy = siteFundsMap[siteId] ? siteFundsMap[siteId].created_by : null;
                    matchedUsers = matchedUsers.filter(function (u) {
                        return siteCreatedBy && Number(u.id) === Number(siteCreatedBy);
                    });
                }

                userSelect.empty();
                if (matchedUsers.length === 0) {
                    userSelect.append('<option value="" disabled selected>-- No users found --</option>');
                } else if (matchedUsers.length === 1) {
                    userSelect.append($('<option>', {
                        value: matchedUsers[0].name,
                        text: matchedUsers[0].name,
                        selected: true
                    }));
                } else {
                    userSelect.append('<option value="" disabled selected>-- Select User --</option>');
                    matchedUsers.forEach(function (u) {
                        userSelect.append($('<option>', {
                            value: u.name,
                            text: u.name
                        }));
                    });
                }

                // Keep transfer_direction aligned for backend sync
                if (isSiteManagerUser && roleSlug === 'account_manager') {
                    $('#transferDirection').val('site_to_office');
                } else {
                    $('#transferDirection').val('office_to_site');
                }
            }

            // Load read-only received & available amount when project site is selected
            $('#transferSiteId').on('change', function () {
                const siteId = $(this).val();
                const info = siteFundsMap[siteId];
                if (info) {
                    $('#siteReceivedAmountReadonly').val(formatInrAmount(info.received_amount));
                    $('#siteAvailableBalanceReadonly').val(formatInrAmount(info.available_amount));
                    $('#siteReceivedAmountBox').slideDown(150);
                } else {
                    $('#siteReceivedAmountBox').slideUp(150);
                }
                populateUsersByRole();
            });

            // Load users dynamically based on selected role
            $('#transferRoleSelect').on('change', populateUsersByRole);

            // Toggle payment mode details
            $('#transferPaymentMode').on('change', function () {
                const mode = $(this).val();
                if (mode === 'Cash') {
                    $('#paymentDetailsRow').slideUp(150);
                } else {
                    $('#paymentDetailsRow').slideDown(150);
                    if (mode === 'UPI') {
                        $('#paymentRefLabel').text('UPI Ref / UTR No');
                    } else if (mode === 'Cheque') {
                        $('#paymentRefLabel').text('Cheque Number ');
                    } else {
                        $('#paymentRefLabel').text('Transaction / Reference No');
                    }
                }
            });

            // Client-side filtering & rendering
            let filteredList = [];
            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function renderTransfersTable() {
                const dir = $('#filterDirection').val();
                const site = $('#filterSite').val();
                const status = $('#filterStatus').val();
                const search = ($('#filterSearch').val() || '').toLowerCase().trim();

                let totalAmt = 0;
                let matchCount = 0;
                filteredList = [];

                const tbody = $('#cashTransfersTableBody');
                tbody.empty();

                rawTransfers.forEach(function (t) {
                    if (dir !== 'all' && t.transfer_direction !== dir) return;
                    if (site !== 'all' && String(t.project_site_id) !== String(site)) return;
                    if (status !== 'all') {
                        if (status === 'pending_acceptance' && t.acceptance_status !== 'pending') return;
                        if (status === 'accepted' && t.acceptance_status !== 'accepted') return;
                        if (status === 'pending_return' && (t.settlement_status !== 'pending' || t.transfer_direction === 'project_cash')) return;
                        if (status === 'settled' && t.settlement_status !== 'settled') return;
                        if (status === 'pending' && t.settlement_status !== 'pending') return;
                    }

                    if (search) {
                        const siteName = t.project_site ? t.project_site.name : '';
                        const haystack = `${t.transfer_date} ${t.handed_over_by} ${t.received_by} ${t.purpose || ''} ${t.payment_reference || ''} ${siteName}`.toLowerCase();
                        if (!haystack.includes(search)) return;
                    }

                    matchCount++;
                    filteredList.push(t);

                    const amt = parseFloat(t.amount) || 0;
                    totalAmt += amt;

                    const isOfficeToSite = t.transfer_direction === 'office_to_site';
                    const isProjectCash = t.transfer_direction === 'project_cash';
                    let dirBadge = '';
                    if (isProjectCash) {
                        dirBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-wallet2 me-1"></i>Project Cash to Site</span>`;
                    } else if (isOfficeToSite) {
                        dirBadge = `<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-arrow-right me-1"></i>Office &rarr; Site</span>`;
                    } else {
                        dirBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="bi bi-arrow-left me-1"></i>Site &rarr; Office</span>`;
                    }

                    const siteName = t.project_site ? escapeHtml(t.project_site.name) : '<span class="text-muted">—</span>';
                    const dateDisplay = `<div class="fw-bold text-dark">${formatDateDisplay(t.transfer_date)}</div><div class="text-muted" style="font-size:0.75rem;">${escapeHtml(t.transfer_time || '')}</div>`;

                    const persons = `<div class="fw-semibold text-dark">${escapeHtml(t.handed_over_by)}</div><div class="text-muted" style="font-size:0.75rem;">&rarr; ${escapeHtml(t.received_by)}</div>`;

                    let acceptanceBadge = '';
                    if (t.acceptance_status === 'accepted') {
                        acceptanceBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Accepted</span>`;
                    } else {
                        const awaitingRole = (t.transfer_direction === 'site_to_office') ? 'Office (AM)' : 'Site Manager';
                        acceptanceBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" title="Dispatched; waiting for ${awaitingRole} to accept in hand"><i class="bi bi-hourglass-split me-1"></i>Pending (${awaitingRole})</span>`;
                    }

                    let statusBadge = '';
                    if (isProjectCash) {
                        statusBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-wallet2 me-1"></i>Project Fund</span>`;
                    } else if (t.settlement_status === 'settled') {
                        statusBadge = `<span class="badge bg-success text-white px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-check2-all me-1"></i>Settled</span>`;
                    } else if (isOfficeToSite) {
                        statusBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>Return Pending</span>`;
                    } else {
                        statusBadge = `<span class="badge bg-warning text-dark px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-arrow-repeat me-1"></i>Return Pending</span>`;
                    }

                    let acceptBtn = '';
                    if (t.can_accept && t.acceptance_status === 'pending') {
                        acceptBtn = `<button class="btn btn-xs btn-success py-1 px-2 btn-accept-transfer text-white fw-bold shadow-sm" data-id="${t.id}" data-dir="${t.transfer_direction}" data-site="${escapeHtml(siteName)}" data-amt="₹${amt.toLocaleString('en-IN')}" data-from="${escapeHtml(t.handed_over_by)}" data-to="${escapeHtml(t.received_by)}" title="Accept & Confirm Cash Receipt"><i class="bi bi-check-circle me-1"></i>Accept</button>`;
                    }

                    const settleBtn = (canSettle && t.settlement_status === 'pending' && t.acceptance_status === 'accepted' && !isProjectCash)
                        ? `<button class="btn btn-xs btn-outline-success py-1 px-2 btn-settle-transfer" data-id="${t.id}" data-dir="${t.transfer_direction}" data-site="${escapeHtml(siteName)}" data-amt="₹${amt.toLocaleString('en-IN')}" data-date="${t.transfer_date}" title="Mark Settled / Returned"><i class="bi bi-check2"></i> Settle</button>`
                        : '';

                    const actionButtons = `
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            ${acceptBtn}
                            ${settleBtn}
                            <button class="btn btn-xs btn-outline-primary py-1 px-2 btn-view-transfer" data-id="${t.id}" title="View Details"><i class="bi bi-eye"></i></button>
                            <button class="btn btn-xs btn-outline-danger py-1 px-2 btn-delete-transfer" data-id="${t.id}" title="Delete Transfer"><i class="bi bi-trash"></i></button>
</div>
                    `;

                    tbody.append(`
                        <tr id="row_transfer_${t.id}">
                            <td>${dateDisplay}</td>
                            <td>${dirBadge}</td>
                            <td>${siteName}</td>
                            <td>${persons}</td>
                            <td>
                                <div class="text-truncate text-dark" style="max-width: 250px;" title="${escapeHtml(t.purpose || '')}">
                                    ${escapeHtml(t.purpose || '—')}
</div>
                            </td>
                            <td class="text-end fw-bold text-dark fs-6">₹${amt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                            <td>
                                <span class="badge bg-light text-dark border">${escapeHtml(t.payment_mode || 'Cash')}</span>
                            </td>
                            <td class="text-center">
                                <div>${acceptanceBadge}</div>
                                ${!isProjectCash ? `<div class="mt-1">${statusBadge}</div>` : ''}
                            </td>
                            <td class="text-center">${actionButtons}</td>
                        </tr>
                    `);
                });

                if (matchCount === 0) {
                    tbody.html('<tr><td colspan="9" class="text-center text-muted py-5"><i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i><div class="fw-semibold">No Cash Transfers Found</div><small>Try selecting a different filter or record a new transfer.</small></td></tr>');
                }

                $('#transfersCountBadge').text(`${matchCount} Entries`);
                $('#footTotalAmount').text('₹' + totalAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

                updatePagination();
            }

            function updatePagination() {
                const totalCount = filteredList.length;
                const totalPages = Math.ceil(totalCount / perPage) || 1;

                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                const rows = $('#cashTransfersTableBody tr');
                rows.hide();

                if (totalCount === 0) {
                    $('#tablePaginationContainer').addClass('d-none');
                    return;
                } else {
                    $('#tablePaginationContainer').removeClass('d-none');
                }

                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, totalCount);

                for (let i = startIndex; i < endIndex; i++) {
                    $(rows[i]).show();
                }

                $('#paginationSummary').html(`Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${totalCount}</strong> entries`);

                renderPaginationButtons(totalPages, currentPage);
            }

            function renderPaginationButtons(totalPages, current) {
                const nav = $('#paginationNav');
                nav.empty();

                const prevDisabled = current === 1 ? 'disabled' : '';
                nav.append(`
                    <li class="page-item ${prevDisabled}">
                        <button class="page-link" type="button" data-page="${current - 1}" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
</button>
                    </li>
                `);

                for (let i = 1; i <= totalPages; i++) {
                    const active = i === current ? 'active' : '';
                    nav.append(`
                        <li class="page-item ${active}">
                            <button class="page-link" type="button" data-page="${i}">${i}</button>
                        </li>
                    `);
                }

                const nextDisabled = current === totalPages ? 'disabled' : '';
                nav.append(`
                    <li class="page-item ${nextDisabled}">
                        <button class="page-link" type="button" data-page="${current + 1}" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
</button>
                    </li>
                `);
            }

            $(document).on('click', '#paginationNav .page-link', function (e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                    currentPage = parseInt(page);
                    updatePagination();
                }
            });

            $('#perPageSelect').on('change', function () {
                perPage = parseInt($(this).val()) || 10;
                currentPage = 1;
                updatePagination();
            });

            $('#filterDirection, #filterSite, #filterStatus').on('change', function () {
                currentPage = 1;
                renderTransfersTable();
            });

            $('#filterSearch').on('keyup input', function () {
                currentPage = 1;
                renderTransfersTable();
            });

            $('#btnResetFilters').on('click', function () {
                $('#filterDirection').val('all');
                $('#filterSite').val('all');
                $('#filterStatus').val('all');
                $('#filterSearch').val('');
                currentPage = 1;
                renderTransfersTable();
            });

            // Initial render
            renderTransfersTable();

            // Submit New Transfer Form
            $('#cashTransferForm').on('submit', function (e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);

                $('#btnSaveTransfer').prop('disabled', true);
                $('#saveTransferSpinner').removeClass('d-none');
                $('#saveTransferBtnText').text('Recording...');
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: "{{ route('cash-transfer.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#btnSaveTransfer').prop('disabled', false);
                        $('#saveTransferSpinner').addClass('d-none');
                        $('#saveTransferBtnText').text('Record Cash Transfer');

                        $('#cashTransferModal').modal('hide');
                        form.reset();
                        resetTransferFormDefaults();

                        showToast(response.message || 'Cash transfer recorded successfully!', 'success');

                        // Prepend newly created transfer to local dataset
                        if (response.data) {
                            rawTransfers.unshift(response.data);
                            renderTransfersTable();
                            updateKpis();
                        }
                    },
                    error: function (xhr) {
                        $('#btnSaveTransfer').prop('disabled', false);
                        $('#saveTransferSpinner').addClass('d-none');
                        $('#saveTransferBtnText').text('Record Cash Transfer');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errs = xhr.responseJSON.errors;
                            if (errs.project_site_id) {
                                $('#transferSiteId').addClass('is-invalid');
                                $('#errTransferSiteId').text(errs.project_site_id[0]);
                            }
                            if (errs.amount) {
                                $('#transferAmount').addClass('is-invalid');
                                $('#errTransferAmount').text(errs.amount[0]);
                            }
                            if (errs.handed_over_by) {
                                $('#transferHandedOverBy').addClass('is-invalid');
                                $('#errTransferHandedOverBy').text(errs.handed_over_by[0]);
                            }
                            if (errs.received_by) {
                                $('#transferReceivedBy').addClass('is-invalid');
                                $('#errTransferReceivedBy').text(errs.received_by[0]);
                            }
                        } else {
                            showToast(xhr.responseJSON?.message || 'Error recording cash transfer.', 'danger');
                        }
                    }
                });
            });

            // Open Settle Modal
            $(document).on('click', '.btn-settle-transfer', function () {
                const id = $(this).data('id');
                const site = $(this).data('site');
                const amt = $(this).data('amt');
                const date = $(this).data('date');
                const dir = $(this).data('dir');

                $('#settleTransferId').val(id);
                $('#settleSiteName').text(site);
                $('#settleAmount').text(amt);
                $('#settleDate').text(date);

                if (dir === 'office_to_site') {
                    $('#settleSyncHint').text('Cash returned from site to office will automatically be recorded as Outflow (- Outflow) in Site Cash and Inflow (+ Inflow) in Office Ledger.');
                    $('#settleNotes').val(`Office cash advance returned from Site on {{ date('d M Y') }}`);
                } else {
                    $('#settleSyncHint').text('Cash returned from office to site will automatically be recorded as Inflow (+ Inflow) in Site Cash and Outflow (- Outflow) in Office Ledger.');
                    $('#settleNotes').val(`Cash returned in full to Site Manager on {{ date('d M Y') }}`);
                }

                $('#settleTransferModal').modal('show');
            });

            // Submit Settle Form
            $('#settleTransferForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#settleTransferId').val();
                const notes = $('#settleNotes').val();

                $('#btnConfirmSettle').prop('disabled', true);
                $('#settleSpinner').removeClass('d-none');

                $.ajax({
                    url: `/cash-transfer/${id}/settle`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        settlement_notes: notes
                    },
                    success: function (res) {
                        $('#btnConfirmSettle').prop('disabled', false);
                        $('#settleSpinner').addClass('d-none');
                        $('#settleTransferModal').modal('hide');

                        showToast(res.message || 'Transfer settled successfully!', 'success');

                        // Update in local dataset
                        const target = rawTransfers.find(t => t.id == id);
                        if (target) {
                            target.settlement_status = 'settled';
                            target.settlement_notes = notes;
                        }
                        renderTransfersTable();
                        updateKpis();
                    },
                    error: function (xhr) {
                        $('#btnConfirmSettle').prop('disabled', false);
                        $('#settleSpinner').addClass('d-none');
                        showToast(xhr.responseJSON?.message || 'Error settling transfer.', 'danger');
                    }
                });
            });

            // Accept Cash Transfer
            $(document).on('click', '.btn-accept-transfer', function () {
                const id = $(this).data('id');
                const amt = $(this).data('amt');
                const from = $(this).data('from');
                const to = $(this).data('to');
                const site = $(this).data('site');

                $('#acceptTransferId').val(id);
                $('#acceptModalAmount').text(amt);
                $('#acceptModalNotes').val('');
                $('#acceptModalDetails').html(`
                    <div class="mb-1"><strong>From :</strong> ${from}</div>
                    <div class="mb-1"><strong>To :</strong> ${to}</div>
                    <div><strong>Project Site :</strong> ${site}</div>
                `);

                $('#acceptTransferModal').modal('show');
            });

            $('#acceptTransferForm').on('submit', function (e) {
                e.preventDefault();
                const id = $('#acceptTransferId').val();
                const notes = $('#acceptModalNotes').val();
                const btn = $('#btnConfirmAccept');
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Accepting...');

                $.ajax({
                    url: "{{ route('cash-transfer.accept', ['id' => '__TRANSFER_ID__']) }}".replace('__TRANSFER_ID__', encodeURIComponent(id)),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        acceptance_notes: notes
                    },
                    success: function (res) {
                        btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Confirm &amp; Accept');
                        $('#acceptTransferModal').modal('hide');
                        showToast(res.message || 'Cash transfer accepted successfully!', 'success');

                        // Update local transfer
                        const target = rawTransfers.find(t => t.id == id);
                        if (target) {
                            target.acceptance_status = 'accepted';
                            target.can_accept = false;
                            target.acceptance_notes = notes;
                        }
                        renderTransfersTable();
                        updateKpis();
                    },
                    error: function (xhr) {
                        btn.prop('disabled', false).html('<i class="bi bi-check-lg me-1"></i>Confirm &amp; Accept');
                        const err = xhr.responseJSON ? xhr.responseJSON.message : 'Error accepting cash transfer.';
                        showToast(err, 'danger');
                    }
                });
            });

            // View Details Modal
            $(document).on('click', '.btn-view-transfer', function () {
                const id = $(this).data('id');
                const t = rawTransfers.find(item => item.id == id);
                if (!t) return;

                const isOfficeToSite = t.transfer_direction === 'office_to_site';
                const isProjectCash = t.transfer_direction === 'project_cash';
                let dirText = '🏗️ Site &rarr; 🏢 Office';
                let dirBadgeClass = 'bg-warning text-dark';
                if (isProjectCash) {
                    dirText = '💰 Project Cash to Site ';
                    dirBadgeClass = 'bg-success text-white';
                } else if (isOfficeToSite) {
                    dirText = '🏢 Office &rarr; 🏗️ Site';
                    dirBadgeClass = 'bg-primary text-white';
                }
                const siteName = t.project_site ? t.project_site.name : 'N/A';

                const acceptStatusHtml = t.acceptance_status === 'accepted'
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle-fill me-1"></i>Accepted / Received</span>'
                    : '<span class="badge bg-warning-subtle text-dark border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>Pending Acceptance</span>';

                const statusHtml = (t.settlement_status === 'settled' || isProjectCash)
                    ? '<span class="badge bg-success">✓ Settled</span>'
                    : (isOfficeToSite ? '<span class="badge bg-success">✓ Completed</span>' : '<span class="badge bg-warning text-dark">⏳ Return Pending</span>');

                let html = `
                    <div class="mb-3 p-3 rounded bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                            <span class="badge ${dirBadgeClass} py-1 px-2">${dirText}</span>
                            <div>${acceptStatusHtml} ${!isProjectCash ? statusHtml : ''}</div>
</div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Amount:</span>
                            <strong class="text-dark fs-5">₹${Number(t.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong>
</div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Date &amp; Time:</span>
                            <span class="text-dark small">${formatDateDisplay(t.transfer_date)} ${t.transfer_time || ''}</span>
</div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Project Site:</span>
                            <span class="fw-semibold text-dark small">${escapeHtml(siteName)}</span>
</div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Handed Over By:</span>
                            <strong class="text-dark small">${escapeHtml(t.handed_over_by)}</strong>
</div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Received By:</span>
                            <strong class="text-dark small">${escapeHtml(t.received_by)}</strong>
</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Payment Mode:</span>
                            <span class="badge bg-white text-dark border">${escapeHtml(t.payment_mode || 'Cash')} ${t.payment_reference ? `(${escapeHtml(t.payment_reference)})` : ''}</span>
</div>
</div>

                    ${t.acceptance_notes ? `
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Acceptance Notes :</label>
                        <div class="p-2 rounded bg-success-subtle border border-success-subtle small text-success">${escapeHtml(t.acceptance_notes)}</div>
</div>
                    ` : ''}

                    ${t.purpose ? `
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Purpose / Reason:</label>
                        <div class="p-2 rounded bg-white border small text-dark">${escapeHtml(t.purpose)}</div>
</div>
                    ` : ''}

                    ${t.settlement_notes ? `
                    <div class="mb-2">
                        <label class="small text-muted fw-semibold">Settlement Notes:</label>
                        <div class="p-2 rounded bg-success-subtle border border-success-subtle small text-success">${escapeHtml(t.settlement_notes)}</div>
</div>
                    ` : ''}

                    ${t.receipt_image ? `
                    <div class="mt-3">
                        <label class="small text-muted fw-semibold mb-1">Voucher / Receipt Image:</label>
                        <div class="text-center p-2 border rounded bg-white">
                            <img src="/storage/${t.receipt_image}" class="img-fluid rounded" style="max-height: 250px;" alt="Receipt">
</div>
</div>
                    ` : ''}
                `;

                $('#viewTransferBody').html(html);
                $('#viewTransferModal').modal('show');
            });

            // Delete Transfer
            $(document).on('click', '.btn-delete-transfer', function () {
                const id = $(this).data('id');
                confirmDelete('Are you sure you want to delete this cash transfer? Linked daily ledger and site cash entries will also be removed.', function () {
                    $.ajax({
                        url: `/cash-transfer/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (res) {
                            showToast(res.message || 'Transfer deleted successfully!', 'success');
                            rawTransfers = rawTransfers.filter(t => t.id != id);
                            renderTransfersTable();
                            updateKpis();
                        },
                        error: function (xhr) {
                            showToast(xhr.responseJSON?.message || 'Error deleting transfer.', 'danger');
                        }
                    });
                });
            });

            // Update KPI cards dynamically
            function updateKpis() {
                let offToSite = 0;
                let projectCash = 0;
                let siteToOff = 0;
                let pending = 0;
                let settled = 0;

                rawTransfers.forEach(function (t) {
                    const amt = parseFloat(t.amount) || 0;
                    if (t.transfer_direction === 'office_to_site') {
                        offToSite += amt;
                    } else if (t.transfer_direction === 'project_cash') {
                        projectCash += amt;
                        settled += amt;
                    } else {
                        siteToOff += amt;
                        if (t.settlement_status === 'pending') {
                            pending += amt;
                        } else {
                            settled += amt;
                        }
                    }
                });

                $('#kpiOfficeToSite').text('₹' + offToSite.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiProjectCash').text('₹' + projectCash.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiSiteToOffice').text('₹' + siteToOff.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiPendingReturn').text('₹' + pending.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#kpiSettled').text('₹' + settled.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            }
        });
    </script>
@endpush
