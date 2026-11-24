<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Statement - {{ $statement->statement_number }} ({{ $site->name }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Base Screen & Print Layout */
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 9pt;
            line-height: 1.35;
        }

        .statement-sheet {
            max-width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 16mm 18mm;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            box-sizing: border-box;
            position: relative;
        }

        .header-brand-title {
            font-size: 16pt;
            font-weight: 800;
            color: #1e3a8a;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .header-brand-sub {
            font-size: 8pt;
            color: #64748b;
            font-weight: 500;
        }

        .statement-title-banner {
            background-color: #f8fafc;
            border-top: 2px solid #1e3a8a;
            border-bottom: 2px solid #1e3a8a;
            padding: 6px 12px;
            margin: 12px 0 16px 0;
            text-align: center;
        }

        .statement-title-banner h5 {
            margin: 0;
            font-size: 11pt;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .meta-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            height: 100%;
        }

        .meta-card .meta-title {
            font-size: 7.5pt;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 4px;
        }

        .table-print {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        .table-print th {
            background-color: #f1f5f9 !important;
            color: #1e293b;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }

        .table-print td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 8.5pt;
            vertical-align: middle;
        }

        .amount-words-card {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 8.5pt;
        }

        .sign-area {
            margin-top: 40px;
            padding-top: 8px;
        }

        .sign-line {
            border-top: 1px solid #475569;
            margin-top: 45px;
            padding-top: 6px;
            text-align: center;
            font-size: 8pt;
            font-weight: 600;
            color: #334155;
        }

        /* Print Media Overrides */
        @media print {
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .statement-sheet {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: 100% !important;
                min-height: auto !important;
            }

            .table-print th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .statement-title-banner {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar for Screen View -->
    <div class="no-print bg-dark text-white py-2 px-3 shadow sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('sites.show', $site->encrypted_id) }}" class="btn btn-outline-light btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Back to Site (தளத்திற்கு திரும்பு)
                </a>
                <span class="text-white-50">|</span>
                <span class="small fw-semibold">Statement #{{ $statement->statement_number }}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1" onclick="window.print()">
                    <i class="bi bi-printer-fill"></i> Print A4 (அச்சிடு)
                </button>
                <button type="button" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" onclick="window.print()">
                    <i class="bi bi-download"></i> Save as PDF (பதிவிறக்கம்)
                </button>
            </div>
        </div>
    </div>

    <!-- The Statement A4 Document -->
    <div class="statement-sheet">

        <!-- Company Header Block -->
        <div class="row align-items-center pb-2">
            <div class="col-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary text-white rounded p-2 text-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <div>
                        <div class="header-brand-title">DEIVENI CONSTRUCTIONS</div>
                        <div class="header-brand-sub">Engineers &bull; Builders &bull; Contractors &bull; Material Suppliers</div>
                        <div class="small text-muted mt-1" style="font-size:7.5pt;">
                            No. 12, Melakkal Main Road, Kochadai, Madurai &bull; GSTIN: 33AAAAA0000A1Z5 &bull; Phone: +91 94442 22333
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4 text-end">
                <div class="border rounded p-2 bg-light d-inline-block text-start" style="min-width: 170px;">
                    <div class="text-muted" style="font-size:7pt; text-transform:uppercase; font-weight:700;">Statement / Bill No:</div>
                    <div class="fw-bold font-monospace text-primary" style="font-size:9.5pt;">{{ $statement->statement_number }}</div>
                    <div class="text-muted mt-1" style="font-size:7pt; text-transform:uppercase; font-weight:700;">Date:</div>
                    <div class="fw-semibold text-dark" style="font-size:8.5pt;">{{ \Carbon\Carbon::parse($statement->statement_date)->format('d M Y') }}</div>
                </div>
            </div>
        </div>

        <!-- Banner Title -->
        <div class="statement-title-banner">
            <h5>{{ $statement->title ?: 'CLIENT PROJECT EXPENSE & WORK STATEMENT' }}</h5>
        </div>

        <!-- Parties Meta Block -->
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="meta-card">
                    <div class="meta-title"><i class="bi bi-person-fill me-1"></i> Billed To (கிளையண்ட் விவரம்):</div>
                    <div class="fw-bold text-dark fs-6">{{ $statement->client_name ?: ($site->client?->name ?? 'Client') }}</div>
                    @if ($statement->client_phone)
                        <div class="small text-muted mt-1">
                            <i class="bi bi-telephone me-1"></i> Phone: <strong>{{ $statement->client_phone }}</strong>
                        </div>
                    @endif
                    @if ($statement->client_address)
                        <div class="small text-muted mt-1">
                            <i class="bi bi-geo-alt me-1"></i> Address: {{ $statement->client_address }}
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="meta-card">
                    <div class="meta-title"><i class="bi bi-building me-1"></i> Project Site (திட்ட தளம்):</div>
                    <div class="fw-bold text-dark fs-6">{{ $site->name }}</div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-pin-map me-1"></i> Location: <strong>{{ $site->location ?? 'Madurai' }}</strong>
                        @if ($site->work_type)
                            &bull; Type: <strong>{{ $site->work_type }}</strong>
                        @endif
                    </div>
                    <div class="small text-muted mt-1">
                        Status: <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:6.5pt;">{{ $site->status ?? 'Active' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Table (Date-Wise Material & Work Items) -->
        @php
            $rawItems = is_array($statement->items) ? $statement->items : (json_decode($statement->items, true) ?: []);
            $dateItems = collect($rawItems)->filter(fn($it) => empty($it['is_weekly']) || $it['is_weekly'] == '0')->sortBy('date')->values();
            $weeklyItems = collect($rawItems)->filter(fn($it) => !empty($it['is_weekly']) && $it['is_weekly'] != '0')->sortBy('start_date')->values();
            $dateItemsTotal = $dateItems->sum(fn($it) => (float) ($it['billed_amount'] ?? ($it['amount'] ?? 0)));
            $weeklyItemsTotal = $weeklyItems->sum(fn($it) => (float) ($it['billed_amount'] ?? ($it['amount'] ?? 0)));
        @endphp

        <table class="table-print">
            <thead>
                <tr>
                    <th class="text-center" style="width: 30px;">#</th>
                    <th style="width: 85px;">Date</th>
                    <th style="width: 130px;">Category</th>
                    <th>Item / Description</th>
                    <th class="text-center" style="width: 85px;">Qty & Unit</th>
                    <th class="text-end" style="width: 110px;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dateItems as $idx => $it)
                    <tr>
                        <td class="text-center text-muted font-monospace">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="text-nowrap font-monospace text-muted" style="font-size: 7.5pt;">
                            {{ !empty($it['date']) ? \Carbon\Carbon::parse($it['date'])->format('d M Y') : '—' }}
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $it['category'] ?? 'Material' }}</span>
                        </td>
                        <td>
                            @if (!empty($it['name_ta']))
                                <div class="fw-bold text-dark" style="font-size: 9.5pt;">{{ $it['name_ta'] }}</div>
                                @if (!empty($it['name_en']))
                                    <small class="text-muted d-block" style="font-size: 7.5pt;">{{ $it['name_en'] }}</small>
                                @endif
                            @else
                                <div class="fw-medium text-dark" style="font-size: 8.5pt;">{{ !empty($it['name_en']) ? $it['name_en'] : ($it['description'] ?? 'Work details') }}</div>
                            @endif
                            @if (!empty($it['ref_no']) && $it['ref_no'] !== 'CUSTOM')
                                <small class="text-muted font-monospace" style="font-size: 7pt;">REF: {{ $it['ref_no'] }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @if (!empty($it['quantity']))
                                <strong>{{ $it['quantity'] }}</strong> <span class="text-muted small">{{ $it['unit'] ?? '' }}</span>
                            @else
                                &mdash;
                            @endif
                        </td>
                        <td class="text-end fw-bold text-dark">
                            ₹{{ number_format((float) ($it['billed_amount'] ?? ($it['amount'] ?? 0)), 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No date-wise items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
            @if ($weeklyItems->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end fw-semibold text-muted" style="border-top: 1.5px solid #cbd5e1; font-size: 8pt;">Items Subtotal:</td>
                        <td class="text-end fw-bold text-dark" style="border-top: 1.5px solid #cbd5e1;">₹{{ number_format($dateItemsTotal, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>

        @if ($weeklyItems->isNotEmpty())
            <!-- Weekly Site & Operational Expenses Table (Placed Separately Below) -->
            <div class="fw-bold text-dark mt-3 mb-1" style="font-size: 8.5pt; letter-spacing: 0.03em;">
                <i class="bi bi-calendar-week me-1 text-primary"></i> WEEKLY SITE & OPERATIONAL EXPENSES
            </div>
            <table class="table-print mt-1">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 30px;">#</th>
                        <th style="width: 170px;">Period (From ~ To)</th>
                        <th>Description</th>
                        <th class="text-end" style="width: 110px;">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($weeklyItems as $wIdx => $wit)
                        <tr>
                            <td class="text-center text-muted font-monospace">{{ str_pad($wIdx + 1, 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="font-monospace text-nowrap text-dark fw-semibold" style="font-size: 8pt;">
                                {{ !empty($wit['start_date']) ? \Carbon\Carbon::parse($wit['start_date'])->format('d M Y') : '' }}
                                ~
                                {{ !empty($wit['end_date']) ? \Carbon\Carbon::parse($wit['end_date'])->format('d M Y') : '' }}
                            </td>
                            <td>
                                <div class="fw-medium text-dark">{{ $wit['description'] ?? 'Weekly Site Expenses' }}</div>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ₹{{ number_format((float) ($wit['billed_amount'] ?? ($wit['amount'] ?? 0)), 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-semibold text-muted" style="border-top: 1.5px solid #cbd5e1; font-size: 8pt;">Weekly Expenses Subtotal:</td>
                        <td class="text-end fw-bold text-dark" style="border-top: 1.5px solid #cbd5e1;">₹{{ number_format($weeklyItemsTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif

        <!-- Summary Table -->
        <table class="table-print mt-2">
            <tfoot>
                @if ($weeklyItems->isNotEmpty() && $dateItems->isNotEmpty())
                    <tr>
                        <td colspan="4" class="text-end fw-semibold text-muted" style="border-top: 1.5px solid #cbd5e1;">Date Items Subtotal:</td>
                        <td class="text-end fw-semibold text-dark" style="border-top: 1.5px solid #cbd5e1; width: 110px;">₹{{ number_format($dateItemsTotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold text-muted">Weekly Expenses Subtotal:</td>
                        <td class="text-end fw-semibold text-dark">₹{{ number_format($weeklyItemsTotal, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="4" class="text-end fw-bold text-muted" style="border-top: 2px solid #cbd5e1;">Subtotal:</td>
                    <td class="text-end fw-bold text-dark" style="border-top: 2px solid #cbd5e1; width: 110px;">₹{{ number_format((float) $statement->subtotal, 2) }}</td>
                </tr>

                @if ((float) $statement->tax_amount > 0)
                    <tr>
                        <td colspan="4" class="text-end small text-muted">Tax / GST ({{ (float) $statement->tax_percent }}%):</td>
                        <td class="text-end small fw-semibold text-dark">₹{{ number_format((float) $statement->tax_amount, 2) }}</td>
                    </tr>
                @endif

                @if ((float) $statement->discount_amount > 0)
                    <tr>
                        <td colspan="4" class="text-end small text-muted">Discount / Advance Adjustment:</td>
                        <td class="text-end small fw-semibold text-danger">−₹{{ number_format((float) $statement->discount_amount, 2) }}</td>
                    </tr>
                @endif

                <!-- Grand Total -->
                <tr style="background-color: #f8fafc;">
                    <td colspan="4" class="text-end fw-bold text-dark fs-6" style="border-top: 2px solid #1e3a8a; border-bottom: 2px solid #1e3a8a;">
                        GRAND TOTAL:
                    </td>
                    <td class="text-end fw-bold text-success fs-6" style="border-top: 2px solid #1e3a8a; border-bottom: 2px solid #1e3a8a;">
                        ₹{{ number_format((float) $statement->total_amount, 2) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Amount in Words -->
        @php
            if (! function_exists('numberToIndianWords')) {
                function numberToIndianWords($number) {
                    if ($number == 0) return 'Rupees Zero Only';
                    $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

                    $convertThree = function ($num) use ($units, $tens) {
                        $str = '';
                        if ($num >= 100) {
                            $str .= $units[(int)($num / 100)] . ' Hundred ';
                            $num %= 100;
                        }
                        if ($num >= 20) {
                            $str .= $tens[(int)($num / 10)] . ' ';
                            $num %= 10;
                        }
                        if ($num > 0) {
                            $str .= $units[$num] . ' ';
                        }
                        return trim($str);
                    };

                    $words = '';
                    $crore = (int)($number / 10000000);
                    $number %= 10000000;
                    $lakh = (int)($number / 100000);
                    $number %= 100000;
                    $thousand = (int)($number / 1000);
                    $number %= 1000;
                    $hundred = $number;

                    if ($crore > 0) $words .= $convertThree($crore) . ' Crore ';
                    if ($lakh > 0) $words .= $convertThree($lakh) . ' Lakh ';
                    if ($thousand > 0) $words .= $convertThree($thousand) . ' Thousand ';
                    if ($hundred > 0) $words .= $convertThree($hundred);

                    return 'Rupees ' . trim($words) . ' Only';
                }
            }
        @endphp
        <div class="amount-words-card mb-3">
            <span class="text-muted small fw-bold">Amount in Words:</span>
            <strong class="text-dark ms-1">{{ numberToIndianWords(round((float)$statement->total_amount)) }}</strong>
        </div>

        <!-- Notes & Payment Terms -->
        @if (!empty($statement->notes))
            <div class="border rounded p-2 mb-3 bg-light" style="font-size:7.5pt;">
                <div class="fw-bold text-dark mb-1 text-uppercase" style="font-size:7pt;">Terms & Instructions / குறிப்புகள்:</div>
                <div style="white-space: pre-line; color: #475569;">{{ $statement->notes }}</div>
            </div>
        @endif

        <!-- Signatures Block -->
        <div class="row sign-area">
            <div class="col-4">
                <div class="sign-line">
                    Prepared By<br>
                    <small class="text-muted">{{ $statement->creator?->name ?? 'Site Supervisor' }}</small>
                </div>
            </div>
            <div class="col-4">
                <div class="sign-line">
                    Client Acknowledgment<br>
                    <small class="text-muted">{{ $statement->client_name ?: 'Client Signature' }}</small>
                </div>
            </div>
            <div class="col-4">
                <div class="sign-line">
                    For Deiveni Constructions<br>
                    <small class="text-muted">Authorized Signatory</small>
                </div>
            </div>
        </div>

        <div class="text-center text-muted mt-4" style="font-size:6.5pt; border-top: 1px solid #f1f5f9; padding-top: 6px;">
            This is a computer generated statement. Generated on {{ now()->format('d M Y, h:i A') }}
        </div>

    </div>

    @if ($autoprint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 500);
            });
        </script>
    @endif

</body>
</html>
