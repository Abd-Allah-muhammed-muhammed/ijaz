<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User | Provider | Admin */
class OperationUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getType(),
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'image' => $this->image_url,
            'created_at' => $this->created_at,
        ];
    }
}
