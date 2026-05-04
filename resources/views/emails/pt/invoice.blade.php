<x-mail::message>
# {{ $isCreditNote ? 'Nota de Crédito' : 'Fatura' }} {{ $invoice->number }}

Bom dia,

@if($customMessage)
{{ $customMessage }}

@else
@if($isCreditNote)
Em anexo encontra-se a nota de crédito N.º **{{ $invoice->number }}**.
@else
Em anexo encontra-se a fatura N.º **{{ $invoice->number }}** no valor de **{{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }}**.
@endif
@endif

@if(!$isCreditNote)
## Detalhes da fatura

| | |
|:--|--:|
| **Data de emissão** | {{ $invoice->issued_at?->format('d/m/Y') }} |
| **Data de vencimento** | {{ $invoice->due_at?->format('d/m/Y') }} |
| **Valor sem IVA** | {{ number_format($invoice->total_ht, 2, ',', ' ') }} {{ $invoice->currency }} |
| **IVA** | {{ number_format($invoice->total_vat, 2, ',', ' ') }} {{ $invoice->currency }} |
| **Total com IVA** | {{ number_format($invoice->total_ttc, 2, ',', ' ') }} {{ $invoice->currency }} |

## Informações de pagamento

@if(!empty($seller['iban']))
- **IBAN:** `{{ $seller['iban'] }}`
@endif
@if(!empty($seller['bic']))
- **BIC:** {{ $seller['bic'] }}
@endif
- **Referência:** {{ $invoice->number }}
@endif

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
Esta mensagem foi enviada automaticamente. A fatura segue em anexo a este email em formato PDF.
</x-mail::subcopy>
</x-mail::message>
