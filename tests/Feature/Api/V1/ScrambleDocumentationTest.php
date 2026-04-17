<?php

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

it('serves API docs UI on the configured route', function () {
    get('/docs/api')->assertSuccessful();
});

it('generates OpenAPI for API v1 routes with bearer auth security', function () {
    $response = getJson('/docs/api.json')->assertSuccessful();

    $spec = $response->json();

    expect($spec)
        ->toHaveKey('paths')
        ->and($spec)
        ->toHaveKey('components.securitySchemes.http')
        ->and($spec['paths'])
        ->toHaveKey('/user/auth/login')
        ->toHaveKey('/wallet/balance')
        ->not->toHaveKey('/categories');
});

it('keeps catalog route documented as public', function () {
    $response = getJson('/docs/api.json')->assertSuccessful();

    $spec = $response->json();
    $catalogOperation = $spec['paths']['/catalog/categories']['get'] ?? null;

    expect($catalogOperation)->not->toBeNull();

    $security = $catalogOperation['security'] ?? null;

    expect($security === null || $security === [])->toBeTrue();
});
