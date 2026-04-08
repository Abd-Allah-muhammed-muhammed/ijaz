<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\NationalityResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see User
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'name' => $this->name,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'phone' => $this->phone,
            'image' => $this->image_url,
            'language' => $this->language,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'email' => $this->email,
            'nationality_id' => $this->nationality_id,
            'nationality' => NationalityResource::make($this->whenLoaded('nationality')),
        ];
    }
}
