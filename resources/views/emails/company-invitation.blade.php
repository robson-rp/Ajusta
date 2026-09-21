<x-mail::message>
# {{ __('You\'ve been invited!') }}

{!! __('**:inviter** has invited you to join **:company** as **:role**.', ['inviter' => e($inviterName), 'company' => e($companyName), 'role' => e($roleName)]) !!}

@if($hasAccount)
{{ __('Log in to accept the invitation:') }}
@else
{{ __('Create your account to get started:') }}
@endif

<x-mail::button :url="$acceptUrl">
{{ $hasAccount ? __('Log In & Accept') : __('Create Account & Accept') }}
</x-mail::button>

{!! __('If you don\'t want to join, you can <a href=":url">decline this invitation</a>.', ['url' => e($declineUrl)]) !!}

{{ __('This invitation will expire in 7 days.') }}

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
