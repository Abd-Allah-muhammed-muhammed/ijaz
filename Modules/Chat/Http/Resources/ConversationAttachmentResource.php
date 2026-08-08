<?php

namespace Modules\Chat\Http\Resources;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Chat attachment payload — MediaLibrary only (MediaResource shape + `available`).
 *
 * @mixin Media
 */
class ConversationAttachmentResource extends JsonResource
{
    /**
     * Request attribute bag key for disk+path → exists memoization.
     * Request-scoped only — never persist across requests.
     */
    public const EXISTS_CACHE_ATTRIBUTE = 'chat.attachment_exists_cache';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Media) {
            return [];
        }

        return $this->fromMedia($this->resource);
    }

    /**
     * Honest unavailable card — never invent a fake filename.
     *
     * @return array<string, mixed>
     */
    public static function unavailablePlaceholder(string $id): array
    {
        return [
            'id' => $id,
            'name' => null,
            'collection_name' => 'attachments',
            'file_name' => '',
            'mime_type' => null,
            'type' => 'application',
            'url' => '',
            'extension' => '',
            'size' => null,
            'available' => false,
            'label' => __('This attachment is no longer available'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromMedia(Media $media): array
    {
        $base = MediaResource::make($media)->resolve();
        $available = $this->mediaFileExists($media);

        if ($available) {
            // Prefer queued WebP conversion when ready; otherwise keep the original URL
            // so images still display while the worker is backed up or stalled.
            return array_merge($base, [
                'available' => true,
                'url' => $media->getAvailableFullUrl(['webp']),
            ]);
        }

        // File missing on disk — keep id for keys, clear download URL / display name
        // so the UI never presents a misleading "filename" as if it were openable.
        return array_merge($base, [
            'available' => false,
            'url' => '',
            'file_name' => '',
            'name' => null,
            'label' => __('This attachment is no longer available'),
        ]);
    }

    private function mediaFileExists(Media $media): bool
    {
        $path = $media->getPathRelativeToRoot();
        $cacheKey = $media->disk.'|'.$path;

        /** @var array<string, bool> $cache */
        $cache = request()->attributes->get(self::EXISTS_CACHE_ATTRIBUTE, []);

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        try {
            $exists = Storage::disk($media->disk)->exists($path);
        } catch (\Throwable) {
            $exists = false;
        }

        $cache[$cacheKey] = $exists;
        request()->attributes->set(self::EXISTS_CACHE_ATTRIBUTE, $cache);

        return $exists;
    }
}
