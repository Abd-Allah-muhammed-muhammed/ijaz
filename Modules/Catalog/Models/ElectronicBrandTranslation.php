<?php

namespace Modules\Catalog\Models;

use App\Services\Normalize\Normalize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectronicBrandTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'locale'];

    public function electronicBrand(): BelongsTo
    {
        return $this->belongsTo(ElectronicBrand::class);
    }

    protected static function booted(): void
    {
        static::saving(static function ($translation) {
            if ($translation->isDirty('name') && ! empty($translation->locale)) {
                $translation->normalized_name = Normalize::make($translation->name, $translation->locale)->toString();
            }
        });
    }
}
