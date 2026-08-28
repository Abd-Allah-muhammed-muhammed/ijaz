<?php

namespace Modules\Catalog\Actions\Bank;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\DTOs\UpdateBankDTO;
use Modules\Catalog\Models\Bank;
use Throwable;

class UpdateBankAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Bank $bank, UpdateBankDTO $dto): Bank
    {
        $bank = DB::transaction(function () use ($bank, $dto): Bank {
            $bank = $this->repository->update($bank, [
                'is_active' => $dto->isActive,
                'translations' => $dto->translations,
            ]);

            if ($dto->logo !== null) {
                $bank->clearMediaCollection('logo');
                $bank->addMedia($dto->logo)->toMediaCollection('logo');
            }

            return $bank->load(['translation', 'translations', 'media']);
        });

        LookupCache::forgetAllLocales('banks:all');
        LookupCache::forgetAllLocales('banks:dropdown');

        return $bank;
    }
}
