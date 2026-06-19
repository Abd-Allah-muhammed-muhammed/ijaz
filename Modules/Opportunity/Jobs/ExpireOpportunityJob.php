<?php

namespace Modules\Opportunity\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Opportunity\Actions\Opportunity\ExpireOpportunityAction;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class ExpireOpportunityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Opportunity $opportunity) {}

    public function handle(ExpireOpportunityAction $action): void
    {
        $action->handle($this->opportunity);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ExpireOpportunityJob failed', [
            'opportunity_id' => $this->opportunity->id,
            'error' => $e->getMessage(),
        ]);
    }
}
