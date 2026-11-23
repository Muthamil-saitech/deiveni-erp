<?php

namespace App\Http\Controllers;

use App\Models\ProjectSite;
use App\Services\Contracts\DailyExpensesReportServiceInterface;
use App\Services\Contracts\ProjectSiteServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyExpensesReportController extends Controller
{
    public function __construct(
        public DailyExpensesReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display the Daily Expenses Report page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user ? $user->isAdmin() : true;

        $sites = ProjectSite::query()->orderBy('name')->get();
        $reportData = $this->reportService->getReportData($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        return view('reports.site_daily_expenses', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'expenses' => $reportData['expenses'],
            'kpis' => $reportData['kpis'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered daily expenses data as an Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $expenses = $this->reportService->getExportData($request->all());
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Daily_Expenses_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($expenses, $kpis, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company & Report Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Daily Expenses Report (அன்றைய கூடுதல் செலவுகள் அறிக்கை)']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'all')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            if (! empty($activeFilters['search'])) {
                fputcsv($handle, ['Search Query', $activeFilters['search']]);
            }
            fputcsv($handle, []);

            // Summary KPIs
            fputcsv($handle, ['--- SUMMARY METRICS ---']);
            fputcsv($handle, ['Total Expenses Amount (INR)', number_format($kpis['total_amount'], 2)]);
            fputcsv($handle, ['Total Entries Count', $kpis['total_count']]);
            fputcsv($handle, ['Distinct Project Sites', $kpis['sites_count']]);
            fputcsv($handle, ['Average Expense Per Entry (INR)', number_format($kpis['avg_amount'], 2)]);
            fputcsv($handle, []);

            // Data Table Headers
            fputcsv($handle, [
                '#',
                'Date',
                'Project Site',
                'Expense Item / Description',
                'Quantity',
                'Unit Price (INR)',
                'Total Amount (INR)',
                'Logged By',
                'Recorded At',
            ]);

            $index = 1;
            foreach ($expenses as $item) {
                $alloc = $item->dailyAllocation;
                $dateFormatted = $alloc && $alloc->date ? Carbon::parse($alloc->date)->format('d-m-Y') : '-';
                $siteName = $alloc && $alloc->projectSite ? $alloc->projectSite->name : '-';
                $creatorName = $alloc && $alloc->creator ? $alloc->creator->name : 'System';
                $recordedAt = $item->created_at ? $item->created_at->format('d-m-Y H:i') : '-';

                fputcsv($handle, [
                    $index++,
                    $dateFormatted,
                    $siteName,
                    $item->item_name,
                    number_format((float) $item->quantity, 2, '.', ''),
                    number_format((float) $item->unit_price, 2, '.', ''),
                    number_format((float) $item->amount, 2, '.', ''),
                    $creatorName,
                    $recordedAt,
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                '',
                '',
                '',
                'Total:',
                '',
                '',
                number_format($kpis['total_amount'], 2, '.', ''),
                '',
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
