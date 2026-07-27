<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Settings\Enums\SettingGroupEnum;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'content',
        'group',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'group' => SettingGroupEnum::class,
        ];
    }
}
