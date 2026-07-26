<?php

use App\Actions\Locale\SwitchLocaleAction;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

test('SwitchLocaleAction returns null for unsupported locale', function () {
    expect(app(SwitchLocaleAction::class)->handle('zz'))->toBeNull();
});

test('SwitchLocaleAction applies locale and returns localized previous URL', function () {
    $locales = array_keys(config('laravellocalization.supportedLocales'));
    expect($locales)->not->toBeEmpty();

    $locale = $locales[0];
    $previous = url('/previous-page');

    $this->from($previous);

    $url = app(SwitchLocaleAction::class)->handle($locale);

    expect($url)->toBe(LaravelLocalization::getLocalizedURL($locale, $previous))
        ->and(app()->getLocale())->toBe($locale);
});
