<?php

namespace Modules\Opportunity\DTOs;

use Illuminate\Support\Carbon;
use Modules\Opportunity\Http\Requests\StoreOpportunityRequest;
use Modules\Opportunity\Http\Requests\UpdateOpportunityRequest;

final readonly class OpportunityData
{
    public function __construct(
        public string $title,
        public string $description,
        public ?float $budget,
        public ?int $region_id,
        public ?int $city_id,
        public ?string $phone,
        public ?string $email,
        public ?Carbon $expires_at,
    ) {}

    public static function fromRequest(StoreOpportunityRequest|UpdateOpportunityRequest $request): self
    {
        $validated = $request->validated();

        return new self(
            title: (string) ($validated['title'] ?? ''),
            description: (string) ($validated['description'] ?? ''),
            budget: isset($validated['budget']) ? (float) $validated['budget'] : null,
            region_id: isset($validated['region_id']) ? (int) $validated['region_id'] : null,
            city_id: isset($validated['city_id']) ? (int) $validated['city_id'] : null,
            phone: $validated['phone'] ?? null,
            email: $validated['email'] ?? null,
            expires_at: isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function persistenceFromValidated(array $validated): array
    {
        $data = [];

        foreach (['title', 'description', 'budget', 'region_id', 'city_id', 'phone', 'email'] as $key) {
            if (array_key_exists($key, $validated)) {
                $data[$key] = $validated[$key];
            }
        }

        if (array_key_exists('expires_at', $validated)) {
            $data['expires_at'] = $validated['expires_at'] !== null
                ? Carbon::parse($validated['expires_at'])
                : null;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'budget' => $this->budget,
            'region_id' => $this->region_id,
            'city_id' => $this->city_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'expires_at' => $this->expires_at,
        ];
    }
}
