<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\DTOs\OpportunityData;
use Modules\Opportunity\Models\Opportunity;
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
            $opportunity = $this->opportunities->create([
                ...$data->toPersistenceArray(),
                'author_type' => $author::class,
                'author_id' => $author->getKey(),
            ]);

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
