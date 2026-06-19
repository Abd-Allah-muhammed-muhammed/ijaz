<?php

namespace App\Models;

use App\Enums\OperationStatusEnum;
use App\Enums\SupportTickets\TicketSupportStatusEnum;
use Database\Factories\TicketSupportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_type
 * @property int $user_id
 * @property string $operation_type
 * @property int $operation_id
 * @property string $title
 * @property string $message
 * @property OperationStatusEnum $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TicketSupport extends Model
{
    /** @use HasFactory<TicketSupportFactory> */
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'operation_type',
        'operation_id',
        'title',
        'message',
        'status',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function operation(): MorphTo
    {
        return $this->morphTo();
    }

    public function chat(): MorphOne
    {
        return $this->MorphOne(Conversation::class, 'operation');
    }

    protected function casts(): array
    {
        return [
            'status' => TicketSupportStatusEnum::class,
        ];
    }
}
