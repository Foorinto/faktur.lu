<!DOCTYPE html>
<html lang="{{ $locale ?? 'fr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $isCreditNote ? __('invoice.credit_note') : __('invoice.invoice') }} {{ $invoice->number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: {{ $fontSize(8) }};
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .page-wrapper {
            position: relative;
            padding-bottom: 40px;
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
            /* Taille réglable indépendamment du texte : la lisibilité d'un
               logo tient à sa forme, pas à la police qui l'entoure. De 90x45
               à 204x102 px selon le réglage. */
            max-width: {{ round(120 * ($logoScale ?? 1)) }}px;
            max-height: {{ round(60 * ($logoScale ?? 1)) }}px;
        }

        /* Document title */
        .document-title {
            font-size: {{ $fontSize(20) }};
            font-weight: bold;
            color: {{ $pdfColor ?? '#7c3aed' }};
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .document-number {
            font-size: {{ $fontSize(10) }};
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
            font-size: {{ $fontSize(9) }};
            margin-bottom: 1px;
        }

        .company-details {
            font-size: {{ $fontSize(8) }};
            color: #333;
            line-height: 1.3;
        }

        .date-row {
            margin-top: 6px;
        }

        .date-label {
            color: {{ $pdfColor ?? '#7c3aed' }};
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
            background-color: {{ $pdfColor ?? '#7c3aed' }};
            color: white;
            padding: 5px 4px;
            text-align: left;
            font-size: {{ $fontSize(7) }};
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
            font-size: {{ $fontSize(8) }};
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
            color: {{ $pdfColor ?? '#7c3aed' }};
        }

        .item-description {
            color: #666;
            font-size: {{ $fontSize(7) }};
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
            font-size: {{ $fontSize(7) }};
            color: #333;
        }

        .condition-value {
            font-size: {{ $fontSize(7) }};
            color: #666;
        }

        /* Bank details */
        .bank-section {
            margin-top: 8px;
            padding-top: 5px;
        }

        .bank-title {
            font-weight: bold;
            font-size: {{ $fontSize(8) }};
            margin-bottom: 3px;
        }

        .bank-row {
            display: table;
            width: 100%;
            font-size: {{ $fontSize(7) }};
            margin-bottom: 1px;
        }

        .bank-label {
            display: table-cell;
            width: 40px;
            color: #666;
        }

        .bank-value {
            display: table-cell;
            font-weight: bold;
        }

        /* Payment QR Codes */
        .qr-payment-wrapper {
            margin-top: 8px;
        }

        .qr-payment-codes {
            display: block;
        }

        .qr-payment-item {
            display: inline-block;
            text-align: center;
            vertical-align: top;
            margin-right: 10px;
        }

        .qr-payment-item img {
            width: 90px;
            height: 90px;
        }

        .qr-payment-label {
            font-size: {{ $fontSize(6) }};
            color: #666;
            margin-top: 2px;
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
            font-size: {{ $fontSize(8) }};
        }

        .total-value {
            display: table-cell;
            padding: 5px 8px;
            text-align: right;
            font-weight: bold;
            width: 50%;
            font-size: {{ $fontSize(8) }};
        }

        /* VAT notice */
        .vat-notice {
            font-size: {{ $fontSize(7) }};
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
            font-size: {{ $fontSize(9) }};
            color: #333;
            margin-bottom: 5px;
        }
        .thanks-message p { margin: 0 0 3px; }
        .thanks-message ul, .thanks-message ol { margin: 0 0 3px; padding-left: 16px; }
        .thanks-message a { color: {{ $pdfColor ?? '#7c3aed' }}; }
        .payment-instructions a { color: {{ $pdfColor ?? '#7c3aed' }}; }
        .payment-instructions ul, .payment-instructions ol { margin: 2px 0; padding-left: 14px; }
        .payment-instructions p { margin: 0 0 2px; }

        .notes-content {
            font-size: {{ $fontSize(7) }};
            color: #666;
            line-height: 1.2;
        }

        .notes-content a {
            color: {{ $pdfColor ?? '#7c3aed' }};
        }

        /* Credit note reference */
        .credit-note-reference {
            background-color: #fef3c7;
            padding: 5px 10px;
            margin-bottom: 10px;
            font-size: {{ $fontSize(8) }};
            color: #92400e;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 10px;
            left: 25px;
            right: 25px;
            font-size: {{ $fontSize(7) }};
            color: #9ca3af;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-branding {
            display: table-cell;
            text-align: center;
            width: 100%;
        }

        .footer-branding a {
            color: {{ $pdfColor ?? '#7c3aed' }};
            text-decoration: none;
        }

        .footer-page {
            position: absolute;
            right: 0;
            top: 0;
        }
    </style>
</head>
<body>
<div class="page-wrapper">
    <div class="container">
        <!-- Top Header with Title and Logo -->
        <div class="top-header">
            <div class="title-section">
                <div class="document-title">{{ $isCreditNote ? __('invoice.credit_note') : __('invoice.invoice') }}</div>
                <div class="document-number">{{ __('invoice.number') }} {{ $invoice->number ?? $invoice->display_number }}</div>
            </div>
            @if(!empty($logoPath))
                <div class="logo-section">
                    <img src="{{ $logoPath }}" alt="Logo">
                </div>
            @endif
        </div>

        @if($isCreditNote && $invoice->credit_note_for)
            <div class="credit-note-reference">
                @if($invoice->credit_note_reason && isset(\App\Models\Invoice::CREDIT_NOTE_REASONS[$invoice->credit_note_reason]))
                    <strong>{{ __('invoice.credit_note_reason') }} :</strong> {{ \App\Models\Invoice::CREDIT_NOTE_REASONS[$invoice->credit_note_reason] }}<br>
                @endif
                {{ $invoice->items->count() < ($invoice->originalInvoice->items->count() ?? 0) ? __('invoice.credit_note_partial') : __('invoice.credit_note_cancels') }} {{ $invoice->originalInvoice->number ?? 'N/A' }}
            </div>
        @endif

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
                @if(!empty($seller['matricule']) && ($seller['country_code'] ?? 'LU') !== 'LU')
                    <div class="company-details">{{ config('countries.'.($seller['country_code'] ?? 'LU').'.fiscal_identifiers.primary.label', '') }} {{ $seller['matricule'] }}</div>
                @endif
                @if(!empty($seller['rcs_number']))
                    <div class="company-details">{{ __('invoice.rcs_number') }} {{ $seller['rcs_number'] }}</div>
                @endif
                @if(!empty($seller['vat_number']))
                    <div class="company-details">{{ __('invoice.vat_number') }} {{ $seller['vat_number'] }}</div>
                @endif
                @if(!empty($seller['show_email_on_invoice']) && !empty($seller['email']))
                    <div class="company-details">{{ $seller['email'] }}</div>
                @endif
                @if(!empty($seller['show_phone_on_invoice']) && !empty($seller['phone']))
                    <div class="company-details">{{ $seller['phone'] }}</div>
                @endif

                <div class="date-row">
                    <span class="date-label">{{ __('invoice.issue_date') }}</span>
                    <span class="date-value">{{ $invoice->issued_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
                </div>
                <div class="date-row">
                    <span class="date-label">{{ __('invoice.due_date') }}</span>
                    <span class="date-value">{{ $invoice->due_at?->format('d/m/Y') ?? '-' }}</span>
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
                    <div class="company-details">{{ __('invoice.siret_number') }} {{ $buyer['registration_number'] }}</div>
                @endif
                @if(!empty($buyer['vat_number']))
                    <div class="company-details">{{ __('invoice.vat_number') }} {{ $buyer['vat_number'] }}</div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th class="col-designation">{{ __('invoice.description') }}</th>
                    <th class="col-unit">{{ __('invoice.unit') }}</th>
                    <th class="col-qty">{{ __('invoice.quantity') }}</th>
                    <th class="col-price">{{ __('invoice.unit_price') }}</th>
                    <th class="col-total">{{ __('invoice.amount_ht') }}</th>
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
                        <td class="text-right">
                            {{ number_format($item->unit_price, 2, ',', ' ') }} €
                            @if((float) $item->discount_value > 0)
                                <div style="font-size: 0.85em; color: #92400e;">
                                    @if($item->discount_type === 'amount')
                                        −{{ number_format($item->discount_value, 2, ',', ' ') }} €
                                    @else
                                        −{{ rtrim(rtrim(number_format($item->discount_value, 2, ',', ' '), '0'), ',') }} %
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-right">
                            @if((float) $item->discount_value > 0)
                                <s style="color: #94a3b8; font-size: 0.8em;">{{ number_format($item->quantity * $item->unit_price, 2, ',', ' ') }} €</s><br>
                            @endif
                            {{ number_format($item->total_ht, 2, ',', ' ') }} €
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                            {{ __('invoice.no_items') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Bottom Section -->
        <div class="bottom-section">
            <div class="conditions-column">
                @php
                    $paymentDays = 30;
                    if ($invoice->due_at && $invoice->issued_at) {
                        $paymentDays = $invoice->issued_at->diffInDays($invoice->due_at);
                    }
                    $showPaymentConditions = !isset($seller['show_payment_conditions']) || $seller['show_payment_conditions'];
                    $latePenaltyText = !empty($seller['late_penalty_text']) ? $seller['late_penalty_text'] : __('invoice.late_penalty_value');
                    $recoveryFeeAmount = (isset($seller['recovery_fee_amount']) && $seller['recovery_fee_amount'] !== null) ? (float) $seller['recovery_fee_amount'] : 40;
                    $discountTerms = !empty($seller['discount_terms']) ? $seller['discount_terms'] : __('invoice.no_discount');
                @endphp

                <div class="condition-item">
                    <div class="condition-label">{{ __('invoice.payment_delay') }}</div>
                    <div class="condition-value">{{ $paymentDays }} {{ __('invoice.days') }}</div>
                </div>

                @if($showPaymentConditions)
                <div class="condition-item">
                    <div class="condition-label">{{ __('invoice.late_penalty') }}</div>
                    <div class="condition-value">{{ $latePenaltyText }}</div>
                </div>

                <div class="condition-item">
                    <div class="condition-label">{{ __('invoice.recovery_fee') }}</div>
                    <div class="condition-value">{{ rtrim(rtrim(number_format($recoveryFeeAmount, 2, ',', ' '), '0'), ',') }} €</div>
                </div>

                <div class="condition-item">
                    <div class="condition-label">{{ __('invoice.discount') }}</div>
                    <div class="condition-value">{{ $discountTerms }}</div>
                </div>
                @endif

                @php
                    // FEAT-098 : la facture peut porter ses propres moyens de
                    // paiement ; sinon elle suit le réglage d'entreprise, puis
                    // le virement. La cascade vit dans le modèle, pas ici.
                    $paymentMethodKeys = $invoice->effectivePaymentMethods();
                    $paymentMethodKnown = \App\Models\BusinessSettings::PAYMENT_METHODS;
                    $paymentMethodLabels = array_map(
                        fn ($k) => in_array($k, $paymentMethodKnown, true) ? __('app.payment_methods.' . $k) : $k,
                        $paymentMethodKeys
                    );
                @endphp
                <div class="condition-item">
                    <div class="condition-label">{{ __('invoice.payment_method') }}</div>
                    <div class="condition-value">{{ implode(', ', $paymentMethodLabels) }}</div>
                </div>

                @if(!empty($seller['payment_instructions']))
                    <div class="condition-item">
                        <div class="condition-label">{{ __('invoice.payment_instructions') }}</div>
                        <div class="condition-value payment-instructions">{!! \App\Support\HtmlSanitizer::clean($seller['payment_instructions']) !!}</div>
                    </div>
                @endif

                @if(!$isCreditNote && (!empty($seller['iban']) || !empty($seller['bic'])))
                    <div class="bank-section">
                        <div class="bank-title">{{ __('invoice.bank_details') }}</div>
                        @if(!empty($seller['bank_name']))
                            <div class="bank-row">
                                <span class="bank-label">{{ __('invoice.bank') }}</span>
                                <span class="bank-value">{{ $seller['bank_name'] }}</span>
                            </div>
                        @endif
                        @if(!empty($seller['iban']))
                            <div class="bank-row">
                                <span class="bank-label">{{ __('invoice.iban') }}</span>
                                <span class="bank-value">{{ $seller['iban'] }}</span>
                            </div>
                        @endif
                        @if(!empty($seller['bic']))
                            <div class="bank-row">
                                <span class="bank-label">{{ __('invoice.bic') }}</span>
                                <span class="bank-value">{{ $seller['bic'] }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                @if(!empty($paymentQrCode) || !empty($customPaymentQrCode))
                    <div class="qr-payment-wrapper">
                        <div class="qr-payment-codes">
                            @if(!empty($customPaymentQrCode))
                                <div class="qr-payment-item">
                                    <img src="{{ $customPaymentQrCode }}" alt="QR Code paiement" />
                                    <div class="qr-payment-label">{{ __('invoice.scan_to_pay') }}</div>
                                </div>
                            @endif
                            @if(!empty($paymentQrCode))
                                <div class="qr-payment-item">
                                    <img src="{{ $paymentQrCode }}" alt="QR Code virement" />
                                    <div class="qr-payment-label">{{ __('invoice.bank_transfer') }} — {{ __('invoice.scan_to_pay') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="totals-column">
                <div class="totals-box">
                    @php $hasDiscounts = count($discounts ?? []) > 0; @endphp
                    <div class="total-row">
                        <span class="total-label">{{ $hasDiscounts ? __('invoice.subtotal_ht') : __('invoice.subtotal') }}</span>
                        <span class="total-value">{{ number_format($hasDiscounts ? ($subtotalHt ?? 0) : ($invoice->total_ht ?? 0), 2, ',', ' ') }} €</span>
                    </div>
                    @foreach(($discounts ?? []) as $discount)
                        @php
                            $dv = (float) $discount->value;
                            $damount = $discount->type === 'amount' ? $dv : ((float) ($subtotalHt ?? 0) * $dv / 100);
                        @endphp
                        <div class="total-row">
                            <span class="total-label">{{ $discount->label ?: __('invoice.discount_line') }}@if($discount->type === 'percent') ({{ rtrim(rtrim(number_format($dv, 2, ',', ' '), '0'), ',') }} %)@endif</span>
                            <span class="total-value">- {{ number_format($damount, 2, ',', ' ') }} €</span>
                        </div>
                    @endforeach
                    @if($hasDiscounts)
                        <div class="total-row">
                            <span class="total-label">{{ __('invoice.subtotal') }}</span>
                            <span class="total-value">{{ number_format($invoice->total_ht ?? 0, 2, ',', ' ') }} €</span>
                        </div>
                    @endif
                    @if(!$isVatExempt)
                        @foreach($vatSummary as $vat)
                            <div class="total-row">
                                <span class="total-label">{{ __('invoice.vat') }} {{ $vat['rate'] }}%</span>
                                <span class="total-value">{{ number_format($vat['vat'], 2, ',', ' ') }} €</span>
                            </div>
                        @endforeach
                    @endif
                    <div class="total-row" style="border-top: 2px solid #1e293b; padding-top: 8px; margin-top: 4px;">
                        <span class="total-label" style="font-weight: 700; font-size: {{ $fontSize(11) }};">{{ __('invoice.total') }}</span>
                        <span class="total-value" style="font-weight: 700; font-size: {{ $fontSize(11) }};">{{ number_format($invoice->total_ttc ?? 0, 2, ',', ' ') }} €</span>
                    </div>
                    {{--
                        Encaissements déjà reçus (FEAT-114).

                        ⚠️ Le bloc vit SOUS le total, jamais au-dessus : un
                        acompte n'est pas une remise. Une remise réduirait la
                        base taxable et donc la TVA ; ici le prix ne change pas,
                        seul le moment du paiement change. Le total TTC et la
                        TVA restent ceux de la prestation entière.
                    --}}
                    @if(count($encaissements ?? []) > 0)
                        @foreach($encaissements as $encaissement)
                            <div class="total-row" style="margin-top: 4px;">
                                <span class="total-label">{{ $encaissement['libelle'] }}</span>
                                <span class="total-value">- {{ number_format($encaissement['montant'], 2, ',', ' ') }} €</span>
                            </div>
                        @endforeach
                        <div class="total-row" style="border-top: 2px solid #1e293b; padding-top: 8px; margin-top: 4px;">
                            <span class="total-label" style="font-weight: 700; font-size: {{ $fontSize(11) }};">
                                {{ ($resteAPayer ?? 0) > 0 ? __('invoice.remaining_due') : __('invoice.fully_paid') }}
                            </span>
                            <span class="total-value" style="font-weight: 700; font-size: {{ $fontSize(11) }};">{{ number_format($resteAPayer ?? 0, 2, ',', ' ') }} €</span>
                        </div>
                    @endif

                    @if($invoice->retention_guarantee_rate && $invoice->retention_guarantee_rate > 0)
                        <div class="total-row" style="margin-top: 8px;">
                            <span class="total-label">{{ __('invoice.retention_guarantee') }} ({{ number_format($invoice->retention_guarantee_rate, 0) }}%)</span>
                            <span class="total-value">- {{ number_format($invoice->retention_guarantee_amount ?? 0, 2, ',', ' ') }} €</span>
                        </div>
                        <div class="total-row" style="border-top: 2px solid #1e293b; padding-top: 8px; margin-top: 4px;">
                            <span class="total-label" style="font-weight: 700; font-size: {{ $fontSize(11) }};">{{ __('invoice.net_total') }}</span>
                            <span class="total-value" style="font-weight: 700; font-size: {{ $fontSize(11) }};">{{ number_format(($invoice->total_ttc ?? 0) - ($invoice->retention_guarantee_amount ?? 0), 2, ',', ' ') }} €</span>
                        </div>
                        @if($invoice->retention_release_date)
                            <div style="margin-top: 6px; font-size: {{ $fontSize(7.5) }}; color: #64748b;">
                                {{ __('invoice.retention_releasable_from', ['date' => $invoice->retention_release_date->format('d/m/Y')]) }}
                            </div>
                        @endif
                    @endif
                </div>

                @if($invoice->effective_vat_mention)
                    <div class="vat-notice">
                        {!! nl2br(e($invoice->effective_vat_mention)) !!}
                    </div>
                @endif
            </div>
        </div>

        <!-- Notes Section -->
        <div class="notes-section">
            @if($invoice->effective_footer_message)
                <div class="thanks-message">{!! \App\Support\HtmlSanitizer::clean($invoice->effective_footer_message) !!}</div>
            @endif

            @if($invoice->notes)
                <div class="notes-content">
                    {!! nl2br(e($invoice->notes)) !!}
                </div>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            @if($showBranding ?? false)
                <div class="footer-branding">
                    {!! __('app.pdf_branding_text', ['app' => '<a href="https://'.config('marque.domaine').'">'.config('marque.nom').'</a>']) !!}
                </div>
            @endif
            <div class="footer-page">{{ __('invoice.page') }} 1/1</div>
        </div>
    </div>
</div>
</body>
</html>
