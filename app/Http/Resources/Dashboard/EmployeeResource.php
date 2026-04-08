<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'id_image' => $this->id_image_url,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_at' => $this->created_at,
            'provider_id' => $this->provider_id,

            'provider' => ProviderResource::make($this->whenLoaded('provider')),
            'company' => ProviderResource::make($this->whenLoaded('company')),
        ];
    }
}
