<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderTypeTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'provider_type_id', 'locale', 'description'];

    /**
     * Get the provider type that owns the translation.
     */
    public function providerType(): BelongsTo
    {
        return $this->belongsTo(ProviderType::class);
    }
}
