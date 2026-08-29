<?php

namespace Modules\Settings\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Settings\Models\SettingHistory;

interface SettingHistoryRepositoryInterface
{
    /**
     * @param  array{key: string, old_content: ?string, new_content: ?string, admin_id: ?int}  $attributes
     */
    public function create(array $attributes): SettingHistory;

    /**
     * @return Collection<int, SettingHistory>
     */
    public function listForKey(string $key): Collection;
}
