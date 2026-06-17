<?php

namespace Modules\Guarantor\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GuarantorStatusHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'guarantor_request_id',
        'actor_id',
        'actor_type',
        'from_status',
        'to_status',
        'reason',
        'notes',
    ];

    public function guarantorRequest(): BelongsTo
    {
        return $this->belongsTo(GuarantorRequest::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
