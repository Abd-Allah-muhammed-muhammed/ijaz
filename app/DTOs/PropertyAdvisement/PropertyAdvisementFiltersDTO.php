<?php

namespace App\DTOs\PropertyAdvisement;

use Illuminate\Http\Request;

final readonly class PropertyAdvisementFiltersDTO
{
    public function __construct(
        public ?string $status,
        public ?string $operation,
        public ?int $propertyTypeId,
        public ?int $cityId,
        public ?int $regionId,
        public ?int $categoryId,
        public ?float $minPrice,
        public ?float $maxPrice,
        public ?float $minArea,
        public ?float $maxArea,
        public ?int $bedroomsCount,
        public ?string $search,
        public int $perPage,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            status: $request->filled('status') ? (string) $request->string('status') : null,
            operation: $request->filled('operation') ? (string) $request->string('operation') : null,
            propertyTypeId: $request->filled('property_type_id') ? $request->integer('property_type_id') : null,
            cityId: $request->filled('city_id') ? $request->integer('city_id') : null,
            regionId: $request->filled('region_id') ? $request->integer('region_id') : null,
            categoryId: $request->filled('category_id') ? $request->integer('category_id') : null,
            minPrice: $request->filled('min_price') ? $request->float('min_price') : null,
            maxPrice: $request->filled('max_price') ? $request->float('max_price') : null,
            minArea: $request->filled('min_area') ? $request->float('min_area') : null,
            maxArea: $request->filled('max_area') ? $request->float('max_area') : null,
            bedroomsCount: $request->filled('bedrooms_count') ? $request->integer('bedrooms_count') : null,
            search: $request->filled('search') ? (string) $request->string('search') : null,
            perPage: $request->integer('per_page', 15),
        );
    }
}
