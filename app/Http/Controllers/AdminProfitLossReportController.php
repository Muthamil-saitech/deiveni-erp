<?php

namespace App\Http\Controllers;

use App\Services\Contracts\AdminProfitLossReportServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminProfitLossReportController extends Controller
{
    public function __construct(
        public AdminProfitLossReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Admin Report - Executive Profit & Loss Report (Admin Only).
     */
    public function adminProfitLoss(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user ? $user->isAdmin() : true;

        $sites = $this->projectSiteService->getAllSites();
        $reportData = $this->reportService->getReportData($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        // Fetch complete dataset across all periods to enable instant zero-latency client-side filtering
        $allData = $this->reportService->getReportData(['period' => 'all']);

        return view('reports.admin_profit_loss', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'reportSites' => $reportData['sites'],
            'allSitesData' => $allData['sites'],
            'kpis' => $reportData['kpis'],
            'statement' => $reportData['statement'],
            'distribution' => $reportData['distribution'],
            'health' => $reportData['health'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered profit and loss report data as an Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $sites = $reportData['sites'];
        $kpis = $reportData['kpis'];
        $statement = $reportData['statement'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Executive_Profit_Loss_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($sites, $kpis, $statement, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company Title & Report Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Executive Profit & Loss Report (நிர்வாக லாப / நஷ்ட அறிக்கை)']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'this_week')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            if (! empty($activeFilters['search'])) {
                fputcsv($handle, ['Search Filter', $activeFilters['search']]);
            }
            fputcsv($handle, []);

            // Executive KPI Summary
            fputcsv($handle, ['--- EXECUTIVE FINANCIAL SUMMARY ---']);
            fputcsv($handle, ['Total Contract Portfolio (INR)', number_format($kpis['contract_portfolio'], 2)]);
            fputcsv($handle, ['Total Revenue Inflow (INR)', number_format($kpis['total_revenue'], 2)]);
            fputcsv($handle, ['Total Operating Cost (INR)', number_format($kpis['total_expense'], 2)]);
            fputcsv($handle, ['Net Operating Profit / (Loss) (INR)', number_format($kpis['net_profit'], 2)]);
            fputcsv($handle, ['Net Operating Margin %', $kpis['net_margin'].'%']);
            fputcsv($handle, ['Active Matching Sites Count', $kpis['sites_count']]);
            fputcsv($handle, []);

            // Detailed Site-by-Site Profit & Loss Register
            fputcsv($handle, ['--- SITE-WISE PROFIT & LOSS REGISTER ---']);
            fputcsv($handle, [
                'Project Site Name',
                'Client Name',
                'Location',
                'Work Type',
                'Contract Estimate (INR)',
                'Revenue Collected (INR)',
                'Labour Wages (INR)',
                'Materials & Tools (INR)',
                'Supplier Payouts (INR)',
                'Site Sundries (INR)',
                'Total Spent (INR)',
                'Net Profit / (Loss) (INR)',
                'Net Margin %',
                'Budget Progress %',
                'P&L Status',
            ]);

            $totEstimate = 0.0;
            $totRev = 0.0;
            $totLabour = 0.0;
            $totMat = 0.0;
            $totSup = 0.0;
            $totSun = 0.0;
            $totSpent = 0.0;
            $totProfit = 0.0;

            foreach ($sites as $s) {
                $totEstimate += (float) $s['estimate'];
                $totRev += (float) $s['revenue'];
                $totLabour += (float) $s['labour'];
                $totMat += (float) $s['material'];
                $totSup += (float) $s['supplier'];
                $totSun += (float) $s['sundries'];
                $totSpent += (float) $s['total_spent'];
                $totProfit += (float) $s['net_profit'];

                fputcsv($handle, [
                    $s['name'],
                    $s['client'],
                    $s['location'],
                    $s['work_type'],
                    number_format((float) $s['estimate'], 2, '.', ''),
                    number_format((float) $s['revenue'], 2, '.', ''),
                    number_format((float) $s['labour'], 2, '.', ''),
                    number_format((float) $s['material'], 2, '.', ''),
                    number_format((float) $s['supplier'], 2, '.', ''),
                    number_format((float) $s['sundries'], 2, '.', ''),
                    number_format((float) $s['total_spent'], 2, '.', ''),
                    number_format((float) $s['net_profit'], 2, '.', ''),
                    $s['margin_pct'].'%',
                    $s['budget_progress'].'%',
                    $s['pnl_status'],
                ]);
            }

            // Totals Row
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTALS (மொத்தம்)',
                '',
                '',
                '',
                number_format($totEstimate, 2, '.', ''),
                number_format($totRev, 2, '.', ''),
                number_format($totLabour, 2, '.', ''),
                number_format($totMat, 2, '.', ''),
                number_format($totSup, 2, '.', ''),
                number_format($totSun, 2, '.', ''),
                number_format($totSpent, 2, '.', ''),
                number_format($totProfit, 2, '.', ''),
                ($totRev > 0 ? round(($totProfit / $totRev) * 100, 1) : 0.0).'%',
                '',
                '',
            ]);
            fputcsv($handle, []);

            // Formal Income & Expense Statement
            fputcsv($handle, ['--- EXECUTIVE INCOME & EXPENSE STATEMENT ---']);
            fputcsv($handle, ['A. OPERATING REVENUE / INCOME (வரவு விவரம்)']);
            fputcsv($handle, ['  Client Advance Payments', number_format($statement['client_advances'], 2, '.', '')]);
            fputcsv($handle, ['  Stage Billing & Other Inflows', number_format($statement['other_inflows'], 2, '.', '')]);
            fputcsv($handle, ['  GROSS REVENUE', number_format($statement['gross_revenue'], 2, '.', '')]);
            fputcsv($handle, []);
            fputcsv($handle, ['B. COST OF CONSTRUCTION & OPERATING EXPENSES (செலவுகள் விவரம்)']);
            fputcsv($handle, ['  Daily Worker Wages Disbursed', number_format($statement['worker_wages'], 2, '.', '')]);
            fputcsv($handle, ['  Material Purchases & Consumables', number_format($statement['materials_consumables'], 2, '.', '')]);
            fputcsv($handle, ['  Power Tools & Machinery Rentals', number_format($statement['tools_rentals'], 2, '.', '')]);
            fputcsv($handle, ['  Supplier Invoices Settled', number_format($statement['supplier_invoices'], 2, '.', '')]);
            fputcsv($handle, ['  Site Handling & Sundry Expenses', number_format($statement['site_sundries'], 2, '.', '')]);
            fputcsv($handle, ['  TOTAL CONSTRUCTION & OPERATING EXPENSES', number_format($statement['total_expenses'], 2, '.', '')]);
            fputcsv($handle, []);
            fputcsv($handle, ['C. NET OPERATING PROFIT / (LOSS)', number_format($statement['net_profit'], 2, '.', '')]);

            fclose($handle);
        }, 200, $headers);
    }
}
