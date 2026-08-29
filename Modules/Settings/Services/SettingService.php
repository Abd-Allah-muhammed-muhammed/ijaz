<?php

namespace Modules\Settings\Services;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Modules\Settings\Actions\Setting\ListPublicSettingsAction;
use Modules\Settings\Actions\Setting\ListSettingHistoryAction;
use Modules\Settings\Actions\Setting\ListSettingsGroupedAction;
use Modules\Settings\Actions\Setting\UpdateSettingsAction;
use Modules\Settings\DTOs\UpdateSettingsDTO;
use Modules\Settings\Models\Setting;
use Modules\Settings\Models\SettingHistory;

class SettingService
{
    public function __construct(
        private readonly ListSettingsGroupedAction $listGroupedAction,
        private readonly UpdateSettingsAction $updateAction,
        private readonly ListPublicSettingsAction $listPublicAction,
        private readonly ListSettingHistoryAction $listHistoryAction,
    ) {}

    /**
     * @return SupportCollection<string, Collection<int, Setting>>
     */
    public function indexGrouped(): SupportCollection
    {
        return $this->listGroupedAction->handle();
    }

    public function update(UpdateSettingsDTO $dto, ?Admin $admin = null): void
    {
        $this->updateAction->handle($dto, $admin);
    }

    /**
     * @return array<string, string>
     */
    public function publicBag(): array
    {
        return $this->listPublicAction->handle();
    }

    /**
     * @return Collection<int, SettingHistory>
     */
    public function historyForKey(string $key): Collection
    {
        return $this->listHistoryAction->handle($key);
    }
}
