<?php

namespace Modules\Catalog\Models;

use App\Support\HasNormalizedAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTranslation extends Model
{
    use HasNormalizedAttributes;

    public $timestamps = false;

    protected $fillable = ['name', 'normalized_name', 'locale', 'bank_id'];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    protected function getHasNormalizedAttributesMap(): array
    {
        return [
            'name' => 'normalized_name',
        ];
    }
}
