<?php

namespace App\Services;

use App\Models\SiteCash;
use App\Repositories\Contracts\AccountLedgerReportRepositoryInterface;
use App\Services\Contracts\AccountLedgerReportServiceInterface;
use Carbon\Carbon;

class AccountLedgerReportService implements AccountLedgerReportServiceInterface
{
    public function __construct(
        public AccountLedgerReportRepositoryInterface $reportRepo
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
        $cashFlow = ! empty($filters['cash_flow']) && $filters['cash_flow'] !== 'all' ? (string) $filters['cash_flow'] : null;
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

        $ledgerModels = $this->reportRepo->getLedgers($siteId, $startDate, $endDate, $flowType, $cashFlow, $search);

        // Pre-fetch all site cashes mapped by ledger_id to know return status
        $siteCashesByLedger = SiteCash::with('returnSiteCash')
            ->whereNotNull('ledger_id')
            ->get()
            ->keyBy('ledger_id');

        $transactions = [];
        $totalIn = 0.0;
        $totalOut = 0.0;

        foreach ($ledgerModels as $tx) {
            $amount = (float) $tx->amount;
            $inVal = ($tx->flow === 'income') ? $amount : 0.0;
            $outVal = ($tx->flow === 'expense') ? $amount : 0.0;

            $totalIn += $inVal;
            $totalOut += $outVal;

            // Resolve project site name
            $siteName = $tx->site_name ?: ($tx->projectSite?->name ?? '-');

            // Resolve party/supplier
            $party = '-';
            if (! empty($tx->supplier_name)) {
                $party = $tx->supplier_name;
            } elseif ($tx->supplier) {
                $party = $tx->supplier->name;
            } elseif (! empty($tx->client_name)) {
                $party = $tx->client_name;
            } elseif ($tx->projectSite?->client) {
                $party = $tx->projectSite->client->name;
            } elseif (! empty($tx->bank_name)) {
                $party = $tx->bank_name.($tx->branch_name ? " ({$tx->branch_name})" : '');
            } elseif (! empty($tx->property_name)) {
                $party = $tx->property_name.($tx->unit_name ? " [{$tx->unit_name}]" : '');
            }

            // Resolve description/particulars
            $desc = $tx->description;
            if (empty($desc)) {
                $desc = $tx->office_expense_details ?: ($tx->transaction_type_name ?: ($tx->source ?? 'Ledger entry'));
            }

            $entryDate = Carbon::parse($tx->entry_date);

            // Check office cash and return status
            $isOfficeCash = $tx->wage_cash_source === 'office_cash' || str_contains((string) $desc, 'Office Cash');
            $sc = $siteCashesByLedger->get($tx->id);
            $returnStatus = null;
            $returnDate = null;
            $returnRef = null;

            if ($isOfficeCash && $tx->flow === 'expense') {
                if ($sc && $sc->return_site_cash_id) {
                    $returnStatus = 'received';
                    $returnDate = $sc->returnSiteCash ? Carbon::parse($sc->returnSiteCash->payment_date)->format('d M Y') : null;
                    $returnRef = $sc->returnSiteCash?->bill_no ?: 'RET-'.$sc->return_site_cash_id;
                } else {
                    $returnStatus = 'not_received';
                }
            } elseif ($tx->transaction_type_name === 'Balance Returned' || str_contains((string) $desc, 'Balance returned')) {
                $returnStatus = 'received';
            }

            $transactions[] = [
                'id' => $tx->id,
                'project_site_id' => $tx->project_site_id,
                'site' => $siteName,
                'source' => $tx->source,
                'flow' => $tx->flow,
                'type' => $tx->source,
                'flow_type' => $tx->source,
                'amount' => $amount,
                'inVal' => $inVal,
                'outVal' => $outVal,
                'desc' => $desc,
                'party' => $party,
                'supplier' => $party,
                'person' => $party,
                'date' => $entryDate->format('d M Y'),
                'raw_date' => $entryDate->toDateString(),
                'time' => $tx->created_at ? $tx->created_at->format('h:i A') : '',
                'image' => $tx->receipt_image,
                'created_by' => $tx->creator?->name ?? '',
                'status' => 'Reconciled',
                'wage_cash_source' => $tx->wage_cash_source,
                'is_office_cash' => $isOfficeCash,
                'return_status' => $returnStatus,
                'return_date' => $returnDate,
                'return_ref' => $returnRef,
            ];
        }

        $netBalance = $totalIn - $totalOut;

        $kpis = [
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'net_balance' => $netBalance,
            'count' => count($transactions),
        ];

        // Compile Office ↔ Site Reconciliation Data
        $officeCashReconciliation = $this->getOfficeCashReconciliation($siteId, $startDate, $endDate);

        return [
            'transactions' => $transactions,
            'kpis' => $kpis,
            'filter_meta' => $meta,
            'office_cash_reconciliation' => $officeCashReconciliation,
            'active_filters' => [
                'period' => $period,
                'site_id' => $siteId,
                'flow_type' => $flowType,
                'cash_flow' => $cashFlow,
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }

    /**
     * Compile Office Cash vs Site Returns reconciliation data.
     *
     * @return array<string, mixed>
     */
    public function getOfficeCashReconciliation(?int $siteId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        // 1. Fetch all Office Cash dispatches (daily wage payments from office cash)
        $query = SiteCash::with(['projectSite.client', 'returnSiteCash'])
            ->where('wage_cash_source', 'office_cash')
            ->where('flow_type', 'outflow')
            ->where('transaction_type', 'Daily Wage Payment');

        if ($siteId) {
            $query->where('project_site_id', $siteId);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        $dispatches = $query->orderByDesc('payment_date')->orderByDesc('id')->get();

        $items = [];
        $totalSent = 0.0;
        $totalReceived = 0.0;
        $countSent = 0;
        $countReceived = 0;
        $countPending = 0;

        foreach ($dispatches as $sc) {
            $amt = (float) $sc->amount;
            $totalSent += $amt;
            $countSent++;

            $isReceived = ($sc->return_site_cash_id !== null);
            if ($isReceived) {
                $totalReceived += $amt;
                $countReceived++;
            } else {
                $countPending++;
            }

            $site = $sc->projectSite;
            $ret = $sc->returnSiteCash;
            $returnDate = $ret ? Carbon::parse($ret->payment_date)->format('d M Y') : null;
            $returnRawDate = $ret ? ($ret->payment_date instanceof \DateTimeInterface ? $ret->payment_date->format('Y-m-d') : (string) $ret->payment_date) : null;
            $paymentDateStr = $sc->payment_date instanceof \DateTimeInterface ? $sc->payment_date->format('Y-m-d') : (string) $sc->payment_date;

            $items[] = [
                'id' => $sc->id,
                'ledger_id' => $sc->ledger_id,
                'project_site_id' => $sc->project_site_id,
                'site_name' => $site?->name ?? 'Site',
                'client_name' => $site?->client?->name ?? '-',
                'date' => Carbon::parse($sc->payment_date)->format('d M Y'),
                'raw_date' => $paymentDateStr,
                'time' => $sc->payment_time ?: '',
                'amount_sent' => $amt,
                'is_received' => $isReceived,
                'status' => $isReceived ? 'received' : 'not_received',
                'status_label' => $isReceived ? 'Received (பெறப்பட்டது)' : 'Not Received (வர வேண்டியது)',
                'return_amount' => $isReceived ? $amt : 0.0,
                'return_date' => $returnDate,
                'return_raw_date' => $returnRawDate,
                'return_time' => $ret?->payment_time,
                'return_ref' => $ret?->bill_no ?: ($sc->return_site_cash_id ? 'RET-'.$sc->return_site_cash_id : null),
                'return_description' => $ret?->description,
                'description' => $sc->description,
                'extra_workers' => $sc->extra_workers,
            ];
        }

        // 2. Fetch all Site Manager Balance Return transactions
        $retQuery = SiteCash::with('projectSite')
            ->where('transaction_type', 'Balance Returned')
            ->where('paid_to', 'Account Manager');

        if ($siteId) {
            $retQuery->where('project_site_id', $siteId);
        }
        if ($startDate && $endDate) {
            $retQuery->whereBetween('payment_date', [$startDate, $endDate]);
        }

        $returns = $retQuery->orderByDesc('payment_date')->orderByDesc('id')->get();
        $returnItems = [];

        foreach ($returns as $r) {
            $rAmt = (float) $r->amount;
            $rDate = Carbon::parse($r->payment_date)->format('d M Y');
            $rRawDate = $r->payment_date instanceof \DateTimeInterface ? $r->payment_date->format('Y-m-d') : (string) $r->payment_date;

            $returnItems[] = [
                'id' => $r->id,
                'project_site_id' => $r->project_site_id,
                'site_name' => $r->projectSite?->name ?? 'Site',
                'date' => $rDate,
                'raw_date' => $rRawDate,
                'time' => $r->payment_time ?: '',
                'amount' => $rAmt,
                'paid_to' => $r->paid_to,
                'description' => $r->description,
                'bill_no' => $r->bill_no ?: 'RET-'.$r->id,
                'returned_wage_ids' => $r->returned_wage_ids ?? [],
            ];
        }

        $totalPending = max(0.0, $totalSent - $totalReceived);

        return [
            'summary' => [
                'total_sent' => $totalSent,
                'total_received' => $totalReceived,
                'total_pending' => $totalPending,
                'count_sent' => $countSent,
                'count_received' => $countReceived,
                'count_pending' => $countPending,
            ],
            'items' => $items,
            'returns' => $returnItems,
        ];
    }
}
