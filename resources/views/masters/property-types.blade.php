@extends('layouts.app')

@section('title', 'Property Type Master')
@section('page_title', 'Property Type Master ')

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
    </style>
@endpush

@section('content')
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Property Types & Units Directory</h5>
                    <p class="text-muted small mb-0">Manage owned and leased commercial shops, residential complexes,
                        warehouse sheds, floor types, tenants, and monthly rent schedules.</p>
</div>
                <div class="d-flex gap-2">
                    <a href="{{ url('/properties') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-building me-1"></i> Property Master
</a>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#propertyTypeModal"
                        id="addPropertyTypeBtn">
                        <i class="bi bi-building-add me-1"></i> Add Property Type
</button>
</div>
</div>
</div>

        <!-- Stat Highlights Row -->
        <div class="row g-3 mb-3 mb-md-4">
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-buildings fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Total Units / Types</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalProps">{{ $stats['total_properties'] ?? 0 }}</h4>
</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="bi bi-door-closed-fill fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Active Units</div>
                        <h4 class="fw-bold mb-0 text-success" id="statOccupiedUnits">{{ $stats['active_properties'] ?? 0 }}
</h4>
</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="bi bi-cash-stack fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Monthly Expected Rent</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalRent">
                            ₹{{ number_format($stats['monthly_expected_rent'] ?? 0) }}</h4>
</div>
</div>
</div>
            <div class="col-6 col-lg-3">
                <div class="card-stat d-flex align-items-center gap-3">
                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-safe2 fs-4"></i>
</div>
                    <div>
                        <div class="text-muted small fw-medium">Total Security Deposit</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotalDeposit">
                            ₹{{ number_format($stats['total_security_deposit'] ?? 0) }}</h4>
</div>
</div>
</div>
</div>

        <!-- Filter & Search Card -->
        <div class="card-custom p-3 mb-3 mb-md-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="propertyTypeSearchInput"
                            placeholder="Search Property, Shop/Office, Floor, Location, Tenant...">
</div>
</div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="propertyTypeFilter">
                        <option value="">All Types</option>
                        <option value="Commercial Shop">Commercial Shop</option>
                        <option value="Residential Flat">Residential Flat</option>
                        <option value="Office Space">Office Space</option>
                        <option value="Warehouse / Shed">Warehouse / Shed</option>
                    </select>
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="propertyStatusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
</div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
                <div class="col-6 col-md-2 text-md-end">
                    <span class="small text-muted" id="propertyTypeCountBadge">Showing <strong class="text-dark"
                            id="visiblePropertyTypeCount">{{ $propertyTypes->count() }}</strong> units</span>
</div>
</div>
</div>

        <!-- Property Types Table -->
        <div class="card-custom overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle" id="propertyTypesTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Property & Location</th>
                            <th>Property Type & Floor</th>
                            <th>Current Tenant Details</th>
                            <th>Monthly Rent</th>
                            <th>Security Advance</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="propertyTypesTableBody">
                        @forelse ($propertyTypes as $index => $item)
                            @php
                                $iconClass = match ($item->property_type) {
                                    'Commercial Shop' => 'bi bi-shop',
                                    'Residential Flat' => 'bi bi-house-door',
                                    'Office Space' => 'bi bi-briefcase',
                                    'Warehouse / Shed' => 'bi bi-box-seam',
                                    default => 'bi bi-building',
                                };
                                $iconWrapperClass = match ($item->property_type) {
                                    'Commercial Shop' => 'bg-primary-subtle text-primary',
                                    'Residential Flat' => 'bg-success-subtle text-success',
                                    'Office Space' => 'bg-warning-subtle text-warning',
                                    'Warehouse / Shed' => 'bg-secondary-subtle text-secondary',
                                    default => 'bg-primary-subtle text-primary',
                                };
                                $badgeClass = match ($item->property_type) {
                                    'Commercial Shop' => 'bg-secondary-subtle text-dark border',
                                    'Residential Flat' => 'bg-info-subtle text-info border',
                                    'Office Space' => 'bg-warning-subtle text-dark border',
                                    'Warehouse / Shed' => 'bg-secondary-subtle text-dark border',
                                    default => 'bg-secondary-subtle text-dark border',
                                };
                                $rentClass = $item->status === 'Active' ? 'text-success' : 'text-muted';
                            @endphp
                            <tr data-id="{{ $item->id }}" data-property-id="{{ $item->property_id }}"
                                data-name="{{ $item->property_name }}"
                                data-shop-office="{{ $item->shop_home_office_name ?? '' }}"
                                data-type="{{ $item->property_type }}" data-floor="{{ $item->floor_type ?? '' }}"
                                data-location="{{ $item->location }}" data-tenant="{{ $item->tenant_name ?? '' }}"
                                data-phone="{{ $item->tenant_phone ?? '' }}" data-rent="{{ (int) $item->monthly_rent }}"
                                data-advance="{{ (int) $item->security_deposit }}" data-status="{{ $item->status }}">
                                <td class="text-muted fw-semibold serial-cell">{{ sprintf('%02d', $index + 1) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-wrapper {{ $iconWrapperClass }} prop-icon-badge"
                                            style="width:36px; height:36px; font-size:1rem;">
                                            <i class="{{ $iconClass }}"></i>
</div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 row-prop-title">{{ $item->property_name }}
</div>
                                            <small class="text-muted row-location">
                                                <i class="bi bi-geo-alt me-1"></i>{{ $item->location }}
                                                @if ($item->shop_home_office_name)
                                                    &bull; <span class="ta-badge">{{ $item->shop_home_office_name }}</span>
                                                @endif
</small>
</div>
</div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <span
                                            class="badge {{ $badgeClass }} row-prop-type w-fit">{{ $item->property_type }}</span>
                                        @if ($item->floor_type)
                                            <span
                                                class="badge bg-light text-secondary border border-secondary-subtle row-prop-floor w-fit"
                                                style="font-size: 0.72rem;">
                                                <i class="bi bi-layers me-1"></i>{{ $item->floor_type }}
</span>
                                        @else
                                            <span
                                                class="badge bg-light text-secondary border border-secondary-subtle row-prop-floor w-fit d-none"
                                                style="font-size: 0.72rem;"></span>
                                        @endif
</div>
                                </td>
                                <td>
                                    @if ($item->tenant_name)
                                        <div
                                            class="fw-semibold {{ $item->status === 'Active' ? 'text-dark' : 'text-warning' }} row-tenant-name">
                                            <i class="bi bi-person-fill text-muted me-1"></i>{{ $item->tenant_name }}
</div>
                                        <small class="text-muted row-tenant-phone">
                                            <i class="bi bi-telephone text-muted me-1"></i>{{ $item->tenant_phone ?: '—' }}
</small>
                                    @else
                                        <div class="fw-semibold text-warning row-tenant-name">
                                            <i class="bi bi-exclamation-circle text-warning me-1"></i>Vacant 
</div>
                                        <small class="text-muted row-tenant-phone">—</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold {{ $rentClass }} fs-6 row-rent">
                                        ₹{{ number_format($item->monthly_rent) }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark row-advance">
                                        ₹{{ number_format($item->security_deposit) }}</div>
                                    <small class="text-muted">Security Deposit</small>
                                </td>
                                <td>
                                    @if ($item->status === 'Active')
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>
                                    @else
                                        <span
                                            class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item edit-property-type-btn" type="button"><i
                                                        class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            <li><button class="dropdown-item text-danger delete-property-type-btn"
                                                    type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button>
                                            </li>
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyPropertyTypesRow">
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-buildings fs-1 d-block mb-2 text-secondary"></i>
                                    No property types recorded yet. Click <strong>Add Property Type</strong> to register
                                    one.
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsRow" class="d-none">
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                No matching property types found for your search or filter criteria.
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
                        <option value="5" selected>5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted ms-md-2" id="paginationSummary">
                        Showing <strong class="text-dark" id="pageStart">1</strong> &minus; <strong class="text-dark"
                            id="pageEnd">{{ min(5, $propertyTypes->count()) }}</strong> of <strong class="text-dark"
                            id="pageTotal">{{ $propertyTypes->count() }}</strong> units
</span>
</div>
                <nav aria-label="Property type table pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1" id="paginationNav">
                        <!-- Dynamic page links rendered by JS -->
                    </ul>
                </nav>
</div>
</div>

</div>

    <!-- Property Type Add / Edit Modal -->
    <div class="modal fade" id="propertyTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="propertyTypeModalTitle">Add Property Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="propertyTypeForm" novalidate>
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="propertyTypeEditId">
                        <div class="row g-3">
                            <!-- Select Property from Property Master -->
                            <div class="col-12 col-md-7">
                                <label class="form-label small fw-semibold" for="modalPropertyId">Select Property
 <span class="text-danger">*</span></label>
                                <select class="form-select" id="modalPropertyId" name="property_id" required>
                                    <option value="">-- Select Property --</option>
                                    @foreach ($propertyList as $prop)
                                        <option value="{{ $prop->id }}">{{ $prop->property_name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errPropertyId"></div>
</div>

                            <div class="col-12 col-md-5">
                                <label class="form-label small fw-semibold" for="modalShopOfficeName">Shop / Home / Office
                                    Name</label>
                                <input type="text" class="form-control" id="modalShopOfficeName"
                                    name="shop_home_office_name" placeholder="e.g. Shop 1 / Flat 2B / Unit 4 / Shed 3">
                                <div class="invalid-feedback" id="errShopOfficeName"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalPropType">Property Type  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="modalPropType" required>
                                    <option value="Commercial Shop">Commercial Shop</option>
                                    <option value="Residential Flat">Residential Flat</option>
                                    <option value="Office Space">Office Space</option>
                                    <option value="Warehouse / Shed">Warehouse / Shed</option>
                                </select>
                                <div class="invalid-feedback" id="errPropType"></div>
</div>

                            <!-- Floor Type input (Task 5) -->
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalFloorType">Floor Type</label>
                                <input type="text" class="form-control" id="modalFloorType"
                                    placeholder="e.g. First Floor (FF), Ground Floor (GF), 2nd Floor (2F)">
                                <div class="invalid-feedback" id="errFloorType"></div>
</div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="modalPropLocation">Location / Address
 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modalPropLocation"
                                    placeholder="e.g. Medavakkam Main Road, Chennai" required>
                                <div class="invalid-feedback" id="errPropLocation"></div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalTenantName">Current Tenant Name
</label>
                                <input type="text" class="form-control" id="modalTenantName"
                                    placeholder="e.g. M/s Royal Travels (or leave blank if vacant)">
                                <div class="invalid-feedback" id="errTenantName"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="modalTenantPhone">Tenant Contact
                                    Phone</label>
                                <input type="text" class="form-control" id="modalTenantPhone"
                                    placeholder="e.g. +91 98410 11223">
                                <div class="invalid-feedback" id="errTenantPhone"></div>
</div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="modalMonthlyRent">Monthly Rent  <span class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="modalMonthlyRent" placeholder="10000"
                                        min="0" step="0.01" required>
                                    <div class="invalid-feedback" id="errMonthlyRent"></div>
</div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="modalAdvanceDeposit">Security Advance
</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="modalAdvanceDeposit" placeholder="100000"
                                        min="0" step="0.01">
                                    <div class="invalid-feedback" id="errAdvanceDeposit"></div>
</div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="modalStatus">Status  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="modalStatus" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errStatus"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="savePropertyTypeBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="savePropertyTypeSpinner"
                                role="status" aria-hidden="true"></span>
                            <span id="savePropertyTypeBtnText">Save Property Type</span>
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            });

            const propertyTypeModal = new bootstrap.Modal(document.getElementById('propertyTypeModal'));

            function applyStats(stats) {
                if (!stats) return;
                if (stats.total_properties !== undefined) $('#statTotalProps').text(stats.total_properties);
                if (stats.active_properties !== undefined) $('#statOccupiedUnits').text(stats.active_properties);
                if (stats.monthly_expected_rent !== undefined) $('#statTotalRent').text('₹' + Number(stats.monthly_expected_rent).toLocaleString('en-IN'));
                if (stats.total_security_deposit !== undefined) $('#statTotalDeposit').text('₹' + Number(stats.total_security_deposit).toLocaleString('en-IN'));
            }

            function recalculateStatsLocal() {
                let totalProps = 0;
                let active = 0;
                let totalRent = 0;
                let totalDeposit = 0;

                $('#propertyTypesTableBody tr[data-name]').each(function () {
                    totalProps++;
                    const status = $(this).data('status');
                    const rent = parseFloat($(this).data('rent')) || 0;
                    const advance = parseFloat($(this).data('advance')) || 0;

                    totalDeposit += advance;

                    if (status === 'Active') {
                        active++;
                        totalRent += rent;
                    }
                });

                $('#statTotalProps').text(totalProps);
                $('#statOccupiedUnits').text(active);
                $('#statTotalRent').text('₹' + totalRent.toLocaleString('en-IN'));
                $('#statTotalDeposit').text('₹' + totalDeposit.toLocaleString('en-IN'));
            }

            function renumberRows() {
                $('#propertyTypesTableBody tr[data-name]').each(function (index) {
                    $(this).find('.serial-cell').text(String(index + 1).padStart(2, '0'));
                });
            }

            function getPropTheme(type) {
                switch (type) {
                    case 'Commercial Shop':
                        return {
                            icon: 'bi bi-shop',
                            wrapper: 'bg-primary-subtle text-primary',
                            badge: 'bg-secondary-subtle text-dark border'
                        };
                    case 'Residential Flat':
                        return {
                            icon: 'bi bi-house-door',
                            wrapper: 'bg-success-subtle text-success',
                            badge: 'bg-info-subtle text-info border'
                        };
                    case 'Office Space':
                        return {
                            icon: 'bi bi-briefcase',
                            wrapper: 'bg-warning-subtle text-warning',
                            badge: 'bg-warning-subtle text-dark border'
                        };
                    case 'Warehouse / Shed':
                        return {
                            icon: 'bi bi-box-seam',
                            wrapper: 'bg-secondary-subtle text-secondary',
                            badge: 'bg-secondary-subtle text-dark border'
                        };
                    default:
                        return {
                            icon: 'bi bi-building',
                            wrapper: 'bg-primary-subtle text-primary',
                            badge: 'bg-secondary-subtle text-dark border'
                        };
                }
            }

            function clearFormErrors() {
                $('#propertyTypeForm .is-invalid').removeClass('is-invalid');
                $('#propertyTypeForm .invalid-feedback').text('').hide();
            }

            function displayFormErrors(errors) {
                clearFormErrors();
                if (errors.property_id) {
                    $('#modalPropertyId').addClass('is-invalid');
                    $('#errPropertyId').text(errors.property_id[0]).show();
                }
                if (errors.property_name) {
                    $('#modalPropertyId').addClass('is-invalid');
                    $('#errPropertyId').text(errors.property_name[0]).show();
                }
                if (errors.shop_home_office_name) {
                    $('#modalShopOfficeName').addClass('is-invalid');
                    $('#errShopOfficeName').text(errors.shop_home_office_name[0]).show();
                }
                if (errors.property_type) {
                    $('#modalPropType').addClass('is-invalid');
                    $('#errPropType').text(errors.property_type[0]).show();
                }
                if (errors.floor_type) {
                    $('#modalFloorType').addClass('is-invalid');
                    $('#errFloorType').text(errors.floor_type[0]).show();
                }
                if (errors.location) {
                    $('#modalPropLocation').addClass('is-invalid');
                    $('#errPropLocation').text(errors.location[0]).show();
                }
                if (errors.tenant_name) {
                    $('#modalTenantName').addClass('is-invalid');
                    $('#errTenantName').text(errors.tenant_name[0]).show();
                }
                if (errors.tenant_phone) {
                    $('#modalTenantPhone').addClass('is-invalid');
                    $('#errTenantPhone').text(errors.tenant_phone[0]).show();
                }
                if (errors.monthly_rent) {
                    $('#modalMonthlyRent').addClass('is-invalid');
                    $('#errMonthlyRent').text(errors.monthly_rent[0]).show();
                }
                if (errors.security_deposit) {
                    $('#modalAdvanceDeposit').addClass('is-invalid');
                    $('#errAdvanceDeposit').text(errors.security_deposit[0]).show();
                }
                if (errors.status) {
                    $('#modalStatus').addClass('is-invalid');
                    $('#errStatus').text(errors.status[0]).show();
                }
            }

            $('#propertyTypeForm input, #propertyTypeForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).closest('.col-12, .col-md-6, .col-md-7, .col-md-5, .col-md-4').find('.invalid-feedback').text('').hide();
            });

            let currentPage = 1;
            let perPage = 5;

            function getMatchingRows() {
                const query = $('#propertyTypeSearchInput').val().toLowerCase().trim();
                const selectedType = $('#propertyTypeFilter').val();
                const selectedStatus = $('#propertyStatusFilter').val();
                const matching = [];

                $('#propertyTypesTableBody tr').each(function () {
                    const rowId = $(this).attr('id');
                    if (rowId === 'emptyPropertyTypesRow' || rowId === 'noResultsRow') return;

                    const name = String($(this).data('name') || '');
                    const shopOffice = String($(this).data('shop-office') || '');
                    const type = String($(this).data('type') || '');
                    const floor = String($(this).data('floor') || '');
                    const location = String($(this).data('location') || '');
                    const tenant = String($(this).data('tenant') || '');
                    const phone = String($(this).data('phone') || '');
                    const status = String($(this).data('status') || '');

                    const haystack = `${name} ${shopOffice} ${type} ${floor} ${location} ${tenant} ${phone}`.toLowerCase();

                    const matchQuery = !query || haystack.includes(query);
                    const matchType = !selectedType || type === selectedType;
                    const matchStatus = !selectedStatus || status === selectedStatus;

                    if (matchQuery && matchType && matchStatus) {
                        matching.push(this);
                    }
                });

                return matching;
            }

            function updatePagination() {
                const matchingRows = getMatchingRows();
                const totalCount = $('#propertyTypesTableBody tr').not('#emptyPropertyTypesRow, #noResultsRow').length;
                const filteredCount = matchingRows.length;
                const totalPages = Math.ceil(filteredCount / perPage) || 1;

                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Hide all data rows initially
                $('#propertyTypesTableBody tr').not('#emptyPropertyTypesRow, #noResultsRow').hide();

                // Handle empty states
                if (totalCount === 0) {
                    if ($('#emptyPropertyTypesRow').length === 0) {
                        $('#propertyTypesTableBody').html(`
                                <tr id="emptyPropertyTypesRow">
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="bi bi-buildings fs-1 d-block mb-2 text-secondary"></i>
                                        No property types recorded yet. Click <strong>Add Property Type</strong> to register one.
                                    </td>
                                </tr>
                            `);
                    }
                    $('#emptyPropertyTypesRow').show();
                    $('#noResultsRow').addClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#propertyTypeCountBadge').html('Showing <strong class="text-dark">0</strong> units');
                    return;
                } else {
                    $('#emptyPropertyTypesRow').hide();
                }

                if (filteredCount === 0) {
                    $('#noResultsRow').removeClass('d-none');
                    $('#tablePaginationContainer').addClass('d-none');
                    $('#propertyTypeCountBadge').html('Showing <strong class="text-dark">0</strong> of <strong class="text-dark">' + totalCount + '</strong> units');
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

                // Re-index serial cell across all matching rows
                matchingRows.forEach(function (row, idx) {
                    $(row).find('.serial-cell').text(String(idx + 1).padStart(2, '0'));
                });

                // Update summary footer text
                let summaryHtml = `Showing <strong class="text-dark">${startIndex + 1}</strong> &minus; <strong class="text-dark">${endIndex}</strong> of <strong class="text-dark">${filteredCount}</strong>`;
                if (filteredCount !== totalCount) {
                    summaryHtml += ` <span class="text-muted">(filtered from ${totalCount})</span>`;
                }
                summaryHtml += ' units';
                $('#paginationSummary').html(summaryHtml);

                $('#propertyTypeCountBadge').html(`Showing <strong class="text-dark">${filteredCount}</strong> of <strong class="text-dark">${totalCount}</strong> units`);

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
                perPage = parseInt($(this).val()) || 5;
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

            // Filter on search, type or status change
            $('#propertyTypeSearchInput, #propertyTypeFilter, #propertyStatusFilter').on('input change', function () {
                currentPage = 1;
                updatePagination();
            });

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#propertyTypeSearchInput').val('');
                $('#propertyTypeFilter').val('');
                $('#propertyStatusFilter').val('');
                currentPage = 1;
                updatePagination();
            });

            // Open Modal for Add
            $('#addPropertyTypeBtn').on('click', function () {
                $('#propertyTypeModalTitle').text('Add Property Type ');
                $('#savePropertyTypeBtnText').text('Save Property Type');
                $('#propertyTypeEditId').val('');
                $('#propertyTypeForm')[0].reset();
                $('#modalPropertyId').val('');
                $('#modalPropType').val('Commercial Shop');
                $('#modalFloorType').val('');
                $('#modalStatus').val('Active');
                $('#modalShopOfficeName').val('');
                clearFormErrors();
            });

            // Open Modal for Edit
            $(document).on('click', '.edit-property-type-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const propertyId = tr.data('property-id');
                const name = tr.data('name');
                const shopOffice = tr.data('shop-office');
                const type = tr.data('type');
                const floor = tr.data('floor');
                const location = tr.data('location');
                const tenant = tr.data('tenant');
                const phone = tr.data('phone');
                const rent = tr.data('rent');
                const advance = tr.data('advance');
                const status = tr.data('status');

                $('#propertyTypeModalTitle').text('Edit Property Type ');
                $('#savePropertyTypeBtnText').text('Update Property Type');
                $('#propertyTypeEditId').val(id);
                $('#modalPropertyId').val(propertyId);
                $('#modalShopOfficeName').val(shopOffice || '');
                $('#modalPropType').val(type);
                $('#modalFloorType').val(floor || '');
                $('#modalPropLocation').val(location);
                $('#modalTenantName').val(tenant || '');
                $('#modalTenantPhone').val(phone === '—' ? '' : phone);
                $('#modalMonthlyRent').val(rent);
                $('#modalAdvanceDeposit').val(advance);
                $('#modalStatus').val(status || 'Active');

                clearFormErrors();
                propertyTypeModal.show();
            });

            // Form Submit
            $('#propertyTypeForm').on('submit', function (e) {
                e.preventDefault();
                clearFormErrors();

                const editId = $('#propertyTypeEditId').val();
                const isEdit = Boolean(editId);
                const url = isEdit ? '{{ route("property-types.update", ":id") }}'.replace(':id', editId) : '{{ route("property-types.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                const postData = {
                    property_id: $('#modalPropertyId').val(),
                    shop_home_office_name: $('#modalShopOfficeName').val().trim() || null,
                    property_type: $('#modalPropType').val(),
                    floor_type: $('#modalFloorType').val().trim() || null,
                    location: $('#modalPropLocation').val().trim(),
                    tenant_name: $('#modalTenantName').val().trim() || null,
                    tenant_phone: $('#modalTenantPhone').val().trim() || null,
                    monthly_rent: $('#modalMonthlyRent').val(),
                    security_deposit: $('#modalAdvanceDeposit').val() !== '' ? $('#modalAdvanceDeposit').val() : 0,
                    status: $('#modalStatus').val()
                };

                const $btn = $('#savePropertyTypeBtn');
                const $spinner = $('#savePropertyTypeSpinner');
                const $btnText = $('#savePropertyTypeBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    method: method,
                    data: postData,
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Property Type' : 'Save Property Type');

                        if (res.success && res.data) {
                            const item = res.data;
                            const theme = getPropTheme(item.property_type);
                            const formattedRent = Number(item.monthly_rent).toLocaleString('en-IN');
                            const formattedAdvance = Number(item.security_deposit || 0).toLocaleString('en-IN');
                            const rentClass = item.status === 'Active' ? 'text-success' : 'text-muted';
                            const statusBadge = item.status === 'Active'
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle row-status">Active</span>'
                                : '<span class="badge bg-secondary-subtle text-muted border border-secondary-subtle row-status">Inactive</span>';

                            const locationSubtitle = item.shop_home_office_name
                                ? `<i class="bi bi-geo-alt me-1"></i>${item.location} &bull; <span class="ta-badge">${item.shop_home_office_name}</span>`
                                : `<i class="bi bi-geo-alt me-1"></i>${item.location}`;

                            const floorBadge = item.floor_type
                                ? `<span class="badge bg-light text-secondary border border-secondary-subtle row-prop-floor w-fit" style="font-size: 0.72rem;"><i class="bi bi-layers me-1"></i>${item.floor_type}</span>`
                                : '';

                            const tenantDisplay = item.tenant_name
                                ? `<div class="fw-semibold ${item.status === 'Active' ? 'text-dark' : 'text-warning'} row-tenant-name"><i class="bi bi-person-fill text-muted me-1"></i>${item.tenant_name}</div>
                                       <small class="text-muted row-tenant-phone"><i class="bi bi-telephone text-muted me-1"></i>${item.tenant_phone || '—'}</small>`
                                : `<div class="fw-semibold text-warning row-tenant-name"><i class="bi bi-exclamation-circle text-warning me-1"></i>Vacant</div>
                                       <small class="text-muted row-tenant-phone">—</small>`;

                            if (isEdit) {
                                const tr = $(`#propertyTypesTableBody tr[data-id="${item.id}"]`);
                                tr.data('property-id', item.property_id)
                                    .data('name', item.property_name || (item.property ? item.property.property_name : ''))
                                    .data('shop-office', item.shop_home_office_name || '')
                                    .data('type', item.property_type)
                                    .data('floor', item.floor_type || '')
                                    .data('location', item.location)
                                    .data('tenant', item.tenant_name || '')
                                    .data('phone', item.tenant_phone || '')
                                    .data('rent', item.monthly_rent)
                                    .data('advance', item.security_deposit)
                                    .data('status', item.status);

                                tr.find('.row-prop-title').text(item.property_name || (item.property ? item.property.property_name : ''));
                                tr.find('.row-location').html(locationSubtitle);
                                tr.find('.prop-icon-badge').attr('class', `stat-icon-wrapper ${theme.wrapper} prop-icon-badge`).html(`<i class="${theme.icon}"></i>`);
                                tr.find('td:nth-child(3)').html(`
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge ${theme.badge} row-prop-type w-fit">${item.property_type}</span>
                                            ${floorBadge}
</div>
                                    `);
                                tr.find('td:nth-child(4)').html(tenantDisplay);
                                tr.find('.row-rent').attr('class', `fw-bold ${rentClass} fs-6 row-rent`).text('₹' + formattedRent);
                                tr.find('.row-advance').text('₹' + formattedAdvance);
                                tr.find('.row-status').replaceWith(statusBadge);

                                showToast(res.message || `Property Type updated successfully!`, 'success');
                            } else {
                                $('#emptyPropertyTypesRow').remove();

                                const newRowHtml = `
                                        <tr data-id="${item.id}"
                                            data-property-id="${item.property_id}"
                                            data-name="${item.property_name || (item.property ? item.property.property_name : '')}"
                                            data-shop-office="${item.shop_home_office_name || ''}"
                                            data-type="${item.property_type}"
                                            data-floor="${item.floor_type || ''}"
                                            data-location="${item.location}"
                                            data-tenant="${item.tenant_name || ''}"
                                            data-phone="${item.tenant_phone || ''}"
                                            data-rent="${item.monthly_rent}"
                                            data-advance="${item.security_deposit}"
                                            data-status="${item.status}">
                                            <td class="text-muted fw-semibold serial-cell">01</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="stat-icon-wrapper ${theme.wrapper} prop-icon-badge" style="width:36px; height:36px; font-size:1rem;">
                                                        <i class="${theme.icon}"></i>
</div>
                                                    <div>
                                                        <div class="fw-bold text-dark fs-6 row-prop-title">${item.property_name || (item.property ? item.property.property_name : '')}</div>
                                                        <small class="text-muted row-location">${locationSubtitle}</small>
</div>
</div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <span class="badge ${theme.badge} row-prop-type w-fit">${item.property_type}</span>
                                                    ${floorBadge}
</div>
                                            </td>
                                            <td>${tenantDisplay}</td>
                                            <td>
                                                <div class="fw-bold ${rentClass} fs-6 row-rent">₹${formattedRent}</div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark row-advance">₹${formattedAdvance}</div>
                                                <small class="text-muted">Security Deposit</small>
                                            </td>
                                            <td>${statusBadge}</td>
                                            <td class="text-end">
                                                <div class="dropdown table-action-dropdown">
                                                    <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false" title="Actions">
                                                        <i class="bi bi-three-dots-vertical"></i>
</button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                        <li><button class="dropdown-item edit-property-type-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                                        <li><hr class="dropdown-divider my-1"></li>
                                                        <li><button class="dropdown-item text-danger delete-property-type-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                                    </ul>
</div>
                                            </td>
                                        </tr>
                                    `;

                                $('#propertyTypesTableBody').prepend(newRowHtml);
                                showToast(res.message || `Property Type added successfully!`, 'success');
                            }

                            propertyTypeModal.hide();
                            $('#propertyTypeForm')[0].reset();
                            if (!isEdit) {
                                currentPage = 1;
                            }
                            if (res.stats) {
                                applyStats(res.stats);
                            } else {
                                recalculateStatsLocal();
                            }
                            updatePagination();
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Property Type' : 'Save Property Type');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayFormErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to save property type.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Delete
            $(document).on('click', '.delete-property-type-btn', function () {
                const tr = $(this).closest('tr');
                const id = tr.data('id');
                const name = tr.data('name');

                if (confirm(`Are you sure you want to delete "${name}"?`)) {
                    $.ajax({
                        url: '{{ route("property-types.destroy", ":id") }}'.replace(':id', id),
                        method: 'POST',
                        success: function (res) {
                            tr.fadeOut(250, function () {
                                $(this).remove();
                                if (res.stats) {
                                    applyStats(res.stats);
                                } else {
                                    recalculateStatsLocal();
                                }
                                updatePagination();
                            });
                            showToast(res.message || `Property Type removed successfully.`, 'success');
                        },
                        error: function (xhr) {
                            showToast(xhr.responseJSON?.message || 'Failed to delete property type.', 'danger');
                        }
                    });
                }
            });

            function showToast(message, type = 'success') {
                if (typeof window.showToast === 'function') {
                    window.showToast(message, type);
                    return;
                }
                const toastContainer = document.querySelector('.toast-container') || createToastContainer();
                const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill';
                const bgClass = type === 'success' ? 'text-bg-success' : 'text-bg-danger';

                const toastEl = document.createElement('div');
                toastEl.className = `toast align-items-center ${bgClass} border-0 shadow`;
                toastEl.setAttribute('role', 'alert');
                toastEl.setAttribute('aria-live', 'assertive');
                toastEl.setAttribute('aria-atomic', 'true');
                toastEl.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body d-flex align-items-center gap-2">
                                <i class="bi bi-${icon}"></i>
                                <span>${message}</span>
</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
</div>
                    `;

                toastContainer.appendChild(toastEl);
                const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                toast.show();
                toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
            }

            function createToastContainer() {
                const container = document.createElement('div');
                container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                container.style.zIndex = '1100';
                document.body.appendChild(container);
                return container;
            }

            // Initial pagination on page load
            updatePagination();
        });
    </script>
@endpush