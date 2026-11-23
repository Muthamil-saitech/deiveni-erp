@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'My Profile Settings (சுயவிவரம்)')

@section('content')
    <div class="container-fluid px-0">

        <div class="row g-4 justify-content-center">
            <!-- Profile Summary Card -->
            <div class="col-12 col-lg-4">
                <div class="card-custom p-4 bg-white text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold fs-2 mb-3 shadow"
                        style="width: 80px; height: 80px;" id="profileAvatarLarge">
                        {{ strtoupper(substr($user->name ?? 'SM', 0, 2)) }}
                    </div>
                    <h5 class="fw-bold text-dark mb-1" id="profileCardName">{{ $user->name }}</h5>
                    <p class="text-muted small mb-2" id="profileCardEmail">{{ $user->email }}</p>
                    <div class="mb-3">
                        <span class="badge {{ $user->role?->badgeClass() ?? 'bg-primary' }} px-3 py-2 fs-6"
                            id="profileCardRole">
                            {{ $user->role?->label() ?? 'User' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Profile Update & Password Change Forms -->
            <div class="col-12 col-lg-8">
                <div class="card-custom p-4 bg-white mb-4">
                    <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">
                        <i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Details
                    </h6>

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-exclamation-octagon-fill me-2"></i>{{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="profileInfoForm" method="POST" action="{{ route('profile.update') }}" novalidate autocomplete="off">
                        @csrf
                        @method('POST')
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="profileName">Full Name <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="profileName"
                                    name="name" value="{{ old('name', $user->name) }}">
                                <div class="invalid-feedback" id="profileNameError">
                                    @error('name') {{ $message }} @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="profileEmail">Email Address <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="profileEmail" name="email" value="{{ old('email', $user->email) }}">
                                <div class="invalid-feedback" id="profileEmailError">
                                    @error('email') {{ $message }} @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="profilePhone">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                    id="profilePhone" name="phone" value="{{ old('phone', $user->phone) }}"
                                    placeholder="e.g. 98400 22222">
                                <div class="invalid-feedback" id="profilePhoneError">
                                    @error('phone') {{ $message }} @enderror
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-deiveni btn-sm" id="saveProfileBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="saveProfileSpinner"
                                        role="status" aria-hidden="true"></span>
                                    <span id="saveProfileBtnText">Save Profile Changes</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Password Change Section (PRD 4.2: Current password required) -->
                <div class="card-custom p-4 bg-white">
                    <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">
                        <i class="bi bi-shield-lock-fill text-danger me-2"></i>Change Password (கடவுச்சொல் மாற்றம்)
                    </h6>
                    <form id="profilePasswordForm" method="POST" action="{{ route('profile.password.update') }}" novalidate autocomplete="off">
                        @csrf
                        @method('POST')
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="currentPassword">Current Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                                    id="currentPassword" name="current_password" placeholder="Enter your current password"
                                    autocomplete="current-password">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Current password is required to
                                    authorise password change.</small>
                                <div class="invalid-feedback" id="currentPasswordError">
                                    @error('current_password') {{ $message }} @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="newPassword">New Password <span
                                        class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('new_password') is-invalid @enderror"
                                    id="newPassword" name="new_password" placeholder="Minimum 8 characters"
                                    autocomplete="new-password">
                                <div class="invalid-feedback" id="newPasswordError">
                                    @error('new_password') {{ $message }} @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label small fw-semibold" for="confirmPassword">Confirm New Password <span
                                        class="text-danger">*</span></label>
                                <input type="password"
                                    class="form-control @error('new_password_confirmation') is-invalid @enderror"
                                    id="confirmPassword" name="new_password_confirmation"
                                    placeholder="Re-enter new password" autocomplete="new-password">
                                <div class="invalid-feedback" id="confirmPasswordError">
                                    @error('new_password_confirmation') {{ $message }} @enderror
                                </div>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-danger btn-sm" id="updatePasswordBtn">
                                    <span class="spinner-border spinner-border-sm d-none me-1" id="updatePasswordSpinner"
                                        role="status" aria-hidden="true"></span>
                                    <span id="updatePasswordBtnText">Update Password</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            @if (session('success'))
                showToast(@json(session('success')), 'success');
            @endif
            @if ($errors->any())
                showToast(@json($errors->first()), 'danger');
            @endif

            function clearProfileErrors() {
                $('#profileInfoForm .form-control').removeClass('is-invalid');
                $('#profileInfoForm .invalid-feedback').text('').hide();
            }

            function clearPasswordErrors() {
                $('#profilePasswordForm .form-control').removeClass('is-invalid');
                $('#profilePasswordForm .invalid-feedback').text('').hide();
            }

            function displayProfileErrors(errors) {
                clearProfileErrors();
                if (!errors) return;

                if (errors.name) {
                    $('#profileName').addClass('is-invalid');
                    $('#profileNameError').text(errors.name[0]).show();
                }
                if (errors.email) {
                    $('#profileEmail').addClass('is-invalid');
                    $('#profileEmailError').text(errors.email[0]).show();
                }
                if (errors.phone) {
                    $('#profilePhone').addClass('is-invalid');
                    $('#profilePhoneError').text(errors.phone[0]).show();
                }
            }

            function displayPasswordErrors(errors) {
                clearPasswordErrors();
                if (!errors) return;

                if (errors.current_password) {
                    $('#currentPassword').addClass('is-invalid');
                    $('#currentPasswordError').text(errors.current_password[0]).show();
                }
                if (errors.new_password) {
                    $('#newPassword').addClass('is-invalid');
                    $('#newPasswordError').text(errors.new_password[0]).show();
                }
                if (errors.new_password_confirmation) {
                    $('#confirmPassword').addClass('is-invalid');
                    $('#confirmPasswordError').text(errors.new_password_confirmation[0]).show();
                }
            }

            $('#profileInfoForm input, #profilePasswordForm input').on('input change', function () {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').text('').hide();
            });

            // Profile Info Update via AJAX
            $('#profileInfoForm').on('submit', function (e) {
                e.preventDefault();
                clearProfileErrors();

                const $btn = $('#saveProfileBtn');
                const $spinner = $('#saveProfileSpinner');
                const $btnText = $('#saveProfileBtnText');

                const postData = {
                    _token: '{{ csrf_token() }}',
                    _method: 'POST',
                    name: $('#profileName').val(),
                    email: $('#profileEmail').val(),
                    phone: $('#profilePhone').val()
                };

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Saving...');

                $.ajax({
                    url: '{{ route("profile.update") }}',
                    method: 'POST',
                    data: postData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Save Profile Changes');

                        if (res.success && res.data) {
                            const data = res.data;
                            $('#profileCardName, #navUserName, #userName').text(data.name);
                            $('#profileCardEmail, #userEmail').text(data.email);
                            if (data.phone) {
                                $('#profileCardPhoneText').text('+91 ' + data.phone);
                            } else {
                                $('#profileCardPhoneText').text('No phone provided');
                            }
                            if (data.avatar) {
                                $('#profileAvatarLarge, #userAvatar, #navAvatar').text(data.avatar);
                            }

                            showToast(res.message || 'Profile information updated successfully (சுயவிவரம் வெற்றிகரமாக மாற்றப்பட்டது)!', 'success');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Save Profile Changes');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayProfileErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : 'An error occurred while updating profile.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });

            // Password Update via AJAX
            $('#profilePasswordForm').on('submit', function (e) {
                e.preventDefault();
                clearPasswordErrors();

                const currentPassword = $('#currentPassword').val();
                const newPassword = $('#newPassword').val();
                const confirmPassword = $('#confirmPassword').val();

                const $btn = $('#updatePasswordBtn');
                const $spinner = $('#updatePasswordSpinner');
                const $btnText = $('#updatePasswordBtnText');

                const postData = {
                    _token: '{{ csrf_token() }}',
                    _method: 'POST',
                    current_password: currentPassword,
                    new_password: newPassword,
                    new_password_confirmation: confirmPassword
                };

                $btn.prop('disabled', true);
                $spinner.removeClass('d-none');
                $btnText.text('Updating...');

                $.ajax({
                    url: '{{ route("profile.password.update") }}',
                    method: 'POST',
                    data: postData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    success: function (res) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Update Password');

                        $('#profilePasswordForm')[0].reset();
                        clearPasswordErrors();
                        showToast(res.message || 'Password updated successfully (கடவுச்சொல் வெற்றிகரமாக மாற்றப்பட்டது)!', 'success');
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false);
                        $spinner.addClass('d-none');
                        $btnText.text('Update Password');

                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            displayPasswordErrors(xhr.responseJSON.errors);
                            const firstErrKey = Object.keys(xhr.responseJSON.errors)[0];
                            showToast(xhr.responseJSON.errors[firstErrKey][0], 'danger');
                        } else {
                            const errorMsg = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : 'An error occurred while updating password.';
                            showToast(errorMsg, 'danger');
                        }
                    }
                });
            });
        });
    </script>
@endpush
