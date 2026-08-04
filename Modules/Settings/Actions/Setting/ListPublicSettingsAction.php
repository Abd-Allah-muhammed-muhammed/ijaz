<?php

namespace Modules\Settings\Actions\Setting;

use App\Support\LookupCache;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;

/**
 * Public catalog settings — only rows with is_public = true.
 *
 * The app('settings') singleton still caches every key→content pair for
 * internal consumers; this action caches the public subset separately so
 * Dashboard is_public toggles take effect after UpdateSettingsAction forgets
 * both keys.
 */
class ListPublicSettingsAction
{
    public function __construct(
        private readonly SettingRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string, string>
     */
    public function handle(): array
    {
        /** @var array<string, string> */
        return LookupCache::rememberForever(
            'settings:public',
            fn (): array => $this->repository->pluckPublicContentByKey(),
        );
    }
}
