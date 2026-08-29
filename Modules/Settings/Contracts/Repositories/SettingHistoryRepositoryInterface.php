<?php

namespace Modules\Settings\Contracts\Repositories;

use Modules\Settings\Models\SettingHistory;

interface SettingHistoryRepositoryInterface
{
    /**
     * @param  array{key: string, old_content: ?string, new_content: ?string, admin_id: ?int, actor_name: ?string}  $attributes
     */
    public function create(array $attributes): SettingHistory;
}
