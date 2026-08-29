<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Enums\SettingGroupEnum;
use Modules\Settings\Enums\SettingTypeEnum;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'content',
        'type',
        'group',
        'is_public',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'group' => SettingGroupEnum::class,
            'type' => SettingTypeEnum::class,
        ];
    }
}
