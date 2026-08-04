<?php

namespace Modules\Settings\Actions\Setting;

use App\Support\LookupCache;
use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\DTOs\UpdateSettingsDTO;

class UpdateSettingsAction
{
    public function __construct(
        private readonly SettingRepositoryInterface $repository,
    ) {}

    public function handle(UpdateSettingsDTO $dto): void
    {
        $this->repository->updateMany($dto->toRepositoryUpdates());

        cache()->forget('settings');
        app()->forgetInstance('settings');
        LookupCache::forget('settings:public');
    }
}
