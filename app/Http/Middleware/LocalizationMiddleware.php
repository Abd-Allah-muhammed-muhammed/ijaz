<?php

namespace App\Http\Middleware;

use App;
use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $locales = $request->header('Accept-Language', 'en');
        $supportedLocales = LaravelLocalization::getSupportedLanguagesKeys();
        $preferredLocale = collect(explode(',', $locales))
            ->map(function ($locale) {
                $parts = explode(';q=', $locale);
                $locale_ = strtolower(trim($parts[0]));

                return [
                    'locale' => explode('-', $locale_)[0] ?? $locale_,
                    'full' => $locale_,
                    'quality' => isset($parts[1]) ? (float) $parts[1] : 1.0,
                ];
            })
            ->sortDesc();
        $preferredLocale = $preferredLocale->firstWhere('locale', fn ($locale) => in_array($locale['locale'], $supportedLocales, true))['locale'] ?? 'en';

        App::setLocale($preferredLocale);

        return $next($request);
    }
}
