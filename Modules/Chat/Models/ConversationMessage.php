<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ConversationMessage extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia, SoftDeletes;

    protected $keyType = 'string';

    protected $fillable = [
        'sender_id', 'sender_type', 'content', 'read_at', 'read_by_id', 'read_by_type',
        'conversation_id', 'receiver_id', 'receiver_type', 'has_attachments', 'deleted_at',
    ];

    public function registerMediaCollections(): void
    {
        // Default disk matches legacy conversation_attachments.store = 'public'.
        // Future S3 cutover: change useDisk(...) (or setAttachmentStorage on the service) only.
        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): MorphTo
    {
        return $this->morphTo('sender')->withTrashed();
    }

    public function receiver(): MorphTo
    {
        return $this->morphTo('receiver')->withTrashed();
    }

    /**
     * @deprecated Legacy custom-table relation. New attachments use MediaLibrary
     *             (`media` / getMedia('attachments')). Kept for the one-time
     *             chat:migrate-attachments-to-medialibrary backfill command.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ConversationAttachment::class);
    }

    public function readBy(): MorphTo
    {
        return $this->morphTo('read_by');
    }

    public function lastMediaAttachment(): ?Media
    {
        return $this->getMedia('attachments')->last();
    }
}
