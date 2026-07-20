<?php

namespace Modules\Geo\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NationalityTranslation extends Model
{
    use HasNormalizedAttributes;

    public $timestamps = false;

    protected $fillable = ['name', 'normalized_name', 'locale', 'nationality_id'];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'name' => 'normalized_name',
        ];
    }
}
