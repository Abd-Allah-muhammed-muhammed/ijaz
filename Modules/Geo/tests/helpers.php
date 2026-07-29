<?php

use App\Models\Admin;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\Models\Permission;

function createGeoDashboardAdmin(array $permissions): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Geo Dashboard Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function withoutGeoDashboardLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

/**
 * @return array<string, array{title: string}>
 */
function geoTitleTranslations(string $prefix): array
{
    return [
        'en' => ['title' => "{$prefix} EN"],
        'ar' => ['title' => "{$prefix} AR"],
        'ur' => ['title' => "{$prefix} UR"],
        'hi' => ['title' => "{$prefix} HI"],
    ];
}

/**
 * @return array<string, array{name: string}>
 */
function geoNameTranslations(string $prefix): array
{
    return [
        'en' => ['name' => "{$prefix} EN"],
        'ar' => ['name' => "{$prefix} AR"],
        'ur' => ['name' => "{$prefix} UR"],
        'hi' => ['name' => "{$prefix} HI"],
    ];
}
