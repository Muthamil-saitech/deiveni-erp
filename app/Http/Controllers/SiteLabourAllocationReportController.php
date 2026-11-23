<?php

namespace App\Http\Controllers;

use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\SiteLabourAllocationReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteLabourAllocationReportController extends Controller
{
    public function __construct(
        public SiteLabourAllocationReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display the Daily Labour Allocation & Wage Sheet report.
     */
    public function index(Request $request): View|JsonResponse
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

        // Also fetch all allocations and weekly wages to enable instant client-side date & search filtering
        $allData = $this->reportService->getReportData(['period' => 'all']);

        return view('reports.site_labour_allocations', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'allocations' => $allData['allocations'],
            'weeklyWages' => $allData['weekly_wages'],
            'kpis' => $reportData['kpis'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered labour allocations and wage data as Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $allocations = $reportData['allocations'];
        $weeklyWages = $reportData['weekly_wages'];
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Labour_Allocation_Wage_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($allocations, $weeklyWages, $kpis, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Report Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Daily Labour Allocation & Wage Sheet Report']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'this_week')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            fputcsv($handle, []);

            // KPI Summary
            fputcsv($handle, ['--- KEY PERFORMANCE SUMMARY ---']);
            fputcsv($handle, ['Total Man-Days Logged', $kpis['total_man_days']]);
            fputcsv($handle, ['Total Labour Wages (INR)', number_format($kpis['total_labour_wage'], 2)]);
            fputcsv($handle, ['Daily Petty Expenses (INR)', number_format($kpis['total_daily_expenses'], 2)]);
            fputcsv($handle, ['Weekly Additional Expenses (INR)', number_format($kpis['total_weekly_expenses'], 2)]);
            fputcsv($handle, ['Total Site Expenses (INR)', number_format($kpis['total_expenses'], 2)]);
            fputcsv($handle, ['Grand Total Day Cost / Payout (INR)', number_format($kpis['grand_total'], 2)]);
            fputcsv($handle, []);

            // Section 1: Daily Labour Allocation Register
            fputcsv($handle, ['--- 1. DAILY LABOUR ALLOCATION & WAGE REGISTER ---']);
            fputcsv($handle, [
                'Date',
                'Project Site',
                'Trade Summary',
                'Workers Count',
                'Labour Wage (INR)',
                'Daily Petty Expenses (INR)',
                'Day Total (INR)',
            ]);

            foreach ($allocations as $row) {
                fputcsv($handle, [
                    $row['date'],
                    $row['site_name'],
                    $row['trade_summary'],
                    $row['total_labour_count'],
                    number_format($row['total_labour_wage'], 2, '.', ''),
                    number_format($row['total_expenses'], 2, '.', ''),
                    number_format($row['grand_total'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, [
                'TOTAL',
                '',
                '',
                $kpis['total_man_days'],
                number_format($kpis['total_labour_wage'], 2, '.', ''),
                number_format($kpis['total_daily_expenses'], 2, '.', ''),
                number_format($kpis['total_labour_wage'] + $kpis['total_daily_expenses'], 2, '.', ''),
            ]);
            fputcsv($handle, []);

            // Section 2: Weekly Wage Sheets & Settlements
            fputcsv($handle, ['--- 2. WEEKLY WAGES & SETTLEMENTS ---']);
            fputcsv($handle, [
                'Week Cycle',
                'Project Site',
                'Total Man-Days',
                'Total Labour Wages (INR)',
                'Daily Expenses Total (INR)',
                'Weekly Additional Expenses (INR)',
                'Net Payout (INR)',
            ]);

            foreach ($weeklyWages as $ww) {
                fputcsv($handle, [
                    $ww['week_label'],
                    $ww['site_name'],
                    $ww['total_labour_man_days'],
                    number_format($ww['total_labour_wages'], 2, '.', ''),
                    number_format($ww['total_daily_expenses'], 2, '.', ''),
                    number_format($ww['total_weekly_expenses'], 2, '.', ''),
                    number_format($ww['net_payout'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, [
                'TOTAL',
                '',
                $kpis['total_man_days'],
                number_format($kpis['total_labour_wage'], 2, '.', ''),
                number_format($kpis['total_daily_expenses'], 2, '.', ''),
                number_format($kpis['total_weekly_expenses'], 2, '.', ''),
                number_format($kpis['grand_total'], 2, '.', ''),
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
