<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Récapitulatif fiscal {{ $year }}</title>
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
            font-size: 16pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2mm;
        }

        .subtitle {
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

        .summary-value.positive {
            color: #059669;
        }

        .summary-value.negative {
            color: #dc2626;
        }

        /* Section title */
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 3mm;
            margin-top: 6mm;
            padding-bottom: 2mm;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
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

        /* Disclaimer */
        .disclaimer {
            margin-top: 8mm;
            padding: 3mm;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            font-size: 7pt;
            color: #92400e;
            text-align: center;
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
                <div class="title">Récapitulatif fiscal</div>
                <div class="subtitle">Année {{ $year }}</div>
            </div>
            <div class="header-right">
                @if($settings)
                    <div class="company-name">{{ $settings->company_name }}</div>
                    <div class="company-info">
                        @if($settings->matricule)
                            Matricule: {{ $settings->matricule }}<br>
                        @endif
                        @if($settings->vat_number)
                            N° TVA: {{ $settings->vat_number }}
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- KPI Summary -->
        <div class="summary">
            <div class="summary-box">
                <div class="summary-label">CA HT</div>
                <div class="summary-value">{{ number_format($summary['revenue']['total_ht'], 2, ',', ' ') }} €</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Dépenses HT</div>
                <div class="summary-value">{{ number_format($summary['expenses']['total_ht'], 2, ',', ' ') }} €</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">TVA nette</div>
                <div class="summary-value">{{ number_format($summary['vat']['balance'], 2, ',', ' ') }} €</div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Bénéfice imposable</div>
                <div class="summary-value {{ $summary['net_profit'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($summary['net_profit'], 2, ',', ' ') }} €
                </div>
            </div>
        </div>

        <!-- Monthly Revenue -->
        <div class="section-title">Revenus mensuels</div>
        <table>
            <thead>
                <tr>
                    <th>Mois</th>
                    <th class="right">CA HT</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = [1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
                @endphp
                @foreach($summary['revenue']['by_month'] as $month => $amount)
                <tr>
                    <td>{{ $months[$month] }}</td>
                    <td class="number">{{ number_format($amount, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total ({{ $summary['revenue']['invoice_count'] }} facture{{ $summary['revenue']['invoice_count'] > 1 ? 's' : '' }})</td>
                    <td class="number">{{ number_format($summary['revenue']['total_ht'], 2, ',', ' ') }} €</td>
                </tr>
            </tfoot>
        </table>

        <!-- Expenses by Category -->
        <div class="section-title">Dépenses par catégorie</div>
        <table>
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>Ligne Form. 152</th>
                    <th class="right">HT</th>
                    <th class="right">TVA déd.</th>
                    <th class="right">Nb</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summary['expenses']['by_category'] as $cat => $data)
                <tr>
                    <td>{{ __('app.expense_categories.' . $cat) }}</td>
                    <td style="font-size: 7pt; color: #6b7280;">{{ $data['form152_label'] }}</td>
                    <td class="number">{{ number_format($data['total_ht'], 2, ',', ' ') }} €</td>
                    <td class="number">{{ number_format($data['total_vat'], 2, ',', ' ') }} €</td>
                    <td class="right">{{ $data['count'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 5mm; color: #6b7280;">
                        Aucune dépense enregistrée.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(count($summary['expenses']['by_category']) > 0)
            <tfoot>
                <tr>
                    <td colspan="2">Total</td>
                    <td class="number">{{ number_format($summary['expenses']['total_ht'], 2, ',', ' ') }} €</td>
                    <td class="number">{{ number_format($summary['expenses']['total_vat_deductible'], 2, ',', ' ') }} €</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>

        <!-- VAT Summary -->
        <div class="section-title">Récapitulatif TVA</div>
        <table>
            <tbody>
                <tr>
                    <td>TVA collectée (factures)</td>
                    <td class="number">{{ number_format($summary['vat']['collected'], 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td>TVA déductible (dépenses)</td>
                    <td class="number">{{ number_format($summary['vat']['deductible'], 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td>Solde TVA</td>
                    <td class="number" style="color: {{ $summary['vat']['balance'] >= 0 ? '#059669' : '#dc2626' }};">
                        {{ number_format($summary['vat']['balance'], 2, ',', ' ') }} €
                    </td>
                </tr>
            </tfoot>
        </table>

        @if(count($summary['revenue']['vat_breakdown']) > 0)
        <div class="section-title">Ventilation TVA par taux</div>
        <table>
            <thead>
                <tr>
                    <th>Taux</th>
                    <th class="right">Base HT</th>
                    <th class="right">Montant TVA</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary['revenue']['vat_breakdown'] as $vat)
                <tr>
                    <td>{{ number_format($vat['rate'], 0) }}%</td>
                    <td class="number">{{ number_format($vat['base'], 2, ',', ' ') }} €</td>
                    <td class="number">{{ number_format($vat['amount'], 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <!-- Disclaimer -->
        <div class="disclaimer">
            ⚠ Document de synthèse à usage interne — non importable directement dans taxx.lu ou MyGuichet.lu.
            Utilisez les montants ci-dessus pour remplir manuellement votre déclaration ou transmettez ce document à votre comptable.
            Ce récapitulatif ne remplace pas un avis professionnel.
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Document généré le {{ $generatedAt->format('d/m/Y à H:i') }} | Page <span class="page-number"></span>
    </div>
</body>
</html>
