<?php

namespace App\Models;

use App\Enums\GuaranteeRequest\GuaranteeRequestStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuaranteeRequest extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'user_type',
        'provider_id',
        'provider_type',
        'description',
        'status',
        'amount',
        'fees',
        'title',
    ];

    protected $attributes = [
        'status' => GuaranteeRequestStatusEnum::New, // Default status
        'fees' => 10,
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function provider(): MorphTo
    {
        return $this->morphTo();
    }

    public function conversation(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(\App\Models\Conversation::class, 'operation');
    }

    protected function casts(): array
    {
        return [
            'status' => GuaranteeRequestStatusEnum::class,
        ];
    }

    protected function total(): Attribute
    {
        return Attribute::get(static fn ($value, array $attributes) => $value ?? (($attributes['amount'] ?? 0) + ($attributes['fees'] ?? 0)));
    }
}
