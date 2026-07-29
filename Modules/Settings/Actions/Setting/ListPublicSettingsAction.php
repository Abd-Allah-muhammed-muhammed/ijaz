<?php

namespace Modules\Settings\Actions\Setting;

use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;

/**
 * Public catalog settings — only rows with is_public = true.
 *
 * The app('settings') singleton still caches every key→content pair for
 * internal consumers; this action queries the DB flag so Dashboard toggles
 * take effect immediately after cache invalidation on update.
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
        return $this->repository->pluckPublicContentByKey();
    }
}
