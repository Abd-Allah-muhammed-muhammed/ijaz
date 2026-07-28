<?php

use App\Support\Api\ApiVersionRegistry;
use App\Support\Api\ApiVersionResolverChain;
use App\Support\Api\ApiVersionService;
use App\Support\Api\Contracts\ApiVersionResolverStrategy;
use App\Support\Api\Facades\ApiVersion as ApiVersionFacade;
use App\Support\Api\Strategies\HeaderResolver;
use App\Support\Api\Strategies\QueryStringResolver;
use App\Support\Api\Strategies\UrlPrefixResolver;
use Illuminate\Http\Request;

test('url prefix resolver extracts version from api/{version}/... paths', function () {
    $resolver = new UrlPrefixResolver;

    expect($resolver->resolve(Request::create('/api/v1/auth/counts', 'GET')))->toBe('v1')
        ->and($resolver->resolve(Request::create('/api/v2/things', 'GET')))->toBe('v2')
        ->and($resolver->resolve(Request::create('/dashboard/home', 'GET')))->toBeNull()
        ->and($resolver->resolve(Request::create('/api', 'GET')))->toBeNull();
});

test('header resolver reads the configured header name', function () {
    $resolver = new HeaderResolver('X-API-Version');

    $withHeader = Request::create('/anything', 'GET');
    $withHeader->headers->set('X-API-Version', 'v1');

    $empty = Request::create('/anything', 'GET');
    $empty->headers->set('X-API-Version', '  ');

    expect($resolver->resolve($withHeader))->toBe('v1')
        ->and($resolver->resolve(Request::create('/anything', 'GET')))->toBeNull()
        ->and($resolver->resolve($empty))->toBeNull();
});

test('query string resolver reads the configured query param', function () {
    $resolver = new QueryStringResolver('version');

    expect($resolver->resolve(Request::create('/anything?version=v1', 'GET')))->toBe('v1')
        ->and($resolver->resolve(Request::create('/anything?version=', 'GET')))->toBeNull()
        ->and($resolver->resolve(Request::create('/anything', 'GET')))->toBeNull();
});

test('chain uses first registry-valid strategy result in priority order', function () {
    $registry = app(ApiVersionRegistry::class);

    $header = new HeaderResolver('X-API-Version');
    $url = new UrlPrefixResolver;

    $chain = new ApiVersionResolverChain([$header, $url], $registry);

    $request = Request::create('/api/v1/auth/counts', 'GET');
    $request->headers->set('X-API-Version', 'v1');

    expect($chain->resolve($request))->toBe('v1');

    $bogusHeader = Request::create('/api/v1/auth/counts', 'GET');
    $bogusHeader->headers->set('X-API-Version', 'v9');

    // Bogus header is ignored; URL strategy still yields a registry-valid v1.
    expect($chain->resolve($bogusHeader))->toBe('v1');
});

test('chain falls back to default version when no strategy matches', function () {
    $chain = new ApiVersionResolverChain(
        [new UrlPrefixResolver],
        app(ApiVersionRegistry::class),
    );

    expect($chain->resolve(Request::create('/dashboard/home', 'GET')))->toBe('v1');
});

test('chain caches the resolved version on the request attributes', function () {
    $strategy = Mockery::mock(ApiVersionResolverStrategy::class);
    $strategy->shouldReceive('resolve')
        ->once()
        ->andReturn('v1');

    $chain = new ApiVersionResolverChain(
        [$strategy],
        app(ApiVersionRegistry::class),
    );

    $request = Request::create('/api/v1/foo', 'GET');

    expect($chain->resolve($request))->toBe('v1')
        ->and($chain->resolve($request))->toBe('v1')
        ->and($request->attributes->get('_resolved_api_version'))->toBe('v1');
});

test('container-bound chain only activates strategies listed in config', function () {
    expect(config('api.negotiation.strategies'))->toBe([
        UrlPrefixResolver::class,
    ]);

    $chain = app(ApiVersionResolverChain::class);
    $request = Request::create('/dashboard?version=v1', 'GET');
    $request->headers->set('X-API-Version', 'v1');

    // Header/query strategies are commented out in config — default wins.
    expect($chain->resolve($request))->toBe('v1')
        ->and($request->attributes->get('_resolved_api_version'))->toBe('v1');
});

test('api version service and facade expose current and isDeprecated helpers', function () {
    $request = Request::create('/api/v1/auth/counts', 'GET');
    $this->app->instance('request', $request);

    $service = app(ApiVersionService::class);

    expect($service->current($request))->toBe('v1')
        ->and($service->isDeprecated('v1'))->toBeFalse()
        ->and(ApiVersionFacade::current($request))->toBe('v1')
        ->and(ApiVersionFacade::isDeprecated('v1'))->toBeFalse()
        ->and(ApiVersionFacade::isDeprecated())->toBeFalse();
});
