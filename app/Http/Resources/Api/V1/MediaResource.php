<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\ProviderTypeFilesEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'name' => $this->name,
            'collection_name' => $this->collection_name,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'type' => $this->type,
            'url' => $this->getUrlForUser(),
            'extension' => $this->extension,
            'size' => $this->humanReadableSize,
        ];
    }

    protected function getUrlForUser(): string
    {
        if ($this->disk === 'public') {
            return $this->getFullUrl();
        }
        if (in_array($this->collection_name, ProviderTypeFilesEnum::collect()->pluck('value')->toArray(), true)) {
            return route('media.file-path', $this);
        }

        return $this->getFullUrl();
    }
}
