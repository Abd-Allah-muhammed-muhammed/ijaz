<?php

namespace Modules\Jobs\DTOs;

use App\Enums\Jobs\JobTypeEnum;
use Illuminate\Support\Carbon;

final readonly class StoreJobDTO
{
    /**
     * @param  list<int>  $skillIds
     */
    public function __construct(
        public string $title,
        public string $description,
        public float $expectedSalary,
        public Carbon $expiredAt,
        public string $contactNumber,
        public int $cityId,
        public int $regionId,
        public int $nationalityId,
        public JobTypeEnum $type,
        public array $skillIds = [],
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        $skillIds = $validated['skills'] ?? [];

        return new self(
            title: (string) $validated['title'],
            description: (string) $validated['description'],
            expectedSalary: (float) $validated['expected_salary'],
            expiredAt: Carbon::parse($validated['expired_at'])->setTimezone('UTC'),
            contactNumber: (string) $validated['contact_number'],
            cityId: (int) $validated['city_id'],
            regionId: (int) $validated['region_id'],
            nationalityId: (int) $validated['nationality_id'],
            type: JobTypeEnum::from((int) $validated['type']),
            skillIds: array_map('intval', is_array($skillIds) ? $skillIds : []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'expected_salary' => $this->expectedSalary,
            'expired_at' => $this->expiredAt,
            'contact_number' => $this->contactNumber,
            'city_id' => $this->cityId,
            'region_id' => $this->regionId,
            'nationality_id' => $this->nationalityId,
            'type' => $this->type,
        ];
    }
}
