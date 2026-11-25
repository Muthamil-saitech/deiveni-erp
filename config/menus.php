<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Navigational Menus & Modules
    |--------------------------------------------------------------------------
    | Central registry of all menus in the Deiveni CMS.
    | To add a new menu in the future, simply add an entry here.
    | It will automatically appear in:
    | 1. Role creation & editing menu checklist
    | 2. Dynamic RBAC permission evaluations
    */
    'items' => [
        // MAIN
        'dashboard' => [
            'key' => 'dashboard',
            'name' => 'Dashboard',
            'name_ta' => 'முகப்பு பலகை',
            'section' => 'MAIN',
            'icon' => 'bi bi-grid-1x2-fill',
            'url' => '/dashboard',
        ],

        // MASTERS
        'worker_categories' => [
            'key' => 'worker_categories',
            'name' => 'Worker Categories',
            'name_ta' => 'பிரிவுகள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-person-badge-fill',
            'url' => '/worker-categories',
        ],
        'units' => [
            'key' => 'units',
            'name' => 'Units Master',
            'name_ta' => 'அளவீடுகள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-rulers',
            'url' => '/units',
        ],
        'materials' => [
            'key' => 'materials',
            'name' => 'Materials Master',
            'name_ta' => 'பொருட்கள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-box-seam-fill',
            'url' => '/materials',
        ],
        'suppliers' => [
            'key' => 'suppliers',
            'name' => 'Suppliers Master',
            'name_ta' => 'சப்ளையர்கள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-truck',
            'url' => '/suppliers',
        ],
        'banks' => [
            'key' => 'banks',
            'name' => 'Bank Master',
            'name_ta' => 'வங்கி தகவல்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-bank',
            'url' => '/banks',
        ],
        'branches' => [
            'key' => 'branches',
            'name' => 'Branch Master',
            'name_ta' => 'வங்கி கிளைகள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-diagram-3',
            'url' => '/branches',
        ],
        'properties' => [
            'key' => 'properties',
            'name' => 'Property Master',
            'name_ta' => 'சொத்து விவரங்கள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-building',
            'url' => '/properties',
        ],
        'property_types' => [
            'key' => 'property_types',
            'name' => 'Property Type Master',
            'name_ta' => 'சொத்து வகைகள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-buildings',
            'url' => '/property-types',
        ],
        'transaction_types' => [
            'key' => 'transaction_types',
            'name' => 'Transaction Types',
            'name_ta' => 'பரிவர்த்தனை வகைகள்',
            'section' => 'MASTERS',
            'icon' => 'bi bi-arrow-left-right',
            'url' => '/transaction-types',
        ],

        // SITE OPERATIONS
        'sites' => [
            'key' => 'sites',
            'name' => 'Project Sites',
            'name_ta' => 'தளங்கள்',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-building-gear',
            'url' => '/sites',
        ],
        'daily_allocation' => [
            'key' => 'daily_allocation',
            'name' => 'Daily Allocation',
            'name_ta' => 'தினசரி ஒதுக்கீடு',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-calendar-check-fill',
            'url' => '/daily-allocation',
        ],
        'weekly_wages' => [
            'key' => 'weekly_wages',
            'name' => 'Weekly Wages',
            'name_ta' => 'வார கூலி',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-journal-text',
            'url' => '/weekly-wages',
        ],
        'temporary_billing' => [
            'key' => 'temporary_billing',
            'name' => 'Temporary Billing',
            'name_ta' => '',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-receipt',
            'url' => '/temporary-billing',
        ],
        'site_cash' => [
            'key' => 'site_cash',
            'name' => 'Site Cash Handling',
            'name_ta' => 'தள பணப்புழக்கம்',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-cash-stack',
            'url' => '/site-cash',
        ],
        'purchases' => [
            'key' => 'purchases',
            'name' => 'Purchases & Inward',
            'name_ta' => 'கொள்முதல்',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-cart-check-fill',
            'url' => '/purchases',
        ],
        'material_usage' => [
            'key' => 'material_usage',
            'name' => 'Material Usage',
            'name_ta' => 'பொருள் பயன்பாடு',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-hammer',
            'url' => '/material-usages',
        ],
        'stock_transfer' => [
            'key' => 'stock_transfer',
            'name' => 'Stock Transfer',
            'name_ta' => 'பொருள் பரிமாற்றம்',
            'section' => 'SITE OPERATIONS',
            'icon' => 'bi bi-arrow-left-right',
            'url' => '/stock-transfers',
        ],
        // FINANCE & LEDGER
        'ledger' => [
            'key' => 'ledger',
            'name' => 'Daily Ledger',
            'name_ta' => 'பேரேடு',
            'section' => 'FINANCE & LEDGER',
            'icon' => 'bi bi-book-half',
            'url' => '/ledger',
        ],
        'admin_ledger' => [
            'key' => 'admin_ledger',
            'name' => 'Admin Ledger',
            'name_ta' => 'நிர்வாக பேரேடு',
            'section' => 'FINANCE & LEDGER',
            'icon' => 'bi bi-journal-bookmark-fill',
            'url' => '/admin-ledger',
        ],
        'cash_transfer' => [
            'key' => 'cash_transfer',
            'name' => 'Cash Transfer',
            'name_ta' => 'பணப் பரிமாற்றம்',
            'section' => 'FINANCE & LEDGER',
            'icon' => 'bi bi-arrow-left-right',
            'url' => '/cash-transfer',
        ],
        'cash_transfer_settle' => [
            'key' => 'cash_transfer_settle',
            'name' => 'Cash Transfer Settlement',
            'name_ta' => 'பரிமாற்றம் செட்டில் செய்தல் (அனுமதி)',
            'section' => 'FINANCE & LEDGER',
            'icon' => 'bi bi-check2-circle',
            'url' => '/cash-transfer',
        ],

        // INVENTORY
        'warehouse' => [
            'key' => 'warehouse',
            'name' => 'Warehouse Store',
            'name_ta' => 'கிடங்கு',
            'section' => 'INVENTORY',
            'icon' => 'bi bi-shop-window',
            'url' => '/warehouse',
        ],

        // REPORTS
        'reports_profit_loss' => [
            'key' => 'reports_profit_loss',
            'name' => 'Profit & Loss',
            'name_ta' => 'லாப/நஷ்டம்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-graph-up-arrow',
            'url' => '/reports/admin/profit-loss',
        ],
        'reports_labour_allocations' => [
            'key' => 'reports_labour_allocations',
            'name' => 'Labour Allocation',
            'name_ta' => 'கூலி பட்டியல்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-people-fill',
            'url' => '/reports/site/labour-allocations',
        ],
        'reports_daily_expenses' => [
            'key' => 'reports_daily_expenses',
            'name' => 'Daily Expenses',
            'name_ta' => 'தினசரி செலவுகள்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-receipt-cutoff',
            'url' => '/reports/site/daily-expenses',
        ],
        'reports_cash_handling' => [
            'key' => 'reports_cash_handling',
            'name' => 'Site Cash Handling',
            'name_ta' => 'தள பணப்புழக்கம்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-cash-stack',
            'url' => '/reports/site/cash-handling',
        ],
        'reports_purchases_inward' => [
            'key' => 'reports_purchases_inward',
            'name' => 'Purchases & Inward',
            'name_ta' => 'கொள்முதல்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-cart-check-fill',
            'url' => '/reports/site/purchases-inward',
        ],
        'reports_warehouse_inventory' => [
            'key' => 'reports_warehouse_inventory',
            'name' => 'Warehouse Stock',
            'name_ta' => 'கிடங்கு இருப்பு',
            'section' => 'REPORTS',
            'icon' => 'bi bi-boxes',
            'url' => '/reports/site/warehouse-inventory',
        ],
        'reports_stock_transfer' => [
            'key' => 'reports_stock_transfer',
            'name' => 'Stock Transfer',
            'name_ta' => 'பொருள் பரிமாற்றம்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-arrow-left-right',
            'url' => '/reports/site/stock-transfers',
        ],
        'reports_ledger' => [
            'key' => 'reports_ledger',
            'name' => 'Financial & Ledger',
            'name_ta' => 'நிதி லெட்ஜர்',
            'section' => 'REPORTS',
            'icon' => 'bi bi-journal-text',
            'url' => '/reports/account/ledger',
        ],

        // ADMINISTRATION
        'users' => [
            'key' => 'users',
            'name' => 'Users & RBAC',
            'name_ta' => 'பயனாளர்கள் & RBAC',
            'section' => 'ADMINISTRATION',
            'icon' => 'bi bi-shield-lock-fill',
            'url' => '/users',
        ],
        'audit_logs' => [
            'key' => 'audit_logs',
            'name' => 'Audit Logs',
            'name_ta' => 'தணிக்கை பதிவு',
            'section' => 'ADMINISTRATION',
            'icon' => 'bi bi-clock-history',
            'url' => '/audit-logs',
        ],
        'profile' => [
            'key' => 'profile',
            'name' => 'My Profile',
            'name_ta' => 'சுயவிவரம்',
            'section' => 'ADMINISTRATION',
            'icon' => 'bi bi-person-circle',
            'url' => '/profile',
        ],
    ],
];
