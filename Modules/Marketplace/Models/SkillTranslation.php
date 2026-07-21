<?php

namespace Modules\Marketplace\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillTranslation extends Model
{
    use HasNormalizedAttributes;

    public $timestamps = false;

    protected $fillable = ['title', 'normalized_title', 'locale', 'skill_id'];

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'title' => 'normalized_title',
        ];
    }
}
