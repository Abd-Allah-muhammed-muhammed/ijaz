<?php

namespace Modules\Geo\Actions\Nationality;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Throwable;

class CorrectSwappedHiUrNationalityTranslationsAction
{
    /**
     * Nationality ids whose hi/ur name values were confirmed swapped
     * (Arabic-script text stored on locale=hi, Devanagari on locale=ur).
     *
     * @var list<int>
     */
    public const SWAPPED_NATIONALITY_IDS = [
        3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16,
        18, 19, 20, 21, 25, 26, 27, 28, 29, 30, 31, 32,
        35, 36, 37,
    ];

    public const FILIPINO_NATIONALITY_ID = 33;

    public const FILIPINO_HI_NAME = 'फ़िलिपीनी';

    public const FILIPINO_UR_NAME = 'فلپائنی';

    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    /**
     * Idempotent: only swaps a pair when hi is still Arabic-script and ur is
     * still Devanagari; only rewrites Filipino when hi/ur are still the known-bad values.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        DB::transaction(function (): void {
            foreach (self::SWAPPED_NATIONALITY_IDS as $nationalityId) {
                $this->swapHiUrIfStillReversed($nationalityId);
            }

            $this->correctFilipinoIfStillWrong();
        });

        LookupCache::forgetAllLocales('nationalities:all');
    }

    private function swapHiUrIfStillReversed(int $nationalityId): void
    {
        $hi = $this->repository->findTranslation($nationalityId, 'hi');
        $ur = $this->repository->findTranslation($nationalityId, 'ur');

        if ($hi === null || $ur === null) {
            return;
        }

        if (! $this->isArabicScript($hi->name) || ! $this->isDevanagari($ur->name)) {
            return;
        }

        $originalHiName = $hi->name;
        $this->repository->saveTranslationName($hi, $ur->name);
        $this->repository->saveTranslationName($ur, $originalHiName);
    }

    private function correctFilipinoIfStillWrong(): void
    {
        $hi = $this->repository->findTranslation(self::FILIPINO_NATIONALITY_ID, 'hi');
        $ur = $this->repository->findTranslation(self::FILIPINO_NATIONALITY_ID, 'ur');

        if ($hi === null || $ur === null) {
            return;
        }

        if ($this->isDevanagari($hi->name) && $this->isArabicScript($ur->name)) {
            return;
        }

        $this->repository->saveTranslationName($hi, self::FILIPINO_HI_NAME);
        $this->repository->saveTranslationName($ur, self::FILIPINO_UR_NAME);
    }

    private function isDevanagari(string $value): bool
    {
        return (bool) preg_match('/\p{Devanagari}/u', $value);
    }

    private function isArabicScript(string $value): bool
    {
        return (bool) preg_match('/\p{Arabic}/u', $value);
    }
}
