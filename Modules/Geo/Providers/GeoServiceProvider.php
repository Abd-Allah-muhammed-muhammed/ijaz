<?php

namespace Modules\Geo\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class GeoServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Geo';

    protected string $nameLower = 'geo';

    public function register(): void
    {
        parent::register();
    }

    public function boot(): void
    {
        parent::boot();
    }
}
