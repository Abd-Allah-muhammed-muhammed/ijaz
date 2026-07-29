<?php

namespace Modules\Wallet\Actions\TopUp;

use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\TopUpRequest;

class CancelTopUpRequestAction
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $repository,
    ) {}

    public function handle(TopUpRequest $topUpRequest): void
    {
        if (! $topUpRequest->status->isPending()) {
            throw new WalletException('Only pending top-up requests can be cancelled.');
        }

        $this->repository->delete($topUpRequest);
    }
}
