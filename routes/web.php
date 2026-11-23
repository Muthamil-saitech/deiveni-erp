<?php

use App\Http\Controllers\AccountLedgerReportController;
use App\Http\Controllers\AdminProfitLossReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashTransferController;
use App\Http\Controllers\DailyAllocationController;
use App\Http\Controllers\DailyExpensesReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialUsageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectSiteController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyTypeController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SiteCashController;
use App\Http\Controllers\SiteCashHandlingReportController;
use App\Http\Controllers\SiteLabourAllocationReportController;
use App\Http\Controllers\SitePurchasesInwardReportController;
use App\Http\Controllers\SiteWarehouseInventoryReportController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockTransferReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionTypeController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WeeklyWageController;
use App\Http\Controllers\WorkerCategoryController;
use App\Models\Bank;
use Illuminate\Support\Facades\Route;

// Authentication (Login page is index page)
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    // Dashboard & Home (URI is /dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::match(['post', 'put'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // 4.4 Masters
    Route::middleware('role:site_manager')->group(function () {
        Route::get('/worker-categories', [WorkerCategoryController::class, 'index'])->name('worker-categories.index');
        Route::get('/worker-categories/next-code', [WorkerCategoryController::class, 'nextCode'])->name('worker-categories.next-code');
        Route::post('/worker-categories', [WorkerCategoryController::class, 'store'])->name('worker-categories.store');
        Route::get('/worker-categories/{id}', [WorkerCategoryController::class, 'show'])->name('worker-categories.show');
        Route::post('/worker-categories/{id}/update', [WorkerCategoryController::class, 'update'])->name('worker-categories.update');
        Route::put('/worker-categories/{id}', [WorkerCategoryController::class, 'update']);
        Route::post('/worker-categories/{id}/delete', [WorkerCategoryController::class, 'destroy'])->name('worker-categories.destroy');
        Route::delete('/worker-categories/{id}', [WorkerCategoryController::class, 'destroy']);

        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::get('/units/{id}', [UnitController::class, 'show'])->name('units.show');
        Route::post('/units/{id}/update', [UnitController::class, 'update'])->name('units.update');
        Route::put('/units/{id}', [UnitController::class, 'update']);
        Route::post('/units/{id}/delete', [UnitController::class, 'destroy'])->name('units.destroy');
        Route::delete('/units/{id}', [UnitController::class, 'destroy']);

        Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
        Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
        Route::get('/materials/{id}', [MaterialController::class, 'show'])->name('materials.show');
        Route::post('/materials/{id}/update', [MaterialController::class, 'update'])->name('materials.update');
        Route::put('/materials/{id}', [MaterialController::class, 'update']);
        Route::post('/materials/{id}/delete', [MaterialController::class, 'destroy'])->name('materials.destroy');
        Route::delete('/materials/{id}', [MaterialController::class, 'destroy']);
    });

    // Masters accessible by Admin, Site Manager, Account Manager
    Route::middleware('role:site_manager,account_manager')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{encryptedId}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::post('/suppliers/{id}/update', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
        Route::post('/suppliers/{id}/delete', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

        Route::get('/transaction-types', [TransactionTypeController::class, 'index'])->name('transaction-types.index');
        Route::post('/transaction-types', [TransactionTypeController::class, 'store'])->name('transaction-types.store');
        Route::get('/transaction-types/{id}', [TransactionTypeController::class, 'show'])->name('transaction-types.show');
        Route::post('/transaction-types/{id}/update', [TransactionTypeController::class, 'update'])->name('transaction-types.update');
        Route::put('/transaction-types/{id}', [TransactionTypeController::class, 'update']);
        Route::post('/transaction-types/{id}/delete', [TransactionTypeController::class, 'destroy'])->name('transaction-types.destroy');
        Route::delete('/transaction-types/{id}', [TransactionTypeController::class, 'destroy']);
    });

    // Bank Master, Branch Master & Rental Master (Accessible by Admin and Account Manager)
    Route::middleware('role:account_manager')->group(function () {
        Route::get('/banks', [BankController::class, 'index'])->name('banks.index');
        Route::post('/banks', [BankController::class, 'store'])->name('banks.store');
        Route::get('/banks/{id}', [BankController::class, 'show'])->name('banks.show');
        Route::post('/banks/{id}/update', [BankController::class, 'update'])->name('banks.update');
        Route::put('/banks/{id}', [BankController::class, 'update'])->name('banks.update.put');
        Route::post('/banks/{id}/delete', [BankController::class, 'destroy'])->name('banks.destroy');
        Route::delete('/banks/{id}', [BankController::class, 'destroy']);
        Route::get('/bank-master', fn () => redirect()->route('banks.index'));

        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('/branches/{id}', [BranchController::class, 'show'])->name('branches.show');
        Route::post('/branches/{id}/update', [BranchController::class, 'update'])->name('branches.update');
        Route::put('/branches/{id}', [BranchController::class, 'update']);
        Route::post('/branches/{id}/delete', [BranchController::class, 'destroy'])->name('branches.destroy');
        Route::delete('/branches/{id}', [BranchController::class, 'destroy']);
        Route::get('/branch-master', fn () => redirect()->route('branches.index'));

        Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
        Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
        Route::get('/properties/{id}', [PropertyController::class, 'show'])->name('properties.show');
        Route::post('/properties/{id}/update', [PropertyController::class, 'update'])->name('properties.update');
        Route::put('/properties/{id}', [PropertyController::class, 'update']);
        Route::post('/properties/{id}/delete', [PropertyController::class, 'destroy'])->name('properties.destroy');
        Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
        Route::get('/property-master', fn () => redirect()->route('properties.index'));

        Route::get('/property-types', [PropertyTypeController::class, 'index'])->name('property-types.index');
        Route::post('/property-types', [PropertyTypeController::class, 'store'])->name('property-types.store');
        Route::get('/property-types/{id}', [PropertyTypeController::class, 'show'])->name('property-types.show');
        Route::post('/property-types/{id}/update', [PropertyTypeController::class, 'update'])->name('property-types.update');
        Route::put('/property-types/{id}', [PropertyTypeController::class, 'update']);
        Route::post('/property-types/{id}/delete', [PropertyTypeController::class, 'destroy'])->name('property-types.destroy');
        Route::delete('/property-types/{id}', [PropertyTypeController::class, 'destroy']);
        Route::get('/property-type-master', fn () => redirect()->route('property-types.index'));

        // Backwards compatibility for rentals
        Route::get('/rentals', [PropertyTypeController::class, 'index'])->name('rentals.index');
        Route::post('/rentals', [PropertyTypeController::class, 'store'])->name('rentals.store');
        Route::get('/rentals/{id}', [PropertyTypeController::class, 'show'])->name('rentals.show');
        Route::post('/rentals/{id}/update', [PropertyTypeController::class, 'update'])->name('rentals.update');
        Route::put('/rentals/{id}', [PropertyTypeController::class, 'update']);
        Route::post('/rentals/{id}/delete', [PropertyTypeController::class, 'destroy'])->name('rentals.destroy');
        Route::delete('/rentals/{id}', [PropertyTypeController::class, 'destroy']);
        Route::get('/rental-master', fn () => redirect()->route('property-types.index'));
    });

    // 4.5 & 4.6 Site Operations (Accessible by Admin and Site Manager)
    Route::middleware('role:site_manager')->group(function () {
        Route::get('/sites', [ProjectSiteController::class, 'index'])->name('sites.index');
        Route::post('/sites', [ProjectSiteController::class, 'store'])->name('sites.store');
        Route::get('/sites/{encryptedId}', [ProjectSiteController::class, 'show'])->name('sites.show');
        Route::get('/sites/{encryptedId}/suppliers/{supplierKey}', [ProjectSiteController::class, 'supplierPurchasesDetail'])->name('sites.supplier-purchases');
        Route::match(['put', 'patch', 'post'], '/sites/{id}', [ProjectSiteController::class, 'update']);
        Route::post('/sites/{id}/update', [ProjectSiteController::class, 'update'])->name('sites.update');

        Route::get('/daily-allocation', [DailyAllocationController::class, 'index'])->name('daily-allocation.index');
        Route::get('/daily-allocation/by-date', [DailyAllocationController::class, 'getByDate'])->name('daily-allocation.by-date');
        Route::get('/daily-allocation/history', [DailyAllocationController::class, 'getHistory'])->name('daily-allocation.history');
        Route::post('/daily-allocation', [DailyAllocationController::class, 'store'])->name('daily-allocation.store');
        Route::post('/daily-allocation/quick-add-worker-category', [DailyAllocationController::class, 'quickAddWorkerCategory'])->name('daily-allocation.quick-add-worker-category');
        Route::post('/daily-allocation/quick-add-material', [DailyAllocationController::class, 'quickAddMaterial'])->name('daily-allocation.quick-add-material');

        Route::get('/weekly-wages', [WeeklyWageController::class, 'index'])->name('weekly-wages.index');
        Route::get('/weekly-wages/data', [WeeklyWageController::class, 'getData'])->name('weekly-wages.data');
        Route::post('/weekly-wages/expenses', [WeeklyWageController::class, 'saveExpenses'])->name('weekly-wages.expenses');
        Route::post('/weekly-wages/settle', [WeeklyWageController::class, 'settle'])->name('weekly-wages.settle');

        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/next-po-number', [PurchaseController::class, 'nextPoNumber'])->name('purchases.next-po-number');
        Route::post('/purchases/quick-create-material', [PurchaseController::class, 'quickCreateMaterial'])->name('purchases.quick-create-material');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{encryptedId}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::post('/purchases/{id}/update', [PurchaseController::class, 'update'])->name('purchases.update');
        Route::put('/purchases/{id}', [PurchaseController::class, 'update']);
        Route::delete('/purchases/{id}', [PurchaseController::class, 'destroy']);
        Route::post('/purchases/{id}/delete', [PurchaseController::class, 'destroy'])->name('purchases.destroy');

        Route::get('/site-cash', [SiteCashController::class, 'index'])->name('site-cash.index');
        Route::get('/site-cash/purchase-orders', [SiteCashController::class, 'getPurchaseOrders'])->name('site-cash.purchase-orders');
        Route::get('/site-cash/daily-allocations', [SiteCashController::class, 'getDailyAllocations'])->name('site-cash.daily-allocations');
        Route::get('/site-cash/office-cash-wages', [SiteCashController::class, 'getOfficeCashWages'])->name('site-cash.office-cash-wages');
        Route::get('/site-cash/cash-transfers', [SiteCashController::class, 'getCashTransfers'])->name('site-cash.cash-transfers');
        Route::post('/site-cash', [SiteCashController::class, 'store'])->name('site-cash.store');
        Route::get('/site-cash/{encryptedId}', [SiteCashController::class, 'show'])->name('site-cash.show');
        Route::post('/site-cash/{id}/delete', [SiteCashController::class, 'destroy'])->name('site-cash.destroy');
        Route::delete('/site-cash/{id}', [SiteCashController::class, 'destroy']);

        // Material Usage routes
        Route::get('/material-usages', [MaterialUsageController::class, 'index'])->name('material-usages.index');
        Route::post('/material-usages', [MaterialUsageController::class, 'store'])->name('material-usages.store');
        Route::get('/material-usages/next-number', [MaterialUsageController::class, 'nextNumber'])->name('material-usages.next-number');
        Route::get('/material-usages/purchase-orders', [MaterialUsageController::class, 'getPurchaseOrders'])->name('material-usages.purchase-orders');
        Route::get('/material-usages/site-materials/{siteId}', [MaterialUsageController::class, 'getSiteMaterials'])->name('material-usages.site-materials');
        Route::get('/material-usages/warehouse-materials', [MaterialUsageController::class, 'getWarehouseMaterials'])->name('material-usages.warehouse-materials');
        Route::get('/material-usages/po-items/{poId}', [MaterialUsageController::class, 'getPOItems'])->name('material-usages.po-items');
        Route::get('/material-usages/{id}', [MaterialUsageController::class, 'show'])->name('material-usages.show');
        Route::post('/material-usages/{id}/update', [MaterialUsageController::class, 'update'])->name('material-usages.update');
        Route::post('/material-usages/{id}/delete', [MaterialUsageController::class, 'destroy'])->name('material-usages.destroy');

        Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers.index');
        Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
        Route::get('/stock-transfers/next-number', [StockTransferController::class, 'nextTransferNumber'])->name('stock-transfers.next-number');
        Route::get('/stock-transfers/site-stock/{siteId}/{materialId}', [StockTransferController::class, 'getSiteStock'])->name('stock-transfers.site-stock');
        Route::get('/stock-transfers/available-materials/{siteId}', [StockTransferController::class, 'availableMaterials'])->name('stock-transfers.available-materials');
        Route::get('/stock-transfers/{id}', [StockTransferController::class, 'show'])->whereNumber('id')->name('stock-transfers.show');
        Route::post('/stock-transfers/{id}/update', [StockTransferController::class, 'update'])->whereNumber('id')->name('stock-transfers.update');
        Route::post('/stock-transfers/{id}/return', [StockTransferController::class, 'storeReturn'])->whereNumber('id')->name('stock-transfers.return');
        Route::post('/stock-transfers/{id}/delete', [StockTransferController::class, 'destroy'])->whereNumber('id')->name('stock-transfers.destroy');
    });

    // 4.7 Finance & Account Management (Accessible by Admin and Account Manager)
    Route::middleware('role:account_manager')->group(function () {
        Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::post('/ledger', [LedgerController::class, 'store'])->name('ledger.store');
        Route::put('/ledger/{id}', [LedgerController::class, 'update']);
        Route::post('/ledger/{id}/update', [LedgerController::class, 'update'])->name('ledger.update');
        Route::get('/ledger/check-wages', [LedgerController::class, 'checkWages'])->name('ledger.check-wages');
        Route::get('/ledger/transaction-types/{category}', [LedgerController::class, 'transactionTypesByCategory'])->name('ledger.transaction-types');
        Route::get('/ledger/{encryptedId}', [LedgerController::class, 'show'])->name('ledger.show');
        Route::post('/ledger/{id}/delete', [LedgerController::class, 'destroy'])->name('ledger.destroy');
    });

    // 4.8 Warehouse & Store / Inventory (Accessible by Admin and Site Manager)
    Route::middleware('role:site_manager')->group(function () {
        Route::get('/warehouse', [WarehouseController::class, 'index'])->name('warehouse.index');
        Route::post('/warehouse/items', [WarehouseController::class, 'storeItem'])->name('warehouse.items.store');
        Route::post('/warehouse/items/{id}/update', [WarehouseController::class, 'updateItem'])->name('warehouse.items.update');
        Route::post('/warehouse/items/{id}/delete', [WarehouseController::class, 'destroyItem'])->name('warehouse.items.destroy');
        Route::post('/warehouse/movements', [WarehouseController::class, 'recordMovement'])->name('warehouse.movements.store');
        Route::get('/warehouse/in-stock', [WarehouseController::class, 'inStockItems'])->name('warehouse.in-stock');
    });

    // 4.8.1 Cash Transfer (Inter-entity transfer between Office & Site)
    Route::middleware('role:owner,admin,account_manager,site_manager')->group(function () {
        Route::get('/cash-transfer', [CashTransferController::class, 'index'])->name('cash-transfer.index');
        Route::post('/cash-transfer', [CashTransferController::class, 'store'])->name('cash-transfer.store');
        Route::get('/cash-transfer/{id}', [CashTransferController::class, 'show'])->name('cash-transfer.show');
        Route::put('/cash-transfer/{id}', [CashTransferController::class, 'update'])->name('cash-transfer.update');
        Route::post('/cash-transfer/{id}/update', [CashTransferController::class, 'update']);
        Route::post('/cash-transfer/{id}/settle', [CashTransferController::class, 'settle'])->name('cash-transfer.settle');
        Route::post('/cash-transfer/{id}/accept', [CashTransferController::class, 'accept'])->name('cash-transfer.accept');
        Route::delete('/cash-transfer/{id}', [CashTransferController::class, 'destroy'])->name('cash-transfer.destroy');
        Route::post('/cash-transfer/{id}/delete', [CashTransferController::class, 'destroy']);
    });

    // 4.9 Reports & Analytics - STANDALONE MODULES (No tab-wise, dedicated page-by-page reports)

    // Task 4 & 5: Admin Dedicated Report (Admin Only)
    Route::middleware('role:owner,admin')->group(function () {
        Route::get('/reports/admin/profit-loss', [AdminProfitLossReportController::class, 'adminProfitLoss'])->name('reports.admin.profit-loss');
        Route::get('/reports/admin/profit-loss/export-excel', [AdminProfitLossReportController::class, 'exportExcel'])->name('reports.admin.profit-loss.export-excel');
    });

    // Task 1 & 5: Site Manager 4 Dedicated Reports (Accessible by Site Manager & Admin)
    Route::middleware('role:site_manager')->group(function () {
        Route::get('/reports/site/labour-allocations', [SiteLabourAllocationReportController::class, 'index'])->name('reports.site.labour-allocations');
        Route::get('/reports/site/labour-allocations/export-excel', [SiteLabourAllocationReportController::class, 'exportExcel'])->name('reports.site.labour-allocations.export-excel');
        Route::get('/reports/site/daily-expenses', [DailyExpensesReportController::class, 'index'])->name('reports.site.daily-expenses');
        Route::get('/reports/site/daily-expenses/export-excel', [DailyExpensesReportController::class, 'exportExcel'])->name('reports.site.daily-expenses.export-excel');
        Route::get('/reports/site/cash-handling', [SiteCashHandlingReportController::class, 'index'])->name('reports.site.cash-handling');
        Route::get('/reports/site/cash-handling/export-excel', [SiteCashHandlingReportController::class, 'exportExcel'])->name('reports.site.cash-handling.export-excel');
        Route::get('/reports/site/purchases-inward', [SitePurchasesInwardReportController::class, 'index'])->name('reports.site.purchases-inward');
        Route::get('/reports/site/purchases-inward/export-excel', [SitePurchasesInwardReportController::class, 'exportExcel'])->name('reports.site.purchases-inward.export-excel');
        Route::get('/reports/site/warehouse-inventory', [SiteWarehouseInventoryReportController::class, 'index'])->name('reports.site.warehouse-inventory');
        Route::get('/reports/site/warehouse-inventory/export-excel', [SiteWarehouseInventoryReportController::class, 'exportExcel'])->name('reports.site.warehouse-inventory.export-excel');
        Route::get('/reports/site/stock-transfers', [StockTransferReportController::class, 'index'])->name('reports.site.stock-transfers');
        Route::get('/reports/site/stock-transfers/export-excel', [StockTransferReportController::class, 'exportExcel'])->name('reports.site.stock-transfers.export-excel');
    });

    // Task 3 & 5: Account Manager Dedicated Report (Accessible by Account Manager & Admin)
    Route::middleware('role:account_manager')->group(function () {
        Route::get('/reports/account/ledger', [AccountLedgerReportController::class, 'index'])->name('reports.account.ledger');
        Route::get('/reports/account/ledger/export-excel', [AccountLedgerReportController::class, 'exportExcel'])->name('reports.account.ledger.export-excel');
    });

    // 4.10 Dynamic RBAC (Roles, Permissions & System Users) & 4.11 Audit Logs (Admin Only)
    Route::middleware('role:owner,admin')->group(function () {
        // Dynamic Role Management & Menu Permissions
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{id}', [RoleController::class, 'show'])->name('roles.show');
        Route::post('/roles/{id}/update', [RoleController::class, 'update'])->name('roles.update');
        Route::put('/roles/{id}', [RoleController::class, 'update'])->name('roles.update.put');
        Route::post('/roles/{id}/delete', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy.delete');

        // System User Management
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users/{id}/update', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{id}/delete', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        // Audit Logs & Activity Monitor
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('audit-logs.export');
        Route::post('/audit-logs/simulate', [AuditLogController::class, 'simulate'])->name('audit-logs.simulate');
        Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
});
