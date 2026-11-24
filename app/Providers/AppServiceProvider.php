<?php

namespace App\Providers;

use App\Models\AdminLedger;
use App\Models\Bank;
use App\Models\Branch;
use App\Models\Client;
use App\Models\DailyAllocation;
use App\Models\Ledger;
use App\Models\Material;
use App\Models\ProjectSite;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\TransactionType;
use App\Models\Unit;
use App\Models\User;
use App\Models\WarehouseItem;
use App\Models\WarehouseMovement;
use App\Models\WeeklyWage;
use App\Models\WorkerCategory;
use App\Observers\AuditObserver;
use App\Repositories\Contracts\AccountLedgerReportRepositoryInterface;
use App\Repositories\Contracts\AdminLedgerRepositoryInterface;
use App\Repositories\Contracts\AdminProfitLossReportRepositoryInterface;
use App\Repositories\Contracts\AuditLogRepositoryInterface;
use App\Repositories\Contracts\BankRepositoryInterface;
use App\Repositories\Contracts\BranchRepositoryInterface;
use App\Repositories\Contracts\CashTransferRepositoryInterface;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\DailyAllocationRepositoryInterface;
use App\Repositories\Contracts\LedgerRepositoryInterface;
use App\Repositories\Contracts\MaterialRepositoryInterface;
use App\Repositories\Contracts\ProjectSiteRepositoryInterface;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use App\Repositories\Contracts\PropertyTypeRepositoryInterface;
use App\Repositories\Contracts\PurchaseRepositoryInterface;
use App\Repositories\Contracts\RentalRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SiteCashHandlingReportRepositoryInterface;
use App\Repositories\Contracts\SiteCashRepositoryInterface;
use App\Repositories\Contracts\SiteLabourAllocationReportRepositoryInterface;
use App\Repositories\Contracts\SitePurchasesInwardReportRepositoryInterface;
use App\Repositories\Contracts\SiteWarehouseInventoryReportRepositoryInterface;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use App\Repositories\Contracts\TransactionTypeRepositoryInterface;
use App\Repositories\Contracts\UnitRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Repositories\Contracts\WeeklyWageRepositoryInterface;
use App\Repositories\Contracts\WorkerCategoryRepositoryInterface;
use App\Repositories\Eloquent\AccountLedgerReportRepository;
use App\Repositories\Eloquent\AdminLedgerRepository;
use App\Repositories\Eloquent\AdminProfitLossReportRepository;
use App\Repositories\Eloquent\AuditLogRepository;
use App\Repositories\Eloquent\BankRepository;
use App\Repositories\Eloquent\BranchRepository;
use App\Repositories\Eloquent\CashTransferRepository;
use App\Repositories\Eloquent\ClientRepository;
use App\Repositories\Eloquent\DailyAllocationRepository;
use App\Repositories\Eloquent\LedgerRepository;
use App\Repositories\Eloquent\MaterialRepository;
use App\Repositories\Eloquent\ProjectSiteRepository;
use App\Repositories\Eloquent\PropertyRepository;
use App\Repositories\Eloquent\PropertyTypeRepository;
use App\Repositories\Eloquent\PurchaseRepository;
use App\Repositories\Eloquent\RentalRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\SiteCashHandlingReportRepository;
use App\Repositories\Eloquent\SiteCashRepository;
use App\Repositories\Eloquent\SiteLabourAllocationReportRepository;
use App\Repositories\Eloquent\SitePurchasesInwardReportRepository;
use App\Repositories\Eloquent\SiteWarehouseInventoryReportRepository;
use App\Repositories\Eloquent\SupplierRepository;
use App\Repositories\Eloquent\TransactionTypeRepository;
use App\Repositories\Eloquent\UnitRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WarehouseRepository;
use App\Repositories\Eloquent\WeeklyWageRepository;
use App\Repositories\Eloquent\WorkerCategoryRepository;
use App\Services\AccountLedgerReportService;
use App\Services\AdminLedgerService;
use App\Services\AdminProfitLossReportService;
use App\Services\AuditLogService;
use App\Services\AuthService;
use App\Services\BankService;
use App\Services\BranchService;
use App\Services\CashTransferService;
use App\Services\Contracts\AccountLedgerReportServiceInterface;
use App\Services\Contracts\AdminLedgerServiceInterface;
use App\Services\Contracts\AdminProfitLossReportServiceInterface;
use App\Services\Contracts\AuditLogServiceInterface;
use App\Services\Contracts\AuthServiceInterface;
use App\Services\Contracts\BankServiceInterface;
use App\Services\Contracts\BranchServiceInterface;
use App\Services\Contracts\CashTransferServiceInterface;
use App\Services\Contracts\DailyAllocationServiceInterface;
use App\Services\Contracts\DailyExpensesReportServiceInterface;
use App\Services\Contracts\DashboardServiceInterface;
use App\Services\Contracts\LedgerServiceInterface;
use App\Services\Contracts\MaterialServiceInterface;
use App\Services\Contracts\MaterialUsageServiceInterface;
use App\Services\Contracts\ProfileServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\PropertyServiceInterface;
use App\Services\Contracts\PropertyTypeServiceInterface;
use App\Services\Contracts\PurchaseServiceInterface;
use App\Services\Contracts\RentalServiceInterface;
use App\Services\Contracts\RoleServiceInterface;
use App\Services\Contracts\SiteCashHandlingReportServiceInterface;
use App\Services\Contracts\SiteCashServiceInterface;
use App\Services\Contracts\SiteLabourAllocationReportServiceInterface;
use App\Services\Contracts\SitePurchasesInwardReportServiceInterface;
use App\Services\Contracts\SiteWarehouseInventoryReportServiceInterface;
use App\Services\Contracts\StockTransferReportServiceInterface;
use App\Services\Contracts\StockTransferServiceInterface;
use App\Services\Contracts\SupplierServiceInterface;
use App\Services\Contracts\TransactionTypeServiceInterface;
use App\Services\Contracts\UnitServiceInterface;
use App\Services\Contracts\UserServiceInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use App\Services\Contracts\WeeklyWageServiceInterface;
use App\Services\Contracts\WorkerCategoryServiceInterface;
use App\Services\DailyAllocationService;
use App\Services\DailyExpensesReportService;
use App\Services\DashboardService;
use App\Services\LedgerService;
use App\Services\MaterialService;
use App\Services\MaterialUsageService;
use App\Services\ProfileService;
use App\Services\ProjectSiteService;
use App\Services\PropertyService;
use App\Services\PropertyTypeService;
use App\Services\PurchaseService;
use App\Services\RentalService;
use App\Services\RoleService;
use App\Services\SiteCashHandlingReportService;
use App\Services\SiteCashService;
use App\Services\SiteLabourAllocationReportService;
use App\Services\SitePurchasesInwardReportService;
use App\Services\SiteWarehouseInventoryReportService;
use App\Services\StockTransferReportService;
use App\Services\StockTransferService;
use App\Services\SupplierService;
use App\Services\TransactionTypeService;
use App\Services\UnitService;
use App\Services\UserService;
use App\Services\WarehouseService;
use App\Services\WeeklyWageService;
use App\Services\WorkerCategoryService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
        );

        $this->app->bind(
            RoleRepositoryInterface::class,
            RoleRepository::class
        );

        $this->app->bind(
            RoleServiceInterface::class,
            RoleService::class
        );

        $this->app->bind(
            AuthServiceInterface::class,
            AuthService::class
        );

        $this->app->bind(
            WorkerCategoryRepositoryInterface::class,
            WorkerCategoryRepository::class
        );

        $this->app->bind(
            WorkerCategoryServiceInterface::class,
            WorkerCategoryService::class
        );

        $this->app->bind(
            UnitRepositoryInterface::class,
            UnitRepository::class
        );

        $this->app->bind(
            UnitServiceInterface::class,
            UnitService::class
        );

        $this->app->bind(
            MaterialRepositoryInterface::class,
            MaterialRepository::class
        );

        $this->app->bind(
            MaterialServiceInterface::class,
            MaterialService::class
        );

        $this->app->bind(
            SupplierRepositoryInterface::class,
            SupplierRepository::class
        );

        $this->app->bind(
            SupplierServiceInterface::class,
            SupplierService::class
        );

        $this->app->bind(
            ProfileServiceInterface::class,
            ProfileService::class
        );

        $this->app->bind(
            BankRepositoryInterface::class,
            BankRepository::class
        );

        $this->app->bind(
            BankServiceInterface::class,
            BankService::class
        );

        $this->app->bind(
            RentalRepositoryInterface::class,
            RentalRepository::class
        );

        $this->app->bind(
            RentalServiceInterface::class,
            RentalService::class
        );

        $this->app->bind(
            PropertyRepositoryInterface::class,
            PropertyRepository::class
        );

        $this->app->bind(
            PropertyServiceInterface::class,
            PropertyService::class
        );

        $this->app->bind(
            PropertyTypeRepositoryInterface::class,
            PropertyTypeRepository::class
        );

        $this->app->bind(
            PropertyTypeServiceInterface::class,
            PropertyTypeService::class
        );

        $this->app->bind(
            BranchRepositoryInterface::class,
            BranchRepository::class
        );

        $this->app->bind(
            BranchServiceInterface::class,
            BranchService::class
        );

        $this->app->bind(
            ClientRepositoryInterface::class,
            ClientRepository::class
        );

        $this->app->bind(
            ProjectSiteRepositoryInterface::class,
            ProjectSiteRepository::class
        );

        $this->app->bind(
            ProjectSiteServiceInterface::class,
            ProjectSiteService::class
        );

        $this->app->bind(
            TransactionTypeRepositoryInterface::class,
            TransactionTypeRepository::class
        );

        $this->app->bind(
            TransactionTypeServiceInterface::class,
            TransactionTypeService::class
        );

        $this->app->bind(
            SiteCashRepositoryInterface::class,
            SiteCashRepository::class
        );

        $this->app->bind(
            SiteCashServiceInterface::class,
            SiteCashService::class
        );

        $this->app->bind(
            DailyAllocationRepositoryInterface::class,
            DailyAllocationRepository::class
        );

        $this->app->bind(
            DailyAllocationServiceInterface::class,
            DailyAllocationService::class
        );

        $this->app->bind(
            WeeklyWageRepositoryInterface::class,
            WeeklyWageRepository::class
        );

        $this->app->bind(
            WeeklyWageServiceInterface::class,
            WeeklyWageService::class
        );

        $this->app->bind(
            SiteLabourAllocationReportRepositoryInterface::class,
            SiteLabourAllocationReportRepository::class
        );

        $this->app->bind(
            SiteLabourAllocationReportServiceInterface::class,
            SiteLabourAllocationReportService::class
        );

        $this->app->bind(
            DailyExpensesReportServiceInterface::class,
            DailyExpensesReportService::class
        );

        $this->app->bind(
            SiteCashHandlingReportRepositoryInterface::class,
            SiteCashHandlingReportRepository::class
        );

        $this->app->bind(
            SiteCashHandlingReportServiceInterface::class,
            SiteCashHandlingReportService::class
        );

        $this->app->bind(
            WarehouseRepositoryInterface::class,
            WarehouseRepository::class
        );

        $this->app->bind(
            WarehouseServiceInterface::class,
            WarehouseService::class
        );

        $this->app->bind(
            PurchaseRepositoryInterface::class,
            PurchaseRepository::class
        );

        $this->app->bind(
            PurchaseServiceInterface::class,
            PurchaseService::class
        );

        $this->app->bind(
            SitePurchasesInwardReportRepositoryInterface::class,
            SitePurchasesInwardReportRepository::class
        );

        $this->app->bind(
            SitePurchasesInwardReportServiceInterface::class,
            SitePurchasesInwardReportService::class
        );

        $this->app->bind(
            SiteWarehouseInventoryReportRepositoryInterface::class,
            SiteWarehouseInventoryReportRepository::class
        );

        $this->app->bind(
            SiteWarehouseInventoryReportServiceInterface::class,
            SiteWarehouseInventoryReportService::class
        );

        $this->app->bind(
            StockTransferServiceInterface::class,
            StockTransferService::class
        );

        $this->app->bind(
            MaterialUsageServiceInterface::class,
            MaterialUsageService::class
        );

        $this->app->bind(
            StockTransferReportServiceInterface::class,
            StockTransferReportService::class
        );

        $this->app->bind(
            DashboardServiceInterface::class,
            DashboardService::class
        );

        $this->app->bind(
            LedgerRepositoryInterface::class,
            LedgerRepository::class
        );

        $this->app->bind(
            LedgerServiceInterface::class,
            LedgerService::class
        );

        $this->app->bind(
            AdminLedgerRepositoryInterface::class,
            AdminLedgerRepository::class
        );

        $this->app->bind(
            AdminLedgerServiceInterface::class,
            AdminLedgerService::class
        );

        $this->app->bind(
            AccountLedgerReportRepositoryInterface::class,
            AccountLedgerReportRepository::class
        );

        $this->app->bind(
            AccountLedgerReportServiceInterface::class,
            AccountLedgerReportService::class
        );

        $this->app->bind(
            AdminProfitLossReportRepositoryInterface::class,
            AdminProfitLossReportRepository::class
        );

        $this->app->bind(
            AdminProfitLossReportServiceInterface::class,
            AdminProfitLossReportService::class
        );

        $this->app->bind(
            AuditLogRepositoryInterface::class,
            AuditLogRepository::class
        );

        $this->app->bind(
            AuditLogServiceInterface::class,
            AuditLogService::class
        );

        $this->app->bind(
            CashTransferRepositoryInterface::class,
            CashTransferRepository::class
        );

        $this->app->bind(
            CashTransferServiceInterface::class,
            CashTransferService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('amount', function ($expression) {
            return "<?php
                \$amount = (float) ($expression);
                \$abs = abs(\$amount);

                if (\$abs >= 10000000) {
                    \$value = \$amount / 10000000;
                    \$suffix = ' Cr';
                } elseif (\$abs >= 100000) {
                    \$value = \$amount / 100000;
                    \$suffix = ' L';
                } elseif (\$abs >= 1000) {
                    \$value = \$amount / 1000;
                    \$suffix = ' K';
                } else {
                    \$value = \$amount;
                    \$suffix = '';
                }

                echo '₹' . rtrim(rtrim(number_format(\$value, 2), '0'), '.') . \$suffix;
            ?>";
        });
        URL::forceScheme('http');
        // Audit Model CRUD Events (Create, Update, Delete)
        $auditableModels = [
            User::class,
            Role::class,
            RoleMenu::class,
            ProjectSite::class,
            Client::class,
            SiteCash::class,
            DailyAllocation::class,
            DailyAllocationItem::class,
            DailyAllocationExpense::class,
            WeeklyWage::class,
            WeeklyWageExpense::class,
            PurchaseOrder::class,
            PurchaseOrderItem::class,
            WarehouseItem::class,
            WarehouseMovement::class,
            Ledger::class,
            AdminLedger::class,
            WorkerCategory::class,
            Unit::class,
            Material::class,
            Supplier::class,
            SupplierMaterial::class,
            Bank::class,
            Branch::class,
            Property::class,
            PropertyType::class,
            TransactionType::class,
        ];

        foreach ($auditableModels as $modelClass) {
            if (class_exists($modelClass)) {
                $modelClass::observe(AuditObserver::class);
            }
        }
    }
}
