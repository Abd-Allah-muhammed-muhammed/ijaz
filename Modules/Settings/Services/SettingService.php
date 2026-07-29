<?php

namespace Modules\Settings\Services;

use Illuminate\Support\Collection;
use Modules\Settings\Actions\Setting\ListPublicSettingsAction;
use Modules\Settings\Actions\Setting\ListSettingsGroupedAction;
use Modules\Settings\Actions\Setting\UpdateSettingsAction;
use Modules\Settings\DTOs\UpdateSettingsDTO;
use Modules\Settings\Models\Setting;

class SettingService
{
    public function __construct(
        private readonly ListSettingsGroupedAction $listGroupedAction,
        private readonly UpdateSettingsAction $updateAction,
        private readonly ListPublicSettingsAction $listPublicAction,
    ) {}

    /**
     * @return Collection<string, \Illuminate\Database\Eloquent\Collection<int, Setting>>
     */
    public function indexGrouped(): Collection
    {
        return $this->listGroupedAction->handle();
    }

    public function update(UpdateSettingsDTO $dto): void
    {
        $this->updateAction->handle($dto);
    }

    /**
     * @return array<string, string>
     */
    public function publicBag(): array
    {
        return $this->listPublicAction->handle();
    }
}
