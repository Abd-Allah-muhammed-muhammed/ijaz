<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Permission\Traits\HasRoles;
use Storage;

/**
 * @property string $name
 * @property string $id_image
 * @property string $phone
 * @property string $email
 * @property string $address
 * @property float $latitude
 * @property float $longitude
 * @property int $provider_id
 * @property Provider $provider
 * @property ?Provider $company
 */
class Employee extends Model
{
    use HasRoles;

    protected $fillable = [
        'name', 'id_image', 'phone', 'email', 'address', 'latitude', 'longitude', 'provider_id',
        'profile_picture', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Provider::class, 'owner_id');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'online' => 'boolean',
        ];
    }

    protected function idImageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->id_image) {
                return null;
            }

            return Storage::disk('public')->url($this->id_image);
        });
    }

    protected function ProfileImageUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->profile_picture) {
                return null;
            }

            return Storage::disk('public')->url($this->profile_picture);
        });
    }

    public function deleteIdImage(): void
    {
        if ($this->id_image && Storage::disk('public')->exists($this->id_image)) {
            Storage::disk('public')->delete($this->id_image);
        }

    }

    public function deleteProfilePicture(): void
    {
        if ($this->profile_picture && Storage::disk('public')->exists($this->profile_picture)) {
            Storage::disk('public')->delete($this->profile_picture);
        }
    }
}
