<?php

namespace App\Console\Commands;

use App\Contracts\Auth\OtpSessionRepositoryInterface;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'auth:prune-expired-otp-sessions')]
class PruneExpiredOtpSessionsCommand extends Command
{
    protected $description = 'Delete expired otp_sessions rows';

    public function handle(OtpSessionRepositoryInterface $otpSessionRepository): int
    {
        $deleted = $otpSessionRepository->deleteExpired();

        $this->info("Pruned {$deleted} expired OTP session(s).");

        return self::SUCCESS;
    }
}
