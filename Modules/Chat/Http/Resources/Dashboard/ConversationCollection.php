<?php

namespace Modules\Chat\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Chat\Http\Resources\ConversationResource;

/**
 * @extends ResourceCollection<ConversationResource>
 */
class ConversationCollection extends ResourceCollection
{
    public $collects = ConversationResource::class;

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
