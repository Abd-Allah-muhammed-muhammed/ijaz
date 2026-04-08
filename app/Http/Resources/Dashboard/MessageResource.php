<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'title' => $this->title,
            'content' => $this->content,
            'created_at' => $this->created_at,

        ];
    }
}
