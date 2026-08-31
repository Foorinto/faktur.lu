<?php

return [
    // Document titles
    'invoice' => 'Fatura',
    'quote' => 'Orçamento',
    'credit_note' => 'Nota de crédito',

    // Header
    'number' => 'N.º',
    'date' => 'Data',
    'issue_date' => 'Data de emissão',
    'due_date' => 'Data de vencimento do pagamento',
    'validity_date' => 'Válido até',
    'reference' => 'Ref.',

    // Parties
    'from' => 'De',
    'to' => 'Para',
    'client' => 'Cliente',
    'vat_number' => 'N.º IVA',
    'rcs_number' => 'N.º RCS',
    'siret_number' => 'N.º RCS/SIRET',

    // Items table
    'description' => 'Designação e descrição',
    'unit' => 'Unidade',
    'quantity' => 'Quantidade',
    'unit_price' => 'Preço unitário',
    'amount_ht' => 'Valor sem IVA',
    'no_items' => 'Nenhum artigo',

    // Totals
    'subtotal' => 'Total sem IVA',
    'subtotal_ht' => 'Subtotal sem IVA',
    'discount_line' => 'Desconto',
    'vat' => 'IVA',
    'total' => 'Total com IVA',
    'net_total' => 'Valor líquido a pagar',

    'deposit_paid' => "Adiantamento pago em :date",
    'payment_received' => "Pagamento de :date",
    'remaining_due' => "Valor em falta",
    'fully_paid' => "Fatura liquidada",
    // Payment
    'payment_terms' => 'Condições de pagamento',
    'payment_delay' => 'Prazo de pagamento',
    'days' => 'dias',
    'late_penalty' => 'Penalização por atraso',
    'late_penalty_value' => '3 vezes a taxa legal',
    'recovery_fee' => 'Indemnização forfetária por despesas de cobrança',
    'discount' => 'Desconto',
    'no_discount' => 'Nenhum',
    'payment_method' => 'Meios de pagamento',
    'payment_instructions' => 'Instruções de pagamento',
    'bank_transfer' => 'Transferência bancária',

    // Bank details
    'bank_details' => 'Identificação bancária',
    'bank' => 'Banco',
    'iban' => 'IBAN',
    'bic' => 'BIC',
    'scan_to_pay' => 'Digitalize para pagar',

    // VAT mentions
    'vat_exempt' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979',
    'vat_exempt_franchise' => 'Regime de isenção de imposto',
    'vat_intra_community' => 'Isenção de IVA - Entrega intracomunitária',
    'vat_reverse_charge' => 'Autoliquidação do IVA pelo adquirente',

    // VAT mentions (full text used on invoice PDF)
    'vat_mentions' => [
        'franchise' => 'TVA non applicable - Article 57bis de la loi modifiée du 12 février 1979 (Regime de isenção de imposto)',
        'reverse_charge' => 'Autoliquidação - Artigo 196.º da Diretiva 2006/112/CE',
        'intra_eu' => 'Isenção de IVA - Entrega intracomunitária (Art. 43.º do Código do IVA)',
        'export' => 'Isenção de IVA - Exportação (Art. 43.º do Código do IVA)',
    ],

    // Credit note
    'credit_note_reason' => 'Motivo',
    'credit_note_cancels' => 'Esta nota de crédito anula a fatura N.º',
    'credit_note_partial' => 'Esta nota de crédito anula parcialmente a fatura N.º',

    // Quote specific
    'quote_validity' => 'Este orçamento é válido',
    'quote_accept' => 'Para aceitar este orçamento, devolva-o assinado com a menção "Aprovado".',
    'quote_info' => 'Este orçamento foi elaborado com base nas informações comunicadas.',
    'quote_prices_valid' => 'Os preços indicados são válidos pelo período mencionado acima.',

    // Footer
    'thank_you' => 'Obrigado pela sua confiança!',
    'page' => 'Página',
    'of' => 'de',

    // Retention guarantee
    'retention_guarantee' => 'Retenção de garantia',
    'retention_releasable_from' => 'Retenção libertável a partir de :date',

    // Quote
    'authorization' => 'Autorização',
];
