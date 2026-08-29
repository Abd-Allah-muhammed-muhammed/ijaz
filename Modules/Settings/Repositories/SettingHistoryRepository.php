<?php

namespace Modules\Settings\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Settings\Contracts\Repositories\SettingHistoryRepositoryInterface;
use Modules\Settings\Models\SettingHistory;

class SettingHistoryRepository implements SettingHistoryRepositoryInterface
{
    /**
     * @param  array{key: string, old_content: ?string, new_content: ?string, admin_id: ?int}  $attributes
     */
    public function create(array $attributes): SettingHistory
    {
        return SettingHistory::query()->create($attributes);
    }

    /**
     * @return Collection<int, SettingHistory>
     */
    public function listForKey(string $key): Collection
    {
        return SettingHistory::query()
            ->with('admin:id,name')
            ->where('key', $key)
            ->latest('id')
            ->get();
    }
}
