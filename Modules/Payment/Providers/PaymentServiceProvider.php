<?php

namespace Modules\Payment\Providers;

use App\Models\OrderOffer;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Handlers\GuarantorPaymentHandler;
use Modules\Payment\Handlers\OrderPaymentHandler;
use Modules\Payment\Handlers\TopUpPaymentHandler;
use Modules\Payment\Registry\PaymentHandlerRegistry;
use Modules\Wallet\Models\TopUpRequest;
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

        $registry = $this->app->make(PaymentHandlerRegistry::class);

        $registry->register(
            OrderOffer::class,
            $this->app->make(OrderPaymentHandler::class),
        );

        $registry->register(
            TopUpRequest::class,
            $this->app->make(TopUpPaymentHandler::class),
        );

        $registry->register(
            GuarantorRequest::class,
            $this->app->make(GuarantorPaymentHandler::class),
        );

        $registry->register(
            GuarantorInstallment::class,
            $this->app->make(GuarantorPaymentHandler::class),
        );
    }
}
