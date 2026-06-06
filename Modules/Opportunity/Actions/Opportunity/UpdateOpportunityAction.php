<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\DTOs\OpportunityData;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class UpdateOpportunityAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, OpportunityData $data, Request $request): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $request) {
            $opportunity = $this->opportunities->update(
                $opportunity,
                OpportunityData::persistenceFromValidated($request->validated())
            );

            if ($request->hasFile('files')) {
                $opportunity->addMultipleMediaFromRequest(['files'])->each(function ($media) {
                    $media->toMediaCollection('files');
                });
            }

            $opportunity->load(['author', 'region.translation', 'city.translation', 'media']);

            return $opportunity;
        });
    }
}
