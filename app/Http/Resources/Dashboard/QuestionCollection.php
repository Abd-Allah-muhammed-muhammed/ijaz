<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Question */
class QuestionCollection extends ResourceCollection
{
    public $collects = QuestionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
