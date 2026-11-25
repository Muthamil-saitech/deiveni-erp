@extends('layouts.app')

@section('title', 'Weekly Settlement Details')
@section('page_title', 'Weekly Settlement Details')

@section('content')
    @php
        $isWeekFullySettled = ($summary['weekly_already_settled'] ?? 0) > 0 && ($summary['balance_amount'] ?? 0) <= 0.01;
    @endphp
    <div class="container-fluid px-0">
        <!-- Header Card -->
        <div class="card-custom p-3 p-md-4 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-calendar-check-fill text-primary me-2"></i>
                            Weekly Settlement Details — {{ $site?->name ?? 'Project Site' }}
                        </h5>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Settled Week</span>
                        @if ($isWeekFullySettled)
                            <span class="badge bg-success text-white">
                                <i class="bi bi-check-all me-1"></i>Fully Paid (Site Cash Settled)
                            </span>
                        @endif
                    </div>
                    <div class="small text-muted mt-1">
                        Period:
                        <strong>{{ \Carbon\Carbon::parse($weeklyWage->week_start_date)->format('d M Y') }}</strong>
                        &ndash;
                        <strong>{{ \Carbon\Carbon::parse($weeklyWage->week_end_date)->format('d M Y') }}</strong>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.close()">
                        <i class="bi bi-x-lg me-1"></i> Close Tab
                    </button>
                </div>
            </div>
        </div>

        <!-- Balance Summary KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card-stat bg-white h-100 border">
                    <span class="text-muted small fw-semibold">Weekly Net Payout</span>
                    <h4 class="fw-bold text-dark mb-0 mt-1">₹{{ number_format($summary['net_payout'], 2) }}</h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat bg-white h-100 border border-success-subtle">
                    <span class="text-muted small fw-semibold">Paid Daily via Site Cash</span>
                    <h4 class="fw-bold text-success mb-0 mt-1">−₹{{ number_format($summary['daily_paid_amount'], 2) }}</h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat bg-white h-100 border">
                    <span class="text-muted small fw-semibold">Already Weekly Settled</span>
                    <h4 class="fw-bold text-info mb-0 mt-1">−₹{{ number_format($summary['weekly_already_settled'], 2) }}</h4>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card-stat bg-white h-100 border border-primary">
                    <span class="text-primary small fw-bold">Balance Payable</span>
                    <h4 class="fw-bold text-primary mb-0 mt-1">₹{{ number_format($summary['balance_amount'], 2) }}</h4>
                </div>
            </div>
        </div>

        <!-- Category-Wise Workers Summary & Site Expenses Breakdown -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-lg-6">
                <div class="card-custom overflow-hidden bg-white h-100">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-people-fill text-primary me-2"></i>Weekly Workers Summary
                        </h6>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                            Total: {{ $summary['total_workers'] }} Workers
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Worker Category</th>
                                    <th class="text-center" style="width: 75px;">Days</th>
                                    <th class="text-center" style="width: 110px;">Workers</th>
                                    <th class="text-end" style="width: 130px;">Total Wage (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary['worker_summary'] as $wRow)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $wRow['label'] }}</span>
                                            @if (!empty($wRow['name_en']) && $wRow['name_en'] !== $wRow['label'])
                                                <span class="small text-muted ms-1">({{ $wRow['name_en'] }})</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $wRow['days_count'] }}</td>
                                        <td class="text-center fw-bold text-primary">{{ $wRow['total_workers'] }}</td>
                                        <td class="text-end fw-semibold">₹{{ number_format($wRow['total_wage'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted small">No worker allocations recorded for this week.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (!empty($summary['worker_summary']))
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="2" class="text-end">Total Labour:</td>
                                        <td class="text-center text-primary">{{ $summary['total_workers'] }} Workers</td>
                                        <td class="text-end">₹{{ number_format($summary['total_labour_wage'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card-custom overflow-hidden bg-white h-100">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="bi bi-receipt-cutoff text-warning me-2"></i>Weekly Site Expenses Breakdown
                        </h6>
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                            Total: ₹{{ number_format($summary['total_site_expenses'], 2) }}
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th style="width: 115px;">Date</th>
                                    <th>Expense Item</th>
                                    <th class="text-center" style="width: 70px;">Qty</th>
                                    <th class="text-end" style="width: 120px;">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summary['expense_summary'] as $eRow)
                                    <tr>
                                        <td class="small text-nowrap">
                                            <span class="fw-semibold">{{ $eRow['date_label'] }}</span>
                                            @if ($eRow['source'] === 'Weekly Expense')
                                                <div class="text-info" style="font-size: 0.7rem;">Weekly Extra</div>
                                            @endif
                                        </td>
                                        <td class="fw-semibold text-dark">{{ $eRow['item_name'] }}</td>
                                        <td class="text-center">{{ rtrim(rtrim(number_format($eRow['quantity'], 2), '0'), '.') }}</td>
                                        <td class="text-end fw-semibold">₹{{ number_format($eRow['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted small">No site expenses recorded for this week.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if (!empty($summary['expense_summary']))
                                <tfoot class="table-light fw-bold">
                                    <tr>
                                        <td colspan="3" class="text-end">Total Site Expenses:</td>
                                        <td class="text-end text-dark">₹{{ number_format($summary['total_site_expenses'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Day-by-Day Allocations & Site Cash Paid Status -->
        <div class="card-custom overflow-hidden bg-white mb-4">
            <div class="p-3 bg-light border-bottom">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-list-check text-primary me-2"></i>Day-by-Day Allocations &amp; Site Cash Payment Status
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-bordered align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 135px;">Date &amp; Day</th>
                            <th>Worker Categories &amp; Breakdown</th>
                            <th class="text-end" style="width: 130px;">Labour Wage</th>
                            <th class="text-end" style="width: 130px;">Site Expenses</th>
                            <th class="text-end" style="width: 130px;">Day Total</th>
                            <th class="text-center" style="width: 170px;">Site Cash Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary['allocations'] as $alloc)
                            @php
                                $dateStr = \Carbon\Carbon::parse($alloc->date)->toDateString();
                                $paidOnDate = $summary['paid_dates_map'][$dateStr] ?? 0.0;
                                $isDayPaid = $paidOnDate > 0;
                                $dayExpensesSum = (float) $alloc->expenses->sum('amount');
                                $dayLabourSum = (float) $alloc->total_labour_wage;
                                $dayTotal = $dayLabourSum + $dayExpensesSum;
                                $hasItemTracking = $alloc->items->contains(fn ($it) => ! empty($it->paid_by) || (bool) $it->referred_to_am);
                                $isPartialDayPaid = $isDayPaid && $paidOnDate < ($dayLabourSum - 0.01);
                            @endphp
                            <tr>
                                <td class="fw-semibold text-nowrap">
                                    {{ \Carbon\Carbon::parse($alloc->date)->format('d M Y') }}
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($alloc->date)->format('l') }}</div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($alloc->items as $item)
                                            @php
                                                $itemPaidBySm = $item->paid_by === 'Site Manager'
                                                    || (! $hasItemTracking && $isDayPaid && $paidOnDate >= ($dayLabourSum - 0.01));
                                                $itemPaidByAm = $item->paid_by === 'Account Manager';
                                                $itemPaidViaWeekly = $isWeekFullySettled && ! $itemPaidBySm && ! $itemPaidByAm;
                                                $isItemPaidGreen = $itemPaidBySm || $itemPaidViaWeekly;
                                            @endphp
                                            <span class="badge {{ $isItemPaidGreen ? 'bg-success-subtle text-success border-success-subtle' : ($itemPaidByAm ? 'bg-info-subtle text-info border-info-subtle' : 'bg-light text-dark') }} border px-2 py-1">
                                                {{ $item->worker_count }} {{ $item->name_ta ?: $item->name_en }}
                                                (₹{{ number_format((float) $item->subtotal, 0) }})
                                                @if ($itemPaidBySm || $itemPaidViaWeekly)
                                                    <span class="ms-1 badge bg-success text-white" style="font-size:0.65rem;">Paid</span>
                                                @elseif ($itemPaidByAm)
                                                    <span class="ms-1 badge bg-info text-white" style="font-size:0.65rem;">Paid by AM</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                    @if ($alloc->expenses->isNotEmpty())
                                        <div class="mt-1 d-flex flex-wrap gap-1">
                                            @foreach ($alloc->expenses as $exp)
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size:0.72rem;">
                                                    {{ $exp->item_name }}: ₹{{ number_format((float) $exp->amount, 0) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($dayLabourSum, 2) }}</td>
                                <td class="text-end text-muted">₹{{ number_format($dayExpensesSum, 2) }}</td>
                                <td class="text-end fw-bold">₹{{ number_format($dayTotal, 2) }}</td>
                                <td class="text-center">
                                    @if ($isWeekFullySettled)
                                        <span class="badge bg-success text-white px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>Paid
                                        </span>
                                    @elseif ($isPartialDayPaid)
                                        <span class="badge bg-info text-white px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>Partial Paid (₹{{ number_format($paidOnDate, 0) }})
                                        </span>
                                        <div class="small text-danger fw-semibold mt-1" style="font-size: 0.72rem;">
                                            Unpaid: ₹{{ number_format($dayLabourSum - $paidOnDate, 0) }}
                                        </div>
                                    @elseif ($isDayPaid)
                                        <span class="badge bg-success text-white px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i>Paid (₹{{ number_format($paidOnDate, 0) }})
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                                            Unpaid
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No daily allocations found for this week.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Additional Weekly Expenses -->
        @if ($weeklyWage->expenses->isNotEmpty())
            <div class="card-custom overflow-hidden bg-white">
                <div class="p-3 bg-light border-bottom">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-receipt text-warning me-2"></i>Additional Weekly Site Expenses
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Expense Item</th>
                                <th class="text-center" style="width: 100px;">Qty</th>
                                <th class="text-end" style="width: 130px;">Unit Price</th>
                                <th class="text-end" style="width: 140px;">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeklyWage->expenses as $wExp)
                                <tr>
                                    <td class="fw-semibold">{{ $wExp->item_name }}</td>
                                    <td class="text-center">{{ $wExp->quantity }}</td>
                                    <td class="text-end">₹{{ number_format((float) $wExp->unit_price, 2) }}</td>
                                    <td class="text-end fw-bold">₹{{ number_format((float) $wExp->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
