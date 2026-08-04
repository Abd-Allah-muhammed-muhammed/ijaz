<?php

use Tests\Support\TestingDatabaseGuard;

test('TestingDatabaseGuard aborts when bootstrap config cache file exists', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ijaz-testing-guard-'.uniqid('', true);
    $cacheDir = $base.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache';
    mkdir($cacheDir, 0777, true);
    file_put_contents($cacheDir.DIRECTORY_SEPARATOR.'config.php', '<?php return [];');

    try {
        expect(fn () => TestingDatabaseGuard::assertNoCachedConfig($base))
            ->toThrow(RuntimeException::class, 'bootstrap/cache/config.php');
    } finally {
        unlink($cacheDir.DIRECTORY_SEPARATOR.'config.php');
        rmdir($cacheDir);
        rmdir($base.DIRECTORY_SEPARATOR.'bootstrap');
        rmdir($base);
    }
});

test('TestingDatabaseGuard allows missing config cache', function (): void {
    $base = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ijaz-testing-guard-clean-'.uniqid('', true);
    mkdir($base, 0777, true);

    try {
        TestingDatabaseGuard::assertNoCachedConfig($base);
        expect(true)->toBeTrue();
    } finally {
        rmdir($base);
    }
});

test('TestingDatabaseGuard requires sqlite memory for testing', function (): void {
    TestingDatabaseGuard::assertIsolatedTestingDatabase('testing', 'sqlite', ':memory:', ':memory:');

    expect(fn () => TestingDatabaseGuard::assertIsolatedTestingDatabase('testing', 'mysql', 'ijaz', 'ijaz_monitoring'))
        ->toThrow(RuntimeException::class, 'non-isolated database');

    expect(fn () => TestingDatabaseGuard::assertIsolatedTestingDatabase('local', 'sqlite', ':memory:'))
        ->toThrow(RuntimeException::class, 'APP_ENV=testing');

    expect(fn () => TestingDatabaseGuard::assertIsolatedTestingDatabase('testing', 'sqlite', ':memory:', 'ijaz_monitoring'))
        ->toThrow(RuntimeException::class, 'forbidden monitoring database');
});

test('running test suite resolves an isolated sqlite memory database', function (): void {
    expect(app()->environment())->toBe('testing')
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('database.connections.monitoring.database'))->toBe(':memory:');
});
