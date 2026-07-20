<?php

namespace Modules\Classifieds\Models;

use App\Traits\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Classifieds\Database\Factories\PropertyAdvisementFactory;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PropertyAdvisement extends Model implements HasMedia
{
    /** @use HasFactory<PropertyAdvisementFactory> */
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
        'facade',
        'street_width',
        'street_type',
        'user_type',
        'user_id',
        'age',
        'area',
        'price',
        'bedrooms_count',
        'bathrooms_count',
        'halls_count',
        'phone',
        'license',
        'options',
        'latitude',
        'longitude',
        'address',
        'property_type_id',
        'city_id',
        'region_id',
        'category_id',
        'show_price',
    ];

    protected $casts = [
        'show_price' => 'boolean',
        'price' => 'float',
        'status' => AdvisementStatusEnum::class,
        'operation' => OperationEnum::class,
        'options' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(PropertiyCategory::class, 'category_id');
    }

    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class);
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
        return $query->where('status', AdvisementStatusEnum::PUBLISHED->value);
    }

    protected static function newFactory(): Factory
    {
        return PropertyAdvisementFactory::new();
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

    public function scopeForLease($query)
    {
        return $query->where('operation', 'lease');
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

            return Storage::disk('public')->url($this->image);
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
