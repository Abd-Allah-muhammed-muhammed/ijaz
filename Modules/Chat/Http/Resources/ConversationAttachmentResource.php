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
            return array_merge($base, [
                'available' => true,
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
        try {
            return Storage::disk($media->disk)->exists($media->getPathRelativeToRoot());
        } catch (\Throwable) {
            return false;
        }
    }
}
