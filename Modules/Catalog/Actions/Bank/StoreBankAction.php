<?php

namespace Modules\Catalog\Actions\Bank;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\DTOs\StoreBankDTO;
use Modules\Catalog\Models\Bank;
use Throwable;

class StoreBankAction
{
    public function __construct(
        private readonly BankRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreBankDTO $dto): Bank
    {
        $bank = DB::transaction(function () use ($dto): Bank {
            $bank = $this->repository->create([
                'is_active' => $dto->isActive,
                'translations' => $dto->translations,
            ]);

            if ($dto->logo !== null) {
                $bank->addMedia($dto->logo)->toMediaCollection('logo');
            }

            return $bank->load(['translation', 'translations', 'media']);
        });

        LookupCache::forgetAllLocales('banks:all');
        LookupCache::forgetAllLocales('banks:dropdown');

        return $bank;
    }
}
