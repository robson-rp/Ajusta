@if ($poweredBy = \App\Support\PoweredBy::clientState())
@lang('mail_sent_with', ['app' => '<a class="footer-link" href="'.e($poweredBy['url']).'" target="_blank">'.e($poweredBy['name']).'</a>'])
@endif
