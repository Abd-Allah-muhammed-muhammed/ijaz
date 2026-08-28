<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Slim public publisher card — id, name, and image only (no phone/email).
 *
 * @mixin User
 */
class PublisherResource extends JsonResource
{
    /**
     * @return array{id: int|string, name: string, image: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->image_url,
        ];
    }
}
