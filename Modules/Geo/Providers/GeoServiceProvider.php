<?php

namespace Modules\Geo\Providers;

use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Repositories\CityRepository;
use Modules\Geo\Repositories\NationalityRepository;
use Modules\Geo\Repositories\RegionRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class GeoServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Geo';

    protected string $nameLower = 'geo';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(RegionRepositoryInterface::class, RegionRepository::class);
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
        $this->app->bind(NationalityRepositoryInterface::class, NationalityRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
