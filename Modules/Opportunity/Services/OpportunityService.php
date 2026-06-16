<?php

namespace Modules\Opportunity\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Opportunity\Actions\Opportunity\CreateOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\DeleteOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\RenewOpportunityAction;
use Modules\Opportunity\Actions\Opportunity\UpdateOpportunityAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\DTOs\OpportunityData;
use Modules\Opportunity\Models\Opportunity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class OpportunityService
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
        private readonly CreateOpportunityAction $createAction,
        private readonly UpdateOpportunityAction $updateAction,
        private readonly DeleteOpportunityAction $deleteAction,
        private readonly RenewOpportunityAction $renewAction,
    ) {}

    public function listPublic(int $perPage = 10): LengthAwarePaginator
    {
        return $this->opportunities->listPublic($perPage);
    }

    public function listByActor(Model $actor, int $perPage = 10): LengthAwarePaginator
    {
        return $this->opportunities->listByActor($actor, $perPage);
    }

    public function loadForShow(Opportunity $opportunity): Opportunity
    {
        $opportunity->load([
            'author',
            'region.translation',
            'city.translation',
            'acceptedOffer.author',
            'media',
        ]);
        $opportunity->loadCount(['offers', 'comments']);

        return $opportunity;
    }

    /**
     * @throws Throwable
     */
    public function create(OpportunityData $data, Model $author, Request $request): Opportunity
    {
        return $this->createAction->handle($data, $author, $request);
    }

    /**
     * @throws Throwable
     */
    public function update(Opportunity $opportunity, OpportunityData $data, Request $request): Opportunity
    {
        return $this->updateAction->handle($opportunity, $data, $request);
    }

    /**
     * @throws Throwable
     */
    public function delete(Opportunity $opportunity): void
    {
        $this->deleteAction->handle($opportunity);
    }

    /**
     * @throws Throwable
     */
    public function renew(Opportunity $opportunity, ?Carbon $expiresAt = null): Opportunity
    {
        return $this->renewAction->handle($opportunity, $expiresAt);
    }

    public function deleteMedia(Opportunity $opportunity, Media $media): void
    {
        $media->delete();
    }
}
