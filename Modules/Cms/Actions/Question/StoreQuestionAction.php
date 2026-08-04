<?php

namespace Modules\Cms\Actions\Question;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Cms\DTOs\StoreQuestionDTO;
use Modules\Cms\Models\Question;
use Throwable;

class StoreQuestionAction
{
    public function __construct(
        private readonly QuestionRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreQuestionDTO $dto): Question
    {
        $question = DB::transaction(fn (): Question => $this->repository->create([
            'translations' => $dto->translations,
        ]));

        LookupCache::forgetAllLocales('questions:all');

        return $question;
    }
}
