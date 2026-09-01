<x-mail::message>
# {{ __('app.email_project_member_removed_title', ['project' => $project->title]) }}

{{ __('app.email_hr_event_invitation_greeting', ['name' => $recipientName]) }}

{{ __('app.email_project_member_removed_body', ['project' => $project->title]) }}

{{ __('app.email_closing') }}
{{ config('marque.nom') }}
</x-mail::message>
