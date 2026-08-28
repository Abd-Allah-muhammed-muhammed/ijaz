<?php

namespace Modules\Guarantor\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Bank;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
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
        'requester_bank_id',
        'counterparty_account_holder',
        'counterparty_iban',
        'counterparty_bank_id',
        'terms_notes',
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

    public function requesterBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'requester_bank_id');
    }

    public function counterpartyBank(): BelongsTo
    {
        return $this->belongsTo(Bank::class, 'counterparty_bank_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('authorized_id')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('contracts')
            ->useDisk('public');

        $this->addMediaCollection('requester_iban_certificate')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('requester_cr_file')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('requester_articles_of_association')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('requester_national_address_file')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('agency_authorization_document')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('counterparty_iban_certificate')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('counterparty_cr_file')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('counterparty_articles_of_association')
            ->useDisk('public')
            ->singleFile();

        $this->addMediaCollection('counterparty_national_address_file')
            ->useDisk('public')
            ->singleFile();

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
