<?php

namespace Modules\Classifieds\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class InstituteAdvisement extends Model implements HasMedia
{
    use HasFactory, HasNormalizedAttributes, InteractsWithMedia;

    protected string $default_image = 'media/avatars/blank.png';

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'title',
        'normalized_title',
        'description',
        'normalized_description',
        'goals',
        'payment_notes',
        'image',
        'status',
        'price',
        'discounted_price',
        'type',
        'study_type',
        'study_level',
        'days_count',
        'hours_count',
        'phone',
        'website',
        'registration_url',
        'course_url',
        'quality_url',
        'address',
        'latitude',
        'longitude',
        'registration_start',
        'registration_end',
        'study_start',
        'study_end',
        'user_type',
        'user_id',
        'specialization_id',
        'city_id',
        'region_id',
        'options',
    ];

    protected $casts = [
        'status' => AdvisementStatusEnum::class,
        'type' => InstituteTypeEnum::class,
        'study_type' => StudyTypeEnum::class,
        'study_level' => StudyLevelEnum::class,
        'price' => 'float',
        'discounted_price' => 'float',
        'days_count' => 'integer',
        'hours_count' => 'integer',
        'registration_start' => 'date',
        'registration_end' => 'date',
        'study_start' => 'date',
        'study_end' => 'date',
        'options' => 'array',
    ];

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(Specialization::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive($query)
    {
        return $query->where('status', AdvisementStatusEnum::PUBLISHED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', AdvisementStatusEnum::PUBLISHED);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk('public');
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->image) {
                return asset($this->default_image);
            }
            if (str_contains($this->image, 'http')) {
                return $this->image;
            }

            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('public');

            return $disk->url($this->image);
        });
    }

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'title' => 'normalized_title',
            'description' => 'normalized_description',
        ];
    }
}
