<?php

namespace Modules\Cms\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cms\Models\Page;

/** @mixin Page */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'translations' => $this->when($this->relationLoaded('translations'), fn () => $this->translations->mapWithKeys(fn ($item) => [$item->locale => [
                'title' => $item->title,
                'content' => $item->content,
            ]])),
        ];
    }
}
