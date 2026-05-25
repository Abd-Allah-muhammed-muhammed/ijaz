<?php

namespace Modules\Catalog\Models;

use App\Services\Normalize\Normalize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['title', 'locale'];

    public function deviceCategory(): BelongsTo
    {
        return $this->belongsTo(DeviceCategory::class);
    }

    protected static function booted(): void
    {
        static::saving(static function ($translation) {
            if ($translation->isDirty('title') && ! empty($translation->locale)) {
                $translation->normalized_title = Normalize::make($translation->title, $translation->locale)->toString();
            }
        });
    }
}
