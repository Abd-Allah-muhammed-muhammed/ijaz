<?php

namespace App\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = is_array($this->data) ? $this->data : [];

        return [
            'id' => $this->id,
            'type' => class_basename((string) $this->type),
            'title' => trans(
                (string) ($data['title_translated_key'] ?? ''),
                is_array($data['translated_attributes'] ?? null) ? $data['translated_attributes'] : [],
            ),
            'body' => trans(
                (string) ($data['body_translated_key'] ?? ''),
                is_array($data['translated_attributes'] ?? null) ? $data['translated_attributes'] : [],
            ),
            'data' => $data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at?->diffForHumans(),
            'created_at_iso' => $this->created_at?->toIso8601String(),
        ];
    }
}
