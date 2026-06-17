<?php

namespace Modules\Guarantor\Providers;

use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Repositories\GuarantorRepository;
use Modules\Guarantor\Repositories\InstallmentRepository;
use Modules\Guarantor\Repositories\StatusHistoryRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class GuarantorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Guarantor';

    protected string $nameLower = 'guarantor';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(
            GuarantorRepositoryInterface::class,
            GuarantorRepository::class,
        );

        $this->app->bind(
            InstallmentRepositoryInterface::class,
            InstallmentRepository::class,
        );

        $this->app->bind(
            StatusHistoryRepositoryInterface::class,
            StatusHistoryRepository::class,
        );
    }

    public function boot(): void
    {
        parent::boot();
        // Policies will be added in Phase 10
        // Commands will be added in Phase 15
    }
}
