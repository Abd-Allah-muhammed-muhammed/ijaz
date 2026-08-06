<?php

namespace Modules\Chat\Http\Resources;

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Models\ConversationAttachment;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Chat attachment payload (MediaLibrary or legacy conversation_attachments).
 *
 * Same shape as MediaResource, plus `available` — false when the backing file
 * is missing/unreadable on disk (legacy rows that failed migration, or Media
 * records whose files were deleted).
 *
 * @mixin Media|ConversationAttachment
 */
class ConversationAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Media) {
            return $this->fromMedia($this->resource);
        }

        if ($this->resource instanceof ConversationAttachment) {
            return $this->fromLegacy($this->resource);
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function unavailablePlaceholder(string $id, ?string $fileName = null): array
    {
        return [
            'id' => $id,
            'name' => $fileName ?: null,
            'collection_name' => 'attachments',
            'file_name' => $fileName ?: '',
            'mime_type' => null,
            'type' => 'application',
            'url' => '',
            'extension' => $fileName ? pathinfo($fileName, PATHINFO_EXTENSION) : '',
            'size' => null,
            'available' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fromMedia(Media $media): array
    {
        $base = MediaResource::make($media)->resolve();
        $available = $this->mediaFileExists($media);

        return array_merge($base, [
            'available' => $available,
            'url' => $available ? ($base['url'] ?? '') : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fromLegacy(ConversationAttachment $attachment): array
    {
        $disk = $attachment->store ?: 'public';
        $available = filled($attachment->path)
            && Storage::disk($disk)->exists($attachment->path);

        $extension = pathinfo((string) $attachment->filename, PATHINFO_EXTENSION);

        return [
            'id' => $attachment->id,
            'name' => pathinfo((string) $attachment->filename, PATHINFO_FILENAME) ?: $attachment->filename,
            'collection_name' => 'attachments',
            'file_name' => $attachment->filename,
            'mime_type' => null,
            'type' => $attachment->type ?: ($extension === 'pdf' ? 'pdf' : 'application'),
            'url' => $available ? (string) $attachment->url : '',
            'extension' => $extension,
            'size' => null,
            'available' => $available,
        ];
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
