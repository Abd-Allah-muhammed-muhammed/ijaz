<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/**
 * Login-only: set App locale from the URL segment or Referer for admin login.
 *
 * Scoped exclusively to dashboard guest login routes. Do not register on the
 * shared dashboard locale group — that overrides session('locale') on every
 * authenticated AJAX request when Referer differs (notifications, device-tokens, etc.).
 *
 * Needed because Wayfinder emits unprefixed /dashboard/login while the login
 * page lives at /{locale}/dashboard/login; without this, __() falls back to
 * config('app.locale') for auth.failed.
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
