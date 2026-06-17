<?php

namespace Modules\Guarantor\Providers;

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
        // Repository bindings will be added in Phase 6
    }

    public function boot(): void
    {
        parent::boot();
        // Policies will be added in Phase 10
        // Commands will be added in Phase 15
    }
}
