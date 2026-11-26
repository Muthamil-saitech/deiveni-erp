<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Bill - {{ $bill->bill_number }} ({{ $site?->name }})</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 12mm 12mm;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 9.5pt;
            line-height: 1.4;
        }

        .bill-sheet {
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

        .bill-title-banner {
            background-color: #f8fafc;
            border-top: 2px solid #1e3a8a;
            border-bottom: 2px solid #1e3a8a;
            padding: 6px 12px;
            margin: 12px 0 16px 0;
            text-align: center;
        }

        .bill-title-banner h5 {
            margin: 0;
            font-size: 11pt;
            font-weight: 700;
            color: #1e3a8a;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .table-print {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 14px;
        }

        .table-print th {
            background-color: #f1f5f9 !important;
            color: #1e293b;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }

        .table-print td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 8.8pt;
            vertical-align: middle;
        }

        .signature-line {
            border-top: 1px dashed #94a3b8;
            padding-top: 5px;
            margin-top: 40px;
            font-size: 8.5pt;
            color: #475569;
            font-weight: 600;
        }

        @media print {
            body {
                background: #ffffff;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .bill-sheet {
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Top Print Action Bar -->
    <div class="container py-2 no-print" style="max-width: 210mm;">
        <div class="d-flex justify-content-between align-items-center bg-white p-2 px-3 rounded shadow-sm border">
            <div class="d-flex gap-2">
                <a href="{{ route('temporary-billing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Saved Bills Table
                </a>
                <a href="{{ route('temporary-billing.edit', $bill->encrypted_id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edit Bill
                </a>
            </div>
            <button type="button" class="btn btn-primary btn-sm fw-bold px-3" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Print Temporary Bill
            </button>
        </div>
    </div>

    <div class="bill-sheet">
        <!-- Brand Header -->
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="header-brand-title">DEIVENI CONSTRUCTION</div>
                <div class="text-muted small">Construction Site Operations &amp; Payment Voucher</div>
            </div>
            <div class="text-end">
                <span class="badge bg-warning-subtle text-dark border border-warning px-2 py-1">TEMPORARY BILL</span>
                <div class="fw-bold font-monospace mt-1">{{ $bill->bill_number }}</div>
                <div class="small text-muted">Date: {{ $bill->bill_date ? $bill->bill_date->format('d M Y') : '-' }}</div>
            </div>
        </div>

        <div class="bill-title-banner">
            <h5>{{ $bill->title ?: 'PAINTER & ELECTRICIAN TEMPORARY BILL' }}</h5>
        </div>

        <!-- Site & Client Metadata -->
        <div class="row g-3 mb-3">
            <div class="col-6">
                <div class="p-2 px-3 bg-light border rounded">
                    <div class="text-muted fw-bold text-uppercase" style="font-size: 7.5pt;">Project Site</div>
                    <div class="fw-bold text-dark">{{ $site?->name ?? 'N/A' }}</div>
                    @if ($site?->location)
                        <div class="small text-muted">{{ $site->location }}</div>
                    @endif
                </div>
            </div>
            <div class="col-6">
                <div class="p-2 px-3 bg-light border rounded">
                    <div class="text-muted fw-bold text-uppercase" style="font-size: 7.5pt;">Client</div>
                    <div class="fw-bold text-dark">{{ $site?->client?->name ?? 'Internal / Site Work' }}</div>
                </div>
            </div>
        </div>

        <!-- Date-wise Payment Table -->
        <table class="table-print">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th style="width: 90px;">Date</th>
                    <th>Work / Payment Description</th>
                    <th style="width: 65px;" class="text-center">Workers</th>
                    <th style="width: 85px;" class="text-end">Rate (₹)</th>
                    <th style="width: 110px;" class="text-end">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bill->items ?? [] as $idx => $item)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-nowrap">{{ !empty($item['date']) ? \Carbon\Carbon::parse($item['date'])->format('d M Y') : '-' }}</td>
                        <td>{{ $item['description'] ?? '-' }}</td>
                        <td class="text-center">{{ (int) ($item['worker_count'] ?? 0) }}</td>
                        <td class="text-end">₹{{ number_format((float) ($item['daily_wage'] ?? 0), 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format((float) ($item['billed_amount'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <td colspan="5" class="text-end fw-bold text-uppercase" style="font-size: 9.5pt;">Grand Total</td>
                    <td class="text-end fw-bold text-primary" style="font-size: 11pt;">₹{{ number_format((float) $bill->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if (!empty($bill->notes))
            <div class="p-2 px-3 bg-light border rounded small mb-4">
                <strong class="text-dark">Notes:</strong> {{ $bill->notes }}
            </div>
        @endif

        <!-- Signature Blocks -->
        <div class="row g-4 mt-4">
            <div class="col-4 text-center">
                <div class="signature-line">Prepared By</div>
            </div>
            <div class="col-4 text-center">
                <div class="signature-line">Painter / Electrician Signature</div>
            </div>
            <div class="col-4 text-center">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
    </div>
</body>
</html>
