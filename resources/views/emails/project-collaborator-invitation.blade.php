<x-mail::message>
# {{ __('app.email_project_collab_invitation_title', ['project' => $project->title]) }}

{{ __('app.email_project_collab_invitation_body', ['project' => $project->title]) }}

@if ($isNewUser)
{{ __('app.email_project_collab_invitation_new_user_hint') }}

<x-mail::button :url="route('password.request')">
{{ __('app.email_project_collab_invitation_setup_button') }}
</x-mail::button>
@else
<x-mail::button :url="route('login')">
{{ __('app.email_project_collab_invitation_login_button') }}
</x-mail::button>
@endif

{{ __('app.email_closing') }}
faktur.lu
</x-mail::message>
