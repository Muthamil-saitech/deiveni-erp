<?php

namespace App\Services;

use App\Repositories\Contracts\SiteCashHandlingReportRepositoryInterface;
use App\Services\Contracts\SiteCashHandlingReportServiceInterface;
use Carbon\Carbon;

class SiteCashHandlingReportService implements SiteCashHandlingReportServiceInterface
{
    public function __construct(
        public SiteCashHandlingReportRepositoryInterface $reportRepo
    ) {}

    /**
     * Get computed filter date ranges and metadata.
     *
     * @return array<string, mixed>
     */
    public function getFilterMeta(): array
    {
        $today = Carbon::today();
        $thisWeekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        $lastWeekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $lastWeekEnd = Carbon::now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $thisMonthStart = Carbon::now()->startOfMonth();
        $thisMonthEnd = Carbon::now()->endOfMonth();

        return [
            'today' => [
                'start' => $today->toDateString(),
                'end' => $today->toDateString(),
                'label' => $today->format('d M Y'),
            ],
            'this_week' => [
                'start' => $thisWeekStart->toDateString(),
                'end' => $thisWeekEnd->toDateString(),
                'label' => $thisWeekStart->format('d M').' - '.$thisWeekEnd->format('d M Y'),
            ],
            'last_week' => [
                'start' => $lastWeekStart->toDateString(),
                'end' => $lastWeekEnd->toDateString(),
                'label' => $lastWeekStart->format('d M').' - '.$lastWeekEnd->format('d M Y'),
            ],
            'this_month' => [
                'start' => $thisMonthStart->toDateString(),
                'end' => $thisMonthEnd->toDateString(),
                'label' => Carbon::now()->format('F Y'),
            ],
            'current_date' => $today->format('d M Y'),
        ];
    }

    /**
     * Get report data containing transactions, KPIs, and filter metadata.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getReportData(array $filters = []): array
    {
        $meta = $this->getFilterMeta();
        $period = $filters['period'] ?? 'this_week';
        $siteId = ! empty($filters['site_id']) && $filters['site_id'] !== 'all' ? (int) $filters['site_id'] : null;
        $flowType = ! empty($filters['flow_type']) && $filters['flow_type'] !== 'all' ? (string) $filters['flow_type'] : null;
        $search = ! empty($filters['search']) ? trim((string) $filters['search']) : null;

        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = $meta['today']['start'];
                $endDate = $meta['today']['end'];
                break;
            case 'this_week':
                $startDate = $meta['this_week']['start'];
                $endDate = $meta['this_week']['end'];
                break;
            case 'last_week':
                $startDate = $meta['last_week']['start'];
                $endDate = $meta['last_week']['end'];
                break;
            case 'this_month':
                $startDate = $meta['this_month']['start'];
                $endDate = $meta['this_month']['end'];
                break;
            case 'custom':
                $startDate = ! empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateString() : null;
                $endDate = ! empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->toDateString() : null;
                break;
            case 'all':
            default:
                // No date restriction
                break;
        }

        $transactionModels = $this->reportRepo->getTransactions($siteId, $startDate, $endDate, $flowType, $search);

        $transactions = [];
        $adv = 0.0;
        $col = 0.0;
        $pur = 0.0;
        $wage = 0.0;
        $ret = 0.0;
        $totalIn = 0.0;
        $totalOut = 0.0;

        foreach ($transactionModels as $tx) {
            $inVal = (float) $tx->in_amount;
            $outVal = (float) $tx->out_amount;
            $type = $tx->transaction_type;

            $isInflow = ($tx->flow_type === 'inflow' || in_array($type, ['Advance Received', 'Owner Advance', 'Client Advance', 'Client Collection'], true));

            if ($inVal == 0.0 && $outVal == 0.0) {
                if ($isInflow) {
                    $inVal = (float) $tx->amount;
                } else {
                    $outVal = (float) $tx->amount;
                }
            }

            if (in_array($type, ['Advance Received', 'Owner Advance', 'Client Advance'], true)) {
                $adv += $inVal;
            } elseif ($type === 'Client Collection') {
                $col += $inVal;
            } elseif (in_array($type, ['Material Purchase', 'Supplier Payment', 'Tool Rental', 'Other Expense'], true)) {
                $pur += $outVal;
            } elseif ($type === 'Daily Wage Payment') {
                $wage += $outVal;
            } elseif (in_array($type, ['Balance Returned', 'Balance Returned to AM', 'Balance Returned to Account Manager'], true)) {
                $ret += $outVal;
            } else {
                if ($isInflow) {
                    $adv += $inVal;
                } else {
                    $pur += $outVal;
                }
            }

            $totalIn += $inVal;
            $totalOut += $outVal;

            $payeeOrSupplier = $tx->supplier_name ?: ($tx->supplier?->name ?? '');
            $billNo = $tx->bill_no ?: ($tx->purchase_order_no ?? '');

            $transactions[] = [
                'id' => $tx->encrypted_id,
                'project_site_id' => $tx->project_site_id,
                'site' => $tx->projectSite?->name ?? 'Unknown Site',
                'type' => $tx->transaction_type,
                'flow_type' => $tx->flow_type,
                'amount' => (float) $tx->amount,
                'inVal' => $inVal,
                'outVal' => $outVal,
                'desc' => $tx->description ?? '',
                'supplier' => $payeeOrSupplier,
                'person' => $payeeOrSupplier,
                'paid_to' => $tx->paid_to ?? 'Site Manager',
                'receiver_name' => $tx->receiver_name ?? '',
                'bank_name' => $tx->bank_name ?? '',
                'upi_id' => $tx->upi_id ?? '',
                'account_holder_name' => $tx->account_holder_name ?? '',
                'payment_mode' => $tx->payment_mode ?? 'Cash',
                'payment_reference' => $tx->payment_reference ?? '',
                'bill' => $billNo,
                'date' => Carbon::parse($tx->payment_date)->format('d M Y'),
                'raw_date' => Carbon::parse($tx->payment_date)->toDateString(),
                'time' => $tx->payment_time ?? '',
                'image' => $tx->receipt_image,
                'items' => is_array($tx->items) ? $tx->items : [],
                'created_by' => $tx->creator?->name ?? '',
            ];
        }

        $netCash = $totalIn - $totalOut;

        $kpis = [
            'adv' => $adv,
            'col' => $col,
            'pur' => $pur,
            'wage' => $wage,
            'ret' => $ret,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net_cash' => $netCash,
            'count' => count($transactions),
        ];

        return [
            'transactions' => $transactions,
            'kpis' => $kpis,
            'filter_meta' => $meta,
            'active_filters' => [
                'period' => $period,
                'site_id' => $siteId,
                'flow_type' => $flowType,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }
}
