<?php

namespace Modules\Payment\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Payment\Models\Payment;

trait HasPayments
{
    // Define relationships and methods related to payments here

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'user');
    }
}
