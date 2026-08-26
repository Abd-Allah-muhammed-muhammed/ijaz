<?php

namespace Modules\Guarantor\Notifications;

use App\Notifications\DomainNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Support\GuarantorFirebaseNotifiable;
use Modules\Payment\Models\Payment;

class GuarantorPaymentReceivedNotification extends DomainNotification implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use GuarantorFirebaseNotifiable;

    public function __construct(
        public GuarantorRequest $guarantorRequest,
        public Payment $payment,
        public ?GuarantorInstallment $installment = null,
    ) {}

    protected function titleKey(): string
    {
        return 'guarantor_payment_received';
    }

    protected function bodyKey(): string
    {
        return 'guarantor_payment_received_body';
    }

    protected function payload(): array
    {
        $payload = [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'type' => $this->guarantorRequest->type->value,
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
        ];

        if ($this->installment !== null) {
            $payload['installment_id'] = $this->installment->id;
            $payload['installment_order'] = $this->installment->order;
        }

        return $payload;
    }

    protected function broadcastData(object $notifiable): array
    {
        return $this->firebaseData($notifiable);
    }

    protected function firebaseData(object $notifiable): array
    {
        $data = [
            'guarantor_request_id' => $this->guarantorRequest->id,
            'payment_id' => (string) $this->payment->id,
        ];

        if ($this->installment !== null) {
            $data['installment_id'] = $this->installment->id;
        }

        return $data;
    }

    protected function sendsFirebase(object $notifiable): bool
    {
        return $this->guarantorPartyReceivesFirebase($notifiable);
    }

    public function broadcastType(): string
    {
        return 'guarantor payment received';
    }
}
