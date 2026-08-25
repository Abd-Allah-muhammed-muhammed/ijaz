<?php

namespace Modules\Payout\Providers;

use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Repositories\PayoutRequestRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PayoutServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Payout';

    protected string $nameLower = 'payout';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(PayoutRequestRepositoryInterface::class, PayoutRequestRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
