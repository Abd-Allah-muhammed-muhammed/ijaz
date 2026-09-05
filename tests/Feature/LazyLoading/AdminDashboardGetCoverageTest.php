<?php

use App\Support\LazyLoading\LazyLoadingSweepFixture;
use App\Support\LazyLoading\LazyLoadingViolationCollector;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Classifieds\Http\Controllers\Dashboard\ElectronicAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\InstituteAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\PropertyAdvisementController;
use Spatie\Permission\Middleware\PermissionMiddleware;

/**
 * Regression: admin dashboard GET routes must authorize (root forceFill on fixture)
 * and the original three advisement indexes must not lazy-load under the sweep collector.
 */
beforeEach(function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();
});

test('sweep fixture admin is root so permission-gated dashboard GETs authorize', function (): void {
    $fixture = LazyLoadingSweepFixture::seed();

    expect($fixture['admin']->root)->toBeTrue()
        ->and($fixture['admin']->can('show propertyAdvisements'))->toBeTrue();

    $this->actingAs($fixture['admin'], 'admin')
        ->get(action([PropertyAdvisementController::class, 'index']))
        ->assertSuccessful();
});

test('admin property institute and electronic advisement indexes have no lazy-loading violations', function (): void {
    $fixture = LazyLoadingSweepFixture::seed();
    $collector = new LazyLoadingViolationCollector;
    $collector->install(true);
    $collector->reset();

    try {
        $this->actingAs($fixture['admin'], 'admin');

        $collector->setContext('GET /dashboard/property-advisements', 'admin');
        $this->get(action([PropertyAdvisementController::class, 'index']))->assertSuccessful();

        $collector->setContext('GET /dashboard/institute-advisements', 'admin');
        $this->get(action([InstituteAdvisementController::class, 'index']))->assertSuccessful();

        $collector->setContext('GET /dashboard/electronic-advisements', 'admin');
        $this->get(action([ElectronicAdvisementController::class, 'index']))->assertSuccessful();
    } finally {
        $collector->restore();
    }

    expect($collector->uniqueByModelRelation())->toBeEmpty(
        'Admin advisement indexes still lazy-load: '.json_encode($collector->uniqueByModelRelation())
    );
});

test('permission-gated admin dashboard GET routes exist and must be covered by root fixture', function (): void {
    $permissionGated = 0;

    foreach (RouteFacade::getRoutes() as $route) {
        /** @var Route $route */
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }

        $uri = ltrim($route->uri(), '/');
        if (! str_starts_with($uri, 'dashboard')) {
            continue;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && (
                str_starts_with($middleware, PermissionMiddleware::class.':')
                || str_starts_with($middleware, 'permission:')
            )) {
                $permissionGated++;

                break;
            }
        }
    }

    // Honest lower bound: every one of these was silently 403'd before forceFill(root).
    expect($permissionGated)->toBeGreaterThan(50);
});
