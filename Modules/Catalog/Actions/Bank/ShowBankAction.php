<?php

namespace Modules\Catalog\Actions\Bank;

use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\Models\Bank;

class ShowBankAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    public function handle(Bank $bank): Bank
    {
        return $this->repository->loadForEdit($bank);
    }
}
