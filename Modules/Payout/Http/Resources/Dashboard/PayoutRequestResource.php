<?php

namespace Modules\Payout\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Payout\Models\PayoutRequest;

/** @mixin PayoutRequest */
class PayoutRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $transferProof = $this->getFirstMedia('transfer_proof');

        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'status' => $this->status->toArray(),
            'gateway_reference' => $this->gateway_reference,
            'transfer_proof_url' => $transferProof?->getAvailableFullUrl(['webp']),
            'failure_reason' => $this->failure_reason,
            'operation_type' => str($this->operation_type)->afterLast('\\')->toString(),
            'recipient' => $this->whenLoaded('recipient', fn ($recipient) => [
                'name' => $recipient->name,
            ]),
            'maker_admin' => $this->whenLoaded('makerAdmin', fn ($admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
            ]),
            'submitted_by_admin' => $this->whenLoaded('submittedByAdmin', fn ($admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
            ]),
            'processed_by_admin' => $this->whenLoaded('processedByAdmin', fn ($admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
            ]),
            'created_at' => $this->created_at,
        ];
    }
}
