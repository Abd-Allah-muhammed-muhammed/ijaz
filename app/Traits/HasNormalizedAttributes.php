<?php

namespace App\Traits;

use App\Services\Normalize\Normalize;

trait HasNormalizedAttributes
{
    /**
     * The "booted" method of the model.
     */
    protected static function bootHasNormalizedAttributes(): void
    {
        static::saving(function ($model) {
            $attributesMap = $model->getHasNormalizedAttributesMap();
            $locale = $model->locale ?: app()->getLocale() ?: config('app.locale', 'ar');

            foreach ($attributesMap as $attribute => $normalizedAttribute) {
                if ($model->isDirty($attribute)) {
                    $model->{$normalizedAttribute} = Normalize::make($model->{$attribute}, $locale);
                }
            }
        });
    }

    /**
     * @return array<string,string>
     */
    abstract protected function getHasNormalizedAttributesMap(): array;
}
