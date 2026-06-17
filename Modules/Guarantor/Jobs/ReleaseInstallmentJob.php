<?php

namespace Modules\Guarantor\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Models\GuarantorInstallment;
use Throwable;

class ReleaseInstallmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public GuarantorInstallment $installment,
        public string $trigger = 'payment',
    ) {}

    public function handle(ReleaseInstallmentAction $action): void
    {
        $action->handle($this->installment, $this->trigger);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ReleaseInstallmentJob failed', [
            'installment_id' => $this->installment->id,
            'error' => $e->getMessage(),
        ]);
    }
}
