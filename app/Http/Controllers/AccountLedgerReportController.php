<?php

namespace App\Http\Controllers;

use App\Models\ProjectSite;
use App\Services\Contracts\AccountLedgerReportServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountLedgerReportController extends Controller
{
    public function __construct(
        public AccountLedgerReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display the Account Manager Financial & Ledger Report page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user ? $user->isAdmin() : true;

        $sites = $user && ! $user->isAdmin() && ! $user->isAccountManager()
            ? $this->projectSiteService->getAllSites()
            : ProjectSite::with('client')->orderBy('name')->get();
        $reportData = $this->reportService->getReportData($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        // Also fetch all entries to enable instant client-side date & search filtering
        $allData = $this->reportService->getReportData(['period' => 'all']);

        return view('reports.account_ledger', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'transactions' => $allData['transactions'],
            'kpis' => $reportData['kpis'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
            'officeCashReconciliation' => $allData['office_cash_reconciliation'],
        ]);
    }

    /**
     * Export the filtered account ledger data as an Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $transactions = $reportData['transactions'];
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Account_Manager_Ledger_Report_'.date('Y-m-d_His').'.csv';

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
            fputcsv($handle, ['Account Manager Financial & Ledger Report (கணக்கு மேலாளர் நிதி & லெட்ஜர் அறிக்கை)']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'this_week')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            if (! empty($activeFilters['flow_type'])) {
                fputcsv($handle, ['Flow Type Filter', $activeFilters['flow_type']]);
            }
            if (! empty($activeFilters['cash_flow'])) {
                fputcsv($handle, ['Cash Flow Filter', ucfirst((string) $activeFilters['cash_flow'])]);
            }
            fputcsv($handle, []);

            // KPI Financial Summary
            fputcsv($handle, ['--- FINANCIAL SUMMARY ---']);
            fputcsv($handle, ['Total Inflow (INR)', number_format($kpis['total_in'], 2)]);
            fputcsv($handle, ['Total Outflow (INR)', number_format($kpis['total_out'], 2)]);
            fputcsv($handle, ['Net Balance / Surplus (INR)', number_format($kpis['net_balance'], 2)]);
            fputcsv($handle, ['Total Entries Count', $kpis['count']]);
            fputcsv($handle, []);

            // Detailed Ledger Table
            fputcsv($handle, ['--- DETAILED ACCOUNT LEDGER REGISTER ---']);
            fputcsv($handle, [
                'Date',
                'Time',
                'Flow Type (Category)',
                'Cash Flow',
                'Project Site',
                'Particulars / Description',
                'Party / Supplier',
                'Inflow (+INR)',
                'Outflow (-INR)',
                'Status',
            ]);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx['date'],
                    $tx['time'],
                    $tx['source'],
                    $tx['flow'] === 'income' ? 'Income (Credit)' : 'Expense (Debit)',
                    $tx['site'],
                    $tx['desc'],
                    $tx['party'],
                    number_format((float) $tx['inVal'], 2, '.', ''),
                    number_format((float) $tx['outVal'], 2, '.', ''),
                    $tx['status'] ?? 'Reconciled',
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
                'NET SURPLUS / BALANCE',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($kpis['net_balance'], 2, '.', ''),
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
