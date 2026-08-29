<?php

namespace Modules\Chat\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Optional ChatTypeHandler capability: label message senders with a
 * conversation-scoped party role (e.g. Guarantor requester/counterparty).
 */
interface ResolvesMessagePartyRole
{
    /**
     * @return 'requester'|'counterparty'|null Null for admin/system/unrelated senders.
     */
    public function resolvePartyRole(Model $sender, Model $operation): ?string;
}
