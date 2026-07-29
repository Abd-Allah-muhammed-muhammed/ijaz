<?php

namespace Modules\Cms\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Cms\Models\Question;

/** @mixin Question */
class QuestionsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'answer' => $this->answer,
        ];
    }
}
