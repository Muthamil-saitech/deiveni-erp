<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\DailyAllocation;
use App\Models\DailyAllocationItem;
use App\Models\Ledger;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PurchaseOrder;
use App\Models\SiteCash;
use App\Models\Supplier;
use App\Models\SupplierMaterial;
use App\Models\User;
use App\Models\WeeklyWage;
use App\Services\Contracts\DailyAllocationServiceInterface;
use App\Services\Contracts\DashboardServiceInterface;
use App\Services\Contracts\LedgerServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\SiteCashServiceInterface;
use App\Services\Contracts\WarehouseServiceInterface;
use App\Services\Contracts\WeeklyWageServiceInterface;
use App\Services\Contracts\WorkerCategoryServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DashboardService implements DashboardServiceInterface
{
    public function __construct(
        public ProjectSiteServiceInterface $projectSiteService,
        public DailyAllocationServiceInterface $dailyAllocationService,
        public WeeklyWageServiceInterface $weeklyWageService,
        public SiteCashServiceInterface $siteCashService,
        public WorkerCategoryServiceInterface $workerCategoryService,
        public WarehouseServiceInterface $warehouseService,
        public ?LedgerServiceInterface $ledgerService = null
    ) {
        $this->ledgerService = $ledgerService ?? app(LedgerServiceInterface::class);
    }

    /**
     * Restrict a query to records created by the given user unless they are an admin.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $resolvedUser = $user ?? Auth::user();

        if ($resolvedUser && ! $resolvedUser->isAdmin()) {
            $query->where('created_by', $resolvedUser->id);
        }

        return $query;
    }

    /**
     * Get aggregated dashboard data for the authenticated user based on role.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(?User $user = null): array
    {
        $user = $user ?? Auth::user();

        // 1. Full dynamic site manager metrics
        $siteManagerData = $this->getSiteManagerDashboardData($user);

        // 2. Full dynamic account manager metrics
        $accountManagerData = $this->getAccountManagerDashboardData($user);

        // Tailor 7-day cashflow chart specifically to the Ledger table for Account Managers
        if ($user && $user->isAccountManager()) {
            $ledgerChartData = $this->getLedger7DayChartData($user);
            $accountManagerData = array_merge($accountManagerData, $ledgerChartData);
        }

        return array_merge([
            'currentUser' => $user,
            'todayFormatted' => Carbon::now()->format('l, d M Y'),
        ], $siteManagerData, $accountManagerData);
    }

    /**
     * Get dynamic site manager dashboard metrics, chart datasets, and tables.
     *
     * @return array<string, mixed>
     */
    public function getSiteManagerDashboardData(?User $user = null): array
    {
        $today = Carbon::today()->toDateString();
        $sites = $this->projectSiteService->getAllSites();
        $siteStats = $this->projectSiteService->getSiteStats();

        // 1. Sites KPI
        $activeSitesCount = $sites->whereIn('status', ['In Progress', 'Planning'])->count();
        $completedSitesCount = $siteStats['completed'] ?? 0;
        $activeSites = $sites->where('status', '!=', 'Completed')->values();

        // 2. Labour KPI
        $todayAllocations = $this->scopeForUser(DailyAllocation::with(['items', 'expenses', 'projectSite']), $user)
            ->whereDate('date', $today)
            ->get();

        if ($todayAllocations->isNotEmpty()) {
            $labourCount = (int) $todayAllocations->sum('total_labour_count');
            $distinctSitesCount = $todayAllocations->pluck('project_site_id')->unique()->count();
            $labourSitesBadge = $distinctSitesCount.' '.($distinctSitesCount === 1 ? 'Site' : 'Sites');
            $isTodayLabour = true;
            $relevantAllocationIds = $todayAllocations->pluck('id')->toArray();
        } else {
            $latestAllocationDate = $this->scopeForUser(DailyAllocation::query(), $user)->max('date');
            if ($latestAllocationDate) {
                $latestAllocations = $this->scopeForUser(DailyAllocation::with(['items', 'expenses', 'projectSite']), $user)
                    ->whereDate('date', $latestAllocationDate)
                    ->get();
                $labourCount = (int) $latestAllocations->sum('total_labour_count');
                $distinctSitesCount = $latestAllocations->pluck('project_site_id')->unique()->count();
                $labourSitesBadge = Carbon::parse($latestAllocationDate)->format('d M').' ('.$distinctSitesCount.' '.($distinctSitesCount === 1 ? 'Site' : 'Sites').')';
                $isTodayLabour = false;
                $relevantAllocationIds = $latestAllocations->pluck('id')->toArray();
            } else {
                $labourCount = 0;
                $labourSitesBadge = '0 Sites';
                $isTodayLabour = false;
                $relevantAllocationIds = [];
            }
        }

        // 3. Weekly Wages KPI
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $currentWeekWages = (float) $this->scopeForUser(WeeklyWage::query(), $user)
            ->where(function ($query) use ($today, $weekStart, $weekEnd) {
                $query->whereBetween('week_start_date', [$weekStart, $weekEnd])
                    ->orWhere(function ($sub) use ($today) {
                        $sub->where('week_start_date', '<=', $today)
                            ->where('week_end_date', '>=', $today);
                    });
            })
            ->sum('net_payout');

        if ($currentWeekWages <= 0) {
            $latestWeeklyWage = $this->scopeForUser(WeeklyWage::query(), $user)->latest('week_start_date')->first();
            if ($latestWeeklyWage) {
                $currentWeekWages = (float) $latestWeeklyWage->net_payout;
            }
        }

        // 4. Site Cash KPI
        $cashStats = $this->siteCashService->getCashStats();
        $cashInHand = (float) ($cashStats['cash_in_hand'] ?? 0.0);

        // 5. Worker Categories KPI
        $workerCategories = $this->workerCategoryService->getAllCategories()
            ->where('status', 'Active')
            ->values();
        $workerCategoriesCount = $workerCategories->count();

        // 6. Warehouse Store KPI
        $warehouseStats = $this->warehouseService->getStats();
        $warehouseTotalItems = (int) ($warehouseStats['total_items'] ?? 0);
        $warehouseLowStockCount = (int) ($warehouseStats['low_stock_count'] ?? 0);

        // 7. 7-Day Cashflow / Income & Expense Chart
        $chartLabels = [];
        $incomeData = [];
        $expenseData = [];
        $sevenDayIncome = 0.0;
        $sevenDayExpense = 0.0;

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $chartLabels[] = $date->format('D d');

            $dayInflow = (float) $this->scopeForUser(SiteCash::query(), $user)
                ->whereDate('payment_date', $dateStr)
                ->where('flow_type', 'inflow')
                ->sum('in_amount');

            $dayOutflow = (float) $this->scopeForUser(SiteCash::query(), $user)
                ->whereDate('payment_date', $dateStr)
                ->where(function ($query) {
                    $query->where('is_expense', true)
                        ->orWhere('flow_type', 'outflow');
                })
                ->sum('out_amount');

            $incomeData[] = $dayInflow;
            $expenseData[] = $dayOutflow;
            $sevenDayIncome += $dayInflow;
            $sevenDayExpense += $dayOutflow;
        }

        $sevenDayNet = $sevenDayIncome - $sevenDayExpense;

        // 8. Trade Category Distribution Breakdown (Donut Chart & Wage Rates)
        $tradeChartLabels = [];
        $tradeChartData = [];
        $totalTradeLabourers = 0;

        if (! empty($relevantAllocationIds)) {
            $tradeBreakdown = DailyAllocationItem::query()
                ->whereIn('daily_allocation_id', $relevantAllocationIds)
                ->selectRaw('name_ta, name_en, SUM(worker_count) as total_count')
                ->groupBy('name_ta', 'name_en')
                ->having('total_count', '>', 0)
                ->get();

            foreach ($tradeBreakdown as $trade) {
                $tradeChartLabels[] = $trade->name_ta.($trade->name_en ? ' ('.Str::limit($trade->name_en, 12).')' : '');
                $tradeChartData[] = (int) $trade->total_count;
                $totalTradeLabourers += (int) $trade->total_count;
            }
        }

        // Fallback trade distribution if no allocations recorded yet
        if (empty($tradeChartData)) {
            $sampleCategories = $workerCategories->take(5);
            foreach ($sampleCategories as $cat) {
                $tradeChartLabels[] = $cat->name_ta;
                $tradeChartData[] = 0;
            }
            $totalTradeLabourers = 0;
        }

        $tradeWageRates = $workerCategories->take(4);

        // 9. Today's Site Daily Entry (Notebook Formula Snapshot)
        $latestAllocation = $this->scopeForUser(DailyAllocation::with(['projectSite', 'items.workerCategory', 'expenses']), $user)
            ->latest('date')
            ->latest('id')
            ->first();

        $latestAllocationFormulaString = '';
        $latestAllocationPreviewItems = [];

        if ($latestAllocation) {
            $formulaParts = [];
            foreach ($latestAllocation->items as $item) {
                if ($item->worker_count > 0) {
                    $formulaParts[] = "({$item->worker_count} {$item->name_ta} &times; ₹".number_format((float) $item->daily_wage).')';
                }
            }

            if ((float) $latestAllocation->total_expenses > 0) {
                $formulaParts[] = '₹'.number_format((float) $latestAllocation->total_expenses).' Expenses';
            }

            $latestAllocationFormulaString = ! empty($formulaParts)
                ? implode(' + ', $formulaParts).' = <strong>₹'.number_format((float) $latestAllocation->grand_total).'</strong>'
                : 'Grand Total: <strong>₹'.number_format((float) $latestAllocation->grand_total).'</strong>';

            $activeItems = $latestAllocation->items->where('worker_count', '>', 0)->values();
            foreach ($activeItems->take(2) as $item) {
                $latestAllocationPreviewItems[] = [
                    'label' => $item->name_ta,
                    'value' => "{$item->worker_count} &times; ₹".number_format((float) $item->daily_wage),
                ];
            }

            if ($activeItems->count() > 2) {
                $third = $activeItems[2];
                $latestAllocationPreviewItems[] = [
                    'label' => $third->name_ta,
                    'value' => "{$third->worker_count} &times; ₹".number_format((float) $third->daily_wage),
                ];
            } else {
                $latestAllocationPreviewItems[] = [
                    'label' => 'Transport / Extra',
                    'value' => '₹'.number_format((float) $latestAllocation->total_expenses),
                ];
            }
        }

        return [
            'activeSitesCount' => $activeSitesCount,
            'completedSitesCount' => $completedSitesCount,
            'activeSites' => $activeSites,
            'labourCount' => $labourCount,
            'labourSitesBadge' => $labourSitesBadge,
            'isTodayLabour' => $isTodayLabour,
            'currentWeekWages' => $currentWeekWages,
            'cashInHand' => $cashInHand,
            'workerCategoriesCount' => $workerCategoriesCount,
            'warehouseTotalItems' => $warehouseTotalItems,
            'warehouseLowStockCount' => $warehouseLowStockCount,
            'chartLabels' => $chartLabels,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'sevenDayIncome' => $sevenDayIncome,
            'sevenDayExpense' => $sevenDayExpense,
            'sevenDayNet' => $sevenDayNet,
            'tradeChartLabels' => $tradeChartLabels,
            'tradeChartData' => $tradeChartData,
            'totalTradeLabourers' => $totalTradeLabourers,
            'tradeWageRates' => $tradeWageRates,
            'latestAllocation' => $latestAllocation,
            'latestAllocationFormulaString' => $latestAllocationFormulaString,
            'latestAllocationPreviewItems' => $latestAllocationPreviewItems,
        ];
    }

    /**
     * Get dynamic account manager dashboard metrics, master summaries, and recent ledgers.
     *
     * @return array<string, mixed>
     */
    public function getAccountManagerDashboardData(?User $user = null): array
    {
        $today = Carbon::today()->toDateString();

        // 1. Today's Ledger Income & Expense
        $todayIncome = (float) $this->scopeForUser(Ledger::income(), $user)->whereDate('entry_date', $today)->sum('amount');
        $todayIncomeCount = (int) $this->scopeForUser(Ledger::income(), $user)->whereDate('entry_date', $today)->count();

        $todayExpense = (float) $this->scopeForUser(Ledger::expense(), $user)->whereDate('entry_date', $today)->sum('amount');
        $todayExpenseCount = (int) $this->scopeForUser(Ledger::expense(), $user)->whereDate('entry_date', $today)->count();

        $latestLedgerDate = $this->scopeForUser(Ledger::query(), $user)->max('entry_date');
        $isTodayLedger = ($todayIncome > 0 || $todayExpense > 0);

        // Overall Ledger Totals & Balance
        $ledgerTotals = $this->ledgerService ? $this->ledgerService->getSummary() : [
            'total_income' => (float) $this->scopeForUser(Ledger::income(), $user)->sum('amount'),
            'total_expense' => (float) $this->scopeForUser(Ledger::expense(), $user)->sum('amount'),
            'closing_balance' => (float) $this->scopeForUser(Ledger::income(), $user)->sum('amount') - (float) $this->scopeForUser(Ledger::expense(), $user)->sum('amount'),
        ];
        $ledgerTotalIncome = (float) ($ledgerTotals['total_income'] ?? 0.0);
        $ledgerTotalExpense = (float) ($ledgerTotals['total_expense'] ?? 0.0);
        $ledgerClosingBalance = (float) ($ledgerTotals['closing_balance'] ?? 0.0);

        // 2. Bank Master & Branch Master
        $activeBanksList = Bank::query()
            ->where('status', 'Active')
            ->withCount(['branches' => fn ($q) => $q->where('status', 'Active')])
            ->with(['branches' => fn ($q) => $q->where('status', 'Active')->orderBy('branch_name')])
            ->orderBy('bank_name')
            ->take(5)
            ->get();
        $activeBanksCount = Bank::where('status', 'Active')->count();
        $activeBranchesCount = Branch::where('status', 'Active')->count();

        // 3. Rental / Property Master
        $activePropertiesCount = Property::where('status', 'Active')->count();
        $activeRentalUnits = PropertyType::where('status', 'Active')->get();
        $activeRentalUnitsCount = $activeRentalUnits->count();
        $totalMonthlyRent = (float) $activeRentalUnits->sum('monthly_rent');
        $occupiedRentalUnitsCount = $activeRentalUnits->filter(fn ($u) => ! empty($u->tenant_name))->count();
        $totalSecurityDeposit = (float) $activeRentalUnits->sum('security_deposit');

        // 4. Supplier Master & Purchase Payables
        $activeSuppliersCount = Supplier::where('status', 'Active')->count();
        $totalPurchaseOrdersCount = $this->scopeForUser(PurchaseOrder::query(), $user)->count();
        $pendingSupplierPayables = (float) $this->scopeForUser(PurchaseOrder::where('status', '!=', 'Paid'), $user)->sum('total_amount');
        $totalSupplierPurchases = (float) $this->scopeForUser(PurchaseOrder::query(), $user)->sum('total_amount');
        $totalSupplierMaterialsCount = SupplierMaterial::count();

        // 5. Recent Ledger Entries
        $recentLedgers = $this->scopeForUser(Ledger::query(), $user)
            ->with([
                'projectSite.client',
                'supplier',
                'bank',
                'branch',
                'property',
                'propertyType',
                'transactionType',
                'creator',
            ])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        return [
            'todayLedgerIncome' => $todayIncome,
            'todayIncomeCount' => $todayIncomeCount,
            'todayLedgerExpense' => $todayExpense,
            'todayExpenseCount' => $todayExpenseCount,
            'isTodayLedger' => $isTodayLedger,
            'latestLedgerDate' => $latestLedgerDate,
            'ledgerTotalIncome' => $ledgerTotalIncome,
            'ledgerTotalExpense' => $ledgerTotalExpense,
            'ledgerClosingBalance' => $ledgerClosingBalance,
            'activeBanksList' => $activeBanksList,
            'activeBanksCount' => $activeBanksCount,
            'activeBranchesCount' => $activeBranchesCount,
            'activePropertiesCount' => $activePropertiesCount,
            'activeRentalUnitsCount' => $activeRentalUnitsCount,
            'totalMonthlyRent' => $totalMonthlyRent,
            'occupiedRentalUnitsCount' => $occupiedRentalUnitsCount,
            'totalSecurityDeposit' => $totalSecurityDeposit,
            'activeSuppliersCount' => $activeSuppliersCount,
            'totalPurchaseOrdersCount' => $totalPurchaseOrdersCount,
            'pendingSupplierPayables' => $pendingSupplierPayables,
            'totalSupplierPurchases' => $totalSupplierPurchases,
            'totalSupplierMaterialsCount' => $totalSupplierMaterialsCount,
            'recentLedgers' => $recentLedgers,
        ];
    }

    /**
     * Get 7-day income, expense and net cashflow data from the Ledger table.
     *
     * @return array<string, mixed>
     */
    public function getLedger7DayChartData(?User $user = null): array
    {
        $chartLabels = [];
        $incomeData = [];
        $expenseData = [];
        $sevenDayIncome = 0.0;
        $sevenDayExpense = 0.0;

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $chartLabels[] = $date->format('D d');

            $dayInflow = (float) $this->scopeForUser(Ledger::income(), $user)
                ->whereDate('entry_date', $dateStr)
                ->sum('amount');

            $dayOutflow = (float) $this->scopeForUser(Ledger::expense(), $user)
                ->whereDate('entry_date', $dateStr)
                ->sum('amount');

            $incomeData[] = $dayInflow;
            $expenseData[] = $dayOutflow;
            $sevenDayIncome += $dayInflow;
            $sevenDayExpense += $dayOutflow;
        }

        $sevenDayNet = $sevenDayIncome - $sevenDayExpense;

        return [
            'chartLabels' => $chartLabels,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'sevenDayIncome' => $sevenDayIncome,
            'sevenDayExpense' => $sevenDayExpense,
            'sevenDayNet' => $sevenDayNet,
        ];
    }
}
