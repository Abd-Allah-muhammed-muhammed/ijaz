<?php

namespace Modules\Settings\Repositories;

use Modules\Settings\Contracts\Repositories\SettingHistoryRepositoryInterface;
use Modules\Settings\Models\SettingHistory;

class SettingHistoryRepository implements SettingHistoryRepositoryInterface
{
    /**
     * @param  array{key: string, old_content: ?string, new_content: ?string, admin_id: ?int, actor_name: ?string}  $attributes
     */
    public function create(array $attributes): SettingHistory
    {
        return SettingHistory::query()->create($attributes);
    }
}
