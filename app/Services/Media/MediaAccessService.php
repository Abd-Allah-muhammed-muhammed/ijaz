<?php

namespace App\Services\Media;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Models\ConversationMessage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAccessService
{
    /**
     * Authorize download for the given context and return the absolute file path.
     *
     * Contexts (preserving prior MediaController behavior):
     * - generic: any authenticated admin/provider (route middleware); no extra ownership check
     * - owned: admin bypass (intentional — "view all media" was never seeded/enforced),
     *   or provider who owns the media's model; also requires the file exists on the local disk
     * - chat: media must belong to a ConversationMessage where the authenticated
     *   admin or provider is sender or receiver
     */
    public function authorizeAndResolvePath(Media $media, string $context): string
    {
        return match ($context) {
            'generic' => $this->resolveGeneric($media),
            'owned' => $this->resolveOwned($media),
            'chat' => $this->resolveChat($media),
            default => abort(404),
        };
    }

    private function resolveGeneric(Media $media): string
    {
        return $media->getPath();
    }

    private function resolveOwned(Media $media): string
    {
        $storage = Storage::disk('local');
        $filePath = $media->getPathRelativeToRoot();
        abort_unless($storage->exists($filePath), 404);

        // Admin bypass is intentional. The commented "view all media" check was never
        // wired to a seeded permission; enabling it would deny every admin today.
        if (Auth::guard('admin')->check()) {
            return $media->getPath();
        }

        $provider = Auth::guard('provider')->user();
        if ($provider !== null && $media->model()->is($provider)) {
            return $media->getPath();
        }

        abort(404);
    }

    private function resolveChat(Media $media): string
    {
        $auth = $this->resolveAdminOrProvider();

        if (
            $auth === null
            || ! $media->model instanceof ConversationMessage
            || ! ($media->model->sender()->is($auth) || $media->model->receiver()->is($auth))
        ) {
            abort(404);
        }

        return $media->getPath();
    }

    private function resolveAdminOrProvider(): ?Authenticatable
    {
        return Auth::guard('admin')->user() ?? Auth::guard('provider')->user();
    }
}
