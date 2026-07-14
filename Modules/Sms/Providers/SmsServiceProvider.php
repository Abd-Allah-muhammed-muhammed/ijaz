<?php

namespace Modules\Sms\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class SmsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Sms';

    protected string $nameLower = 'sms';

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(module_path('Sms', 'config/sms.php'), 'sms');
    }

    public function boot(): void
    {
        parent::boot();
    }
}
