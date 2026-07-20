<?php

namespace Modules\Geo\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CityTranslation extends Model
{
    use HasNormalizedAttributes;

    public $timestamps = false;

    protected $fillable = ['title', 'normalized_title', 'locale', 'city_id'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'title' => 'normalized_title',
        ];
    }
}
