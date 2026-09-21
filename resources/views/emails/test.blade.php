@component('mail::message')
# {{ __('Test Email from :app', ['app' => config('app.name')]) }}

{{ $my_message }}

@endcomponent
