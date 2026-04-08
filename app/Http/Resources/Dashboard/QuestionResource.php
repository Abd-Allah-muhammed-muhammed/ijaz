<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Question */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'answer' => $this->answer,
            'created_at' => $this->created_at,
            'translations' => $this->when($this->relationLoaded('translations'), fn () => $this->translations->mapWithKeys(fn ($item) => [$item->locale => [
                'title' => $item->title,
                'answer' => $item->answer,
            ]])),
        ];
    }
}
