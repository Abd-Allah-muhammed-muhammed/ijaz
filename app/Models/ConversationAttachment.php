<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ConversationAttachment extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    protected $appends = [
        'url',
    ];

    protected $hidden = [
        'store',
    ];

    protected $fillable = [
        'conversation_message_id', 'type', 'filename', 'path', 'store',
    ];

    public static function booted(): void
    {
        parent::booted();
        static::deleted(function (ConversationAttachment $chatAttachment) {
            if ($chatAttachment->path && Storage::disk($chatAttachment->store)->exists($chatAttachment->path)) {
                Storage::disk($chatAttachment->store)->delete($chatAttachment->path);
            }
        });
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class);
    }

    protected function url(): Attribute
    {
        return Attribute::get(function () {
            return \Storage::disk($this->store)->url($this->path);
        });
    }
}
