<?php

namespace App\Models;

use App\Services\Chat\Contracts\HasConversation;
use App\Traits\HasBroadcastChanel;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class System extends Authenticatable implements HasConversation
{
    use HasApiTokens, HasBroadcastChanel;

    //
    public function getType(): string
    {
        return 'system';
    }

    protected $fillable = [
        'name', 'online',
    ];

    public function getImageUrl(): string
    {
        return asset('media/avatars/blank.png');
    }
}
