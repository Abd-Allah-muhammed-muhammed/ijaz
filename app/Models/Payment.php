<?php

namespace App\Models;

use App\Enums\Payment\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Lib\Payment\DTOs\PaymentResponse;

/**
 * @template TUser
 * @template TProduct
 *
 * @property string $id
 * @property string $transaction_id
 * @property string $driver
 * @property array|null $request
 * @property array|null $response
 * @property float $amount
 * @property PaymentStatusEnum $status
 * @property string|null $message
 * @property string|null $url
 * @property string $user_id
 * @property class-string<TUser> $user_type
 * @property string $product_id
 * @property class-string<TProduct> $product_type
 * @property-read TUser $user
 * @property-read TProduct $product
 */
class Payment extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'transaction_id', 'driver', 'request', 'response', 'amount', 'status', 'message', 'url', 'user_id',
        'user_type', 'product_id', 'product_type',
    ];

    protected $casts = [
        'request' => 'array',
        'response' => 'array',
        'status' => PaymentStatusEnum::class,
    ];

    public function toPaymentResponse(): PaymentResponse
    {
        return new PaymentResponse(
            status: $this->status,
            transactionId: $this->transaction_id,
            driver: $this->driver,
            url: $this->url,
            payable: $this->amount > 0,
            data: [],
            message: $this->message
        );
    }

    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function product(): MorphTo
    {
        return $this->morphTo();
    }
}
