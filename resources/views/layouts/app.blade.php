<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | Deiveni Construction Management System</title>

    <!-- Google Fonts: Plus Jakarta Sans & Noto Sans Tamil -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Tamil:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
           --bs-font-sans-serif: 'Plus Jakarta Sans', 'Noto Sans Tamil', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
           --deiveni-primary: #1e3a8a;
           --deiveni-primary-dark: #0f172a;
           --deiveni-primary-light: #3b82f6;
           --deiveni-accent: #d97706;
           --deiveni-accent-light: #fef3c7;
           --deiveni-sidebar-bg: #0f172a;
           --deiveni-sidebar-hover: #1e293b;
           --deiveni-sidebar-active: #2563eb;
           --deiveni-bg: #f8fafc;
           --deiveni-card-bg: #ffffff;
           --deiveni-border: #e2e8f0;
           --deiveni-text-main: #1e293b;
           --deiveni-text-muted: #64748b;
        }

        body {
            font-family: var(--bs-font-sans-serif);
            background-color: var(--deiveni-bg);
            color: var(--deiveni-text-main);
            overflow-x: hidden;
            font-size: 0.925rem;
            padding-bottom: 60px;
            /* space for mobile bottom nav on small screens */
        }

        @media (min-width: 992px) {
            html, body {
                height: 100vh;
                overflow: hidden;
            }

            body {
                padding-bottom: 0;
            }

            .app-wrapper {
                height: 100vh;
                max-height: 100vh;
                overflow: hidden;
            }

            #sidebar {
                height: 100vh;
                max-height: 100vh;
                overflow: hidden;
                position: sticky;
                top: 0;
            }

            #sidebar .sidebar-nav {
                overflow-y: auto;
                overflow-x: hidden;
                flex: 1 1 auto;
            }

            #main-wrapper {
                height: 100vh;
                max-height: 100vh;
                overflow: hidden;
            }

            #main-content-scroll {
                height: calc(100vh - 60px);
                overflow-y: auto;
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
            }

            #main-content {
                min-height: auto;
                flex: 1 0 auto;
            }
        }

        /* Custom scrollbars for separate scrolling */
        #sidebar .sidebar-nav::-webkit-scrollbar {
            width: 5px;
        }

        #sidebar .sidebar-nav::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }

        #sidebar .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.18);
            border-radius: 4px;
        }

        #sidebar .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.35);
        }

        #main-content-scroll::-webkit-scrollbar {
            width: 7px;
        }

        #main-content-scroll::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        #main-content-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        #main-content-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media print {
            html, body, .app-wrapper, #main-wrapper, #main-content-scroll, #main-content {
                height: auto !important;
                overflow: visible !important;
            }

            #sidebar, #top-navbar, .mobile-bottom-nav, footer {
                display: none !important;
            }
        }

        .ta-text {
            font-family: 'Noto Sans Tamil', 'Plus Jakarta Sans', sans-serif;
            font-weight: 500;
        }

        /* Sidebar Styling */
        #sidebar {
            width: 260px;
            background-color: var(--deiveni-sidebar-bg);
            min-height: 100vh;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1040;
        }

        .sidebar-brand {
            padding: 1.15rem 1.25rem;
            background: linear-gradient(180deg, rgba(30, 58, 138, 0.3) 0%, rgba(15, 23, 42, 0.6) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.35);
        }

        .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            font-weight: 700;
            padding: 0.85rem 1.25rem 0.35rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.15rem;
            margin: 0.1rem 0.65rem;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.865rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .sidebar-nav .nav-link i {
            font-size: 1.05rem;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            color: #94a3b8;
            transition: color 0.2s ease;
        }

        .sidebar-nav .nav-link:hover {
            color: #ffffff;
            background-color: var(--deiveni-sidebar-hover);
        }

        .sidebar-nav .nav-link:hover i {
            color: #60a5fa;
        }

        .sidebar-nav .nav-link.active {
            color: #ffffff;
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
            font-weight: 600;
        }

        .sidebar-nav .nav-link.active i {
            color: #ffffff;
        }

        /* Top Navbar */
        #top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--deiveni-border);
            padding: 0.65rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        @media (min-width: 768px) {
            #top-navbar {
                padding: 0.75rem 1.5rem;
            }
        }

        /* Main Content */
        #main-content {
            min-height: calc(100vh - 65px);
            padding: 1rem 0.85rem;
            transition: all 0.3s ease;
        }

        @media (min-width: 768px) {
            #main-content {
                padding: 1.5rem;
            }
        }

        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                transform: translateX(-100%);
                height: 100vh;
            }

            #sidebar.show {
                transform: translateX(0);
            }

            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(15, 23, 42, 0.65);
                backdrop-filter: blur(3px);
                z-index: 1030;
                display: none;
            }

            .sidebar-backdrop.show {
                display: block;
            }
        }

        /* Mobile Bottom Quick Navigation Bar */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-around;
            z-index: 1025;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }

        .mobile-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            font-size: 0.68rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            transition: color 0.15s ease;
            flex: 1;
            text-align: center;
        }

        .mobile-nav-item i {
            font-size: 1.15rem;
            margin-bottom: 1px;
        }

        .mobile-nav-item.active,
        .mobile-nav-item:hover {
            color: #2563eb;
        }

        /* Cards & Components */
        .card-custom {
            background: #ffffff;
            border: 1px solid var(--deiveni-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-stat {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid var(--deiveni-border);
            background: #ffffff;
            padding: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }

        .stat-icon-wrapper {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .bg-income-subtle {
            background-color: #ecfdf5;
            color: #059669;
        }

        .bg-expense-subtle {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .bg-workers-subtle {
            background-color: #eff6ff;
            color: #2563eb;
        }

        .bg-sites-subtle {
            background-color: #fffbeb;
            color: #d97706;
        }

        .bg-cash-subtle {
            background-color: #f0fdf4;
            color: #16a34a;
        }

        .bg-warehouse-subtle {
            background-color: #f5f3ff;
            color: #7c3aed;
        }

        /* Tables */
        .table-custom {
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 0.85rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .table-custom td {
            padding: 0.75rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.885rem;
        }

        .btn-deiveni {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: #ffffff;
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.45rem 0.85rem;
            box-shadow: 0 2px 5px rgba(30, 58, 138, 0.2);
            transition: all 0.2s ease;
        }

        .btn-deiveni:hover {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            color: #ffffff;
        }

        .ta-badge {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 0.78rem;
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            display: inline-block;
            margin-left: 0.3rem;
            font-family: 'Noto Sans Tamil', sans-serif;
        }

        /* Table Action Three-Dot Dropdown */
        .table-action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: #64748b;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.15s ease-in-out;
        }

        .table-action-btn:hover,
        .table-action-btn:focus,
        .show>.table-action-btn {
            background-color: #e2e8f0;
            color: #0f172a;
            border-color: #cbd5e1;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .table-action-dropdown .dropdown-menu {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            padding: 0.4rem 0;
            min-width: 145px;
            font-size: 0.85rem;
            z-index: 1055;
        }

        .table-action-dropdown .dropdown-item {
            padding: 0.45rem 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            color: #334155;
            transition: all 0.12s ease;
            background: transparent;
            border: none;
            width: 100%;
            text-align: left;
        }

        .table-action-dropdown .dropdown-item i {
            font-size: 0.95rem;
            width: 18px;
        }

        .table-action-dropdown .dropdown-item:hover,
        .table-action-dropdown .dropdown-item:focus {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .table-action-dropdown .dropdown-item.text-danger:hover,
        .table-action-dropdown .dropdown-item.text-danger:focus {
            background-color: #fef2f2;
            color: #dc2626 !important;
        }

        .modal-content {
            border-radius: 14px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .toast-container {
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 9999;
        }

        /* Searchable Select Styles */
        .searchable-select-wrap .searchable-btn {
            background-color: #fff;
            border-color: #cbd5e1;
            color: #1e293b;
            font-size: 0.85rem;
            cursor: pointer;
            text-align: left;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .searchable-select-wrap .searchable-btn:focus,
        .searchable-select-wrap .searchable-btn[aria-expanded="true"] {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15);
            outline: none;
        }
        /* The floating menu: position is set via JS (position: fixed), so we only
           control visual appearance here. */
        .searchable-menu {
            border-radius: 8px;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,.12), 0 4px 10px -3px rgba(0,0,0,.08) !important;
        }
        .searchable-opt-item {
            border-radius: 6px;
            transition: background 0.15s ease;
        }
        .searchable-opt-item:hover, .searchable-opt-item.active {
            background-color: #eff6ff !important;
            color: #1e40af !important;
        }
        .searchable-opt-item.active .opt-title {
            color: #1e40af !important;
            font-weight: 700 !important;
        }
    </style>
    @stack('styles')
</head>

<body>

    <!-- Sidebar Backdrop for Mobile -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="d-flex app-wrapper" id="appWrapper">
        <!-- Sidebar -->
        <aside id="sidebar" class="d-flex flex-column flex-shrink-0">
            <!-- Brand -->
            <div class="sidebar-brand d-flex align-items-center justify-content-between">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-white text-decoration-none">
                    <div class="brand-logo-icon me-2">
                        <i class="fa-solid fa-helmet-safety"></i>
</div>
                    <div>
                        <div class="fw-bold fs-6 tracking-wide lh-1 text-white">DEIVENI</div>
                        <small class="text-warning text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.05em;">Construction</small>
</div>
</a>
                <button class="btn btn-sm btn-link text-white-50 d-lg-none p-0" id="closeSidebarBtn">
                    <i class="bi bi-x-lg fs-5"></i>
</button>
</div>

            <!-- Navigation Links -->
            <div class="sidebar-nav flex-grow-1 overflow-y-auto">
                @if (auth()->user()?->canAccess('dashboard') ?? true)
                    <div class="sidebar-heading">MAIN</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ Request::is('dashboard*') ? 'active' : '' }}" data-menu="dashboard">
                                <i class="bi bi-grid-1x2-fill"></i>
                                <span>Dashboard</span>
</a>
                        </li>
                    </ul>
                @endif

                @php
                    $canSeeMasters = auth()->user()?->canAccessAny(['worker_categories', 'units', 'materials', 'suppliers', 'banks', 'branches', 'properties', 'property_types', 'transaction_types']) ?? true;
                @endphp

                @if ($canSeeMasters)
                    <div class="sidebar-heading" data-role-section="masters">MASTERS</div>
                    <ul class="nav flex-column">
                        @if (auth()->user()?->canAccess('worker_categories') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/worker-categories') }}"
                                    class="nav-link {{ Request::is('worker-categories*') ? 'active' : '' }}"
                                    data-menu="worker_categories">
                                    <i class="bi bi-person-badge-fill"></i>
                                    <span>Worker Categories</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('units') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/units') }}" class="nav-link {{ Request::is('units*') ? 'active' : '' }}"
                                    data-menu="units">
                                    <i class="bi bi-rulers"></i>
                                    <span>Units Master</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('materials') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/materials') }}"
                                    class="nav-link {{ Request::is('materials*') ? 'active' : '' }}" data-menu="materials">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Materials Master</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('suppliers') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/suppliers') }}"
                                    class="nav-link {{ Request::is('suppliers*') ? 'active' : '' }}" data-menu="suppliers">
                                    <i class="bi bi-truck"></i>
                                    <span>Suppliers</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('banks') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/banks') }}"
                                    class="nav-link {{ Request::is('banks*') || Request::is('bank-master*') ? 'active' : '' }}"
                                    data-menu="banks">
                                    <i class="bi bi-bank"></i>
                                    <span>Bank Master</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('branches') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/branches') }}"
                                    class="nav-link {{ Request::is('branches*') || Request::is('branch-master*') ? 'active' : '' }}"
                                    data-menu="branches">
                                    <i class="bi bi-diagram-3"></i>
                                    <span>Branch Master</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('properties') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/properties') }}"
                                    class="nav-link {{ Request::is('properties*') || Request::is('property-master*') ? 'active' : '' }}"
                                    data-menu="properties">
                                    <i class="bi bi-building"></i>
                                    <span>Property Master</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('property_types') || auth()->user()?->canAccess('rentals') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/property-types') }}"
                                    class="nav-link {{ Request::is('property-types*') || Request::is('property-type-master*') || Request::is('rentals*') || Request::is('rental-master*') ? 'active' : '' }}"
                                    data-menu="property_types">
                                    <i class="bi bi-buildings"></i>
                                    <span>Property Type Master</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('transaction_types') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/transaction-types') }}"
                                    class="nav-link {{ Request::is('transaction-types*') ? 'active' : '' }}"
                                    data-menu="transaction_types">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Transaction Types</span>
</a>
                            </li>
                        @endif
                    </ul>
                @endif

                @php
                    $canSeeSiteOps = auth()->user()?->canAccessAny(['sites', 'daily_allocation', 'weekly_wages', 'site_cash', 'purchases', 'material_usage', 'stock_transfer']) ?? true;
                @endphp

                @if ($canSeeSiteOps)
                    <div class="sidebar-heading" data-role-section="site-mgmt">SITE OPERATIONS</div>
                    <ul class="nav flex-column">
                        @if (auth()->user()?->canAccess('sites') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/sites') }}" class="nav-link {{ Request::is('sites*') ? 'active' : '' }}"
                                    data-menu="sites">
                                    <i class="bi bi-building-gear"></i>
                                    <span>Project Sites</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('daily_allocation') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/daily-allocation') }}"
                                    class="nav-link {{ Request::is('daily-allocation*') ? 'active' : '' }}"
                                    data-menu="daily_allocation">
                                    <i class="bi bi-calendar-check-fill"></i>
                                    <span>Daily Allocation</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('weekly_wages') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/weekly-wages') }}"
                                    class="nav-link {{ Request::is('weekly-wages*') ? 'active' : '' }}"
                                    data-menu="weekly_wages">
                                    <i class="bi bi-journal-text"></i>
                                    <span>Weekly Wages</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('site_cash') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/site-cash') }}"
                                    class="nav-link {{ Request::is('site-cash*') ? 'active' : '' }}" data-menu="site_cash">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Site Cash Handling</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('purchases') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/purchases') }}"
                                    class="nav-link {{ Request::is('purchases*') ? 'active' : '' }}" data-menu="purchases">
                                    <i class="bi bi-cart-check-fill"></i>
                                    <span>Purchases & Inward</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('material_usage') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/material-usages') }}"
                                    class="nav-link {{ Request::is('material-usage*') ? 'active' : '' }}" data-menu="material_usage">
                                    <i class="bi bi-hammer"></i>
                                    <span>Material Usage</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('stock_transfer') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/stock-transfers') }}"
                                    class="nav-link {{ Request::is('stock-transfers*') ? 'active' : '' }}" data-menu="stock_transfer">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Stock Transfer</span>
</a>
                            </li>
                        @endif
                    </ul>
                @endif

                @php
                    $canSeeFinance = auth()->user()?->canAccessAny(['ledger', 'cash_transfer', 'admin_ledger']) ?? true;
                @endphp

                @if ($canSeeFinance)
                    <div class="sidebar-heading" data-role-section="accounts">FINANCE & LEDGER</div>
                    <ul class="nav flex-column">
                        @if (auth()->user()?->canAccess('ledger') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/ledger') }}" class="nav-link {{ Request::is('ledger*') ? 'active' : '' }}"
                                    data-menu="ledger">
                                    <i class="bi bi-book-half"></i>
                                    <span>Daily Ledger</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('admin_ledger') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/admin-ledger') }}" class="nav-link {{ Request::is('admin-ledger*') ? 'active' : '' }}"
                                    data-menu="admin_ledger">
                                    <i class="bi bi-journal-bookmark-fill"></i>
                                    <span>Admin Ledger</span>
</a>
                            </li>
                        @endif
                        @if (auth()->user()?->canAccess('cash_transfer') ?? true)
                            <li class="nav-item">
                                <a href="{{ url('/cash-transfer') }}"
                                    class="nav-link {{ Request::is('cash-transfer*') ? 'active' : '' }}"
                                    data-menu="cash_transfer">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Cash Transfer</span>
</a>
                            </li>
                        @endif
                    </ul>
                @endif

                @if (auth()->user()?->canAccess('warehouse') ?? true)
                    <div class="sidebar-heading" data-role-section="inventory">INVENTORY</div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a href="{{ url('/warehouse') }}"
                                class="nav-link {{ Request::is('warehouse*') ? 'active' : '' }}" data-menu="warehouse">
                                <i class="bi bi-shop-window"></i>
                                <span>Warehouse Store</span>
</a>
                        </li>
                    </ul>
                @endif

                @php
                    $u = auth()->user();
                    $canProfitLoss = ($u?->canAccess('reports_profit_loss') ?? false);
                    $canLabourAlloc = ($u?->canAccess('reports_labour_allocations') ?? false);
                    $canDailyExpenses = ($u?->canAccess('reports_daily_expenses') ?? false);
                    $canCashHandling = ($u?->canAccess('reports_cash_handling') ?? false);
                    $canPurchasesInward = ($u?->canAccess('reports_purchases_inward') ?? false);
                    $canWarehouseStock = ($u?->canAccess('reports_warehouse_inventory') ?? false);
                    $canStockTransferReport = ($u?->canAccess('reports_stock_transfer') ?? false);
                    $canAccountLedger = ($u?->canAccess('reports_ledger') ?? false);

                    $showReportsSection = $canProfitLoss || $canLabourAlloc || $canDailyExpenses || $canCashHandling || $canPurchasesInward || $canWarehouseStock || $canStockTransferReport || $canAccountLedger;
                @endphp

                @if ($showReportsSection)
                    <div class="sidebar-heading" data-role-section="reports">REPORTS & ANALYTICS</div>
                    <ul class="nav flex-column">
                        @if ($canProfitLoss)
                            <li class="nav-item" data-menu="reports_profit_loss">
                                <a href="{{ route('reports.admin.profit-loss') }}"
                                    class="nav-link {{ Request::is('reports/admin/profit-loss*') ? 'active' : '' }}">
                                    <i class="bi bi-graph-up-arrow"></i>
                                    <span>Profit & Loss</span>
</a>
                            </li>
                        @endif
                        @if ($canLabourAlloc)
                            <li class="nav-item" data-menu="reports_labour_allocations">
                                <a href="{{ route('reports.site.labour-allocations') }}"
                                    class="nav-link {{ Request::is('reports/site/labour-allocations*') ? 'active' : '' }}">
                                    <i class="bi bi-people-fill"></i>
                                    <span>Labour Allocation</span>
</a>
                            </li>
                        @endif
                        @if ($canDailyExpenses)
                            <li class="nav-item" data-menu="reports_daily_expenses">
                                <a href="{{ route('reports.site.daily-expenses') }}"
                                    class="nav-link {{ Request::is('reports/site/daily-expenses*') ? 'active' : '' }}">
                                    <i class="bi bi-receipt-cutoff"></i>
                                    <span>Daily Expenses</span>
</a>
                            </li>
                        @endif
                        @if ($canCashHandling)
                            <li class="nav-item" data-menu="reports_cash_handling">
                                <a href="{{ route('reports.site.cash-handling') }}"
                                    class="nav-link {{ Request::is('reports/site/cash-handling*') ? 'active' : '' }}">
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Site Cash Handling</span>
</a>
                            </li>
                        @endif
                        @if ($canPurchasesInward)
                            <li class="nav-item" data-menu="reports_purchases_inward">
                                <a href="{{ route('reports.site.purchases-inward') }}"
                                    class="nav-link {{ Request::is('reports/site/purchases-inward*') ? 'active' : '' }}">
                                    <i class="bi bi-cart-check-fill"></i>
                                    <span>Purchases & Inward</span>
</a>
                            </li>
                        @endif
                        @if ($canWarehouseStock)
                            <li class="nav-item" data-menu="reports_warehouse_inventory">
                                <a href="{{ route('reports.site.warehouse-inventory') }}"
                                    class="nav-link {{ Request::is('reports/site/warehouse-inventory*') ? 'active' : '' }}">
                                    <i class="bi bi-boxes"></i>
                                    <span>Warehouse Stock</span>
</a>
                            </li>
                        @endif
                        @if ($canStockTransferReport)
                            <li class="nav-item" data-menu="reports_stock_transfer">
                                <a href="{{ route('reports.site.stock-transfers') }}"
                                    class="nav-link {{ Request::is('reports/site/stock-transfers*') ? 'active' : '' }}">
                                    <i class="bi bi-arrow-left-right"></i>
                                    <span>Stock Transfer</span>
</a>
                            </li>
                        @endif
                        @if ($canAccountLedger)
                            <li class="nav-item" data-menu="reports_ledger">
                                <a href="{{ route('reports.account.ledger') }}"
                                    class="nav-link {{ Request::is('reports/account/ledger*') ? 'active' : '' }}">
                                    <i class="bi bi-journal-text"></i>
                                    <span>Financial & Ledger</span>
</a>
                            </li>
                        @endif
                    </ul>
                @endif

                @php
                    $canAdmin = (auth()->user()?->canAccess('users') || auth()->user()?->canAccess('audit_logs'));
                    $canProfile = (auth()->user()?->canAccess('profile') ?? true);
                @endphp

                @if ($canAdmin || $canProfile)
                    <div class="sidebar-heading" data-role-section="admin">
                        {{ $canAdmin ? 'ADMINISTRATION' : 'ACCOUNT' }}
</div>
                    <ul class="nav flex-column">
                        @if (auth()->user()?->canAccess('users'))
                            <li class="nav-item">
                                <a href="{{ url('/users') }}" class="nav-link {{ Request::is('users*') ? 'active' : '' }}"
                                    data-menu="users">
                                    <i class="bi bi-shield-lock-fill"></i>
                                    <span>Users & RBAC</span>
</a>
                            </li>
                        @endif

                        @if (auth()->user()?->canAccess('audit_logs'))
                            <li class="nav-item">
                                <a href="{{ url('/audit-logs') }}"
                                    class="nav-link {{ Request::is('audit-logs*') ? 'active' : '' }}" data-menu="audit_logs">
                                    <i class="bi bi-clock-history"></i>
                                    <span>Audit Logs</span>
</a>
                            </li>
                        @endif

                        @if ($canProfile)
                            <li class="nav-item">
                                <a href="{{ url('/profile') }}" class="nav-link {{ Request::is('profile*') ? 'active' : '' }}"
                                    data-menu="profile">
                                    <i class="bi bi-person-circle"></i>
                                    <span>My Profile</span>
</a>
                            </li>
                        @endif
                    </ul>
                @endif
</div>

            <!-- Sidebar Footer -->
            <div
                class="p-3 border-top border-secondary border-opacity-25 bg-black bg-opacity-20 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold me-2"
                        style="width: 32px; height: 32px;" id="userAvatar">
                        {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
</div>
                    <div style="line-height: 1.2;">
                        <div class="text-white small fw-semibold" id="userName">{{ Auth::user()->name ?? 'K. Deiveni' }}
</div>
                        <small class="text-white-50" style="font-size: 0.7rem;" id="userEmail">{{ Auth::user()->email ??
                            'owner@deiveni.com' }}</small>
</div>
</div>
                <a href="{{ route('logout') }}" class="text-white-50 hover-white" title="Logout">
                    <i class="bi bi-box-arrow-right fs-5"></i>
</a>
</div>
        </aside>

        <!-- Main Wrapper -->
        <div class="d-flex flex-column flex-grow-1 overflow-hidden" id="main-wrapper" style="min-width: 0;">

            <!-- Top Navbar -->
            <header id="top-navbar" class="d-flex align-items-center justify-content-between flex-shrink-0">
                <div class="d-flex align-items-center">
                    <button class="btn btn-light btn-sm d-lg-none me-2 border" id="openSidebarBtn"
                        aria-label="Open menu">
                        <i class="bi bi-list fs-5"></i>
</button>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center fs-6 fs-md-5">
                            @yield('page_title', 'Dashboard')
</h5>
                        <small class="text-muted d-none d-md-inline" id="currentDateDisplay"></small>
</div>
</div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Top Role Pill Indicator -->
                    <span
                        class="badge {{ Auth::user()?->roleBadgeClass() ?? 'bg-primary text-white' }} d-none d-sm-inline-flex align-items-center px-2 py-1"
                        id="topRoleBadge">
                        <i class="bi bi-shield-check me-1"></i>{{ Auth::user()?->roleName() ?? 'Owner' }}
</span>

                    <!-- Profile Dropdown -->
                    <div class="dropdown">
                        <button
                            class="btn btn-light btn-sm border d-flex align-items-center gap-1 rounded-pill px-2 py-1"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                style="width: 26px; height: 26px; font-size: 0.75rem;" id="navAvatar">
                                {{ strtoupper(substr(Auth::user()->name ?? 'AD', 0, 2)) }}
</div>
                            <span class="d-none d-md-inline small fw-semibold"
                                id="navUserName">{{ Auth::user()->name ?? 'K. Deiveni' }}</span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 0.65rem;"></i>
</button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 10px;">
                            <li class="dropdown-header text-uppercase fw-bold text-muted small"
                                style="font-size: 0.68rem;">Signed in as {{ Auth::user()?->roleName() ?? 'Admin' }}
                            </li>
                            <li><a class="dropdown-item" href="{{ url('/profile') }}"><i
                                        class="bi bi-person me-2"></i>My Profile</a></li>
                            @if (auth()->user()?->canAccess('users'))
                                <li><a class="dropdown-item" href="{{ url('/users') }}"><i
                                            class="bi bi-shield-check me-2"></i>User Permissions</a></li>
                            @endif
                            @if (auth()->user()?->canAccess('audit_logs'))
                                <li><a class="dropdown-item" href="{{ url('/audit-logs') }}"><i
                                            class="bi bi-clock-history me-2"></i>Audit Logs</a></li>
                            @endif
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}"><i
                                        class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
</div>
</div>
            </header>

            <!-- Main Content Area with Independent Scroll -->
            <div id="main-content-scroll" class="flex-grow-1 overflow-y-auto">
                <main id="main-content">
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-shield-exclamation me-2"></i>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
                    @endif
                    @yield('content')
                </main>

                <!-- Desktop Footer -->
                <footer class="bg-white border-top py-2 px-4 text-center text-muted small mt-auto d-none d-md-block">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center">
                        <div>
                            <strong>Deiveni Construction Management System</strong> &bull; Phase 1 Prototype
</div>
                        <!-- <div class="ta-text text-muted" style="font-size: 0.8rem;">
                            Construction Site Operations & Financial Management System
</div> -->
</div>
                </footer>
</div>
</div>
</div>

    <!-- Mobile Bottom Quick Action Bar (Role-Aware) -->
    <nav class="mobile-bottom-nav d-lg-none">
        <a href="{{ route('dashboard') }}" class="mobile-nav-item {{ Request::is('dashboard*') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Home</span>
</a>
        @if (auth()->user()?->isAccountManager())
            <a href="{{ url('/banks') }}" class="mobile-nav-item {{ Request::is('banks*') ? 'active' : '' }}">
                <i class="bi bi-bank"></i>
                <span>Banks</span>
</a>
            <a href="{{ url('/ledger') }}" class="mobile-nav-item {{ Request::is('ledger*') ? 'active' : '' }}">
                <i class="bi bi-book-half"></i>
                <span>Ledger</span>
</a>
            @if ($showReportsSection)
                @php
                    $mobileAmReportUrl = $canAccountLedger ? route('reports.account.ledger') : ($canLabourAlloc ? route('reports.site.labour-allocations') : ($canCashHandling ? route('reports.site.cash-handling') : ($canPurchasesInward ? route('reports.site.purchases-inward') : ($canWarehouseStock ? route('reports.site.warehouse-inventory') : route('reports.admin.profit-loss')))));
                @endphp
                <a href="{{ $mobileAmReportUrl }}"
                    class="mobile-nav-item {{ Request::is('reports*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reports</span>
</a>
            @endif
            <a href="{{ url('/profile') }}" class="mobile-nav-item {{ Request::is('profile*') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i>
                <span>Profile</span>
</a>
        @elseif (auth()->user()?->isSiteManager())
            <a href="{{ url('/worker-categories') }}"
                class="mobile-nav-item {{ Request::is('worker-categories*') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i>
                <span>Categories</span>
</a>
            <a href="{{ url('/weekly-wages') }}" class="mobile-nav-item {{ Request::is('weekly-wages*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Wages</span>
</a>
            <a href="{{ url('/site-cash') }}" class="mobile-nav-item {{ Request::is('site-cash*') ? 'active' : '' }}">
                <i class="bi bi-cash-stack"></i>
                <span>Cash</span>
</a>
            @if ($showReportsSection)
                @php
                    $mobileSmReportUrl = $canLabourAlloc ? route('reports.site.labour-allocations') : ($canCashHandling ? route('reports.site.cash-handling') : ($canPurchasesInward ? route('reports.site.purchases-inward') : ($canWarehouseStock ? route('reports.site.warehouse-inventory') : ($canAccountLedger ? route('reports.account.ledger') : route('reports.admin.profit-loss')))));
                @endphp
                <a href="{{ $mobileSmReportUrl }}"
                    class="mobile-nav-item {{ Request::is('reports*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reports</span>
</a>
            @endif
        @else
            <a href="{{ url('/weekly-wages') }}" class="mobile-nav-item {{ Request::is('weekly-wages*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Wages</span>
</a>
            <a href="{{ url('/ledger') }}" class="mobile-nav-item {{ Request::is('ledger*') ? 'active' : '' }}">
                <i class="bi bi-book-half"></i>
                <span>Ledger</span>
</a>
            @if ($showReportsSection)
                @php
                    $mobileAdminReportUrl = $canProfitLoss ? route('reports.admin.profit-loss') : ($canLabourAlloc ? route('reports.site.labour-allocations') : ($canCashHandling ? route('reports.site.cash-handling') : ($canPurchasesInward ? route('reports.site.purchases-inward') : ($canWarehouseStock ? route('reports.site.warehouse-inventory') : route('reports.account.ledger')))));
                @endphp
                <a href="{{ $mobileAdminReportUrl }}"
                    class="mobile-nav-item {{ Request::is('reports*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Reports</span>
</a>
            @endif
            <a href="{{ url('/users') }}" class="mobile-nav-item {{ Request::is('users*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Users</span>
</a>
        @endif
    </nav>


    <!-- Global Toast Container -->
    <div class="toast-container p-3" id="toastPlacement">
        <div id="liveToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert"
            aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-5 me-2" id="toastIcon"></i>
                    <span id="toastMessage">Action completed successfully!</span>
</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
</div>
</div>
</div>

    <!-- Global Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-inline-flex p-3 mb-3">
                        <i class="bi bi-trash3-fill fs-2"></i>
</div>
                    <h5 class="modal-title fw-bold mb-2">Are you sure?</h5>
                    <p class="text-muted small mb-4" id="deleteModalMessage">Do you really want to delete this record?
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger btn-sm px-3" id="confirmDeleteBtn">Delete</button>
</div>
</div>
</div>
</div>
</div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery 3.7.1 -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- App Global Script -->
    <script>
        // Show Current Date //
        const today = new Date();

        const formattedDate = today.toLocaleDateString('en-GB', {
            weekday: 'long',
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });

        $("#currentDateDisplay").html("<i class='bi bi-calendar3 me-1'></i>" + formattedDate);

        window.showToast = function (message, type = 'success') {
            const toastEl = document.getElementById('liveToast');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');

            toastMessage.innerText = message;

            if (type === 'success') {
                toastEl.className = 'toast align-items-center text-white bg-success border-0 shadow-lg';
                toastIcon.className = 'bi bi-check-circle-fill text-white fs-5 me-2';
            } else if (type === 'danger' || type === 'error') {
                toastEl.className = 'toast align-items-center text-white bg-danger border-0 shadow-lg';
                toastIcon.className = 'bi bi-exclamation-octagon-fill text-white fs-5 me-2';
            } else if (type === 'warning') {
                toastEl.className = 'toast align-items-center text-dark bg-warning border-0 shadow-lg';
                toastIcon.className = 'bi bi-exclamation-triangle-fill text-dark fs-5 me-2';
            } else {
                toastEl.className = 'toast align-items-center text-white bg-dark border-0 shadow-lg';
                toastIcon.className = 'bi bi-info-circle-fill text-info fs-5 me-2';
            }

            const toast = new bootstrap.Toast(toastEl, { delay: 3200 });
            toast.show();
        };

        let deleteCallback = null;
        window.confirmDelete = function (message, callback) {
            $('#deleteModalMessage').text(message || 'Do you really want to delete this record?');
            deleteCallback = callback;
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        };

        $('#confirmDeleteBtn').on('click', function () {
            if (typeof deleteCallback === 'function') {
                deleteCallback();
            }
            $('#deleteConfirmModal').modal('hide');
        });

        // Searchable Select Global Component
        window.makeSearchableSelect = function ($select, config = {}) {
            if (!$select || !$select.length) return;

            $select.each(function () {
                const select = $(this);
                let container = select.next('.searchable-select-wrap');
                const isRebuild = container.length > 0;

                const placeholder = config.placeholder || select.find('option:first').text() || '-- Select Material --';
                const type = config.type || (select.hasClass('wh-select-item') ? 'warehouse' : 'material');

                if (!isRebuild) {
                    select.addClass('d-none');
                    const wrapHtml = `
                        <div class="searchable-select-wrap w-100 position-relative">
                            <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center searchable-btn shadow-none"
                                    aria-expanded="false" style="min-height: 31px;">
                                <span class="searchable-text text-truncate text-muted">${placeholder}</span>
                                <i class="bi bi-chevron-down ms-1 text-muted" style="font-size: 0.72rem;"></i>
</button>
                            <div class="searchable-menu p-2 shadow border rounded bg-white d-none"
                                 style="min-width: 290px; max-height: 350px; z-index: 99999; overflow: hidden;">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light border-end-0 py-1"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" class="form-control searchable-search-input border-start-0 ps-0 py-1"
                                           placeholder="Type to search ..." autocomplete="off">
                                    <button class="btn btn-outline-secondary searchable-clear-search d-none py-1 px-2" type="button"><i class="bi bi-x"></i></button>
</div>
                                <div class="searchable-options-list overflow-auto" style="max-height: 250px;">
</div>
</div>
</div>
                    `;
                    container = $(wrapHtml);
                    select.after(container);
                }

                const menu = container.find('.searchable-menu');
                const button = container.find('.searchable-btn');
                const textSpan = container.find('.searchable-text');
                const searchInput = container.find('.searchable-search-input');
                const clearBtn = container.find('.searchable-clear-search');
                const optionsList = container.find('.searchable-options-list');

                // Use fixed positioning so the menu is never clipped by modal overflow or
                // table-responsive containers.
                function positionMenu() {
                    const rect = button[0].getBoundingClientRect();
                    const menuWidth = Math.max(rect.width, 290);
                    const spaceBelow = window.innerHeight - rect.bottom - 8;
                    const spaceAbove = rect.top - 8;
                    const menuHeight = Math.min(350, spaceBelow > 200 ? spaceBelow : spaceAbove);

                    let top = rect.bottom + 2;
                    let maxH = menuHeight;

                    // Flip above if not enough space below
                    if (spaceBelow < 180 && spaceAbove > spaceBelow) {
                        const estimatedH = Math.min(350, spaceAbove);
                        top = rect.top - estimatedH - 2;
                        maxH = estimatedH;
                    }

                    menu.css({
                        position: 'fixed',
                        top: top + 'px',
                        left: rect.left + 'px',
                        width: menuWidth + 'px',
                        maxHeight: maxH + 'px',
                        zIndex: 99999
                    });
                    optionsList.css('max-height', (maxH - 70) + 'px');
                }

                function openMenu() {
                    if (button.prop('disabled')) return;
                    // Close any other open searchable menus first
                    $('.searchable-menu').not(menu).addClass('d-none').removeData('ss-open');
                    positionMenu();
                    menu.removeClass('d-none');
                    button.attr('aria-expanded', 'true');
                    searchInput.val('').trigger('input');
                    setTimeout(() => searchInput.focus(), 40);
                    $(window).on('scroll.ss resize.ss', positionMenu);
                }

                function closeMenu() {
                    menu.addClass('d-none');
                    button.attr('aria-expanded', 'false');
                    $(window).off('scroll.ss resize.ss');
                }

                function buildOptions() {
                    optionsList.empty();
                    let hasOptions = false;

                    select.find('option').each(function () {
                        const opt = $(this);
                        const val = opt.val();
                        if (val === '' && opt.is(':disabled')) return;

                        hasOptions = true;
                        const text = opt.text().trim();
                        const rate = opt.data('rate');
                        const stock = opt.data('stock');
                        const unit = opt.data('unit');
                        const tamil = opt.data('tamil');
                        const grade = opt.data('grade');
                        const isSelected = opt.is(':selected') && val !== '';

                        let badgeHtml = '';
                        if (type === 'warehouse') {
                            if (stock !== undefined && stock !== null) {
                                badgeHtml += `<span class="badge bg-light text-dark border ms-1" style="font-size:0.7rem;">Stock: ${stock}</span>`;
                            }
                            if (rate !== undefined && rate !== null && rate > 0) {
                                badgeHtml += `<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.7rem;">₹${parseFloat(rate).toLocaleString('en-IN')}</span>`;
                            }
                        } else {
                            if (rate !== undefined && rate !== null && rate > 0) {
                                badgeHtml += `<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:0.7rem;">₹${parseFloat(rate).toLocaleString('en-IN')}</span>`;
                            }
                            if (unit) {
                                badgeHtml += `<span class="badge bg-light text-secondary border ms-1" style="font-size:0.7rem;">${unit}</span>`;
                            }
                            if (grade) {
                                badgeHtml += `<span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.7rem;">${grade}</span>`;
                            }
                        }

                        const subText = tamil ? `<small class="text-muted d-block" style="font-size:0.75rem;">${tamil}</small>` : '';

                        const item = $(`
                            <div class="py-1 px-2 border-bottom border-light searchable-opt-item ${isSelected ? 'active bg-primary bg-opacity-10 text-primary' : ''}"
                                 data-val="${val}" data-search="${text.toLowerCase()} ${tamil ? String(tamil).toLowerCase() : ''}"
                                 style="cursor: pointer; font-size: 0.85rem; border-radius: 6px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-truncate me-2">
                                        <div class="fw-semibold text-truncate opt-title text-dark">${text}</div>
                                        ${subText}
</div>
                                    <div class="flex-shrink-0 text-end">
                                        ${badgeHtml}
</div>
</div>
</div>
                        `);

                        optionsList.append(item);
                    });

                    if (!hasOptions) {
                        if (config.allowNew) {
                            optionsList.html(`
                                <div class="p-3 text-center text-muted small">
                                    <i class="bi bi-inbox me-1"></i>No items available
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary searchable-add-empty-btn py-1 px-2" style="font-size:0.78rem;">
                                            <i class="bi bi-plus-circle me-1"></i>Add New Material 
</button>
</div>
</div>
                            `);
                            optionsList.find('.searchable-add-empty-btn').on('click', function (e) {
                                e.stopPropagation();
                                if (typeof config.onAddNew === 'function') {
                                    config.onAddNew('', select, container, closeMenu);
                                }
                            });
                        } else {
                            optionsList.html('<div class="p-3 text-center text-muted small"><i class="bi bi-inbox me-1"></i>No items available</div>');
                        }
                    }

                    syncSelectedDisplay();
                }

                function syncSelectedDisplay() {
                    const selectedOpt = select.find('option:selected');
                    if (selectedOpt.length && selectedOpt.val() !== '') {
                        textSpan.text(selectedOpt.text().trim()).removeClass('text-muted').addClass('text-dark fw-semibold');
                        optionsList.find('.searchable-opt-item').removeClass('active bg-primary bg-opacity-10 text-primary');
                        optionsList.find(`.searchable-opt-item[data-val="${selectedOpt.val()}"]`).addClass('active bg-primary bg-opacity-10 text-primary');
                    } else {
                        textSpan.text(placeholder).addClass('text-muted').removeClass('text-dark fw-semibold');
                        optionsList.find('.searchable-opt-item').removeClass('active bg-primary bg-opacity-10 text-primary');
                    }

                    if (select.prop('disabled')) {
                        button.prop('disabled', true).addClass('bg-light');
                    } else {
                        button.prop('disabled', false).removeClass('bg-light');
                    }
                }

                buildOptions();

                if (!isRebuild) {
                    // Toggle menu open/close on button click
                    button.on('click', function (e) {
                        e.stopPropagation();
                        if (menu.hasClass('d-none')) {
                            openMenu();
                        } else {
                            closeMenu();
                        }
                    });

                    // Close when clicking outside
                    $(document).on('click.ss-' + Math.random().toString(36).slice(2), function (e) {
                        if (!container.is(e.target) && container.has(e.target).length === 0 && !menu.is(e.target) && menu.has(e.target).length === 0) {
                            closeMenu();
                        }
                    });

                    // Filter on search input
                    searchInput.on('input', function () {
                        const rawQuery = $(this).val().trim();
                        const query = rawQuery.toLowerCase();
                        if (query) {
                            clearBtn.removeClass('d-none');
                        } else {
                            clearBtn.addClass('d-none');
                        }

                        let matchesCount = 0;
                        let exactMatchFound = false;
                        optionsList.find('.searchable-opt-item').each(function () {
                            const searchStr = $(this).data('search') || '';
                            const optText = $(this).find('.opt-title').text().trim().toLowerCase();
                            if (optText === query) {
                                exactMatchFound = true;
                            }
                            if (!query || searchStr.includes(query)) {
                                $(this).removeClass('d-none');
                                matchesCount++;
                            } else {
                                $(this).addClass('d-none');
                            }
                        });

                        optionsList.find('.no-matches-msg, .searchable-add-new-wrap').remove();
                        if (matchesCount === 0) {
                            optionsList.append('<div class="no-matches-msg p-3 text-center text-muted small"><i class="bi bi-search me-1"></i>No matching materials found</div>');
                        }

                        if (config.allowNew && rawQuery.length > 0 && !exactMatchFound) {
                            const addNewBtn = $(`
                                <div class="searchable-add-new-wrap p-2 border-top bg-light mt-1">
                                    <button type="button" class="btn btn-sm btn-primary w-100 searchable-add-new-btn d-flex align-items-center justify-content-center py-1 fw-semibold" style="font-size:0.8rem;">
                                        <i class="bi bi-plus-circle me-1"></i>
                                        <span>Add "${rawQuery}" as New Material</span>
</button>
</div>
                            `);
                            addNewBtn.find('.searchable-add-new-btn').on('click', function (e) {
                                e.stopPropagation();
                                if (typeof config.onAddNew === 'function') {
                                    config.onAddNew(rawQuery, select, container, closeMenu);
                                }
                            });
                            optionsList.append(addNewBtn);
                        }
                    });

                    clearBtn.on('click', function (e) {
                        e.stopPropagation();
                        searchInput.val('').trigger('input').focus();
                    });

                    // Select option
                    optionsList.on('click', '.searchable-opt-item', function (e) {
                        e.stopPropagation();
                        const val = $(this).data('val');
                        select.val(val).trigger('change');
                        syncSelectedDisplay();
                        closeMenu();
                    });

                    select.on('change', function () {
                        syncSelectedDisplay();
                    });

                    select.on('rebuild', function () {
                        buildOptions();
                    });
                }
            });
        };

        $(document).ready(function () {
            $('#openSidebarBtn').on('click', function () {
                $('#sidebar').addClass('show');
                $('#sidebarBackdrop').addClass('show');
            });

            $('#closeSidebarBtn, #sidebarBackdrop').on('click', function () {
                $('#sidebar').removeClass('show');
                $('#sidebarBackdrop').removeClass('show');
            });

            // Close all floating searchable menus when any modal closes
            $(document).on('hide.bs.modal', function () {
                $('.searchable-menu').addClass('d-none');
                $('.searchable-btn').attr('aria-expanded', 'false');
                $(window).off('scroll.ss resize.ss');
            });
        });
    </script>
    @stack('scripts')
</body>

</html>