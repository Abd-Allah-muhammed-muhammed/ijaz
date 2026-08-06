<?php

namespace Modules\Chat\Http\Resources;

use App\Http\Resources\Api\V1\MediaResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Chat attachment API shape — identical to MediaResource.
 *
 * @deprecated Prefer MediaResource directly. Kept so Chat-domain FQCNs remain
 *             searchable after the MediaLibrary migration.
 *
 * @mixin Media
 */
class ConversationAttachmentResource extends MediaResource {}
