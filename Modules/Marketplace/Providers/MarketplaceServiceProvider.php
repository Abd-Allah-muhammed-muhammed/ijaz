<?php

namespace Modules\Marketplace\Providers;

use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Repositories\CategoryRepository;
use Modules\Marketplace\Repositories\ProviderTypeRepository;
use Modules\Marketplace\Repositories\SkillRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MarketplaceServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Marketplace';

    protected string $nameLower = 'marketplace';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(SkillRepositoryInterface::class, SkillRepository::class);
        $this->app->bind(ProviderTypeRepositoryInterface::class, ProviderTypeRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
