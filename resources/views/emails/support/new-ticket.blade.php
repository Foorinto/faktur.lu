<x-mail::message>
# {{ __('app.email_support_new_ticket_heading') }}

{{ __('app.email_support_new_ticket_intro') }}

**{{ __('app.email_support_reference') }}** {{ $ticket->reference }}

**{{ __('app.email_support_from') }}** {{ $ticket->user->name }} ({{ $ticket->user->email }})

**{{ __('app.email_support_category') }}** {{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}

**{{ __('app.email_support_subject') }}** {{ $ticket->subject }}

---

**{{ __('app.email_support_message') }}**

{{ $messageContent }}

---

<x-mail::button :url="$adminUrl">
{{ __('app.email_support_cta_view_ticket') }}
</x-mail::button>

{{ __('app.email_regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
