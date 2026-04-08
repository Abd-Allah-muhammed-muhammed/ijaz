<?php

namespace App\Models;

use App\Services\Chat\Contracts\HasConversation;
use App\Traits\HasBroadcastChanel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Storage;

class Admin extends Authenticatable implements HasConversation
{
    /** @use HasFactory<UserFactory> */
    use HasBroadcastChanel, HasFactory, HasRoles, Notifiable;

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

    protected string $default_image = 'media/avatars/blank.png';

    protected $appends = [
        'image_url',
    ];

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }

    }

    public function getType(): string
    {
        return 'admin';
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
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
        return Attribute::get(function () {
            return $this->image ? Storage::disk('public')->url($this->image) : asset($this->default_image);
        });
    }

    public function getImageUrl(): string
    {
        return $this->image_url;
    }
}
