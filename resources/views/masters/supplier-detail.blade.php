@extends('layouts.app')

@section('title', $supplier->name . ' - Supplier Statement & Purchase Summary')
@section('page_title', 'Supplier Statement (சப்ளையர் கணக்கு & கொள்முதல் விவரம்)')

@push('styles')
    <style>
        .card-custom {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .table th {
            font-size: 0.75rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .table td {
            font-size: 0.85rem;
        }

        .filter-preset-btn.active {
            background-color: #0d6efd !important;
            color: #ffffff !important;
            border-color: #0d6efd !important;
        }

        /* Dedicated Print Styles */
        .print-only {
            display: none !important;
        }

        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }

        @media print {
            body {
                background: #ffffff !important;
                color: #0f172a !important;
                font-size: 9pt !important;
            }

            .no-print,
            #sidebar,
            .top-navbar,
            .mobile-bottom-nav,
            .breadcrumb,
            .btn,
            .btn-close,
            footer {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            .card-custom {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                margin-bottom: 12px !important;
                padding: 10px !important;
                page-break-inside: avoid !important;
            }

            .table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .table th,
            .table td {
                border: 1px solid #cbd5e1 !important;
                padding: 5px 8px !important;
                font-size: 8.5pt !important;
            }

            .table-light {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .badge {
                border: 1px solid #64748b !important;
                color: #0f172a !important;
                background: transparent !important;
                font-weight: 600 !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Screen Header & Navigation (Hidden in Print) --}}
    <div class="card-custom p-3 mb-3 bg-white no-print">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center shadow-sm" title="Back to Suppliers">
                    <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
                </a>
                <div class="vr d-none d-md-block"></div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-bold mb-0 text-dark">{{ $supplier->name }}</h5>
                        @if ($supplier->status === 'Active')
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Active (செயலில்)</span>
                        @else
                            <span class="badge bg-secondary-subtle text-muted border border-secondary-subtle">Inactive</span>
                        @endif
                    </div>
                    <div class="text-muted small d-flex flex-wrap align-items-center gap-3 mt-1" style="font-size: 0.78rem;">
                        <span><i class="bi bi-person me-1"></i>{{ $supplier->contact_person ?: '—' }}</span>
                        @if($supplier->phone)
                            <span><i class="bi bi-telephone me-1"></i><a href="tel:{{ $supplier->phone }}" class="text-decoration-none text-muted">{{ $supplier->phone }}</a></span>
                        @endif
                        @if($supplier->gst_number)
                            <span class="badge bg-light text-dark border">GST: {{ $supplier->gst_number }}</span>
                        @endif
                        @if($supplier->address)
                            <span class="text-truncate" style="max-width: 250px;"><i class="bi bi-geo-alt me-1"></i>{{ $supplier->address }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($supplier->phone)
                    <a href="tel:{{ $supplier->phone }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center shadow-sm">
                        <i class="bi bi-telephone-fill me-1"></i> Call Supplier
                    </a>
                @endif
                <button onclick="window.print()" class="btn btn-primary btn-sm d-inline-flex align-items-center shadow-sm px-3">
                    <i class="bi bi-printer me-1"></i> Print Statement (அச்சிடுக)
                </button>
            </div>
        </div>
    </div>

    {{-- Official Print Header (Only visible when printing) --}}
    <div class="print-only mb-3">
        <div class="border-bottom pb-2 mb-2 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-0 text-primary">DEIVENI CONSTRUCTIONS &amp; DEVELOPERS</h4>
                <div class="small text-muted">Construction &amp; Project Management ERP System</div>
            </div>
            <div class="text-end small">
                <div class="fw-bold text-dark">SUPPLIER STATEMENT OF ACCOUNT</div>
                <div class="text-muted">Date: {{ now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">
                <div class="p-2 border rounded">
                    <div class="small fw-bold text-primary">SUPPLIER INFORMATION:</div>
                    <div class="fw-bold fs-6">{{ $supplier->name }}</div>
                    <div class="small text-muted">Contact: {{ $supplier->contact_person ?: 'N/A' }} | Phone: {{ $supplier->phone }}</div>
                    @if($supplier->gst_number)
                        <div class="small text-muted">GSTIN: <strong>{{ $supplier->gst_number }}</strong></div>
                    @endif
                    @if($supplier->address)
                        <div class="small text-muted">Address: {{ $supplier->address }}</div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 border rounded">
                    <div class="small fw-bold text-success">FINANCIAL POSITION:</div>
                    <div class="d-flex justify-content-between small"><span>Total Purchased:</span> <strong>₹{{ number_format($financialStats['total_purchased'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between small"><span>Total Paid:</span> <strong>₹{{ number_format($financialStats['total_paid'], 2) }}</strong></div>
                    <div class="d-flex justify-content-between small border-top pt-1 mt-1 text-danger"><span>Balance Outstanding:</span> <strong>₹{{ number_format($financialStats['balance_payable'], 2) }}</strong></div>
                </div>
            </div>
        </div>
    </div>

    {{-- COMMON SUMMARY KPI CARDS (mela common card ah total amount evlo & balance evlo kudukanum) --}}
    <div class="row g-3 mb-4" id="kpiCardsRow">
        {{-- Total Purchased Amount --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size:0.75rem;">Total Purchased (மொத்த கொள்முதல்)</span>
                        <h4 class="fw-bold mb-0 text-primary mt-1">₹{{ number_format($financialStats['total_purchased'], 2) }}</h4>
                        <small class="text-muted">{{ $financialStats['orders_count'] }} {{ Str::plural('Order', $financialStats['orders_count']) }}</small>
                    </div>
                    <div class="bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="bi bi-cart-check-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Paid Amount --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-custom p-3 bg-white h-100 border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size:0.75rem;">Total Paid (செலுத்தப்பட்ட தொகை)</span>
                        <h4 class="fw-bold mb-0 text-success mt-1">₹{{ number_format($financialStats['total_paid'], 2) }}</h4>
                        <small class="text-muted">Settled to Supplier</small>
                    </div>
                    <div class="bg-success-subtle text-success p-2 rounded-3">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Balance Payable / Outstanding --}}
        <div class="col-12 col-sm-6 col-xl-3">
            @php
                $isPending = $financialStats['balance_payable'] > 0;
            @endphp
            <div class="card-custom p-3 bg-white h-100 border-start border-4 {{ $isPending ? 'border-danger' : 'border-success' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size:0.75rem;">Balance to Pay (மீதமுள்ள பாக்கி)</span>
                        <h4 class="fw-bold mb-0 {{ $isPending ? 'text-danger' : 'text-success' }} mt-1">
                            ₹{{ number_format($financialStats['balance_payable'], 2) }}
                        </h4>
                        @if($isPending)
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1" style="font-size:0.68rem;">Pending Payment (பாக்கி உள்ளது)</span>
                        @else
                            <span class="badge bg-success-subtle text-success border border-success-subtle mt-1" style="font-size:0.68rem;">All Cleared (செலுத்தப்பட்டது)</span>
                        @endif
                    </div>
                    <div class="{{ $isPending ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} p-2 rounded-3">
                        <i class="bi bi-hourglass-split fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sites Supplied --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="text-muted small fw-semibold d-block text-uppercase" style="font-size:0.75rem;">Projects Supplied (வழங்கிய திட்டங்கள்)</span>
                        <h4 class="fw-bold mb-0 text-dark mt-1">{{ $financialStats['sites_count'] }} Projects (தளங்கள்)</h4>
                        <small class="text-muted">{{ $financialStats['materials_count'] }} Material Varieties (பொருட்கள்)</small>
                    </div>
                    <div class="bg-info-subtle text-info p-2 rounded-3">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLE 1: ALL-TIME COMMON STOCK & MATERIALS PURCHASED SUMMARY --}}
    {{-- (oru table la ithu vara enna enna stock evlo vanngirukom and athoda value common ah) --}}
    <div class="card-custom mb-4 overflow-hidden" id="commonStockCard">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-boxes text-primary"></i>
                <span>1. Total Stock &amp; Materials Purchased (இதுவரை வாங்கிய மொத்த இருப்பு &amp; மதிப்பு - Common Stock)</span>
            </div>
            <span class="badge bg-light text-dark border">{{ count($totalMaterialsSummary) }} Materials</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="commonStockTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-2 text-muted" style="width: 35px;">#</th>
                        <th class="py-2 text-muted">Material Name (பொருள் பெயர்)</th>
                        <th class="py-2 text-center text-muted">Total Stock Purchased (வாங்கிய மொத்த அளவு)</th>
                        <th class="py-2 text-end text-muted">Average Rate (சராசரி விலை / அலகு)</th>
                        <th class="py-2 text-end text-muted">Total Value (மொத்த மதிப்பு)</th>
                        <th class="pe-3 py-2 text-muted">Projects Supplied (சென்றடைந்த தளங்கள்)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($totalMaterialsSummary as $mIdx => $mSummary)
                        <tr>
                            <td class="ps-3 text-muted text-center font-monospace">{{ str_pad($mIdx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $mSummary['material_name'] }}</div>
                                @if(!empty($mSummary['material_name_ta']))
                                    <div class="ta-text text-muted" style="font-size: 0.72rem;">{{ $mSummary['material_name_ta'] }}</div>
                                @endif
                            </td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    {{ number_format($mSummary['total_quantity'], 2) }} {{ $mSummary['unit'] }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-secondary">
                                ₹{{ number_format($mSummary['average_price'], 2) }}
                                <span class="text-muted" style="font-size: 0.7rem;">/ {{ $mSummary['unit'] }}</span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ₹{{ number_format($mSummary['total_amount'], 2) }}
                            </td>
                            <td class="pe-3">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($mSummary['sites'] as $sName)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal" style="font-size:0.7rem;">
                                            <i class="bi bi-building me-1"></i>{{ $sName }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-box-seam fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                No material purchase records found for this supplier.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($totalMaterialsSummary) > 0)
                    <tfoot class="table-light border-top">
                        <tr>
                            <td colspan="2" class="ps-3 fw-bold text-dark">Total Purchased Stock (மொத்தம்)</td>
                            <td class="text-center fw-bold text-dark font-monospace">{{ number_format($financialStats['total_quantity_supplied'], 2) }} items</td>
                            <td class="text-end text-muted small">Grand Total Value:</td>
                            <td class="text-end fw-bold text-success fs-6">₹{{ number_format($financialStats['total_purchased'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- TABLE 2: PROJECT-BASED SUPPLY BREAKDOWN WITH PROJECT DROPDOWN & DATE FILTER --}}
    {{-- (next oru table athula project dropdown vacchi athula antha project ku ena ena materil evlo supply pannirukom with date filter) --}}
    <div class="card-custom mb-4 overflow-hidden" id="projectSuppliesCard">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-diagram-3-fill text-success"></i>
                <span>2. Project-wise Material Supply Details (திட்டம் வாரியாக சப்ளை செய்த பொருட்கள் &amp; விலை விவரம்)</span>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle" id="projectFilterStatusBadge">
                All Projects
            </span>
        </div>

        {{-- Project Dropdown & Date Filter Controls (Hidden in Print) --}}
        <div class="p-3 bg-light border-bottom no-print">
            <div class="row g-2 align-items-center">
                {{-- Project Dropdown --}}
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="bi bi-building me-1 text-primary"></i>Select Project (திட்ட களம் தேர்வு செய்க):
                    </label>
                    <select id="projectFilterSelect" class="form-select form-select-sm border-primary">
                        <option value="all" selected>-- All Projects (அனைத்து திட்ட களங்கள்) --</option>
                        @foreach($projectSitesList as $pSite)
                            <option value="{{ $pSite['id'] }}">
                                {{ $pSite['name'] }} (₹{{ number_format($pSite['total_amount'], 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- From Date --}}
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">From Date (தேதி முதல்):</label>
                    <input type="date" id="tableFilterFromDate" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
                </div>

                {{-- To Date --}}
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">To Date (தேதி வரை):</label>
                    <input type="date" id="tableFilterToDate" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                </div>

                {{-- Action & Presets --}}
                <div class="col-12 col-md-4 d-flex flex-wrap align-items-end gap-1 mt-auto">
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 filter-preset-btn active" data-preset="all" style="font-size:0.75rem;">
                        All
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 filter-preset-btn" data-preset="this-month" style="font-size:0.75rem;">
                        This Month
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 filter-preset-btn" data-preset="last-30" style="font-size:0.75rem;">
                        Last 30 Days
                    </button>
                    <button type="button" id="resetTableFiltersBtn" class="btn btn-sm btn-outline-secondary py-1 px-2 d-inline-flex align-items-center gap-1 ms-auto" style="font-size:0.75rem;">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Supplies Table --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="projectSuppliesTable" style="font-size:0.83rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-2 text-muted" style="width: 35px;">#</th>
                        <th class="py-2 text-muted">Date &amp; PO # (தேதி &amp; ஆணை எண்)</th>
                        <th class="py-2 text-muted">Project Site (திட்ட களம்)</th>
                        <th class="py-2 text-muted">Material Name (பொருள்)</th>
                        <th class="py-2 text-center text-muted">Quantity (அளவு)</th>
                        <th class="py-2 text-end text-muted">Unit Price (விலை / அலகு)</th>
                        <th class="py-2 text-end text-muted">Total Price (மொத்த விலை)</th>
                        <th class="pe-3 py-2 text-end text-muted no-print" style="width: 90px;">Action</th>
                    </tr>
                </thead>
                <tbody id="projectSuppliesTbody">
                    @forelse($allPoItems as $itemIndex => $lineItem)
                        <tr class="supply-item-row"
                            data-site-id="{{ $lineItem['project_site_id'] }}"
                            data-site-name="{{ strtolower($lineItem['site_name']) }}"
                            data-date="{{ $lineItem['order_date_raw'] }}"
                            data-qty="{{ $lineItem['quantity'] }}"
                            data-total="{{ $lineItem['total_price'] }}">
                            <td class="ps-3 text-muted text-center font-monospace row-counter">
                                {{ str_pad($itemIndex + 1, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    <a href="{{ route('purchases.show', $lineItem['po_encrypted_id']) }}" class="text-decoration-none text-primary" target="_blank">
                                        {{ $lineItem['po_number'] }}
                                    </a>
                                </div>
                                <div class="text-muted small" style="font-size:0.72rem;">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $lineItem['order_date'] }}
                                </div>
                            </td>
                            <td>
                                <span class="fw-bold text-dark">{{ $lineItem['site_name'] }}</span>
                                @if(!empty($lineItem['site_location']))
                                    <div class="text-muted small" style="font-size:0.7rem;">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $lineItem['site_location'] }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold text-dark">{{ $lineItem['material_name'] }}</span>
                                @if(!empty($lineItem['material_name_ta']))
                                    <div class="ta-text text-muted" style="font-size:0.7rem;">{{ $lineItem['material_name_ta'] }}</div>
                                @endif
                            </td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    {{ number_format($lineItem['quantity'], 2) }} {{ $lineItem['unit'] }}
                                </span>
                            </td>
                            <td class="text-end fw-semibold text-secondary">
                                ₹{{ number_format($lineItem['unit_price'], 2) }}
                                <span class="text-muted" style="font-size:0.7rem;">/ {{ $lineItem['unit'] }}</span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ₹{{ number_format($lineItem['total_price'], 2) }}
                            </td>
                            <td class="pe-3 text-end no-print">
                                <a href="{{ route('purchases.show', $lineItem['po_encrypted_id']) }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.72rem;" target="_blank">
                                    <i class="bi bi-eye me-1"></i>View PO
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr id="emptySuppliesRow">
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-cart-x fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                No material supply records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light border-top" id="suppliesTfoot">
                    <tr>
                        <td colspan="4" class="ps-3 fw-bold text-dark" id="footSelectedLabel">
                            Total for Selected Project / Date (மொத்தம்)
                        </td>
                        <td class="text-center fw-bold text-dark font-monospace" id="footFilteredQty">
                            {{ number_format($financialStats['total_quantity_supplied'], 2) }} items
                        </td>
                        <td class="text-end text-muted small">Selected Total:</td>
                        <td class="text-end fw-bold text-success fs-6" id="footFilteredTotal">
                            ₹{{ number_format($financialStats['total_purchased'], 2) }}
                        </td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Print Footer: Signatures (Only in Print) --}}
    <div class="print-only mt-5 pt-4">
        <div class="row text-center">
            <div class="col-4">
                <div class="border-top pt-2 small fw-bold">Prepared By (தயாரித்தவர்)</div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2 small fw-bold">Verified By (சரிபார்த்தவர்)</div>
            </div>
            <div class="col-4">
                <div class="border-top pt-2 small fw-bold">Authorized Signatory (அங்கீகரிக்கப்பட்டவர்)</div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        // Quick Date Presets
        $('.filter-preset-btn').on('click', function () {
            $('.filter-preset-btn').removeClass('active');
            $(this).addClass('active');

            const preset = $(this).data('preset');
            const now = new Date();
            let from = '';
            let to = '';

            const formatDate = (d) => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (preset === 'all') {
                from = '';
                to = '';
            } else if (preset === 'this-month') {
                from = formatDate(new Date(now.getFullYear(), now.getMonth(), 1));
                to = formatDate(new Date(now.getFullYear(), now.getMonth() + 1, 0));
            } else if (preset === 'last-30') {
                const past = new Date();
                past.setDate(past.getDate() - 30);
                from = formatDate(past);
                to = formatDate(now);
            }

            $('#tableFilterFromDate').val(from);
            $('#tableFilterToDate').val(to);
            applyDynamicTableFilter();
        });

        // Trigger filter on project dropdown change or date input change
        $('#projectFilterSelect, #tableFilterFromDate, #tableFilterToDate').on('change', function () {
            applyDynamicTableFilter();
        });

        // Reset Filter
        $('#resetTableFiltersBtn').on('click', function () {
            $('#projectFilterSelect').val('all');
            $('#tableFilterFromDate').val('');
            $('#tableFilterToDate').val('');
            $('.filter-preset-btn').removeClass('active');
            $('.filter-preset-btn[data-preset="all"]').addClass('active');
            applyDynamicTableFilter();
        });

        function applyDynamicTableFilter() {
            const selectedSiteId = $('#projectFilterSelect').val();
            const from = $('#tableFilterFromDate').val();
            const to = $('#tableFilterToDate').val();

            let visibleCount = 0;
            let totalQty = 0;
            let totalAmt = 0;

            $('.supply-item-row').each(function () {
                const rowSiteId = String($(this).data('site-id'));
                const rowDate = $(this).data('date'); // YYYY-MM-DD
                let matchesSite = (selectedSiteId === 'all' || rowSiteId === String(selectedSiteId));
                let matchesDate = true;

                if (from && rowDate && rowDate < from) {
                    matchesDate = false;
                }
                if (to && rowDate && rowDate > to) {
                    matchesDate = false;
                }

                const visible = matchesSite && matchesDate;
                $(this).toggle(visible);

                if (visible) {
                    visibleCount++;
                    totalQty += parseFloat($(this).data('qty')) || 0;
                    totalAmt += parseFloat($(this).data('total')) || 0;
                    $(this).find('.row-counter').text(String(visibleCount).padStart(2, '0'));
                }
            });

            // Update badge & label
            const siteText = selectedSiteId === 'all' ? 'All Projects' : $('#projectFilterSelect option:selected').text().split('(')[0].trim();
            $('#projectFilterStatusBadge').text(siteText + (visibleCount > 0 ? ` (${visibleCount} items)` : ' (0 items)'));
            $('#footSelectedLabel').text(`Total for ${siteText} (மொத்தம்)`);

            // Update footer totals
            $('#footFilteredQty').text(totalQty.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' items');
            $('#footFilteredTotal').text('₹' + totalAmt.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

            // Empty state handling
            if (visibleCount === 0 && $('.supply-item-row').length > 0) {
                if ($('#noMatchingRow').length === 0) {
                    $('#projectSuppliesTbody').append('<tr id="noMatchingRow"><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-filter fs-2 d-block mb-1 opacity-50"></i>No materials supplied matching the selected project / date filter.</td></tr>');
                } else {
                    $('#noMatchingRow').show();
                }
                $('#suppliesTfoot').hide();
            } else {
                $('#noMatchingRow').remove();
                $('#suppliesTfoot').show();
            }
        }
    });
</script>
@endpush
