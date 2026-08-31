<?php

return [
    // Document titles
    'invoice' => 'Rechnung',
    'quote' => 'Devis',
    'credit_note' => 'Gutschrëft',

    // Header
    'number' => 'Nr.',
    'date' => 'Datum',
    'issue_date' => 'Ausstelungsdatum',
    'due_date' => 'Fällegkeetsdatum',
    'validity_date' => 'Gëlteg bis',
    'reference' => 'Ref.',

    // Parties
    'from' => 'Vun',
    'to' => 'Un',
    'client' => 'Client',
    'vat_number' => 'TVA Nr.',
    'rcs_number' => 'RCS Nr.',
    'siret_number' => 'RCS/SIRET Nr.',

    // Items table
    'description' => 'Bezeechnung a Beschreiwung',
    'unit' => 'Eenheet',
    'quantity' => 'Quantitéit',
    'unit_price' => 'Eenheetspräis',
    'amount_ht' => 'Montant HT',
    'no_items' => 'Keng Artikelen',

    // Totals
    'subtotal' => 'Total HT',
    'subtotal_ht' => 'Ënnersumme HT',
    'discount_line' => 'Remise',
    'vat' => 'TVA',
    'total' => 'Total TTC',
    'net_total' => 'Ze bezuelen',

    'deposit_paid' => "Akonto bezuelt den :date",
    'payment_received' => "Bezuelung vum :date",
    'remaining_due' => "Reschtbetrag",
    'fully_paid' => "Ganz bezuelt",
    // Payment
    'payment_terms' => 'Bezuelungskonditiounen',
    'payment_delay' => 'Bezuelungsfrist',
    'days' => 'Deeg',
    'late_penalty' => 'Verspéidungszënsen',
    'late_penalty_value' => '3 Mol den legalen Taux',
    'recovery_fee' => 'Pauschal Entschiedegung fir Réclamatiounskäschten',
    'discount' => 'Remise',
    'no_discount' => 'Keen',
    'payment_method' => 'Bezuelungsmethod',
    'payment_instructions' => 'Bezuelinstruktiounen',
    'bank_transfer' => 'Iwwerweisung',

    // Bank details
    'bank_details' => 'Bankverbindung',
    'bank' => 'Bank',
    'iban' => 'IBAN',
    'bic' => 'BIC',
    'scan_to_pay' => 'Scannt fir ze bezuelen',

    // VAT mentions
    'vat_exempt' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979',
    'vat_exempt_franchise' => 'Steierbefreiungsregelung',
    'vat_intra_community' => 'TVA-befreit - Innergemeinschaftlech Liwwerung',
    'vat_reverse_charge' => 'Steierulaaschter vum Leeschtungsempfänger',

    // VAT mentions (full text used on invoice PDF)
    'vat_mentions' => [
        'franchise' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 (Steierbefreiungsregelung)',
        'reverse_charge' => 'Steierulaaschter vum Leeschtungsempfänger - Artikel 196 vun der Richtlinn 2006/112/EG',
        'intra_eu' => 'TVA-befreit - Innergemeinschaftlech Liwwerung (Art. 43 vum TVA Code)',
        'export' => 'TVA-befreit - Export (Art. 43 vum TVA Code)',
    ],

    // Credit note
    'credit_note_reason' => 'Grond',
    'credit_note_cancels' => 'Dës Gutschrëft stornéiert d\'Rechnung Nr.',
    'credit_note_partial' => 'Dës Gutschrëft stornéiert deelweis d\'Rechnung Nr.',

    // Quote specific
    'quote_validity' => 'Dësen Devis ass gëlteg fir',
    'quote_accept' => 'Fir dësen Devis unzehuelen, schéckt en eis w.e.g. ënnerschriwwen mat "Accord" zréck.',
    'quote_info' => 'Dësen Devis baséiert op den iwwermëttelten Informatiounen.',
    'quote_prices_valid' => 'D\'Präisser sinn fir déi uewe genannten Dauer gëlteg.',

    // Footer
    'thank_you' => 'Merci fir Äert Vertrauen!',
    'page' => 'Säit',
    'of' => 'vun',

    // Retention guarantee
    'retention_guarantee' => 'Garantieabzog',
    'retention_releasable_from' => 'Abzog fräiginn vum :date',

    // Quote
    'authorization' => 'Erlaabnis',
];
