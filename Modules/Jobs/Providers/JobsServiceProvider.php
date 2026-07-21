<?php

namespace Modules\Jobs\Providers;

use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\Repositories\JobRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class JobsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Jobs';

    protected string $nameLower = 'jobs';

    public function register(): void
    {
        parent::register();

        $this->app->bind(JobRepositoryInterface::class, JobRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
