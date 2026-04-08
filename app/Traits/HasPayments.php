<?php

namespace App\Traits;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPayments
{
    // Define relationships and methods related to payments here

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'user');
    }
}
