<?php

namespace App\Services\Chat\Resources;

use App\Models\ConversationAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ConversationAttachment */
/** @see  ConversationAttachment */
class ConversationAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'filename' => $this->filename,
            'url' => $this->url,
        ];
    }
}
