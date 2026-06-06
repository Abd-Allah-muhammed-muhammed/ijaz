<?php

namespace Modules\Opportunity\Http\Resources;

use App\Http\Resources\Api\BaseCollection;

class CommentCollection extends BaseCollection
{
    public $collects = CommentResource::class;
}
