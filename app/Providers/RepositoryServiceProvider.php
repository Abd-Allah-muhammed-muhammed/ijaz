<?php

namespace App\Providers;

use App\Contracts\Repositories\CarBrandRepositoryInterface;
use App\Contracts\Repositories\CarCategoryRepositoryInterface;
use App\Contracts\Repositories\CarTypeRepositoryInterface;
use App\Contracts\Repositories\PropertyCategory\PropertyCategoryRepositoryInterface;
use App\Contracts\Repositories\PropertyType\PropertyTypeRepositoryInterface;
use App\Contracts\Services\CarBrandServiceInterface;
use App\Contracts\Services\CarCategoryServiceInterface;
use App\Contracts\Services\CarTypeServiceInterface;
use App\Repositories\CarBrandRepository;
use App\Repositories\CarCategoryRepository;
use App\Repositories\CarTypeRepository;
use App\Repositories\PropertyCategory\PropertyCategoryRepository;
use App\Repositories\PropertyType\PropertyTypeRepository;
use App\Services\CarBrandService;
use App\Services\CarCategoryService;
use App\Services\CarTypeService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    private array $binding_classes = [
        PropertyTypeRepositoryInterface::class => PropertyTypeRepository::class,
        PropertyCategoryRepositoryInterface::class => PropertyCategoryRepository::class,
        CarBrandRepositoryInterface::class => CarBrandRepository::class,
        CarTypeRepositoryInterface::class => CarTypeRepository::class,
        CarCategoryRepositoryInterface::class => CarCategoryRepository::class,
        CarBrandServiceInterface::class => CarBrandService::class,
        CarTypeServiceInterface::class => CarTypeService::class,
        CarCategoryServiceInterface::class => CarCategoryService::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        foreach ($this->binding_classes as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
