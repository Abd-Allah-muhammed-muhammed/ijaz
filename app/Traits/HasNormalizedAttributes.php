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
            foreach ($attributesMap as $attribute => $normalizedAttribute) {
                if ($model->isDirty($attribute)) {
                    $model->{$normalizedAttribute} = Normalize::make($model->{$attribute}, $model->locale);
                }
            }
        });
    }

    /**
     * @return array<string,string>
     */
    abstract protected function getHasNormalizedAttributesMap(): array;
}
