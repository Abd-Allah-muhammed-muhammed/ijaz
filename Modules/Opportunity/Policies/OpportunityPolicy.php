<?php

namespace Modules\Opportunity\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Policies\Concerns\AuthorizesOpportunityAuthor;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OpportunityPolicy
{
    use AuthorizesOpportunityAuthor;

    public function update(Model $user, Opportunity $opportunity): bool
    {
        return $this->isAuthor($user, $opportunity);
    }

    public function renew(Model $user, Opportunity $opportunity): bool
    {
        $isOwner = $opportunity->author_type === $user::class
            && $opportunity->author_id === $user->getKey();

        return $isOwner && $opportunity->status->isIn([
            OpportunityStatusEnum::New,
            OpportunityStatusEnum::OfferAccepted,
            OpportunityStatusEnum::Expired,
        ]);
    }

    public function delete(Model $user, Opportunity $opportunity): bool
    {
        return $opportunity->status === OpportunityStatusEnum::New;
    }

    public function deleteMedia(Model $user, Opportunity $opportunity): bool
    {
        return $opportunity->status === OpportunityStatusEnum::New;
    }

    public function removeMedia(Model $user, Opportunity $opportunity, Media $media): bool
    {
        return $media->model()->is($opportunity);
    }

    public function acceptOffer(Model $user, Opportunity $opportunity, OpportunityOffer $offer): bool
    {
        return $opportunity->status === OpportunityStatusEnum::New
            && $offer->opportunity_id === $opportunity->id;
    }

    public function rejectOffer(Model $user, Opportunity $opportunity, OpportunityOffer $offer): bool
    {
        return $offer->opportunity_id === $opportunity->id;
    }

    public function chat(Model $user, Opportunity $opportunity): bool
    {
        if (! in_array($opportunity->status, [OpportunityStatusEnum::OfferAccepted, OpportunityStatusEnum::InProgress], true)) {
            return false;
        }

        if ($this->isAuthor($user, $opportunity)) {
            return true;
        }

        $opportunity->loadMissing('acceptedOffer');

        if ($opportunity->acceptedOffer === null) {
            return false;
        }

        return $opportunity->acceptedOffer->author_type === $user::class
            && $opportunity->acceptedOffer->author_id === $user->getKey();
    }
}
