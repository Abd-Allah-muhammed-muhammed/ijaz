<?php

namespace Modules\Settings\Providers;

use Modules\Settings\Contracts\Repositories\SettingHistoryRepositoryInterface;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\Repositories\SettingHistoryRepository;
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
        $this->app->bind(SettingHistoryRepositoryInterface::class, SettingHistoryRepository::class);
    }

    public function boot(): void
    {
        parent::boot();
    }
}
