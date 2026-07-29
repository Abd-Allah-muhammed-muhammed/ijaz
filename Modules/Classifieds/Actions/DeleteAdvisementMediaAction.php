<?php

namespace Modules\Classifieds\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DeleteAdvisementMediaAction
{
    public function handle(Model $advisement, Media $media): void
    {
        if (! Schema::hasTable('media') || $media->model_id !== $advisement->id || $media->model_type !== $advisement::class) {
            throw new AccessDeniedHttpException;
        }

        $media->delete();
    }
}
