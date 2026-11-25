@extends('layouts.app')

@section('title', 'Workers Master')
@section('page_title', 'Workers Master ')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Action Row -->
    <div class="card-custom p-3 mb-4 bg-white">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Worker Directory</h5>
</div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="exportWorkersBtn">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export
</button>
                <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#workerModal" id="addWorkerBtn">
                    <i class="bi bi-person-plus-fill me-1"></i> Add New Worker
</button>
</div>
</div>
</div>

    <!-- Filter & Search Card -->
    <div class="card-custom p-3 mb-4 bg-white">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="workerSearchInput" placeholder="Search by English / தமிழ் Name, Phone...">
</div>
</div>
            <div class="col-6 col-md-3">
                <select class="form-select form-select-sm" id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="Mason / மேஸ்திரி">Mason / மேஸ்திரி</option>
                    <option value="Helper / சித்தாள்">Helper / சித்தாள்</option>
                    <option value="Painter / பெயிண்டர்">Painter / பெயிண்டர்</option>
                    <option value="Electrician / எலக்ட்ரீஷியன்">Electrician / எலக்ட்ரீஷியன்</option>
                    <option value="Carpenter / தச்சர்">Carpenter / தச்சர்</option>
                    <option value="Bar Bender / கம்பிகட்டுனர்">Bar Bender / கம்பிகட்டுனர்</option>
                    <option value="Plumber / பிளம்பர்">Plumber / பிளம்பர்</option>
                </select>
</div>
            <div class="col-6 col-md-2">
                <select class="form-select form-select-sm" id="statusFilter">
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
                <span class="small text-muted" id="workerCountBadge">Showing <strong class="text-dark">10</strong> workers</span>
</div>
</div>
</div>

    <!-- Workers Table -->
    <div class="card-custom overflow-hidden">
        <div class="table-responsive">
            <table class="table table-custom table-hover" id="workersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Worker Name</th>
                        <th>Category</th>
                        <th>Phone No</th>
                        <th>Default Daily Wage (₹)</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 70px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="workersTableBody">
                    <!-- Dynamic Sample Rows -->
                    <tr data-id="1" data-name-en="Ramesh" data-name-ta="ரமேஷ்" data-cat="Mason / மேஸ்திரி" data-phone="98401 23456" data-wage="1200" data-status="Active">
                        <td class="text-muted fw-semibold">01</td>
                        <td>
                            <div class="fw-bold text-dark">Ramesh</div>
                            <span class="ta-badge">ரமேஷ்</span>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Mason / மேஸ்திரி</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98401 23456</td>
                        <td class="fw-bold text-success fs-6">₹1,200</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="2" data-name-en="Venkatesan" data-name-ta="வெங்கடேசன்" data-cat="Mason / மேஸ்திரி" data-phone="98402 34567" data-wage="1200" data-status="Active">
                        <td class="text-muted fw-semibold">02</td>
                        <td>
                            <div class="fw-bold text-dark">Venkatesan</div>
                            <span class="ta-badge">வெங்கடேசன்</span>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">Mason / மேஸ்திரி</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98402 34567</td>
                        <td class="fw-bold text-success fs-6">₹1,200</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="3" data-name-en="Suresh" data-name-ta="சுரேஷ்" data-cat="Helper / சித்தாள்" data-phone="98403 45678" data-wage="900" data-status="Active">
                        <td class="text-muted fw-semibold">03</td>
                        <td>
                            <div class="fw-bold text-dark">Suresh</div>
                            <span class="ta-badge">சுரேஷ்</span>
                        </td>
                        <td><span class="badge bg-info-subtle text-info border border-info-subtle">Helper / சித்தாள்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98403 45678</td>
                        <td class="fw-bold text-success fs-6">₹900</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="4" data-name-en="Murugan" data-name-ta="முருகன்" data-cat="Helper / சித்தாள்" data-phone="98404 56789" data-wage="900" data-status="Active">
                        <td class="text-muted fw-semibold">04</td>
                        <td>
                            <div class="fw-bold text-dark">Murugan</div>
                            <span class="ta-badge">முருகன்</span>
                        </td>
                        <td><span class="badge bg-info-subtle text-info border border-info-subtle">Helper / சித்தாள்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98404 56789</td>
                        <td class="fw-bold text-success fs-6">₹900</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="5" data-name-en="Priya" data-name-ta="பிரியா" data-cat="Helper / சித்தாள்" data-phone="98405 67890" data-wage="850" data-status="Active">
                        <td class="text-muted fw-semibold">05</td>
                        <td>
                            <div class="fw-bold text-dark">Priya</div>
                            <span class="ta-badge">பிரியா</span>
                        </td>
                        <td><span class="badge bg-info-subtle text-info border border-info-subtle">Helper / சித்தாள்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98405 67890</td>
                        <td class="fw-bold text-success fs-6">₹850</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="6" data-name-en="Karthik" data-name-ta="கார்த்திக்" data-cat="Painter / பெயிண்டர்" data-phone="98406 78901" data-wage="1100" data-status="Active">
                        <td class="text-muted fw-semibold">06</td>
                        <td>
                            <div class="fw-bold text-dark">Karthik</div>
                            <span class="ta-badge">கார்த்திக்</span>
                        </td>
                        <td><span class="badge bg-warning-subtle text-dark border">Painter / பெயிண்டர்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98406 78901</td>
                        <td class="fw-bold text-success fs-6">₹1,100</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="7" data-name-en="Shanmugam" data-name-ta="சண்முகம்" data-cat="Electrician / எலக்ட்ரீஷியன்" data-phone="98407 89012" data-wage="1300" data-status="Active">
                        <td class="text-muted fw-semibold">07</td>
                        <td>
                            <div class="fw-bold text-dark">Shanmugam</div>
                            <span class="ta-badge">சண்முகம்</span>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-dark border">Electrician / எலக்ட்ரீஷியன்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98407 89012</td>
                        <td class="fw-bold text-success fs-6">₹1,300</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="8" data-name-en="Arumugam" data-name-ta="ஆறுமுகம்" data-cat="Carpenter / தச்சர்" data-phone="98408 90123" data-wage="1350" data-status="Active">
                        <td class="text-muted fw-semibold">08</td>
                        <td>
                            <div class="fw-bold text-dark">Arumugam</div>
                            <span class="ta-badge">ஆறுமுகம்</span>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-dark border">Carpenter / தச்சர்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98408 90123</td>
                        <td class="fw-bold text-success fs-6">₹1,350</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="9" data-name-en="Mani" data-name-ta="மணி" data-cat="Bar Bender / கம்பிகட்டுனர்" data-phone="98409 01234" data-wage="1250" data-status="Active">
                        <td class="text-muted fw-semibold">09</td>
                        <td>
                            <div class="fw-bold text-dark">Mani</div>
                            <span class="ta-badge">மணி</span>
                        </td>
                        <td><span class="badge bg-dark-subtle text-dark border">Bar Bender / கம்பிகட்டுனர்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98409 01234</td>
                        <td class="fw-bold text-success fs-6">₹1,250</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                    <tr data-id="10" data-name-en="Ganesan" data-name-ta="கணேசன்" data-cat="Plumber / பிளம்பர்" data-phone="98410 12345" data-wage="1200" data-status="Inactive">
                        <td class="text-muted fw-semibold">10</td>
                        <td>
                            <div class="fw-bold text-dark">Ganesan</div>
                            <span class="ta-badge">கணேசன்</span>
                        </td>
                        <td><span class="badge bg-secondary-subtle text-dark border">Plumber / பிளம்பர்</span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i>98410 12345</td>
                        <td class="fw-bold text-success fs-6">₹1,200</td>
                        <td><span class="badge bg-secondary-subtle text-muted">Inactive</span></td>
                        <td class="text-end">
                            <div class="dropdown table-action-dropdown">
                                <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                    <i class="bi bi-three-dots-vertical"></i>
</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                                </ul>
</div>
                        </td>
                    </tr>
                </tbody>
            </table>
</div>
</div>

</div>

<!-- Worker Add / Edit Modal -->
<div class="modal fade" id="workerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="workerModalTitle">Add New Worker</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
            <form id="workerForm">
                <div class="modal-body">
                    <input type="hidden" id="workerEditId">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Name in English <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="workerNameEn" placeholder="e.g. Ramesh" required>
</div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Name in Tamil <span class="text-danger">*</span></label>
                            <input type="text" class="form-control ta-text" id="workerNameTa" placeholder="எ.கா. ரமேஷ்" required>
</div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Category  <span class="text-danger">*</span></label>
                            <select class="form-select" id="workerCategory" required>
                                <option value="Mason / மேஸ்திரி">Mason / மேஸ்திரி</option>
                                <option value="Helper / சித்தாள்">Helper / சித்தாள்</option>
                                <option value="Painter / பெயிண்டர்">Painter / பெயிண்டர்</option>
                                <option value="Electrician / எலக்ட்ரீஷியன்">Electrician / எலக்ட்ரீஷியன்</option>
                                <option value="Carpenter / தச்சர்">Carpenter / தச்சர்</option>
                                <option value="Bar Bender / கம்பிகட்டுனர்">Bar Bender / கம்பிகட்டுனர்</option>
                                <option value="Plumber / பிளம்பர்">Plumber / பிளம்பர்</option>
                            </select>
</div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Default Daily Wage (₹) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="workerWage" placeholder="1200" required min="100">
</div>
</div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="tel" class="form-control" id="workerPhone" placeholder="98400 00000">
</div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select class="form-select" id="workerStatus">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
</div>
</div>
</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-deiveni btn-sm" id="saveWorkerBtn">Save Worker</button>
</div>
            </form>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let nextId = 11;

    // Filter table function
    function filterTable() {
        const searchText = $('#workerSearchInput').val().toLowerCase();
        const catFilter = $('#categoryFilter').val();
        const statFilter = $('#statusFilter').val();
        let visibleCount = 0;

        $('#workersTableBody tr').each(function() {
            const nameEn = $(this).data('name-en').toString().toLowerCase();
            const nameTa = $(this).data('name-ta').toString().toLowerCase();
            const phone = $(this).data('phone').toString().toLowerCase();
            const cat = $(this).data('cat');
            const stat = $(this).data('status');

            const matchSearch = !searchText || nameEn.includes(searchText) || nameTa.includes(searchText) || phone.includes(searchText);
            const matchCat = !catFilter || cat === catFilter;
            const matchStat = !statFilter || stat === statFilter;

            if (matchSearch && matchCat && matchStat) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        $('#workerCountBadge').html(`Showing <strong class="text-dark">${visibleCount}</strong> workers`);
    }

    $('#workerSearchInput, #categoryFilter, #statusFilter').on('input change', filterTable);

    // Reset filters logic
    $('#historyFilterResetBtn').on('click', function() {
        $('#workerSearchInput').val('');
        $('#categoryFilter').val('');
        $('#statusFilter').val('');
        filterTable();
    });

    // Reset Add Modal
    $('#addWorkerBtn').on('click', function() {
        $('#workerModalTitle').text('Add New Worker ');
        $('#workerEditId').val('');
        $('#workerForm')[0].reset();
        $('#workerWage').val('1200');
    });

    // Edit Worker Modal
    $(document).on('click', '.edit-worker-btn', function() {
        const tr = $(this).closest('tr');
        $('#workerModalTitle').text('Edit Worker ');
        $('#workerEditId').val(tr.data('id'));
        $('#workerNameEn').val(tr.data('name-en'));
        $('#workerNameTa').val(tr.data('name-ta'));
        $('#workerCategory').val(tr.data('cat'));
        $('#workerWage').val(tr.data('wage'));
        $('#workerPhone').val(tr.data('phone'));
        $('#workerStatus').val(tr.data('status'));

        const modal = new bootstrap.Modal(document.getElementById('workerModal'));
        modal.show();
    });

    // Submit Worker Form
    $('#workerForm').on('submit', function(e) {
        e.preventDefault();
        const editId = $('#workerEditId').val();
        const nameEn = $('#workerNameEn').val().trim();
        const nameTa = $('#workerNameTa').val().trim();
        const cat = $('#workerCategory').val();
        const wage = $('#workerWage').val();
        const phone = $('#workerPhone').val().trim() || 'N/A';
        const status = $('#workerStatus').val();

        if (editId) {
            // Edit existing row
            const tr = $(`#workersTableBody tr[data-id="${editId}"]`);
            tr.data('name-en', nameEn)
              .data('name-ta', nameTa)
              .data('cat', cat)
              .data('wage', wage)
              .data('phone', phone)
              .data('status', status);

            const badgeClass = status === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted';
            tr.find('td:nth-child(2)').html(`<div class="fw-bold text-dark">${nameEn}</div><span class="ta-badge">${nameTa}</span>`);
            tr.find('td:nth-child(3)').html(`<span class="badge bg-primary-subtle text-primary border border-primary-subtle">${cat}</span>`);
            tr.find('td:nth-child(4)').html(`<i class="bi bi-telephone text-muted me-1"></i>${phone}`);
            tr.find('td:nth-child(5)').html(`₹${parseInt(wage).toLocaleString('en-IN')}`);
            tr.find('td:nth-child(6)').html(`<span class="badge ${badgeClass}">${status}</span>`);

            showToast(`Worker "${nameEn}" updated successfully!`, 'success');
        } else {
            // Add new row
            const rowNum = String(nextId).padStart(2, '0');
            const badgeClass = status === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-muted';
            const newRow = `
                <tr data-id="${nextId}" data-name-en="${nameEn}" data-name-ta="${nameTa}" data-cat="${cat}" data-phone="${phone}" data-wage="${wage}" data-status="${status}">
                    <td class="text-muted fw-semibold">${rowNum}</td>
                    <td>
                        <div class="fw-bold text-dark">${nameEn}</div>
                        <span class="ta-badge">${nameTa}</span>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">${cat}</span></td>
                    <td><i class="bi bi-telephone text-muted me-1"></i>${phone}</td>
                    <td class="fw-bold text-success fs-6">₹${parseInt(wage).toLocaleString('en-IN')}</td>
                    <td><span class="badge ${badgeClass}">${status}</span></td>
                    <td class="text-end">
                        <div class="dropdown table-action-dropdown">
                            <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                <i class="bi bi-three-dots-vertical"></i>
</button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><button class="dropdown-item edit-worker-btn" type="button"><i class="bi bi-pencil text-primary me-2"></i>Edit</button></li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li><button class="dropdown-item text-danger delete-worker-btn" type="button"><i class="bi bi-trash3 text-danger me-2"></i>Delete</button></li>
                            </ul>
</div>
                    </td>
                </tr>
            `;
            $('#workersTableBody').prepend(newRow);
            nextId++;
            showToast(`Worker "${nameEn}" (${nameTa}) added successfully!`, 'success');
        }

        $('#workerModal').modal('hide');
        filterTable();
    });

    // Delete Worker
    $(document).on('click', '.delete-worker-btn', function() {
        const tr = $(this).closest('tr');
        const name = tr.data('name-en');
        confirmDelete(`Are you sure you want to remove worker "${name}" from the system?`, function() {
            tr.fadeOut(300, function() {
                $(this).remove();
                filterTable();
                showToast(`Worker "${name}" deleted.`, 'danger');
            });
        });
    });

    // Export button
    $('#exportWorkersBtn').on('click', function() {
        showToast('Exporting Workers Directory to Excel/CSV...', 'info');
    });
});
</script>
@endpush
