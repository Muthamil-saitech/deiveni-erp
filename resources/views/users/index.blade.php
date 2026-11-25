@extends('layouts.app')

@section('title', 'User Management & Dynamic RBAC')
@section('page_title', 'User Management & Dynamic RBAC ')

@section('content')
    <div class="container-fluid px-0">

        <!-- Top Header & Action Row -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Dynamic Role-Based Access Control (RBAC) & System Users</h5>
</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#roleModal"
                        id="addRoleBtn">
                        <i class="bi bi-shield-plus me-1"></i> Create New Role
</button>
                    <button class="btn btn-deiveni btn-sm" data-bs-toggle="modal" data-bs-target="#userModal"
                        id="addUserBtn">
                        <i class="bi bi-person-plus-fill me-1"></i> Add System User
</button>
</div>
</div>
</div>

        <!-- SECTION 1: Role Creation & Roles List (First Section) -->
        <div class="card-custom overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-shield-lock-fill text-primary me-2"></i>1. System Roles & Menu Permissions

</h6>
</div>
                <span class="badge bg-primary" id="rolesCountBadge">{{ count($roles) }} Defined Roles</span>
</div>

            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" id="rolesTable">
                    <thead>
                        <tr>
                            <th style="min-width: 170px;">Role Name</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th style="min-width: 320px;">Accessible Menus</th>
                            <th class="text-center" style="width: 100px;">Assigned Users</th>
                            <th class="text-end" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        @forelse($roles as $role)
                            @php
                                $roleMenuKeys = $role->menus->pluck('menu_key')->all();
                                $allMenus = config('menus.items', []);
                            @endphp
                            <tr data-id="{{ $role->id }}" data-name="{{ $role->name }}" data-desc="{{ $role->description }}"
                                data-system="{{ $role->is_system ? '1' : '0' }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge {{ $role->badgeClass() }} me-2 px-2 py-1 fs-6">
                                            <i class="bi bi-shield-shaded me-1"></i>{{ $role->name }}
</span>
</div>
                                </td>
                                <td>
                                    @if($role->is_system)
                                        <span class="badge bg-secondary-subtle text-secondary border">System Default</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info border">Custom Role</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="small text-muted">{{ $role->description ?: 'No description provided.' }}</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        @php
                                            $shownCount = 0;
                                        @endphp
                                        @foreach($roleMenuKeys as $mKey)
                                            @if(isset($allMenus[$mKey]) && $shownCount < 4)
                                                <span class="badge bg-light text-dark border small"
                                                    title="{{ $allMenus[$mKey]['name'] }}">
                                                    <i
                                                        class="{{ $allMenus[$mKey]['icon'] }} me-1 text-primary"></i>{{ $allMenus[$mKey]['name'] }}
</span>
                                                @php $shownCount++; @endphp
                                            @endif
                                        @endforeach
                                        @if(count($roleMenuKeys) > 4)
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"
                                                title="{{ count($roleMenuKeys) }} total menus">
                                                +{{ count($roleMenuKeys) - 4 }} more ({{ count($roleMenuKeys) }} total)
</span>
                                        @elseif(count($roleMenuKeys) === 0)
                                            <span class="badge bg-danger-subtle text-danger">No Menus Assigned</span>
                                        @endif
</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-light text-dark border fw-semibold px-2 py-1">
                                        <i
                                            class="bi bi-people me-1 text-muted"></i>{{ $role->users_count ?? $role->users->count() }}
</span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <button class="dropdown-item edit-role-btn" type="button"
                                                    data-id="{{ $role->id }}">
                                                    <i class="bi bi-pencil text-primary me-2"></i>Edit Role & Menus
</button>
                                            </li>
                                            @if(!$role->is_system)
                                                <li>
                                                    <hr class="dropdown-divider my-1">
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger delete-role-btn" type="button"
                                                        data-id="{{ $role->id }}" data-name="{{ $role->name }}">
                                                        <i class="bi bi-trash3 text-danger me-2"></i>Delete Role
</button>
                                                </li>
                                            @endif
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No roles found. Click "+ Create New Role" to
                                    get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>
</div>

        <!-- SECTION 2: User Creation & Accounts List (Second Section - Replaces Static Matrix) -->
        <div class="card-custom overflow-hidden mb-4">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-people-fill text-primary me-2"></i>2. System User Accounts 
</h6>
</div>
                <span class="badge bg-primary" id="usersCountBadge">{{ count($users) }} Registered Users</span>
</div>
            <div class="table-responsive">
                <table class="table table-custom table-hover align-middle mb-0" id="usersTable">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th class="text-end" style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        @forelse($users as $user)
                            @php
                                $roleName = $user->roleName();
                                $badgeClass = $user->roleBadgeClass();
                                $initials = strtoupper(substr($user->name, 0, 2));
                            @endphp
                            <tr data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}"
                                data-phone="{{ $user->phone }}" data-role-id="{{ $user->role_id }}"
                                data-role="{{ $user->roleValue() }}" data-status="{{ $user->status }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold me-2"
                                            style="width: 34px; height: 34px; font-size: 0.82rem;">
                                            {{ $initials }}
</div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                                            <small class="text-muted">{{ $roleName }}</small>
</div>
</div>
                                </td>
                                <td>
                                    <span class="text-dark font-monospace small">{{ $user->email }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }} border">{{ $roleName }}</span>
                                </td>
                                <td>
                                    <span class="font-monospace small">{{ $user->phone ?: '—' }}</span>
                                </td>
                                <td>
                                    @if($user->status === 'Active')
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted">Inactive</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $user->created_at ? $user->created_at->format('d M Y, h:i A') : 'Default' }}
                                </td>
                                <td class="text-end">
                                    <div class="dropdown table-action-dropdown">
                                        <button class="btn table-action-btn" type="button" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Actions">
                                            <i class="bi bi-three-dots-vertical"></i>
</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <button class="dropdown-item reset-pwd-btn" type="button"
                                                    data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                    <i class="bi bi-key-fill text-warning me-2"></i>Reset Password
</button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item edit-user-btn" type="button"
                                                    data-id="{{ $user->id }}">
                                                    <i class="bi bi-pencil text-primary me-2"></i>Edit User
</button>
                                            </li>
                                            @if($user->email !== 'owner@deiveni.com')
                                                <li>
                                                    <hr class="dropdown-divider my-1">
                                                </li>
                                                <li>
                                                    <button class="dropdown-item text-danger delete-user-btn" type="button"
                                                        data-id="{{ $user->id }}" data-name="{{ $user->name }}">
                                                        <i class="bi bi-trash3 text-danger me-2"></i>Delete User
</button>
                                                </li>
                                            @endif
                                        </ul>
</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No users found. Click "+ Add System User" to
                                    create an account.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
</div>
</div>

</div>

    <!-- Role Add / Edit Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form id="roleForm" class="modal-content" style="max-height: 85vh;" novalidate autocomplete="off">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="roleModalTitle">Create New Role & Assign Menus</h5>
                        <small class="text-muted">Enter role name and select the menus this role should have access
                            to.</small>
</div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <div class="modal-body" style="overflow-y: auto; max-height: calc(85vh - 130px);">
                    <input type="hidden" id="roleEditId">

                    <!-- Role Details -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-7">
                            <label class="form-label small fw-semibold" for="formRoleName">Role Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formRoleName" name="name"
                                placeholder="e.g. Site Supervisor, Junior Accountant" required>
                            <div class="invalid-feedback" id="errRoleName"></div>
</div>
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-semibold" for="formRoleDescription">Description</label>
                            <input type="text" class="form-control" id="formRoleDescription" name="description"
                                placeholder="Brief note on duties">
                            <div class="invalid-feedback" id="errRoleDescription"></div>
</div>
</div>

                    <!-- Menu Permissions Checkboxes -->
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-check2-square text-primary me-2"></i>Menu Access Permissions 
</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" id="selectAllMenusBtn"
                                style="font-size: 0.78rem;">
                                Select All
</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
                                id="deselectAllMenusBtn" style="font-size: 0.78rem;">
                                Deselect All
</button>
</div>
</div>
                    <div class="invalid-feedback d-block mb-2" id="errRoleMenus" style="display: none;"></div>

                    @foreach($menuSections as $sectionName => $sectionMenus)
                        <div class="mb-3 p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-uppercase text-secondary tracking-wide">
                                    <i class="bi bi-folder2-open me-1 text-primary"></i>{{ $sectionName }}
</span>
                                <button type="button"
                                    class="btn btn-link btn-sm text-decoration-none p-0 toggle-section-btn text-muted"
                                    data-section="{{ Str::slug($sectionName) }}" style="font-size: 0.75rem;">
                                    Toggle Section
</button>
</div>
                            <div class="row g-2 section-checkbox-group" data-section="{{ Str::slug($sectionName) }}">
                                @foreach($sectionMenus as $menuKey => $menu)
                                    <div class="col-12 col-sm-6 col-md-4">
                                        <div class="form-check p-2 bg-white rounded border h-100 d-flex align-items-center">
                                            <input class="form-check-input menu-checkbox ms-1 me-2" type="checkbox" name="menus[]"
                                                value="{{ $menuKey }}" id="menu_{{ $menuKey }}"
                                                data-section="{{ Str::slug($sectionName) }}">
                                            <label class="form-check-label small d-flex flex-column" for="menu_{{ $menuKey }}"
                                                style="cursor: pointer;">
                                                <span class="fw-semibold text-dark">
                                                    <i
                                                        class="{{ $menu['icon'] ?? 'bi bi-app' }} me-1 text-primary"></i>{{ $menu['name'] }}
                                                    @if(in_array($menuKey, ['users', 'audit_logs'], true))
                                                        <span class="badge bg-secondary-subtle text-secondary border ms-1"
                                                            style="font-size: 0.65rem;">Owner Only</span>
                                                    @endif
</span>
                                                @if(!empty($menu['name_ta']))
                                                    <span class="ta-text text-muted"
                                                        style="font-size: 0.72rem;">{{ $menu['name_ta'] }}</span>
                                                @endif
</label>
</div>
</div>
                                @endforeach
</div>
</div>
                    @endforeach

</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-deiveni btn-sm" id="saveRoleSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="saveRoleSpinner" role="status"
                            aria-hidden="true"></span>
                        <span id="saveRoleBtnText">Save Role</span>
</button>
</div>
            </form>
</div>
</div>

    <!-- User Add / Edit Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="userModalTitle">Add System User</h5>
                        <small class="text-muted">Create an account and assign a role.</small>
</div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="userForm" novalidate autocomplete="off">
                    <div class="modal-body">
                        <input type="hidden" id="userEditId">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="formUserName">Full Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="formUserName" name="name"
                                    placeholder="e.g. Ramesh Kumar" required>
                                <div class="invalid-feedback" id="errUserName"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="formUserEmail">Email Address <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="formUserEmail" name="email"
                                    placeholder="user@deiveni.com" required>
                                <div class="invalid-feedback" id="errUserEmail"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="formUserPhone">Phone Number <span
                                        class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="formUserPhone" name="phone"
                                    placeholder="98400 00000" required>
                                <div class="invalid-feedback" id="errUserPhone"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="formUserRole">Assigned Role  <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="formUserRole" name="role_id" required>
                                    <option value="" disabled selected>-- Select Dynamic Role --</option>
                                    @foreach($roles as $roleOption)
                                        <option value="{{ $roleOption->id }}">{{ $roleOption->name }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="errUserRole"></div>
</div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="formUserStatus">Account Status</label>
                                <select class="form-select" id="formUserStatus" name="status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback" id="errUserStatus"></div>
</div>
                            <div class="col-12" id="passwordFieldGroup">
                                <label class="form-label small fw-semibold" for="formUserPassword">Password <span
                                        class="text-muted">(Default:
                                        Deiveni@2026)</span></label>
                                <div class="input-group has-validation">
                                    <input type="password" class="form-control" id="formUserPassword" name="password"
                                        placeholder="Leave empty for default" autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleUserPasswordBtn"
                                        title="Show/Hide Password" tabindex="-1">
                                        <i class="bi bi-eye" id="toggleUserPasswordIcon"></i>
</button>
                                    <div class="invalid-feedback" id="errUserPassword"></div>
</div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-deiveni btn-sm" id="saveUserSubmitBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="saveUserSpinner" role="status"
                                aria-hidden="true"></span>
                            <span id="saveUserBtnText">Save User</span>
</button>
</div>
                </form>
</div>
</div>
</div>

    <!-- Password Reset Modal -->
    <div class="modal fade" id="passwordResetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Reset Password</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
                <form id="passwordResetForm" novalidate autocomplete="off">
                    <input type="hidden" id="resetUserId">
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Reset password for <strong id="resetTargetUser">User</strong>:</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="newTempPassword">New Temporary Password <span
                                    class="text-danger">*</span></label>
                            <div class="input-group input-group-sm has-validation">
                                    <input type="password" class="form-control" id="newTempPassword" name="password"
                                    value="" required minlength="6" autocomplete="new-password">
                                <button class="btn btn-outline-secondary" type="button" id="toggleResetPasswordBtn"
                                    title="Show/Hide Password" tabindex="-1">
                                    <i class="bi bi-eye" id="toggleResetPasswordIcon"></i>
</button>
                                <div class="invalid-feedback" id="errResetPassword"></div>
</div>
</div>
</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning btn-sm text-dark fw-semibold"
                            id="resetPasswordSubmitBtn">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="resetPasswordSpinner"
                                role="status" aria-hidden="true"></span>
                            <span id="resetPasswordBtnText">Reset Password</span>
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
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            // ==========================================
            // Validation Helpers
            // ==========================================
            function clearRoleErrors() {
                $('#roleForm .is-invalid').removeClass('is-invalid');
                $('#roleForm .invalid-feedback').text('').hide();
            }

            function displayRoleErrors(errors) {
                clearRoleErrors();
                if (!errors) return;

                if (errors.name) {
                    $('#formRoleName').addClass('is-invalid');
                    $('#errRoleName').text(errors.name[0]).show();
                }
                if (errors.description) {
                    $('#formRoleDescription').addClass('is-invalid');
                    $('#errRoleDescription').text(errors.description[0]).show();
                }
                if (errors.menus) {
                    $('#errRoleMenus').text(errors.menus[0]).show();
                }
            }

            function clearUserErrors() {
                $('#userForm .is-invalid').removeClass('is-invalid');
                $('#userForm .invalid-feedback').text('').hide();
            }

            function displayUserErrors(errors) {
                clearUserErrors();
                if (!errors) return;

                if (errors.name) {
                    $('#formUserName').addClass('is-invalid');
                    $('#errUserName').text(errors.name[0]).show();
                }
                if (errors.email) {
                    $('#formUserEmail').addClass('is-invalid');
                    $('#errUserEmail').text(errors.email[0]).show();
                }
                if (errors.phone) {
                    $('#formUserPhone').addClass('is-invalid');
                    $('#errUserPhone').text(errors.phone[0]).show();
                }
                if (errors.role_id) {
                    $('#formUserRole').addClass('is-invalid');
                    $('#errUserRole').text(errors.role_id[0]).show();
                }
                if (errors.status) {
                    $('#formUserStatus').addClass('is-invalid');
                    $('#errUserStatus').text(errors.status[0]).show();
                }
                if (errors.password) {
                    $('#formUserPassword').addClass('is-invalid');
                    $('#errUserPassword').text(errors.password[0]).show();
                }
            }

            function clearPasswordResetErrors() {
                $('#passwordResetForm .is-invalid').removeClass('is-invalid');
                $('#passwordResetForm .invalid-feedback').text('').hide();
            }

            function displayPasswordResetErrors(errors) {
                clearPasswordResetErrors();
                if (!errors) return;

                if (errors.password) {
                    $('#newTempPassword').addClass('is-invalid');
                    $('#errResetPassword').text(errors.password[0]).show();
                }
            }

            // Real-time validation clearance on user input
            $('#roleForm input, #roleForm textarea').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
            });

            $('#userForm input, #userForm select').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
                $(this).closest('.input-group').find('.invalid-feedback').text('').hide();
            });

            $('#passwordResetForm input').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).closest('.input-group').find('.invalid-feedback').text('').hide();
            });

            // ==========================================
            // 1. ROLE MANAGEMENT (Checkboxes & Creation)
            // ==========================================

            // Open Add Role Modal
            $('#addRoleBtn').on('click', function () {
                $('#roleModalTitle').text('Create New Role & Assign Menus');
                $('#saveRoleBtnText').text('Save Role');
                $('#roleEditId').val('');
                $('#roleForm')[0].reset();
                $('#formRoleName').val('').prop('readonly', false);
                $('#formRoleDescription').val('');
                clearRoleErrors();

                // Reset all checkboxes to enabled & unchecked
                $('.menu-checkbox').prop('checked', false).prop('disabled', false);

                // Task 2: Administration access is strictly for Owner role (disabled & unchecked for new roles)
                $('#menu_users').prop('checked', false).prop('disabled', true);
                $('#menu_audit_logs').prop('checked', false).prop('disabled', true);
            });

            // Select All / Deselect All Menus
            $('#selectAllMenusBtn').on('click', function () {
                $('.menu-checkbox:not(:disabled)').prop('checked', true);
            });

            $('#deselectAllMenusBtn').on('click', function () {
                $('.menu-checkbox:not(:disabled)').prop('checked', false);
            });

            // Section Toggle
            $('.toggle-section-btn').on('click', function () {
                const sec = $(this).data('section');
                const checkboxes = $(`.section-checkbox-group[data-section="${sec}"] .menu-checkbox:not(:disabled)`);
                if (checkboxes.length > 0) {
                    const allChecked = checkboxes.length === checkboxes.filter(':checked').length;
                    checkboxes.prop('checked', !allChecked);
                }
            });

            // Edit Role Click
            $(document).on('click', '.edit-role-btn', function () {
                const roleId = $(this).data('id');
                $('#roleModalTitle').text('Edit Role & Menu Permissions');
                $('#saveRoleBtnText').text('Update Role');
                $('#roleEditId').val(roleId);
                clearRoleErrors();
                $('.menu-checkbox').prop('checked', false).prop('disabled', false);

                $.get('{{ route("roles.show", ":id") }}'.replace(':id', roleId), function (response) {
                    if (response.success) {
                        const role = response.data;
                        $('#formRoleName').val(role.name);
                        $('#formRoleDescription').val(role.description || '');

                        const isOwner = role.is_system && (role.slug === 'owner' || role.slug === 'admin' || (role.name && role.name.toLowerCase() === 'owner'));

                        if (isOwner) {
                            $('#formRoleName').prop('readonly', true);
                            $('.menu-checkbox').prop('disabled', false);

                            if (role.menus && Array.isArray(role.menus)) {
                                role.menus.forEach(function (menuKey) {
                                    $(`#menu_${menuKey}`).prop('checked', true);
                                });
                            }

                            // Task 2: Only owner is defaultly checked and disabled for administration access
                            $('#menu_users').prop('checked', true).prop('disabled', true);
                            $('#menu_audit_logs').prop('checked', true).prop('disabled', true);
                        } else {
                            $('#formRoleName').prop('readonly', role.is_system);
                            $('.menu-checkbox').prop('disabled', false);

                            if (role.menus && Array.isArray(role.menus)) {
                                role.menus.forEach(function (menuKey) {
                                    $(`#menu_${menuKey}`).prop('checked', true);
                                });
                            }

                            // Task 2: Administration access is strictly for Owner role (disabled and unchecked)
                            $('#menu_users').prop('checked', false).prop('disabled', true);
                            $('#menu_audit_logs').prop('checked', false).prop('disabled', true);
                        }

                        const modal = new bootstrap.Modal(document.getElementById('roleModal'));
                        modal.show();
                    }
                }).fail(function (xhr) {
                    showToast(xhr.responseJSON?.message || 'Error fetching role details', 'danger');
                });
            });

            // Save Role (Create or Update)
            $('#roleForm').on('submit', function (e) {
                e.preventDefault();
                clearRoleErrors();

                const roleId = $('#roleEditId').val();
                const roleName = $('#formRoleName').val().trim();
                const description = $('#formRoleDescription').val().trim();

                let checkedMenus = $('.menu-checkbox:checked').map(function () {
                    return $(this).val();
                }).get();

                const isOwner = (roleName.toLowerCase() === 'owner') || (roleId && String(roleId) === '1');

                if (isOwner) {
                    // Task 2: Owner always retains administration access
                    if (!checkedMenus.includes('users')) checkedMenus.push('users');
                    if (!checkedMenus.includes('audit_logs')) checkedMenus.push('audit_logs');
                } else {
                    // Non-owners can never have administration access
                    checkedMenus = checkedMenus.filter(m => m !== 'users' && m !== 'audit_logs');
                }

                const payload = {
                    name: roleName,
                    description: description,
                    menus: checkedMenus
                };

                const isUpdate = Boolean(roleId);
                const url = isUpdate ? '{{ route("roles.update", ":id") }}'.replace(':id', roleId) : '{{ route("roles.store") }}';
                const method = isUpdate ? 'POST' : 'POST';

                const $btn = $('#saveRoleSubmitBtn');
                const $spinner = $('#saveRoleSpinner');
                const $btnText = $('#saveRoleBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    method: method,
                    data: payload,
                    success: function (response) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isUpdate ? 'Update Role' : 'Save Role');

                        $('#roleModal').modal('hide');
                        showToast(response.message || 'Role saved successfully!', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 700);
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isUpdate ? 'Update Role' : 'Save Role');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayRoleErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const msg = xhr.responseJSON?.message || 'Error saving role';
                            showToast(msg, 'danger');
                        }
                    }
                });
            });

            // Delete Role
            $(document).on('click', '.delete-role-btn', function () {
                const roleId = $(this).data('id');
                const roleName = $(this).data('name');

                confirmDelete(`Are you sure you want to delete role "${roleName}"?`, function () {
                    $.ajax({
                        url: '{{ route("roles.destroy", ":id") }}'.replace(':id', roleId),
                        method: 'POST',
                        success: function (response) {
                            showToast(response.message || 'Role deleted!', 'success');
                            setTimeout(function () {
                                window.location.reload();
                            }, 600);
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Error deleting role';
                            showToast(msg, 'danger');
                        }
                    });
                });
            });

            // ==========================================
            // 2. USER MANAGEMENT
            // ==========================================

            // Open Add User Modal
            $('#addUserBtn').on('click', function () {
                $('#userModalTitle').text('Add System User');
                $('#saveUserBtnText').text('Save User');
                $('#userEditId').val('');
                $('#userForm')[0].reset();
                $('#passwordFieldGroup').show();
                $('#formUserPassword').prop('required', false);
                clearUserErrors();
            });

            // Edit User Click
            $(document).on('click', '.edit-user-btn', function () {
                const userId = $(this).data('id');
                $('#userModalTitle').text('Edit System User');
                $('#saveUserBtnText').text('Update User');
                $('#userEditId').val(userId);
                $('#passwordFieldGroup').hide();
                clearUserErrors();

                $.get('{{ route("users.show", ":id") }}'.replace(':id', userId), function (response) {
                    if (response.success) {
                        const user = response.data;
                        $('#formUserName').val(user.name);
                        $('#formUserEmail').val(user.email);
                        $('#formUserPhone').val(user.phone || '');
                        $('#formUserRole').val(user.role_id);
                        $('#formUserStatus').val(user.status);

                        const modal = new bootstrap.Modal(document.getElementById('userModal'));
                        modal.show();
                    }
                }).fail(function (xhr) {
                    showToast(xhr.responseJSON?.message || 'Error fetching user details', 'danger');
                });
            });

            // Save User Form
            $('#userForm').on('submit', function (e) {
                e.preventDefault();
                clearUserErrors();

                const userId = $('#userEditId').val();
                const payload = {
                    name: $('#formUserName').val().trim(),
                    email: $('#formUserEmail').val().trim(),
                    phone: $('#formUserPhone').val().trim(),
                    role_id: $('#formUserRole').val(),
                    status: $('#formUserStatus').val(),
                    password: $('#formUserPassword').val() || undefined
                };

                const isUpdate = Boolean(userId);
                const url = isUpdate ? '{{ route("users.update", ":id") }}'.replace(':id', userId) : '{{ route("users.store") }}';
                const method = isUpdate ? 'POST' : 'POST';

                const $btn = $('#saveUserSubmitBtn');
                const $spinner = $('#saveUserSpinner');
                const $btnText = $('#saveUserBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: url,
                    method: method,
                    data: payload,
                    success: function (response) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isUpdate ? 'Update User' : 'Save User');

                        $('#userModal').modal('hide');
                        showToast(response.message || 'User saved successfully!', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 700);
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text(isUpdate ? 'Update User' : 'Save User');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayUserErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const msg = xhr.responseJSON?.message || 'Error saving user';
                            showToast(msg, 'danger');
                        }
                    }
                });
            });

            // Reset Password Modal
            $(document).on('click', '.reset-pwd-btn', function () {
                const userId = $(this).data('id');
                const userName = $(this).data('name');

                $('#resetUserId').val(userId);
                $('#resetTargetUser').text(userName);
                $('#newTempPassword').val('').attr('type', 'password');
                $('#toggleResetPasswordIcon').removeClass('bi-eye-slash').addClass('bi-eye');
                $('#resetPasswordBtnText').text('Reset Password');
                clearPasswordResetErrors();

                const modal = new bootstrap.Modal(document.getElementById('passwordResetModal'));
                modal.show();
            });

            // Toggle Password Visibility (Reset Password Modal)
            $('#toggleResetPasswordBtn').on('click', function () {
                const input = $('#newTempPassword');
                const icon = $('#toggleResetPasswordIcon');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });

            // Toggle Password Visibility (Add/Edit User Modal)
            $('#toggleUserPasswordBtn').on('click', function () {
                const input = $('#formUserPassword');
                const icon = $('#toggleUserPasswordIcon');
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('bi-eye').addClass('bi-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('bi-eye-slash').addClass('bi-eye');
                }
            });

            // Reset password field types & clear errors on modal close
            $('#passwordResetModal').on('hidden.bs.modal', function () {
                $('#newTempPassword').val('');
                $('#resetUserId').val('');
                $('#newTempPassword').attr('type', 'password');
                $('#toggleResetPasswordIcon').removeClass('bi-eye-slash').addClass('bi-eye');
                clearPasswordResetErrors();
            });
            $('#userModal').on('hidden.bs.modal', function () {
                $('#formUserPassword').attr('type', 'password');
                $('#toggleUserPasswordIcon').removeClass('bi-eye-slash').addClass('bi-eye');
                clearUserErrors();
            });
            $('#roleModal').on('hidden.bs.modal', function () {
                clearRoleErrors();
            });

            $('#passwordResetForm').on('submit', function (e) {
                e.preventDefault();
                clearPasswordResetErrors();

                const userId = $('#resetUserId').val();
                const newPassword = $('#newTempPassword').val();

                const $btn = $('#resetPasswordSubmitBtn');
                const $spinner = $('#resetPasswordSpinner');
                const $btnText = $('#resetPasswordBtnText');

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Resetting...');

                $.ajax({
                    url: '{{ route("users.reset-password", ":id") }}'.replace(':id', userId),
                    method: 'POST',
                    data: { password: newPassword },
                    success: function (response) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Reset Password');

                        $('#passwordResetModal').modal('hide');
                        showToast(response.message || 'Password reset successfully!', 'success');
                        setTimeout(function () {
                            window.location.reload();
                        }, 500);
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Reset Password');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayPasswordResetErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const msg = xhr.responseJSON?.message || 'Error resetting password';
                            showToast(msg, 'danger');
                        }
                    }
                });
            });

            // Delete User
            $(document).on('click', '.delete-user-btn', function () {
                const userId = $(this).data('id');
                const userName = $(this).data('name');

                confirmDelete(`Are you sure you want to delete user account "${userName}"?`, function () {
                    $.ajax({
                        url: '{{ route("users.destroy", ":id") }}'.replace(':id', userId),
                        method: 'POST',
                        success: function (response) {
                            showToast(response.message || 'User deleted!', 'success');
                            setTimeout(function () {
                                window.location.reload();
                            }, 600);
                        },
                        error: function (xhr) {
                            const msg = xhr.responseJSON?.message || 'Error deleting user';
                            showToast(msg, 'danger');
                        }
                    });
                });
            });
        });
    </script>
@endpush
