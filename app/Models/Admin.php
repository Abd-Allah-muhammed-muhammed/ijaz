<?php

namespace App\Models;

use App\Services\Firebase\Contract\InteractWithFirebase;
use App\Support\HasBroadcastChannel;
use App\Support\HasStoredFileUrl;
use App\Traits\HasDeviceTokens;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Modules\Chat\Contracts\HasConversation;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements HasConversation, InteractWithFirebase
{
    /** @use HasFactory<UserFactory> */
    use HasBroadcastChannel, HasDeviceTokens, HasFactory, HasRoles, HasStoredFileUrl, Notifiable;

    public string $guard_name = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'job',
        'image',
        'email_verified_at',
        'online',
        'language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'root',
    ];

    protected $appends = [
        'image_url',
    ];

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk($this->storedFileDisk())->delete($this->image);
        }

    }

    public function getType(): string
    {
        return 'admin';
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'root' => 'boolean',
            'online' => 'boolean',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->storedFileUrl($this->image));
    }

    protected function storedFileDisk(): string
    {
        return 'public';
    }

    protected function defaultImagePlaceholder(): ?string
    {
        return asset('media/avatars/blank.png');
    }

    public function getImageUrl(): string
    {
        return $this->image_url;
    }
}
