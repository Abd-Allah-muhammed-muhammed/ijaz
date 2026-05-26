<?php

namespace Modules\Classifieds\DTOs;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final readonly class ElectronicAdvisementDTO
{
    /**
     * @param  array<mixed>|null  $options
     * @param  array<int, UploadedFile>|null  $files
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $condition,
        public int $deviceCategoryId,
        public int $cityId,
        public int $regionId,
        public float $price,
        public bool $showPrice,
        public ?int $electronicBrandId = null,
        public ?string $modelName = null,
        public ?string $storage = null,
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
            condition: (string) $validated['condition'],
            deviceCategoryId: (int) $validated['device_category_id'],
            cityId: (int) $validated['city_id'],
            regionId: (int) $validated['region_id'],
            price: (float) $validated['price'],
            showPrice: (bool) ($validated['show_price'] ?? false),
            electronicBrandId: isset($validated['electronic_brand_id']) ? (int) $validated['electronic_brand_id'] : null,
            modelName: $validated['model_name'] ?? null,
            storage: $validated['storage'] ?? null,
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
            'condition' => $this->condition,
            'device_category_id' => $this->deviceCategoryId,
            'electronic_brand_id' => $this->electronicBrandId,
            'model_name' => $this->modelName,
            'storage' => $this->storage,
            'city_id' => $this->cityId,
            'region_id' => $this->regionId,
            'price' => $this->price,
            'show_price' => $this->showPrice,
            'color' => $this->color,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'options' => $this->options,
        ];
    }
}
