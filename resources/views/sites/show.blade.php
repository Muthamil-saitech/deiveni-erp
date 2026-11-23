@extends('layouts.app')

@section('title', $site->name . ' - Project Site Details')
@section('page_title', 'Project Site Details (கட்டுமான திட்ட தளம் முழு விவரம்)')

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

        @media print {
            body {
                background-color: #ffffff !important;
                color: #111827 !important;
                font-size: 11pt !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            #sidebar,
            .top-navbar,
            .no-print,
            .btn,
            .dropdown,
            nav,
            header,
            footer,
            .breadcrumb {
                display: none !important;
            }

            .main-content,
            .content-wrapper,
            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .card-custom,
            .card {
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
                break-inside: avoid;
            }

            .table-custom th,
            .table-custom td {
                border-color: #cbd5e1 !important;
                color: #000 !important;
            }

            .badge {
                border: 1px solid #000 !important;
                color: #000 !important;
                background: #fff !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $estNum = (float) $site->estimated_budget;
        $spentNum = (float) ($site->spent ?? 0);
        $remainingNum = max(0, $estNum - $spentNum);
        $pct = $estNum > 0 ? min(100, round(($spentNum / $estNum) * 100, 2)) : 0;

        $estInLakhs = number_format($estNum / 100000, 2);
        $spentInLakhs = number_format($spentNum / 100000, 2);
        $remainingInLakhs = number_format($remainingNum / 100000, 2);

        $statusBadgeClass = match ($site->status) {
            'Completed' => 'bg-success-subtle text-success',
            'In Progress' => 'bg-warning-subtle text-dark',
            'Planning' => 'bg-secondary-subtle text-muted',
            'On Hold' => 'bg-danger-subtle text-danger',
            default => 'bg-primary-subtle text-primary',
        };

        $workTypeBadgeClass = $site->work_type === 'Alteration'
            ? 'bg-secondary-subtle text-secondary border'
            : 'bg-info-subtle text-info border border-info-subtle';

        $startDateFormatted = $site->start_date ? \Carbon\Carbon::parse($site->start_date)->format('d M Y') : 'Not Set';
        $endDateFormatted = $site->end_date ? \Carbon\Carbon::parse($site->end_date)->format('d M Y') : 'Not Set';

        $durationDays = null;
        if ($site->start_date && $site->end_date) {
            $durationDays = \Carbon\Carbon::parse($site->start_date)->diffInDays(\Carbon\Carbon::parse($site->end_date));
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $site->client?->phone ?? '');
    @endphp

    <div class="container-fluid px-0">

        <!-- Top Navigation / Action Bar -->
        <div class="card-custom p-3 mb-4 bg-white no-print">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <a href="{{ route('sites.index') }}"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        title="Back to Project Sites">
                        <i class="bi bi-arrow-left me-1"></i> Back to Sites
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="fw-bold mb-0 text-dark">{{ $site->name }}</h5>
                            <span class="badge {{ $workTypeBadgeClass }} fw-semibold">{{ $site->work_type }}</span>
                            <span class="badge {{ $statusBadgeClass }}">{{ $site->status }}</span>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $site->location }}
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if ($site->client?->phone)
                        <a href="tel:{{ $site->client->phone }}"
                            class="btn btn-outline-success btn-sm d-inline-flex align-items-center">
                            <i class="bi bi-telephone-fill me-1"></i> Call Client
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4 Quick Stat Summary Cards -->
        <div class="row g-3 mb-4">
            <!-- 1. Work Type Card -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Work Type (பணி வகை)</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1">{{ $site->work_type }}</h6>
                            <small
                                class="text-muted">{{ $site->work_type === 'New Build' ? 'புதிய கட்டுமானம்' : 'சிறு மாற்றம் / புதுப்பித்தல்' }}</small>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2 rounded-3">
                            <i class="bi bi-hammer fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Estimated Budget -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Est. Budget (மதிப்பீடு)</span>
                            <h5 class="fw-bold mb-0 text-success mt-1">₹{{ number_format($estNum, 2) }}</h5>
                            <small class="text-muted">₹{{ $estInLakhs }} Lakhs</small>
                        </div>
                        <div class="bg-success-subtle text-success p-2 rounded-3">
                            <i class="bi bi-cash-stack fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Total Spent -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Total Spent (செலவு)</span>
                            <h5 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($spentNum, 2) }}</h5>
                            <small class="text-muted">{{ number_format($pct, 2) }}% utilized (₹{{ $spentInLakhs }} L)</small>
                        </div>
                        <div class="bg-danger-subtle text-danger p-2 rounded-3">
                            <i class="bi bi-graph-up-arrow fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Project Status & Progress -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Site Status (நிலை)</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1">{{ $site->status }}</h6>
                            <small class="text-muted">Remaining: ₹{{ $remainingInLakhs }} L</small>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2 rounded-3">
                            <i class="bi bi-speedometer2 fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Site Overview & Financials -->
            <div class="col-12 col-lg-7">                <!-- Site Details Card -->
                <div class="card-custom p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-building me-2 text-primary"></i>Site Overview (தள விவரங்கள்)
                        </h6>
                        <span class="badge {{ $statusBadgeClass }} px-3 py-1">{{ $site->status }}</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block fw-semibold">Site Project Name</small>
                            <span class="fw-bold text-dark fs-6">{{ $site->name }}</span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block fw-semibold">Location / Area</small>
                            <span class="text-dark">
                                <i class="bi bi-geo-alt text-danger me-1"></i>{{ $site->location }}
                            </span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block fw-semibold">Work Type</small>
                            <span class="badge {{ $workTypeBadgeClass }} mt-1">{{ $site->work_type }}</span>
                        </div>
                        <div class="col-12 col-sm-6">
                        </div>

                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block fw-semibold">Start Date</small>
                            <span class="text-dark"><i
                                    class="bi bi-calendar-check me-1 text-primary"></i>{{ $startDateFormatted }}</span>
                        </div>
                        <div class="col-12 col-sm-6">
                            <small class="text-muted d-block fw-semibold">Target End Date</small>
                            <span class="text-dark"><i
                                    class="bi bi-calendar-x me-1 text-primary"></i>{{ $endDateFormatted }}</span>
                        </div>

                        @if ($durationDays !== null)
                            <div class="col-12">
                                <div class="bg-light p-2 rounded small text-muted d-flex align-items-center">
                                    <i class="bi bi-clock-history me-2 text-primary fs-6"></i>
                                    <span>Planned Timeline: <strong>{{ $durationDays }} Days</strong> ({{ $startDateFormatted }}
                                        to {{ $endDateFormatted }})</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Budget & Financial Card -->
                <div class="card-custom p-4 bg-white mb-4">
                    <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">
                        <i class="bi bi-currency-rupee me-1 text-primary"></i>Budget & Expenditure (நிதி மற்றும் பட்ஜெட்
                        பயன்பாடு)
                    </h6>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Spent: <strong class="text-danger">₹{{ number_format($spentNum, 2) }}
                                    ({{ $spentInLakhs }} Lakhs)</strong></span>
                            <span class="fw-bold text-dark">Estimated: ₹{{ number_format($estNum, 2) }} ({{ $estInLakhs }}
                                Lakhs)</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar {{ $pct > 90 ? 'bg-danger' : ($pct > 75 ? 'bg-warning' : 'bg-primary') }}"
                                role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0"
                                aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span>{{ $pct }}% utilized</span>
                            <span>Remaining Balance: <strong
                                    class="text-success">₹{{ number_format($remainingNum, 2) }}</strong></span>
                        </div>
                    </div>

                    <div class="row g-2 pt-2 border-top mb-0">
                        <div class="col-4 text-center border-end">
                            <small class="text-muted d-block">Total Budget</small>
                            <strong class="text-dark">₹{{ $estInLakhs }} L</strong>
                        </div>
                        <div class="col-4 text-center border-end">
                            <small class="text-muted d-block">Utilized</small>
                            <strong class="text-danger">₹{{ $spentInLakhs }} L</strong>
                        </div>
                        <div class="col-4 text-center">
                            <small class="text-muted d-block">Available</small>
                            <strong class="text-success">₹{{ $remainingInLakhs }} L</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Client Information & Operations -->
            <div class="col-12 col-lg-5">
                <!-- Client Information Card -->
                <div class="card-custom p-4 bg-white mb-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-person-circle me-2 text-primary"></i>Client Information (வாடிக்கையாளர் தகவல்)
                        </h6>
                        <span class="badge bg-success-subtle text-success">
                            <i class="bi bi-check-circle me-1"></i>Active Client
                        </span>
                    </div>

                    @if ($site->client)
                        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                style="width: 46px; height: 46px; font-size: 1.1rem;">
                                {{ strtoupper(substr($site->client->name, 0, 1)) }}
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">{{ $site->client->name }}</h6>
                            </div>
                        </div>

                        <div class="vstack gap-3 small">
                            <div>
                                <span class="text-muted d-block fw-semibold"><i
                                        class="bi bi-telephone text-primary me-1"></i>Phone Number:</span>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span
                                        class="font-monospace fw-semibold text-dark">{{ $site->client->phone ?: 'Not Provided' }}</span>
                                    @if ($site->client->phone)
                                        <a href="tel:{{ $site->client->phone }}" class="btn btn-sm btn-outline-primary py-0 px-2"
                                            title="Call">
                                            <i class="bi bi-telephone-fill"></i>
                                        </a>
                                        @if ($cleanPhone)
                                            <a href="https://wa.me/91{{ $cleanPhone }}" target="_blank"
                                                class="btn btn-sm btn-outline-success py-0 px-2" title="WhatsApp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <div>
                                <span class="text-muted d-block fw-semibold"><i
                                        class="bi bi-geo-alt text-primary me-1"></i>Billing & Contact Address:</span>
                                <span class="text-dark">{{ $site->client->address ?: $site->location }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-muted small py-3 text-center">
                            <i class="bi bi-person-x fs-3 d-block mb-2 text-warning"></i>
                            No dedicated client linked to this site.
                        </div>
                    @endif
                </div>

                <!-- Site Operations Quick Links removed - replaced by Tabs in Budget card -->

                <!-- Record Metadata -->
                <div class="card-custom p-3 bg-white text-muted small">
                    <div class="d-flex justify-content-between">
                        <span>Created:</span>
                        <strong
                            class="text-dark">{{ $site->created_at ? $site->created_at->format('d M Y, h:i A') : 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span>Last Updated:</span>
                        <strong
                            class="text-dark">{{ $site->updated_at ? $site->updated_at->format('d M Y, h:i A') : 'N/A' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FULL-WIDTH TABS CARD: Labours / Materials Used / Stock Transferred ===== --}}
        <div class="card-custom p-4 bg-white mt-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-bar-chart-steps me-2 text-primary"></i>Site Activity (தளத்தின் செயல்பாடுகள்)
                </h6>
            </div>

            {{-- Tab Nav --}}
            <ul class="nav nav-tabs border-bottom-0" id="siteExpTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center gap-2 px-3 py-2" id="tab-labour"
                        data-bs-toggle="tab" data-bs-target="#panel-labour" type="button" role="tab"
                        aria-controls="panel-labour" aria-selected="true" style="font-size:0.83rem;">
                        <i class="bi bi-people-fill text-primary"></i>
                        <span class="fw-semibold">Labours <span class="ta-text" style="font-size:0.75rem;">(தொழிலாளர்கள்)</span></span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.68rem;">{{ $labourRecords->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2 px-3 py-2" id="tab-materials"
                        data-bs-toggle="tab" data-bs-target="#panel-materials" type="button" role="tab"
                        aria-controls="panel-materials" aria-selected="false" style="font-size:0.83rem;">
                        <i class="bi bi-box-seam-fill text-warning"></i>
                        <span class="fw-semibold">Materials Used <span class="ta-text" style="font-size:0.75rem;">(பொருள் பயன்பாடு)</span></span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.68rem;">{{ $materialUsageRecords->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2 px-3 py-2" id="tab-transfers"
                        data-bs-toggle="tab" data-bs-target="#panel-transfers" type="button" role="tab"
                        aria-controls="panel-transfers" aria-selected="false" style="font-size:0.83rem;">
                        <i class="bi bi-arrow-left-right text-success"></i>
                        <span class="fw-semibold">Stock Transferred <span class="ta-text" style="font-size:0.75rem;">(பொருள் பரிமாற்றம்)</span></span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.68rem;">{{ $stockTransferRecords->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom" id="siteExpTabsContent">

                {{-- ===== LABOUR TAB ===== --}}
                <div class="tab-pane fade show active" id="panel-labour" role="tabpanel">
                    @if ($labourRecords->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-25"></i>
                            <div class="fw-semibold">No Labour Records (தொழிலாளர் பதிவுகள் இல்லை)</div>
                            <small>No daily allocation records found for this site.</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="labourTabTable">
                                <thead class="table-light">
                                    <tr class="text-muted small text-uppercase fw-bold" style="font-size:0.74rem;">
                                        <th class="ps-3">DATE <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(தேதி)</span></th>
                                        <th>WORKER CATEGORIES <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பணியாளர் வகைகள்)</span></th>
                                        <th class="text-center">LABOURS <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(எண்ணிக்கை)</span></th>
                                        <th class="text-end">LABOUR WAGE (₹) <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(கூலி)</span></th>
                                        <th class="text-end">EXPENSES (₹) <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(செலவு)</span></th>
                                        <th class="text-end">GRAND TOTAL (₹) <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(மொத்தம்)</span></th>
                                        <th>RECORDED BY <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பதிவு செய்தவர்)</span></th>
                                    </tr>
                                </thead>
                                <tbody id="labourTabBody">
                                    @foreach ($labourRecords as $da)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold text-dark" style="font-size:0.85rem;">{{ $da->date?->format('d M Y') }}</div>
                                                <div class="text-muted" style="font-size:0.72rem;">{{ $da->date?->format('D') }}</div>
                                            </td>
                                            <td style="max-width:220px;">
                                                @if ($da->items && $da->items->count() > 0)
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach ($da->items->take(3) as $item)
                                                            <span class="badge bg-light text-dark border" style="font-size:0.68rem;">
                                                                {{ $item->name_en ?: ($item->workerCategory?->name_en ?? 'Worker') }}
                                                                @if ($item->worker_count)
                                                                    <span class="text-primary fw-bold ms-1">×{{ $item->worker_count }}</span>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                        @if ($da->items->count() > 3)
                                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.68rem;">+{{ $da->items->count() - 3 }} more</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 fs-6">{{ $da->total_labour_count }}</span>
                                            </td>
                                            <td class="text-end fw-semibold text-dark">{{ number_format((float) $da->total_labour_wage, 2) }}</td>
                                            <td class="text-end text-muted">{{ (float) $da->total_expenses > 0 ? number_format((float) $da->total_expenses, 2) : '—' }}</td>
                                            <td class="text-end fw-bold" style="color:#1e3a8a;">{{ number_format((float) $da->grand_total, 2) }}</td>
                                            <td class="text-muted small">{{ $da->creator?->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-3 py-2 bg-light border-top rounded-bottom" id="labourPagination">
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">Rows per page:</span>
                                <select class="form-select form-select-sm" style="width:65px;" id="labourPerPage">
                                    <option value="5" selected>5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                </select>
                                <span class="small text-muted ms-2" id="labourTabSummary"></span>
                            </div>
                            <nav><ul class="pagination pagination-sm mb-0 gap-1" id="labourTabNav"></ul></nav>
                        </div>
                    @endif
                </div>

                {{-- ===== MATERIALS USED TAB ===== --}}
                <div class="tab-pane fade" id="panel-materials" role="tabpanel">
                    @if ($materialUsageRecords->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary opacity-25"></i>
                            <div class="fw-semibold">No Material Usage Records (பொருள் பயன்பாடு பதிவுகள் இல்லை)</div>
                            <small>No materials have been recorded as used for this site.</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="materialsTabTable">
                                <thead class="table-light">
                                    <tr class="text-muted small text-uppercase fw-bold" style="font-size:0.74rem;">
                                        <th class="ps-3">USAGE NO & DATE <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பயன்பாடு எண் & தேதி)</span></th>
                                        <th>SOURCE / PO <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(ஆதாரம்)</span></th>
                                        <th style="min-width:260px;">MATERIALS, QTY & PRICE <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பொருட்கள் & விலை)</span></th>
                                        <th class="text-end">TOTAL QTY <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(மொத்த அளவு)</span></th>
                                        <th class="text-end">TOTAL COST (₹) <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(மொத்த தொகை)</span></th>
                                        <th>RECORDED BY <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பதிவு செய்தவர்)</span></th>
                                    </tr>
                                </thead>
                                <tbody id="materialsTabBody">
                                    @foreach ($materialUsageRecords as $mu)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold font-monospace text-dark" style="font-size:0.85rem;">{{ $mu->usage_no }}</div>
                                                <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-calendar3 me-1"></i>{{ $mu->usage_date?->format('d M Y') }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $hasWarehouse = $mu->items->where('source_type', 'Own Warehouse')->count() > 0;
                                                    $poNumbers = $mu->items->where('source_type', '!=', 'Own Warehouse')->pluck('source_reference')->filter()->unique();
                                                @endphp
                                                @if ($hasWarehouse)
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle mb-1" style="font-size:0.72rem;">
                                                        <i class="bi bi-boxes me-1"></i>Own Warehouse
                                                    </span><br>
                                                @endif
                                                @if ($poNumbers->count() > 0)
                                                    @foreach ($poNumbers as $poNum)
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1" style="font-size:0.72rem;">
                                                            <i class="bi bi-receipt me-1"></i>{{ $poNum }}
                                                        </span><br>
                                                    @endforeach
                                                @elseif (!$hasWarehouse && $mu->purchaseOrder)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.72rem;">
                                                        <i class="bi bi-receipt me-1"></i>{{ $mu->purchaseOrder->po_number }}
                                                    </span>
                                                    <div class="text-muted mt-1" style="font-size:0.7rem;">{{ $mu->purchaseOrder->supplier?->name }}</div>
                                                @elseif (!$hasWarehouse)
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @foreach ($mu->items as $item)
                                                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:3px 0;border-bottom:1px dashed #f1f5f9;font-size:0.8rem;">
                                                        <div>
                                                            <span class="fw-semibold text-dark">{{ $item->material_name }}</span>
                                                            @if ($item->source_type === 'Own Warehouse')
                                                                <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.65rem;"><i class="bi bi-boxes me-1"></i>Warehouse</span>
                                                            @else
                                                                <span class="badge bg-light text-secondary border ms-1" style="font-size:0.65rem;">{{ $item->source_reference ?: 'PO' }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-end" style="white-space:nowrap;">
                                                            <span class="fw-semibold text-primary">{{ number_format((float) $item->used_quantity, 2) }} {{ $item->unit }}</span>
                                                            <span class="text-muted ms-1" style="font-size:0.72rem;">@ ₹{{ number_format((float) $item->unit_price, 2) }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </td>
                                            <td class="text-end fw-semibold text-dark">{{ number_format((float) $mu->total_used_quantity, 2) }}</td>
                                            <td class="text-end fw-bold text-success">₹{{ number_format((float) $mu->total_cost, 2) }}</td>
                                            <td class="text-muted small">{{ $mu->creator?->name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-3 py-2 bg-light border-top rounded-bottom" id="materialsPagination">
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">Rows per page:</span>
                                <select class="form-select form-select-sm" style="width:65px;" id="materialsPerPage">
                                    <option value="5" selected>5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                </select>
                                <span class="small text-muted ms-2" id="materialsTabSummary"></span>
                            </div>
                            <nav><ul class="pagination pagination-sm mb-0 gap-1" id="materialsTabNav"></ul></nav>
                        </div>
                    @endif
                </div>

                {{-- ===== STOCK TRANSFERRED TAB ===== --}}
                <div class="tab-pane fade" id="panel-transfers" role="tabpanel">
                    @if ($stockTransferRecords->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-left-right fs-1 d-block mb-2 text-secondary opacity-25"></i>
                            <div class="fw-semibold">No Stock Transfers (பொருள் பரிமாற்றம் இல்லை)</div>
                            <small>No stock transfers from this site yet.</small>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="transfersTabTable">
                                <thead class="table-light">
                                    <tr class="text-muted small text-uppercase fw-bold" style="font-size:0.74rem;">
                                        <th class="ps-3">TRANSFER NO & DATE <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பரிமாற்ற எண் & தேதி)</span></th>
                                        <th>DESTINATION SITE <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பெறும் தளம்)</span></th>
                                        <th style="min-width:200px;">MATERIALS <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பொருட்கள்)</span></th>
                                        <th class="text-center">QTY <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(அளவு)</span></th>
                                        <th class="text-end">VALUE (₹) <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(மதிப்பு)</span></th>
                                        <th>RETURN STATUS <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(திரும்ப நிலை)</span></th>
                                        <th>BY <span class="ta-text fw-normal text-lowercase" style="font-size:0.68rem;">(பதிவு)</span></th>
                                    </tr>
                                </thead>
                                <tbody id="transfersTabBody">
                                    @foreach ($stockTransferRecords as $st)
                                        <tr>
                                            <td class="ps-3">
                                                <div class="fw-bold font-monospace text-dark" style="font-size:0.85rem;">{{ $st->transfer_no }}</div>
                                                <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-calendar3 me-1"></i>{{ $st->transfer_date?->format('d M Y') }}</div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size:0.65rem;">
                                                    <i class="bi bi-box-arrow-right me-1"></i>TRANSFER
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge border" style="background:#dcfce7;color:#166534;border-color:#bbf7d0!important;font-size:0.72rem;">
                                                    <i class="bi bi-geo-alt me-1"></i>{{ $st->toSite?->site_name ?? 'Site #'.$st->to_project_site_id }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($st->items->count() > 1)
                                                    <div class="fw-semibold text-dark" style="font-size:0.82rem;">
                                                        <i class="bi bi-boxes text-primary me-1"></i>{{ $st->items->count() }} Materials
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        @foreach ($st->items->take(2) as $it)
                                                            <span class="badge bg-light text-dark border" style="font-size:0.68rem;">
                                                                {{ $it->material_name }} ({{ (float) $it->quantity }} {{ $it->unit }})
                                                            </span>
                                                        @endforeach
                                                        @if ($st->items->count() > 2)
                                                            <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.68rem;">+{{ $st->items->count() - 2 }} more</span>
                                                        @endif
                                                    </div>
                                                @elseif ($st->items->count() === 1)
                                                    @php $si = $st->items->first(); @endphp
                                                    <div class="fw-semibold text-dark" style="font-size:0.82rem;">{{ $si->material_name }}</div>
                                                    <span class="badge border mt-1 {{ $si->source_type === 'Own Warehouse' ? 'bg-info-subtle text-info border-info-subtle' : 'bg-primary-subtle text-primary border-primary-subtle' }}" style="font-size:0.65rem;">
                                                        {{ $si->source_reference ?: $si->source_type }}
                                                    </span>
                                                @else
                                                    <span class="fw-semibold text-dark" style="font-size:0.82rem;">{{ $st->material?->name_en ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold fs-6 text-dark">{{ number_format((float) $st->quantity, 2) }}</div>
                                                <div class="text-muted small" style="font-size:0.72rem;">{{ $st->unit ?? 'Items' }}</div>
                                            </td>
                                            <td class="text-end">
                                                @if ((float) $st->total_amount > 0)
                                                    <div class="fw-bold text-primary">₹{{ number_format((float) $st->total_amount, 2) }}</div>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $rs = $st->return_status ?? 'Pending Return';
                                                @endphp
                                                @if (!$st->return_expected)
                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:0.7rem;">No Return Needed</span>
                                                @elseif (str_contains($rs, 'Fully'))
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.7rem;">
                                                        <i class="bi bi-check2-circle me-1"></i>Fully Returned
                                                    </span>
                                                    <div class="text-muted mt-1" style="font-size:0.68rem;">{{ (float) $st->returned_quantity }}/{{ (float) $st->quantity }}</div>
                                                @elseif (str_contains($rs, 'Partial'))
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:0.7rem;">
                                                        <i class="bi bi-hourglass-split me-1"></i>Partial
                                                    </span>
                                                    <div class="text-muted mt-1" style="font-size:0.68rem;">{{ (float) $st->returned_quantity }}/{{ (float) $st->quantity }} returned</div>
                                                @else
                                                    <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size:0.7rem;">
                                                        <i class="bi bi-clock-history me-1"></i>Pending ({{ (float) ($st->pending_return_quantity ?? $st->quantity) }} {{ $st->unit }})
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="small fw-semibold text-dark">{{ $st->creator?->name ?? '—' }}</div>
                                                <div class="text-muted" style="font-size:0.68rem;">{{ $st->created_at?->diffForHumans() }}</div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-3 py-2 bg-light border-top rounded-bottom" id="transfersPagination">
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-muted">Rows per page:</span>
                                <select class="form-select form-select-sm" style="width:65px;" id="transfersPerPage">
                                    <option value="5" selected>5</option>
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                </select>
                                <span class="small text-muted ms-2" id="transfersTabSummary"></span>
                            </div>
                            <nav><ul class="pagination pagination-sm mb-0 gap-1" id="transfersTabNav"></ul></nav>
                        </div>
                    @endif
                </div>

            </div>{{-- end tab-content --}}
        </div>{{-- end tabs card --}}

        <!-- Site Cash & Project Transactions Section (தள ரொக்கம் மற்றும் திட்ட நிதிப் பரிவர்த்தனைகள்) -->
        <div class="card-custom p-4 bg-white mt-4">
            @php
                $txnsList = isset($combinedCashTransactions) && $combinedCashTransactions->isNotEmpty()
                    ? $combinedCashTransactions
                    : ($site->siteCashes->isNotEmpty() ? $site->siteCashes->map(function($sc) {
                        return [
                            'id' => 'sc_' . $sc->id,
                            'raw_id' => $sc->id,
                            'source' => 'site_cash',
                            'source_label' => 'Site Cash Handling',
                            'handler_role' => 'Site Manager',
                            'handler_badge_class' => 'bg-warning-subtle text-dark border border-warning-subtle',
                            'handler_name' => $sc->paid_by ?: ($sc->creator?->name ?? 'Site Manager'),
                            'date' => $sc->payment_date ? \Carbon\Carbon::parse($sc->payment_date)->format('Y-m-d') : '',
                            'date_formatted' => $sc->payment_date ? \Carbon\Carbon::parse($sc->payment_date)->format('d M Y') : 'N/A',
                            'time' => $sc->payment_time ?: '',
                            'type' => $sc->transaction_type,
                            'party_name' => $sc->supplier_name ?: ($sc->supplier?->name ?: ($sc->paid_to ?: ($sc->receiver_name ?: '-'))),
                            'description' => $sc->description,
                            'bill_no' => $sc->bill_no ?: 'N/A',
                            'receipt_image' => $sc->receipt_image,
                            'in_amount' => (float) $sc->in_amount,
                            'out_amount' => (float) $sc->out_amount,
                            'items' => $sc->items,
                            'view_url' => route('site-cash.show', $sc->encrypted_id),
                        ];
                    }) : collect());

                $totalTxnCount = $txnsList->count();
                $uniqueTypes = $txnsList->pluck('type')->unique()->filter()->sort();
            @endphp

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom pb-3 mb-3">
                <div>
                    <h6 class="fw-bold mb-1 text-dark">
                        <i class="bi bi-wallet2 me-2 text-primary"></i>Site Cash & Project Transactions (தள ரொக்கம் மற்றும் திட்ட பரிவர்த்தனைகள்)
                    </h6>
                    <small class="text-muted">Account Manager project disbursements (Office Ledger) & Site Manager field cash transactions (Site Cash).</small>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                        {{ $totalTxnCount }} Transactions
                    </span>
                    <a href="{{ route('cash-transfer.index') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center">
                        <i class="bi bi-arrow-left-right me-1"></i> Cash Transfer
                    </a>
                    <a href="{{ route('site-cash.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">
                        <i class="bi bi-plus-circle me-1"></i> Record Site Cash
                    </a>
                </div>
            </div>

            <!-- Project Cash Flow Quick Metrics -->
            @if(isset($projectCashKpis))
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md">
                        <div class="p-2 px-3 rounded bg-light border h-100">
                            <small class="text-muted d-block fw-semibold" style="font-size:0.72rem;">Funds Received (வரவு)</small>
                            <h6 class="fw-bold text-dark mb-0 mt-1">₹{{ number_format($projectCashKpis['total_funds_received'] ?? 0, 2) }}</h6>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="p-2 px-3 rounded bg-light border h-100">
                            <small class="text-muted d-block fw-semibold" style="font-size:0.72rem;">AM Project Spent (அலுவலக செலவு)</small>
                            <h6 class="fw-bold text-primary mb-0 mt-1">₹{{ number_format($projectCashKpis['am_spent'] ?? 0, 2) }}</h6>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="p-2 px-3 rounded bg-light border h-100">
                            <small class="text-muted d-block fw-semibold" style="font-size:0.72rem;">AM Remaining Fund (மீதம்)</small>
                            <h6 class="fw-bold text-info mb-0 mt-1">₹{{ number_format($projectCashKpis['am_balance'] ?? 0, 2) }}</h6>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="p-2 px-3 rounded bg-light border h-100">
                            <small class="text-muted d-block fw-semibold" style="font-size:0.72rem;">SM Site Spent (தள செலவு)</small>
                            <h6 class="fw-bold text-danger mb-0 mt-1">₹{{ number_format($projectCashKpis['sm_spent'] ?? 0, 2) }}</h6>
                        </div>
                    </div>
                    <div class="col-12 col-md">
                        <div class="p-2 px-3 rounded bg-light border h-100">
                            <small class="text-muted d-block fw-semibold" style="font-size:0.72rem;">Site Cash in Hand (கையிருப்பு)</small>
                            <h6 class="fw-bold text-success mb-0 mt-1">₹{{ number_format($projectCashKpis['site_cash_in_hand'] ?? 0, 2) }}</h6>
                        </div>
                    </div>
                </div>
            @endif

            @if ($txnsList->isNotEmpty())
                <!-- Filter & Search Controls -->
                <div class="p-3 mb-3 bg-light rounded-3 border">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="cashSearchInput"
                                    placeholder="Search by spender, party, description, bill, date...">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select class="form-select form-select-sm" id="cashHandlerFilter">
                                <option value="">All Spenders / Handlers (அனைவரும்)</option>
                                <option value="Account Manager">Account Manager Only (அக்கவுண்டன்ட்)</option>
                                <option value="Site Manager">Site Manager Only (தள மேலாளர்)</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select class="form-select form-select-sm" id="cashTypeFilter">
                                <option value="">All Transaction Types (அனைத்து வகை)</option>
                                @foreach ($uniqueTypes as $tType)
                                    <option value="{{ $tType }}">{{ $tType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 w-100" id="historyFilterResetBtn"
                                title="Reset filters">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </button>
                        </div>
                        <div class="col-6 col-md-1 text-md-end text-start">
                            <span class="small text-muted" id="cashCountBadge">Showing <strong class="text-dark"
                                    id="visibleCashCount">{{ $totalTxnCount }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle mb-0" id="siteCashTable">
                        <thead>
                            <tr>
                                <th style="min-width: 110px;">Date &amp; Time</th>
                                <th style="min-width: 170px;">Spender / Handled By (யார் செலவு செய்தது)</th>
                                <th style="min-width: 160px;">Type (வகை)</th>
                                <th style="min-width: 160px;">Party / Supplier / Payee</th>
                                <th style="min-width: 200px;">Description / Notes (விவரம்)</th>
                                <th style="min-width: 120px;">Bill / Ref No</th>
                                <th class="text-end" style="min-width: 120px;">Inflow (+)</th>
                                <th class="text-end" style="min-width: 120px;">Outflow (-)</th>
                                <th class="text-center" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="siteCashTableBody">
                            @foreach ($txnsList as $txn)
                                @php
                                    $type = (string) ($txn['type'] ?? '');
                                    $badgeClass = match ($type) {
                                        'Client Collection', 'Client Advance', 'Client Receipt (வரவு)' => 'bg-success-subtle text-success border border-success-subtle',
                                        'Project Cash Received' => 'bg-success text-white',
                                        'Project Cash Transfer to Site' => 'bg-info-subtle text-info border border-info-subtle',
                                        'Material Purchase', 'Supplier Payment', 'Tool Rental', 'Daily Wage Payment', 'Other Expense', 'Project Expense (செலவு)' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'Balance Returned' => 'bg-warning-subtle text-dark border border-warning-subtle',
                                        default => 'bg-primary-subtle text-primary border border-primary-subtle',
                                    };

                                    $formattedDate = $txn['date_formatted'] ?? 'N/A';
                                    $timeStr = $txn['time'] ?? '';
                                    $partyName = $txn['party_name'] ?? '-';
                                    $descStr = $txn['description'] ?? '';
                                    $billNo = $txn['bill_no'] ?? 'N/A';
                                    $handlerRole = $txn['handler_role'] ?? 'Site Manager';
                                    $handlerName = $txn['handler_name'] ?? '-';
                                    $sourceLabel = $txn['source_label'] ?? 'Site Cash';

                                    $itemsText = '';
                                    if (!empty($txn['items']) && is_array($txn['items'])) {
                                        foreach ($txn['items'] as $it) {
                                            $itemsText .= ' ' . ($it['qty'] ?? '') . ' ' . ($it['unit'] ?? '') . ' ' . ($it['materialName'] ?? ($it['material'] ?? ''));
                                        }
                                    }
                                    $haystack = strtolower(trim($type . ' ' . $partyName . ' ' . $descStr . ' ' . $billNo . ' ' . $formattedDate . ' ' . $timeStr . ' ' . $handlerRole . ' ' . $handlerName . ' ' . $sourceLabel . ' ' . $itemsText));
                                @endphp
                                <tr data-id="{{ $txn['id'] }}"
                                    data-handler="{{ $handlerRole }}"
                                    data-type="{{ $type }}"
                                    data-supplier="{{ $partyName }}"
                                    data-desc="{{ $descStr }}"
                                    data-bill="{{ $billNo }}"
                                    data-search="{{ $haystack }}">
                                    <td class="small">
                                        <div class="fw-bold text-dark">{{ $formattedDate }}</div>
                                        @if($timeStr)
                                            <span class="text-muted" style="font-size:0.75rem;">{{ $timeStr }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            @if ($handlerRole === 'Account Manager')
                                                <span class="badge bg-primary text-white border border-primary px-2 py-1" style="font-size:0.75rem;">
                                                    <i class="bi bi-briefcase me-1"></i>Account Manager
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark border border-warning px-2 py-1" style="font-size:0.75rem;">
                                                    <i class="bi bi-person-badge me-1"></i>Site Manager
                                                </span>
                                            @endif
                                            <div class="small fw-semibold text-dark mt-1" style="font-size:0.8rem;">{{ $handlerName }}</div>
                                            <small class="text-muted" style="font-size:0.72rem;">({{ $sourceLabel }})</small>
                                        </div>
                                    </td>
                                    <td><span class="badge {{ $badgeClass }}">{{ $type }}</span></td>
                                    <td>
                                        @if ($partyName && $partyName !== '-')
                                            <div>
                                                <span class="fw-semibold text-primary">
                                                    <i class="bi bi-person-fill me-1"></i>{{ $partyName }}
                                                </span>
                                                @if (!empty($txn['items']) && is_array($txn['items']))
                                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                                        @foreach ($txn['items'] as $it)
                                                            <span class="badge bg-light text-dark border py-1 px-2" style="font-size:0.72rem;">
                                                                <i class="bi bi-box-seam text-secondary me-1"></i>{{ $it['qty'] ?? 1 }}
                                                                {{ $it['unit'] ?? 'Unit' }} {{ $it['materialName'] ?? ($it['material'] ?? '') }}
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
                                        @if ($descStr)
                                            @php
                                                $cleanDesc = $descStr;
                                                if (str_contains($cleanDesc, 'Painter & Electrician daily wages')) {
                                                    $cleanDesc = 'Painter & Electrician daily wages';
                                                }
                                            @endphp
                                            <div class="text-dark small text-truncate" style="max-width: 250px;" title="{{ $descStr }}">
                                                {{ \Illuminate\Support\Str::limit($cleanDesc, 45) }}
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-monospace text-secondary small">{{ $billNo }}</span>
                                        @if (!empty($txn['receipt_image']))
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"
                                                title="Receipt image attached">
                                                <i class="bi bi-image"></i>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-success">
                                        {{ (float) ($txn['in_amount'] ?? 0) > 0 ? '+₹' . number_format((float) $txn['in_amount'], 2) : '-' }}
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ (float) ($txn['out_amount'] ?? 0) > 0 ? '−₹' . number_format((float) $txn['out_amount'], 2) : '-' }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ $txn['view_url'] }}"
                                            class="btn btn-outline-primary btn-sm px-2 py-1"
                                            title="View Details (விவரங்களை பார்க்க)">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
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
                            Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark" id="pageEnd">{{ min(10, $totalTxnCount) }}</strong> of <strong class="text-dark" id="pageTotal">{{ $totalTxnCount }}</strong> transactions
                        </span>
                    </div>
                    <nav aria-label="Site cash transactions pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                            <!-- Dynamic page links rendered by JS -->
                        </ul>
                    </nav>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-wallet2 fs-2 d-block mb-2 text-secondary"></i>
                    <div class="fw-semibold">No Cash Transactions Recorded for this Site</div>
                    <small class="text-muted">Account Manager project disbursements and Site Manager petty cash records will show here once recorded.</small>
                    <div class="mt-3 d-flex justify-content-center gap-2">
                        <a href="{{ route('cash-transfer.index') }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-arrow-left-right me-1"></i> Cash Transfer
                        </a>
                        <a href="{{ route('site-cash.index') }}" class="btn btn-sm btn-deiveni">
                            <i class="bi bi-plus-circle me-1"></i> Record Site Cash
                        </a>
                    </div>
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            if ($('#siteCashTableBody').length === 0) {
                return;
            }

            let currentPage = 1;
            let perPage = parseInt($('#perPageSelect').val()) || 10;

            function getMatchingRows() {
                const query = $('#cashSearchInput').val().toLowerCase().trim();
                const selectedType = $('#cashTypeFilter').val();
                const selectedHandler = $('#cashHandlerFilter').val();
                const matching = [];

                $('#siteCashTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyCashRow' || rowId === 'noResultsRow') return;

                    const searchData = String($(this).data('search') || '').toLowerCase();
                    const type = String($(this).data('type') || '');
                    const handler = String($(this).data('handler') || '');

                    const matchQuery = !query || searchData.includes(query);
                    const matchType = !selectedType || type === selectedType;
                    const matchHandler = !selectedHandler || handler === selectedHandler;

                    if (matchQuery && matchType && matchHandler) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#siteCashTableBody tr').not('#emptyCashRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#siteCashTableBody tr').not('#emptyCashRow, #noResultsRow').hide();

                if (totalCount === 0) {
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#cashCountBadge').html('Showing <strong class="text-dark">0</strong> records');
                    return;
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#cashCountBadge').html(`Showing <strong class="text-dark">0</strong> of <strong class="text-dark">${totalCount}</strong> records`);
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

                $('#cashCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> records`);

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

            // Filter on search or type change
            $('#cashSearchInput, #cashTypeFilter, #cashHandlerFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#cashSearchInput').val('');
                $('#cashTypeFilter').val('');
                $('#cashHandlerFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Initial run
            updatePagination();
        });
    </script>

    {{-- ===== TAB PAGINATION SCRIPT ===== --}}
    <script>
        $(document).ready(function () {

            // Generic tab table paginator
            // configs: { bodyId, navId, summaryId, perPageSelectId }
            function makeTabPaginator(bodyId, navId, summaryId, perPageSelectId) {
                let page = 1;
                let perPage = parseInt($('#' + perPageSelectId).val()) || 5;

                function allRows() {
                    return $('#' + bodyId + ' tr');
                }

                function renderPages(total) {
                    const nav = $('#' + navId);
                    nav.empty();
                    if (total <= 1) return;

                    const prevDis = page === 1 ? 'disabled' : '';
                    nav.append(`<li class="page-item ${prevDis}"><button class="page-link" type="button" data-p="${page - 1}" aria-label="Prev"><i class="bi bi-chevron-left"></i></button></li>`);

                    for (let p = 1; p <= total; p++) {
                        if (total > 7 && p !== 1 && p !== total && Math.abs(p - page) > 1) {
                            if ((p === 2 && page > 3) || (p === total - 1 && page < total - 2)) {
                                nav.append(`<li class="page-item disabled"><span class="page-link border-0 bg-transparent">&hellip;</span></li>`);
                            }
                            continue;
                        }
                        const act = p === page ? 'active' : '';
                        nav.append(`<li class="page-item ${act}"><button class="page-link" type="button" data-p="${p}">${p}</button></li>`);
                    }

                    const nextDis = page === total ? 'disabled' : '';
                    nav.append(`<li class="page-item ${nextDis}"><button class="page-link" type="button" data-p="${page + 1}" aria-label="Next"><i class="bi bi-chevron-right"></i></button></li>`);
                }

                function update() {
                    const rows = allRows();
                    const total = rows.length;
                    const totalPages = Math.ceil(total / perPage) || 1;
                    if (page > totalPages) page = totalPages;
                    if (page < 1) page = 1;

                    rows.hide();
                    const start = (page - 1) * perPage;
                    const end = Math.min(start + perPage, total);
                    rows.slice(start, end).show();

                    if (total === 0) {
                        $('#' + summaryId).text('');
                        return;
                    }

                    $('#' + summaryId).html(`Showing <strong>${start + 1}</strong>&ndash;<strong>${end}</strong> of <strong>${total}</strong>`);
                    renderPages(totalPages);
                }

                // per page change
                $('#' + perPageSelectId).on('change', function () {
                    perPage = parseInt($(this).val()) || 5;
                    page = 1;
                    update();
                });

                // page click via event delegation on nav
                $(document).on('click', '#' + navId + ' .page-link', function () {
                    const p = parseInt($(this).data('p'));
                    if (p && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
                        page = p;
                        update();
                    }
                });

                // initial
                update();
            }

            // Labour tab
            if ($('#labourTabBody tr').length > 0) {
                makeTabPaginator('labourTabBody', 'labourTabNav', 'labourTabSummary', 'labourPerPage');
            }

            // Materials tab
            if ($('#materialsTabBody tr').length > 0) {
                makeTabPaginator('materialsTabBody', 'materialsTabNav', 'materialsTabSummary', 'materialsPerPage');
            }

            // Transfers tab
            if ($('#transfersTabBody tr').length > 0) {
                makeTabPaginator('transfersTabBody', 'transfersTabNav', 'transfersTabSummary', 'transfersPerPage');
            }
        });
    </script>
@endpush