<?php

use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Support\Notifications\TicketCreatedNotification;

/**
 * Proves SetLocaleFromRequest must NOT run on authenticated dashboard AJAX
 * routes — otherwise a differing Referer overrides session('locale').
 */
test('an authenticated admin with a saved session locale keeps that locale on an unprefixed AJAX request to a non-login dashboard route (e.g. notifications.mark-as-read), even when the Referer differs', function (): void {
    // Disable redirect middlewares so the unprefixed AJAX URL is handled in-place.
    // SetLocaleFromRequest must NOT apply here (login-only scope); if it were still
    // on the shared dashboard group, Referer would override session locale to en.
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    $admin = createSupportDashboardAdmin();

    $notification = DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => TicketCreatedNotification::class,
        'notifiable_type' => $admin->getMorphClass(),
        'notifiable_id' => $admin->getKey(),
        'data' => [
            'title_translated_key' => 'support_ticket_created_title',
            'body_translated_key' => 'support_ticket_created_body',
            'translated_attributes' => [],
            'ticket_support_id' => 1,
        ],
        'read_at' => null,
    ]);

    app()->setLocale('ar');

    $this->actingAs($admin, 'admin')
        ->withSession(['locale' => 'ar'])
        ->withHeader('Referer', url('/en/dashboard'))
        ->postJson(route('dashboard.notifications.mark-as-read', $notification))
        ->assertSuccessful();

    expect(app()->getLocale())
        ->toBe('ar')
        ->not->toBe('en');
});
