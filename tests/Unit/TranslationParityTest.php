<?php

/**
 * pt_AO is maintained by hand (not via Crowdin), so guard it against drift:
 * every English key must exist, with the same placeholders and plural forms.
 */
function flattenTranslations(array $messages, string $prefix = ''): array
{
    $flat = [];

    foreach ($messages as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $flat += flattenTranslations($value, $path);
        } else {
            $flat[$path] = (string) $value;
        }
    }

    return $flat;
}

function translationPlaceholders(string $message): array
{
    preg_match_all('/\{\w+\}|(?<![\w\/]):[a-z_]+/', $message, $matches);
    $placeholders = $matches[0];
    sort($placeholders);

    return $placeholders;
}

function loadTranslations(string $locale): array
{
    return flattenTranslations(json_decode(file_get_contents(lang_path($locale.'.json')), true, flags: JSON_THROW_ON_ERROR));
}

test('pt_AO has every English key', function () {
    $missing = array_keys(array_diff_key(loadTranslations('en'), loadTranslations('pt_AO')));

    expect($missing)->toBe([]);
});

test('pt_AO keeps the placeholders and plural forms of each English message', function () {
    $english = loadTranslations('en');
    $portuguese = loadTranslations('pt_AO');

    $mismatched = [];

    foreach ($english as $key => $message) {
        if (! isset($portuguese[$key])) {
            continue;
        }

        $sameParams = translationPlaceholders($message) === translationPlaceholders($portuguese[$key]);
        $samePlurals = substr_count($message, '|') === substr_count($portuguese[$key], '|');

        if (! $sameParams || ! $samePlurals) {
            $mismatched[] = $key;
        }
    }

    expect($mismatched)->toBe([]);
});

test('pt_AO is offered in the language list', function () {
    expect(collect(config('invoiceshelf.languages'))->pluck('code'))->toContain('pt_AO');
});
