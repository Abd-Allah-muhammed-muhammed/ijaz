<?php

namespace App\Actions\Locale;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SwitchLocaleAction
{
    /**
     * Validates the locale against supported locales, applies it, and returns
     * the localized previous URL. Returns null when the locale is unsupported
     * (callers should redirect back).
     */
    public function handle(string $locale): ?string
    {
        if (! in_array($locale, array_keys(config('laravellocalization.supportedLocales')))) {
            return null;
        }

        LaravelLocalization::setLocale($locale);

        return LaravelLocalization::getLocalizedURL($locale, url()->previous());
    }
}
