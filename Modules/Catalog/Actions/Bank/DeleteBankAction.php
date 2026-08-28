<?php

namespace Modules\Catalog\Actions\Bank;

use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\Models\Bank;

class DeleteBankAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    public function handle(Bank $bank): void
    {
        $this->repository->delete($bank);

        LookupCache::forgetAllLocales('banks:all');
        LookupCache::forgetAllLocales('banks:dropdown');
    }
}
