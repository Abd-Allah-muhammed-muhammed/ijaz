<?php

namespace Modules\Orders\Console\Commands;

use Illuminate\Console\Command;
use Modules\Orders\Actions\Offer\ExpireStalePendingOrderOfferAction;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Models\OrderOffer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orders:expire-pending-offers')]
class ExpirePendingOrderOffersCommand extends Command
{
    protected $description = 'Expire stale pending order offers past the configured offer-expiry window';

    public function handle(
        OrderOfferRepositoryInterface $offers,
        ExpireStalePendingOrderOfferAction $expireOffer,
    ): int {
        $days = max(1, (int) app('settings')->get('order_offer_expiry_days', 7));
        $createdBefore = now()->subDays($days);
        $expired = 0;

        $offers->getPendingCreatedBefore($createdBefore)->each(function (OrderOffer $offer) use ($expireOffer, &$expired): void {
            if ($expireOffer->handle($offer)) {
                $expired++;
            }
        });

        $this->info("Expired {$expired} pending order offers older than {$days} days.");

        return self::SUCCESS;
    }
}
