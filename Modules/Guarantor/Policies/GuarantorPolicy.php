<?php

namespace Modules\Guarantor\Policies;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorPolicy
{
    public function update(Model $user, GuarantorRequest $request): bool
    {
        return $this->isRequester($user, $request)
            && $request->status->is(GuarantorStatusEnum::New);
    }

    public function delete(Model $user, GuarantorRequest $request): bool
    {
        return $this->isRequester($user, $request)
            && $request->status->is(GuarantorStatusEnum::New);
    }

    public function deleteMedia(Model $user, GuarantorRequest $request): bool
    {
        return $this->isRequester($user, $request)
            && $request->status->is(GuarantorStatusEnum::New);
    }

    public function updateStatus(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request);
    }

    public function pay(Model $user, GuarantorRequest $request): bool
    {
        return $this->isCounterparty($user, $request)
            && $request->status->is(GuarantorStatusEnum::Approved);
    }

    public function end(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request)
            && $request->status->isIn([
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ]);
    }

    public function cancel(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request)
            && $request->status->isIn([
                GuarantorStatusEnum::New,
                GuarantorStatusEnum::Approved,
            ]);
    }

    public function chat(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request)
            && $request->status->isIn([
                GuarantorStatusEnum::Approved,
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ]);
    }

    public function view(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request);
    }

    private function isRequester(Model $user, GuarantorRequest $request): bool
    {
        return $request->requester_type === $user::class
            && (string) $request->requester_id === (string) $user->getKey();
    }

    private function isCounterparty(Model $user, GuarantorRequest $request): bool
    {
        return $request->counterparty_type === $user::class
            && (string) $request->counterparty_id === (string) $user->getKey();
    }

    private function isParty(Model $user, GuarantorRequest $request): bool
    {
        return $this->isRequester($user, $request)
            || $this->isCounterparty($user, $request);
    }
}
