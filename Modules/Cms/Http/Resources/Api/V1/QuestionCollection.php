<?php

namespace Modules\Cms\Http\Resources\Api\V1;

use App\Http\Resources\Api\BaseCollection;

class QuestionCollection extends BaseCollection
{
    public $collects = QuestionsResource::class;
}
