<?php

namespace Modules\Wallet\Support;

final class WalletSearch
{
    /**
     * Normalize a wallet search term: trim whitespace and strip a leading '#'
     * (UI tables render reference numbers as "#uuid").
     */
    public static function normalize(mixed $search): ?string
    {
        if (! is_string($search)) {
            return null;
        }

        $normalized = ltrim(trim($search), '#');

        return $normalized !== '' ? $normalized : null;
    }
}
