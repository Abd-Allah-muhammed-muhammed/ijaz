<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\Facades\Blade;
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

        $this->app->booting(function () {
            $this->bridgePaytabsConfig();
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadMigrationsFrom(module_path('Payment', 'Database/Migrations'));
        $this->loadViewsFrom(module_path('Payment', 'Resources/views'), 'payment');

        Blade::anonymousComponentPath(module_path('Payment', 'Resources/views/components'), 'payment');
    }

    private function bridgePaytabsConfig(): void
    {
        $mode = config('payment.drivers.paytabs.mode', 'test');
        $config = config("payment.drivers.paytabs.{$mode}", []);

        config([
            'paytabs.profile_id' => $config['profile_id'] ?? null,
            'paytabs.server_key' => $config['server_key'] ?? null,
            'paytabs.currency' => $config['currency'] ?? 'SAR',
            'paytabs.region' => $config['region'] ?? 'SAU',
        ]);
    }
}
