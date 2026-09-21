<x-mail::message>
@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($buttonLabel && $buttonUrl)
<x-mail::button :url="$buttonUrl">
{{ $buttonLabel }}
</x-mail::button>
@endif

{{ __('Thanks') }},<br>
{{ config('app.name') }}
</x-mail::message>
