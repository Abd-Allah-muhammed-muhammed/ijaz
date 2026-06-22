<?php

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\User;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Gateways\PayTabsGateway;
use Modules\Payment\Models\Payment;

function createPaymentFor(Model $owner, Model $product, array $attributes = []): Payment
{
    return Payment::factory()
        ->forProduct($product, $owner)
        ->create($attributes);
}

/**
 * @return array{user: User, provider: Provider, order: Order, offer: OrderOffer}
 */
function createOrderPaymentContext(float $price = 500.0): array
{
    $user = createWalletUser();
    $provider = createWalletProvider();
    $category = CategoryFactory::new()->create(['icon' => 'media/test-category.png']);

    $order = Order::query()->create([
        'title' => 'Test Order',
        'description' => 'Test order description',
        'budget_start' => 400,
        'budget_end' => 600,
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'category_id' => $category->id,
        'price' => $price,
        'status' => OrderStatusEnum::OfferProvided,
        'user_fees' => 25,
        'provider_fees' => 50,
    ]);

    $offer = OrderOffer::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'category_id' => $category->id,
        'price' => $price,
        'description' => 'Test offer',
        'status' => OfferStatusEnum::Accepted,
    ]);

    $order->update(['accepted_offer_id' => $offer->id]);

    return compact('user', 'provider', 'order', 'offer');
}

function mockPayTabsGateway(PaymentVerifyResult $result): void
{
    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('verify')->andReturn($result);

    app()->instance(PayTabsGateway::class, $gateway);
}
