<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

it('round-trips pulse dashboard classes through a serializing cache store', function (): void {
    // Array store skips serialization; file store exercises serializable_classes.
    $store = Cache::store('file');
    $key = 'pulse-serializable-classes-test';

    $payload = [
        collect([(object) ['hits' => '1.00', 'misses' => '0.00']]),
        1.23,
        CarbonImmutable::parse('2026-08-01 00:00:00'),
    ];

    $store->put($key, $payload, 60);

    $restored = $store->get($key);

    expect($restored)->toBeArray()
        ->and($restored[0])->toBeInstanceOf(Collection::class)
        ->and($restored[0]->first())->toBeInstanceOf(stdClass::class)
        ->and($restored[0]->first()->hits)->toBe('1.00')
        ->and($restored[2])->toBeInstanceOf(CarbonImmutable::class);

    $store->forget($key);
});

it('allows the pulse-required classes in cache.serializable_classes', function (): void {
    expect(config('cache.serializable_classes'))->toContain(
        stdClass::class,
        Collection::class,
        CarbonImmutable::class,
    );
});
