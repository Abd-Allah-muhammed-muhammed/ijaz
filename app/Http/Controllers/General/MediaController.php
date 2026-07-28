<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Services\Media\MediaAccessService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaAccessService $mediaAccessService,
    ) {}

    public function media(Media $media): BinaryFileResponse
    {
        $path = $this->mediaAccessService->authorizeAndResolvePath($media, 'generic');

        return response()->file($path);
    }

    public function file(Media $media): BinaryFileResponse
    {
        $path = $this->mediaAccessService->authorizeAndResolvePath($media, 'owned');

        return response()->file($path);
    }

    public function chatMedia(Media $media): BinaryFileResponse
    {
        $path = $this->mediaAccessService->authorizeAndResolvePath($media, 'chat');

        return response()->file($path);
    }
}
