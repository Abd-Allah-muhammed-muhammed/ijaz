<?php

namespace Modules\Settings\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Settings\Enums\SettingGroupEnum;
use Modules\Settings\Enums\SettingTypeEnum;
use Modules\Settings\Models\Setting;

/** @mixin Setting */
class SettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'content' => $this->content,
            'type' => $this->type?->value ?? SettingTypeEnum::Text->value,
            'group' => $this->group?->value ?? SettingGroupEnum::General->value,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
