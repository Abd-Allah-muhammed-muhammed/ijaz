<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Conversation
 *
 * @template T of Model
 *
 * @mixin Eloquent
 *
 * @property string $id
 * @property string $user1_id
 * @property string $user1_type
 * @property string $user2_id
 * @property string $user2_type
 * @property string|null $last_message_id
 * @property Carbon|null $last_message_at
 * @property string|null $operation_id
 * @property string|null $operation_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ?T $operation
 * @property-read Collection<int, ConversationMessage> $messages
 * @property-read int|null $messages_count
 * @property-read ConversationMessage|null $lastMassage
 * @property-read Model|Eloquent $user1
 * @property-read Model|Eloquent $user2
 *
 * @method static Builder|Conversation newModelQuery()
 * @method static Builder|Conversation newQuery()
 * @method static Builder|Conversation query()
 * @method static Builder|Conversation withCountUnreadMessagesFor(Model $model)
 * @method static Builder|Conversation whereCreatedAt($value)
 * @method static Builder|Conversation whereId($value)
 * @method static Builder|Conversation whereLastMessageAt($value)
 * @method static Builder|Conversation whereLastMessageId($value)
 * @method static Builder|Conversation whereOperationId($value)
 * @method static Builder|Conversation whereOperationType($value)
 * @method static Builder|Conversation whereUpdatedAt($value)
 * @method static Builder|Conversation whereUser1Id($value)
 * @method static Builder|Conversation whereUser1Type($value)
 * @method static Builder|Conversation whereUser2Id($value)
 */
class Conversation extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'user1_id', 'user1_type', 'user2_id', 'user2_type', 'last_message_id', 'last_message_at',
        'operation_id', 'operation_type',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function lastMassage(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class, 'last_message_id');
    }

    public function user1(): MorphTo
    {
        return $this->morphTo('user1')->withTrashed();
    }

    public function user2(): MorphTo
    {
        return $this->morphTo('user2')->withTrashed();
    }

    public function operation(): MorphTo
    {
        return $this->morphTo();
    }

    #[Scope]
    protected function withCountUnreadMessagesFor(Builder $query, Model $model): Builder
    {
        return $query->withCount([
            'messages as unread_messages_count' => function (\Illuminate\Contracts\Database\Eloquent\Builder $query) use ($model) {
                $query->whereMorphedTo('receiver', $model)->whereNull('read_at');
            },
        ]);
    }

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }
}
