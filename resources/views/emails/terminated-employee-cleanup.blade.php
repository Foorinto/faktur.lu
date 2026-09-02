<x-mail::message>
# {{ __('app.email_terminated_cleanup_title') }}

{{ __('app.email_terminated_cleanup_body', ['name' => trim($employee->first_name.' '.$employee->last_name)]) }}

@if (count($projects) > 0)
**{{ __('app.email_terminated_cleanup_projects_list') }} ({{ count($projects) }})** :

@foreach ($projects as $project)
- {{ $project->title }}
@endforeach
@endif

---

{{ __('app.email_terminated_cleanup_portal_note') }}

{{ __('app.email_closing') }}
{{ config('marque.nom') }}
</x-mail::message>
