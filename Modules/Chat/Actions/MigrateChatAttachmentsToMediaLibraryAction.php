<?php

namespace Modules\Chat\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Chat\Models\ConversationAttachment;
use Modules\Chat\Models\ConversationMessage;

final class MigrateChatAttachmentsToMediaLibraryAction
{
    /**
     * @return array{migrated: int, skipped: int, missing: int, total: int}
     */
    public function handle(bool $dryRun = false): array
    {
        $migrated = 0;
        $skipped = 0;
        $missing = 0;

        $query = ConversationAttachment::query()->orderBy('created_at');

        $total = (clone $query)->count();

        $query->each(function (ConversationAttachment $attachment) use ($dryRun, &$migrated, &$skipped, &$missing): void {
            $message = ConversationMessage::query()->find($attachment->conversation_message_id);

            if ($message === null) {
                $missing++;

                return;
            }

            $alreadyMigrated = $message->getMedia('attachments')->contains(
                fn ($media) => $media->getCustomProperty('legacy_attachment_id') === $attachment->id
            );

            if ($alreadyMigrated) {
                $skipped++;

                return;
            }

            $disk = $attachment->store ?: 'public';

            if (! Storage::disk($disk)->exists($attachment->path)) {
                $missing++;

                return;
            }

            if ($dryRun) {
                $migrated++;

                return;
            }

            $message
                ->addMediaFromDisk($attachment->path, $disk)
                ->preservingOriginal()
                ->usingFileName($attachment->filename)
                ->usingName(pathinfo($attachment->filename, PATHINFO_FILENAME) ?: $attachment->filename)
                ->withCustomProperties([
                    'legacy_attachment_id' => $attachment->id,
                ])
                ->toMediaCollection('attachments', $disk);

            if (! $message->has_attachments) {
                $message->forceFill(['has_attachments' => true])->save();
            }

            $migrated++;
        });

        return compact('migrated', 'skipped', 'missing', 'total');
    }
}
