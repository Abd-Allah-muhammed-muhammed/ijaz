<?php

namespace App\Models;

use App\Enums\Jobs\JobTypeEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class JobOffer extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'user_id', 'user_type', 'title', 'description', 'expired_at', 'contact_number',
        'city_id', 'region_id', 'nationality_id', 'type', 'expected_salary',
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function jobOfferSkills(): HasMany
    {
        return $this->hasMany(JobOfferSkill::class, 'job_offer_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_offer_skill', 'job_offer_id', 'skill_id')->using(JobOfferSkill::class);
    }

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'type' => JobTypeEnum::class,
        ];
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('expired_at', '>', now());
    }
}
