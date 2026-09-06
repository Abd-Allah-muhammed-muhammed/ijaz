<?php

namespace App\Support\Media;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Narrow MediaLibrary attach helper: optional clear + addMedia + toMediaCollection.
 *
 * Intentionally NOT a general upload service — domain policies (Guarantor KYC,
 * Chat attachments, Payout proofs) stay in their own Actions.
 */
final class ReplaceMediaOnModel
{
    /**
     * @param  UploadedFile|string  $file  Uploaded file or absolute filesystem path
     */
    public function attach(
        HasMedia $model,
        string $collection,
        UploadedFile|string $file,
        string $disk,
        bool $replace = true,
        ?string $fileName = null,
        ?string $name = null,
    ): Media {
        if ($replace) {
            $model->clearMediaCollection($collection);
        }

        $pending = $model->addMedia($file);

        if ($name !== null) {
            $pending->usingName($name);
        }

        if ($fileName !== null) {
            $pending->usingFileName($fileName);
        }

        return $pending->toMediaCollection($collection, $disk);
    }
}
