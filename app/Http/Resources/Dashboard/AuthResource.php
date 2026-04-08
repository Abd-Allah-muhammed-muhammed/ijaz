<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Admin;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Admin | Provider
 *
 * @see Admin
 */
class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return match (get_class($this->resource)) {
            Admin::class => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'image' => $this->getImageUrl(),
                'root' => $this->root,
                'phone' => $this->phone,
                'type' => $this->getType(),
                'socket_id' => $this->getAuthIdentifierForBroadcasting(),
                'permissions' => $this->getPermissions(),
                'roles' => $this->getRoles(),
            ],
            Provider::class => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'image' => $this->getImageUrl(),
                'phone' => $this->phone,
                'type' => $this->getType(),
                'socket_id' => $this->getAuthIdentifierForBroadcasting(),
                'categories' => $this->categories()->pluck('id')->toArray(),
            ],
            default => [],
        };

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'image' => $this->getImageUrl(),
            'root' => $this->root,
            'phone' => $this->phone,
            'type' => $this->getType(),
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'permissions' => $this->getPermissions(),
            'roles' => $this->getRoles(),
        ];
    }

    private function getImageUrl(): ?string
    {
        return match (get_class($this->resource)) {
            Admin::class => $this->image_url,
            Provider::class => $this->logo_url,
            default => null,
        };

    }

    private function getPermissions(): array
    {
        if (method_exists($this, 'getAllPermissions')) {
            return $this->getAllPermissions()?->pluck('name')?->toArray();
        }

        return [];
    }

    private function getRoles(): array
    {
        if (method_exists($this, 'getRoleNames')) {
            return $this->getRoleNames()?->toArray();
        }

        return [];
    }
}
