@extends('layouts.app')

@section('title', $supplier->name . ' - Supplier Details')
@section('page_title', 'Supplier Details (சப்ளையர் முழு விவரம்)')

@section('content')
    <div class="container-fluid px-0">
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('suppliers.index') }}"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                        title="Back to Suppliers Master">
                        <i class="bi bi-arrow-left me-1"></i> Back to Suppliers
                    </a>
                    <div class="vr d-none d-md-block"></div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold mb-0 text-dark">{{ $supplier->name }}</h5>
                            @if ($supplier->status === 'Active')
                                <span class="badge bg-success-subtle text-success">Active (செயலில்)</span>
                            @else
                                <span class="badge bg-secondary-subtle text-muted">Inactive (நிறுத்தப்பட்டது)</span>
                            @endif
                        </div>
                        <small class="text-muted">Supplier ID: <span
                                class="font-monospace text-primary fw-semibold">#SUP-{{ str_pad($supplier->id, 4, '0', STR_PAD_LEFT) }}</span></small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="tel:{{ $supplier->phone }}"
                        class="btn btn-outline-primary btn-sm d-inline-flex align-items-center">
                        <i class="bi bi-telephone-fill me-1"></i> Call Supplier
                    </a>
                </div>
            </div>
        </div>


        <!-- Quick Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Contact Person</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1">{{ $supplier->contact_person ?: '—' }}</h6>
                        </div>
                        <div class="bg-primary-subtle text-primary p-2 rounded-3">
                            <i class="bi bi-person-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Phone Number</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1 font-monospace">{{ $supplier->phone }}</h6>
                        </div>
                        <div class="bg-success-subtle text-success p-2 rounded-3">
                            <i class="bi bi-telephone-fill fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">GSTIN / Tax No</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1 font-monospace">
                                {{ $supplier->gst_number ?: 'Not Registered' }}</h6>
                        </div>
                        <div class="bg-warning-subtle text-warning p-2 rounded-3">
                            <i class="bi bi-receipt-cutoff fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card-custom p-3 bg-white h-100 border-start border-4 border-info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold d-block text-uppercase">Materials Supplied</span>
                            <h6 class="fw-bold mb-0 text-dark mt-1">{{ $supplier->materials->count() }} Items</h6>
                        </div>
                        <div class="bg-info-subtle text-info p-2 rounded-3">
                            <i class="bi bi-boxes fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Business & Contact Profile -->
            <div class="col-12 col-lg-4">
                <div class="card-custom bg-white p-3 h-100">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex align-items-center">
                        <i class="bi bi-building-fill text-primary me-2"></i> Supplier Information (விவரங்கள்)
                    </h6>

                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">Business / Shop:</span>
                            <span class="fw-semibold text-dark text-end">{{ $supplier->name }}</span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">Contact Person:</span>
                            <span class="fw-semibold text-dark text-end">{{ $supplier->contact_person ?: '—' }}</span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">Phone Number:</span>
                            <a href="tel:{{ $supplier->phone }}"
                                class="fw-semibold text-primary text-decoration-none text-end font-monospace">
                                {{ $supplier->phone }}
                            </a>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">GST Number:</span>
                            <span
                                class="fw-semibold font-monospace text-dark text-end">{{ $supplier->gst_number ?: '—' }}</span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">Status:</span>
                            <span>
                                @if ($supplier->status === 'Active')
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-muted">Inactive</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item px-0 py-2">
                            <span class="text-muted d-block mb-1">Address / Location:</span>
                            <div class="p-2 bg-light rounded text-dark fw-medium">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                {{ $supplier->address ?: 'Address not provided' }}
                            </div>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">Registered Date:</span>
                            <span
                                class="text-secondary">{{ $supplier->created_at ? $supplier->created_at->format('d M Y, h:i A') : '—' }}</span>
                        </li>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-start">
                            <span class="text-muted">Last Updated:</span>
                            <span
                                class="text-secondary">{{ $supplier->updated_at ? $supplier->updated_at->format('d M Y, h:i A') : '—' }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Materials, Quality Grades & Rates -->
            <div class="col-12 col-lg-8">
                <div class="card-custom bg-white p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="bi bi-tag-fill text-primary me-2"></i> Materials Supplied, Grade &amp; Rates (வழங்கும்
                            பொருட்கள் &amp; விலை பட்டியல்)
                        </h6>
                        <span class="badge bg-light border text-secondary">{{ $supplier->materials->count() }} items
                            mapped</span>
                    </div>

                    @if ($supplier->materials->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>Material Name (பொருள்)</th>
                                        <th>Unit (அலகு)</th>
                                        <th>Quality Grade (தரம்)</th>
                                        <th class="text-end">Unit Rate / Price (விலை)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supplier->materials as $idx => $mat)
                                        <tr>
                                            <td class="text-muted fw-semibold">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $mat->name_en }}</div>
                                                @if ($mat->name_ta)
                                                    <small class="ta-text text-muted">{{ $mat->name_ta }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-light border text-dark">{{ $mat->unit?->name_en ?? (string) $mat->unit }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $grade = $mat->pivot->grade ?? '1st Quality';
                                                    $gradeColor = match ($grade) {
                                                        '1st Quality' => 'primary',
                                                        'Premium' => 'success',
                                                        'Standard' => 'info',
                                                        '2nd Quality' => 'warning',
                                                        '3rd Quality' => 'secondary',
                                                        default => 'secondary',
                                                    };
                                                @endphp
                                                <span
                                                    class="badge bg-{{ $gradeColor }}-subtle text-{{ $gradeColor }} border border-{{ $gradeColor }}-subtle">
                                                    {{ $grade }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold text-success fs-6">
                                                    ₹{{ number_format((float) ($mat->pivot->price ?? 0), 2) }}
                                                </span>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">per
                                                    {{ $mat->unit?->name_en ?? (string) $mat->unit }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-box-seam display-5 d-block mb-2 text-secondary opacity-50"></i>
                            <h6>No Materials Mapped (பொருட்கள் சேர்க்கப்படவில்லை)</h6>
                            <p class="small mb-3">No materials or pricing have been registered for this supplier yet.</p>
                            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Edit Supplier to Add Materials
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection