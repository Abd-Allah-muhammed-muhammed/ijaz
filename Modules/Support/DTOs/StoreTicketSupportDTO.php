<?php

namespace Modules\Support\DTOs;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final readonly class StoreTicketSupportDTO
{
    public function __construct(
        public string $userType,
        public int $userId,
        public ?string $operationType,
        public ?string $operationId,
        public string $title,
        public string $message,
    ) {}

    /**
     * @param  array{operation_type?: string|null, operation_id?: string|null, title: string, message: string}  $validated
     */
    public static function fromValidated(array $validated, Model $user): self
    {
        return new self(
            userType: $user::class,
            userId: $user->getKey(),
            operationType: match ($validated['operation_type'] ?? null) {
                'order' => Order::class,
                null => null,
                default => throw new RuntimeException('invalid operation type'),
            },
            operationId: $validated['operation_id'] ?? null,
            title: $validated['title'],
            message: $validated['message'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_type' => $this->userType,
            'user_id' => $this->userId,
            'operation_type' => $this->operationType,
            'operation_id' => $this->operationId,
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
