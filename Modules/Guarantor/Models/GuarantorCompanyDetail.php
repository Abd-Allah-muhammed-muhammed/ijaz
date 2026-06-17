<?php

namespace Modules\Guarantor\Models;

use App\Models\City;
use App\Models\Region;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class GuarantorCompanyDetail extends Model implements HasMedia
{
    use HasUuids, InteractsWithMedia;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'guarantor_request_id',
        'company_name',
        'commercial_register',
        'region_id',
        'city_id',
        'authorized_name',
        'authorized_id_number',
        'authorization_type',
        'requester_account_holder',
        'requester_iban',
        'counterparty_account_holder',
        'counterparty_iban',
    ];

    public function guarantorRequest(): BelongsTo
    {
        return $this->belongsTo(GuarantorRequest::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('authorized_id')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('contracts')
            ->useDisk('public');

        $this->addMediaCollection('iban_certificates')
            ->useDisk('public');

        $this->addMediaCollection('company_documents')
            ->useDisk('public');
    }

    protected function casts(): array
    {
        return [
            'authorization_type' => AuthorizationTypeEnum::class,
            'requester_iban' => 'encrypted',
            'requester_account_holder' => 'encrypted',
            'counterparty_iban' => 'encrypted',
            'counterparty_account_holder' => 'encrypted',
        ];
    }
}
