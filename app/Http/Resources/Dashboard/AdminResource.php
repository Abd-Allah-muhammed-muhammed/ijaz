<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Admin */
class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'image' => $this->image_url,
            'address' => $this->address,
            'job' => $this->job,
            'root' => $this->root,
            'online' => $this->online,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
        ];
    }
}
