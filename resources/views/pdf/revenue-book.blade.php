<!DOCTYPE html>
<html lang="{{ $locale ?? app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('app.pdf_revenue_book_title') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
            color: #1f2937;
        }

        .container {
            padding: 15mm 10mm;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10mm;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 5mm;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: top;
        }

        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2mm;
        }

        .period {
            font-size: 11pt;
            color: #6b7280;
        }

        .company-name {
            font-size: 10pt;
            font-weight: bold;
            color: #1f2937;
        }

        .company-info {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 1mm;
        }

        /* Summary boxes */
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 8mm;
        }

        .summary-box {
            display: table-cell;
            width: 25%;
            padding: 3mm;
            text-align: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .summary-box:first-child {
            border-radius: 4px 0 0 4px;
        }

        .summary-box:last-child {
            border-radius: 0 4px 4px 0;
        }

        .summary-label {
            font-size: 7pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-value {
            font-size: 11pt;
            font-weight: bold;
            color: #1f2937;
            margin-top: 1mm;
        }

        .summary-value.highlight {
            color: #059669;
        }

        /* VAT Summary */
        .vat-summary {
            margin-bottom: 8mm;
        }

        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3mm;
            padding-bottom: 2mm;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            font-weight: 600;
            font-size: 8pt;
            color: #374151;
            padding: 2.5mm 2mm;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
        }

        th.right {
            text-align: right;
        }

        td {
            padding: 2mm;
            font-size: 8pt;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        td.right {
            text-align: right;
        }

        td.number {
            font-family: DejaVu Sans Mono, monospace;
            text-align: right;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        tfoot td {
            background: #f3f4f6;
            font-weight: bold;
            border-top: 2px solid #d1d5db;
            padding: 2.5mm 2mm;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 10mm;
            right: 10mm;
            font-size: 7pt;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 3mm;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="title">{{ __('app.pdf_revenue_book_title') }}</div>
                <div class="period">
                    {{ __('app.pdf_period_from_to', ['start' => $startDate->format('d/m/Y'), 'end' => $endDate->format('d/m/Y')]) }}
                </div>
            </div>
            <div class="header-right">
                @if($settings)
                    <div class="company-name">{{ $settings->company_name }}</div>
                    <div class="company-info">
                        @if($settings->matricule)
                            {{ __('app.pdf_matricule_label') }}: {{ $settings->matricule }}<br>
                        @endif
                        @if($settings->vat_number)
                            {{ __('app.pdf_vat_number_label') }}: {{ $settings->vat_number }}
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-box">
                <div class="summary-label">{{ __('app.pdf_paid_invoices') }}</div>
                <div class="summary-value">{{ $totals['count'] }}</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">{{ __('app.pdf_total_ht') }}</div>
                <div class="summary-value">{{ number_format($totals['ht'], 2, ',', ' ') }} €</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">{{ __('app.pdf_total_vat') }}</div>
                <div class="summary-value">{{ number_format($totals['vat'], 2, ',', ' ') }} €</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">{{ __('app.pdf_total_ttc') }}</div>
                <div class="summary-value highlight">{{ number_format($totals['ttc'], 2, ',', ' ') }} €</div>
            </div>
        </div>

        <!-- VAT Breakdown -->
        @if(count($vatBreakdown) > 0)
        <div class="vat-summary">
            <div class="section-title">{{ __('app.pdf_vat_summary_title') }}</div>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('app.pdf_vat_rate') }}</th>
                        <th class="right">{{ __('app.pdf_base_ht') }}</th>
                        <th class="right">{{ __('app.pdf_vat_amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vatBreakdown as $vat)
                    <tr>
                        <td>{{ number_format($vat['rate'], 0) }}%</td>
                        <td class="number">{{ number_format($vat['base'], 2, ',', ' ') }} €</td>
                        <td class="number">{{ number_format($vat['amount'], 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>{{ __('app.pdf_total') }}</td>
                        <td class="number">{{ number_format($totals['ht'], 2, ',', ' ') }} €</td>
                        <td class="number">{{ number_format($totals['vat'], 2, ',', ' ') }} €</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @endif

        <!-- Invoices List -->
        <div class="section-title">{{ __('app.pdf_revenue_details') }}</div>
        <table>
            <thead>
                <tr>
                    <th>{{ __('app.pdf_payment_date') }}</th>
                    <th>{{ __('app.pdf_invoice_number_short') }}</th>
                    <th>{{ __('app.pdf_client') }}</th>
                    <th class="right">{{ __('app.pdf_total_ht') }}</th>
                    <th class="right">{{ __('invoice.vat') }}</th>
                    <th class="right">{{ __('app.pdf_total_ttc') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->paid_at->format('d/m/Y') }}</td>
                    <td>{{ $invoice->number }}</td>
                    <td>{{ $invoice->client->name }}</td>
                    <td class="number">{{ number_format($invoice->total_ht, 2, ',', ' ') }} €</td>
                    <td class="number">{{ number_format($invoice->total_vat, 2, ',', ' ') }} €</td>
                    <td class="number">{{ number_format($invoice->total_ttc, 2, ',', ' ') }} €</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 10mm; color: #6b7280;">
                        {{ __('app.pdf_no_revenue_period') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(count($invoices) > 0)
            <tfoot>
                <tr>
                    <td colspan="3">{{ __('app.pdf_total_invoices_count', ['count' => $totals['count'], 'plural' => $totals['count'] > 1 ? 's' : '']) }}</td>
                    <td class="number">{{ number_format($totals['ht'], 2, ',', ' ') }} €</td>
                    <td class="number">{{ number_format($totals['vat'], 2, ',', ' ') }} €</td>
                    <td class="number" style="color: #059669;">{{ number_format($totals['ttc'], 2, ',', ' ') }} €</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        {{ __('app.pdf_generated_on', ['date' => $generatedAt->format('d/m/Y H:i')]) }} | {{ __('app.pdf_page') }} <span class="page-number"></span>
    </div>
</body>
</html>
