<?php

namespace Modules\Classifieds\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class CarAdvisementDTO
{
    /**
     * @param  array<int, UploadedFile>|null  $files
     * @param  array<mixed>|null  $options
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $operation,
        public string $usageStatus,
        public int $carBrandId,
        public int $carTypeId,
        public ?int $carCategoryId,
        public int $year,
        public float $price,
        public bool $showPrice,
        public int $cityId,
        public int $regionId,
        public ?int $mileage = null,
        public ?string $transmission = null,
        public ?string $fuelType = null,
        public ?string $engineSize = null,
        public ?string $color = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?array $options = null,
        public ?array $files = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $validated = self::validatedInput($request);

        return new self(
            title: (string) $validated['title'],
            description: (string) $validated['description'],
            operation: (string) $validated['operation'],
            usageStatus: (string) $validated['usage_status'],
            carBrandId: (int) $validated['car_brand_id'],
            carTypeId: (int) $validated['car_type_id'],
            carCategoryId: isset($validated['car_category_id']) ? (int) $validated['car_category_id'] : null,
            year: (int) $validated['year'],
            price: (float) $validated['price'],
            showPrice: (bool) ($validated['show_price'] ?? false),
            cityId: (int) $validated['city_id'],
            regionId: (int) $validated['region_id'],
            mileage: isset($validated['mileage']) ? (int) $validated['mileage'] : null,
            transmission: $validated['transmission'] ?? null,
            fuelType: $validated['fuel_type'] ?? null,
            engineSize: $validated['engine_size'] ?? null,
            color: $validated['color'] ?? null,
            phone: $validated['phone'] ?? null,
            address: $validated['address'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            options: $validated['options'] ?? null,
            files: $request->hasFile('files') ? $request->file('files') : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function validatedInput(Request $request): array
    {
        if (method_exists($request, 'validated')) {
            /** @var array<string, mixed> $validated */
            $validated = $request->validated();

            return $validated;
        }

        /** @var array<string, mixed> $all */
        $all = $request->all();

        return $all;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPersistenceArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'operation' => $this->operation,
            'usage_status' => $this->usageStatus,
            'car_brand_id' => $this->carBrandId,
            'car_type_id' => $this->carTypeId,
            'car_category_id' => $this->carCategoryId,
            'year' => $this->year,
            'price' => $this->price,
            'show_price' => $this->showPrice,
            'city_id' => $this->cityId,
            'region_id' => $this->regionId,
            'mileage' => $this->mileage,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuelType,
            'engine_size' => $this->engineSize,
            'color' => $this->color,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'options' => $this->options,
        ];
    }
}
