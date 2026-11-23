<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\ProjectSite;
use App\Services\Contracts\ProjectSiteServiceInterface;
use App\Services\Contracts\StockTransferReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockTransferReportController extends Controller
{
    public function __construct(
        public StockTransferReportServiceInterface $reportService,
        public ProjectSiteServiceInterface $projectSiteService
    ) {}

    /**
     * Display the Stock Transfer & Inter-Site Material Movement Report page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user ? $user->isAdmin() : true;

        $sites = ProjectSite::query()->orderBy('name')->get();
        $materials = Material::where('status', 'Active')->orderBy('name_en')->get();
        $reportData = $this->reportService->getReportData($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        return view('reports.site_stock_transfers', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'sites' => $sites,
            'materials' => $materials,
            'transfers' => $reportData['transfers'],
            'kpis' => $reportData['kpis'],
            'siteBalances' => $reportData['site_balances'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered stock transfer data as Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $transfers = $reportData['transfers'];
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Stock_Transfer_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transfers, $kpis, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Inter-Site Stock Transfer Report (தளங்களுக்கிடையேயான பொருள் பரிமாற்ற அறிக்கை)']);
            fputcsv($handle, ['Generated On:', date('d M Y, h:i A')]);
            fputcsv($handle, []);

            // Summary KPIs
            fputcsv($handle, ['SUMMARY / முக்கிய புள்ளிவிவரங்கள்']);
            fputcsv($handle, ['Total Outward Transfers (மொத்த பரிமாற்றங்கள்):', $kpis['total_transfers_count']]);
            fputcsv($handle, ['Total Transferred Qty (பரிமாற்ற அளவு):', $kpis['total_transferred_qty']]);
            fputcsv($handle, ['Total Returns (திரும்ப பெற்றவை):', $kpis['total_returns_count']]);
            fputcsv($handle, ['Total Returned Qty (திரும்பிய அளவு):', $kpis['total_returned_qty']]);
            fputcsv($handle, ['Pending Returns Count (பாக்கி பரிமாற்றங்கள்):', $kpis['pending_transfers_count']]);
            fputcsv($handle, ['Total Pending Qty (பாக்கி அளவு):', $kpis['total_pending_qty']]);
            fputcsv($handle, ['Fully Returned (நிறைவு):', $kpis['fully_returned_count']]);
            fputcsv($handle, []);

            // Applied Filters
            if (! empty($activeFilters)) {
                fputcsv($handle, ['APPLIED FILTERS / பயன்படுத்தப்பட்ட வடிகட்டிகள்']);
                foreach ($activeFilters as $key => $val) {
                    fputcsv($handle, [ucfirst($key), $val]);
                }
                fputcsv($handle, []);
            }

            // Data Headers
            fputcsv($handle, [
                '#',
                'Transfer No (எண்)',
                'Date (தேதி)',
                'Type (வகை)',
                'From Site (வழங்கிய தளம்)',
                'To Site (பெற்ற தளம்)',
                'Material (பொருள்)',
                'Quantity (அளவு)',
                'Unit (அளவீடு)',
                'Returned Qty (திரும்பிய அளவு)',
                'Pending Qty (பாக்கி அளவு)',
                'Return Status (நிலை)',
                'Vehicle No (வாகனம்)',
                'Driver (ஓட்டுநர்)',
                'Challan No (சலான்)',
                'Created By (பதிவு செய்தவர்)',
                'Remarks (குறிப்புகள்)',
            ]);

            $index = 1;
            foreach ($transfers as $tr) {
                $matName = $tr->material ? ($tr->material->name_en.($tr->material->name_ta ? ' ('.$tr->material->name_ta.')' : '')) : '-';
                fputcsv($handle, [
                    $index++,
                    $tr->transfer_no,
                    $tr->transfer_date?->format('d-m-Y') ?? '-',
                    $tr->transfer_type === 'RETURN' ? 'Return (திருப்பி கொடுத்தது)' : 'Transfer (வழங்கியது)',
                    $tr->fromSite?->site_name ?? '-',
                    $tr->toSite?->site_name ?? '-',
                    $matName,
                    (float) $tr->quantity,
                    $tr->unit ?? '-',
                    (float) $tr->returned_quantity,
                    $tr->transfer_type === 'TRANSFER' ? $tr->pending_return_quantity : 0,
                    $tr->return_status ?? '-',
                    $tr->vehicle_no ?? '-',
                    $tr->driver_name ?? '-',
                    $tr->challan_no ?? '-',
                    $tr->creator?->name ?? 'System',
                    $tr->remarks ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
