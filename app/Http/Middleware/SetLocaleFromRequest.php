<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Ensure App::setLocale() matches the URL (or Referer) locale for web requests.
 *
 * mcamara's route-group prefix calls LaravelLocalization::setLocale() while
 * routes are loading, which works when the request path is /{locale}/....
 * Wayfinder-generated form actions are unprefixed (/dashboard/login), so that
 * side effect never sees the locale and App falls back to config('app.locale').
 * The named localization middlewares (localeSessionRedirect, etc.) do not call
 * App::setLocale() for those unprefixed POSTs either.
 */
class SetLocaleFromRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        $supportedLocales = LaravelLocalization::getSupportedLanguagesKeys();
        $locale = $this->localeFromSegment($request->segment(1), $supportedLocales)
            ?? $this->localeFromReferer($request, $supportedLocales);

        if ($locale !== null) {
            App::setLocale($locale);
        }

        return $next($request);
    }

    /**
     * @param  list<string>  $supportedLocales
     */
    private function localeFromSegment(mixed $segment, array $supportedLocales): ?string
    {
        if (! is_string($segment) || $segment === '') {
            return null;
        }

        return in_array($segment, $supportedLocales, true) ? $segment : null;
    }

    /**
     * @param  list<string>  $supportedLocales
     */
    private function localeFromReferer(Request $request, array $supportedLocales): ?string
    {
        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $path = parse_url($referer, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $segment = explode('/', trim($path, '/'))[0] ?? null;

        return $this->localeFromSegment($segment, $supportedLocales);
    }
}
