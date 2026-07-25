<?php

namespace App\Support;

/*
 * Distinct alias is required: Pint's no_unused_imports strips same-namespace
 * `use App\Support\Normalize;` as redundant, and no_unneeded_import_alias strips
 * `as Normalize`. A non-matching alias keeps the explicit import searchable
 * (see .cursor/rules/layered-architecture.mdc — Explicit imports).
 */
use App\Support\Normalize as TextNormalize;

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
                    $model->{$normalizedAttribute} = TextNormalize::make($model->{$attribute}, $locale);
                }
            }
        });
    }

    /**
     * @return array<string,string>
     */
    abstract protected function getHasNormalizedAttributesMap(): array;
}
