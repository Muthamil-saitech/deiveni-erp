@extends('layouts.app')

@section('title', 'Admin Ledger')
@section('page_title', 'Admin Ledger (நிர்வாக பேரேடு)')

@push('styles')
    <style>
        .pagination .page-link {
            color: #1e3a8a;
            border-color: #e2e8f0;
            border-radius: 6px;
            padding: 0.32rem 0.65rem;
            font-size: 0.825rem;
            font-weight: 500;
        }

        .pagination .page-link:hover {
            background-color: #eff6ff;
            color: #1e40af;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            border-color: #1e3a8a;
            color: #ffffff;
        }

        .nav-tabs .nav-link {
            font-weight: 600;
            font-size: 0.875rem;
            color: #64748b;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.6rem 1rem;
        }

        .nav-tabs .nav-link.active {
            color: #1e40af;
            border-bottom: 2px solid #1e40af;
            background: transparent;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0">

        <!-- Navigation Tabs: Daily Ledger vs Admin Ledger -->
        <ul class="nav nav-tabs mb-3 border-bottom">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('ledger.index') }}">
                    <i class="bi bi-book-half me-1"></i> Daily Ledger (தினசரி பேரேடு)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('admin-ledger.index') }}">
                    <i class="bi bi-journal-bookmark-fill me-1 text-primary"></i> Admin Ledger (நிர்வாக பேரேடு)
                </a>
            </li>
        </ul>

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-journal-check text-primary me-2"></i>Admin Ledger (நிர்வாக வரவு - செலவு பேரேடு)
                    </h5>
                    <small class="text-muted">Client Payments (Income +) & Supplier Payments (Expense -)</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#adminLedgerModal"
                        id="openAddAdminLedgerBtn">
                        <i class="bi bi-plus-circle-fill me-1"></i> Add Admin Ledger Entry
                    </button>
                </div>
            </div>
        </div>

        <!-- Notebook Balance Summary Bar -->
        <div class="card-custom p-3 p-md-4 mb-4 text-white"
            style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
            <div class="row text-center g-3 align-items-center">
                <!-- Total Income -->
                <div class="col-6 col-md-4 border-end border-secondary border-opacity-25">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-arrow-down-left-circle text-success me-1"></i>Total Client Payments (Income Dr)
                    </div>
                    <h3 class="fw-bold text-success mb-0" id="summaryTotalIncome">
                        +₹{{ number_format($summary['total_income'] ?? 0, 2) }}
                    </h3>
                    <small class="ta-text text-white-50" style="font-size: 0.72rem;">மொத்த வாடிக்கையாளர் வரவு (+)</small>
                </div>

                <!-- Total Expense -->
                <div class="col-6 col-md-4 border-end border-secondary border-opacity-25">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-arrow-up-right-circle text-danger me-1"></i>Total Supplier Payments (Expense Cr)
                    </div>
                    <h3 class="fw-bold text-danger mb-0" id="summaryTotalExpense">
                        -₹{{ number_format($summary['total_expense'] ?? 0, 2) }}
                    </h3>
                    <small class="ta-text text-white-50" style="font-size: 0.72rem;">மொத்த சப்ளையர் பட்டுவாடா (-)</small>
                </div>

                <!-- Net Running Balance -->
                <div class="col-12 col-md-4">
                    <div class="text-white-50 small text-uppercase fw-semibold" style="font-size: 0.72rem;">
                        <i class="bi bi-wallet2 text-warning me-1"></i>Net Running Balance (இருப்பு)
                    </div>
                    @php
                        $net = (float) ($summary['net_balance'] ?? 0);
                    @endphp
                    <h2 class="fw-bold {{ $net >= 0 ? 'text-info' : 'text-warning' }} mb-0" id="summaryNetBalance">
                        {{ $net >= 0 ? '₹' . number_format($net, 2) : '-₹' . number_format(abs($net), 2) }}
                    </h2>
                    <small class="ta-text text-white-50" style="font-size: 0.72rem;">கையிருப்பு நிகர பேலன்ஸ்</small>
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="adminLedgerSearch"
                            placeholder="Search Client, Supplier, Project, Ref, Description...">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="categoryFilter">
                        <option value="">All Categories (அனைத்தும்)</option>
                        <option value="client_payment">Client Payment (+ Income)</option>
                        <option value="supplier_payment">Supplier Payment (- Expense)</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="dateFilter">
                        <option value="all" selected>All Dates (அனைத்து தேதிகள்)</option>
                        <option value="today">Today (இன்று)</option>
                        <option value="yesterday">Yesterday (நேற்று)</option>
                        <option value="custom">Pick Date (தேதி தேர்வு)...</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 d-none" id="customDateCol">
                    <input type="date" class="form-control form-control-sm" id="customDateInput"
                        value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="filterResetBtn"
                        title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
                <div class="col-12 col-md-3 text-md-end">
                    <span class="small text-muted">Showing <strong class="text-dark"
                            id="entryCountDisplay">{{ $entries->count() }}</strong> entries</span>
                </div>
            </div>
        </div>

        <!-- Admin Ledger Table -->
        <div class="card-custom overflow-hidden bg-white shadow-xs">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="adminLedgerTable">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Category</th>
                            <th>Flow</th>
                            <th>Party (Client / Supplier)</th>
                            <th>Project Site</th>
                            <th>Mode & Ref</th>
                            <th>Description</th>
                            <th class="text-end text-success">Income (+)</th>
                            <th class="text-end text-danger">Expense (-)</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center pe-3" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="adminLedgerTableBody">
                        @forelse($entries as $entry)
                            @php
                                $isIncome = ($entry->flow === 'income');
                                $bal = $runningBalances[$entry->id] ?? 0;
                            @endphp
                            <tr id="row-{{ $entry->id }}" data-category="{{ $entry->category }}"
                                data-date="{{ $entry->entry_date?->format('Y-m-d') }}">
                                <td class="ps-3 font-monospace small">
                                    {{ $entry->entry_date ? \Carbon\Carbon::parse($entry->entry_date)->format('d M Y') : '—' }}
                                </td>
                                <td>
                                    @if ($entry->category === 'client_payment')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-person-check me-1"></i>Client Payment (+)
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-truck me-1"></i>Supplier Payment (-)
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($isIncome)
                                        <span class="badge bg-success text-white" style="font-size:0.7rem;">Dr (வரவு)</span>
                                    @else
                                        <span class="badge bg-danger text-white" style="font-size:0.7rem;">Cr (செலவு)</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->category === 'client_payment')
                                        <strong>{{ $entry->client_name ?: ($entry->client?->name ?? '—') }}</strong>
                                    @else
                                        <strong>{{ $entry->supplier_name ?: ($entry->supplier?->name ?? '—') }}</strong>
                                    @endif
                                </td>
                                <td>
                                    @if ($entry->category === 'client_payment')
                                        <span class="text-dark"><i class="bi bi-building text-primary me-1"></i>{{ $entry->site_name ?: ($entry->projectSite?->name ?? '—') }}</span>
                                    @else
                                        @if ($entry->is_site_based)
                                            <span class="text-dark"><i class="bi bi-building text-primary me-1"></i>{{ $entry->site_name ?: ($entry->projectSite?->name ?? 'Site') }}</span>
                                            @if ($entry->purchase_order_no)
                                                <span class="badge bg-light text-dark border ms-1 font-monospace" style="font-size:0.7rem;">{{ $entry->purchase_order_no }}</span>
                                            @endif
                                        @else
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <i class="bi bi-collection me-1"></i>Total (All Sites)
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border me-1">{{ $entry->payment_mode }}</span>
                                    @if ($entry->payment_reference || $entry->cheque_no)
                                        <small class="font-monospace text-muted">{{ $entry->payment_reference ?: $entry->cheque_no }}</small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted d-inline-block text-truncate" style="max-width: 180px;" title="{{ $entry->description }}">
                                        {{ $entry->description ?: '—' }}
                                    </small>
                                </td>
                                <td class="text-end font-monospace fw-semibold text-success">
                                    {{ $isIncome ? '+₹' . number_format((float) $entry->amount, 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace fw-semibold text-danger">
                                    {{ ! $isIncome ? '-₹' . number_format((float) $entry->amount, 2) : '—' }}
                                </td>
                                <td class="text-end font-monospace fw-bold {{ $bal >= 0 ? 'text-primary' : 'text-danger' }}">
                                    {{ $bal >= 0 ? '₹' . number_format($bal, 2) : '-₹' . number_format(abs($bal), 2) }}
                                </td>
                                <td class="text-center pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin-ledger.show', $entry->encrypted_id) }}"
                                            class="btn btn-outline-primary btn-sm py-0 px-2" title="View details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm py-0 px-2 btn-delete-entry"
                                            data-id="{{ $entry->id }}" title="Delete entry">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyLedgerRow">
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                    <h6>No Admin Ledger entries found</h6>
                                    <p class="small mb-2">Click "Add Admin Ledger Entry" to record your first transaction.</p>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="adminLedgerFilterEmptyRow" class="d-none">
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-search fs-3 d-block mb-2 text-secondary"></i>
                                <h6>No matching transactions found</h6>
                                <p class="small mb-0">Try adjusting your search query or filter criteria.</p>
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
                            id="pageEnd">{{ min(10, count($entries)) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ count($entries) }}</strong> entries
                    </span>
                </div>
                <nav aria-label="Admin ledger table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
            </div>
        </div>

    </div>

    <!-- Record Admin Ledger Entry Modal -->
    <div class="modal fade" id="adminLedgerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-journal-plus me-2"></i>Add Admin Ledger Entry (நிர்வாக பேரேடு பதிவு)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="adminLedgerForm" enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Entry Date -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Entry Date (தேதி) <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="entryDate" name="entry_date"
                                    value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback" id="errEntryDate"></div>
                            </div>

                            <!-- Category Selection -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Payment Category (வகை) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="categorySelect" name="category" required>
                                    <option value="supplier_payment" selected>Supplier Payment (-) [Expense / செலவு]</option>
                                    <option value="client_payment">Client Payment (+) [Income / வரவு]</option>
                                </select>
                                <div class="invalid-feedback" id="errCategory"></div>
                            </div>

                            <!-- CLIENT PAYMENT SECTION -->
                            <div class="col-12" id="clientPaymentSection" style="display: none;">
                                <div class="p-3 bg-success-subtle bg-opacity-25 rounded-3 border border-success-subtle">
                                    <h6 class="fw-bold text-success mb-3">
                                        <i class="bi bi-person-check me-1"></i>Client Payment Details (வாடிக்கையாளர் வரவு விவரம்)
                                    </h6>
                                    <div class="row g-3">
                                        <!-- Select Client -->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold text-dark">Select Client (வாடிக்கையாளர்) <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="clientSelect" name="client_id">
                                                <option value="" disabled selected>-- Select Client --</option>
                                                @foreach ($clients as $client)
                                                    <option value="{{ $client->id }}" data-name="{{ $client->name }}">
                                                        {{ $client->name }} {{ $client->phone ? "({$client->phone})" : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errClientId"></div>
                                        </div>

                                        <!-- Select Project Site (under that client) -->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold text-dark">Select Project (திட்டம் / தளம்) <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="clientProjectSelect"
                                                name="project_site_id">
                                                <option value="" disabled selected>-- Select Client First --</option>
                                            </select>
                                            <div class="invalid-feedback" id="errClientProjectSiteId"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SUPPLIER PAYMENT SECTION -->
                            <div class="col-12" id="supplierPaymentSection">
                                <div class="p-3 bg-light rounded-3 border">
                                    <!-- Payment Basis Radio: Site Based vs Total - Only shown if supplier in master is NOT site-based -->
                                    <div class="mb-3" id="adminSupplierPaymentScopeBox" style="display: none;">
                                        <label class="form-label small fw-semibold text-dark mb-1">
                                            <i class="bi bi-diagram-3 text-primary me-1"></i>Payment Basis (பட்டுவாடா வகை) <span
                                                class="text-danger">*</span>
                                        </label>
                                        <div class="d-flex gap-4 pt-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="supplier_payment_scope"
                                                    id="adminScopeSiteBased" value="site_based" checked>
                                                <label class="form-check-label small fw-semibold" for="adminScopeSiteBased">
                                                    <i class="bi bi-building me-1 text-primary"></i>Site Based (தள வாரியாக)
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="supplier_payment_scope"
                                                    id="adminScopeTotal" value="total">
                                                <label class="form-check-label small fw-semibold text-primary"
                                                    for="adminScopeTotal">
                                                    <i class="bi bi-cash-stack me-1 text-success"></i>Total (மொத்தமாக)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <!-- Select Supplier -->
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold text-dark">Select Supplier (சப்ளையர்) <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="supplierSelect" name="supplier_id">
                                                <option value="" disabled selected>-- Select Supplier --</option>
                                                @foreach ($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                                        data-contact="{{ $supplier->contact_person }}"
                                                        data-phone="{{ $supplier->phone }}"
                                                        data-is-site-based="{{ $supplier->is_site_based ? '1' : '0' }}">
                                                        {{ $supplier->name }} {{ $supplier->contact_person ? "({$supplier->contact_person})" : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errSupplierId"></div>
                                        </div>

                                        <!-- Select Site (Shown ONLY when Site Based) -->
                                        <div class="col-12 col-md-6" id="supplierSiteGroup">
                                            <label class="form-label small fw-semibold text-dark">Project Site (திட்ட தளம்) <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select fw-semibold" id="supplierSiteSelect"
                                                name="project_site_id">
                                                <option value="" disabled selected>-- Select Project Site --</option>
                                                @foreach ($sites as $site)
                                                    <option value="{{ $site->id }}" data-name="{{ $site->name }}">
                                                        {{ $site->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="errSupplierProjectSiteId"></div>
                                        </div>

                                        <!-- Purchase Order Selection (Shown ONLY when Site Based) -->
                                        <div class="col-12" id="supplierPoGroup">
                                            <label class="form-label small fw-semibold text-dark mb-1">
                                                <i class="bi bi-file-earmark-text text-primary me-1"></i>Purchase Order (கொள்முதல் ஆணை - Optional)
                                            </label>
                                            <select class="form-select font-monospace" id="purchaseOrderSelect"
                                                name="purchase_order_no">
                                                <option value="" selected>-- Select Site & Supplier above --</option>
                                            </select>
                                            <div class="invalid-feedback" id="errPurchaseOrderNo"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Amount -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" id="amountLabel">Amount (ரூ.) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white fw-bold">₹</span>
                                    <input type="number" step="0.01" class="form-control fw-bold" id="adminAmount"
                                        name="amount" placeholder="e.g. 25000" required>
                                    <div class="invalid-feedback" id="errAmount"></div>
                                </div>
                            </div>

                            <!-- Payment Mode -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Payment Mode (செலுத்தும் முறை) <span
                                        class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="paymentModeSelect" name="payment_mode" required>
                                    <option value="Cash" selected>Cash (ரொக்கம்)</option>
                                    <option value="Cheque">Cheque (காசோலை)</option>
                                    <option value="UPI">UPI / GPay / PhonePe</option>
                                    <option value="Bank Transfer">Bank Transfer / NEFT / RTGS</option>
                                </select>
                                <div class="invalid-feedback" id="errPaymentMode"></div>
                            </div>

                            <!-- Cheque / Bank Extended Fields -->
                            <div class="col-12" id="bankChequeDetailsGroup" style="display: none;">
                                <div class="p-3 bg-light rounded-3 border">
                                    <div class="row g-2">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold">Cheque / Ref No</label>
                                            <input type="text" class="form-control font-monospace" id="chequeNo"
                                                name="cheque_no" placeholder="e.g. CHQ-991204">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold">Bank Name</label>
                                            <input type="text" class="form-control" id="bankName" name="bank_name"
                                                placeholder="e.g. SBI, HDFC, ICICI">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Description / Notes (குறிப்பு)</label>
                                <textarea class="form-control" id="adminDescription" name="description" rows="2"
                                    placeholder="Enter payment details, stage info, or notes..."></textarea>
                                <div class="invalid-feedback" id="errDescription"></div>
                            </div>

                            <!-- Receipt Image Upload -->
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Receipt / Voucher Document (Optional)</label>
                                <input type="file" class="form-control form-control-sm" id="adminReceiptImage"
                                    name="receipt_image" accept="image/*,application/pdf">
                                <div class="invalid-feedback" id="errReceiptImage"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold" id="saveAdminLedgerBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveSpinner"></span>
                            <i class="bi bi-check-circle me-1"></i> Save Entry
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
            // Category switch (Supplier Payment vs Client Payment)
            $('#categorySelect').on('change', function () {
                const category = $(this).val();
                if (category === 'client_payment') {
                    $('#clientPaymentSection').slideDown(150);
                    $('#supplierPaymentSection').slideUp(150);
                    $('#amountLabel').html('Income Amount (வரவு ரூ.) <span class="text-danger">*</span>');
                    // Reset supplier inputs
                    $('#supplierSelect').val('');
                    $('#supplierSiteSelect').val('');
                    $('#purchaseOrderSelect').empty().append('<option value="" selected>-- Select Site & Supplier above --</option>');
                } else {
                    $('#clientPaymentSection').slideUp(150);
                    $('#supplierPaymentSection').slideDown(150);
                    $('#amountLabel').html('Expense Amount (செலவு ரூ.) <span class="text-danger">*</span>');
                    // Reset client inputs
                    $('#clientSelect').val('');
                    $('#clientProjectSelect').empty().append('<option value="" disabled selected>-- Select Client First --</option>');
                }
            });

            // Supplier Payment Basis switch (Site Based vs Total)
            $('input[name="supplier_payment_scope"]').on('change', function () {
                syncSupplierScope();
            });

            function syncSupplierScope() {
                const isSiteBased = $('input[name="supplier_payment_scope"]:checked').val() === 'site_based';
                if (isSiteBased) {
                    $('#supplierSiteGroup').slideDown(150);
                    $('#supplierPoGroup').slideDown(150);
                    $('#adminAmount').val('');
                    $('#adminDescription').val('');
                    loadPurchaseOrders();
                } else {
                    // Total scope: hide site & PO
                    $('#supplierSiteGroup').slideUp(150);
                    $('#supplierPoGroup').slideUp(150);
                    $('#supplierSiteSelect').val('');
                    $('#purchaseOrderSelect').val('');
                    calculateTotalSupplierPending();
                }
            }

            // Client change -> Load projects for that client
            $('#clientSelect').on('change', function () {
                const clientId = $(this).val();
                const clientName = $(this).find('option:selected').data('name') || '';
                const $projectSelect = $('#clientProjectSelect');

                $projectSelect.empty().append('<option value="" disabled selected>Loading projects...</option>');

                if (!clientId) {
                    $projectSelect.empty().append('<option value="" disabled selected>-- Select Client First --</option>');
                    return;
                }

                $.ajax({
                    url: '{{ route("admin-ledger.client-projects") }}',
                    type: 'GET',
                    data: { client_id: clientId },
                    success: function (res) {
                        $projectSelect.empty();
                        if (res.success && res.projects && res.projects.length > 0) {
                            $projectSelect.append('<option value="" disabled selected>-- Select Project Site --</option>');
                            res.projects.forEach(function (proj) {
                                $projectSelect.append(`<option value="${proj.id}">${proj.name} (${proj.location || proj.status})</option>`);
                            });
                            if (res.projects.length === 1) {
                                $projectSelect.val(res.projects[0].id);
                            }
                        } else {
                            $projectSelect.append('<option value="" disabled selected>-- No projects found for this client --</option>');
                        }
                    },
                    error: function () {
                        $projectSelect.empty().append('<option value="" disabled selected>Failed to load projects</option>');
                    }
                });

                if (clientName) {
                    $('#adminDescription').val(`Client payment received from ${clientName}`);
                }
            });

            // Supplier change
            $('#supplierSelect').on('change', function () {
                const selected = $(this).find('option:selected');
                const supplierIsSiteBased = selected.data('is-site-based');
                if (supplierIsSiteBased !== undefined && supplierIsSiteBased !== null && supplierIsSiteBased !== '') {
                    const isSb = (String(supplierIsSiteBased) === '1' || supplierIsSiteBased === true);
                    if (isSb) {
                        $('#adminSupplierPaymentScopeBox').hide();
                        $('#adminScopeSiteBased').prop('checked', true);
                    } else {
                        $('#adminSupplierPaymentScopeBox').slideDown(150);
                        $('#adminScopeTotal').prop('checked', true);
                    }
                    syncSupplierPaymentScope();
                } else {
                    $('#adminSupplierPaymentScopeBox').hide();
                    const isSiteBased = $('input[name="supplier_payment_scope"]:checked').val() === 'site_based';
                    if (!isSiteBased) {
                        calculateTotalSupplierPending();
                    } else {
                        loadPurchaseOrders();
                    }
                }
            });

            // Supplier site change
            $('#supplierSiteSelect').on('change', function () {
                loadPurchaseOrders();
            });

            // Load POs for Site Based
            function loadPurchaseOrders() {
                const supplierId = $('#supplierSelect').val();
                const supplierName = $('#supplierSelect option:selected').data('name') || '';
                const siteId = $('#supplierSiteSelect').val();
                const $poSelect = $('#purchaseOrderSelect');

                if (!supplierId || !siteId) {
                    $poSelect.empty().append('<option value="" selected>-- Select Site & Supplier above --</option>');
                    return;
                }

                $poSelect.empty().append('<option value="" selected>Loading purchase orders...</option>');

                $.ajax({
                    url: '{{ route("admin-ledger.purchase-orders") }}',
                    type: 'GET',
                    data: {
                        supplier_id: supplierId,
                        supplier_name: supplierName,
                        project_site_id: siteId,
                        scope: 'site_based',
                        is_site_based: 1
                    },
                    success: function (res) {
                        $poSelect.empty();
                        if (res.success && res.orders && res.orders.length > 0) {
                            $poSelect.append('<option value="" selected>-- Select Purchase Order (Optional) --</option>');
                            res.orders.forEach(function (po) {
                                const amt = parseFloat(po.total_amount) || 0;
                                const formatted = amt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                $poSelect.append(`<option value="${po.po_number}" data-amount="${amt}">${po.po_number} - ₹${formatted} (${po.status})</option>`);
                            });
                        } else {
                            $poSelect.append('<option value="" selected>-- No Pending POs for this site --</option>');
                        }
                    }
                });
            }

            // PO selection -> auto fill amount & description
            $('#purchaseOrderSelect').on('change', function () {
                const selected = $(this).find('option:selected');
                const amt = selected.data('amount');
                const poNo = $(this).val();
                const suppName = $('#supplierSelect option:selected').data('name') || '';

                if (amt) {
                    $('#adminAmount').val(parseFloat(amt).toFixed(2));
                }
                if (poNo) {
                    $('#adminDescription').val(`Supplier payment to ${suppName} against PO: ${poNo}`);
                }
            });

            // Calculate Total Supplier Pending across all sites
            function calculateTotalSupplierPending() {
                const supplierId = $('#supplierSelect').val();
                const supplierName = $('#supplierSelect option:selected').data('name') || '';

                if (!supplierId) {
                    $('#adminAmount').val('');
                    return;
                }

                $.ajax({
                    url: '{{ route("admin-ledger.purchase-orders") }}',
                    type: 'GET',
                    data: {
                        supplier_id: supplierId,
                        supplier_name: supplierName,
                        scope: 'total',
                        is_site_based: 0
                    },
                    success: function (res) {
                        if (res.success) {
                            const total = parseFloat(res.total_pending_amount) || 0;
                            const count = parseInt(res.pending_count) || 0;
                            const formatted = total.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                            if (total > 0) {
                                $('#adminAmount').val(total.toFixed(2));
                                $('#adminDescription').val(`Total Supplier Payment to ${supplierName}: ₹${formatted} (${count} pending POs across all sites)`);
                                showToast(`Total pending ₹${formatted} across ${count} POs loaded.`, 'info');
                            } else {
                                $('#adminAmount').val('');
                                $('#adminDescription').val(supplierName ? `Total Supplier Payment to ${supplierName}` : 'Total Supplier Payment');
                                showToast(`No pending POs found for ${supplierName}.`, 'info');
                            }
                        }
                    }
                });
            }

            // Payment Mode Change
            $('#paymentModeSelect').on('change', function () {
                const mode = $(this).val();
                if (mode === 'Cheque' || mode === 'Bank Transfer') {
                    $('#bankChequeDetailsGroup').slideDown(150);
                } else {
                    $('#bankChequeDetailsGroup').slideUp(150);
                }
            });

            // Submit Admin Ledger Form
            $('#adminLedgerForm').on('submit', function (e) {
                e.preventDefault();

                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').hide();

                const formData = new FormData(this);
                const $btn = $('#saveAdminLedgerBtn');
                const $spinner = $('#saveSpinner');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("admin-ledger.store") }}',
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
                            $('#adminLedgerModal').modal('hide');
                            showToast(res.message || 'Entry recorded successfully!', 'success');
                            setTimeout(() => window.location.reload(), 700);
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            for (const [key, msgs] of Object.entries(errors)) {
                                if (key === 'entry_date') {
                                    $('#entryDate').addClass('is-invalid');
                                    $('#errEntryDate').text(msgs[0]).show();
                                } else if (key === 'category') {
                                    $('#categorySelect').addClass('is-invalid');
                                    $('#errCategory').text(msgs[0]).show();
                                } else if (key === 'client_id') {
                                    $('#clientSelect').addClass('is-invalid');
                                    $('#errClientId').text(msgs[0]).show();
                                } else if (key === 'project_site_id') {
                                    if ($('#categorySelect').val() === 'client_payment') {
                                        $('#clientProjectSelect').addClass('is-invalid');
                                        $('#errClientProjectSiteId').text(msgs[0]).show();
                                    } else {
                                        $('#supplierSiteSelect').addClass('is-invalid');
                                        $('#errSupplierProjectSiteId').text(msgs[0]).show();
                                    }
                                } else if (key === 'supplier_id') {
                                    $('#supplierSelect').addClass('is-invalid');
                                    $('#errSupplierId').text(msgs[0]).show();
                                } else if (key === 'amount') {
                                    $('#adminAmount').addClass('is-invalid');
                                    $('#errAmount').text(msgs[0]).show();
                                } else if (key === 'payment_mode') {
                                    $('#paymentModeSelect').addClass('is-invalid');
                                    $('#errPaymentMode').text(msgs[0]).show();
                                } else {
                                    showToast(msgs[0], 'danger');
                                }
                            }
                        } else {
                            showToast('An error occurred. Please try again.', 'danger');
                        }
                    }
                });
            });

            // Modal Reset on Close
            $('#adminLedgerModal').on('hidden.bs.modal', function () {
                $('#adminLedgerForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('').hide();
                $('#categorySelect').val('supplier_payment').trigger('change');
                $('#adminSupplierPaymentScopeBox').hide();
                $('#adminScopeSiteBased').prop('checked', true).trigger('change');
                $('#bankChequeDetailsGroup').hide();
                $('#entryDate').val('{{ date("Y-m-d") }}');
            });

            // Delete entry
            $(document).on('click', '.btn-delete-entry', function () {
                const id = $(this).data('id');
                if (!confirm('Are you sure you want to delete this admin ledger entry?')) {
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
                            $(`#row-${id}`).fadeOut(300, function () { $(this).remove(); });
                            setTimeout(() => window.location.reload(), 600);
                        }
                    },
                    error: function () {
                        showToast('Failed to delete entry.', 'danger');
                    }
                });
            });

            // Pagination & Filter state
            let currentPage = 1;
            let perPage = 10;

            function getMatchingRows() {
                const search = ($('#adminLedgerSearch').val() || '').toLowerCase().trim();
                const category = $('#categoryFilter').val();
                const dateMode = $('#dateFilter').val();
                const customDate = $('#customDateInput').val();

                const todayStr = '{{ date("Y-m-d") }}';
                const yesterdayStr = '{{ date("Y-m-d", strtotime("-1 day")) }}';

                const matchingRows = [];

                $('#adminLedgerTableBody tr').each(function () {
                    const id = $(this).attr('id');
                    if (id === 'emptyLedgerRow' || id === 'adminLedgerFilterEmptyRow') return;

                    const rowText = $(this).text().toLowerCase();
                    const rowCategory = $(this).data('category') || '';
                    const rowDate = $(this).data('date') || '';

                    let matchSearch = (!search || rowText.includes(search));
                    let matchCategory = (!category || rowCategory === category);
                    let matchDate = true;

                    if (dateMode === 'today') {
                        matchDate = (rowDate === todayStr);
                    } else if (dateMode === 'yesterday') {
                        matchDate = (rowDate === yesterdayStr);
                    } else if (dateMode === 'custom' && customDate) {
                        matchDate = (rowDate === customDate);
                    }

                    if (matchSearch && matchCategory && matchDate) {
                        matchingRows.push(this);
                    }
                });

                return matchingRows;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const filteredCount = matchingRows.length;
                const totalCount = $('#adminLedgerTableBody tr:not(#emptyLedgerRow):not(#adminLedgerFilterEmptyRow)').length;

                const totalPages = Math.max(1, Math.ceil(filteredCount / perPage));
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows
                $('#adminLedgerTableBody tr:not(#emptyLedgerRow):not(#adminLedgerFilterEmptyRow)').hide();

                if (totalCount === 0) {
                    $('#adminLedgerFilterEmptyRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#entryCountDisplay').text('0');
                    return;
                }

                if (filteredCount === 0) {
                    $('#adminLedgerFilterEmptyRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#entryCountDisplay').text(`0 of ${totalCount}`);
                    return;
                } else {
                    $('#adminLedgerFilterEmptyRow').addClass('d-none');
                    $('#tablePaginationContainer').removeClass('d-none');
                }

                // Slice visible rows for current page
                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, filteredCount);

                for (let i = startIndex; i < endIndex; i++) {
                    $(matchingRows[i]).show();
                }

                // Update summary text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' entries';
                $('#paginationSummary').html(summaryHtml);

                if (filteredCount !== totalCount) {
                    $('#entryCountDisplay').text(`${filteredCount} of ${totalCount}`);
                } else {
                    $('#entryCountDisplay').text(filteredCount);
                }

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

            function filterTable() {
                currentPage = 1;
                updatePagination();
            }

            $('#adminLedgerSearch').on('input', filterTable);
            $('#categoryFilter').on('change', filterTable);
            $('#dateFilter').on('change', function () {
                if ($(this).val() === 'custom') {
                    $('#customDateCol').removeClass('d-none');
                } else {
                    $('#customDateCol').addClass('d-none');
                }
                filterTable();
            });
            $('#customDateInput').on('change', filterTable);

            $('#filterResetBtn').on('click', function () {
                $('#adminLedgerSearch').val('');
                $('#categoryFilter').val('');
                $('#dateFilter').val('all');
                $('#customDateCol').addClass('d-none');
                filterTable();
            });

            // Initial pagination setup
            updatePagination();
        });
    </script>
@endpush
