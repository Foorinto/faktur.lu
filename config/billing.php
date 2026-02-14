<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency used for invoices and financial calculations.
    | Luxembourg uses EUR as its official currency.
    |
    */

    'default_currency' => env('DEFAULT_CURRENCY', 'EUR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | List of currencies supported for client billing.
    |
    */

    'supported_currencies' => ['EUR', 'USD', 'GBP', 'CHF'],

    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | Default country for new businesses. VAT rates, thresholds and legal
    | mentions are loaded from config/countries.php based on this setting.
    | Supported: LU, FR, BE, DE
    |
    */

    'default_country' => env('BUSINESS_COUNTRY', 'LU'),

    /*
    |--------------------------------------------------------------------------
    | Supported Countries
    |--------------------------------------------------------------------------
    |
    | List of countries where faktur.lu can be used.
    | Full configuration in config/countries.php
    |
    */

    'supported_countries' => ['LU', 'FR', 'BE', 'DE'],

    /*
    |--------------------------------------------------------------------------
    | VAT Rates
    |--------------------------------------------------------------------------
    |
    | DEPRECATED: Use config('countries.XX.vat_rates') instead.
    | Kept for backward compatibility with existing code.
    | These are Luxembourg rates - will be removed in future version.
    |
    */

    'vat_rates' => [
        'standard' => 17.00,
        'intermediate' => 14.00,
        'reduced' => 8.00,
        'super_reduced' => 3.00,
    ],

    'default_vat_rate' => 17.00,

    /*
    |--------------------------------------------------------------------------
    | VAT Franchise Threshold
    |--------------------------------------------------------------------------
    |
    | DEPRECATED: Use config('countries.XX.franchise.threshold') instead.
    | Kept for backward compatibility.
    |
    */

    'vat_franchise_threshold' => (int) env('VAT_FRANCHISE_THRESHOLD', 35000),

    /*
    |--------------------------------------------------------------------------
    | Simplified Accounting Threshold
    |--------------------------------------------------------------------------
    |
    | Annual revenue threshold below which simplified accounting
    | rules apply in Luxembourg.
    | As of 2024: 100,000 EUR
    |
    */

    'simplified_accounting_threshold' => (int) env('SIMPLIFIED_ACCOUNTING_THRESHOLD', 100000),

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Format
    |--------------------------------------------------------------------------
    |
    | Format for generating invoice numbers.
    | {YEAR} will be replaced with the current year
    | {NUMBER} will be replaced with the sequential number (padded)
    |
    */

    'invoice_number_format' => '{YEAR}-{NUMBER}',
    'invoice_number_padding' => 3, // Results in 001, 002, etc.

    /*
    |--------------------------------------------------------------------------
    | Default Payment Terms
    |--------------------------------------------------------------------------
    |
    | Default number of days before an invoice is due.
    |
    */

    'default_payment_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Late Payment Interest Rate
    |--------------------------------------------------------------------------
    |
    | Annual interest rate applied to late payments (in percentage).
    | Luxembourg law allows up to 8% for B2B transactions.
    |
    */

    'late_payment_interest_rate' => 8.00,

    /*
    |--------------------------------------------------------------------------
    | Legal Mentions
    |--------------------------------------------------------------------------
    |
    | Required legal mentions for Luxembourg invoices.
    |
    */

    'legal_mentions' => [
        'vat_franchise' => 'Exonéré de TVA - Article 57, alinéa 1 du Code de la TVA luxembourgeois (régime de franchise de taxe)',
        'late_payment' => 'En cas de retard de paiement, des intérêts de :rate% l\'an seront appliqués conformément à la loi.',
    ],

];
