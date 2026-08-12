<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

/**
 * @return array{locale: string, message: string}
 */
dataset('admin_login_failure_locales', [
    'ur' => ['ur', 'یہ اسناد ہمارے ریکارڈ سے مطابقت نہیں رکھتیں۔'],
    'hi' => ['hi', 'ये प्रमाण-पत्र हमारे रिकॉर्ड से मेल नहीं खाते।'],
    'en' => ['en', 'These credentials do not match our records.'],
]);

beforeEach(function (): void {
    // Production default is Arabic — without URL/Referer locale wiring the
    // auth.failed message would always resolve to this string.
    config(['app.locale' => 'ar']);
    app()->setLocale('ar');

    // Keep redirect middlewares off so the Wayfinder-style unprefixed POST
    // reaches authenticate(); SetLocaleFromRequest still runs on the route group.
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();
});

test('admin login failure returns the validation error message in the URL-prefixed locale (ur/hi/en), not hardcoded Arabic', function (string $locale, string $message): void {
    $admin = Admin::query()->create([
        'name' => 'Locale Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('correct-password'),
        'language' => 'en',
        'address' => 'Riyadh',
        'job' => 'Admin',
    ]);

    // from() sets the previous URL for redirects; Referer is what the browser
    // (and SetLocaleFromRequest) use for the locale-prefixed login page.
    $localizedLoginUrl = url("/{$locale}/dashboard/login");

    $response = $this->from("/{$locale}/dashboard/login")
        ->withHeader('Referer', $localizedLoginUrl)
        ->post('/dashboard/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

    $response->assertSessionHasErrors(['email' => $message]);

    expect(session('errors')?->getBag('default')?->first('email'))
        ->toBe($message)
        ->toBe(__('auth.failed', [], $locale))
        ->not->toBe(__('auth.failed', [], 'ar'));
})->with('admin_login_failure_locales');
