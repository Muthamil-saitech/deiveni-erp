<?php

namespace App\Http\Controllers;

use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\SitePurchasesInwardReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitePurchasesInwardReportController extends Controller
{
    public function __construct(
        public SitePurchasesInwardReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display the Purchases & Material Inward Report page.
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

        // Fetch all purchases to enable instant client-side date, site & text filtering
        $allData = $this->reportService->getReportData(['period' => 'all']);

        return view('reports.site_purchases_inward', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'purchases' => $allData['purchases'],
            'kpis' => $reportData['kpis'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered purchases and material inward data as an Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $purchases = $reportData['purchases'];
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Purchases_Material_Inward_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($purchases, $kpis, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Purchases & Material Inward Report (பொருட்கள் கொள்முதல் & தள வரவு அறிக்கை)']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'this_week')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            if (! empty($activeFilters['status']) && $activeFilters['status'] !== 'all') {
                fputcsv($handle, ['Delivery Status Filter', $activeFilters['status']]);
            }
            fputcsv($handle, []);

            // KPI Financial Summary
            fputcsv($handle, ['--- PROCUREMENT & INWARD SUMMARY ---']);
            fputcsv($handle, ['Total Purchase Orders', $kpis['po_count']]);
            fputcsv($handle, ['Total Purchase Value (INR)', number_format($kpis['total_value'], 2)]);
            fputcsv($handle, ['Delivered & Inwarded Orders', $kpis['delivered_count']]);
            fputcsv($handle, ['Inward Pending Orders', $kpis['pending_count']]);
            fputcsv($handle, []);

            // Data Table Headers
            fputcsv($handle, [
                'PO Number',
                'Order Date',
                'Project Site',
                'Supplier Name',
                'Material / Equipment',
                'Quantity',
                'Unit',
                'Rate (INR)',
                'Total Amount (INR)',
                'Delivery Status',
            ]);

            // Data Rows (Output each line item cleanly to avoid unstructured multi-item strings)
            $totalCalculatedAmount = 0.0;
            foreach ($purchases as $po) {
                if (! empty($po['items']) && is_array($po['items'])) {
                    foreach ($po['items'] as $item) {
                        $qty = (float) ($item['quantity'] ?? 0);
                        $rate = (float) ($item['unit_price'] ?? 0);
                        $sub = (float) ($item['total_price'] ?? ($qty * $rate));
                        $totalCalculatedAmount += $sub;

                        fputcsv($handle, [
                            $po['poNumber'] ?? '',
                            $po['date'] ?? '',
                            $po['site'] ?? '',
                            $po['supplier'] ?? '',
                            $item['material_name'] ?? ($po['material'] ?? ''),
                            $qty,
                            $item['unit'] ?? ($po['unit'] ?? 'Nos'),
                            number_format($rate, 2, '.', ''),
                            number_format($sub, 2, '.', ''),
                            $po['status'] ?? '',
                        ]);
                    }
                } else {
                    $qty = (float) ($po['qty'] ?? 1);
                    $rate = (float) ($po['rate'] ?? 0);
                    $total = (float) ($po['total'] ?? ($qty * $rate));
                    $totalCalculatedAmount += $total;

                    fputcsv($handle, [
                        $po['poNumber'] ?? '',
                        $po['date'] ?? '',
                        $po['site'] ?? '',
                        $po['supplier'] ?? '',
                        $po['material'] ?? '',
                        $qty,
                        $po['unit'] ?? 'Nos',
                        number_format($rate, 2, '.', ''),
                        number_format($total, 2, '.', ''),
                        $po['status'] ?? '',
                    ]);
                }
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
                'Total Value (INR)',
                number_format($kpis['total_value'] > 0 ? $kpis['total_value'] : $totalCalculatedAmount, 2, '.', ''),
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
