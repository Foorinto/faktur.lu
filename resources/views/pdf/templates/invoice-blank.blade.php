<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: Helvetica, sans-serif; font-size: 10pt; color: #1a202c; }
        .header { border-bottom: 3px solid #9b5de5; padding-bottom: 1em; margin-bottom: 1.5em; }
        .invoice-title { font-size: 28pt; font-weight: bold; color: #9b5de5; }
        .field { border-bottom: 1px solid #cbd5e0; padding: 0.5em 0; min-height: 1.4em; }
        .label { font-size: 8pt; text-transform: uppercase; color: #718096; letter-spacing: 0.05em; margin-top: 0.5em; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; width: 50%; padding-right: 1em; }
        table { width: 100%; border-collapse: collapse; margin-top: 1em; }
        table th { background: #9b5de5; color: white; padding: 0.6em; text-align: left; font-size: 9pt; }
        table td { padding: 0.6em; border-bottom: 1px solid #e2e8f0; height: 2em; }
        .totals { margin-top: 1em; margin-left: auto; width: 50%; }
        .totals td { padding: 0.5em 0.8em; }
        .totals .total-row td { background: #9b5de5; color: white; font-weight: bold; }
        .legal { margin-top: 1.5em; padding: 1em; background: #fef3c7; border-left: 3px solid #f59e0b; font-size: 8.5pt; }
        .footer { margin-top: 2em; text-align: center; font-size: 8pt; color: #718096; padding-top: 1em; border-top: 1px solid #e2e8f0; }
        .powered-by { color: #9b5de5; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
@php
    $L = ['fr' => ['title' => 'Facture', 'from' => 'Émetteur', 'to' => 'Client', 'name' => 'Nom / Raison sociale', 'address' => 'Adresse', 'vat' => 'Numéro de TVA', 'matricule' => 'Matricule', 'invoice_no' => 'Numéro de facture', 'date' => 'Date', 'due' => 'Échéance', 'desc' => 'Description', 'qty' => 'Qté', 'price' => 'Prix unitaire HT', 'vatrate' => 'TVA %', 'total_ht' => 'Total HT', 'subtotal' => 'Sous-total HT', 'vat_total' => 'TVA', 'total' => 'Total TTC', 'payment' => 'Conditions de paiement', 'iban' => 'IBAN', 'bic' => 'BIC', 'mentions' => 'Mentions légales obligatoires (Luxembourg)', 'mentions_text' => '· Numéro de TVA luxembourgeois (LU + 8 chiffres)\\n· Numérotation séquentielle continue (Article 61 LIVA)\\n· Mention "Autoliquidation - Article 21 LIVA" pour les opérations B2B intra-UE\\n· Conservation 10 ans obligatoire\\n· Date d\'émission obligatoire'],
         'en' => ['title' => 'Invoice', 'from' => 'From', 'to' => 'To', 'name' => 'Name / Company', 'address' => 'Address', 'vat' => 'VAT number', 'matricule' => 'Registration number', 'invoice_no' => 'Invoice number', 'date' => 'Date', 'due' => 'Due date', 'desc' => 'Description', 'qty' => 'Qty', 'price' => 'Unit price net', 'vatrate' => 'VAT %', 'total_ht' => 'Net total', 'subtotal' => 'Subtotal net', 'vat_total' => 'VAT', 'total' => 'Total', 'payment' => 'Payment terms', 'iban' => 'IBAN', 'bic' => 'BIC', 'mentions' => 'Mandatory legal mentions (Luxembourg)', 'mentions_text' => '· Luxembourg VAT number (LU + 8 digits)\\n· Continuous sequential numbering (LIVA Article 61)\\n· "Reverse charge - Article 21 LIVA" mention for B2B intra-EU\\n· 10-year retention mandatory\\n· Issue date required'],
    ];
    $T = $L[$language] ?? $L['fr'];
@endphp

<div class="header">
    <div class="invoice-title">{{ $T['title'] }}</div>
</div>

<div class="grid">
    <div class="col">
        <div class="label">{{ $T['from'] }}</div>
        <div class="label">{{ $T['name'] }}</div><div class="field"></div>
        <div class="label">{{ $T['address'] }}</div><div class="field"></div><div class="field"></div>
        <div class="label">{{ $T['vat'] }}</div><div class="field"></div>
        <div class="label">{{ $T['matricule'] }}</div><div class="field"></div>
    </div>
    <div class="col">
        <div class="label">{{ $T['to'] }}</div>
        <div class="label">{{ $T['name'] }}</div><div class="field"></div>
        <div class="label">{{ $T['address'] }}</div><div class="field"></div><div class="field"></div>
        <div class="label">{{ $T['vat'] }}</div><div class="field"></div>
    </div>
</div>

<div style="margin-top: 1.5em;">
    <div class="grid">
        <div class="col"><div class="label">{{ $T['invoice_no'] }}</div><div class="field"></div></div>
        <div class="col"><div class="label">{{ $T['date'] }}</div><div class="field"></div></div>
    </div>
    <div class="grid">
        <div class="col"><div class="label">{{ $T['due'] }}</div><div class="field"></div></div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:50%">{{ $T['desc'] }}</th>
            <th style="width:10%; text-align:right">{{ $T['qty'] }}</th>
            <th style="width:15%; text-align:right">{{ $T['price'] }}</th>
            <th style="width:10%; text-align:right">{{ $T['vatrate'] }}</th>
            <th style="width:15%; text-align:right">{{ $T['total_ht'] }}</th>
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i < 6; $i++)
            <tr><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

<table class="totals">
    <tr><td>{{ $T['subtotal'] }}</td><td style="text-align:right">_______ €</td></tr>
    <tr><td>{{ $T['vat_total'] }} 17%</td><td style="text-align:right">_______ €</td></tr>
    <tr class="total-row"><td>{{ $T['total'] }}</td><td style="text-align:right">_______ €</td></tr>
</table>

<div style="margin-top: 1.5em;">
    <div class="grid">
        <div class="col"><div class="label">{{ $T['iban'] }}</div><div class="field"></div></div>
        <div class="col"><div class="label">{{ $T['bic'] }}</div><div class="field"></div></div>
    </div>
    <div class="label">{{ $T['payment'] }}</div><div class="field"></div>
</div>

<div class="legal">
    <strong>{{ $T['mentions'] }} :</strong><br>
    {!! nl2br(str_replace('\\n', "\n", $T['mentions_text'])) !!}
</div>

<div class="footer">
    {{ $T['title'] }} — <a href="https://faktur.lu" class="powered-by">faktur.lu</a> — Modèle gratuit / Free template
</div>
</body>
</html>
