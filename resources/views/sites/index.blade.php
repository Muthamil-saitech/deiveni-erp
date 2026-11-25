@extends('layouts.app')

@section('title', 'Project Sites')
@section('page_title', 'Project Sites ')

@section('content')
    <div class="container-fluid px-0">

        <!-- Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Client & Project Sites</h5>
</div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#siteModal"
                        id="addSiteBtn">
                        <i class="bi bi-building-fill-add me-1"></i> Add New Site
</button>
</div>
</div>
</div>

        <!-- Filter & Search Cards -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="siteSearchInput"
                            placeholder="Search Site Name, Client Name, Location...">
</div>
</div>
                <div class="col-6 col-md-3">
                    <select class="form-select form-select-sm" id="workTypeFilter">
                        <option value="">All Work Types</option>
                        <option value="New Build">New Build</option>
                        <option value="Alteration">Alteration</option>
                    </select>
</div>
                <div class="col-6 col-md-2">
                    <select class="form-select form-select-sm" id="siteStatusFilter">
                        <option value="">All Status</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Planning">Planning</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
</div>
                <div class="col-6 col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" id="historyFilterResetBtn"
                    title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
</button>
</div>
                <div class="col-12 col-md-2 text-md-end">
                    <span class="small text-muted" id="siteCountBadge">Showing <strong class="text-dark"
                            id="visibleSiteCount">{{ $sites->count() }}</strong> Sites</span>
</div>
</div>
</div>

        <!-- Sites Cards Grid View -->
        <div class="row g-4" id="sitesContainer">
            @forelse ($sites as $site)
                @php
                    $estNum = (float) $site->estimated_budget;
                    $spentNum = (float) ($site->spent ?? 0);
                    $estInLakhs = number_format($estNum / 100000, 2);
                    $spentInLakhs = number_format($spentNum / 100000, 2);
                    $pct = $estNum > 0 ? min(100, round(($spentNum / $estNum) * 100, 2)) : 0;

                    $borderClass = $site->work_type === 'Alteration' ? 'border-warning' : 'border-primary';
                    $badgeTypeClass = $site->work_type === 'Alteration' ? 'bg-secondary-subtle text-secondary border' : 'bg-info-subtle text-info border border-info-subtle';

                    $statusBadgeClass = match ($site->status) {
                        'Completed' => 'bg-success-subtle text-success',
                        'In Progress' => 'bg-warning-subtle text-dark',
                        'Planning' => 'bg-secondary-subtle text-muted',
                        'On Hold' => 'bg-danger-subtle text-danger',
                        default => 'bg-primary-subtle text-primary',
                    };

                    $formattedStart = $site->start_date ? \Carbon\Carbon::parse($site->start_date)->format('Y-m-d') : '';
                    $displayStart = $site->start_date ? \Carbon\Carbon::parse($site->start_date)->format('d M Y') : 'N/A';
                    $formattedEnd = $site->end_date ? \Carbon\Carbon::parse($site->end_date)->format('Y-m-d') : '';
                @endphp
                <div class="col-12 col-md-6 col-xl-4 site-card-col" id="site-card-{{ $site->id }}" data-id="{{ $site->id }}"
                    data-name="{{ $site->name }}" data-location="{{ $site->location }}" data-client-id="{{ $site->client_id }}"
                    data-client="{{ $site->client?->name ?? 'N/A' }}" data-phone="{{ $site->client?->phone ?? '' }}"
                    data-address="{{ $site->client?->address ?? '' }}" data-type="{{ $site->work_type }}"
                    data-start="{{ $formattedStart }}" data-end="{{ $formattedEnd }}"
                    data-estimate="{{ $site->estimated_budget }}" data-spent="{{ $site->spent ?? 0 }}"
                    data-status="{{ $site->status }}">
                    <div
                        class="card-custom h-100 p-3 p-md-4 d-flex flex-column justify-content-between border-top border-4 {{ $borderClass }}">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge {{ $badgeTypeClass }} fw-semibold">{{ $site->work_type }}</span>
                                <span class="badge {{ $statusBadgeClass }}">{{ $site->status }}</span>
                        
</div>
                            <h5 class="fw-bold text-dark mb-1 site-card-title">
                                <a href="{{ route('sites.show', $site->encrypted_id) }}"
                                    class="text-dark text-decoration-none">{{ $site->name }}</a>
</h5>
                            <p class="text-muted small mb-3"><i class="bi bi-geo-alt text-danger me-1"></i><span
                                    class="site-card-location">{{ $site->location }}</span></p>

                            <div class="bg-light p-2 rounded-3 mb-3 small">
                                <div class="fw-semibold text-dark"><i class="bi bi-person-fill text-primary me-1"></i><span
                                        class="site-card-client">{{ $site->client?->name ?? 'N/A' }}</span></div>
                                <div class="text-muted"><i class="bi bi-telephone text-muted me-1"></i><span
                                        class="site-card-phone">{{ $site->client?->phone ?? '-' }}</span></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-house me-1"></i><span
                                        class="site-card-address">{{ $site->client?->address ?: $site->location }}</span></div>
</div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Budget Spent: <strong>₹{{ $spentInLakhs }} L</strong></span>
                                    <span class="fw-bold text-dark">Est: ₹{{ $estInLakhs }} L</span>
</div>
                                <div class="progress" style="height: 7px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $pct }}%;"
                                        aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
</div>
                                <div class="text-end small text-muted mt-1" style="font-size: 0.72rem;">{{ number_format($pct, 2) }}% of estimate utilised</div>
</div>
</div>
                        <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>Start:
                                {{ $displayStart }}</span>
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('sites.show', $site->encrypted_id) }}" class="btn table-action-btn"
                                    title="View Site Details">
                                    <i class="bi bi-eye text-info"></i>
</a>
                                <button class="btn table-action-btn edit-site-btn" type="button" title="Edit Site">
                                    <i class="bi bi-pencil text-primary"></i>
</button>
</div>
</div>
</div>
</div>
            @empty
            @endforelse
</div>

        <!-- Empty State Container -->
        <div id="sitesEmptyState" class="card-custom p-5 bg-white text-center my-3"
            style="{{ $sites->isEmpty() ? '' : 'display: none;' }}">
            <div class="rounded-circle bg-light d-inline-flex p-3 mb-3">
                <i class="bi bi-building fs-1 text-muted"></i>
</div>
            <h5 class="fw-bold text-dark mb-1">No Project Sites Recorded Yet</h5>
            <p class="text-muted small mb-3">There are no project sites found in the system. Click "Add New Site" below to
                create your first site.</p>
            <div>
                <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#siteModal">
                    <i class="bi bi-building-fill-add me-1"></i> Add New Site
</button>
</div>
</div>
</div>

    <!-- Site Add / Edit Modal -->
    <div class="modal fade" id="siteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="siteModalTitle">Add Project Site</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="siteForm" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="siteEditId" name="id">
                        <input type="hidden" id="client_id" name="client_id">

                        <div class="row g-3">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary border-bottom pb-2 mb-2"><i
                                        class="bi bi-building me-1"></i>Site Details</h6>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Site Project Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="siteName" name="name"
                                    placeholder="e.g. Site A - Duplex Villa">
                                <div class="invalid-feedback" id="errSiteName"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Location / Area <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="siteLocation" name="location"
                                    placeholder="e.g. Porur, Chennai">
                                <div class="invalid-feedback" id="errSiteLocation"></div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">Work Type  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="siteWorkType" name="work_type">
                                    <option value="New Build">New Build</option>
                                    <option value="Alteration">Alteration</option>
                                </select>
                                <div class="invalid-feedback" id="errSiteWorkType"></div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">Start Date</label>
                                <input type="date" class="form-control" id="siteStartDate" name="start_date">
                                <div class="invalid-feedback" id="errSiteStartDate"></div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold" for="enddate">End Date</label>
                                <input type="date" class="form-control" id="enddate" name="end_date">
                                <div class="invalid-feedback" id="errSiteEndDate"></div>
</div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold">Estimated Budget (₹) <span
                                        class="text-danger">*</span></label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" class="form-control" id="siteEstimate"
                                        name="estimated_budget" placeholder="5000000">
                                    <div class="invalid-feedback" id="errSiteEstimate"></div>
</div>
</div>

                            <div class="col-12 mt-4">
                                <div
                                    class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-2 mb-2 gap-2">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <h6 class="fw-bold text-primary mb-0">
                                            <i class="bi bi-person-circle me-1"></i>Client Details 
</h6>
</div>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Customer Type Selection">
                                        <input type="radio" class="btn-check" name="clientOption" id="clientOptionNew"
                                            value="new" checked autocomplete="off">
                                        <label class="btn btn-outline-primary btn-sm py-1 px-2" for="clientOptionNew"
                                            title="Create a new client">
                                            <i class="bi bi-person-plus-fill me-1"></i>New Client
</label>

                                        <input type="radio" class="btn-check" name="clientOption" id="clientOptionExisting"
                                            value="existing" autocomplete="off">
                                        <label class="btn btn-outline-primary btn-sm py-1 px-2" for="clientOptionExisting"
                                            title="Choose from existing customers">
                                            <i class="bi bi-people-fill me-1"></i>Select Existing Customer
</label>
</div>
</div>
</div>

                            <!-- Existing Customer Selection Dropdown Container -->
                            <div class="col-12" id="existingClientWrapper" style="display: none;">
                                <div class="p-3 bg-light rounded-3 border border-primary-subtle">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-semibold text-primary mb-0"
                                            for="existingCustomerSelect">
                                            <i class="bi bi-person-lines-fill me-1"></i>Select Existing Customer
 <span class="text-danger">*</span>
</label>
                                        <span class="small text-muted" id="existingCustomerCountBadge"></span>
</div>
                                    <select class="form-select" id="existingCustomerSelect" name="existing_client_id">
                                        <option value="">-- Choose Existing Customer --</option>
                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}" data-id="{{ $client->id }}"
                                                data-name="{{ $client->name }}" data-phone="{{ $client->phone }}"
                                                data-address="{{ $client->address }}">
                                                {{ $client->name }}{{ $client->phone ? ' • ' . $client->phone : '' }}{{ $client->address ? ' (' . $client->address . ')' : '' }}
</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="errExistingCustomer"></div>
                                    <!-- Selected Customer Quick Summary Card -->
                                    <div id="selectedCustomerPreview"
                                        class="mt-2 p-2 bg-white rounded border small text-muted" style="display: none;">
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <span class="badge bg-success-subtle text-success"><i
                                                    class="bi bi-check-circle me-1"></i>Linked</span>
                                            <strong id="prevCustName" class="text-dark"></strong>
                                            <span id="prevCustPhone" class="text-muted"></span>
                                            <span id="prevCustAddress" class="text-muted text-truncate"
                                                style="max-width: 280px;"></span>
</div>
</div>
</div>
</div>

                            <!-- New Client Form Fields (Hidden when Select Existing Customer is active) -->
                            <div class="col-12" id="newClientFieldsWrapper">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">Client Full Name <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="clientName" name="client_name"
                                            placeholder="e.g. Mr. R. Balaji">
                                        <div class="invalid-feedback" id="errClientName"></div>
</div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small fw-semibold">Client Phone Number <span
                                                class="text-danger">*</span></label>
                                        <input type="tel" class="form-control" id="clientPhone" name="client_phone"
                                            placeholder="98400 11223">
                                        <div class="invalid-feedback" id="errClientPhone"></div>
</div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Client Address</label>
                                        <input type="text" class="form-control" id="clientAddress" name="client_address"
                                            placeholder="e.g. No. 5, Mount Poonamallee Road, Porur">
                                        <div class="invalid-feedback" id="errClientAddress"></div>
</div>
</div>
</div>

                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold">Project Status</label>
                                <select class="form-select" id="siteStatus" name="status">
                                    <option value="In Progress">In Progress</option>
                                    <option value="Planning">Planning</option>
                                    <option value="Completed">Completed</option>
                                    <option value="On Hold">On Hold</option>
                                </select>
                                <div class="invalid-feedback" id="errSiteStatus"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="siteSubmitBtn">
                            <span class="spinner-border spinner-border-sm d-none me-1" id="siteSubmitSpinner"></span>
                            <span id="siteSubmitBtnText">Save Project Site</span>
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
            // Setup CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Store clients list in memory
            let clientsList = @json($clients);

            function clearSiteFormErrors() {
                $('#siteForm .is-invalid').removeClass('is-invalid');
                $('#siteForm .invalid-feedback').text('').hide();
            }

            function displaySiteFormErrors(errors) {
                clearSiteFormErrors();
                if (!errors) return;

                if (errors.name) {
                    $('#siteName').addClass('is-invalid');
                    $('#errSiteName').text(errors.name[0]).show();
                }
                if (errors.location) {
                    $('#siteLocation').addClass('is-invalid');
                    $('#errSiteLocation').text(errors.location[0]).show();
                }
                if (errors.work_type) {
                    $('#siteWorkType').addClass('is-invalid');
                    $('#errSiteWorkType').text(errors.work_type[0]).show();
                }
                if (errors.start_date) {
                    $('#siteStartDate').addClass('is-invalid');
                    $('#errSiteStartDate').text(errors.start_date[0]).show();
                }
                if (errors.end_date) {
                    $('#enddate').addClass('is-invalid');
                    $('#errSiteEndDate').text(errors.end_date[0]).show();
                }
                if (errors.estimated_budget) {
                    $('#siteEstimate').addClass('is-invalid');
                    $('#errSiteEstimate').text(errors.estimated_budget[0]).show();
                }
                if (errors.client_id) {
                    $('#existingCustomerSelect').addClass('is-invalid');
                    $('#errExistingCustomer').text(errors.client_id[0]).show();
                }
                if (errors.client_name) {
                    $('#clientName').addClass('is-invalid');
                    $('#errClientName').text(errors.client_name[0]).show();
                }
                if (errors.client_phone) {
                    $('#clientPhone').addClass('is-invalid');
                    $('#errClientPhone').text(errors.client_phone[0]).show();
                }
                if (errors.client_address) {
                    $('#clientAddress').addClass('is-invalid');
                    $('#errClientAddress').text(errors.client_address[0]).show();
                }
                if (errors.status) {
                    $('#siteStatus').addClass('is-invalid');
                    $('#errSiteStatus').text(errors.status[0]).show();
                }
            }

            // Clear errors on input / change
            $('#siteForm input, #siteForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
                $(this).closest('.input-group').find('.invalid-feedback').text('').hide();
            });

            // Populate existing customers dropdown
            function populateExistingCustomerDropdown(selectedId = null) {
                const select = $('#existingCustomerSelect');
                select.empty();

                if (!clientsList || clientsList.length === 0) {
                    select.append('<option value="" disabled selected>No saved customers found. Please enter details as New Client.</option>');
                    $('#existingCustomerCountBadge').text('0 customers');
                    select.prop('disabled', true);
                } else {
                    select.prop('disabled', false);
                    $('#existingCustomerCountBadge').text(`${clientsList.length} customer(s) available`);
                    select.append('<option value="">-- Choose Existing Customer --</option>');
                    clientsList.forEach(function (cust) {
                        const phoneDisplay = cust.phone ? ` • ${cust.phone}` : '';
                        const addrDisplay = cust.address ? ` (${cust.address})` : '';
                        const label = `${cust.name}${phoneDisplay}${addrDisplay}`;
                        const isSelected = selectedId && parseInt(selectedId) === parseInt(cust.id);
                        const opt = $('<option></option>')
                            .val(cust.id)
                            .text(label)
                            .attr('data-id', cust.id)
                            .attr('data-name', cust.name)
                            .attr('data-phone', cust.phone || '')
                            .attr('data-address', cust.address || '')
                            .prop('selected', isSelected);
                        select.append(opt);
                    });

                    if (selectedId) {
                        select.trigger('change');
                    }
                }
            }

            // Radio change between New Client and Existing Customer
            $('input[name="clientOption"]').on('change', function () {
                const selectedVal = $(this).val();
                if (selectedVal === 'existing') {
                    $('#newClientFieldsWrapper').slideUp(150);
                    $('#existingClientWrapper').slideDown(150);
                    populateExistingCustomerDropdown($('#client_id').val());

                    if (!clientsList || clientsList.length === 0) {
                        showToast('No saved customers found yet. Please enter details as a New Client.', 'info');
                    } else {
                        $('#existingCustomerSelect').focus();
                    }
                } else {
                    $('#existingClientWrapper').slideUp(150);
                    $('#newClientFieldsWrapper').slideDown(150);
                    $('#selectedCustomerPreview').hide();
                    $('#existingCustomerSelect').val('');
                    $('#client_id').val('');
                }
            });

            // Handle selection from Existing Customer dropdown
            $('#existingCustomerSelect').on('change', function () {
                const selectedOption = $(this).find('option:selected');
                const val = $(this).val();

                if (val && selectedOption.length > 0) {
                    const custId = selectedOption.attr('data-id') || val;
                    const name = selectedOption.attr('data-name') || '';
                    const phone = selectedOption.attr('data-phone') || '';
                    const address = selectedOption.attr('data-address') || '';

                    $('#client_id').val(custId);
                    $('#clientName').val(name);
                    $('#clientPhone').val(phone);
                    $('#clientAddress').val(address);

                    $('#prevCustName').text(name);
                    $('#prevCustPhone').text(phone ? '• 📞 ' + phone : '');
                    $('#prevCustAddress').text(address ? '• 📍 ' + address : '');
                    $('#selectedCustomerPreview').slideDown(150);
                } else {
                    $('#client_id').val('');
                    $('#clientName').val('');
                    $('#clientPhone').val('');
                    $('#clientAddress').val('');
                    $('#selectedCustomerPreview').slideUp(150);
                }
            });

            // Reset modal for creating a new site
            function resetSiteModalForNew() {
                $('#siteModalTitle').text('Add Project Site ');
                $('#siteEditId').val('');
                $('#client_id').val('');
                $('#siteForm')[0].reset();
                $('#siteSubmitBtnText').text('Save Project Site');
                $('#clientOptionNew').prop('checked', true);
                $('#newClientFieldsWrapper').show();
                $('#existingClientWrapper').hide();
                $('#selectedCustomerPreview').hide();
                $('#existingCustomerSelect').val('');
                clearSiteFormErrors();
                populateExistingCustomerDropdown();
            }

            // Generate HTML for a site card
            function generateSiteCardHtml(site) {
                const estNum = parseFloat(site.estimated_budget) || 0;
                const spentNum = parseFloat(site.spent) || 0;
                const estInLakhs = (estNum / 100000).toFixed(1);
                const spentInLakhs = (spentNum / 100000).toFixed(1);
                const pct = estNum > 0 ? Math.min(100, Math.round((spentNum / estNum) * 100)) : 0;

                const clientObj = site.client || {};
                const clientName = clientObj.name || site.client_name || 'N/A';
                const clientPhone = clientObj.phone || site.client_phone || '-';
                const clientAddress = clientObj.address || site.client_address || site.location;
                const clientId = site.client_id || (clientObj ? clientObj.id : '');

                let borderClass = site.work_type === 'Alteration' ? 'border-warning' : 'border-primary';
                let badgeTypeClass = site.work_type === 'Alteration' ? 'bg-secondary-subtle text-secondary border' : 'bg-info-subtle text-info border border-info-subtle';

                let statusBadgeClass = 'bg-primary-subtle text-primary';
                if (site.status === 'Completed') statusBadgeClass = 'bg-success-subtle text-success';
                else if (site.status === 'In Progress') statusBadgeClass = 'bg-warning-subtle text-dark';
                else if (site.status === 'Planning') statusBadgeClass = 'bg-secondary-subtle text-muted';
                else if (site.status === 'On Hold') statusBadgeClass = 'bg-danger-subtle text-danger';

                const startDateStr = site.start_date ? site.start_date.substring(0, 10) : '';
                const endDateStr = site.end_date ? site.end_date.substring(0, 10) : '';
                const displayStart = startDateStr || 'N/A';

                return `
                            <div class="col-12 col-md-6 col-xl-4 site-card-col" 
                                 id="site-card-${site.id}"
                                 data-id="${site.id}" 
                                 data-name="${site.name}" 
                                 data-location="${site.location}" 
                                 data-client-id="${clientId}" 
                                 data-client="${clientName}" 
                                 data-phone="${clientPhone}" 
                                 data-address="${clientAddress}" 
                                 data-type="${site.work_type}" 
                                 data-start="${startDateStr}" 
                                 data-end="${endDateStr}" 
                                 data-estimate="${site.estimated_budget}" 
                                 data-spent="${site.spent || 0}" 
                                 data-status="${site.status}">
                                <div class="card-custom h-100 p-3 p-md-4 d-flex flex-column justify-content-between border-top border-4 ${borderClass}">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge ${badgeTypeClass} fw-semibold">${site.work_type}</span>
                                            <span class="badge ${statusBadgeClass}">${site.status}</span>
</div>
                                        <h5 class="fw-bold text-dark mb-1 site-card-title"><a href="${'{{ route("sites.show", ":id") }}'.replace(':id', site.encrypted_id || site.id)}" class="text-dark text-decoration-none">${site.name}</a></h5>
                                        <p class="text-muted small mb-3"><i class="bi bi-geo-alt text-danger me-1"></i><span class="site-card-location">${site.location}</span></p>

                                        <div class="bg-light p-2 rounded-3 mb-3 small">
                                            <div class="fw-semibold text-dark"><i class="bi bi-person-fill text-primary me-1"></i><span class="site-card-client">${clientName}</span></div>
                                            <div class="text-muted"><i class="bi bi-telephone text-muted me-1"></i><span class="site-card-phone">${clientPhone}</span></div>
                                            <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-house me-1"></i><span class="site-card-address">${clientAddress}</span></div>
</div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span class="text-muted">Budget Spent: <strong>₹${spentInLakhs} L</strong></span>
                                                <span class="fw-bold text-dark">Est: ₹${estInLakhs} L</span>
</div>
                                            <div class="progress" style="height: 7px;">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${pct}%;" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
</div>
                                            <div class="text-end small text-muted mt-1" style="font-size: 0.72rem;">${pct}% of estimate utilised</div>
</div>
</div>

                                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                        <span class="small text-muted"><i class="bi bi-calendar-event me-1"></i>Start: ${displayStart}</span>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="${'{{ route("sites.show", ":id") }}'.replace(':id', site.encrypted_id || site.id)}" class="btn table-action-btn" title="View Site Details">
                                                <i class="bi bi-eye text-info"></i>
</a>
                                            <button class="btn table-action-btn edit-site-btn" type="button" title="Edit Site">
                                                <i class="bi bi-pencil text-primary"></i>
</button>
</div>
</div>
</div>
</div>
                        `;
            }

            // Client-side filter
            function filterSites() {
                const query = $('#siteSearchInput').val().toLowerCase();
                const type = $('#workTypeFilter').val();
                const status = $('#siteStatusFilter').val();
                let visible = 0;

                $('.site-card-col').each(function () {
                    const name = ($(this).data('name') || '').toString().toLowerCase();
                    const location = ($(this).data('location') || '').toString().toLowerCase();
                    const client = ($(this).data('client') || '').toString().toLowerCase();
                    const rowType = $(this).data('type');
                    const rowStatus = $(this).data('status');

                    const matchQuery = !query || name.includes(query) || location.includes(query) || client.includes(query);
                    const matchType = !type || rowType === type;
                    const matchStatus = !status || rowStatus === status;

                    if (matchQuery && matchType && matchStatus) {
                        $(this).show();
                        visible++;
                    } else {
                        $(this).hide();
                    }
                });

                $('#visibleSiteCount').text(visible);
            }

            $('#siteSearchInput, #workTypeFilter, #siteStatusFilter').on('input change', filterSites);

            // Reset filters logic
            $('#historyFilterResetBtn').on('click', function () {
                $('#siteSearchInput').val('');
                $('#workTypeFilter').val('');
                $('#siteStatusFilter').val('');
                filterSites();
            });

            $('#addSiteBtn').on('click', resetSiteModalForNew);
            $('[data-bs-target="#siteModal"]').on('click', function () {
                if (!$(this).hasClass('edit-site-btn')) {
                    resetSiteModalForNew();
                }
            });

            // Edit site
            $(document).on('click', '.edit-site-btn', function () {
                const card = $(this).closest('.site-card-col');
                const siteId = card.data('id');
                const siteName = card.data('name');
                const siteLocation = card.data('location');
                const siteType = card.data('type');
                const siteStart = card.data('start');
                const siteEnd = card.data('end');
                const siteEstimate = card.data('estimate');
                const siteStatus = card.data('status');
                const clientId = card.data('client-id');
                const clientName = card.data('client');
                const clientPhone = card.data('phone');
                const clientAddress = card.data('address');

                $('#siteModalTitle').text('Edit Project Site ');
                $('#siteEditId').val(siteId);
                $('#siteName').val(siteName);
                $('#siteLocation').val(siteLocation);
                $('#siteWorkType').val(siteType);
                $('#siteStartDate').val(siteStart || '');
                $('#enddate').val(siteEnd || '');
                $('#siteEstimate').val(siteEstimate);
                $('#siteStatus').val(siteStatus);

                $('#client_id').val(clientId || '');
                $('#clientName').val(clientName !== 'N/A' ? clientName : '');
                $('#clientPhone').val(clientPhone !== '-' ? clientPhone : '');
                $('#clientAddress').val(clientAddress);

                $('#clientOptionNew').prop('checked', true);
                $('#newClientFieldsWrapper').show();
                $('#existingClientWrapper').hide();
                $('#selectedCustomerPreview').hide();
                $('#siteSubmitBtnText').text('Update Project Site');

                clearSiteFormErrors();
                populateExistingCustomerDropdown(clientId);

                const modal = new bootstrap.Modal(document.getElementById('siteModal'));
                modal.show();
            });



            // Form Submit (Add / Edit) via AJAX
            $('#siteForm').on('submit', function (e) {
                e.preventDefault();
                clearSiteFormErrors();

                const editId = $('#siteEditId').val();
                const isEdit = !!editId;
                const clientOpt = $('input[name="clientOption"]:checked').val();
                const existingClientId = $('#existingCustomerSelect').val();

                if (clientOpt === 'existing' && !existingClientId) {
                    $('#existingCustomerSelect').addClass('is-invalid');
                    $('#errExistingCustomer').text('Please choose an existing customer or switch to "New Client".').show();
                    showToast('Please choose an existing customer or switch to "New Client".', 'warning');
                    $('#existingCustomerSelect').focus();
                    return;
                }

                const $btn = $('#siteSubmitBtn');
                const $spinner = $('#siteSubmitSpinner');
                const $btnText = $('#siteSubmitBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text(isEdit ? 'Updating Site...' : 'Saving Site...');

                const formData = {
                    name: $('#siteName').val().trim(),
                    location: $('#siteLocation').val().trim(),
                    work_type: $('#siteWorkType').val(),
                    start_date: $('#siteStartDate').val() || null,
                    end_date: $('#enddate').val() || null,
                    estimated_budget: $('#siteEstimate').val(),
                    status: $('#siteStatus').val(),
                    client_option: clientOpt,
                    client_id: (clientOpt === 'existing' && existingClientId) ? existingClientId : ($('#client_id').val() || null),
                    client_name: $('#clientName').val().trim(),
                    client_phone: $('#clientPhone').val().trim(),
                    client_address: $('#clientAddress').val().trim() || null
                };

                const url = isEdit ? '{{ route("sites.update", ":id") }}'.replace(':id', editId) : '{{ route("sites.store") }}';
                const method = isEdit ? 'POST' : 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    dataType: 'json',
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Project Site' : 'Save Project Site');

                        if (res.success) {
                            if (res.clients) {
                                clientsList = res.clients;
                            }

                            const site = res.data;
                            const newCardHtml = generateSiteCardHtml(site);

                            if (isEdit) {
                                $(`#site-card-${editId}`).replaceWith(newCardHtml);
                            } else {
                                $('#sitesContainer').prepend(newCardHtml);
                                $('#sitesEmptyState').hide();
                            }

                            $('#siteModal').modal('hide');
                            resetSiteModalForNew();
                            filterSites();
                            showToast(res.message, 'success');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isEdit ? 'Update Project Site' : 'Save Project Site');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            displaySiteFormErrors(errors);
                            const firstErrKey = Object.keys(errors)[0];
                            showToast(errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'An error occurred while saving the project site.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Initial setup
            populateExistingCustomerDropdown();
            filterSites();
        });
    </script>
@endpush