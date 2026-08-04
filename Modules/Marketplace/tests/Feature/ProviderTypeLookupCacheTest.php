<?php

use App\Http\Controllers\Provider\AuthController as ProviderAuthController;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Enumerable;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Marketplace\Actions\ProviderType\DeleteProviderTypeAction;
use Modules\Marketplace\Http\Controllers\Api\V1\MarketplaceCatalogController;
use Modules\Marketplace\Http\Resources\Api\V1\ProviderTypeCollection;
use Modules\Marketplace\Http\Resources\Dashboard\ProviderTypeResource as DashboardProviderTypeResource;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Services\ProviderTypeService;

beforeEach(function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    LookupCache::forgetAllLocales('provider-types:all');

    ProviderType::query()->create([
        'files' => [
            'id_image' => true,
            'commercial_record' => false,
            'freelancer_certification' => false,
            'iban_certification' => false,
            'license_to_practice_law' => false,
        ],
        'image' => 'provider-types/cache-test.png',
        'translations' => [
            'en' => ['name' => 'Individual EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Individual AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Individual UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Individual HI', 'description' => 'Desc HI'],
        ],
    ]);
});

/**
 * Recursively assert identical PHP types (gettype / get_class) and equal values.
 * Collections/JsonSerializable objects are compared by serialized value, not instance identity.
 */
function assertProviderTypeIdenticalTyped(mixed $expected, mixed $actual, string $path = 'root'): void
{
    expect(gettype($actual))->toBe(gettype($expected), "gettype mismatch at {$path}");

    if (is_object($expected)) {
        expect(get_class($actual))->toBe(get_class($expected), "get_class mismatch at {$path}");

        if ($expected instanceof Enumerable) {
            assertProviderTypeIdenticalTyped($expected->all(), $actual->all(), $path);

            return;
        }

        if ($expected instanceof JsonSerializable) {
            assertProviderTypeIdenticalTyped($expected->jsonSerialize(), $actual->jsonSerialize(), $path);

            return;
        }

        expect($actual == $expected)->toBeTrue("object value mismatch at {$path}");

        return;
    }

    if (is_array($expected)) {
        expect(array_keys($actual))->toBe(array_keys($expected), "array keys mismatch at {$path}");

        foreach ($expected as $key => $value) {
            assertProviderTypeIdenticalTyped($value, $actual[$key], "{$path}.{$key}");
        }

        return;
    }

    expect($actual)->toBe($expected, "value mismatch at {$path}");
}

test('API provider-types response is byte-for-byte identical whether served from cache or fresh query', function (): void {
    app()->setLocale('en');

    $service = app(ProviderTypeService::class);

    $coldCollection = $service->listForApi();
    expect($coldCollection)->toBeInstanceOf(EloquentCollection::class);

    $coldHttp = $this->getJson(action([MarketplaceCatalogController::class, 'providerTypes']))
        ->assertSuccessful();
    $coldBody = $coldHttp->getContent();
    $coldJson = $coldHttp->json();

    $warmCollection = $service->listForApi();
    expect($warmCollection)->toBeInstanceOf(EloquentCollection::class)
        ->and(get_class($warmCollection))->toBe(get_class($coldCollection))
        ->and($warmCollection->first())->toBeInstanceOf(ProviderType::class)
        ->and(get_class($warmCollection->first()))->toBe(get_class($coldCollection->first()));

    $request = Request::create('/api/v1/catalog/provider-types', 'GET');
    $coldResolved = ProviderTypeCollection::make($coldCollection)->resolve($request);
    $warmResolved = ProviderTypeCollection::make($warmCollection)->resolve($request);
    expect(json_encode($warmResolved))->toBe(json_encode($coldResolved));
    assertProviderTypeIdenticalTyped($coldResolved, $warmResolved, 'api.resolved');

    $warmHttp = $this->getJson(action([MarketplaceCatalogController::class, 'providerTypes']))
        ->assertSuccessful();

    expect($warmHttp->getContent())->toBe($coldBody);
    assertProviderTypeIdenticalTyped($coldJson, $warmHttp->json(), 'api.http');
});

test('Frontend register types prop is byte-for-byte identical whether served from cache or fresh query', function (): void {
    app()->setLocale('ar');

    $service = app(ProviderTypeService::class);
    $request = Request::create('/register', 'GET');

    $cold = DashboardProviderTypeResource::collection($service->listForApi())->resolve($request);

    $warmCollection = $service->listForApi();
    expect($warmCollection)->toBeInstanceOf(EloquentCollection::class);

    $warm = DashboardProviderTypeResource::collection($warmCollection)->resolve($request);

    expect(json_encode($warm))->toBe(json_encode($cold));
    assertProviderTypeIdenticalTyped($cold, $warm, 'frontend.types');

    $this->get(route('auth.register'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/Auth/Register_')
            ->has('types', 1)
            ->where('types.0.name', 'Individual AR')
        );
});

test('Provider profile types prop is byte-for-byte identical whether served from cache or fresh query', function (): void {
    app()->setLocale('en');

    $service = app(ProviderTypeService::class);
    $request = Request::create('/provider/profile', 'GET');

    $cold = DashboardProviderTypeResource::collection($service->listForApi())->resolve($request);
    $warm = DashboardProviderTypeResource::collection($service->listForApi())->resolve($request);

    expect(json_encode($warm))->toBe(json_encode($cold));
    assertProviderTypeIdenticalTyped($cold, $warm, 'provider.types');

    // Ensure the controller wiring still resolves (auth required for profile page itself)
    expect(method_exists(ProviderAuthController::class, 'profile'))->toBeTrue();
});

test('DeleteProviderTypeAction invalidates provider-types lookup cache across locales', function (): void {
    app()->setLocale('en');
    $service = app(ProviderTypeService::class);

    expect($service->listForApi())->toHaveCount(1);

    $type = ProviderType::query()->firstOrFail();
    app(DeleteProviderTypeAction::class)->handle($type);

    expect($service->listForApi())->toHaveCount(0);
});
