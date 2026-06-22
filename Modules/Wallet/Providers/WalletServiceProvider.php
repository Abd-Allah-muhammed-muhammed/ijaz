<?php

namespace Modules\Wallet\Providers;

use Illuminate\Support\Facades\Event;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\Listeners\HandleTopUpPaymentCompleted;
use Modules\Wallet\Listeners\HandleTopUpPaymentFailed;
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

        Event::listen(PaymentCompleted::class, HandleTopUpPaymentCompleted::class);
        Event::listen(PaymentFailed::class, HandleTopUpPaymentFailed::class);

        // Policies — added in later phases
    }
}
