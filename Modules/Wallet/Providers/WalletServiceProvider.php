<?php

namespace Modules\Wallet\Providers;

use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\Repositories\WalletRepository;
use Modules\Wallet\Repositories\WalletTransactionRepository;
use Modules\Wallet\Services\WalletService;
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

        $this->app->bind(
            WalletRepositoryInterface::class,
            WalletRepository::class,
        );

        $this->app->bind(
            WalletTransactionRepositoryInterface::class,
            WalletTransactionRepository::class,
        );

        $this->app->bind(
            WalletService::class,
            WalletService::class,
        );
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(module_path('Wallet', 'Database/Migrations'));

        // Policies — added in later phases
    }
}
