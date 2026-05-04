<x-mail::message>
# @if($level === 1)
Lembrete de pagamento
@elseif($level === 2)
Aviso de pagamento
@else
Notificação formal
@endif

{{ $customMessage }}

## Detalhes da fatura

| | |
|:--|--:|
| **Número** | {{ $invoice->number }} |
| **Data de emissão** | {{ $invoice->issued_at?->format('d/m/Y') }} |
| **Data de vencimento** | {{ $invoice->due_at?->format('d/m/Y') }} |
| **Total com IVA** | {{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }} |
@if($daysOverdue > 0)
| **Atraso** | {{ $daysOverdue }} dia{{ $daysOverdue > 1 ? 's' : '' }} |
@endif

## Informações de pagamento

@if(!empty($seller['iban']))
- **IBAN:** `{{ $seller['iban'] }}`
@endif
@if(!empty($seller['bic']))
- **BIC:** {{ $seller['bic'] }}
@endif
- **Referência:** {{ $invoice->number }}

---

Com os melhores cumprimentos,

**{{ $seller['company_name'] ?? $seller['name'] ?? 'A equipa' }}**

@if(!empty($seller['email']))
{{ $seller['email'] }}
@endif
@if(!empty($seller['phone']))
| {{ $seller['phone'] }}
@endif

<x-mail::subcopy>
A fatura original segue em anexo a este email em formato PDF.
</x-mail::subcopy>
</x-mail::message>
