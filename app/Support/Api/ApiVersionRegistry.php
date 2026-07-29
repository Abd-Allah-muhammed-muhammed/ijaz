<?php

namespace App\Support\Api;

use InvalidArgumentException;

/**
 * Typed access to config('api.versions') — no routing logic.
 *
 * Used by route providers to load enabled version folders/prefixes, and by the
 * informational resolver chain to validate version keys. Validation here does
 * not change which controller Laravel dispatches for a request.
 */
final class ApiVersionRegistry
{
    /**
     * @return list<ApiVersion>
     */
    public function all(): array
    {
        /** @var array<string, array<string, mixed>> $versions */
        $versions = config('api.versions', []);

        $result = [];

        foreach (array_keys($versions) as $key) {
            $result[] = ApiVersion::fromConfig((string) $key);
        }

        return $result;
    }

    /**
     * @return list<ApiVersion>
     */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (ApiVersion $version): bool => $version->enabled,
        ));
    }

    public function get(string $key): ?ApiVersion
    {
        /** @var array<string, array<string, mixed>> $versions */
        $versions = config('api.versions', []);

        if (! array_key_exists($key, $versions)) {
            return null;
        }

        return ApiVersion::fromConfig($key);
    }

    public function default(): ApiVersion
    {
        $key = (string) config('api.default_version', 'v1');
        $version = $this->get($key);

        if ($version === null) {
            throw new InvalidArgumentException(
                "Default API version [{$key}] is not defined in config/api.php.",
            );
        }

        return $version;
    }
}
