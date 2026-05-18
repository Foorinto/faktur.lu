<x-mail::message>
# {{ __('app.email_hr_event_invitation_title', ['title' => $event->title]) }}

{{ __('app.email_hr_event_invitation_greeting', ['name' => $recipientName]) }}

{{ __('app.email_hr_event_invitation_body') }}

**{{ __('app.hr_event_title') }}** : {{ $event->title }}
**{{ __('app.hr_event_starts_at') }}** : {{ $event->starts_at->isoFormat('LLLL') }}
**{{ __('app.hr_event_ends_at') }}** : {{ $event->ends_at->isoFormat('LLLL') }}

@if ($event->location_type === 'room' && $event->room)
**{{ __('app.hr_event_location') }}** : {{ $event->room->name }}@if ($event->room->location) — {{ $event->room->location }}@endif
@elseif ($event->location_type === 'address' && $event->address)
**{{ __('app.hr_event_location') }}** : {{ $event->address }}
@elseif ($event->location_type === 'video' && $event->video_url)
**{{ __('app.hr_event_location') }}** : [{{ $event->video_url }}]({{ $event->video_url }})
@endif

@if ($event->description)
---

{{ $event->description }}
@endif

{{ __('app.email_closing') }}
faktur.lu
</x-mail::message>
