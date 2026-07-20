<?php

namespace Modules\Classifieds\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Classifieds\Database\Factories\CarAdvisementFactory;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CarAdvisement extends Model implements HasMedia
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
        'operation',
        'usage_status',
        'user_type',
        'user_id',
        'car_brand_id',
        'car_type_id',
        'car_category_id',
        'year',
        'mileage',
        'transmission',
        'fuel_type',
        'engine_size',
        'color',
        'price',
        'show_price',
        'phone',
        'latitude',
        'longitude',
        'address',
        'city_id',
        'region_id',
        'options',
    ];

    protected $casts = [
        'show_price' => 'boolean',
        'price' => 'float',
        'status' => AdvisementStatusEnum::class,
        'operation' => OperationEnum::class,
        'usage_status' => UsageStatusEnum::class,
        'options' => 'array',
        'year' => 'integer',
        'mileage' => 'integer',
    ];

    public function carBrand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarType::class);
    }

    public function carCategory(): BelongsTo
    {
        return $this->belongsTo(CarCategory::class);
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

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeForSale($query)
    {
        return $query->where('operation', 'sale');
    }

    public function scopeForRent($query)
    {
        return $query->where('operation', 'rent');
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

    protected static function newFactory(): Factory
    {
        return CarAdvisementFactory::new();
    }
}
