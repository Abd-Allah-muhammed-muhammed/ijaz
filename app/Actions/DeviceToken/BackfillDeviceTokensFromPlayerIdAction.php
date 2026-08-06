<?php

namespace App\Actions\DeviceToken;

use App\DTOs\DeviceToken\BackfillDeviceTokensResult;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillDeviceTokensFromPlayerIdAction
{
    private const CHUNK_SIZE = 200;

    public function handle(bool $dryRun = false): BackfillDeviceTokensResult
    {
        if (! Schema::hasColumn('users', 'player_id')) {
            return new BackfillDeviceTokensResult(migrated: 0, skipped: 0, conflicts: 0);
        }

        if (! Schema::hasTable('device_tokens')) {
            return new BackfillDeviceTokensResult(migrated: 0, skipped: 0, conflicts: 0);
        }

        $migrated = 0;
        $skipped = 0;
        $conflicts = 0;
        $userClass = (new User)->getMorphClass();
        $now = now();

        DB::table('users')
            ->whereNotNull('player_id')
            ->where('player_id', '!=', '')
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($users) use (
                $dryRun,
                $userClass,
                $now,
                &$migrated,
                &$skipped,
                &$conflicts,
            ): void {
                $tokens = $users->pluck('player_id')->unique()->values()->all();

                $existingByToken = DeviceToken::query()
                    ->whereIn('token', $tokens)
                    ->get(['token', 'tokenable_type', 'tokenable_id'])
                    ->keyBy('token');

                $rows = [];

                foreach ($users as $user) {
                    $token = $user->player_id;
                    $existing = $existingByToken->get($token);

                    if ($existing !== null) {
                        $ownedByThisUser = $existing->tokenable_type === $userClass
                            && (int) $existing->tokenable_id === (int) $user->id;

                        if ($ownedByThisUser) {
                            $skipped++;

                            continue;
                        }

                        $conflicts++;

                        continue;
                    }

                    // Same token already queued in this chunk for an earlier user.
                    if (isset($rows[$token])) {
                        $conflicts++;

                        continue;
                    }

                    $migrated++;

                    if ($dryRun) {
                        // Reserve the token in-memory so later users in this chunk
                        // count as conflicts the same way a real insert would.
                        $rows[$token] = true;

                        continue;
                    }

                    $rows[$token] = [
                        'tokenable_type' => $userClass,
                        'tokenable_id' => $user->id,
                        'token' => $token,
                        'platform' => null,
                        'device_name' => null,
                        'last_used_at' => $user->updated_at ?? $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($dryRun || $rows === []) {
                    return;
                }

                DB::table('device_tokens')->insert(array_values($rows));
            });

        return new BackfillDeviceTokensResult(
            migrated: $migrated,
            skipped: $skipped,
            conflicts: $conflicts,
        );
    }
}
