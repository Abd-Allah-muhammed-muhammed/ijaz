<?php

namespace Modules\Catalog\Actions\Bank;

use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\Models\Bank;

class ToggleActiveBankAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    public function handle(Bank $bank): Bank
    {
        $bank = $this->repository->update($bank, [
            'is_active' => ! $bank->is_active,
        ]);

        LookupCache::forgetAllLocales('banks:all');
        LookupCache::forgetAllLocales('banks:dropdown');

        return $bank;
    }
}
