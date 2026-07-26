<?php

namespace Modules\Settings\Actions\Setting;

use Modules\Settings\Contracts\Repositories\SettingRepositoryInterface;
use Modules\Settings\DTOs\UpdateSettingsDTO;

class UpdateSettingsAction
{
    public function __construct(
        private readonly SettingRepositoryInterface $repository,
    ) {}

    public function handle(UpdateSettingsDTO $dto): void
    {
        $this->repository->updateMany($dto->values);

        cache()->forget('settings');
        app()->forgetInstance('settings');
    }
}
