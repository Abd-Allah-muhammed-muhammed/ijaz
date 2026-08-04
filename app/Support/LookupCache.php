<?php

namespace App\Support;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Shared caching layer for mostly-static lookup / reference data.
 *
 * Key convention (always prefixed with `lookup:`):
 * - `lookup:{key}`
 * - `lookup:{key}:{locale}`
 * - `lookup:{key}:{locale}:{scopeId}`
 *
 * Examples:
 *
 * ```php
 * // Forever — invalidate only via forget*()
 * LookupCache::rememberForever('settings:public', fn () => $repo->pluckPublicContentByKey());
 *
 * LookupCache::rememberForeverForLocale('regions:select', $locale, fn () => $repo->listForSelect());
 *
 * LookupCache::rememberForeverScoped(
 *     'cities:select',
 *     $locale,
 *     $regionId,
 *     fn () => $repo->listForSelect(regionId: $regionId),
 * );
 *
 * // TTL — e.g. admin dashboard stats (Tier 2)
 * LookupCache::rememberFor('stats:admin:home', 60, fn () => $service->forHome());
 * LookupCache::rememberForLocaleFor('catalog:brands', $locale, 3600, fn () => ...);
 * LookupCache::rememberScopedFor('skills:select', $locale, $categoryId, 3600, fn () => ...);
 *
 * // Invalidation (call from Store/Update/Delete Actions)
 * LookupCache::forget('settings:public');
 * LookupCache::forgetForLocale('regions:select', 'ar');
 * LookupCache::forgetAllLocales('regions:select');
 * LookupCache::forgetScoped('cities:select', $regionId);
 * LookupCache::forgetScopedAllLocales('cities:select', $regionId);
 * LookupCache::flush(); // every lookup:* entry — dev / emergency only
 * ```
 *
 * Type preservation:
 * Laravel's cache uses PHP serialize(). Values round-trip as the same type **only if**
 * their classes are allow-listed in `config('cache.serializable_classes')`. This app
 * currently allows `Illuminate\Support\Collection`, `stdClass`, and `CarbonImmutable`.
 * `Illuminate\Database\Eloquent\Collection` / Eloquent models are NOT allow-listed and
 * become `__PHP_Incomplete_Class` on cache hit (verified against CACHE_STORE=database).
 *
 * Root cause of the earlier Collection→array bug: Tier 1 domain code intentionally
 * cached `->toArray()` / mapped arrays (to avoid the Eloquent allow-list gap) but still
 * type-hinted callers as `Eloquent\Collection`. The cache layer did not transmute types —
 * the closure returned arrays and those arrays were returned unchanged. LookupCache
 * therefore returns the closure's value unmodified. Callers must either:
 * 1. Cache allow-listed types (arrays, DTOs, Support\Collection), and type-hint accordingly, or
 * 2. Expand `cache.serializable_classes` before caching Eloquent objects.
 *
 * Driver notes:
 * Production uses CACHE_STORE=database (no cache tags). flush() / forgetAllLocales() /
 * forgetScoped*() use a tracked-key registry. When the store supports tags (Redis,
 * array, Memcached), entries are also written under a `lookup` tag so flush() can use
 * tag flush as a fast path, with the registry remaining the source of truth for
 * granular invalidation.
 */
final class LookupCache
{
    private const PREFIX = 'lookup';

    private const REGISTRY_KEY = 'lookup:__registry__';

    private const ROOT_TAG = 'lookup';

    // -------------------------------------------------------------------------
    // Forever
    // -------------------------------------------------------------------------

    public static function rememberForever(string $key, Closure $query): mixed
    {
        return self::remember($key, locale: null, scopeId: null, seconds: null, query: $query);
    }

    public static function rememberForeverForLocale(string $key, string $locale, Closure $query): mixed
    {
        return self::remember($key, locale: $locale, scopeId: null, seconds: null, query: $query);
    }

    public static function rememberForeverScoped(string $key, string $locale, string|int $scopeId, Closure $query): mixed
    {
        return self::remember($key, locale: $locale, scopeId: $scopeId, seconds: null, query: $query);
    }

    // -------------------------------------------------------------------------
    // TTL
    // -------------------------------------------------------------------------

    public static function rememberFor(string $key, int $seconds, Closure $query): mixed
    {
        return self::remember($key, locale: null, scopeId: null, seconds: $seconds, query: $query);
    }

    public static function rememberForLocaleFor(string $key, string $locale, int $seconds, Closure $query): mixed
    {
        return self::remember($key, locale: $locale, scopeId: null, seconds: $seconds, query: $query);
    }

    public static function rememberScopedFor(string $key, string $locale, string|int $scopeId, int $seconds, Closure $query): mixed
    {
        return self::remember($key, locale: $locale, scopeId: $scopeId, seconds: $seconds, query: $query);
    }

    // -------------------------------------------------------------------------
    // Invalidation
    // -------------------------------------------------------------------------

    public static function forget(string $key): void
    {
        self::forgetExact(self::buildKey($key), $key, locale: null, scopeId: null);
    }

    public static function forgetForLocale(string $key, string $locale): void
    {
        self::forgetExact(self::buildKey($key, $locale), $key, locale: $locale, scopeId: null);
    }

    public static function forgetAllLocales(string $key): void
    {
        foreach (self::registry() as $fullKey => $meta) {
            if (($meta['key'] ?? null) !== $key) {
                continue;
            }

            self::forgetExact(
                $fullKey,
                $key,
                locale: $meta['locale'] ?? null,
                scopeId: $meta['scope'] ?? null,
            );
        }
    }

    public static function forgetScoped(string $key, string|int $scopeId): void
    {
        self::forgetScopedAllLocales($key, $scopeId);
    }

    public static function forgetScopedAllLocales(string $key, string|int $scopeId): void
    {
        $scope = (string) $scopeId;

        foreach (self::registry() as $fullKey => $meta) {
            if (($meta['key'] ?? null) !== $key) {
                continue;
            }

            if (($meta['scope'] ?? null) !== $scope) {
                continue;
            }

            self::forgetExact(
                $fullKey,
                $key,
                locale: $meta['locale'] ?? null,
                scopeId: $scope,
            );
        }
    }

    /**
     * Clear every tracked `lookup:*` entry. Does not touch unrelated cache keys.
     * Dev / emergency use only.
     */
    public static function flush(): void
    {
        if (self::supportsTags()) {
            Cache::tags([self::ROOT_TAG])->flush();
        } else {
            foreach (array_keys(self::registry()) as $fullKey) {
                Cache::forget($fullKey);
            }
        }

        Cache::forget(self::REGISTRY_KEY);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private static function remember(
        string $key,
        ?string $locale,
        string|int|null $scopeId,
        ?int $seconds,
        Closure $query,
    ): mixed {
        $fullKey = self::buildKey($key, $locale, $scopeId);
        self::track($fullKey, $key, $locale, $scopeId);

        $cache = self::store($key, $scopeId);

        if ($seconds === null) {
            return $cache->rememberForever($fullKey, $query);
        }

        return $cache->remember($fullKey, $seconds, $query);
    }

    private static function forgetExact(
        string $fullKey,
        string $logicalKey,
        ?string $locale,
        string|int|null $scopeId,
    ): void {
        self::store($logicalKey, $scopeId)->forget($fullKey);
        self::untrack($fullKey);
    }

    /**
     * @param  string  $key  Logical key (no `lookup:` prefix)
     */
    private static function buildKey(string $key, ?string $locale = null, string|int|null $scopeId = null): string
    {
        $parts = [self::PREFIX, $key];

        if ($locale !== null) {
            $parts[] = $locale;
        }

        if ($scopeId !== null) {
            $parts[] = (string) $scopeId;
        }

        return implode(':', $parts);
    }

    private static function store(string $logicalKey, string|int|null $scopeId = null): Repository
    {
        if (! self::supportsTags()) {
            return Cache::store();
        }

        $tags = [self::ROOT_TAG, self::PREFIX.':'.$logicalKey];

        if ($scopeId !== null) {
            $tags[] = self::PREFIX.':'.$logicalKey.':scope:'.$scopeId;
        }

        return Cache::tags($tags);
    }

    private static function supportsTags(): bool
    {
        return Cache::supportsTags();
    }

    /**
     * @return array<string, array{key: string, locale: ?string, scope: ?string}>
     */
    private static function registry(): array
    {
        /** @var array<string, array{key: string, locale: ?string, scope: ?string}> */
        return Cache::get(self::REGISTRY_KEY, []);
    }

    private static function track(
        string $fullKey,
        string $logicalKey,
        ?string $locale,
        string|int|null $scopeId,
    ): void {
        $registry = self::registry();
        $registry[$fullKey] = [
            'key' => $logicalKey,
            'locale' => $locale,
            'scope' => $scopeId === null ? null : (string) $scopeId,
        ];
        Cache::forever(self::REGISTRY_KEY, $registry);
    }

    private static function untrack(string $fullKey): void
    {
        $registry = self::registry();

        if (! array_key_exists($fullKey, $registry)) {
            return;
        }

        unset($registry[$fullKey]);

        if ($registry === []) {
            Cache::forget(self::REGISTRY_KEY);

            return;
        }

        Cache::forever(self::REGISTRY_KEY, $registry);
    }
}
