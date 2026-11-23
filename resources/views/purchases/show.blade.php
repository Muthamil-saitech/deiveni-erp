@extends('layouts.app')

@section('title', 'Purchase Order #' . $id . ' Details')
@section('page_title', 'Purchase Order Details (கொள்முதல் ஆணை முழு விவரம்)')

@push('styles')
    <style>
        /* Base Screen Styles */
        .print-document {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .po-header-box {
            border-bottom: 2px dashed #cbd5e1;
            padding-bottom: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .po-meta-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.85rem 1rem;
        }

        .party-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.85rem 1rem;
            height: 100%;
        }

        .notes-box {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .signature-line {
            border-top: 1px solid #64748b;
            margin: 40px 15px 0 15px;
            padding-top: 6px;
            text-align: center;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
        }

        .table-custom th {
            font-size: 0.825rem;
            letter-spacing: 0.02em;
        }

        .table-custom td {
            font-size: 0.875rem;
        }

        /* Clean, Exact Single-Page Print Layout */
        @page {
            size: A4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }

        @media print {
            *,
            *::before,
            *::after {
                box-sizing: border-box !important;
            }

            html,
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 8.5pt !important;
                line-height: 1.25 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Flatten flex layout parents from layouts.app to standard block */
            body > div.d-flex,
            body > div.d-flex > div,
            #main-content,
            #purchaseDetailApp,
            #poContentBox {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
                float: none !important;
            }

            /* Hide everything except the purchase order slip */
            #sidebar,
            .sidebar-backdrop,
            #top-navbar,
            .top-navbar,
            .mobile-bottom-nav,
            .toast-container,
            .no-print,
            .stat-card-row,
            .btn,
            header,
            footer,
            nav,
            .breadcrumb,
            .alert-dismissible {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* Single-page container constraints */
            .print-document {
                border: 1.5px solid #1e3a8a !important;
                border-radius: 4px !important;
                padding: 10px 14px !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
                background: #ffffff !important;
            }

            /* Header Section */
            .po-header-box {
                border-bottom: 1.5px dashed #475569 !important;
                padding-bottom: 6px !important;
                margin-bottom: 6px !important;
            }

            .po-print-row {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: flex-start !important;
                gap: 8px !important;
            }

            .po-print-col-company {
                width: 58% !important;
                flex: 0 0 58% !important;
                max-width: 58% !important;
            }

            .po-print-col-meta {
                width: 42% !important;
                flex: 0 0 42% !important;
                max-width: 42% !important;
            }

            .po-meta-card {
                background-color: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                padding: 5px 8px !important;
                border-radius: 4px !important;
            }

            .po-meta-card .meta-item {
                font-size: 8pt !important;
                padding: 1px 0 !important;
            }

            /* Vendor & Site 2-Column Cards */
            .party-cards-row {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                gap: 8px !important;
                margin-bottom: 6px !important;
            }

            .party-cards-row > div {
                width: 50% !important;
                flex: 0 0 50% !important;
                max-width: 50% !important;
            }

            .party-card {
                background-color: #f8fafc !important;
                border: 1px solid #cbd5e1 !important;
                padding: 5px 8px !important;
                border-radius: 4px !important;
                height: 100% !important;
            }

            .party-card h6 {
                font-size: 8.5pt !important;
                margin-bottom: 2px !important;
            }

            .party-card .small {
                font-size: 7.5pt !important;
                line-height: 1.2 !important;
            }

            /* Ordered Items Table */
            .table-responsive {
                overflow: visible !important;
                display: block !important;
                width: 100% !important;
                margin-bottom: 4px !important;
            }

            .table-custom {
                width: 100% !important;
                table-layout: fixed !important;
                border-collapse: collapse !important;
                margin-bottom: 0 !important;
            }

            .table-custom th,
            .table-custom td {
                border: 1px solid #94a3b8 !important;
                padding: 3px 5px !important;
                font-size: 8pt !important;
                line-height: 1.2 !important;
                color: #0f172a !important;
            }

            .table-custom thead th {
                background-color: #f1f5f9 !important;
                font-weight: 700 !important;
                color: #0f172a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-custom tfoot td {
                font-size: 8pt !important;
                padding: 2.5px 5px !important;
            }

            .table-custom tfoot tr.table-primary {
                background-color: #eff6ff !important;
                border-top: 1.5px solid #1e3a8a !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-custom tfoot tr.table-primary td {
                font-weight: 800 !important;
                color: #1e3a8a !important;
                font-size: 8.5pt !important;
            }

            /* Amount in words & Notes */
            .amount-words-row {
                font-size: 8pt !important;
                margin-top: 2px !important;
                margin-bottom: 4px !important;
            }

            .notes-box {
                border: 1px solid #cbd5e1 !important;
                background-color: #f8fafc !important;
                padding: 4px 8px !important;
                margin-bottom: 4px !important;
                border-radius: 4px !important;
            }

            .notes-box .small,
            .notes-box p {
                font-size: 7.5pt !important;
                line-height: 1.2 !important;
            }

            /* Signatures */
            .po-signatures {
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                margin-top: 14px !important;
                padding-top: 4px !important;
            }

            .po-signatures > div {
                flex: 1 !important;
            }

            .signature-line {
                border-top: 1px solid #475569 !important;
                margin: 20px 8px 0 8px !important;
                padding-top: 3px !important;
                text-align: center !important;
                font-size: 7.5pt !important;
                font-weight: 600 !important;
                color: #1e293b !important;
            }

            .badge {
                border: 1px solid #475569 !important;
                color: #0f172a !important;
                background: #ffffff !important;
                font-weight: 600 !important;
                padding: 1px 4px !important;
                font-size: 7pt !important;
            }

            .d-print-flex {
                display: flex !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0" id="purchaseDetailApp">

        <!-- Top Navigation / Action Bar -->
        <div class="card-custom p-3 mb-4 bg-white no-print">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('purchases.index') }}"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        title="Back to Material Purchases">
                        <i class="bi bi-arrow-left me-1"></i> Back to Purchases
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="fw-bold mb-0 text-dark" id="poHeaderTitle">Purchase Order</h5>
                            <span class="badge" id="poHeaderStatusBadge">-</span>
                        </div>
                        <small class="text-muted">
                            PO / Bill No: <span class="font-monospace text-primary fw-semibold"
                                id="poHeaderBillNo">#{{ $id }}</span> &bull;
                            <i class="bi bi-calendar3 me-1"></i><span id="poHeaderDate">-</span> &bull;
                            <i class="bi bi-geo-alt me-1"></i><span id="poHeaderSite">-</span>
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-deiveni btn-sm d-inline-flex align-items-center"
                        onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Purchase Order (அச்சிடுக)
                    </button>
                </div>
            </div>
        </div>

        <!-- Record Not Found State (Hidden by default) -->
        <div class="card-custom p-5 text-center bg-white mb-4 no-print" id="poNotFoundBox" style="display: none;">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-1 d-block mb-3"></i>
            <h5 class="fw-bold text-dark">Purchase Record Not Found</h5>
            <p class="text-muted small mb-3">The requested purchase order (#{{ $id }}) could not be located in recorded
                purchases.</p>
            <a href="{{ route('purchases.index') }}" class="btn btn-deiveni btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Return to Purchases List
            </a>
        </div>

        <!-- Main Detail Content -->
        <div id="poContentBox">

            <!-- Quick Stat Cards (Screen only) -->
            <div class="row g-3 mb-4 stat-card-row no-print">
                <!-- 1. Total Bill Amount -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Total Bill Amount</span>
                                <h4 class="fw-bold mb-0 text-success mt-1" id="statTotalAmount">₹0</h4>
                            </div>
                            <div class="bg-success-subtle text-success p-2 rounded-3">
                                <i class="bi bi-currency-rupee fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Order Status -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Order Status (நிலை)</span>
                                <div class="mt-1" id="statStatusBadge">
                                    <span class="badge bg-secondary">Loading...</span>
                                </div>
                            </div>
                            <div class="bg-primary-subtle text-primary p-2 rounded-3">
                                <i class="bi bi-patch-check-fill fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Destination Site -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Project Site (தளம்)</span>
                                <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" id="statSiteName"
                                    style="max-width: 170px;">-</h6>
                            </div>
                            <div class="bg-info-subtle text-info p-2 rounded-3">
                                <i class="bi bi-buildings fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Supplier / Vendor -->
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card-custom p-3 bg-white h-100 border-start border-4 border-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold d-block text-uppercase">Supplier (சப்ளையர்)</span>
                                <h6 class="fw-bold mb-0 text-dark mt-1 text-truncate" id="statSupplierName"
                                    style="max-width: 170px;">-</h6>
                            </div>
                            <div class="bg-warning-subtle text-warning p-2 rounded-3">
                                <i class="bi bi-truck fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Printable Official Purchase Order Document -->
            <div class="card-custom p-4 p-md-5 bg-white print-document mb-4">

                <!-- Formal Document Header -->
                <div class="po-header-box">
                    <div class="row align-items-start po-print-row gy-3">
                        <div class="col-12 col-md-7 po-print-col-company">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-primary text-white px-2 py-1"><i
                                        class="bi bi-building me-1"></i>CONSTRUCTION</span>
                                <span class="text-muted small fw-semibold text-uppercase">Material Inward & Purchase
                                    Order</span>
                            </div>
                            <h3 class="fw-extrabold text-primary mb-1 tracking-tight">DEIVENI CONSTRUCTIONS</h3>
                            <p class="text-muted small mb-1 lh-sm">
                                Engineering, Civil Construction & Infrastructure Development<br>
                                Paarai Pati & North West Street, Madurai, Tamil Nadu - 625001<br>
                                <i class="bi bi-telephone-fill me-1"></i> +91 94441 00000 &bull; <i
                                    class="bi bi-envelope-fill me-1"></i> contact@deiveniconstructions.com
                            </p>
                            <div class="text-muted small"><strong>GSTIN:</strong> <span
                                    class="font-monospace fw-semibold text-dark">33ABCDE1234F1Z5</span></div>
                        </div>

                        <div class="col-12 col-md-5 po-print-col-meta text-md-end">
                            <div class="po-meta-card text-start d-inline-block w-100" style="max-width: 320px;">
                                <div class="fw-bold text-dark fs-6 text-uppercase border-bottom pb-1 mb-2 text-primary d-flex justify-content-between align-items-center">
                                    <span>Purchase Order Details</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-print-none" style="font-size: 0.65rem;">PO SLIP</span>
                                </div>
                                <div class="small mb-1 meta-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">PO / Bill No:</span>
                                    <strong class="text-primary font-monospace" id="slipBillNo">{{ $id }}</strong>
                                </div>
                                <div class="small mb-1 meta-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Order Date:</span>
                                    <strong class="text-dark" id="slipDate">-</strong>
                                </div>
                                <div class="small mb-1 meta-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Order Status:</span>
                                    <span id="slipStatusBadge"><span class="badge bg-secondary">-</span></span>
                                </div>
                                <div class="small meta-item d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Payment Type:</span>
                                    <strong class="text-dark" id="slipPaymentType">Credit / Site Voucher</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vendor & Destination Site Two Columns -->
                <div class="row g-3 mb-3 party-cards-row">
                    <div class="col-12 col-md-6">
                        <div class="party-card">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                                <span class="text-uppercase small fw-bold text-primary">
                                    <i class="bi bi-truck me-1"></i> Vendor / Supplier Information (சப்ளையர்)
                                </span>
                                <span class="badge bg-secondary-subtle text-dark border">Supplier</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" id="slipSupplierName">-</h6>
                            <div class="small text-muted mb-1" id="slipSupplierContact">
                                <i class="bi bi-telephone me-1"></i> <span id="slipSupplierPhone">-</span>
                            </div>
                            <div class="small text-muted" id="slipSupplierAddress">
                                <i class="bi bi-geo-alt me-1"></i> Verified Material Supplier Partner
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="party-card">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                                <span class="text-uppercase small fw-bold text-primary">
                                    <i class="bi bi-geo-alt-fill me-1"></i> Destination Project Site (டெலிவரி தளம்)
                                </span>
                                <span class="badge bg-info-subtle text-primary border border-info-subtle">Site Delivery</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1" id="slipSiteName">-</h6>
                            <div class="small text-muted mb-1">
                                <i class="bi bi-building me-1"></i> <span id="slipSiteAddress">Site Inward Location</span>
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-check2-circle text-success me-1"></i> Inward Entry Verified & Logged
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return / Cancellation Notice Box (Shown dynamically when needed) -->
                <div class="alert alert-warning border border-warning p-2 mb-2" id="slipReasonBox" style="display: none;">
                    <div class="fw-bold small text-dark mb-1" id="slipReasonHeading"></div>
                    <div class="small text-muted" id="slipReasonText"></div>
                </div>

                <!-- Itemized Materials Table -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark mb-0 small text-uppercase">
                            <i class="bi bi-list-check text-primary me-1"></i> Ordered Materials & Items Breakdown (பொருட்கள் விவரம்)
                        </h6>
                        <span class="badge bg-light text-dark border" id="slipItemsCountBadge">0 Items</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-custom align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;" class="text-center">#</th>
                                    <th style="width: 43%;">Material / Item Description (பொருள் பெயர்)</th>
                                    <th class="text-center" style="width: 12%;">Quantity (அளவு)</th>
                                    <th class="text-center" style="width: 12%;">Unit</th>
                                    <th class="text-end" style="width: 13%;">Unit Rate (ரூ.)</th>
                                    <th class="text-end" style="width: 15%;">Amount (மொத்தம் ரூ.)</th>
                                </tr>
                            </thead>
                            <tbody id="slipItemsTableBody">
                                <!-- Inward items injected here -->
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Subtotal:</td>
                                    <td class="text-end fw-bold font-monospace" id="slipSubtotal">₹0</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="text-end fw-semibold">Transport / Handling / Taxes:</td>
                                    <td class="text-end text-muted font-monospace">₹0</td>
                                </tr>
                                <tr class="table-primary border-primary">
                                    <td colspan="5" class="text-end fw-extrabold fs-6">Total Bill Amount (மொத்த தொகை):</td>
                                    <td class="text-end fw-extrabold fs-5 text-primary font-monospace" id="slipGrandTotal">
                                        ₹0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1 amount-words-row">
                        <small class="text-muted">
                            <strong>Amount in Words (தொகை வார்த்தைகளில்):</strong> <span class="fst-italic text-dark fw-semibold" id="slipAmountWords">Zero Rupees
                                Only</span>
                        </small>
                    </div>
                </div>

                <!-- Notes & Remarks Section -->
                <div class="notes-box mb-3">
                    <h6 class="fw-bold text-dark mb-1 small text-uppercase">
                        <i class="bi bi-card-text text-primary me-1"></i> Inward Notes & Delivery Remarks (குறிப்புகள்)
                    </h6>
                    <p class="text-muted small mb-0" id="slipNotesText">
                        No specific delivery notes recorded for this purchase order.
                    </p>
                </div>

                <!-- Signatures & Verification Section -->
                <div class="row pt-2 text-center po-signatures">
                    <div class="col-4">
                        <div class="signature-line">
                            <div class="fw-bold">Prepared By / Store Incharge</div>
                            <div class="text-muted small" style="font-size: 7.5pt;">(தயாரித்தவர் / ஸ்டோர்)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="signature-line">
                            <div class="fw-bold">Received By / Site Supervisor</div>
                            <div class="text-muted small" style="font-size: 7.5pt;">(தள மேற்பார்வையாளர்)</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="signature-line">
                            <div class="fw-bold">Authorized Signatory</div>
                            <div class="text-muted small" style="font-size: 7.5pt;">(அங்கீகரிக்கப்பட்ட கையொப்பம்)</div>
                        </div>
                    </div>
                </div>

                <!-- Official Footer (Print Only) -->
                <div class="d-none d-print-flex justify-content-between align-items-center mt-2 pt-1 border-top text-muted" style="font-size: 7.5pt;">
                    <span>DEIVENI CONSTRUCTIONS &bull; Official Purchase Order Slip</span>
                    <span>Printed on: {{ now()->format('d/m/Y h:i A') }}</span>
                    <span>Page 1 of 1</span>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            const PURCHASES_STORAGE_KEY = 'deiveni_purchases';
            const targetId = "{{ $id }}";

            // Standard preset purchases dictionary in case localStorage is empty
            const DEFAULT_PRESET_PURCHASES = [
                {
                    id: 'INV-2026-881',
                    numericId: '1',
                    billNo: 'INV-2026-881',
                    poNumber: 'INV-2026-881',
                    date: '2026-10-10',
                    site: 'Mr. Jayakumar Sir Site',
                    siteAddress: 'Paarai Pati, Madurai',
                    supplier: 'Chettinad Cement Agency',
                    supplierPhone: '94442 22333',
                    material: 'Cement (OPC 53 Grade)',
                    items: [
                        { material: 'Cement (OPC 53 Grade)', qty: 200, unit: 'Bags', rate: 410, total: 82000 }
                    ],
                    total: 82000,
                    status: 'Order Completed',
                    statusReason: '',
                    notes: '200 Bags delivered directly to Mr. Jayakumar Sir Site (Paarai Pati) for foundation slab casting.'
                },
                {
                    id: 'DC-2026-441',
                    numericId: '2',
                    billNo: 'DC-2026-441',
                    poNumber: 'DC-2026-441',
                    date: '2026-10-12',
                    site: 'Mr. Jayakumar Sir Site',
                    siteAddress: 'Paarai Pati, Madurai',
                    supplier: 'Sri Balaji Blue Metals & M-Sand',
                    supplierPhone: '94441 11222',
                    material: 'M-Sand (Manufactured Sand)',
                    items: [
                        { material: 'M-Sand (Manufactured Sand)', qty: 4, unit: 'Load', rate: 8500, total: 34000 }
                    ],
                    total: 34000,
                    status: 'Partial Received',
                    statusReason: '',
                    notes: '4 Loads M-Sand for brick masonry and plastering mortar at Paarai Pati site.'
                },
                {
                    id: 'PO-2026-101',
                    numericId: '3',
                    billNo: 'PO-2026-101',
                    poNumber: 'PO-2026-101',
                    date: '2026-10-15',
                    site: 'Mr. Jayakumar Sir Site',
                    siteAddress: 'Paarai Pati, Madurai',
                    supplier: 'SSS Electric Stores',
                    supplierPhone: '98421 77889',
                    material: 'Demolition Breaker, Electrical Conduit Pipes',
                    items: [
                        { material: 'Demolition Breaker', qty: 2, unit: 'Hours', rate: 1000, total: 2000 },
                        { material: 'Electrical Conduit Pipes', qty: 50, unit: 'Nos', rate: 95, total: 4750 }
                    ],
                    total: 6750,
                    status: 'Ordered',
                    statusReason: '',
                    notes: 'Breaker rental for rock clearing and conduit pipes delivered to site.'
                },
                {
                    id: 'DC-2026-442',
                    numericId: '4',
                    billNo: 'DC-2026-442',
                    poNumber: 'DC-2026-442',
                    date: '2026-10-18',
                    site: 'Mr. Parthasarathi Sir Site',
                    siteAddress: 'North West Street, Madurai',
                    supplier: 'Sri Balaji Blue Metals & M-Sand',
                    supplierPhone: '94441 11222',
                    material: '20mm Blue Metal Aggregate',
                    items: [
                        { material: '20mm Blue Metal Aggregate', qty: 5, unit: 'Load', rate: 6200, total: 31000 }
                    ],
                    total: 31000,
                    status: 'Order Return',
                    statusReason: 'Quality check failed: irregular blue metal size',
                    notes: '5 Loads 20mm blue metal aggregate for pillar casting at North West Street site.'
                },
                {
                    id: 'INV-2026-505',
                    numericId: '5',
                    billNo: 'INV-2026-505',
                    poNumber: 'INV-2026-505',
                    date: '2026-10-20',
                    site: 'Mr. Parthasarathi Sir Site',
                    siteAddress: 'North West Street, Madurai',
                    supplier: 'Sunder Hardwares',
                    supplierPhone: '98422 33445',
                    material: 'Power Drill & Tools',
                    items: [
                        { material: 'Power Drill & Tools', qty: 2, unit: 'Nos', rate: 2500, total: 5000 }
                    ],
                    total: 5000,
                    status: 'Order Canceled',
                    statusReason: 'Site requirement rescheduled',
                    notes: 'Power drill machines and accessories for structural work at Parthasarathi site.'
                }
            ];

            function getPurchasesList() {
                try {
                    const raw = localStorage.getItem(PURCHASES_STORAGE_KEY);
                    if (raw) {
                        const parsed = JSON.parse(raw);
                        if (Array.isArray(parsed) && parsed.length > 0) {
                            return parsed;
                        }
                    }
                } catch (e) {
                    console.warn('Error reading from localStorage', e);
                }
                return DEFAULT_PRESET_PURCHASES;
            }

            const dbPurchaseRaw = @json($purchase ?? null);
            let purchase = null;

            if (dbPurchaseRaw) {
                const items = (dbPurchaseRaw.items || []).map(i => ({
                    material: i.material_name,
                    qty: parseFloat(i.quantity) || 1,
                    unit: i.unit || 'Nos',
                    rate: parseFloat(i.unit_price) || 0,
                    total: parseFloat(i.total_price) || (parseFloat(i.quantity) * parseFloat(i.unit_price))
                }));

                purchase = {
                    id: dbPurchaseRaw.po_number,
                    numericId: String(dbPurchaseRaw.id),
                    billNo: dbPurchaseRaw.po_number,
                    poNumber: dbPurchaseRaw.po_number,
                    date: dbPurchaseRaw.order_date ? dbPurchaseRaw.order_date.substring(0, 10) : '',
                    site: dbPurchaseRaw.project_site ? dbPurchaseRaw.project_site.name : (dbPurchaseRaw.site_name || 'Project Site'),
                    siteAddress: dbPurchaseRaw.project_site ? (dbPurchaseRaw.project_site.location || '') : '',
                    supplier: dbPurchaseRaw.supplier_name,
                    supplierPhone: dbPurchaseRaw.supplier ? dbPurchaseRaw.supplier.phone : '',
                    material: items.map(i => i.material).join(', '),
                    items: items,
                    total: parseFloat(dbPurchaseRaw.total_amount) || 0,
                    status: dbPurchaseRaw.status,
                    statusReason: dbPurchaseRaw.status_reason || '',
                    notes: dbPurchaseRaw.notes || ''
                };
            }

            if (!purchase) {
                const purchases = getPurchasesList();
                purchase = purchases.find(p =>
                    String(p.id).toLowerCase() === String(targetId).toLowerCase() ||
                    String(p.billNo).toLowerCase() === String(targetId).toLowerCase() ||
                    String(p.poNumber).toLowerCase() === String(targetId).toLowerCase() ||
                    String(p.numericId) === String(targetId)
                ) || DEFAULT_PRESET_PURCHASES.find(p =>
                    String(p.id).toLowerCase() === String(targetId).toLowerCase() ||
                    String(p.billNo).toLowerCase() === String(targetId).toLowerCase() ||
                    String(p.numericId) === String(targetId)
                );
            }

            if (!purchase) {
                $('#poNotFoundBox').show();
                $('#poContentBox').hide();
                $('#poHeaderStatusBadge').hide();
                return;
            }

            // Helper: Number to Indian Currency Words
            function numberToWordsINR(amount) {
                const words = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                    'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

                function convert(num) {
                    if (num < 20) return words[num];
                    if (num < 100) return tens[Math.floor(num / 10)] + (num % 10 !== 0 ? ' ' + words[num % 10] : '');
                    if (num < 1000) return words[Math.floor(num / 100)] + ' Hundred' + (num % 100 !== 0 ? ' and ' + convert(num % 100) : '');
                    if (num < 100000) return convert(Math.floor(num / 1000)) + ' Thousand' + (num % 1000 !== 0 ? ' ' + convert(num % 1000) : '');
                    if (num < 10000000) return convert(Math.floor(num / 100000)) + ' Lakh' + (num % 100000 !== 0 ? ' ' + convert(num % 100000) : '');
                    return convert(Math.floor(num / 10000000)) + ' Crore' + (num % 10000000 !== 0 ? ' ' + convert(num % 10000000) : '');
                }

                const amt = Math.round(amount);
                if (amt === 0) return 'Zero Rupees Only';
                return convert(amt) + ' Rupees Only';
            }

            // Badge styling helper
            function getStatusBadge(status) {
                status = status || 'Order Completed';
                switch (status) {
                    case 'Ordered':
                        return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock me-1"></i>Ordered (ஆர்டர் செய்யப்பட்டது)</span>';
                    case 'Partial Received':
                        return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-pie-chart me-1"></i>Partial Received (பகுதி பெறப்பட்டது)</span>';
                    case 'Order Return':
                        return '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle px-2 py-1"><i class="bi bi-arrow-return-left me-1"></i>Order Return (திரும்பியது)</span>';
                    case 'Order Canceled':
                        return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Order Canceled (ரத்து செய்யப்பட்டது)</span>';
                    case 'Order Completed':
                    default:
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Order Completed (முழுவதும் பெறப்பட்டது)</span>';
                }
            }

            // Helper: Format Date to "16 Nov 2026"
            function formatDate(dateStr) {
                if (!dateStr) return 'N/A';
                const parts = String(dateStr).split('T')[0].split(' ')[0].split('-');
                const d = parts.length === 3 ? new Date(parts[0], parts[1] - 1, parts[2]) : new Date(dateStr);
                return !isNaN(d.getTime())
                    ? d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                    : dateStr;
            }

            const billNoVal = purchase.billNo || purchase.poNumber || purchase.id;
            const dateVal = formatDate(purchase.date);
            const siteVal = purchase.site || 'Site';
            const supplierVal = purchase.supplier || 'Supplier';
            const statusVal = purchase.status || 'Order Completed';
            const statusReasonVal = purchase.statusReason || '';
            const totalVal = parseFloat(purchase.total) || 0;
            const notesVal = purchase.notes || 'Inward materials checked and accepted.';

            // Populate Top Header Bar
            $('#poHeaderTitle').text(`Purchase Order #${billNoVal}`);
            $('#poHeaderStatusBadge').replaceWith(getStatusBadge(statusVal));
            $('#poHeaderBillNo').text(billNoVal);
            $('#poHeaderDate').text(dateVal);
            $('#poHeaderSite').text(siteVal);

            // Populate Quick Stat Cards
            $('#statTotalAmount').text('₹' + totalVal.toLocaleString('en-IN'));
            $('#statStatusBadge').html(getStatusBadge(statusVal));
            $('#statSiteName').text(siteVal);
            $('#statSupplierName').text(supplierVal);

            // Populate Printable Document
            $('#slipBillNo').text(billNoVal);
            $('#slipDate').text(dateVal);
            $('#slipStatusBadge').html(getStatusBadge(statusVal));
            $('#slipSiteName').text(siteVal);
            $('#slipSiteAddress').text(purchase.siteAddress || `${siteVal}, Madurai`);
            $('#slipSupplierName').text(supplierVal);
            $('#slipSupplierPhone').text(purchase.supplierPhone || '94441 11222');
            $('#slipNotesText').text(notesVal);
            $('#slipSubtotal').text('₹' + totalVal.toLocaleString('en-IN'));
            $('#slipGrandTotal').text('₹' + totalVal.toLocaleString('en-IN'));
            $('#slipAmountWords').text(numberToWordsINR(totalVal));

            // Handle Return or Cancellation Notice
            if ((statusVal === 'Order Return' || statusVal === 'Order Canceled') && statusReasonVal) {
                if (statusVal === 'Order Return') {
                    $('#slipReasonHeading').html('<i class="bi bi-arrow-return-left me-1"></i> Order Return Notice (பொருட்கள் திருப்பியதற்கான குறிப்பு)');
                } else {
                    $('#slipReasonHeading').html('<i class="bi bi-x-circle me-1"></i> Order Cancellation Notice (ஆர்டர் ரத்து செய்யப்பட்டதற்கான குறிப்பு)');
                }
                $('#slipReasonText').text(statusReasonVal);
                $('#slipReasonBox').show();
            } else {
                $('#slipReasonBox').hide();
            }

            // Populate Items Breakdown Table
            let items = purchase.items;
            if (typeof items === 'string') {
                try { items = JSON.parse(items); } catch (e) { items = []; }
            }
            if (!items || !Array.isArray(items) || items.length === 0) {
                items = [{
                    material: purchase.material || 'Material Item',
                    qty: purchase.qty || 1,
                    unit: purchase.unit || 'Unit',
                    rate: purchase.rate || totalVal,
                    total: totalVal
                }];
            }

            $('#slipItemsCountBadge').text(`${items.length} Item${items.length === 1 ? '' : 's'}`);
            const tbody = $('#slipItemsTableBody').empty();

            items.forEach(function (item, idx) {
                const rateNum = parseFloat(item.rate) || 0;
                const totalNum = item.total ? parseFloat(item.total) : ((parseFloat(item.qty) || 0) * rateNum);
                const row = `
                            <tr>
                                <td class="text-center text-muted fw-semibold">${String(idx + 1).padStart(2, '0')}</td>
                                <td>
                                    <div class="fw-bold text-dark">${item.material}</div>
                                </td>
                                <td class="text-center fw-semibold text-dark">${item.qty}</td>
                                <td class="text-center text-muted">${item.unit}</td>
                                <td class="text-end font-monospace">₹${rateNum.toLocaleString('en-IN')}</td>
                                <td class="text-end fw-bold text-dark font-monospace">₹${totalNum.toLocaleString('en-IN')}</td>
                            </tr>
                        `;
                tbody.append(row);
            });
        });
    </script>
@endpush