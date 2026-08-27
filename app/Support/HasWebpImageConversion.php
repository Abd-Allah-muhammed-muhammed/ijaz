<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Queued WebP conversion for image media (originals preserved).
 *
 * Models using InteractsWithMedia should call {@see registerWebpImageConversion()}
 * from their {@see registerMediaConversions()} override.
 *
 * Queue name comes from config('media-library.queue_name') (media-conversions).
 */
trait HasWebpImageConversion
{
    /**
     * Register the shared `webp` conversion for image MIME types only.
     *
     * PDFs and other non-images are skipped. Override
     * {@see webpConversionCollections()} to limit which collections qualify
     * (e.g. Guarantor `files` but never `requester_signature` / `counterparty_signature`).
     */
    protected function registerWebpImageConversion(?Media $media = null): void
    {
        if ($media === null || ! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        $collections = $this->webpConversionCollections();

        if (is_array($collections) && $collections !== [] && ! in_array($media->collection_name, $collections, true)) {
            return;
        }

        $conversion = $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(82)
            ->width(1920)
            ->queued();

        if (is_array($collections) && $collections !== []) {
            $conversion->performOnCollections(...$collections);
        }
    }

    /**
     * Collections eligible for WebP conversion, or null for every collection
     * that receives an image upload on this model.
     *
     * @return list<string>|null
     */
    protected function webpConversionCollections(): ?array
    {
        return null;
    }
}
