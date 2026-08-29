<?php

namespace Modules\Settings\Actions\Setting;

use Illuminate\Database\Eloquent\Collection;
use Modules\Settings\Contracts\Repositories\SettingHistoryRepositoryInterface;
use Modules\Settings\Models\SettingHistory;

class ListSettingHistoryAction
{
    public function __construct(
        private readonly SettingHistoryRepositoryInterface $historyRepository,
    ) {}

    /**
     * @return Collection<int, SettingHistory>
     */
    public function handle(string $key): Collection
    {
        return $this->historyRepository->listForKey($key);
    }
}
