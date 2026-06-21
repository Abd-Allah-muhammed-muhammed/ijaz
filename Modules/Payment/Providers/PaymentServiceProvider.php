<?php

namespace Modules\Payment\Providers;

use Modules\Payment\Registry\PaymentHandlerRegistry;
use Modules\Payment\Services\PaymentService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PaymentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Payment';

    protected string $nameLower = 'payment';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(module_path('Payment', 'config/payment.php'), 'payment');

        $this->app->singleton(PaymentHandlerRegistry::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(module_path('Payment', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('Payment', 'Resources/views'), 'payment');

        // Handlers registered in Phase 10
    }
}