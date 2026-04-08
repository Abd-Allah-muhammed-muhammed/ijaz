<?php

namespace App\Http\Resources\General;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** @see Admin */
class SkillCollection extends ResourceCollection
{
    //  public $collects = SkillResource::class;

    public function toArray(Request $request): array
    {
        return $this->collection->toArray();
    }
}
