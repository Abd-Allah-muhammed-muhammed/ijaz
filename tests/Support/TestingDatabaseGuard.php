<?php

namespace Tests\Support;

use RuntimeException;

/**
 * Prevents the test suite from ever booting against the real development database.
 *
 * Root cause this guards against: a present bootstrap/cache/config.php bakes
 * DB_CONNECTION=mysql / DB_DATABASE=ijaz into config and causes Laravel to ignore
 * phpunit.xml's sqlite/:memory: overrides — LazilyRefreshDatabase can then run
 * migrate:fresh against the real MySQL database.
 */
final class TestingDatabaseGuard
{
    /**
     * Development database names that must never be used while APP_ENV=testing.
     *
     * @var list<string>
     */
    public const FORBIDDEN_DATABASE_NAMES = [
        'ijaz',
        'ijaz_monitoring',
    ];

    public static function assertNoCachedConfig(string $basePath): void
    {
        $cachedConfig = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'config.php';

        if (! is_file($cachedConfig)) {
            return;
        }

        throw new RuntimeException(
            "Refusing to run tests while bootstrap/cache/config.php exists.\n".
            "Cached configuration ignores phpunit.xml DB_* overrides and can point\n".
            "LazilyRefreshDatabase / RefreshDatabase at the real MySQL database (ijaz),\n".
            "which can wipe local development data via migrate:fresh.\n".
            'Fix: run `php artisan config:clear` then re-run tests.'
        );
    }

    public static function assertIsolatedTestingDatabase(
        string $environment,
        string $defaultConnection,
        string $defaultDatabase,
        ?string $monitoringDatabase = null,
    ): void {
        if ($environment !== 'testing') {
            throw new RuntimeException(
                "Refusing to run tests unless APP_ENV=testing (got [{$environment}])."
            );
        }

        if ($defaultConnection !== 'sqlite' || $defaultDatabase !== ':memory:') {
            throw new RuntimeException(
                "Refusing to run tests against a non-isolated database.\n".
                "Expected default connection sqlite with database :memory: (per phpunit.xml).\n".
                "Got connection [{$defaultConnection}] database [{$defaultDatabase}].\n".
                'If bootstrap/cache/config.php exists, run `php artisan config:clear`.'
            );
        }

        foreach (self::FORBIDDEN_DATABASE_NAMES as $forbidden) {
            if (strcasecmp($defaultDatabase, $forbidden) === 0) {
                throw new RuntimeException(
                    "Refusing to run tests against forbidden database name [{$forbidden}]."
                );
            }
        }

        if ($monitoringDatabase !== null
            && $monitoringDatabase !== ''
            && $monitoringDatabase !== ':memory:'
            && in_array(strtolower($monitoringDatabase), array_map('strtolower', self::FORBIDDEN_DATABASE_NAMES), true)
        ) {
            throw new RuntimeException(
                "Refusing to run tests against forbidden monitoring database [{$monitoringDatabase}].\n".
                'Expected DB_MONITORING_DATABASE=:memory: (per phpunit.xml).'
            );
        }
    }
}
