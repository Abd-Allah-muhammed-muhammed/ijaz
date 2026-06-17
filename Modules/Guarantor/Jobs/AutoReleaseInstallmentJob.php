<?php

namespace Modules\Guarantor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Throwable;

class AutoReleaseInstallmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public GuarantorInstallment $installment) {}

    public function handle(ReleaseInstallmentAction $action): void
    {
        $this->installment->refresh();

        if ($this->installment->status->isNot(InstallmentStatusEnum::Paid)) {
            return;
        }

        $action->handle($this->installment, 'auto_release');
    }

    public function failed(Throwable $e): void
    {
        Log::error('AutoReleaseInstallmentJob failed', [
            'installment_id' => $this->installment->id,
            'error' => $e->getMessage(),
        ]);
    }
}
