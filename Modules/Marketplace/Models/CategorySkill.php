<?php

namespace Modules\Marketplace\Models;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategorySkill extends Pivot
{
    protected $fillable = [
        'category_id',
        'skill_id',
        'provider_id',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'skill_id' => 'integer',
        'provider_id' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
