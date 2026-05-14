<x-mail::message>
# {{ __('app.email_support_reply_heading') }}

{{ __('app.email_greeting_name', ['name' => $ticket->user->name]) }}

{!! __('app.email_support_reply_received', ['reference' => $ticket->reference]) !!}

**{{ __('app.email_support_reply_subject') }}** {{ $ticket->subject }}

---

{!! nl2br(e($replyContent)) !!}

---

<x-mail::button :url="$ticketUrl">
{{ __('app.email_support_reply_cta') }}
</x-mail::button>

{{ __('app.email_support_reply_history') }}

{{ __('app.email_regards') }}<br>
{{ __('app.email_support_team_signature', ['app' => config('app.name')]) }}
</x-mail::message>
