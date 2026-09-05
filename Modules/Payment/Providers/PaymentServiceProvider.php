<?php

namespace Modules\Payment\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Http\Middleware\RequirePaytabsIpnSignatureHeader;
use Modules\Payment\Repositories\PaymentRepository;
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

        $this->app->bind(
            PaymentRepositoryInterface::class,
            PaymentRepository::class,
        );

        $this->app->booting(function () {
            $this->bridgePaytabsConfig();
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->loadViewsFrom(module_path('Payment', 'resources/views'), 'payment');

        Blade::anonymousComponentPath(module_path('Payment', 'resources/views/components'), 'payment');

        $this->app->booted(function (): void {
            $route = Route::getRoutes()->getByName('payment_ipn');

            if ($route === null) {
                return;
            }

            $route->middleware(RequirePaytabsIpnSignatureHeader::class);
        });
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
