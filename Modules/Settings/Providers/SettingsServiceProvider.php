<?php

namespace Modules\Settings\Providers;

use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Repositories\SettingRepository;
use Nwidart\Modules\Support\ModuleServiceProvider;

class SettingsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Settings';

    protected string $nameLower = 'settings';

    protected array $providers = [
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(SettingRepositoryInterface::class, SettingRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
