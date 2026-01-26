<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Devis {{ $quote->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #333;
        }

        .container {
            padding: 15px 25px;
        }

        /* Top header with title and logo */
        .top-header {
            width: 100%;
            margin-bottom: 10px;
            position: relative;
        }

        .title-section {
            display: inline-block;
            vertical-align: top;
        }

        .logo-section {
            position: absolute;
            top: 0;
            right: 0;
        }

        .logo-section img {
            max-width: 120px;
            max-height: 60px;
        }

        /* Document title */
        .document-title {
            font-size: 20pt;
            font-weight: bold;
            color: #7c3aed;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .document-number {
            font-size: 10pt;
            font-weight: bold;
            color: #333;
        }

        /* Header with parties */
        .header-section {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .seller-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .buyer-info {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 30px;
        }

        .company-name {
            font-weight: bold;
            font-size: 9pt;
            margin-bottom: 1px;
        }

        .company-details {
            font-size: 8pt;
            color: #333;
            line-height: 1.3;
        }

        .date-row {
            margin-top: 6px;
        }

        .date-label {
            color: #7c3aed;
            font-weight: bold;
        }

        .date-value {
            font-weight: bold;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            margin-bottom: 18px;
        }

        .items-table th {
            background-color: #7c3aed;
            color: white;
            padding: 5px 4px;
            text-align: left;
            font-size: 7pt;
            font-weight: normal;
        }

        .items-table th:first-child {
            width: 20px;
            text-align: center;
        }

        .items-table th.col-designation {
            width: auto;
        }

        .items-table th.col-unit {
            width: 50px;
            text-align: center;
        }

        .items-table th.col-qty {
            width: 45px;
            text-align: center;
        }

        .items-table th.col-price {
            width: 70px;
            text-align: right;
        }

        .items-table th.col-total {
            width: 75px;
            text-align: right;
        }

        .items-table td {
            padding: 6px 4px;
            border-bottom: 1px solid #e5e5e5;
            font-size: 8pt;
            vertical-align: top;
        }

        .items-table td:first-child {
            text-align: center;
            color: #666;
        }

        .items-table td.text-center {
            text-align: center;
        }

        .items-table td.text-right {
            text-align: right;
        }

        .item-title {
            font-weight: bold;
            color: #7c3aed;
        }

        .item-description {
            color: #666;
            font-size: 7pt;
            margin-top: 1px;
            line-height: 1.2;
        }

        /* Bottom section with conditions and totals */
        .bottom-section {
            display: table;
            width: 100%;
            margin-top: 8px;
        }

        .conditions-column {
            display: table-cell;
            width: 55%;
            vertical-align: top;
            padding-right: 20px;
        }

        .totals-column {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        /* Conditions */
        .condition-item {
            margin-bottom: 3px;
        }

        .condition-label {
            font-weight: bold;
            font-size: 7pt;
            color: #333;
        }

        .condition-value {
            font-size: 7pt;
            color: #666;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
            margin-top: 5px;
        }

        .status-draft {
            background-color: #e5e7eb;
            color: #374151;
        }

        .status-sent {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .status-accepted {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-declined {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Totals */
        .totals-box {
            border: 1px solid #e5e5e5;
        }

        .total-row {
            display: table;
            width: 100%;
            border-bottom: 1px solid #e5e5e5;
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .total-label {
            display: table-cell;
            padding: 5px 8px;
            font-weight: bold;
            background-color: #f8f8f8;
            width: 50%;
            font-size: 8pt;
        }

        .total-value {
            display: table-cell;
            padding: 5px 8px;
            text-align: right;
            font-weight: bold;
            width: 50%;
            font-size: 8pt;
        }

        /* VAT notice */
        .vat-notice {
            font-size: 7pt;
            color: #666;
            margin-top: 5px;
            font-style: italic;
            line-height: 1.2;
        }

        /* Notes section */
        .notes-section {
            margin-top: 12px;
        }

        .thanks-message {
            font-size: 9pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .notes-content {
            font-size: 7pt;
            color: #666;
            line-height: 1.2;
        }

        .notes-content a {
            color: #7c3aed;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 10px;
            left: 25px;
            right: 25px;
            text-align: right;
            font-size: 7pt;
            color: #999;
        }

        .page-number {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Top Header with Title and Logo -->
        <div class="top-header">
            <div class="title-section">
                <div class="document-title">Devis</div>
                <div class="document-number">Réf. {{ $quote->reference ?? 'BROUILLON' }}</div>
            </div>
            @if(!empty($logoPath))
                <div class="logo-section">
                    <img src="{{ $logoPath }}" alt="Logo">
                </div>
            @endif
        </div>

        <!-- Header with seller and buyer -->
        <div class="header-section">
            <div class="seller-info">
                <div class="company-name">{{ $seller['company_name'] ?? $seller['name'] ?? '' }}</div>
                @if(!empty($seller['website']))
                    <div class="company-details">{{ $seller['website'] }}</div>
                @endif
                <div class="company-details">
                    {{ $seller['address'] ?? '' }}<br>
                    {{ $seller['postal_code'] ?? '' }} {{ $seller['city'] ?? '' }} {{ $seller['country'] ?? '' }}
                </div>
                @if(!empty($seller['matricule']))
                    <div class="company-details">N° Matricule : {{ $seller['matricule'] }}</div>
                @endif
                @if(($seller['vat_regime'] ?? '') === 'assujetti' && !empty($seller['vat_number']))
                    <div class="company-details">N° TVA : {{ $seller['vat_number'] }}</div>
                @endif

                <div class="date-row">
                    <span class="date-label">Date d'émission</span>
                    <span class="date-value">{{ $quote->created_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
                </div>
                <div class="date-row">
                    <span class="date-label">Valide jusqu'au</span>
                    <span class="date-value">{{ $quote->valid_until?->format('d/m/Y') ?? '-' }}</span>
                </div>
            </div>

            <div class="buyer-info">
                <div class="company-name">{{ $buyer['company_name'] ?? $buyer['name'] ?? '' }}</div>
                @if(!empty($buyer['contact_name']))
                    <div class="company-details">{{ $buyer['contact_name'] }}</div>
                @endif
                <div class="company-details">
                    {{ $buyer['address'] ?? '' }}<br>
                    {{ $buyer['postal_code'] ?? '' }} {{ $buyer['city'] ?? '' }} {{ $buyer['country'] ?? '' }}
                </div>
                @if(!empty($buyer['registration_number']))
                    <div class="company-details">N° RCS/SIRET : {{ $buyer['registration_number'] }}</div>
                @endif
                @if(!empty($buyer['vat_number']))
                    <div class="company-details">N° TVA : {{ $buyer['vat_number'] }}</div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th class="col-designation">Désignation et description</th>
                    <th class="col-unit">Unité</th>
                    <th class="col-qty">Quantité</th>
                    <th class="col-price">Prix unitaire</th>
                    <th class="col-total">Montant HT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="item-title">{{ $item->title }}</div>
                            @if($item->description)
                                <div class="item-description">{!! nl2br(e($item->description)) !!}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->unit_label ?? '-' }}</td>
                        <td class="text-center">{{ $item->quantity == (int)$item->quantity ? (int)$item->quantity : number_format($item->quantity, 2, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2, ',', ' ') }} €</td>
                        <td class="text-right">{{ number_format($item->total_ht, 2, ',', ' ') }} €</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                            Aucun article
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Bottom Section -->
        <div class="bottom-section">
            <div class="conditions-column">
                <div class="condition-item">
                    <div class="condition-label">Validité du devis</div>
                    <div class="condition-value">
                        @if($quote->valid_until)
                            Jusqu'au {{ $quote->valid_until->format('d/m/Y') }}
                        @else
                            30 jours
                        @endif
                    </div>
                </div>

                <div class="condition-item">
                    <div class="condition-label">Conditions de paiement</div>
                    <div class="condition-value">À réception de facture, sous 30 jours</div>
                </div>

                <div class="condition-item">
                    <div class="condition-label">Moyens de paiement acceptés</div>
                    <div class="condition-value">Virement bancaire</div>
                </div>

                @if($quote->notes)
                    <div class="condition-item" style="margin-top: 10px;">
                        <div class="condition-label">Conditions particulières</div>
                        <div class="condition-value">{!! nl2br(e($quote->notes)) !!}</div>
                    </div>
                @endif
            </div>

            <div class="totals-column">
                <div class="totals-box">
                    <div class="total-row">
                        <span class="total-label">Total HT</span>
                        <span class="total-value">{{ number_format($quote->total_ht ?? 0, 2, ',', ' ') }} €</span>
                    </div>
                    @if(!$isVatExempt)
                        @foreach($vatSummary as $vat)
                            <div class="total-row">
                                <span class="total-label">TVA {{ $vat['rate'] }}%</span>
                                <span class="total-value">{{ number_format($vat['vat'], 2, ',', ' ') }} €</span>
                            </div>
                        @endforeach
                        <div class="total-row">
                            <span class="total-label">Total TTC</span>
                            <span class="total-value">{{ number_format($quote->total_ttc ?? 0, 2, ',', ' ') }} €</span>
                        </div>
                    @endif
                </div>

                @if($isVatExempt)
                    <div class="vat-notice">
                        TVA non applicable, art. 57 du Code de la TVA luxembourgeois<br>
                        (Régime de franchise de taxe)
                    </div>
                @endif
            </div>
        </div>

        <!-- Notes Section -->
        <div class="notes-section">
            <div class="thanks-message">Merci de votre confiance !</div>
            <div class="notes-content">
                Ce devis est établi sur la base des informations communiquées.
                Les prix indiqués sont valables pour la durée mentionnée ci-dessus.
                Pour accepter ce devis, veuillez nous le retourner signé avec la mention "Bon pour accord".
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="page-number">1/1</div>
    </div>
</body>
</html>
