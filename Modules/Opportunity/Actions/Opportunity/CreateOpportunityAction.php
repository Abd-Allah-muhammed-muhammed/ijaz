<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\DTOs\OpportunityData;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Notifications\OpportunityCreatedConfirmationNotification;
use Throwable;

class CreateOpportunityAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(OpportunityData $data, Model $author, Request $request): Opportunity
    {
        return DB::transaction(function () use ($data, $author, $request) {
            $attributes = [
                ...$data->toPersistenceArray(),
                'author_type' => $author::class,
                'author_id' => $author->getKey(),
                'status' => OpportunityStatusEnum::PendingAdmin,
            ];

            // Omit expires_at → default listing window (null reads as 00:00:00 on mobile).
            if ($attributes['expires_at'] === null) {
                $attributes['expires_at'] = now()->addDays(Opportunity::DEFAULT_DURATION_DAYS);
            }

            $opportunity = $this->opportunities->create($attributes);

            if ($request->hasFile('files')) {
                $opportunity->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection('files');
                });
            }

            $opportunity->load(['author', 'region.translation', 'city.translation', 'media']);

            $author->notify(new OpportunityCreatedConfirmationNotification($opportunity));

            return $opportunity;
        });
    }
}
