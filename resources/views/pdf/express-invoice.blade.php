<!DOCTYPE html>
<html lang="{{ $language }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice['number'] }}</title>
    <style>
        @page { margin: 1.5cm; }
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10pt;
            color: #1a202c;
            line-height: 1.4;
        }
        .header {
            border-bottom: 3px solid #9b5de5;
            padding-bottom: 1.5em;
            margin-bottom: 2em;
        }
        .header-grid {
            display: table;
            width: 100%;
        }
        .header-cell {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .invoice-title {
            font-size: 24pt;
            font-weight: bold;
            color: #9b5de5;
            margin: 0 0 0.3em 0;
        }
        .invoice-number {
            font-size: 12pt;
            color: #4a5568;
        }
        .text-right { text-align: right; }
        .sender { margin-bottom: 1.5em; }
        .sender-name {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 0.3em;
        }
        .small {
            font-size: 9pt;
            color: #4a5568;
        }
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 2em;
        }
        .party-block {
            display: table-cell;
            vertical-align: top;
            width: 48%;
        }
        .party-block:first-child { padding-right: 4%; }
        .party-label {
            font-size: 8pt;
            text-transform: uppercase;
            color: #9b5de5;
            letter-spacing: 0.05em;
            margin-bottom: 0.3em;
            font-weight: bold;
        }
        .party-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 0.2em;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 2em;
            border: 1px solid #e2e8f0;
        }
        .meta-table td {
            padding: 0.5em 0.8em;
            border-bottom: 1px solid #e2e8f0;
        }
        .meta-table td:first-child {
            background: #f7fafc;
            font-weight: bold;
            width: 30%;
            color: #4a5568;
        }
        .meta-table tr:last-child td { border-bottom: none; }

        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5em;
        }
        .lines-table th {
            background: #9b5de5;
            color: white;
            text-align: left;
            padding: 0.6em 0.8em;
            font-size: 9pt;
            text-transform: uppercase;
        }
        .lines-table td {
            padding: 0.7em 0.8em;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .lines-table tr:nth-child(even) td {
            background: #f7fafc;
        }
        .text-right-col { text-align: right; }
        .totals {
            margin-left: auto;
            width: 50%;
            border: 1px solid #e2e8f0;
        }
        .totals td {
            padding: 0.5em 0.8em;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals td:first-child {
            background: #f7fafc;
            font-weight: bold;
            color: #4a5568;
        }
        .totals .total-row td {
            background: #9b5de5;
            color: white;
            font-size: 12pt;
            font-weight: bold;
            border-bottom: none;
        }
        .notes {
            margin-top: 2em;
            padding: 1em;
            background: #f7fafc;
            border-left: 4px solid #9b5de5;
            font-size: 9pt;
        }
        .legal-mention {
            margin-top: 1.5em;
            padding: 0.8em 1em;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            font-size: 9pt;
            color: #78350f;
        }
        .footer {
            margin-top: 3em;
            padding-top: 1em;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #718096;
            text-align: center;
        }
        .powered-by {
            color: #9b5de5;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>
@php
    $labels = [
        'fr' => [
            'invoice' => 'Facture', 'invoice_number' => 'Numéro de facture', 'invoice_date' => 'Date',
            'due_date' => 'Échéance', 'from' => 'Émetteur', 'to' => 'Client',
            'description' => 'Description', 'quantity' => 'Qté', 'unit_price' => 'PU HT',
            'vat_rate' => 'TVA', 'total_ht' => 'Total HT',
            'subtotal' => 'Sous-total HT', 'vat' => 'TVA', 'total' => 'Total TTC',
            'notes' => 'Notes', 'vat_no' => 'N° TVA',
            'reverse_charge' => 'Autoliquidation - Article 21 LIVA. La TVA est due par le preneur.',
            'iban' => 'IBAN', 'bic' => 'BIC',
            'powered_by' => 'Généré gratuitement avec',
        ],
        'de' => [
            'invoice' => 'Rechnung', 'invoice_number' => 'Rechnungsnummer', 'invoice_date' => 'Datum',
            'due_date' => 'Fälligkeit', 'from' => 'Absender', 'to' => 'Kunde',
            'description' => 'Beschreibung', 'quantity' => 'Menge', 'unit_price' => 'Stk-Preis',
            'vat_rate' => 'MwSt', 'total_ht' => 'Netto',
            'subtotal' => 'Zwischensumme netto', 'vat' => 'MwSt', 'total' => 'Bruttobetrag',
            'notes' => 'Anmerkungen', 'vat_no' => 'USt-IdNr',
            'reverse_charge' => 'Reverse Charge - Artikel 21 LIVA. Die MwSt ist vom Empfänger geschuldet.',
            'iban' => 'IBAN', 'bic' => 'BIC',
            'powered_by' => 'Kostenlos erstellt mit',
        ],
        'en' => [
            'invoice' => 'Invoice', 'invoice_number' => 'Invoice number', 'invoice_date' => 'Date',
            'due_date' => 'Due date', 'from' => 'From', 'to' => 'To',
            'description' => 'Description', 'quantity' => 'Qty', 'unit_price' => 'Unit price',
            'vat_rate' => 'VAT', 'total_ht' => 'Net total',
            'subtotal' => 'Subtotal net', 'vat' => 'VAT', 'total' => 'Total',
            'notes' => 'Notes', 'vat_no' => 'VAT no.',
            'reverse_charge' => 'Reverse charge - Article 21 LIVA. VAT to be paid by the recipient.',
            'iban' => 'IBAN', 'bic' => 'BIC',
            'powered_by' => 'Generated for free with',
        ],
        'lb' => [
            'invoice' => 'Rechnung', 'invoice_number' => 'Rechnungsnummer', 'invoice_date' => 'Datum',
            'due_date' => 'Fällegkeet', 'from' => 'Vu', 'to' => 'Un',
            'description' => 'Beschreiwung', 'quantity' => 'Quant', 'unit_price' => 'Stk-Präis',
            'vat_rate' => 'TVA', 'total_ht' => 'Total HT',
            'subtotal' => 'Zwëschesumme HT', 'vat' => 'TVA', 'total' => 'Total TTC',
            'notes' => 'Bemierkungen', 'vat_no' => 'TVA-Nr',
            'reverse_charge' => 'Autoliquidatioun - Artikel 21 LIVA. D\'TVA gëtt vum Empfänger geschuld.',
            'iban' => 'IBAN', 'bic' => 'BIC',
            'powered_by' => 'Gratis erstellt mat',
        ],
        'pt' => [
            'invoice' => 'Fatura', 'invoice_number' => 'Número de fatura', 'invoice_date' => 'Data',
            'due_date' => 'Vencimento', 'from' => 'Emissor', 'to' => 'Cliente',
            'description' => 'Descrição', 'quantity' => 'Qtd', 'unit_price' => 'Preço unit.',
            'vat_rate' => 'IVA', 'total_ht' => 'Total sem IVA',
            'subtotal' => 'Subtotal sem IVA', 'vat' => 'IVA', 'total' => 'Total com IVA',
            'notes' => 'Notas', 'vat_no' => 'N.º IVA',
            'reverse_charge' => 'Autoliquidação - Artigo 21 LIVA. O IVA é devido pelo destinatário.',
            'iban' => 'IBAN', 'bic' => 'BIC',
            'powered_by' => 'Gerado gratuitamente com',
        ],
    ];
    $L = $labels[$language] ?? $labels['fr'];
    $currencySymbol = ['EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF '][$currency] ?? $currency;
    $fmt = fn ($n) => number_format($n, 2, ',', ' ') . ' ' . $currencySymbol;
@endphp

<div class="header">
    <div class="header-grid">
        <div class="header-cell">
            <div class="sender">
                <div class="sender-name">{{ $sender['name'] }}</div>
                @if(!empty($sender['address']))
                    <div class="small">{!! nl2br(e($sender['address'])) !!}</div>
                @endif
                @if(!empty($sender['vat_number']))
                    <div class="small">{{ $L['vat_no'] }}: {{ $sender['vat_number'] }}</div>
                @endif
                @if(!empty($sender['email']))
                    <div class="small">{{ $sender['email'] }}</div>
                @endif
                @if(!empty($sender['phone']))
                    <div class="small">{{ $sender['phone'] }}</div>
                @endif
            </div>
        </div>
        <div class="header-cell text-right">
            <div class="invoice-title">{{ $L['invoice'] }}</div>
            <div class="invoice-number">N° {{ $invoice['number'] }}</div>
        </div>
    </div>
</div>

<div class="parties">
    <div class="party-block">
        <div class="party-label">{{ $L['from'] }}</div>
        <div class="party-name">{{ $sender['name'] }}</div>
        @if(!empty($sender['address']))
            <div class="small">{!! nl2br(e($sender['address'])) !!}</div>
        @endif
    </div>
    <div class="party-block">
        <div class="party-label">{{ $L['to'] }}</div>
        <div class="party-name">{{ $client['name'] }}</div>
        @if(!empty($client['address']))
            <div class="small">{!! nl2br(e($client['address'])) !!}</div>
        @endif
        @if(!empty($client['vat_number']))
            <div class="small">{{ $L['vat_no'] }}: {{ $client['vat_number'] }}</div>
        @endif
    </div>
</div>

<table class="meta-table">
    <tr>
        <td>{{ $L['invoice_date'] }}</td>
        <td>{{ \Carbon\Carbon::parse($invoice['date'])->format('d/m/Y') }}</td>
    </tr>
    @if(!empty($invoice['due_date']))
    <tr>
        <td>{{ $L['due_date'] }}</td>
        <td>{{ \Carbon\Carbon::parse($invoice['due_date'])->format('d/m/Y') }}</td>
    </tr>
    @endif
</table>

<table class="lines-table">
    <thead>
        <tr>
            <th>{{ $L['description'] }}</th>
            <th class="text-right-col">{{ $L['quantity'] }}</th>
            <th class="text-right-col">{{ $L['unit_price'] }}</th>
            <th class="text-right-col">{{ $L['vat_rate'] }}</th>
            <th class="text-right-col">{{ $L['total_ht'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lines as $line)
            @php $lineHt = $line['quantity'] * $line['unit_price']; @endphp
            <tr>
                <td>{{ $line['description'] }}</td>
                <td class="text-right-col">{{ rtrim(rtrim(number_format($line['quantity'], 2, ',', ' '), '0'), ',') }}</td>
                <td class="text-right-col">{{ $fmt($line['unit_price']) }}</td>
                <td class="text-right-col">{{ $reverseCharge ? '-' : $line['vat_rate'].'%' }}</td>
                <td class="text-right-col">{{ $fmt($lineHt) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td>{{ $L['subtotal'] }}</td>
        <td class="text-right">{{ $fmt($totalHt) }}</td>
    </tr>
    @if(!$reverseCharge)
        @foreach($vatBreakdown as $rate => $amounts)
            @if($amounts['tva'] > 0)
                <tr>
                    <td>{{ $L['vat'] }} {{ $rate }}%</td>
                    <td class="text-right">{{ $fmt($amounts['tva']) }}</td>
                </tr>
            @endif
        @endforeach
    @endif
    <tr class="total-row">
        <td>{{ $L['total'] }}</td>
        <td class="text-right">{{ $fmt($totalTtc) }}</td>
    </tr>
</table>

@if($reverseCharge)
    <div class="legal-mention">
        ⚖ {{ $L['reverse_charge'] }}
    </div>
@endif

@if(!empty($invoice['notes']))
    <div class="notes">
        <strong>{{ $L['notes'] }}:</strong><br>
        {!! nl2br(e($invoice['notes'])) !!}
    </div>
@endif

@if(!empty($sender['iban']))
    <div class="notes">
        {{ $L['iban'] }}: <strong>{{ $sender['iban'] }}</strong>
        @if(!empty($sender['bic'])) &nbsp;·&nbsp; {{ $L['bic'] }}: <strong>{{ $sender['bic'] }}</strong> @endif
    </div>
@endif

<div class="footer">
    {{ $L['powered_by'] }} <a href="https://faktur.lu" class="powered-by">faktur.lu</a> - {{ $L['invoice'] }} {{ \Carbon\Carbon::now()->format('Y') }}
</div>

</body>
</html>
