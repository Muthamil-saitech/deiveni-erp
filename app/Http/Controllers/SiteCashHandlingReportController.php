<?php

namespace App\Http\Controllers;

use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\SiteCashHandlingReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteCashHandlingReportController extends Controller
{
    public function __construct(
        public SiteCashHandlingReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display the Site Cash Handling Report page.
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

        // Also fetch all transactions to enable instant client-side date & search filtering
        $allData = $this->reportService->getReportData(['period' => 'all']);

        return view('reports.site_cash_handling', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'transactions' => $allData['transactions'],
            'kpis' => $reportData['kpis'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered site cash handling data as an Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $transactions = $reportData['transactions'];
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Site_Cash_Handling_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions, $kpis, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Site Cash Handling Report (தள பணப் புழக்கம் & செலவுகள் அறிக்கை)']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'this_week')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            if (! empty($activeFilters['flow_type'])) {
                fputcsv($handle, ['Flow Type Filter', $activeFilters['flow_type']]);
            }
            fputcsv($handle, []);

            // KPI Financial Summary
            fputcsv($handle, ['--- FINANCIAL SUMMARY & CASH FORMULA ---']);
            fputcsv($handle, ['Total Cash Inflow (INR)', number_format($kpis['total_in'], 2)]);
            fputcsv($handle, ['Total Cash Outflow (INR)', number_format($kpis['total_out'], 2)]);
            fputcsv($handle, ['Net Cash Balance in Hand (INR)', number_format($kpis['net_cash'], 2)]);
            fputcsv($handle, ['Advances Received (Adv)', number_format($kpis['adv'], 2)]);
            fputcsv($handle, ['Client Collections (Col)', number_format($kpis['col'], 2)]);
            fputcsv($handle, ['Purchases & Supplier Payments (Pur)', number_format($kpis['pur'], 2)]);
            fputcsv($handle, ['Daily Wage Payments (Wage)', number_format($kpis['wage'], 2)]);
            fputcsv($handle, ['Balance Returned (Ret)', number_format($kpis['ret'], 2)]);
            fputcsv($handle, ['Total Entries Count', $kpis['count']]);
            fputcsv($handle, []);

            // Transaction Table
            fputcsv($handle, ['--- DETAILED SITE CASH REGISTER ---']);
            fputcsv($handle, [
                'Date',
                'Time',
                'Project Site',
                'Flow Type',
                'Paid To / Recipient',
                'Particulars / Description',
                'Payee / Supplier',
                'Payment Mode',
                'Reference No',
                'Bill No',
                'Cash In (+INR)',
                'Cash Out (-INR)',
            ]);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx['date'],
                    $tx['time'],
                    $tx['site'],
                    $tx['type'],
                    $tx['paid_to'] ?? 'Site Manager',
                    $tx['desc'],
                    $tx['supplier'],
                    $tx['payment_mode'] ?? 'Cash',
                    $tx['payment_reference'] ?? '',
                    $tx['bill'],
                    number_format((float) $tx['inVal'], 2, '.', ''),
                    number_format((float) $tx['outVal'], 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($kpis['total_in'], 2, '.', ''),
                number_format($kpis['total_out'], 2, '.', ''),
                '',
            ]);
            fputcsv($handle, [
                'NET CASH BALANCE',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($kpis['net_cash'], 2, '.', ''),
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
