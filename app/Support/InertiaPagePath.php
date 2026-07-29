<?php

namespace App\Support;

/**
 * Maps Inertia backend component names to physical paths under resources/js.
 *
 * Backend still renders `Dashboard/…`, `Provider/…`, `Frontend/…`, `Errors/…`.
 * Files live under apps/{admin,provider,web}/pages and shared/pages/Errors.
 */
final class InertiaPagePath
{
    /**
     * Path relative to resources/js, without extension.
     * Example: Frontend/LandingPage → apps/web/pages/LandingPage
     */
    public static function relative(string $component): string
    {
        return match (true) {
            str_starts_with($component, 'Dashboard/') => 'apps/admin/pages/'.substr($component, strlen('Dashboard/')),
            str_starts_with($component, 'Provider/') => 'apps/provider/pages/'.substr($component, strlen('Provider/')),
            str_starts_with($component, 'Frontend/') => 'apps/web/pages/'.substr($component, strlen('Frontend/')),
            str_starts_with($component, 'Errors/') => 'shared/pages/Errors/'.substr($component, strlen('Errors/')),
            default => $component,
        };
    }

    /**
     * Absolute filesystem path to the .tsx page module.
     */
    public static function absolute(string $component, string $extension = 'tsx'): string
    {
        return resource_path('js/'.self::relative($component).'.'.$extension);
    }

    /**
     * Vite entry path used from Blade (@vite).
     */
    public static function viteEntry(string $component, string $extension = 'tsx'): string
    {
        return 'resources/js/'.self::relative($component).'.'.$extension;
    }
}
