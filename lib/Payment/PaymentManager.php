<?php

namespace Lib\Payment;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Manager;
use Lib\Payment\Contracts\IPaymentGate;
use Lib\Payment\Gates\PayTabsGate;
use Lib\Payment\Gates\TestingGate;

class PaymentManager extends Manager
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultDriver()
    {
        return config('app.payment.driver', default: 'testing');
    }

    /**
     * Create a driver instance.
     */
    public function createTestingDriver(): IPaymentGate
    {
        return new TestingGate;
    }

    /**
     * @throws BindingResolutionException
     */
    public function createPayTabsDriver(): IPaymentGate
    {
        return $this->container->make(PayTabsGate::class);
    }
}
