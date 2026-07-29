<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    config(['app.debug' => false]);
    $this->withoutVite();
});

test('web 404 renders the Inertia ErrorPage with correct status', function () {
    $this->get('/definitely-missing-inertia-error-page-xyz')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page
            ->component('Errors/ErrorPage')
            ->where('status', 404)
            ->where('title', __('error_404_title'))
            ->where('message', __('error_404_message'))
        );
});

test('web 403 renders the Inertia ErrorPage with correct status', function () {
    Route::middleware('web')->get('/__inertia-error-probe/forbidden', function () {
        throw new HttpException(403);
    });

    $this->get('/__inertia-error-probe/forbidden')
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page
            ->component('Errors/ErrorPage')
            ->where('status', 403)
            ->where('title', __('error_403_title'))
            ->where('message', __('error_403_message'))
        );
});

test('api 404 still returns JSON, not the Inertia error page', function () {
    $response = $this->getJson('/api/v1/definitely-missing-api-resource-xyz')
        ->assertNotFound();

    expect($response->headers->get('content-type'))->toContain('application/json')
        ->and($response->getContent())->not->toContain('Errors/ErrorPage')
        ->and($response->json('component'))->toBeNull();
});

test('local environment with debug on still shows default error handling', function () {
    config(['app.debug' => true]);

    $response = $this->get('/definitely-missing-inertia-error-page-debug-xyz')
        ->assertNotFound();

    expect($response->getContent())->not->toContain('Errors/ErrorPage');
});

test('error page translations resolve for all supported locales', function (string $locale) {
    app()->setLocale($locale);

    expect(__('error_404_title'))->not->toBe('error_404_title')
        ->and(__('error_404_message'))->not->toBe('error_404_message')
        ->and(__('error_403_title'))->not->toBe('error_403_title')
        ->and(__('error_403_message'))->not->toBe('error_403_message')
        ->and(__('back_to_home'))->not->toBe('back_to_home');
})->with(['en', 'ar', 'hi', 'ur']);

test('switching locale produces translated error page props for 404 and 403', function (string $locale, int $status) {
    config(['app.debug' => false]);

    if ($status === 404) {
        $response = $this->get("/{$locale}/definitely-missing-localized-error-page-xyz");
    } else {
        Route::middleware('web')->get('/__inertia-error-probe/forbidden-'.$locale, function () use ($locale) {
            app()->setLocale($locale);
            throw new HttpException(403);
        });

        $response = $this->get('/__inertia-error-probe/forbidden-'.$locale);
    }

    app()->setLocale($locale);

    $response
        ->assertStatus($status)
        ->assertInertia(fn ($page) => $page
            ->component('Errors/ErrorPage')
            ->where('status', $status)
            ->where('title', __('error_'.$status.'_title'))
            ->where('message', __('error_'.$status.'_message'))
        );
})->with([
    ['ar', 404],
    ['hi', 404],
    ['ur', 404],
    ['ar', 403],
    ['en', 403],
]);
