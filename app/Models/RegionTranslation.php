<?php

namespace App\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionTranslation extends Model
{
    use HasNormalizedAttributes;

    public $timestamps = false;

    protected $fillable = ['title', 'normalized_title', 'locale', 'region_id'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'title' => 'normalized_title',
        ];
    }
}
