<?php

namespace Modules\Payout\DTOs;

use Illuminate\Database\Eloquent\Model;

final readonly class CreatePayoutRequestData
{
    public function __construct(
        public Model $operation,
        public Model $recipient,
        public float $amount,
    ) {}
}
