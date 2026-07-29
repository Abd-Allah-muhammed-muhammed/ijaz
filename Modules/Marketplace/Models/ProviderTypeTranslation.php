<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderTypeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'provider_type_id', 'locale', 'description'];

    public function providerType(): BelongsTo
    {
        return $this->belongsTo(ProviderType::class);
    }
}
