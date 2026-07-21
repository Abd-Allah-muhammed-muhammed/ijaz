<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Contracts\OTPS\HasOTPsContract;
use App\Enums\Users\UserStatusEnum;
use App\Services\Firebase\Contract\InteractWithFirebase;
use App\Services\Firebase\DTO\Target;
use App\Traits\Blockable;
use App\Traits\HasBroadcastChanel;
use App\Traits\HasOTPs;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Geo\Models\Nationality;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Jobs\Concerns\HasJobs;
use Modules\Payment\Traits\HasPayments;
use Modules\Wallet\Traits\HasWallet;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $f_name
 * @property string $l_name
 * @property string|null $image
 * @property string $email
 * @property string|null $language
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $password
 * @property string|null $phone
 * @property int|null $nationality_id
 * @property UserStatusEnum $status
 * @property Carbon|null $blocked_at
 * @property Carbon|null $blocked_until
 * @property string|null $player_id
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $phone_verified_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ConversationMessage> $receivedMessages
 * @property-read Collection<int, ConversationMessage> $sentMessages
 * @property-read Collection<int, GuarantorRequest> $assignedGuarantorRequests
 * @property-read Collection<int, GuarantorRequest> $guarantorRequests
 * @property-read Collection<int, Order> $orders
 * @property-read Nationality|null $nationality
 * @property-read string $image_url
 * @property-read string $name
 * @property-read Collection<int, VerificationCode> $verificationCodes
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereBlockedAt($value)
 * @method static Builder|User whereBlockedUntil($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereFName($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLanguage($value)
 * @method static Builder|User whereLName($value)
 * @method static Builder|User whereLatitude($value)
 * @method static Builder|User whereLongitude($value)
 * @method static Builder|User whereNationalityId($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User wherePhone($value)
 * @method static Builder|User wherePlayerId($value)
 * @method static Builder|User whereRememberToken($value)
 * @method static Builder|User whereStatus($value)
 * @method static Builder|User whereUpdatedAt($value)
 *
 * @mixin  Model
 */
class User extends Authenticatable implements HasConversation, HasOTPsContract, InteractWithFirebase
{
    /** @use HasFactory<UserFactory> */
    use Blockable, HasApiTokens, HasBroadcastChanel, HasFactory, HasJobs, HasOTPs, HasPayments, HasWallet, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'f_name',
        'l_name',
        'image',
        'email',
        'language',
        'latitude',
        'longitude',
        'password',
        'phone',
        'nationality_id',
        'status',
        'blocked_at',
        'blocked_until',
        'player_id',
    ];

    protected string $default_image = 'media/avatars/blank.png';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getType(): string
    {
        return 'user';
    }

    public function deleteImage(): void
    {
        if ($this->image) {
            Storage::disk('public')->delete($this->image);
        }
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function getImageUrl(): string
    {
        return $this->image_url;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function guarantorRequests(): MorphMany
    {
        return $this->morphMany(
            GuarantorRequest::class,
            'requester'
        );
    }

    public function assignedGuarantorRequests(): MorphMany
    {
        return $this->morphMany(
            GuarantorRequest::class,
            'counterparty'
        );
    }

    public function propertyAdvisements(): MorphMany
    {
        return $this->morphMany(PropertyAdvisement::class, 'user');
    }

    public function carAdvisements(): MorphMany
    {
        return $this->morphMany(CarAdvisement::class, 'user');
    }

    public function electronicAdvisements(): MorphMany
    {
        return $this->morphMany(ElectronicAdvisement::class, 'user');
    }

    public function instituteAdvisements(): MorphMany
    {
        return $this->morphMany(InstituteAdvisement::class, 'user');
    }

    public function receivedMessages(): MorphMany
    {
        return $this->morphMany(ConversationMessage::class, 'receiver');
    }

    public function unreadReceivedMessages(): MorphMany
    {
        return $this->morphMany(ConversationMessage::class, 'receiver')->whereNull('read_at');
    }

    public function sentMessages(): MorphMany
    {
        return $this->morphMany(ConversationMessage::class, 'sender');
    }

    public function unreadSentMessages(): MorphMany
    {
        return $this->morphMany(ConversationMessage::class, 'sender')->whereNull('read_at');
    }

    public function routeNotificationForFirebase(): Target
    {
        return new Target('token', $this->player_id);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatusEnum::class,
            'blocked_at' => 'datetime',
            'blocked_until' => 'datetime',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->f_name.' '.$this->l_name);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function () {
            return $this->image ? Storage::disk('public')->url($this->image) : asset($this->default_image);
        });
    }
}
