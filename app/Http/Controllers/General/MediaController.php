<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Modules\Chat\Models\ConversationMessage;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function media(Media $media)
    {
        /**
         *  validation checks
         */
        return response()->file($media->getPath());
    }

    public function file(Media $media): ?BinaryFileResponse
    {
        $storage = Storage::disk('local');
        $filePath = $media->getPathRelativeToRoot();
        abort_unless($storage->exists($filePath), 404);
        if (auth('admin')->check()) {
            //    if (auth('admin')->check() && auth('admin')->user()->can('view all media')) {
            return response()->file($media->getPath());
        }
        if (auth('provider')->check() && $media->model()->is(auth('provider')->user())) {
            return response()->file($media->getPath());
        }

        abort(404);
    }

    public function chatMedia(Media $media): BinaryFileResponse
    {
        /**
         *  validation checks
         */
        $auth = auth()->user();
        if (! $media->model instanceof ConversationMessage || ! ($media->model->sender()->is($auth) || $media->model->receiver()->is($auth))) {
            abort(404);
        }

        return response()->file($media->getPath());

    }
}
