<?php

namespace Modules\Reviews\Providers;

use Modules\Reviews\Contracts\Repositories\ReviewRepositoryInterface;
use Modules\Reviews\Repositories\ReviewRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class ReviewsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Reviews';

    protected string $nameLower = 'reviews';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
