<?php

namespace App\Console\Commands;

use App\Actions\Auth\Provider\PruneExpiredProviderRegistrationUploadsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('auth:prune-provider-registration-uploads')]
#[Description('Delete provider registration temp uploads older than the configured retention window')]
class PruneProviderRegistrationUploadsCommand extends Command
{
    public function handle(PruneExpiredProviderRegistrationUploadsAction $action): int
    {
        $deleted = $action->handle();

        $this->info("Pruned {$deleted} expired provider registration upload(s).");

        return self::SUCCESS;
    }
}
