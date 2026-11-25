<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login | Deiveni Construction Management System</title>

    <!-- Google Fonts: Plus Jakarta Sans & Noto Sans Tamil -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
           --deiveni-primary: #1e3a8a;
           --deiveni-primary-hover: #1d4ed8;
           --deiveni-accent: #f59e0b;
           --deiveni-accent-dark: #d97706;
           --deiveni-dark: #0f172a;
           --deiveni-slate: #1e293b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans Tamil', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--deiveni-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient glowing background orbs */
        .ambient-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            background: radial-gradient(circle at 15% 20%, rgba(30, 58, 138, 0.45) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(217, 119, 6, 0.18) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(15, 23, 42, 0.8) 0%, #090d16 100%);
        }

        /* Subtle architectural grid pattern overlay */
        .ambient-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 44px 44px;
            opacity: 0.8;
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card-header-gradient {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            padding: 2.2rem 2rem 1.4rem;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }

        .brand-logo-icon {
            width: 58px;
            height: 58px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.75rem;
            box-shadow: 0 8px 20px rgba(217, 119, 6, 0.35);
            margin-bottom: 0.9rem;
            transition: transform 0.25s ease;
        }

        .brand-logo-icon:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 0.2rem;
        }

        .brand-subtitle-ta {
            font-family: 'Noto Sans Tamil', sans-serif;
            color: #d97706;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-block;
        }

        .card-body-content {
            padding: 1.8rem 2rem 2rem;
        }

        .form-floating-custom {
            position: relative;
            margin-bottom: 1.2rem;
        }

        .form-floating-custom .form-control {
            height: 52px;
            padding: 0.75rem 1rem 0.75rem 2.85rem;
            font-size: 0.925rem;
            border-radius: 12px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .form-floating-custom .form-control:focus {
            background-color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        .form-floating-custom .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.15rem;
            transition: color 0.2s ease;
            pointer-events: none;
        }

        .form-floating-custom .form-control:focus~.input-icon {
            color: #2563eb;
        }

        .toggle-password-btn {
            position: absolute;
            top: 50%;
            right: 0.75rem;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #94a3b8;
            padding: 0.35rem 0.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: color 0.15s ease;
            font-size: 1.05rem;
        }

        .toggle-password-btn:hover {
            color: #334155;
        }

        .btn-submit-login {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #2563eb 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.965rem;
            letter-spacing: 0.02em;
            padding: 0.85rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.35);
            transition: all 0.25s ease;
            width: 100%;
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-submit-login:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 50%, #3b82f6 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(37, 99, 235, 0.4);
            color: #ffffff;
        }

        .btn-submit-login:active {
            transform: translateY(0);
        }

        .form-check-input:checked {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
        }

        .footer-note {
            text-align: center;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            color: #64748b;
            font-size: 0.785rem;
        }

        .security-badge {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            border-radius: 30px;
            padding: 0.4rem 1rem;
            font-size: 0.76rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.25rem;
        }

        .security-badge i {
            color: #10b981;
        }

        .ta-text {
            font-family: 'Noto Sans Tamil', sans-serif;
        }

        @media (max-width: 576px) {
            .card-header-gradient {
                padding: 1.75rem 1.5rem 1.2rem;
            }

            .card-body-content {
                padding: 1.4rem 1.5rem 1.75rem;
            }

            .brand-title {
                font-size: 1.15rem;
            }
        }

        .role-demo-pill {
            padding: 0.4rem 0.5rem;
            font-size: 0.76rem;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            flex: 1;
        }

        .role-demo-pill:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .role-demo-pill.active {
            background: #1e3a8a;
            color: #ffffff;
            border-color: #1e3a8a;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }

        .role-demo-pill.active small {
            color: rgba(255, 255, 255, 0.75) !important;
        }
    </style>
</head>

<body>

    <!-- Ambient Glowing Background Elements -->
    <div class="ambient-bg">
        <div class="ambient-grid"></div>
</div>

    <!-- Main Login Card Container -->
    <div class="login-wrapper">
        <div class="login-card">

            <!-- Card Header & Branding -->
            <div class="card-header-gradient">
                <div class="brand-logo-icon">
                    <i class="fa-solid fa-helmet-safety"></i>
</div>
                <h4 class="brand-title">DEIVENI CONSTRUCTION</h4>
                <div class="brand-subtitle-ta">Construction &amp; Project ERP</div>
                <p class="text-muted small mt-1 mb-0">Daily Wages, Attendance & Financial Ledger ERP</p>
</div>

            <!-- Card Body: Credentials Form -->
            <div class="card-body-content">

                <!-- 3 Role Fast-Access Switcher -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted d-flex justify-content-between mb-1">
                        <span>Select Role for Quick Login</span>
</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="role-demo-pill" id="pillOwner" onclick="selectRole('owner')">
                            <span><i class="fa-solid fa-crown me-1 text-warning"></i> Owner</span>
                            <small class="text-muted">Admin</small>
</button>
                        <button type="button" class="role-demo-pill active" id="pillSiteManager" onclick="selectRole('site_manager')">
                            <span><i class="fa-solid fa-hard-hat me-1 text-warning"></i> Site Mgr</span>
                            <small class="text-muted">Operations</small>
</button>
                        <button type="button" class="role-demo-pill" id="pillAccountManager" onclick="selectRole('account_manager')">
                            <span><i class="fa-solid fa-calculator me-1 text-success"></i> Acc Mgr</span>
                            <small class="text-muted">Finance</small>
</button>
</div>
</div>

                <form id="loginForm" method="POST" action="{{ route('login.post') }}" novalidate>
                    @csrf

                    <!-- Alert Message Container (for backend validation feedback) -->
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 px-3 small mb-3 d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-6 flex-shrink-0"></i>
                            <div>{{ $errors->first() }}</div>
</div>
                    @endif

                    <div id="loginAlert" class="alert alert-danger py-2 px-3 small d-none mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <span id="loginAlertText">Please enter valid login credentials.</span>
</div>

                    <!-- Email / Username Input -->
                    <div class="form-floating-custom">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="loginEmail" name="email"
                            placeholder="Email address" value="{{ old('email', 'sm@deiveni.com') }}" autocomplete="username" required>
                        <i class="bi bi-person-fill input-icon"></i>
</div>

                    <!-- Password Input -->
                    <div class="form-floating-custom">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="loginPassword" name="password"
                            placeholder="Password" value="password123" autocomplete="current-password" required>
                        <i class="bi bi-lock-fill input-icon"></i>
                        <button type="button" class="toggle-password-btn" id="togglePasswordBtn"
                            aria-label="Toggle password visibility" title="Show or hide password">
                            <i class="bi bi-eye" id="passwordEyeIcon"></i>
</button>
</div>

                    <!-- Options Row: Remember Me & Forgot Password -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember" value="1"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label small text-secondary" for="rememberMe"
                                style="font-size: 0.84rem; cursor: pointer;">
                                Remember this device
</label>
</div>
</div>

                    <!-- Sign In Action Button -->
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-submit-login" id="submitBtn">
                            <i class="bi bi-box-arrow-in-right me-2" id="submitBtnIcon"></i>
                            <span id="submitBtnText">Sign In to Workspace</span>
</button>
</div>

                    <!-- Security & Admin Info Footer -->
                    <div class="footer-note">
                        <div class="fw-semibold text-dark mb-0.5">Authorized Personnel Only</div>
                    </div>
                </form>
</div>
</div>
</div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Pre-configured role credentials for quick testing
        const roleCredentials = {
            owner: { email: 'owner@deiveni.com', pass: 'password123', pillId: 'pillOwner' },
            site_manager: { email: 'sm@deiveni.com', pass: 'password123', pillId: 'pillSiteManager' },
            account_manager: { email: 'am@deiveni.com', pass: 'password123', pillId: 'pillAccountManager' }
        };

        function selectRole(roleKey) {
            const config = roleCredentials[roleKey];
            if (!config) return;

            document.getElementById('loginEmail').value = config.email;
            document.getElementById('loginPassword').value = config.pass;

            document.querySelectorAll('.role-demo-pill').forEach(btn => btn.classList.remove('active'));
            document.getElementById(config.pillId).classList.add('active');

            // Hide previous client-side error
            const alertBox = document.getElementById('loginAlert');
            if (alertBox) alertBox.classList.add('d-none');
        }

        // Highlight active pill on initial load if old email matches
        (function initActivePill() {
            const currentEmail = (document.getElementById('loginEmail').value || '').trim().toLowerCase();
            document.querySelectorAll('.role-demo-pill').forEach(btn => btn.classList.remove('active'));

            if (currentEmail === 'owner@deiveni.com' || currentEmail.includes('owner')) {
                document.getElementById('pillOwner')?.classList.add('active');
            } else if (currentEmail === 'am@deiveni.com' || currentEmail.includes('account') || currentEmail.startsWith('am@')) {
                document.getElementById('pillAccountManager')?.classList.add('active');
            } else if (currentEmail === 'sm@deiveni.com' || currentEmail.startsWith('sm@')) {
                document.getElementById('pillSiteManager')?.classList.add('active');
            }
        })();

        // Password Visibility Toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('loginPassword');
        const eyeIcon = document.getElementById('passwordEyeIcon');

        toggleBtn.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        });

        // Form Submission with Loading Feedback
        document.getElementById('loginForm').addEventListener('submit', function (event) {
            const emailInput = document.getElementById('loginEmail');
            const passInput = document.getElementById('loginPassword');
            const alertBox = document.getElementById('loginAlert');
            const alertText = document.getElementById('loginAlertText');
            const submitBtn = document.getElementById('submitBtn');
            const submitBtnText = document.getElementById('submitBtnText');
            const submitBtnIcon = document.getElementById('submitBtnIcon');

            const email = (emailInput.value || '').trim();
            const password = (passInput.value || '').trim();

            if (!email || !password) {
                event.preventDefault();
                alertBox.classList.remove('d-none');
                alertText.innerText = 'Please enter both your email address and password.';
                return;
            }

            alertBox.classList.add('d-none');

            // Submit Button loading state
            submitBtn.disabled = true;
            submitBtnIcon.className = 'spinner-border spinner-border-sm me-2';
            submitBtnText.innerText = 'Authenticating...';
        });
    </script>
</body>

</html>