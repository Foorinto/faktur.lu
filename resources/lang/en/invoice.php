<?php

return [
    // Document titles
    'invoice' => 'Invoice',
    'quote' => 'Quote',
    'credit_note' => 'Credit Note',

    // Header
    'number' => 'No.',
    'date' => 'Date',
    'issue_date' => 'Issue Date',
    'due_date' => 'Due Date',
    'validity_date' => 'Valid Until',
    'reference' => 'Ref.',

    // Parties
    'from' => 'From',
    'to' => 'To',
    'client' => 'Client',
    'vat_number' => 'VAT No.',
    'rcs_number' => 'Reg. No.',
    'siret_number' => 'Reg. No.',

    // Items table
    'description' => 'Description',
    'unit' => 'Unit',
    'quantity' => 'Quantity',
    'unit_price' => 'Unit Price',
    'amount_ht' => 'Amount (excl. VAT)',
    'no_items' => 'No items',

    // Totals
    'subtotal' => 'Subtotal (excl. VAT)',
    'subtotal_ht' => 'Subtotal excl. VAT',
    'discount_line' => 'Discount',
    'vat' => 'VAT',
    'total' => 'Total (incl. VAT)',
    'net_total' => 'Amount Due',

    'deposit_paid' => "Deposit paid on :date",
    'payment_received' => "Payment received on :date",
    'remaining_due' => "Balance due",
    'fully_paid' => "Paid in full",
    'deposit_requested' => "Deposit on order",
    'deposit_balance' => "Balance on completion",
    'deposit_notice' => "The deposit is payable to confirm the order and secure the appointment. It will be deducted from the amount due on the invoice.",
    // Payment
    'payment_terms' => 'Payment Terms',
    'payment_delay' => 'Payment Period',
    'days' => 'days',
    'late_penalty' => 'Late Payment Penalty',
    'late_penalty_value' => '3 times the legal rate',
    'recovery_fee' => 'Fixed compensation for recovery costs',
    'discount' => 'Discount',
    'no_discount' => 'None',
    'payment_method' => 'Payment Method',
    'payment_instructions' => 'Payment instructions',
    'bank_transfer' => 'Bank Transfer',

    // Bank details
    'bank_details' => 'Bank Details',
    'bank' => 'Bank',
    'iban' => 'IBAN',
    'bic' => 'BIC',
    'scan_to_pay' => 'Scan to pay',

    // VAT mentions
    'vat_exempt' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979',
    'vat_exempt_franchise' => 'Tax exemption scheme',
    'vat_intra_community' => 'VAT exempt - Intra-community supply',
    'vat_reverse_charge' => 'Reverse charge mechanism applies',

    // VAT mentions (full text used on invoice PDF)
    'vat_mentions' => [
        'franchise' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 (VAT exemption scheme for small businesses)',
        'reverse_charge' => 'Reverse charge - Article 196 of Directive 2006/112/EC',
        'intra_eu' => 'VAT exempt - Intra-community supply (Art. 43 of the VAT Code)',
        'export' => 'VAT exempt - Export (Art. 43 of the VAT Code)',
    ],

    // Credit note
    'credit_note_reason' => 'Reason',
    'credit_note_cancels' => 'This credit note cancels invoice No.',
    'credit_note_partial' => 'This credit note partially cancels invoice No.',

    // Quote specific
    'quote_validity' => 'This quote is valid for',
    'quote_accept' => 'To accept this quote, please return it signed with "Approved".',
    'quote_info' => 'This quote is based on the information provided.',
    'quote_prices_valid' => 'The prices shown are valid for the duration mentioned above.',

    // Footer
    'thank_you' => 'Thank you for your business!',
    'page' => 'Page',
    'of' => 'of',

    // Retention guarantee
    'retention_guarantee' => 'Retention guarantee',
    'retention_releasable_from' => 'Retention releasable from :date',

    // Quote
    'authorization' => 'Authorization',
];
