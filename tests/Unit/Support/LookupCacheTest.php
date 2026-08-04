<?php

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    Cache::flush();
    Cache::forget('lookup:__registry__');
});

test('rememberForever caches and returns the exact same value and type on repeat calls', function (): void {
    $calls = 0;

    $first = LookupCache::rememberForever('demo:forever', function () use (&$calls): string {
        $calls++;

        return 'payload';
    });

    $second = LookupCache::rememberForever('demo:forever', function () use (&$calls): string {
        $calls++;

        return 'should-not-run';
    });

    expect($first)->toBe('payload')
        ->and($second)->toBe('payload')
        ->and($second)->toBeString()
        ->and($calls)->toBe(1);
});

test('rememberForeverForLocale produces different cache entries for different locales', function (): void {
    $calls = [];

    $ar = LookupCache::rememberForeverForLocale('regions:select', 'ar', function () use (&$calls): string {
        $calls[] = 'ar';

        return 'الرياض';
    });

    $en = LookupCache::rememberForeverForLocale('regions:select', 'en', function () use (&$calls): string {
        $calls[] = 'en';

        return 'Riyadh';
    });

    LookupCache::rememberForeverForLocale('regions:select', 'ar', fn (): string => 'miss');
    LookupCache::rememberForeverForLocale('regions:select', 'en', fn (): string => 'miss');

    expect($ar)->toBe('الرياض')
        ->and($en)->toBe('Riyadh')
        ->and($calls)->toBe(['ar', 'en']);
});

test('rememberForeverScoped produces different cache entries for different scope ids', function (): void {
    $calls = [];

    $a = LookupCache::rememberForeverScoped('cities:select', 'ar', 1, function () use (&$calls): string {
        $calls[] = 1;

        return 'city-1';
    });

    $b = LookupCache::rememberForeverScoped('cities:select', 'ar', 2, function () use (&$calls): string {
        $calls[] = 2;

        return 'city-2';
    });

    LookupCache::rememberForeverScoped('cities:select', 'ar', 1, fn (): string => 'miss');
    LookupCache::rememberForeverScoped('cities:select', 'ar', 2, fn (): string => 'miss');

    expect($a)->toBe('city-1')
        ->and($b)->toBe('city-2')
        ->and($calls)->toBe([1, 2]);
});

test('rememberFor expires after the given TTL', function (): void {
    $calls = 0;

    LookupCache::rememberFor('stats:home', 30, function () use (&$calls): string {
        $calls++;

        return 'v'.$calls;
    });

    LookupCache::rememberFor('stats:home', 30, function () use (&$calls): string {
        $calls++;

        return 'v'.$calls;
    });

    expect($calls)->toBe(1);

    $this->travel(31)->seconds();

    $after = LookupCache::rememberFor('stats:home', 30, function () use (&$calls): string {
        $calls++;

        return 'v'.$calls;
    });

    expect($calls)->toBe(2)
        ->and($after)->toBe('v2');
});

test('forget removes a simple cached key', function (): void {
    $calls = 0;

    LookupCache::rememberForever('settings:public', function () use (&$calls): array {
        $calls++;

        return ['phone' => '966'];
    });

    LookupCache::forget('settings:public');

    $again = LookupCache::rememberForever('settings:public', function () use (&$calls): array {
        $calls++;

        return ['phone' => '966'];
    });

    expect($again)->toBe(['phone' => '966'])
        ->and($calls)->toBe(2);
});

test('forgetForLocale only removes that locale\'s entry, not others', function (): void {
    $calls = ['ar' => 0, 'en' => 0];

    LookupCache::rememberForeverForLocale('regions:select', 'ar', function () use (&$calls): string {
        $calls['ar']++;

        return 'ar-1';
    });
    LookupCache::rememberForeverForLocale('regions:select', 'en', function () use (&$calls): string {
        $calls['en']++;

        return 'en-1';
    });

    LookupCache::forgetForLocale('regions:select', 'ar');

    LookupCache::rememberForeverForLocale('regions:select', 'ar', function () use (&$calls): string {
        $calls['ar']++;

        return 'ar-2';
    });
    LookupCache::rememberForeverForLocale('regions:select', 'en', function () use (&$calls): string {
        $calls['en']++;

        return 'en-miss';
    });

    expect($calls['ar'])->toBe(2)
        ->and($calls['en'])->toBe(1);
});

test('forgetAllLocales removes entries across all locales for a key', function (): void {
    $calls = ['ar' => 0, 'en' => 0, 'other' => 0];

    LookupCache::rememberForeverForLocale('regions:select', 'ar', function () use (&$calls): string {
        $calls['ar']++;

        return 'ar';
    });
    LookupCache::rememberForeverForLocale('regions:select', 'en', function () use (&$calls): string {
        $calls['en']++;

        return 'en';
    });
    LookupCache::rememberForever('unrelated:key', function () use (&$calls): string {
        $calls['other']++;

        return 'keep';
    });

    LookupCache::forgetAllLocales('regions:select');

    LookupCache::rememberForeverForLocale('regions:select', 'ar', function () use (&$calls): string {
        $calls['ar']++;

        return 'ar';
    });
    LookupCache::rememberForeverForLocale('regions:select', 'en', function () use (&$calls): string {
        $calls['en']++;

        return 'en';
    });
    LookupCache::rememberForever('unrelated:key', function () use (&$calls): string {
        $calls['other']++;

        return 'keep';
    });

    expect($calls['ar'])->toBe(2)
        ->and($calls['en'])->toBe(2)
        ->and($calls['other'])->toBe(1);
});

test('forgetScoped only removes that scope\'s entry', function (): void {
    $calls = [1 => 0, 2 => 0];

    LookupCache::rememberForeverScoped('cities:select', 'ar', 1, function () use (&$calls): string {
        $calls[1]++;

        return 'c1';
    });
    LookupCache::rememberForeverScoped('cities:select', 'en', 1, function () use (&$calls): string {
        $calls[1]++;

        return 'c1-en';
    });
    LookupCache::rememberForeverScoped('cities:select', 'ar', 2, function () use (&$calls): string {
        $calls[2]++;

        return 'c2';
    });

    LookupCache::forgetScoped('cities:select', 1);

    LookupCache::rememberForeverScoped('cities:select', 'ar', 1, function () use (&$calls): string {
        $calls[1]++;

        return 'c1';
    });
    LookupCache::rememberForeverScoped('cities:select', 'en', 1, function () use (&$calls): string {
        $calls[1]++;

        return 'c1-en';
    });
    LookupCache::rememberForeverScoped('cities:select', 'ar', 2, function () use (&$calls): string {
        $calls[2]++;

        return 'c2';
    });

    expect($calls[1])->toBe(4)
        ->and($calls[2])->toBe(1);
});

test('flush removes all lookup: prefixed keys without touching unrelated cache keys', function (): void {
    Cache::put('app:unrelated', 'preserve-me', 60);

    LookupCache::rememberForever('settings:public', fn (): string => 'settings');
    LookupCache::rememberForeverForLocale('regions:select', 'ar', fn (): string => 'regions');
    LookupCache::rememberForeverScoped('cities:select', 'ar', 9, fn (): string => 'cities');

    LookupCache::flush();

    $settingsCalls = 0;
    LookupCache::rememberForever('settings:public', function () use (&$settingsCalls): string {
        $settingsCalls++;

        return 'settings';
    });

    expect(Cache::get('app:unrelated'))->toBe('preserve-me')
        ->and($settingsCalls)->toBe(1);
});

test('caching a Collection returns a Collection on cache hit, not a plain array', function (): void {
    // Array store skips serialization; file store exercises real PHP serialize + allow-list.
    config(['cache.default' => 'file']);
    Cache::store('file')->flush();

    $original = Collection::make([
        ['id' => 1, 'title' => 'Riyadh'],
        ['id' => 2, 'title' => 'Jeddah'],
    ]);

    $first = LookupCache::rememberForever('geo:regions', fn (): Collection => $original);
    $second = LookupCache::rememberForever('geo:regions', fn (): Collection => Collection::make(['should-not-run']));

    expect($first)->toBeInstanceOf(Collection::class)
        ->and($second)->toBeInstanceOf(Collection::class)
        ->and($second)->not->toBeArray()
        ->and($second->all())->toBe($original->all());

    Cache::store('file')->forget('lookup:geo:regions');
    Cache::store('file')->forget('lookup:__registry__');
    config(['cache.default' => env('CACHE_STORE', 'array')]);
});

test('caching an array returns an array on cache hit', function (): void {
    config(['cache.default' => 'file']);
    Cache::store('file')->flush();

    $payload = ['phone' => '966500000000', 'email' => 'info@ijaz.sa'];

    $first = LookupCache::rememberForever('settings:public', fn (): array => $payload);
    $second = LookupCache::rememberForever('settings:public', fn (): array => ['should' => 'not-run']);

    expect($first)->toBeArray()
        ->and($second)->toBeArray()
        ->and($second)->toBe($payload)
        ->and($second)->not->toBeInstanceOf(Collection::class);

    Cache::store('file')->forget('lookup:settings:public');
    Cache::store('file')->forget('lookup:__registry__');
    config(['cache.default' => env('CACHE_STORE', 'array')]);
});

test('flush uses the registry path on the database cache driver', function (): void {
    config(['cache.default' => 'database']);

    if (! Schema::hasTable('cache')) {
        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }

    Cache::flush();

    expect(Cache::supportsTags())->toBeFalse();

    Cache::put('app:unrelated', 'preserve-me', 60);
    LookupCache::rememberForever('settings:public', fn (): string => 'settings');
    LookupCache::rememberForeverForLocale('regions:select', 'ar', fn (): string => 'regions');

    LookupCache::flush();

    $recomputed = 0;
    LookupCache::rememberForever('settings:public', function () use (&$recomputed): string {
        $recomputed++;

        return 'settings';
    });

    expect(Cache::get('app:unrelated'))->toBe('preserve-me')
        ->and($recomputed)->toBe(1)
        ->and(Cache::supportsTags())->toBeFalse();

    config(['cache.default' => env('CACHE_STORE', 'array')]);
});

test('Eloquent Collections are not safely round-tripped under serializable_classes (documents root cause)', function (): void {
    config(['cache.default' => 'file']);
    Cache::store('file')->flush();

    $eloquent = new EloquentCollection([
        (object) ['id' => 1],
    ]);

    Cache::store('file')->put('probe:eloquent-collection', $eloquent, 60);
    $restored = Cache::store('file')->get('probe:eloquent-collection');

    expect($restored)->toBeInstanceOf(__PHP_Incomplete_Class::class);

    Cache::store('file')->forget('probe:eloquent-collection');
    config(['cache.default' => env('CACHE_STORE', 'array')]);
});
