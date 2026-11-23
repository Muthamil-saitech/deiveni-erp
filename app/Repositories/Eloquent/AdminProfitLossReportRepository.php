<?php

namespace App\Repositories\Eloquent;

use App\Models\ProjectSite;
use App\Models\SiteCash;
use App\Repositories\Contracts\AdminProfitLossReportRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AdminProfitLossReportRepository implements AdminProfitLossReportRepositoryInterface
{
    /**
     * Get all active and existing project sites with client relation.
     *
     * @return Collection<int, ProjectSite>
     */
    public function getProjectSites(): Collection
    {
        return ProjectSite::with('client')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get site-wise and aggregate financial dataset for profit and loss analysis.
     * Solely calculated from Site Manager Site Cash Handling transactions (SiteCash).
     *
     * @return array<string, mixed>
     */
    public function getReportData(?int $siteId = null, ?string $startDate = null, ?string $endDate = null, ?string $search = null): array
    {
        $sites = $this->getProjectSites();

        if ($siteId !== null && $siteId > 0) {
            $sites = $sites->where('id', $siteId)->values();
        }

        if (! empty($search)) {
            $term = strtolower(trim($search));
            $sites = $sites->filter(function ($s) use ($term) {
                $siteName = strtolower((string) $s->name);
                $location = strtolower((string) $s->location);
                $clientName = strtolower((string) ($s->client?->name ?? ''));
                $workType = strtolower((string) $s->work_type);

                return str_contains($siteName, $term)
                    || str_contains($location, $term)
                    || str_contains($clientName, $term)
                    || str_contains($workType, $term);
            })->values();
        }

        // Fetch SiteCash transactions in date range solely from Site Manager Site Cash Handling
        $siteCashQuery = SiteCash::query()->with(['projectSite', 'supplier']);
        if ($startDate !== null && $endDate !== null) {
            $siteCashQuery->whereBetween('payment_date', [$startDate, $endDate]);
        } elseif ($startDate !== null) {
            $siteCashQuery->where('payment_date', '>=', $startDate);
        } elseif ($endDate !== null) {
            $siteCashQuery->where('payment_date', '<=', $endDate);
        }
        $siteCashRecords = $siteCashQuery->get();

        $processedSites = [];
        $totalPortfolio = 0.0;
        $totalRevenue = 0.0;
        $totalLabour = 0.0;
        $totalMaterial = 0.0;
        $totalTools = 0.0;
        $totalSupplier = 0.0;
        $totalSundries = 0.0;

        foreach ($sites as $site) {
            $sId = (int) $site->id;
            $sName = (string) $site->name;
            $estimate = (float) $site->estimated_budget;
            $totalPortfolio += $estimate;

            $siteRevenue = 0.0;
            $siteLabour = 0.0;
            $siteMaterial = 0.0;
            $siteTools = 0.0;
            $siteSupplier = 0.0;
            $siteSundries = 0.0;

            // Process SiteCash for this site
            $siteCashForSite = $siteCashRecords->filter(function ($tx) use ($sId, $sName) {
                return (int) $tx->project_site_id === $sId || ($tx->projectSite && $tx->projectSite->name === $sName);
            });

            foreach ($siteCashForSite as $sc) {
                $inVal = (float) $sc->in_amount;
                $outVal = (float) $sc->out_amount;
                $rawAmount = (float) $sc->amount;
                $txType = trim((string) $sc->transaction_type);
                $txTypeLower = strtolower($txType);
                $descLower = strtolower((string) $sc->description);

                // Inflow: Revenue (Owner Advance, Client Advance, Client Collection, Advance Received)
                $inflowTypes = ['owner advance', 'advance received', 'client advance', 'client collection'];
                $isInflow = in_array($txTypeLower, $inflowTypes, true) || $sc->flow_type === 'inflow' || $inVal > 0;

                if ($isInflow) {
                    $siteRevenue += ($inVal > 0 ? $inVal : $rawAmount);
                }

                // Outflow Expense: Exclude balance returned to office / AM
                $returnTypes = ['balance returned', 'balance returned to am', 'balance returned to account manager'];
                $isReturn = in_array($txTypeLower, $returnTypes, true);

                $isExpense = ($sc->is_expense || $sc->flow_type === 'outflow' || $outVal > 0) && ! $isReturn;

                if ($isExpense) {
                    $amount = ($outVal > 0 ? $outVal : $rawAmount);

                    if (str_contains($txTypeLower, 'supplier')) {
                        // 1. Explicit Supplier Payment (always Supplier Payout, even if description lists material types like M-Sand, cement, etc.)
                        $siteSupplier += $amount;
                    } elseif (str_contains($txTypeLower, 'tool') || str_contains($txTypeLower, 'rental')) {
                        // 2. Explicit Tool / Machinery Rental
                        $siteTools += $amount;
                    } elseif (str_contains($txTypeLower, 'wage') || str_contains($txTypeLower, 'labour')) {
                        // 3. Explicit Daily Labour Wages
                        $siteLabour += $amount;
                    } elseif (str_contains($txTypeLower, 'material')) {
                        // 4. Explicit Material Purchase
                        $siteMaterial += $amount;
                    } elseif (str_contains($txTypeLower, 'other') || str_contains($txTypeLower, 'expense') || str_contains($txTypeLower, 'sundr')) {
                        // 5. Explicit Other Expense / Sundries
                        $siteSundries += $amount;
                    } elseif (! empty($sc->supplier_id) || ! empty($sc->supplier_name)) {
                        // Fallback: Has linked supplier
                        $siteSupplier += $amount;
                    } elseif (str_contains($descLower, 'wage') || str_contains($descLower, 'labour')) {
                        // Fallback: Description indicates labour wage
                        $siteLabour += $amount;
                    } elseif (str_contains($descLower, 'tool') || str_contains($descLower, 'breaker') || str_contains($descLower, 'rent') || str_contains($descLower, 'machinery')) {
                        // Fallback: Description indicates tool rental
                        $siteTools += $amount;
                    } elseif (str_contains($descLower, 'material') || str_contains($descLower, 'sand') || str_contains($descLower, 'cement') || str_contains($descLower, 'brick') || str_contains($descLower, 'steel') || str_contains($descLower, 'gravel') || str_contains($descLower, 'metal')) {
                        // Fallback: Description indicates material
                        $siteMaterial += $amount;
                    } else {
                        // Fallback: Sundries
                        $siteSundries += $amount;
                    }
                }
            }

            $siteMaterialAndTools = $siteMaterial + $siteTools;
            $siteTotalSpent = $siteLabour + $siteMaterialAndTools + $siteSupplier + $siteSundries;
            $siteNetProfit = $siteRevenue - $siteTotalSpent;
            $marginPct = $siteRevenue > 0
                ? round(($siteNetProfit / $siteRevenue) * 100, 1)
                : ($siteTotalSpent > 0 ? -100.0 : 0.0);

            $budgetProgress = $estimate > 0
                ? min(100, (int) round(($siteTotalSpent / $estimate) * 100))
                : 0;

            $status = $siteNetProfit > 0
                ? 'Profitable'
                : ($siteNetProfit == 0.0 ? 'Break-even' : 'Deficit');

            $processedSites[] = [
                'id' => $site->id,
                'name' => $site->name,
                'client' => $site->client?->name ?? 'Client',
                'client_phone' => $site->client?->phone ?? '',
                'location' => $site->location ?? '',
                'work_type' => $site->work_type ?? 'Construction',
                'start_date' => $site->start_date ? Carbon::parse($site->start_date)->format('d M Y') : '-',
                'end_date' => $site->end_date ? Carbon::parse($site->end_date)->format('d M Y') : '-',
                'estimate' => $estimate,
                'revenue' => $siteRevenue,
                'labour' => $siteLabour,
                'material' => $siteMaterialAndTools,
                'tools' => $siteTools,
                'supplier' => $siteSupplier,
                'sundries' => $siteSundries,
                'total_spent' => $siteTotalSpent,
                'net_profit' => $siteNetProfit,
                'margin_pct' => $marginPct,
                'budget_progress' => $budgetProgress,
                'site_status' => $site->status,
                'pnl_status' => $status,
            ];

            $totalRevenue += $siteRevenue;
            $totalLabour += $siteLabour;
            $totalMaterial += $siteMaterial;
            $totalTools += $siteTools;
            $totalSupplier += $siteSupplier;
            $totalSundries += $siteSundries;
        }

        $grossRevenue = $totalRevenue;
        $totalOperatingExpense = $totalLabour + $totalMaterial + $totalTools + $totalSupplier + $totalSundries;
        $netOperatingProfit = $grossRevenue - $totalOperatingExpense;
        $overallMarginPct = $grossRevenue > 0
            ? round(($netOperatingProfit / $grossRevenue) * 100, 1)
            : ($totalOperatingExpense > 0 ? -100.0 : 0.0);

        // Expense distribution percentages
        $materialAndToolsTotal = $totalMaterial + $totalTools;
        $labourPct = $totalOperatingExpense > 0 ? (int) round(($totalLabour / $totalOperatingExpense) * 100) : 0;
        $materialPct = $totalOperatingExpense > 0 ? (int) round(($materialAndToolsTotal / $totalOperatingExpense) * 100) : 0;
        $supplierPct = $totalOperatingExpense > 0 ? (int) round(($totalSupplier / $totalOperatingExpense) * 100) : 0;
        $sundryPct = $totalOperatingExpense > 0 ? (int) round(($totalSundries / $totalOperatingExpense) * 100) : 0;

        // Financial health assessment
        $health = [
            'status' => $netOperatingProfit > 0 ? 'positive' : ($netOperatingProfit == 0.0 ? 'neutral' : 'negative'),
            'title' => $netOperatingProfit > 0
                ? 'Financial Health: Strong Positive (ஆரோக்கியமான லாபம்)'
                : ($netOperatingProfit == 0.0 ? 'Financial Health: Break-Even (சமநிலை)' : 'Financial Health: Operating Deficit (செலவு அதிகம்)'),
            'desc' => $netOperatingProfit > 0
                ? 'Total client revenue collections exceed operating expenditures. Operating cashflow is healthy across active sites.'
                : ($netOperatingProfit == 0.0
                    ? 'Revenues and operating expenditures are evenly balanced. Ensure upcoming client stage invoices are generated promptly.'
                    : 'Operating expenditures currently exceed receipts for this selected period. Prioritize client milestone advance collections.'),
            'icon' => $netOperatingProfit > 0
                ? 'bi-shield-check text-success'
                : ($netOperatingProfit == 0.0 ? 'bi-info-circle text-warning' : 'bi-exclamation-triangle text-danger'),
        ];

        return [
            'sites' => $processedSites,
            'kpis' => [
                'contract_portfolio' => $totalPortfolio,
                'total_revenue' => $grossRevenue,
                'total_expense' => $totalOperatingExpense,
                'net_profit' => $netOperatingProfit,
                'net_margin' => $overallMarginPct,
                'sites_count' => count($processedSites),
            ],
            'statement' => [
                'client_advances' => $totalRevenue,
                'other_inflows' => 0.0,
                'gross_revenue' => $grossRevenue,
                'worker_wages' => $totalLabour,
                'materials_consumables' => $totalMaterial,
                'tools_rentals' => $totalTools,
                'supplier_invoices' => $totalSupplier,
                'site_sundries' => $totalSundries,
                'total_expenses' => $totalOperatingExpense,
                'net_profit' => $netOperatingProfit,
            ],
            'distribution' => [
                'labour' => ['amount' => $totalLabour, 'pct' => $labourPct],
                'material' => ['amount' => $materialAndToolsTotal, 'pct' => $materialPct],
                'supplier' => ['amount' => $totalSupplier, 'pct' => $supplierPct],
                'sundry' => ['amount' => $totalSundries, 'pct' => $sundryPct],
            ],
            'health' => $health,
        ];
    }
}
