<?php

namespace Modules\Classifieds\Actions;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

class StoreAdvisementMediaAction
{
    /**
     * @param  array<int, UploadedFile>|null  $files
     */
    public function handle(HasMedia $model, ?array $files): void
    {
        if ($files === null || $files === []) {
            return;
        }

        foreach ($files as $file) {
            $model->addMedia($file)->toMediaCollection();
        }
    }
}
