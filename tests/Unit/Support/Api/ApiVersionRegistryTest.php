<?php

use App\Support\Api\ApiVersion;
use App\Support\Api\ApiVersionRegistry;

test('default version matches v1 routing literals used before config', function () {
    $version = app(ApiVersionRegistry::class)->default();

    expect($version->key)->toBe('v1')
        ->and($version->enabled)->toBeTrue()
        ->and($version->folder)->toBe('V1')
        ->and($version->prefix)->toBe('api/v1')
        ->and($version->name)->toBe('api.v1.')
        ->and($version->deprecated)->toBeFalse()
        ->and($version->sunsetAt)->toBeNull()
        ->and($version->successor)->toBeNull();
});

test('enabled returns only active versions', function () {
    $enabled = app(ApiVersionRegistry::class)->enabled();

    expect($enabled)->toHaveCount(1)
        ->and($enabled[0]->key)->toBe('v1');
});

test('fromConfig and toArray round-trip the configured shape', function () {
    $version = ApiVersion::fromConfig('v1');

    expect($version->toArray())->toMatchArray([
        'key' => 'v1',
        'enabled' => true,
        'folder' => 'V1',
        'prefix' => 'api/v1',
        'name' => 'api.v1.',
        'deprecated' => false,
        'sunset_at' => null,
        'successor' => null,
    ]);
});

test('get returns null for unknown version keys', function () {
    expect(app(ApiVersionRegistry::class)->get('v9'))->toBeNull();
});
