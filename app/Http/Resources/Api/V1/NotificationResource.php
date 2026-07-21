<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Models\Category;

/** @mixin Category */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => basename($this->type),
            'data' => $this->data,
            'title' => trans($this->data['title_translated_key'] ?? '', $this->data['translated_attributes'] ?? []),
            'body' => trans($this->data['body_translated_key'] ?? '', $this->data['translated_attributes'] ?? []),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
