<?php

namespace Modules\Marketplace\Http\Resources\Dashboard;

use App\Http\Resources\Concerns\MergesWhenTranslationLoaded;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Marketplace\Models\Skill;

/** @mixin Skill */
class SkillResource extends JsonResource
{
    use MergesWhenTranslationLoaded;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            $this->mergeWhenTranslationLoaded(fn () => [
                'title' => $this->title,
            ]),
            'translations' => $this->whenLoaded('translations', function () {
                return $this->translations->keyBy('locale');
            }),
        ];
    }
}
