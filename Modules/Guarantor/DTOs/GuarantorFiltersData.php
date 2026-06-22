<?php

namespace Modules\Guarantor\DTOs;

use Illuminate\Http\Request;
use Modules\Guarantor\Enums\GuarantorTypeEnum;

final readonly class GuarantorFiltersData
{
    /**
     * @param  array<string>|null  $statuses
     */
    public function __construct(
        public ?array $statuses = null,
        public ?GuarantorTypeEnum $type = null,
        public ?string $role = null,
        public ?string $search = null,
        public ?string $date_from = null,
        public ?string $date_to = null,
        public int $per_page = 10,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $statuses = null;

        if ($request->has('status')) {
            $raw = $request->input('status');

            if (is_array($raw)) {
                $statuses = $raw;
            } elseif (is_string($raw) && str_contains($raw, ',')) {
                $statuses = array_map('trim', explode(',', $raw));
            } elseif (is_string($raw) && $raw !== '') {
                $statuses = [$raw];
            }
        }

        return new self(
            statuses: $statuses,
            type: $request->enum('type', GuarantorTypeEnum::class),
            role: $request->string('role')->value() ?: null,
            search: $request->string('search')->value() ?: null,
            date_from: $request->string('date_from')->value() ?: null,
            date_to: $request->string('date_to')->value() ?: null,
            per_page: $request->integer('per_page', 10),
        );
    }
}
