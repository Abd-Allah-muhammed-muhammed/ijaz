<?php

namespace Modules\Settings\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Settings\Models\SettingHistory;

/** @mixin SettingHistory */
class SettingHistoryResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     key: string,
     *     old_content: ?string,
     *     new_content: ?string,
     *     created_at: ?string,
     *     actor: array{id: int, name: string}|null
     * }
     */
    public function toArray(Request $request): array
    {
        $admin = $this->admin;

        return [
            'id' => $this->id,
            'key' => $this->key,
            'old_content' => $this->old_content,
            'new_content' => $this->new_content,
            'created_at' => $this->created_at?->toIso8601String(),
            'actor' => $admin === null
                ? null
                : [
                    'id' => (int) $admin->id,
                    'name' => (string) $admin->name,
                ],
        ];
    }
}
