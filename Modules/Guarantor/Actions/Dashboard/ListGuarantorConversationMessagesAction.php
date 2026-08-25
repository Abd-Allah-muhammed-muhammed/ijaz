<?php

namespace Modules\Guarantor\Actions\Dashboard;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;

class ListGuarantorConversationMessagesAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantors,
    ) {}

    public function handle(GuarantorRequest $guarantorRequest, int $perPage = 15, ?string $search = null): ?LengthAwarePaginator
    {
        return $this->guarantors->paginateConversationMessages($guarantorRequest, $perPage, $search);
    }
}
