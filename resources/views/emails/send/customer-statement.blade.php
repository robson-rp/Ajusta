@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => ''])
            {{ $data['customer']->company->name }}
        @endcomponent
    @endslot

    @slot('subcopy')
        @component('mail::subcopy')
            {!! $data['body'] !!}
        @endcomponent
    @endslot

    @slot('footer')
        @component('mail::footer')
            @lang('mail_sent_with', ['app' => config('app.name')])
        @endcomponent
    @endslot
@endcomponent
