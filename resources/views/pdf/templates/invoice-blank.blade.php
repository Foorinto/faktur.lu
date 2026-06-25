<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1cm 1.2cm; }
        body { font-family: Helvetica, sans-serif; font-size: 9pt; color: #1a202c; line-height: 1.3; }
        .header { border-bottom: 2px solid #9b5de5; padding-bottom: 0.4em; margin-bottom: 0.8em; }
        .invoice-title { font-size: 22pt; font-weight: bold; color: #9b5de5; }
        .field { border-bottom: 1px solid #cbd5e0; padding: 0.25em 0; min-height: 1em; }
        .label { font-size: 7pt; text-transform: uppercase; color: #718096; letter-spacing: 0.04em; margin-top: 0.3em; }
        .grid { display: table; width: 100%; }
        .col { display: table-cell; vertical-align: top; width: 50%; padding-right: 0.6em; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5em; }
        table th { background: #9b5de5; color: white; padding: 0.4em; text-align: left; font-size: 8pt; }
        table td { padding: 0.45em; border-bottom: 1px solid #e2e8f0; height: 1.4em; }
        .totals { margin-top: 0.6em; margin-left: auto; width: 50%; }
        .totals td { padding: 0.3em 0.6em; }
        .totals .total-row td { background: #9b5de5; color: white; font-weight: bold; }
        .legal { margin-top: 0.8em; padding: 0.6em 0.8em; background: #fef3c7; border-left: 2px solid #f59e0b; font-size: 7.5pt; line-height: 1.4; }
        .footer { margin-top: 0.8em; text-align: center; font-size: 7pt; color: #718096; padding-top: 0.4em; border-top: 1px solid #e2e8f0; }
        .powered-by { color: #9b5de5; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
@php
    $lang = $language ?? 'fr';
    $L = [
        'fr' => [
            'title' => 'Facture', 'from' => 'Émetteur', 'to' => 'Client',
            'name' => 'Nom / Raison sociale', 'address' => 'Adresse',
            'vat' => 'Numéro de TVA', 'matricule' => 'Matricule',
            'invoice_no' => 'Numéro de facture', 'date' => 'Date', 'due' => 'Échéance',
            'desc' => 'Description', 'qty' => 'Qté', 'price' => 'Prix unitaire HT',
            'vatrate' => 'TVA %', 'total_ht' => 'Total HT', 'subtotal' => 'Sous-total HT',
            'vat_total' => 'TVA', 'total' => 'Total TTC',
            'payment' => 'Conditions de paiement', 'iban' => 'IBAN', 'bic' => 'BIC',
            'mentions' => 'Mentions légales obligatoires (Luxembourg)',
            'mentions_text' => '· Numéro de TVA luxembourgeois (LU + 8 chiffres)\\n· Numérotation séquentielle continue (Article 61 LIVA)\\n· Mention "Autoliquidation - Article 21 LIVA" pour les opérations B2B intra-UE\\n· Conservation 10 ans obligatoire',
            'footer' => 'Modèle gratuit',
        ],
        'de' => [
            'title' => 'Rechnung', 'from' => 'Aussteller', 'to' => 'Kunde',
            'name' => 'Name / Firma', 'address' => 'Adresse',
            'vat' => 'MwSt-Nummer', 'matricule' => 'Registriernummer',
            'invoice_no' => 'Rechnungsnummer', 'date' => 'Datum', 'due' => 'Fälligkeit',
            'desc' => 'Beschreibung', 'qty' => 'Menge', 'price' => 'Einzelpreis netto',
            'vatrate' => 'MwSt %', 'total_ht' => 'Nettosumme', 'subtotal' => 'Zwischensumme netto',
            'vat_total' => 'MwSt', 'total' => 'Gesamtbetrag',
            'payment' => 'Zahlungsbedingungen', 'iban' => 'IBAN', 'bic' => 'BIC',
            'mentions' => 'Pflichtangaben (Luxemburg)',
            'mentions_text' => '· Luxemburgische MwSt-Nummer (LU + 8 Ziffern)\\n· Lückenlose fortlaufende Nummerierung (Artikel 61 LIVA)\\n· Hinweis "Reverse Charge - Artikel 21 LIVA" bei B2B-Geschäften innerhalb der EU\\n· 10 Jahre Aufbewahrungspflicht',
            'footer' => 'Kostenlose Vorlage',
        ],
        'en' => [
            'title' => 'Invoice', 'from' => 'From', 'to' => 'To',
            'name' => 'Name / Company', 'address' => 'Address',
            'vat' => 'VAT number', 'matricule' => 'Registration number',
            'invoice_no' => 'Invoice number', 'date' => 'Date', 'due' => 'Due date',
            'desc' => 'Description', 'qty' => 'Qty', 'price' => 'Unit price net',
            'vatrate' => 'VAT %', 'total_ht' => 'Net total', 'subtotal' => 'Subtotal net',
            'vat_total' => 'VAT', 'total' => 'Total',
            'payment' => 'Payment terms', 'iban' => 'IBAN', 'bic' => 'BIC',
            'mentions' => 'Mandatory legal mentions (Luxembourg)',
            'mentions_text' => '· Luxembourg VAT number (LU + 8 digits)\\n· Continuous sequential numbering (LIVA Article 61)\\n· "Reverse charge - Article 21 LIVA" mention for B2B intra-EU\\n· 10-year retention mandatory',
            'footer' => 'Free template',
        ],
        'lb' => [
            'title' => 'Rechnung', 'from' => 'Emetteur', 'to' => 'Client',
            'name' => 'Numm / Firma', 'address' => 'Adress',
            'vat' => 'TVA-Nummer', 'matricule' => 'Matricule',
            'invoice_no' => 'Rechnungsnummer', 'date' => 'Datum', 'due' => 'Echéance',
            'desc' => 'Beschreiwung', 'qty' => 'Quantitéit', 'price' => 'Eenzelpräis HT',
            'vatrate' => 'TVA %', 'total_ht' => 'Total HT', 'subtotal' => 'Ënnertotal HT',
            'vat_total' => 'TVA', 'total' => 'Total TTC',
            'payment' => 'Bezuelungskonditiounen', 'iban' => 'IBAN', 'bic' => 'BIC',
            'mentions' => 'Obligatoresch gesetzlech Erwähnungen (Lëtzebuerg)',
            'mentions_text' => '· Lëtzebuerger TVA-Nummer (LU + 8 Zifferen)\\n· Kontinuéierlech sequentiell Numeréierung (Artikel 61 LIVA)\\n· Erwähnung "Autoliquidatioun - Artikel 21 LIVA" fir B2B-Operatiounen bannent der EU\\n· 10 Joer Opbewahrungspflicht',
            'footer' => 'Gratis Modell',
        ],
        'pt' => [
            'title' => 'Fatura', 'from' => 'Emissor', 'to' => 'Cliente',
            'name' => 'Nome / Razão social', 'address' => 'Morada',
            'vat' => 'Número de IVA', 'matricule' => 'Número de registo',
            'invoice_no' => 'Número da fatura', 'date' => 'Data', 'due' => 'Vencimento',
            'desc' => 'Descrição', 'qty' => 'Qtd', 'price' => 'Preço unitário líquido',
            'vatrate' => 'IVA %', 'total_ht' => 'Total líquido', 'subtotal' => 'Subtotal líquido',
            'vat_total' => 'IVA', 'total' => 'Total',
            'payment' => 'Condições de pagamento', 'iban' => 'IBAN', 'bic' => 'BIC',
            'mentions' => 'Menções legais obrigatórias (Luxemburgo)',
            'mentions_text' => '· Número de IVA luxemburguês (LU + 8 dígitos)\\n· Numeração sequencial contínua (Artigo 61 LIVA)\\n· Menção "Autoliquidação - Artigo 21 LIVA" para operações B2B intra-UE\\n· Conservação obrigatória 10 anos',
            'footer' => 'Modelo gratuito',
        ],
    ];
    $T = $L[$lang] ?? $L['fr'];
@endphp

<div class="header">
    <div class="invoice-title">{{ $T['title'] }}</div>
</div>

<div class="grid">
    <div class="col">
        <div class="label">{{ $T['from'] }}</div>
        <div class="label">{{ $T['name'] }}</div><div class="field"></div>
        <div class="label">{{ $T['address'] }}</div><div class="field"></div>
        <div class="label">{{ $T['vat'] }} / {{ $T['matricule'] }}</div><div class="field"></div>
    </div>
    <div class="col">
        <div class="label">{{ $T['to'] }}</div>
        <div class="label">{{ $T['name'] }}</div><div class="field"></div>
        <div class="label">{{ $T['address'] }}</div><div class="field"></div>
        <div class="label">{{ $T['vat'] }}</div><div class="field"></div>
    </div>
</div>

<div style="margin-top: 0.8em;">
    <div class="grid">
        <div class="col"><div class="label">{{ $T['invoice_no'] }}</div><div class="field"></div></div>
        <div class="col"><div class="label">{{ $T['date'] }} / {{ $T['due'] }}</div><div class="field"></div></div>
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
        @for ($i = 0; $i < 5; $i++)
            <tr><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

<table class="totals">
    <tr><td>{{ $T['subtotal'] }}</td><td style="text-align:right">_______ €</td></tr>
    <tr><td>{{ $T['vat_total'] }} 17%</td><td style="text-align:right">_______ €</td></tr>
    <tr class="total-row"><td>{{ $T['total'] }}</td><td style="text-align:right">_______ €</td></tr>
</table>

<div style="margin-top: 0.6em;">
    <div class="grid">
        <div class="col"><div class="label">{{ $T['iban'] }}</div><div class="field"></div></div>
        <div class="col"><div class="label">{{ $T['bic'] }} / {{ $T['payment'] }}</div><div class="field"></div></div>
    </div>
</div>

<div class="legal">
    <strong>{{ $T['mentions'] }} :</strong><br>
    {!! nl2br(str_replace('\\n', "\n", $T['mentions_text'])) !!}
</div>

<div class="footer">
    {{ $T['title'] }} - <a href="https://faktur.lu" class="powered-by">faktur.lu</a> - {{ $T['footer'] }}
</div>
</body>
</html>
