<?php

namespace Modules\Classifieds\Models;

use App\Models\City;
use App\Models\Region;
use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ElectronicAdvisement extends Model implements HasMedia
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
        'image',
        'status',
        'condition',
        'color',
        'price',
        'show_price',
        'phone',
        'address',
        'latitude',
        'longitude',
        'user_type',
        'user_id',
        'device_category_id',
        'city_id',
        'region_id',
        'options',
    ];

    protected $casts = [
        'show_price' => 'boolean',
        'price' => 'float',
        'status' => AdvisementStatusEnum::class,
        'condition' => ElectronicConditionEnum::class,
        'options' => 'array',
    ];

    public function deviceCategory(): BelongsTo
    {
        return $this->belongsTo(DeviceCategory::class);
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
