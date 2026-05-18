<x-mail::message>
# {{ $isSameDay ? __('app.email_hr_event_reminder_today_title', ['title' => $event->title]) : __('app.email_hr_event_reminder_tomorrow_title', ['title' => $event->title]) }}

{{ __('app.email_hr_event_invitation_greeting', ['name' => $recipientName]) }}

{{ $isSameDay ? __('app.email_hr_event_reminder_today_body') : __('app.email_hr_event_reminder_tomorrow_body') }}

**{{ __('app.hr_event_title') }}** : {{ $event->title }}
**{{ __('app.hr_event_starts_at') }}** : {{ $event->starts_at->isoFormat('LLLL') }}

@if ($event->location_type === 'room' && $event->room)
**{{ __('app.hr_event_location') }}** : {{ $event->room->name }}
@elseif ($event->location_type === 'address' && $event->address)
**{{ __('app.hr_event_location') }}** : {{ $event->address }}
@elseif ($event->location_type === 'video' && $event->video_url)
**{{ __('app.hr_event_location') }}** : [{{ $event->video_url }}]({{ $event->video_url }})
@endif

{{ __('app.email_closing') }}
faktur.lu
</x-mail::message>
