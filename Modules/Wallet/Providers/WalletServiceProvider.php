<?php

namespace Modules\Wallet\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class WalletServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Wallet';

    protected string $nameLower = 'wallet';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        // Repository + Service bindings — added in later phases
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(module_path('Wallet', 'Database/Migrations'));

        // Policies — added in later phases
    }
}
