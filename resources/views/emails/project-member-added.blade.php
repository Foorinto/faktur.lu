<x-mail::message>
# {{ __('app.email_project_member_added_title', ['project' => $project->title]) }}

{{ __('app.email_hr_event_invitation_greeting', ['name' => $recipientName]) }}

{{ __('app.email_project_member_added_body', ['project' => $project->title]) }}

@if ($project->description)
---

{{ Str::limit(strip_tags($project->description), 300) }}
@endif

{{ __('app.email_closing') }}
faktur.lu
</x-mail::message>
