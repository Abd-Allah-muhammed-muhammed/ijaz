<?php

namespace App\Http\Resources\Concerns;

use Illuminate\Http\Resources\MergeValue;
use Illuminate\Http\Resources\MissingValue;

/**
 * Safe merge of Astrotomic translated attributes under preventLazyLoading.
 *
 * Never pass `$this->whenLoaded('translation')` as a mergeWhen condition — MissingValue
 * is truthy, so the block always runs. Never pass an eager array that touches `$this->title`
 * / `$this->name` as a mergeWhen argument — PHP evaluates args before mergeWhen can
 * short-circuit. Always defer attribute access with a closure.
 */
trait MergesWhenTranslationLoaded
{
    /**
     * @param  callable(): array<string, mixed>  $attributes
     */
    protected function mergeWhenTranslationLoaded(callable $attributes): MergeValue|MissingValue
    {
        return $this->mergeWhen(
            $this->relationLoaded('translation') || $this->relationLoaded('translations'),
            $attributes,
        );
    }
}
