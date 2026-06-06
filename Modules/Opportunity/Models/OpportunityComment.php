<?php

namespace Modules\Opportunity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Opportunity\Database\Factories\OpportunityCommentFactory;

class OpportunityComment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'opportunity_id',
        'author_type',
        'author_id',
        'body',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function author(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): OpportunityCommentFactory
    {
        return OpportunityCommentFactory::new();
    }
}
