<?php

namespace App\Http\Controllers;

use App\Services\Contracts\SiteWarehouseInventoryReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteWarehouseInventoryReportController extends Controller
{
    public function __construct(
        public SiteWarehouseInventoryReportServiceInterface $reportService
    ) {}

    /**
     * Display the Central Warehouse & Store Inventory Report page.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user ? $user->isAdmin() : true;
        $isSiteManager = $user ? $user->isSiteManager() : false;

        $reportData = $this->reportService->getReportData($request->all());

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        // Fetch all items and movements to enable instant client-side date, category, status & text filtering
        $allData = $this->reportService->getReportData(['period' => 'all']);

        return view('reports.site_warehouse_inventory', [
            'user' => $user,
            'isAdmin' => $isAdmin,
            'isSiteManager' => $isSiteManager,
            'items' => $allData['items'],
            'movements' => $allData['movements'],
            'categories' => $reportData['categories'],
            'kpis' => $reportData['kpis'],
            'filterMeta' => $reportData['filter_meta'],
            'activeFilters' => $reportData['active_filters'],
        ]);
    }

    /**
     * Export the filtered warehouse inventory and stock movements data as an Excel-compatible CSV.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $reportData = $this->reportService->getReportData($request->all());
        $items = $reportData['items'];
        $movements = $reportData['movements'];
        $kpis = $reportData['kpis'];
        $activeFilters = $reportData['active_filters'];

        $filename = 'Warehouse_Inventory_Report_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($items, $movements, $kpis, $activeFilters) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Company Header
            fputcsv($handle, ['DEIVENI CONSTRUCTIONS & DEVELOPERS']);
            fputcsv($handle, ['Central Warehouse & Store Inventory Report (மைய கிடங்கு இருப்பு & உபகரணங்கள் அறிக்கை)']);
            fputcsv($handle, ['Generated Date', date('d M Y, h:i A')]);
            fputcsv($handle, ['Active Filter Period', ucfirst(str_replace('_', ' ', (string) ($activeFilters['period'] ?? 'this_week')))]);
            if (! empty($activeFilters['start_date']) && ! empty($activeFilters['end_date'])) {
                fputcsv($handle, ['Date Range', $activeFilters['start_date'].' to '.$activeFilters['end_date']]);
            }
            if (! empty($activeFilters['category']) && $activeFilters['category'] !== 'all') {
                fputcsv($handle, ['Category Filter', $activeFilters['category']]);
            }
            if (! empty($activeFilters['stock_status']) && $activeFilters['stock_status'] !== 'all') {
                fputcsv($handle, ['Stock Status Filter', $activeFilters['stock_status'] === 'low' ? 'Low Stock Alert' : 'Normal Stock']);
            }
            fputcsv($handle, []);

            // KPI Financial & Operational Summary
            fputcsv($handle, ['--- WAREHOUSE SUMMARY & KPIS ---']);
            fputcsv($handle, ['Total Stock Valuation (INR)', number_format($kpis['total_valuation'], 2)]);
            fputcsv($handle, ['Active Catalog SKU Items', $kpis['sku_count']]);
            fputcsv($handle, ['Low Stock Warning Items', $kpis['low_stock_count']]);
            fputcsv($handle, ['Total Physical Units in Warehouse', number_format($kpis['total_units'], 2)]);
            fputcsv($handle, ['Stock Movements in Period', $kpis['movements_count']]);
            fputcsv($handle, ['Total Units Dispatched to Sites', number_format($kpis['issued_qty'], 2)]);
            fputcsv($handle, ['Total Units Inwarded', number_format($kpis['inward_qty'], 2)]);
            fputcsv($handle, []);

            // SECTION 1: INVENTORY REGISTER
            fputcsv($handle, ['--- 1. CURRENT WAREHOUSE INVENTORY REGISTER ---']);
            fputcsv($handle, [
                'SKU Code',
                'Item Name (English)',
                'Item Name (Tamil)',
                'Category',
                'Current Stock',
                'Unit',
                'Safety Threshold',
                'Stock Status',
                'Unit Rate (INR)',
                'Total Valuation (INR)',
                'Issued to Site / Dispatched',
            ]);

            $totalValuation = 0.0;
            $totalUnits = 0.0;

            foreach ($items as $item) {
                $stock = (float) ($item['stock'] ?? 0);
                $rate = (float) ($item['unitRate'] ?? 0);
                $val = (float) ($item['valuation'] ?? ($stock * $rate));
                $totalValuation += $val;
                $totalUnits += $stock;

                fputcsv($handle, [
                    $item['sku'] ?? '',
                    $item['nameEn'] ?? '',
                    $item['nameTa'] ?? '',
                    $item['category'] ?? '',
                    number_format($stock, 2, '.', ''),
                    $item['unit'] ?? 'Nos',
                    number_format((float) ($item['threshold'] ?? 0), 2, '.', ''),
                    ($item['isLow'] ?? false) ? 'Low Stock Alert' : 'Normal',
                    number_format($rate, 2, '.', ''),
                    number_format($val, 2, '.', ''),
                    $item['issuedTo'] ?? 'In Warehouse',
                ]);
            }

            fputcsv($handle, [
                'TOTALS',
                '',
                '',
                '',
                number_format($totalUnits, 2, '.', ''),
                '',
                '',
                '',
                'Total Valuation (INR)',
                number_format($totalValuation, 2, '.', ''),
                '',
            ]);
            fputcsv($handle, []);

            // SECTION 2: STOCK MOVEMENT REGISTER
            fputcsv($handle, ['--- 2. STOCK MOVEMENT & DISPATCH REGISTER ---']);
            fputcsv($handle, [
                'Movement Date',
                'Reference / Voucher No',
                'SKU Code',
                'Item Name',
                'Category',
                'Movement Type',
                'Quantity',
                'Unit',
                'Destination Site / Location',
                'Handled / Logged By',
                'Notes / Remarks',
            ]);

            $totalMovedQty = 0.0;
            foreach ($movements as $m) {
                $qty = (float) ($m['quantity'] ?? 0);
                $totalMovedQty += $qty;

                fputcsv($handle, [
                    $m['formattedDate'] ?? ($m['date'] ?? ''),
                    $m['refNo'] ?? '',
                    $m['itemSku'] ?? '',
                    $m['itemNameEn'] ?? '',
                    $m['itemCategory'] ?? '',
                    $m['movementType'] ?? '',
                    number_format($qty, 2, '.', ''),
                    $m['unit'] ?? 'Nos',
                    $m['siteName'] ?? '',
                    $m['creatorName'] ?? '',
                    $m['notes'] ?? '',
                ]);
            }

            fputcsv($handle, [
                'TOTAL MOVEMENTS',
                count($movements),
                '',
                '',
                '',
                'Total Units Moved',
                number_format($totalMovedQty, 2, '.', ''),
                '',
                '',
                '',
                '',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
