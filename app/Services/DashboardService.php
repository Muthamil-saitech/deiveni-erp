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
use Carbon\CarbonPeriod;
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
     * Get start date, end date, and label for a given period filter.
     *
     * @param  array{start_date?: ?string, end_date?: ?string}  $customDates
     * @return array{start: string, end: string, label: string, ta_label: string}
     */
    public function getDateRangeForPeriod(string $period, array $customDates = []): array
    {
        $now = Carbon::now();

        if ($period === 'custom' && ! empty($customDates['start_date'])) {
            $startDate = Carbon::parse($customDates['start_date'])->toDateString();
            $endDate = ! empty($customDates['end_date'])
                ? Carbon::parse($customDates['end_date'])->toDateString()
                : $startDate;

            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            return [
                'start' => $startDate,
                'end' => $endDate,
                'label' => Carbon::parse($startDate)->format('d M').' - '.Carbon::parse($endDate)->format('d M Y'),
                'ta_label' => 'குறிப்பிட்ட தேதி',
            ];
        }

        if ($period === 'all') {
            return [
                'start' => '1970-01-01',
                'end' => '2099-12-31',
                'label' => 'All Time',
                'ta_label' => 'அனைத்தும்',
            ];
        }

        return match ($period) {
            'this_week' => [
                'start' => $now->copy()->subDays(6)->toDateString(),
                'end' => $now->toDateString(),
                'label' => 'This Week',
                'ta_label' => 'இந்த வாரம்',
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString(),
                'label' => 'This Month',
                'ta_label' => 'இந்த மாதம்',
            ],
            'this_year' => [
                'start' => $now->copy()->startOfYear()->toDateString(),
                'end' => $now->copy()->endOfYear()->toDateString(),
                'label' => 'This Year',
                'ta_label' => 'இந்த ஆண்டு',
            ],
            default => [
                'start' => $now->toDateString(),
                'end' => $now->toDateString(),
                'label' => 'Today',
                'ta_label' => 'இன்று',
            ],
        };
    }

    /**
     * Get aggregated dashboard data for the authenticated user based on role.
     *
     * @param  array{start_date?: ?string, end_date?: ?string}  $customDates
     * @return array<string, mixed>
     */
    public function getDashboardData(?User $user = null, string $period = 'today', array $customDates = []): array
    {
        $user = $user ?? Auth::user();

        // 1. Full dynamic site manager metrics
        $siteManagerData = $this->getSiteManagerDashboardData($user);

        // 2. Full dynamic account manager / admin metrics
        $accountManagerData = $this->getAccountManagerDashboardData($user, $period, $customDates);

        // Period cashflow chart tailored to Ledger table for Admin and Account Manager
        if ($user && ($user->isAdmin() || $user->isAccountManager())) {
            $ledgerChartData = $this->getLedger7DayChartData($user, $period, $customDates);
            $accountManagerData = array_merge($accountManagerData, $ledgerChartData);
        }

        $periodInfo = $this->getDateRangeForPeriod($period, $customDates);

        // Admin-specific Expense Distribution by Source
        $adminExpenseBreakdown = [];
        if ($user && $user->isAdmin()) {
            $adminExpenseBreakdown = $this->getAdminExpenseBreakdown($user, $periodInfo['start'], $periodInfo['end'], $period);
        }

        // Attach period spent to active sites based on date filter
        $sitePeriodExpensesQuery = $this->scopeForUser(Ledger::expense(), $user)
            ->whereNotNull('project_site_id');
        if ($period !== 'all') {
            $sitePeriodExpensesQuery->whereBetween('entry_date', [$periodInfo['start'], $periodInfo['end']]);
        }
        $sitePeriodExpenses = $sitePeriodExpensesQuery
            ->groupBy('project_site_id')
            ->selectRaw('project_site_id, SUM(amount) as total_spent')
            ->pluck('total_spent', 'project_site_id');

        $sitePeriodAllocationsQuery = $this->scopeForUser(DailyAllocation::query(), $user)
            ->whereNotNull('project_site_id');
        if ($period !== 'all') {
            $sitePeriodAllocationsQuery->whereBetween('date', [$periodInfo['start'], $periodInfo['end']]);
        }
        $sitePeriodAllocations = $sitePeriodAllocationsQuery
            ->groupBy('project_site_id')
            ->selectRaw('project_site_id, SUM(grand_total) as total_spent')
            ->pluck('total_spent', 'project_site_id');

        if (isset($siteManagerData['activeSites'])) {
            foreach ($siteManagerData['activeSites'] as $site) {
                $ledgerSpent = (float) ($sitePeriodExpenses[$site->id] ?? 0.0);
                $allocSpent = (float) ($sitePeriodAllocations[$site->id] ?? 0.0);
                $site->period_spent = max($ledgerSpent, $allocSpent);
            }
        }

        return array_merge([
            'currentUser' => $user,
            'todayFormatted' => Carbon::now()->format('l, d M Y'),
            'selectedPeriod' => $period,
            'periodLabel' => $periodInfo['label'],
            'periodLabelTa' => $periodInfo['ta_label'],
            'periodStart' => $periodInfo['start'],
            'periodEnd' => $periodInfo['end'],
            'customStartDate' => $customDates['start_date'] ?? '',
            'customEndDate' => $customDates['end_date'] ?? '',
        ], $siteManagerData, $accountManagerData, $adminExpenseBreakdown);
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
     * @param  array{start_date?: ?string, end_date?: ?string}  $customDates
     * @return array<string, mixed>
     */
    public function getAccountManagerDashboardData(?User $user = null, string $period = 'today', array $customDates = []): array
    {
        $today = Carbon::today()->toDateString();
        $dateRange = $this->getDateRangeForPeriod($period, $customDates);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        // 1. Period-aware Ledger Income & Expense
        $periodIncomeQuery = $this->scopeForUser(Ledger::income(), $user);
        $periodExpenseQuery = $this->scopeForUser(Ledger::expense(), $user);

        if ($period !== 'all') {
            $periodIncomeQuery->whereBetween('entry_date', [$startDate, $endDate]);
            $periodExpenseQuery->whereBetween('entry_date', [$startDate, $endDate]);
        }

        $periodIncome = (float) $periodIncomeQuery->sum('amount');
        $periodIncomeCount = (int) $periodIncomeQuery->count();

        $periodExpense = (float) $periodExpenseQuery->sum('amount');
        $periodExpenseCount = (int) $periodExpenseQuery->count();

        // Also calculate today's specific numbers
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

        // Period-specific Labour & Allocation metrics
        $periodAllocationsQuery = $this->scopeForUser(DailyAllocation::query(), $user);
        if ($period !== 'all') {
            $periodAllocationsQuery->whereBetween('date', [$startDate, $endDate]);
        }
        $periodAllocationsCount = (int) $periodAllocationsQuery->count();
        $periodLabourCount = (int) $periodAllocationsQuery->sum('total_labour_count');
        $periodWagesTotal = (float) $periodAllocationsQuery->sum('grand_total');

        // Period-specific Purchases & PO metrics
        $periodPurchaseOrdersQuery = $this->scopeForUser(PurchaseOrder::query(), $user);
        if ($period !== 'all') {
            $periodPurchaseOrdersQuery->whereBetween('order_date', [$startDate, $endDate]);
        }
        $periodPurchaseOrdersCount = (int) $periodPurchaseOrdersQuery->count();
        $periodPurchasesTotal = (float) $periodPurchaseOrdersQuery->sum('total_amount');

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
        $periodPendingPayablesQuery = $this->scopeForUser(PurchaseOrder::where('status', '!=', 'Paid'), $user);
        if ($period !== 'all') {
            $periodPendingPayablesQuery->whereBetween('order_date', [$startDate, $endDate]);
        }
        $periodPendingPayables = (float) $periodPendingPayablesQuery->sum('total_amount');
        $totalSupplierPurchases = (float) $this->scopeForUser(PurchaseOrder::query(), $user)->sum('total_amount');
        $totalSupplierMaterialsCount = SupplierMaterial::count();

        // 5. Recent Ledger Entries filtered by date range
        $recentLedgersQuery = $this->scopeForUser(Ledger::query(), $user);
        if ($period !== 'all') {
            $recentLedgersQuery->whereBetween('entry_date', [$startDate, $endDate]);
        }
        $recentLedgers = $recentLedgersQuery
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
            ->take(8)
            ->get();

        return [
            'periodIncome' => $periodIncome,
            'periodIncomeCount' => $periodIncomeCount,
            'periodExpense' => $periodExpense,
            'periodExpenseCount' => $periodExpenseCount,
            'periodNet' => $periodIncome - $periodExpense,
            'periodAllocationsCount' => $periodAllocationsCount,
            'periodLabourCount' => $periodLabourCount,
            'periodWagesTotal' => $periodWagesTotal,
            'periodPurchaseOrdersCount' => $periodPurchaseOrdersCount,
            'periodPurchasesTotal' => $periodPurchasesTotal,
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
            'periodPendingPayables' => $periodPendingPayables,
            'totalSupplierPurchases' => $totalSupplierPurchases,
            'totalSupplierMaterialsCount' => $totalSupplierMaterialsCount,
            'recentLedgers' => $recentLedgers,
        ];
    }

    /**
     * Get income, expense and net cashflow data from the Ledger table for a period.
     *
     * @param  array{start_date?: ?string, end_date?: ?string}  $customDates
     * @return array<string, mixed>
     */
    public function getLedger7DayChartData(?User $user = null, string $period = 'today', array $customDates = []): array
    {
        $chartLabels = [];
        $incomeData = [];
        $expenseData = [];
        $totalPeriodIncome = 0.0;
        $totalPeriodExpense = 0.0;

        if ($period === 'custom') {
            $dateRange = $this->getDateRangeForPeriod($period, $customDates);
            $start = Carbon::parse($dateRange['start']);
            $end = Carbon::parse($dateRange['end']);
            $daysDiff = $start->diffInDays($end);

            if ($daysDiff <= 31) {
                for ($d = 0; $d <= $daysDiff; $d++) {
                    $date = $start->copy()->addDays($d);
                    $dateStr = $date->toDateString();
                    $chartLabels[] = $date->format('d M');

                    $dayInflow = (float) $this->scopeForUser(Ledger::income(), $user)
                        ->whereDate('entry_date', $dateStr)
                        ->sum('amount');

                    $dayOutflow = (float) $this->scopeForUser(Ledger::expense(), $user)
                        ->whereDate('entry_date', $dateStr)
                        ->sum('amount');

                    $incomeData[] = $dayInflow;
                    $expenseData[] = $dayOutflow;
                    $totalPeriodIncome += $dayInflow;
                    $totalPeriodExpense += $dayOutflow;
                }
            } else {
                // Group by month
                $periodMonths = CarbonPeriod::create($start->startOfMonth(), '1 month', $end->endOfMonth());
                foreach ($periodMonths as $m) {
                    $mStart = $m->copy()->startOfMonth()->toDateString();
                    $mEnd = $m->copy()->endOfMonth()->toDateString();
                    $chartLabels[] = $m->format('M y');

                    $mInflow = (float) $this->scopeForUser(Ledger::income(), $user)
                        ->whereBetween('entry_date', [$mStart, $mEnd])
                        ->sum('amount');

                    $mOutflow = (float) $this->scopeForUser(Ledger::expense(), $user)
                        ->whereBetween('entry_date', [$mStart, $mEnd])
                        ->sum('amount');

                    $incomeData[] = $mInflow;
                    $expenseData[] = $mOutflow;
                    $totalPeriodIncome += $mInflow;
                    $totalPeriodExpense += $mOutflow;
                }
            }
        } elseif ($period === 'all') {
            // Multi-year timeline across all recorded data without grouping into months of a single year
            $minEntryDate = $this->scopeForUser(Ledger::query(), $user)->min('entry_date');
            $maxEntryDate = $this->scopeForUser(Ledger::query(), $user)->max('entry_date');

            $earliestRecordYear = $minEntryDate ? (int) Carbon::parse($minEntryDate)->year : Carbon::now()->year;
            $latestRecordYear = $maxEntryDate ? (int) Carbon::parse($maxEntryDate)->year : Carbon::now()->year;

            // Ensure a multi-year range from earliest record up to current/latest year (at least past 5 years if only current year has entries)
            $startYear = min($earliestRecordYear, Carbon::now()->subYears(4)->year);
            $endYear = max($latestRecordYear, Carbon::now()->year);

            for ($y = $startYear; $y <= $endYear; $y++) {
                $chartLabels[] = (string) $y;

                $yInflow = (float) $this->scopeForUser(Ledger::income(), $user)
                    ->whereYear('entry_date', $y)
                    ->sum('amount');

                $yOutflow = (float) $this->scopeForUser(Ledger::expense(), $user)
                    ->whereYear('entry_date', $y)
                    ->sum('amount');

                $incomeData[] = $yInflow;
                $expenseData[] = $yOutflow;
            }

            // All-time totals spanning all years and entries
            $totalPeriodIncome = (float) $this->scopeForUser(Ledger::income(), $user)->sum('amount');
            $totalPeriodExpense = (float) $this->scopeForUser(Ledger::expense(), $user)->sum('amount');
        } elseif ($period === 'this_year') {
            // Group by 12 months of the year
            $year = Carbon::now()->year;
            for ($month = 1; $month <= 12; $month++) {
                $monthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
                $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
                $chartLabels[] = Carbon::create($year, $month, 1)->format('M');

                $monthInflow = (float) $this->scopeForUser(Ledger::income(), $user)
                    ->whereBetween('entry_date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $monthOutflow = (float) $this->scopeForUser(Ledger::expense(), $user)
                    ->whereBetween('entry_date', [$monthStart, $monthEnd])
                    ->sum('amount');

                $incomeData[] = $monthInflow;
                $expenseData[] = $monthOutflow;
                $totalPeriodIncome += $monthInflow;
                $totalPeriodExpense += $monthOutflow;
            }
        } elseif ($period === 'this_month') {
            // Group by each week or 5-day buckets or days in current month
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
            $daysInMonth = $endOfMonth->day;

            // Step by 2 or 3 days if month is large, or daily up to end of month
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = Carbon::now()->startOfMonth()->day($d);
                $dateStr = $date->toDateString();
                $chartLabels[] = $date->format('d M');

                $dayInflow = (float) $this->scopeForUser(Ledger::income(), $user)
                    ->whereDate('entry_date', $dateStr)
                    ->sum('amount');

                $dayOutflow = (float) $this->scopeForUser(Ledger::expense(), $user)
                    ->whereDate('entry_date', $dateStr)
                    ->sum('amount');

                $incomeData[] = $dayInflow;
                $expenseData[] = $dayOutflow;
                $totalPeriodIncome += $dayInflow;
                $totalPeriodExpense += $dayOutflow;
            }
        } elseif ($period === 'this_week') {
            // 7 days rolling window ending today
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
                $totalPeriodIncome += $dayInflow;
                $totalPeriodExpense += $dayOutflow;
            }
        } else {
            // 'today': show today's specific hourly or daily summary, or 7-day trend ending today
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
                $totalPeriodIncome += $dayInflow;
                $totalPeriodExpense += $dayOutflow;
            }
        }

        $periodNet = $totalPeriodIncome - $totalPeriodExpense;

        return [
            'chartLabels' => $chartLabels,
            'incomeData' => $incomeData,
            'expenseData' => $expenseData,
            'sevenDayIncome' => $totalPeriodIncome,
            'sevenDayExpense' => $totalPeriodExpense,
            'sevenDayNet' => $periodNet,
        ];
    }

    /**
     * Get expense breakdown by category/source for Admin dashboard.
     *
     * @return array{expenseBreakdownLabels: array<string>, expenseBreakdownData: array<float>, totalExpenseBreakdown: float, topExpenseItems: array<array{label: string, value: float, percentage: float}>}
     */
    public function getAdminExpenseBreakdown(?User $user, string $startDate, string $endDate, string $period = ''): array
    {
        $expensesQuery = $this->scopeForUser(Ledger::expense(), $user);
        if ($period !== 'all') {
            $expensesQuery->whereBetween('entry_date', [$startDate, $endDate]);
        }
        $expensesBySource = $expensesQuery
            ->selectRaw('source, SUM(amount) as total_amount')
            ->groupBy('source')
            ->having('total_amount', '>', 0)
            ->orderByDesc('total_amount')
            ->get();

        $labels = [];
        $data = [];
        $total = 0.0;
        $items = [];

        foreach ($expensesBySource as $row) {
            $sourceLabel = match ($row->source) {
                'Construction' => 'Construction Sites (தள செலவுகள்)',
                'Supplier' => 'Supplier Purchases (சப்ளையர்)',
                'Office and other expenses' => 'Office & Admin (அலுவலக செலவு)',
                'Rental' => 'Rental Properties (வாடகை)',
                'Bank', 'Bank Valuation' => 'Bank / Financial (வங்கி)',
                default => $row->source ?: 'General Expense',
            };

            $amount = (float) $row->total_amount;
            $labels[] = $sourceLabel;
            $data[] = $amount;
            $total += $amount;
        }

        // When expenses exist for this period, calculate percentages
        foreach ($labels as $index => $label) {
            $val = $data[$index] ?? 0.0;
            $items[] = [
                'label' => $label,
                'value' => $val,
                'percentage' => $total > 0 ? round(($val / $total) * 100, 1) : 0,
            ];
        }

        return [
            'expenseBreakdownLabels' => $labels,
            'expenseBreakdownData' => $data,
            'totalExpenseBreakdown' => $total,
            'topExpenseItems' => $items,
        ];
    }
}
