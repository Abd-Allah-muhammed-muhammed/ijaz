<?php

namespace Modules\Cms\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Cms\Models\Question;

/** @see Question */
class QuestionCollection extends ResourceCollection
{
    public $collects = QuestionResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
