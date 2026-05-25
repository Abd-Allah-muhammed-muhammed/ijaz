<?php

namespace Modules\Classifieds\Providers;

use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\Repositories\CarAdvisementRepository;
use Modules\Classifieds\Repositories\ElectronicAdvisementRepository;
use Modules\Classifieds\Repositories\InstituteAdvisementRepository;
use Modules\Classifieds\Repositories\PropertyAdvisementRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ClassifiedsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Classifieds';

    protected string $nameLower = 'classifieds';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(PropertyAdvisementRepositoryInterface::class, PropertyAdvisementRepository::class);
        $this->app->bind(CarAdvisementRepositoryInterface::class, CarAdvisementRepository::class);
        $this->app->bind(ElectronicAdvisementRepositoryInterface::class, ElectronicAdvisementRepository::class);
        $this->app->bind(InstituteAdvisementRepositoryInterface::class, InstituteAdvisementRepository::class);
    }
}
