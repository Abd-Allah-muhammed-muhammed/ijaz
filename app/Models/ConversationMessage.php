<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ConversationMessage extends Model implements HasMedia
{
    use HasUuids , InteractsWithMedia;

    protected $keyType = 'string';

    protected $fillable = [
        'sender_id', 'sender_type', 'content', 'read_at', 'read_by_id', 'read_by_type',
        'conversation_id', 'receiver_id', 'receiver_type', 'has_attachments', 'deleted_at',
    ];

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

    public function attachments(): HasMany
    {
        return $this->hasMany(ConversationAttachment::class);
    }

    public function lastAttachment(): HasOne
    {
        return $this->hasOne(ConversationAttachment::class)->ofMany()->latest();
    }

    public function readBy(): MorphTo
    {
        return $this->morphTo('read_by');
    }
}
