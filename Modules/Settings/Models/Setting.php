<?php

namespace Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    /**
     * Canonical Dashboard tab order (UI taxonomy).
     *
     * @var list<string>
     */
    public const GROUPS = [
        'general',
        'wallet',
        'payment',
        'guarantor',
        'chat',
    ];

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
        ];
    }
}
