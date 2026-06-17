<?php

namespace Modules\Guarantor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Notifications\InstallmentDueNotification;
use Modules\Guarantor\Notifications\InstallmentOverdueNotification;
use Throwable;

class NotifyOverdueInstallmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 300;

    public function __construct(public GuarantorInstallment $installment) {}

    public function handle(LogGuarantorStatusHistoryAction $logStatusHistory): void
    {
        $installment = $this->installment->fresh()->load([
            'guarantorRequest.requester',
            'guarantorRequest.counterparty',
        ]);

        $request = $installment->guarantorRequest;
        $counterparty = $request->counterparty;
        $requester = $request->requester;

        $daysOverdue = (int) $installment->due_date->startOfDay()->diffInDays(now()->startOfDay());

        if ($daysOverdue >= 14) {
            AutoReleaseInstallmentJob::dispatch($installment)
                ->onQueue('guarantor');

            return;
        }

        if ($daysOverdue >= 3) {
            if ($request->status->isNot(GuarantorStatusEnum::Overdue)) {
                $fromStatus = $request->status->value;

                $request->update([
                    'status' => GuarantorStatusEnum::Overdue,
                    'overdue_at' => now(),
                ]);

                $logStatusHistory->handle(
                    request: $request,
                    actor: $counterparty,
                    fromStatus: $fromStatus,
                    toStatus: GuarantorStatusEnum::Overdue->value,
                    notes: "Installment #{$installment->order} overdue by {$daysOverdue} days",
                );
            }

            $counterparty->notify(new InstallmentOverdueNotification($installment));
            $requester->notify(new InstallmentOverdueNotification($installment));

            $installment->update(['overdue_notified_at' => now()]);

            return;
        }

        if ($daysOverdue >= 1) {
            $counterparty->notify(new InstallmentDueNotification($installment));
            $installment->update(['overdue_notified_at' => now()]);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('NotifyOverdueInstallmentJob failed', [
            'installment_id' => $this->installment->id,
            'error' => $e->getMessage(),
        ]);
    }
}
