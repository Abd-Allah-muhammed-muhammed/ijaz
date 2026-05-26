<?php

namespace Modules\Catalog\Providers;

use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Contracts\Services\CarBrandServiceInterface;
use Modules\Catalog\Contracts\Services\CarCategoryServiceInterface;
use Modules\Catalog\Contracts\Services\CarTypeServiceInterface;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\Contracts\Services\ElectronicBrandServiceInterface;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;
use Modules\Catalog\Repositories\CarBrandRepository;
use Modules\Catalog\Repositories\CarCategoryRepository;
use Modules\Catalog\Repositories\CarTypeRepository;
use Modules\Catalog\Repositories\DeviceCategoryRepository;
use Modules\Catalog\Repositories\ElectronicBrandRepository;
use Modules\Catalog\Repositories\PropertyCategoryRepository;
use Modules\Catalog\Repositories\PropertyTypeRepository;
use Modules\Catalog\Repositories\SpecializationRepository;
use Modules\Catalog\Services\CarBrandService;
use Modules\Catalog\Services\CarCategoryService;
use Modules\Catalog\Services\CarTypeService;
use Modules\Catalog\Services\DeviceCategoryService;
use Modules\Catalog\Services\ElectronicBrandService;
use Modules\Catalog\Services\SpecializationService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Catalog';

    protected string $nameLower = 'catalog';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(PropertyTypeRepositoryInterface::class, PropertyTypeRepository::class);
        $this->app->bind(PropertyCategoryRepositoryInterface::class, PropertyCategoryRepository::class);
        $this->app->bind(CarBrandRepositoryInterface::class, CarBrandRepository::class);
        $this->app->bind(CarTypeRepositoryInterface::class, CarTypeRepository::class);
        $this->app->bind(CarCategoryRepositoryInterface::class, CarCategoryRepository::class);
        $this->app->bind(DeviceCategoryRepositoryInterface::class, DeviceCategoryRepository::class);
        $this->app->bind(ElectronicBrandRepositoryInterface::class, ElectronicBrandRepository::class);
        $this->app->bind(SpecializationRepositoryInterface::class, SpecializationRepository::class);

        $this->app->bind(CarBrandServiceInterface::class, CarBrandService::class);
        $this->app->bind(CarTypeServiceInterface::class, CarTypeService::class);
        $this->app->bind(CarCategoryServiceInterface::class, CarCategoryService::class);
        $this->app->bind(DeviceCategoryServiceInterface::class, DeviceCategoryService::class);
        $this->app->bind(ElectronicBrandServiceInterface::class, ElectronicBrandService::class);
        $this->app->bind(SpecializationServiceInterface::class, SpecializationService::class);
    }
}
