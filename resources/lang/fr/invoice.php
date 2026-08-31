<?php

return [
    // Document titles
    'invoice' => 'Facture',
    'quote' => 'Devis',
    'credit_note' => 'Avoir',

    // Header
    'number' => 'N°',
    'date' => 'Date',
    'issue_date' => 'Date d\'émission',
    'due_date' => 'Date d\'exigibilité du paiement',
    'validity_date' => 'Valide jusqu\'au',
    'reference' => 'Réf.',

    // Parties
    'from' => 'De',
    'to' => 'À',
    'client' => 'Client',
    'vat_number' => 'N° TVA',
    'rcs_number' => 'N° RCS',
    'siret_number' => 'N° RCS/SIRET',

    // Items table
    'description' => 'Désignation et description',
    'unit' => 'Unité',
    'quantity' => 'Quantité',
    'unit_price' => 'Prix unitaire',
    'amount_ht' => 'Montant HT',
    'no_items' => 'Aucun article',

    // Totals
    'subtotal' => 'Total HT',
    'subtotal_ht' => 'Sous-total HT',
    'discount_line' => 'Remise',
    'vat' => 'TVA',
    'total' => 'Total TTC',
    'net_total' => 'Net à payer',

    'deposit_paid' => "Acompte versé le :date",
    'payment_received' => "Règlement du :date",
    'remaining_due' => "Reste à payer",
    'fully_paid' => "Facture soldée",
    'deposit_requested' => "Acompte à la commande",
    'deposit_balance' => "Solde à la prestation",
    'deposit_notice' => "L'acompte est à régler pour valider la commande et fixer le rendez-vous. Il sera déduit du montant à payer sur la facture.",
    // Payment
    'payment_terms' => 'Conditions de paiement',
    'payment_delay' => 'Délai de paiement',
    'days' => 'jours',
    'late_penalty' => 'Pénalité de retard',
    'late_penalty_value' => '3 fois le taux légal',
    'recovery_fee' => 'Indemnité forfaitaire pour frais de recouvrement',
    'discount' => 'Escompte',
    'no_discount' => 'Aucun',
    'payment_method' => 'Moyens de paiement',
    'payment_instructions' => 'Instructions de paiement',
    'bank_transfer' => 'Virement',

    // Bank details
    'bank_details' => 'Relevé d\'identité Bancaire',
    'bank' => 'Banque',
    'iban' => 'IBAN',
    'bic' => 'BIC',
    'scan_to_pay' => 'Scannez pour payer',

    // VAT mentions
    'vat_exempt' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979',
    'vat_exempt_franchise' => 'Régime de franchise de taxe',
    'vat_intra_community' => 'Exonération de TVA - Livraison intracommunautaire',
    'vat_reverse_charge' => 'Autoliquidation de la TVA par le preneur',

    // VAT mentions (full text used on invoice PDF)
    'vat_mentions' => [
        'franchise' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 (Régime de franchise de taxe)',
        'reverse_charge' => 'Autoliquidation - Article 196 de la directive 2006/112/CE',
        'intra_eu' => 'Exonération de TVA - Livraison intracommunautaire (Art. 43 du Code de la TVA)',
        'export' => 'Exonération de TVA - Exportation (Art. 43 du Code de la TVA)',
    ],

    // Credit note
    'credit_note_reason' => 'Motif',
    'credit_note_cancels' => 'Cet avoir annule la facture N°',
    'credit_note_partial' => 'Cet avoir annule partiellement la facture N°',

    // Quote specific
    'quote_validity' => 'Ce devis est valable',
    'quote_accept' => 'Pour accepter ce devis, veuillez nous le retourner signé avec la mention "Bon pour accord".',
    'quote_info' => 'Ce devis est établi sur la base des informations communiquées.',
    'quote_prices_valid' => 'Les prix indiqués sont valables pour la durée mentionnée ci-dessus.',

    // Footer
    'thank_you' => 'Merci de votre confiance !',
    'page' => 'Page',
    'of' => 'sur',

    // Retention guarantee
    'retention_guarantee' => 'Retenue de garantie',
    'retention_releasable_from' => 'Retenue libérable à compter du :date',

    // Quote
    'authorization' => 'Autorisation',
];
