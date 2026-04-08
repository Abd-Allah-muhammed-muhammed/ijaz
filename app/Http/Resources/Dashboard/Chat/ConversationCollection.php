<?php

namespace App\Http\Resources\Dashboard\Chat;

use App\Services\Chat\Resources\ConversationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @extends ResourceCollection<ConversationResource>
 */
class ConversationCollection extends ResourceCollection
{
    public $collects = ConversationResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
