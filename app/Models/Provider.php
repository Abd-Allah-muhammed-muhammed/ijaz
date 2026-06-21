<?php

namespace App\Models;

use App\Enums\Providers\ProviderStatusEnum;
use App\Services\Firebase\Contract\InteractWithFirebase;
use App\Services\Firebase\DTO\Target;
use App\Traits\Blockable;
use App\Traits\HasBroadcastChanel;
use App\Traits\HasJobs;
use App\Traits\HasReviews;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Chat\Contracts\HasConversation;
use Modules\Payment\Traits\HasPayments;
use Modules\Wallet\Traits\HasWallet;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;
use Storage;

/**
 * @property string $name
 * @property string $code
 * @property string $iban
 * @property string $about
 * @property string $logo
 * @property string $commercial_record
 * @property ?string $commercial_record_url
 * @property ?string $logo_url
 * @property string $tax_number
 * @property string $phone
 * @property string $email
 * @property string $website
 * @property string $address
 * @property float $latitude
 * @property float $longitude
 * @property int $provider_type_id
 * @property int $owner_id
 * @property Employee $owner
 * @property ProviderType $provider_type
 * @property Employee[] $employees
 * @property int $city_id
 * @property City $city
 * @property int $region_id
 * @property Region $region
 * @property ProviderStatusEnum $status
 * @property string $language
 */
class Provider extends Authenticatable implements HasConversation, HasMedia, InteractWithFirebase
{
    use Blockable, HasBroadcastChanel, HasJobs, HasPayments, HasReviews, HasRoles, HasWallet, InteractsWithMedia, Notifiable;

    protected string $default_image = 'media/avatars/blank.png';

    protected $fillable = [
        'name', 'code', 'iban', 'about', 'logo', 'tax_number', 'phone', 'email', 'website', 'address',
        'latitude', 'longitude', 'provider_type_id', 'region_id', 'city_id', 'status', 'password', 'language', 'blocked_at', 'blocked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function providerType(): BelongsTo
    {
        return $this->belongsTo(ProviderType::class, 'provider_type_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function categorySkills(): HasMany
    {
        return $this->hasMany(CategorySkill::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->BelongsToMany(Skill::class, 'category_skill', 'provider_id', 'skill_id')
            ->withPivot('category_id')
            ->using(CategorySkill::class);
    }

    public function providerCategories(): HasMany
    {
        return $this->hasMany(ProviderCategory::class, 'provider_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'provider_category', 'provider_id', 'category_id')
            ->using(ProviderCategory::class);
    }

    public function deleteLogo(): void
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            Storage::disk('public')->delete($this->logo);
        }
    }

    public function deleteCommercialRecord(): void
    {
        if ($this->commercial_record && Storage::disk('public')->exists($this->commercial_record)) {
            Storage::disk('public')->delete($this->commercial_record);
        }
    }

    public function getType(): string
    {
        return 'provider';
    }

    public function getImageUrl(): string
    {
        return $this->logoUrl;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'provider_id');
    }

    public function orderOffers(): HasMany
    {
        return $this->hasMany(OrderOffer::class, 'provider_id');
    }

    public function routeNotificationForFirebase(): Target
    {
        return new Target('token', null);
    }

    protected function commercialRecordUrl(): Attribute
    {
        return Attribute::get(function () {
            if (empty($this->commercial_record)) {
                return null;
            }

            return Storage::disk('public')->url($this->commercial_record);
        });
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(function () {
            if (empty($this->logo)) {
                return asset($this->default_image);
            }

            return Storage::disk('public')->url($this->logo) ?: asset($this->default_image);
        });
    }

    protected function paddedCode(): Attribute
    {
        return Attribute::get(fn () => $this->code ? str_pad($this->code, 11, '0', STR_PAD_LEFT) : null);
    }

    protected function casts(): array
    {
        return [
            'status' => ProviderStatusEnum::class,
            'password' => 'hashed',
            'blocked_at' => 'datetime',
            'blocked_until' => 'datetime',
        ];
    }
}
