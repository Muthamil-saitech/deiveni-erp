@extends('layouts.app')

@section('title', 'Supplier Purchases - ' . $supplierName . ' | ' . $site->name)
@section('page_title', 'Supplier Purchase Details ')

@push('styles')
    <style>
        .card-custom {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
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

        @media print {
            .no-print {
                display: none !important;
            }
            .card-custom {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
            }
            body {
                background: #ffffff !important;
            }
        }
    </style>
@endpush

@section('content')
<div class="container-fluid px-3 py-3">

    {{-- Breadcrumb & Toolbar --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sites.index') }}" class="text-decoration-none">Project Sites</a></li>
                <li class="breadcrumb-item"><a href="{{ route('sites.show', $site->encrypted_id) }}" class="text-decoration-none">{{ $site->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $supplierName }}</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('sites.show', $site->encrypted_id) }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-arrow-left"></i> Back to Site 
</a>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1 shadow-sm">
                <i class="bi bi-printer"></i> Print 
</button>
</div>
</div>

    {{-- Header Banner & Identity Cards --}}
    <div class="row g-3 mb-4">
        {{-- Site Card --}}
        <div class="col-12 col-md-6">
            <div class="card-custom p-3 h-100 border-start border-4 border-primary">
                <div class="d-flex align-items-center gap-2 text-primary small fw-semibold mb-1">
                    <i class="bi bi-building"></i> PROJECT SITE 
</div>
                <h5 class="fw-bold text-dark mb-1">{{ $site->name }}</h5>
                <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                    @if($site->location)
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $site->location }}</span>
                    @endif
                    @if($site->client?->name)
                        <span><i class="bi bi-person me-1"></i>Client: <strong>{{ $site->client->name }}</strong></span>
                    @endif
                    @if($site->work_type)
                        <span class="badge bg-light text-dark border">{{ $site->work_type }}</span>
                    @endif
</div>
</div>
</div>

        {{-- Supplier Card --}}
        <div class="col-12 col-md-6">
            <div class="card-custom p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center gap-2 text-success small fw-semibold">
                        <i class="bi bi-shop"></i> SUPPLIER DETAILS 
</div>
                    @if(!empty($supplier?->gst_number))
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-normal">GST: {{ $supplier->gst_number }}</span>
                    @endif
</div>
                <h5 class="fw-bold text-dark mb-1">{{ $supplierName }}</h5>
                <div class="text-muted small d-flex flex-wrap gap-3">
                    @if(!empty($supplier?->contact_person))
                        <span><i class="bi bi-person me-1"></i>{{ $supplier->contact_person }}</span>
                    @endif
                    @if(!empty($supplier?->phone))
                        <span><i class="bi bi-telephone me-1"></i><a href="tel:{{ $supplier->phone }}" class="text-decoration-none text-muted">{{ $supplier->phone }}</a></span>
                    @endif
                    @if(!empty($supplier?->address))
                        <span><i class="bi bi-geo-alt me-1"></i>{{ $supplier->address }}</span>
                    @endif
</div>
</div>
</div>
</div>

    {{-- Overview KPI Cards --}}
    <div class="row g-2 mb-4" id="kpiCardsRow">
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 text-center">
                <div class="text-muted small mb-1" style="font-size:0.75rem;">Total Purchase Value</div>
                <div class="fs-4 fw-bold text-success" id="kpiTotalValue">₹{{ number_format($supplierStats['total_amount'], 2) }}</div>
</div>
</div>
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 text-center">
                <div class="text-muted small mb-1" style="font-size:0.75rem;">Purchase Orders</div>
                <div class="fs-4 fw-bold text-primary" id="kpiTotalOrders">{{ $supplierStats['total_pos'] }} {{ Str::plural('Order', $supplierStats['total_pos']) }}</div>
</div>
</div>
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 text-center">
                <div class="text-muted small mb-1" style="font-size:0.75rem;">Materials Types</div>
                <div class="fs-4 fw-bold text-warning" id="kpiTotalMaterials">{{ $supplierStats['materials_count'] }}</div>
</div>
</div>
        <div class="col-6 col-md-3">
            <div class="card-custom p-3 text-center">
                <div class="text-muted small mb-1" style="font-size:0.75rem;">Total Quantity</div>
                <div class="fs-4 fw-bold text-dark font-monospace" id="kpiTotalQuantity">{{ number_format($supplierStats['total_quantity'], 2) }}</div>
</div>
</div>
</div>

    {{-- DATE FILTER BAR --}}
    <div class="card-custom p-3 mb-4 bg-light no-print">
        <form method="GET" action="{{ route('sites.supplier-purchases', ['encryptedId' => $site->encrypted_id, 'supplierKey' => $supplierKey]) }}" id="dateFilterForm">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                    <span class="fw-bold text-dark small text-nowrap">
                        <i class="bi bi-calendar-range text-primary me-1"></i>Date Filter :
</span>
</div>
                <div class="col-6 col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white small text-muted">From</span>
                        <input type="date" name="from_date" id="filterFromDate" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
</div>
</div>
                <div class="col-6 col-md-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white small text-muted">To</span>
                        <input type="date" name="to_date" id="filterToDate" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
</div>
</div>
                <div class="col-12 col-md-auto d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-funnel"></i> Apply Filter
</button>
                    <a href="{{ route('sites.supplier-purchases', ['encryptedId' => $site->encrypted_id, 'supplierKey' => $supplierKey]) }}" class="btn btn-outline-secondary btn-sm px-3 d-inline-flex align-items-center gap-1" id="resetFilterBtn">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset 
</a>
</div>
                <div class="col-12 col-md-auto ms-md-auto d-flex flex-wrap align-items-center gap-1">
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 filter-preset-btn" data-preset="all" style="font-size:0.75rem;">
                        All Dates
</button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 filter-preset-btn" data-preset="this-month" style="font-size:0.75rem;">
                        This Month 
</button>
                    <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 filter-preset-btn" data-preset="last-30" style="font-size:0.75rem;">
                        Last 30 Days
</button>
</div>
</div>
        </form>
</div>

    {{-- SECTION 1: Materials & Price Breakdown Summary --}}
    <div class="card-custom mb-4 overflow-hidden" id="materialSummaryCard">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
            <div class="fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-box-seam-fill text-primary"></i>
                <span>Materials & Price Summary</span>
</div>
            <span class="badge bg-light text-dark border" id="materialsCountBadge">{{ count($materialSummary) }} Materials</span>
</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="materialsSummaryTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 py-2 text-muted" style="width: 40px;">#</th>
                        <th class="py-2 text-muted">Material Name</th>
                        <th class="py-2 text-center text-muted">Purchased Quantity</th>
                        <th class="py-2 text-end text-muted">Average Rate</th>
                        <th class="pe-3 py-2 text-end text-muted">Total Amount</th>
                    </tr>
                </thead>
                <tbody id="materialSummaryTbody">
                    @forelse($materialSummary as $mIndex => $mItem)
                        <tr class="material-summary-row" data-material="{{ strtolower($mItem['material_name']) }}">
                            <td class="ps-3 text-muted text-center">{{ str_pad($mIndex + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $mItem['material_name'] }}</div>
                                @if(!empty($mItem['material_name_ta']))
                                    <div class="ta-text text-muted" style="font-size: 0.72rem;">{{ $mItem['material_name_ta'] }}</div>
                                @endif
                            </td>
                            <td class="text-center font-monospace">
                                <span class="badge bg-light text-dark border px-2 py-1">
                                    {{ number_format($mItem['total_quantity'], 2) }} {{ $mItem['unit'] }}
</span>
                            </td>
                            <td class="text-end fw-semibold text-secondary">
                                ₹{{ number_format($mItem['average_price'], 2) }}
                                <span class="text-muted" style="font-size: 0.7rem;">/ {{ $mItem['unit'] }}</span>
                            </td>
                            <td class="pe-3 text-end fw-bold text-dark">
                                ₹{{ number_format($mItem['total_amount'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">No material purchases recorded for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light border-top" id="materialSummaryTfoot">
                    <tr>
                        <td colspan="2" class="ps-3 fw-bold text-dark">Total Material Summary </td>
                        <td class="text-center fw-bold text-dark font-monospace" id="summaryTotalQty">{{ number_format($supplierStats['total_quantity'], 2) }} items</td>
                        <td class="text-end text-muted small">Total Value:</td>
                        <td class="pe-3 text-end fw-bold text-success fs-6" id="summaryTotalAmount">₹{{ number_format($supplierStats['total_amount'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
</div>
</div>

    {{-- SECTION 2: Date-wise Purchase History & Orders Breakdown --}}
    <div class="mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check-fill text-success"></i>
            <span>Date-wise Purchase Breakdown</span>
</h6>
        <span class="text-muted small" id="ordersFilterStatus">Showing {{ count($dateGroupedPurchases) }} orders</span>
</div>

    {{-- Empty Message if no orders match date filter --}}
    <div class="card-custom p-4 text-center text-muted mb-4 d-none" id="noOrdersMessage">
        <i class="bi bi-calendar-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
        <div class="fw-bold text-dark">No Purchases Found for Selected Date Range</div>
        <div class="small text-muted mb-3">No purchase orders found in the selected date range.</div>
        <a href="{{ route('sites.supplier-purchases', ['encryptedId' => $site->encrypted_id, 'supplierKey' => $supplierKey]) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-arrow-counterclockwise me-1"></i>View All Purchases 
</a>
</div>

    <div class="d-flex flex-column gap-3 mb-4" id="dateGroupedContainer">
        @forelse($dateGroupedPurchases as $poGroupIndex => $poGroup)
            <div class="card-custom overflow-hidden date-grouped-card" data-date="{{ $poGroup['order_date_raw'] }}" data-amount="{{ $poGroup['total_amount'] }}" data-qty="{{ $poGroup['total_quantity'] }}">
                <div class="card-header bg-light py-2 px-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            <i class="bi bi-calendar3 me-1"></i>Date: <strong>{{ $poGroup['order_date'] }}</strong>
</span>
                        <div class="fw-bold text-dark">
                            Purchase Order: 
                            <a href="{{ route('purchases.show', $poGroup['po_encrypted_id']) }}" class="text-decoration-none text-primary" target="_blank">
                                {{ $poGroup['po_number'] }}
</a>
</div>
                        @if(!empty($poGroup['status']))
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal" style="font-size:0.68rem;">
                                {{ $poGroup['status'] }}
</span>
                        @endif
                        @if(!empty($poGroup['delivery_status']))
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fw-normal" style="font-size:0.68rem;">
                                {{ $poGroup['delivery_status'] }}
</span>
                        @endif
</div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <span class="fw-bold text-success">
                            PO Total: ₹{{ number_format($poGroup['total_amount'], 2) }}
</span>
                        <a href="{{ route('purchases.show', $poGroup['po_encrypted_id']) }}" class="btn btn-sm btn-outline-primary py-0 px-2 no-print" style="font-size:0.75rem;" target="_blank">
                            <i class="bi bi-eye me-1"></i>View PO
</a>
</div>
</div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 py-2 text-muted" style="width: 40px;">#</th>
                                <th class="py-2 text-muted">Material Name</th>
                                <th class="py-2 text-center text-muted">Purchased Quantity</th>
                                <th class="py-2 text-end text-muted">Unit Price</th>
                                <th class="pe-3 py-2 text-end text-muted">Total Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($poGroup['items'] as $itemIndex => $lineItem)
                                <tr class="po-item-row" data-material="{{ strtolower($lineItem['material_name']) }}" data-qty="{{ $lineItem['quantity'] }}" data-unit="{{ $lineItem['unit'] }}" data-price="{{ $lineItem['unit_price'] }}" data-total="{{ $lineItem['total_price'] }}">
                                    <td class="ps-3 text-muted text-center">{{ str_pad($itemIndex + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $lineItem['material_name'] }}</div>
                                        @if(!empty($lineItem['material_name_ta']))
                                            <div class="ta-text text-muted" style="font-size:0.72rem;">{{ $lineItem['material_name_ta'] }}</div>
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
                                    <td class="pe-3 text-end fw-bold text-dark">
                                        ₹{{ number_format($lineItem['total_price'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light border-top">
                            <tr>
                                <td colspan="2" class="ps-3 fw-bold text-dark">Total for Date {{ $poGroup['order_date'] }} ({{ $poGroup['po_number'] }})</td>
                                <td class="text-center fw-bold text-dark font-monospace">{{ number_format($poGroup['total_quantity'], 2) }} items</td>
                                <td class="text-end text-muted small">Subtotal:</td>
                                <td class="pe-3 text-end fw-bold text-success fs-6">₹{{ number_format($poGroup['total_amount'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
</div>
</div>
        @empty
            <div class="card-custom p-4 text-center text-muted">
                <i class="bi bi-cart-x fs-1 text-secondary opacity-50 d-block mb-2"></i>
                <div class="fw-bold text-dark">No Purchase Orders Recorded</div>
                <div class="small text-muted">No purchase orders recorded for this supplier yet.</div>
</div>
        @endforelse
</div>

    {{-- Grand Total Bottom Card --}}
    <div class="card-custom p-3 bg-light border text-end mb-4" id="grandTotalCard">
        <span class="text-muted me-2">Total Purchased from <strong>{{ $supplierName }}</strong> for this site:</span>
        <span class="fs-5 fw-bold text-success" id="grandTotalAmount">₹{{ number_format($supplierStats['total_amount'], 2) }}</span>
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

            $('#filterFromDate').val(from);
            $('#filterToDate').val(to);
            applyLiveDateFilter(from, to);
        });

        // Instant Live Date Filtering
        $('#filterFromDate, #filterToDate').on('change', function () {
            $('.filter-preset-btn').removeClass('active');
            const from = $('#filterFromDate').val();
            const to = $('#filterToDate').val();
            applyLiveDateFilter(from, to);
        });

        function applyLiveDateFilter(from, to) {
            let visibleOrders = 0;
            let totalFilteredAmount = 0;
            let totalFilteredQty = 0;
            const materialTotals = {};

            $('.date-grouped-card').each(function () {
                const cardDate = $(this).data('date'); // YYYY-MM-DD
                let show = true;

                if (from && cardDate && cardDate < from) {
                    show = false;
                }
                if (to && cardDate && cardDate > to) {
                    show = false;
                }

                $(this).toggle(show);

                if (show) {
                    visibleOrders++;
                    const cardAmt = parseFloat($(this).data('amount')) || 0;
                    const cardQty = parseFloat($(this).data('qty')) || 0;
                    totalFilteredAmount += cardAmt;
                    totalFilteredQty += cardQty;

                    // Aggregate materials inside this card
                    $(this).find('.po-item-row').each(function () {
                        const matName = $(this).data('material');
                        const qty = parseFloat($(this).data('qty')) || 0;
                        const total = parseFloat($(this).data('total')) || 0;

                        if (!materialTotals[matName]) {
                            materialTotals[matName] = { qty: 0, total: 0 };
                        }
                        materialTotals[matName].qty += qty;
                        materialTotals[matName].total += total;
                    });
                }
            });

            const totalCards = $('.date-grouped-card').length;
            $('#ordersFilterStatus').text(`Showing ${visibleOrders} of ${totalCards} orders`);

            if (visibleOrders === 0 && totalCards > 0) {
                $('#noOrdersMessage').removeClass('d-none');
                $('#grandTotalCard').hide();
                $('#materialSummaryCard').hide();
            } else {
                $('#noOrdersMessage').addClass('d-none');
                $('#grandTotalCard').show();
                $('#materialSummaryCard').show();
            }

            // Update KPI cards
            const formattedTotal = '₹' + totalFilteredAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            $('#kpiTotalValue').text(formattedTotal);
            $('#kpiTotalOrders').text(visibleOrders + (visibleOrders === 1 ? ' Order' : ' Orders'));
            $('#kpiTotalQuantity').text(totalFilteredQty.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#grandTotalAmount').text(formattedTotal);

            // Update Material Summary table visibility based on active materials
            let visibleMatsCount = 0;
            $('.material-summary-row').each(function () {
                const matName = $(this).data('material');
                const hasUsage = materialTotals[matName] && materialTotals[matName].qty > 0;
                $(this).toggle(Boolean(hasUsage || (!from && !to)));
                if (hasUsage || (!from && !to)) {
                    visibleMatsCount++;
                }
            });

            $('#kpiTotalMaterials').text(visibleMatsCount);
            $('#materialsCountBadge').text(visibleMatsCount + ' Materials');
            $('#summaryTotalQty').text(totalFilteredQty.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' items');
            $('#summaryTotalAmount').text(formattedTotal);
        }
    });
</script>
@endpush
