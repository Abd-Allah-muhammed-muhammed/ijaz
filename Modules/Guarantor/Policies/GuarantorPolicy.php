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
            && $request->status->is(GuarantorStatusEnum::PendingAdmin);
    }

    public function delete(Model $user, GuarantorRequest $request): bool
    {
        return $this->isRequester($user, $request)
            && $request->status->is(GuarantorStatusEnum::PendingAdmin);
    }

    public function deleteMedia(Model $user, GuarantorRequest $request): bool
    {
        return $this->isRequester($user, $request)
            && $request->status->is(GuarantorStatusEnum::PendingAdmin);
    }

    public function accept(Model $user, GuarantorRequest $request): bool
    {
        return $this->isCounterparty($user, $request)
            && $request->status->is(GuarantorStatusEnum::ApprovedByAdmin);
    }

    public function reject(Model $user, GuarantorRequest $request): bool
    {
        return $this->isCounterparty($user, $request)
            && $request->status->is(GuarantorStatusEnum::ApprovedByAdmin);
    }

    public function pay(Model $user, GuarantorRequest $request): bool
    {
        return $this->isCounterparty($user, $request)
            && $request->status->is(GuarantorStatusEnum::Accepted);
    }

    public function end(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request)
            && $request->status->isIn([
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ]);
    }

    public function dispute(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request)
            && $request->status->isIn([
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ]);
    }

    public function withdraw(Model $user, GuarantorRequest $request): bool
    {
        if (! $this->isParty($user, $request)) {
            return false;
        }

        if ($request->status->is(GuarantorStatusEnum::ApprovedByAdmin)) {
            return $this->isRequester($user, $request);
        }

        return $request->status->is(GuarantorStatusEnum::Accepted);
    }

    public function chat(Model $user, GuarantorRequest $request): bool
    {
        return $this->isParty($user, $request)
            && $request->status->isIn([
                GuarantorStatusEnum::Accepted,
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
                GuarantorStatusEnum::Disputed,
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
